<?php

namespace App\Services\Admin\Reports\Executives;

use App\Helpers\ParticipantPriceHelper;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExecutivesPartialAccountService
{
    /**
     * Normaliza un documento removiendo puntos, guiones y espacios
     */
    private function normalizeDocument(?string $document): string
    {
        if (!$document) {
            return '';
        }
        return preg_replace('/[.\-\s]/', '', $document);
    }

    /**
     * Retorna datos para Estado de Cuenta Parcial (apoderados)
     */
    public function getPartialAccounts(array $filters): array
    {
        $page = (int)($filters['page'] ?? 1);
        $perPage = 25;

        // Soportar tanto programId como programCode para compatibilidad
        $programId = $filters['programId'] ?? null;
        $programCode = $filters['programCode'] ?? null;
        // Fechas opcionales - si no se proporcionan, se muestra todo el histórico
        $dateFrom = $filters['dateFrom'] ?? null;
        $dateTo = $filters['dateTo'] ?? null;
        // Búsqueda por documento/RUT
        $documentSearch = !empty($filters['documentSearch']) ? $this->normalizeDocument($filters['documentSearch']) : null;

        $items = collect([]);

        // Buscar program_course por ID o por código
        $programCourse = null;
        $programCourseId = null;
        if ($programId) {
            $programCourse = \App\Models\ProgramCourse::find($programId);
            $programCourseId = $programCourse?->id;
        } elseif ($programCode) {
            $programCourse = \App\Models\ProgramCourse::where('code', $programCode)->first();
            $programCourseId = $programCourse?->id;
        }

        // Si hay programa O hay búsqueda por documento, ejecutar la consulta
        if ($programCourse || $documentSearch) {
            // Usar la misma lógica de consulta que ConsolidatedPayments
            $query = DB::table('participant_program as pp')
                ->leftJoin('participants as p', 'pp.participant_id', '=', 'p.id')
                ->leftJoin('program_courses as pgc', 'pgc.id', '=', 'pp.program_id')
                ->leftJoin('programs as pr', 'pr.id', '=', 'pgc.program_id')
                ->leftJoin('orders as o', function($join) {
                    $join->on('o.participant_id', '=', 'p.id')
                         ->on('o.program_id', '=', 'pgc.id');
                })
                ->leftJoin('orders_detail as od', 'o.id', '=', 'od.order_id')
                ->leftJoin('payments as pay', 'o.id', '=', 'pay.order_id')
                ->leftJoin('payment_gateways as pg', 'pay.payment_gateway_id', '=', 'pg.id')
                ->leftJoin('installment_plans as ip', 'o.id', '=', 'ip.order_id')
                ->leftJoin('installments as inst', 'ip.id', '=', 'inst.installment_plan_id')
                ->leftJoin('participant_program_discounts as ppd', 'pp.id', '=', 'ppd.participant_program_id')
                ->when($programCourseId, function ($q) use ($programCourseId) {
                    $q->where('pp.program_id', $programCourseId);
                })
                ->when($documentSearch, function ($q) use ($documentSearch) {
                    $q->whereRaw("REPLACE(REPLACE(p.document_number, '.', ''), '-', '') LIKE ?", ["%{$documentSearch}%"]);
                })
                ->when(!empty($filters['status']), function ($q) use ($filters) {
                    $isActive = $filters['status'] === 'active';
                    $q->where('pp.is_active', $isActive);
                })
                ->groupBy('pp.id', 'p.id', 'pr.id', 'p.first_name', 'p.second_name', 'p.first_last_name', 'p.second_last_name', 'pp.individual_price', 'pgc.code', 'pp.is_active', 'pp.program_id')
                ->select([
                    'pp.id as participant_program_id',
                    'p.id as participant_id',
                    'p.first_name',
                    'p.second_name',
                    'p.first_last_name',
                    'p.second_last_name',
                    'pp.individual_price',
                    'pgc.code as program_code',
                    'pp.program_id as program_course_id',
                    'pp.is_active',
                    DB::raw('COALESCE(pp.individual_price, 0) as price'),
                    DB::raw('COALESCE(SUM(CASE WHEN pay.amount > 0 AND pay.status IN ("approved", "completed") THEN pay.amount ELSE 0 END), 0) as abono'),
                    DB::raw('COUNT(DISTINCT inst.id) as total_installments'),
                    DB::raw('MAX(CASE WHEN pay.amount > 0 AND pay.status IN ("approved", "completed") THEN pg.name END) as payment_gateway'),
                ])
                // Ordenar por apellidos y luego nombres
                ->orderBy('p.first_last_name', 'asc')
                ->orderBy('p.second_last_name', 'asc')
                ->orderBy('p.first_name', 'asc')
                ->orderBy('p.second_name', 'asc');

            // Log SQL query para debug
            \Illuminate\Support\Facades\Log::info('ExecutivesPartialAccount SQL', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            $results = $query->get();

            \Illuminate\Support\Facades\Log::info('ExecutivesPartialAccount Results', [
                'count' => $results->count(),
                'first_5_names' => $results->take(5)->map(fn($r) => [
                    'first_name' => $r->first_name,
                    'first_last_name' => $r->first_last_name,
                    'second_last_name' => $r->second_last_name
                ])->toArray()
            ]);

            foreach ($results as $row) {
                // Construir nombre en formato: "Apellido1 Apellido2 Nombre1 Nombre2"
                $participantName = trim(implode(' ', array_filter([
                    $row->first_last_name,
                    $row->second_last_name,
                    $row->first_name,
                    $row->second_name
                ]))) ?: 'N/A';
                $participantName = ucwords(strtolower($participantName));

                // Si no hay programa seleccionado, agregar el código del programa al nombre
                if (!$programCourse && $row->program_code) {
                    $participantName = "[{$row->program_code}] {$participantName}";
                }

                $rowProgramCourseId = $programCourseId ?? $row->program_course_id;

                // ============================================================
                // PRECIO: Usar ParticipantPriceHelper (misma lógica que vista Participantes)
                // ============================================================
                $participant = \App\Models\Participant::find($row->participant_id);
                $rowProgramCourse = \App\Models\ProgramCourse::find($rowProgramCourseId);

                $totalDue = 0;
                $basePrice = (float) $row->price;
                if ($participant && $rowProgramCourse) {
                    $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $rowProgramCourse);
                    $totalDue = $priceData['final_price'];
                    $basePrice = $priceData['base_price'];
                } else {
                    $totalDue = $basePrice;
                }

                // Calcular descuentos desglosados para las columnas de visualización
                $scholarship = 0.0;
                $released = 0.0;
                $simpleDiscounts = 0.0;
                $ppDiscounts = DB::table('participant_program_discounts')
                    ->where('participant_program_id', $row->participant_program_id)
                    ->get();

                foreach ($ppDiscounts as $disc) {
                    $discAmount = 0.0;
                    if ($disc->percent && $disc->percent > 0) {
                        $discAmount += ($basePrice * $disc->percent) / 100;
                    }
                    if ($disc->amount && $disc->amount > 0) {
                        $discAmount += (float) $disc->amount;
                    }
                    if ($disc->discount_type === 'scholarship') {
                        $scholarship += $discAmount;
                    } elseif ($disc->discount_type === 'released') {
                        $released += $discAmount;
                    } else {
                        $simpleDiscounts += $discAmount;
                    }
                }

                // PRECIO mostrado = base - descuentos simples (sin incluir beca ni liberado)
                $price = $basePrice - $simpleDiscounts;

                // ============================================================
                // ABONO: Misma lógica que vista Participantes (GetParticipantsService)
                // normalPayments (excl. subscription source) + subscriptionPayments (cuotas pagadas)
                // ============================================================
                $orderIds = \App\Models\Order::where('participant_id', $row->participant_id)
                    ->where('program_id', $rowProgramCourseId)
                    ->pluck('id')->all();

                // 1. Pagos normales (excluir pagos de suscripción para evitar doble conteo)
                //    Y excluir aportes (AP) para la columna ABONO
                $normalPayments = 0.0;
                $aporteAmount = 0.0;
                if (!empty($orderIds)) {
                    // Pagos normales sin aportes ni suscripciones
                    $normalPayments = (float) Payment::whereIn('order_id', $orderIds)
                        ->whereIn('status', ['approved', 'completed'])
                        ->where(function($query) {
                            $query->whereNull('payment_source')
                                  ->orWhere('payment_source', '!=', 'subscription');
                        })
                        ->where(function($q) {
                            $q->whereNull('payment_option_id')
                              ->orWhereHas('paymentOption', function($sq) {
                                  $sq->where('report_code', '!=', 'AP');
                              });
                        })
                        ->sum('amount');

                    // Aportes (pagos con report_code 'AP', excluyendo subscription source)
                    $aporteAmount = (float) Payment::whereIn('order_id', $orderIds)
                        ->whereIn('status', ['approved', 'completed'])
                        ->where(function($query) {
                            $query->whereNull('payment_source')
                                  ->orWhere('payment_source', '!=', 'subscription');
                        })
                        ->whereHas('paymentOption', function($q) {
                            $q->where('report_code', 'AP');
                        })
                        ->sum('amount');
                }

                // 2. Cuotas de suscripción pagadas (installments) - solo de planes activos
                $subscriptionPayments = (float) DB::table('installments')
                    ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
                    ->where('installment_plans.participant_id', $row->participant_id)
                    ->where('installment_plans.program_id', $rowProgramCourseId)
                    ->where('installment_plans.status', '!=', 'cancelled')
                    ->where('installments.status', 'paid')
                    ->sum('installments.amount');

                // Abono = pagos normales (sin aportes) + cuotas de suscripción
                $abono = $normalPayments + $subscriptionPayments;

                // Total pagado real (incluyendo aportes) - para calcular saldo igual que Participantes
                $totalPaid = $abono + $aporteAmount;

                // ============================================================
                // SALDO: (Abono + Aporte/Beca + Liberado) - Precio
                // Positivo = excedente, Negativo = saldo deudor
                // ============================================================
                $saldo = round(($abono + $scholarship + $aporteAmount + $released) - $price, 2);

                // Cuotas pagadas y vencidas
                $paidInstallments = 0;
                $overdueInstallments = 0;
                $totalInstallments = 0;
                if (!empty($orderIds)) {
                    $installmentPlan = \App\Models\InstallmentPlan::whereIn('order_id', $orderIds)->first();
                    if ($installmentPlan) {
                        $totalInstallments = $installmentPlan->installments()->count();
                        $paidInstallments = $installmentPlan->installments()->where('status', 'paid')->count();
                        $overdueInstallments = $installmentPlan->installments()
                            ->where(function($query) {
                                $query->where('status', 'overdue')
                                      ->orWhere(function($q) {
                                          $q->where('status', 'pending')
                                            ->where('due_date', '<', now());
                                      });
                            })->count();
                    }
                }

                // Ajuste para participantes DE BAJA
                $displayPrice = $price;
                if (!$row->is_active) {
                    $saldo = 0;
                    $displayPrice = min($price, $abono);
                }

                // Forma de pago
                $paymentMethod = 'N/A';
                if (!empty($orderIds)) {
                    $lastPayment = Payment::whereIn('order_id', $orderIds)
                        ->whereIn('status', ['approved', 'completed'])
                        ->where('amount', '>', 0)
                        ->with('paymentOption')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($lastPayment && $lastPayment->paymentOption) {
                        $paymentMethod = $lastPayment->paymentOption->report_code ?: $row->payment_gateway ?: 'N/A';
                    } elseif ($row->payment_gateway) {
                        $paymentMethod = $row->payment_gateway;
                    }
                }

                $items->push([
                    'student' => $participantName,
                    'status' => $row->is_active ? 'Activo' : 'De Baja',
                    'price' => $displayPrice,
                    'abono' => $abono,
                    'paid_installments' => $paidInstallments,
                    'total_installments' => $totalInstallments,
                    'overdue_installments' => $overdueInstallments,
                    'payment_method' => $paymentMethod,
                    'scholarship' => $scholarship + $aporteAmount,
                    'released' => $released,
                    'balance' => $saldo,
                ]);
            }
        }

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'partialAccounts' => $paginator,
            'filters' => [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'programId' => $programId,
                'programCode' => $programCode,
                'documentSearch' => $filters['documentSearch'] ?? "",
                'status' => $filters['status'] ?? "",
            ],
            'programs' => \App\Models\ProgramCourse::select('id', 'code', 'name')->where('active', true)->orderBy('code')->get(),
        ];
    }

    /**
     * Mapea códigos de forma de pago a nombres descriptivos
     */
    private function mapPaymentMethodCode(string $code): string
    {
        $mapping = [
            'KP' => 'Khipu',
            'BX' => 'Tarjeta Presencial',
            'TE' => 'Transferencia',
            'VP' => 'Pago en VirtualPos',
            'VPI' => 'Pago Internacional',
            'AP' => 'Aporte',
        ];

        return $mapping[$code] ?? $code;
    }
}


