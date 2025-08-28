<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentScheduleSummaryTransformer
{
    public function transformExecutiveSummary(Collection $data): Collection
    {
        // Log de datos de entrada
        Log::info('TRANSFORMER INPUT DATA:', [
            'total_records' => $data->count(),
            'sample_records' => $data->take(3)->map(function($item) {
                return [
                    'sales_executive_id' => $item->sales_executive_id ?? 'N/A',
                    'sales_executive_name' => $item->sales_executive_name ?? 'N/A',
                    'program_id' => $item->program_id ?? 'N/A',
                    'program_name' => $item->program_name ?? 'N/A',
                    'year_month' => $item->year_month ?? 'N/A',
                    'due_date' => $item->due_date ?? 'N/A'
                ];
            })->toArray()
        ]);

        if ($data->isEmpty()) {
            return collect();
        }

        // Agrupar por ejecutivo -> programa -> mes
        $grouped = $data->groupBy([
            function($item) {
                return $item->sales_executive_id ?? 'sin_ejecutivo';
            },
            function($item) {
                return $item->program_id ?? 'sin_programa';
            },
            function($item) {
                return $item->year_month ?? 'sin_fecha';
            }
        ]);

        $result = collect();

        foreach ($grouped as $executiveId => $executiveData) {
            foreach ($executiveData as $programId => $programData) {
                $executiveInfo = $data->where('sales_executive_id', $executiveId)->first();
                $programInfo = $data->where('program_id', $programId)->first();

                // Datos base del ejecutivo y programa
                $executiveProgramData = [
                    'sales_executive_id' => $executiveId,
                    'sales_executive_name' => $executiveInfo->sales_executive_name ?? 'Sin Asignar',
                    'program_id' => $programId,
                    'program_code' => $programInfo->program_code ?? '',
                    'program_name' => $programInfo->program_name ?? 'Sin Programa',
                    'program_destination' => $programInfo->program_destination ?? '',
                    'months' => collect()
                ];

                // Procesar cada mes
                foreach ($programData as $yearMonth => $monthData) {
                    if ($yearMonth === 'sin_fecha') continue;
                    
                    $monthStats = $this->calculateMonthStats($monthData);
                    
                    // Formatear el mes para mostrar
                    try {
                        $date = Carbon::createFromFormat('Y-m', $yearMonth);
                        $monthStats['year_month'] = $yearMonth;
                        $monthStats['month_name'] = $date->locale('es')->translatedFormat('F Y');
                        $monthStats['month_short'] = $date->locale('es')->translatedFormat('M');
                        $monthStats['year'] = $date->year;
                        $monthStats['month'] = $date->month;
                    } catch (\Exception $e) {
                        $monthStats['year_month'] = $yearMonth;
                        $monthStats['month_name'] = $yearMonth;
                        $monthStats['month_short'] = $yearMonth;
                        $monthStats['year'] = 0;
                        $monthStats['month'] = 0;
                    }

                    $executiveProgramData['months']->push($monthStats);
                }

                // Log ANTES del ordenamiento
                Log::info('BEFORE SORTING - ' . $executiveProgramData['sales_executive_name'] . ' - ' . $executiveProgramData['program_name'], [
                    'months_before_sort' => $executiveProgramData['months']->map(function($m) {
                        return [
                            'month_name' => $m['month_name'],
                            'year' => $m['year'],
                            'month' => $m['month'],
                            'year_month' => $m['year_month'],
                            'sort_value' => ($m['year'] * 1000) + $m['month']
                        ];
                    })->toArray()
                ]);

                // Ordenar meses cronológicamente (más antiguo primero)
                $executiveProgramData['months'] = $executiveProgramData['months']->sortBy(function ($month) {
                    $sortValue = ($month['year'] * 1000) + $month['month'];
                    Log::info('Sorting month: ' . $month['month_name'] . ' with value: ' . $sortValue);
                    return $sortValue;
                });
                
                // Log DESPUÉS del ordenamiento
                Log::info('AFTER SORTING - ' . $executiveProgramData['sales_executive_name'] . ' - ' . $executiveProgramData['program_name'], [
                    'months_after_sort' => $executiveProgramData['months']->map(function($m) {
                        return [
                            'month_name' => $m['month_name'],
                            'year' => $m['year'],
                            'month' => $m['month'],
                            'year_month' => $m['year_month'],
                            'sort_value' => ($m['year'] * 1000) + $m['month']
                        ];
                    })->toArray(),
                    'first_month' => $executiveProgramData['months']->first()['month_name'] ?? 'N/A',
                    'last_month' => $executiveProgramData['months']->last()['month_name'] ?? 'N/A'
                ]);

                if ($executiveProgramData['months']->isNotEmpty()) {
                    // Convertir Collection a array para el frontend
                    $executiveProgramData['months'] = $executiveProgramData['months']->values()->toArray();
                    $result->push($executiveProgramData);
                }
            }
        }

        // Ordenar grupos por su mes más antiguo (YYYY-MM asc). Desempatar por nombre.
        $sorted = $result->sortBy(function ($group) {
            $firstMonth = $group['months'][0] ?? null;
            $year = (int)($firstMonth['year'] ?? 0);
            $month = (int)($firstMonth['month'] ?? 0);
            $yearMonthKey = sprintf('%04d-%02d', $year, $month);
            return $yearMonthKey . '|' . ($group['sales_executive_name'] ?? '') . '|' . ($group['program_name'] ?? '');
        });

        return $sorted->values();
    }

    private function calculateMonthStats(Collection $monthData): array
    {
        // Log de datos del mes
        Log::info('CALCULATING MONTH STATS:', [
            'month_data_count' => $monthData->count(),
            'sample_month_data' => $monthData->take(2)->map(function($item) {
                return [
                    'payment_status' => $item->payment_status ?? 'N/A',
                    'payment_method_type' => $item->payment_method_type ?? 'N/A',
                    'installment_amount' => $item->installment_amount ?? 'N/A',
                    'due_date' => $item->due_date ?? 'N/A'
                ];
            })->toArray()
        ]);

        // Contadores
        $unpaidCount = 0;
        $paidTcCount = 0;
        $paidPatCount = 0;

        // Montos
        $unpaidAmount = 0;
        $paidTcAmount = 0;
        $paidPatAmount = 0;

        foreach ($monthData as $installment) {
            $amount = (float) $installment->installment_amount;

            if ($installment->payment_status === 'paid') {
                // Cuota pagada - verificar método
                if ($installment->payment_method_type === 'TC') {
                    $paidTcCount++;
                    $paidTcAmount += $amount;
                } elseif ($installment->payment_method_type === 'PAT') {
                    $paidPatCount++;
                    $paidPatAmount += $amount;
                } else {
                    // Si no se puede determinar el método, asumimos TC por defecto
                    $paidTcCount++;
                    $paidTcAmount += $amount;
                }
            } else {
                // Cuota no pagada (pendiente o vencida)
                $unpaidCount++;
                $unpaidAmount += $amount;
            }
        }

        return [
            // Contadores
            'cuotas_no_pagadas_count' => $unpaidCount,
            'cuotas_pagadas_tc_count' => $paidTcCount,
            'cuotas_pagadas_pat_count' => $paidPatCount,
            
            // Montos
            'cuotas_no_pagadas_amount' => $unpaidAmount,
            'cuotas_pagadas_tc_amount' => $paidTcAmount,
            'cuotas_pagadas_pat_amount' => $paidPatAmount,
            
            // Totales
            'total_cuotas_count' => $unpaidCount + $paidTcCount + $paidPatCount,
            'total_amount' => $unpaidAmount + $paidTcAmount + $paidPatAmount,
        ];
    }
}
