<?php

namespace App\Services\Admin\Reports\ITSimple;

use App\Models\Installment;
use App\Models\ProgramCourse;
use Illuminate\Support\Collection;

class ITSimpleReportDataProvider
{
    /**
     * Obtener datos agrupados por número de negocio con monto total recaudado.
     * Solo incluye cuotas ya pagadas (no futuras).
     */
    public function getData(array $filters = []): Collection
    {
        $query = Installment::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->with(['installmentPlan']);

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

        // Obtener códigos de programas
        $programCourses = ProgramCourse::pluck('code', 'id')->toArray();

        // Obtener todas las cuotas pagadas
        $installments = $query->get();

        // Agrupar por programa y sumar montos
        $grouped = $installments->groupBy(function ($installment) {
            return $installment->installmentPlan?->program_id;
        });

        return $grouped->map(function ($items, $programId) use ($programCourses) {
            $programCode = $programCourses[$programId] ?? 'N/A';
            $totalAmount = $items->sum('amount');
            $totalInstallments = $items->count();

            return [
                'program_id' => $programId,
                'program_code' => $programCode,
                'total_collected' => $totalAmount,
                'installments_count' => $totalInstallments,
            ];
        })->sortByDesc('total_collected')->values();
    }

    /**
     * Obtener datos detallados (cada cuota individual) para exportación completa.
     */
    public function getDetailedData(array $filters = []): Collection
    {
        $query = Installment::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->with(['installmentPlan.participant']);

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

        // Ordenar por código de programa y fecha de pago
        $query->orderBy('paid_at', 'desc');

        // Obtener códigos de programas
        $programCourses = ProgramCourse::pluck('code', 'id')->toArray();

        return $query->get()->map(function ($installment) use ($programCourses) {
            $plan = $installment->installmentPlan;
            $programCode = $programCourses[$plan?->program_id] ?? 'N/A';

            return [
                'program_code' => $programCode,
                'amount' => $installment->amount,
                'paid_at' => $installment->paid_at?->format('d/m/Y'),
            ];
        });
    }
}
