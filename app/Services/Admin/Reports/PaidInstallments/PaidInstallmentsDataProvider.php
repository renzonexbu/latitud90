<?php

namespace App\Services\Admin\Reports\PaidInstallments;

use App\Models\Installment;
use App\Models\ProgramCourse;
use App\Models\ParticipantProgram;
use App\Models\Document;
use Illuminate\Support\Collection;

class PaidInstallmentsDataProvider
{
    /**
     * Mapeo de tipos de documento
     */
    private function getDocumentTypesMap(): array
    {
        return Document::pluck('name', 'id')->toArray();
    }

    /**
     * Traducir estado del plan a español
     */
    private function translatePlanStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Activo',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            'pending' => 'Pendiente',
            'overdue' => 'Vencido',
            default => $status ?? 'N/A',
        };
    }

    /**
     * Formatear RUT chileno
     */
    private function formatRut(?string $rut): string
    {
        if (!$rut) return '';

        $cleanRut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($cleanRut) < 2) return $cleanRut;

        $body = substr($cleanRut, 0, -1);
        $dv = strtoupper(substr($cleanRut, -1));

        $formattedBody = number_format((int)$body, 0, '', '.');

        return "{$formattedBody}-{$dv}";
    }

    /**
     * Obtener todos los registros de cuotas pagadas
     */
    public function getData(array $filters = []): Collection
    {
        $documentTypes = $this->getDocumentTypesMap();
        $query = Installment::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->with([
                'installmentPlan.participant',
                'installmentPlan'
            ]);

        // Filtro por programa
        if (!empty($filters['program_id'])) {
            $query->whereHas('installmentPlan', function ($q) use ($filters) {
                $q->where('program_id', $filters['program_id']);
            });
        }

        // Filtro por rango de fechas de pago
        if (!empty($filters['date_from'])) {
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }

        // Búsqueda por nombre del participante
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('installmentPlan.participant', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('first_last_name', 'like', "%{$search}%")
                    ->orWhere('second_last_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        // Filtrar por estado del participante (usa is_active de participant_program)
        if (!empty($filters['participant_status'])) {
            $query->whereHas('installmentPlan', function ($q) use ($filters) {
                $q->whereExists(function ($subquery) use ($filters) {
                    $subquery->from('participant_program as pp_filter')
                        ->whereColumn('pp_filter.participant_id', 'installment_plans.participant_id')
                        ->whereColumn('pp_filter.program_id', 'installment_plans.program_id');

                    if ($filters['participant_status'] === 'active') {
                        $subquery->where(function($q2) {
                            $q2->where('pp_filter.is_active', true)
                               ->orWhereNull('pp_filter.is_active');
                        });
                    } elseif ($filters['participant_status'] === 'inactive') {
                        $subquery->where('pp_filter.is_active', false);
                    }
                });
            });
        }

        // Ordenar por fecha de pago descendente
        $query->orderBy('paid_at', 'desc');

        // Cargar program_courses para obtener el código
        $programCourses = ProgramCourse::pluck('code', 'id')->toArray();

        return $query->get()->map(function ($installment) use ($programCourses, $documentTypes) {
            $plan = $installment->installmentPlan;
            $participant = $plan?->participant;
            $programCode = $programCourses[$plan?->program_id] ?? 'N/A';

            // Obtener tipo de documento
            $documentTypeId = $participant?->document_type;
            $documentTypeName = $documentTypes[$documentTypeId] ?? 'N/A';

            // Si es RUT, formatearlo. Si es otro tipo de documento, usar RUT genérico
            if (strtoupper($documentTypeName) === 'RUT') {
                $documentNumber = $this->formatRut($participant?->document_number ?? '');
            } else {
                $documentNumber = '11.111.111-1';
            }

            // Obtener is_active de participant_program
            $participantProgramIsActive = true;
            if ($plan?->participant_id && $plan?->program_id) {
                $participantProgram = ParticipantProgram::where('participant_id', $plan->participant_id)
                    ->where('program_id', $plan->program_id)
                    ->first();
                $participantProgramIsActive = $participantProgram?->is_active ?? true;
            }

            return [
                'id' => $installment->id,
                'program_code' => $programCode,
                'participant_name' => $participant?->full_name ?? 'N/A',
                'document_type' => $documentTypeName,
                'participant_document' => $documentNumber,
                'participant_is_active' => $participantProgramIsActive,
                'amount' => $installment->amount,
                'paid_at' => $installment->paid_at?->format('d/m/Y H:i:s'),
                'paid_at_raw' => $installment->paid_at,
                'installment_number' => $installment->installment_number,
                'total_installments' => $plan?->total_installments ?? 0,
                'installment_label' => "Cuota {$installment->installment_number} de " . ($plan?->total_installments ?? '?'),
                'plan_status' => $this->translatePlanStatus($plan?->status),
            ];
        });
    }
}
