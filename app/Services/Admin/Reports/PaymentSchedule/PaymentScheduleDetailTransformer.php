<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentScheduleDetailTransformer
{
    public function transformForView(Collection $data): Collection
    {
        // 1) Cada fila es una cuota por vencer; no deduplicar por participante para no perder cuotas de meses distintos
        $mapped = $data->map(function ($item) {
            // Calcular precio real del participante (base - descuentos + ajustes)
            $participantPrice = $this->calculateParticipantPrice($item->participant_id, $item->program_id);
            $finalPrice = (float) ($participantPrice['final_price'] ?? 0);

            // Desglose de descuentos (becas) y liberados
            $discountBreakdown = $this->calculateDiscountBreakdown($item->participant_id, $item->program_id);
            $scholarshipsAmount = (float) ($discountBreakdown['normal_discounts'] ?? 0);
            $releasedAmount = (float) ($discountBreakdown['released_amount'] ?? 0);

            // Total pagado por la orden (pagos aprobados)
            $totalPaidAmount = $this->calculateTotalPaid($item->participant_id, $item->program_id);

            // Estadísticas de cuotas (todos los planes)
            $installmentStats = $this->calculateInstallmentStats($item->participant_id, $item->program_id);
            $totalInstallments = $installmentStats['total_installments'];
            $paidInstallments = $installmentStats['paid_installments'];

            // Estado de cuotas en formato X/Y, 0/0 si no existen cuotas
            $installmentsDisplay = $totalInstallments > 0
                ? ($paidInstallments . '/' . $totalInstallments)
                : '0/0';

            // Saldo por cobrar = precio final - total pagado (no negativo)
            $remainingBalance = max($finalPrice - $totalPaidAmount, 0);

            // Abonos + becas (pagos efectuados + descuentos normales)
            $abonosBecas = $totalPaidAmount + $scholarshipsAmount;

            // Tipo de documento real del participante (RUT/PASAPORTE)
            $documentTypeCode = $this->normalizeDocumentType($item->participant_document_type ?? null);

            return (object) [
                // Ejecutivo y programa
                'sales_executive_name' => $item->sales_executive_name,
                'program_code' => $item->program_code,
                'program_name' => $item->program_name,

                // Alumno
                'participant_name' => $this->toTitleCase(trim($item->participant_name)),
                'participant_document' => $item->document_number,

                // Documento
                'document_type_code' => $documentTypeCode,

                // Programa
                'program_departure_date' => $item->program_departure_date,

                // Cuota por vencer (esta fila)
                'next_due_date' => $item->due_date ?? null,
                'next_due_year_month' => ($item->due_date ? (new \DateTime($item->due_date))->format('Y-m') : null),
                'installment_number' => $item->installment_number ?? null,
                'installment_amount' => (float) ($item->installment_amount ?? 0),

                // Montos
                'program_price' => $finalPrice,
                'scholarships_amount' => $scholarshipsAmount,
                'abonos_becas_amount' => $abonosBecas,
                'released_amount' => $releasedAmount,
                'total_pending_amount' => $remainingBalance,

                // Estado de cuotas
                'paid_installments_display' => $installmentsDisplay,
                'participant_status' => $item->participant_status ?? null,
            ];
        });

        // 2) Ordenar por próxima fecha de vencimiento ascendente
        return $mapped->sortBy(function ($row) {
            $key = $row->next_due_date ?? null;
            return $key ? (new \DateTime($key))->format('Y-m-d') : '9999-12-31';
        })->values();
    }

    private function calculateTotalPaid(int $participantId, int $programId): float
    {
        return (float) DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.participant_id', $participantId)
            ->where('orders.program_id', $programId)
            ->whereIn('payments.status', ['completed', 'approved'])
            ->sum('payments.amount');
    }

    private function calculateInstallmentStats(int $participantId, int $programId): array
    {
        $planIds = DB::table('installment_plans')
            ->where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->pluck('id');

        if ($planIds->isEmpty()) {
            return [
                'total_installments' => 0,
                'paid_installments' => 0,
            ];
        }

        $stats = DB::table('installments')
            ->whereIn('installment_plan_id', $planIds->all())
            ->selectRaw('COUNT(*) as total_installments, COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_installments')
            ->first();

        return [
            'total_installments' => (int) ($stats->total_installments ?? 0),
            'paid_installments' => (int) ($stats->paid_installments ?? 0),
        ];
    }

    private function calculateParticipantPrice(int $participantId, int $programId): array
    {
        try {
            $participant = \App\Models\Participant::find($participantId);
            $program = \App\Models\Program::find($programId);

            if (!$participant || !$program) {
                return [
                    'base_price' => 0,
                    'adjustments' => 0,
                    'discounts' => 0,
                    'final_price' => 0,
                ];
            }

            return \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        } catch (\Exception $e) {
            return [
                'base_price' => 0,
                'adjustments' => 0,
                'discounts' => 0,
                'final_price' => 0,
            ];
        }
    }

    private function calculateDiscountBreakdown(int $participantId, int $programId): array
    {
        try {
            $pp = DB::table('participant_program')
                ->where('participant_id', $participantId)
                ->where('program_id', $programId)
                ->first();

            if (!$pp) {
                return [
                    'normal_discounts' => 0,
                    'released_amount' => 0,
                ];
            }

            $discounts = DB::table('participant_program_discounts')
                ->where('participant_program_id', $pp->id)
                ->get();

            $normalDiscounts = 0.0;
            $releasedAmount = 0.0;

            foreach ($discounts as $discount) {
                if ($discount->discount_type === 'released') {
                    if ($discount->percent == 100) {
                        $basePrice = $this->getBasePrice($participantId, $programId);
                        $releasedAmount += $basePrice;
                    } else {
                        $releasedAmount += (float) ($discount->amount ?? 0);
                    }
                } else {
                    if ($discount->percent && $discount->percent > 0) {
                        $basePrice = $this->getBasePrice($participantId, $programId);
                        $normalDiscounts += ($basePrice * $discount->percent) / 100;
                    }
                    if ($discount->amount && $discount->amount > 0) {
                        $normalDiscounts += (float) $discount->amount;
                    }
                }
            }

            return [
                'normal_discounts' => (float) $normalDiscounts,
                'released_amount' => (float) $releasedAmount,
            ];
        } catch (\Exception $e) {
            return [
                'normal_discounts' => 0,
                'released_amount' => 0,
            ];
        }
    }

    private function getBasePrice(int $participantId, int $programId): float
    {
        $pp = DB::table('participant_program')
            ->where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->first();

        if ($pp && $pp->individual_price) {
            return (float) $pp->individual_price;
        }

        $programPrice = DB::table('programs')
            ->where('id', $programId)
            ->value('trip_price');

        return (float) ($programPrice ?? 0);
    }

    private function normalizeDocumentType(?string $docName): string
    {
        if (!$docName) {
            return 'N/A';
        }
        $upper = mb_strtoupper(trim($docName), 'UTF-8');
        if ($upper === 'RUT' || $upper === 'RUN') {
            return 'RUT';
        }
        if ($upper === 'PASAPORTE' || $upper === 'PASSPORT') {
            return 'PASAPORTE';
        }
        return $upper; // devolver tal cual para otros tipos
    }

    private function toTitleCase(string $text): string
    {
        if ($text === '') {
            return '';
        }
        // Normalizar y aplicar title case respetando acentos
        $lower = mb_strtolower($text, 'UTF-8');
        return preg_replace_callback('/\b[\p{L}]/u', function ($m) {
            return mb_strtoupper($m[0], 'UTF-8');
        }, $lower);
    }
}
