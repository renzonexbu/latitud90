<?php

namespace App\Services\Admin\Reports\RecoverySchedule;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class RecoveryScheduleFilters
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        $this->validateFilters($filters);
        
        if (!empty($filters['programId'])) {
            $query->where('prog.id', $filters['programId']);
        }

        if (!empty($filters['salesExecutiveId'])) {
            $query->where('se.id', $filters['salesExecutiveId']);
        }
        
        // Solo aplicar filtro de estado si se especifica explícitamente
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'pending':
                    $query->where('i.status', 'pending')
                          ->where('i.due_date', '>=', Carbon::now()->format('Y-m-d'));
                    break;
                case 'overdue':
                    $query->where('i.status', 'pending')
                          ->where('i.due_date', '<', Carbon::now()->format('Y-m-d'));
                    break;
                case 'paid':
                    $query->where('i.status', 'paid');
                    break;
                case 'upcoming':
                    $query->where('i.status', 'pending')
                          ->where('i.due_date', '>=', Carbon::now()->format('Y-m-d'))
                          ->where('i.due_date', '<=', Carbon::now()->addDays(30)->format('Y-m-d'));
                    break;
            }
        }
        
        // Solo aplicar filtros de fecha si están especificados
        if (!empty($filters['dateFrom'])) {
            $query->where('i.due_date', '>=', $filters['dateFrom']);
        }
        
        if (!empty($filters['dateTo'])) {
            $query->where('i.due_date', '<=', $filters['dateTo']);
        }
        
        // Ordenar por fecha de vencimiento más reciente primero
        $query->orderBy('i.due_date', 'desc');
        
        return $query;
    }
    
    public function validateFilters(array $filters): void
    {
        if (isset($filters['dateFrom']) && isset($filters['dateTo'])) {
            $dateFrom = Carbon::parse($filters['dateFrom']);
            $dateTo = Carbon::parse($filters['dateTo']);
            
            if ($dateFrom->gt($dateTo)) {
                throw new \InvalidArgumentException('La fecha de inicio no puede ser mayor a la fecha de fin');
            }
        }
    }
    
    public function getDefaultFilters(): array
    {
        $today = Carbon::now();
        $nextMonth = $today->copy()->addMonth();
        
        return [
            'dateFrom' => $today->format('Y-m-d'),
            'dateTo' => $nextMonth->format('Y-m-d'),
        ];
    }
}
