<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentScheduleSummaryTransformer
{
    public function transformExecutiveSummary(Collection $data): Collection
    {
        if ($data->isEmpty()) {
            return collect();
        }

        // Agrupar por ejecutivo -> program_course -> mes
        $grouped = $data->groupBy([
            function ($item) {
                return $item->sales_executive_id ?? 'sin_ejecutivo';
            },
            function ($item) {
                // IMPORTANTE: Agrupar por program_course_id (plan específico), NO por program_id (plantilla)
                return $item->program_course_id ?? 'sin_programa';
            },
            function ($item) {
                return $item->year_month ?? 'sin_fecha';
            }
        ]);

        $result = collect();

        foreach ($grouped as $executiveId => $executiveData) {
            foreach ($executiveData as $programCourseId => $programData) {
                $executiveInfo = $data->where('sales_executive_id', $executiveId)->first();
                $programInfo = $data->where('program_course_id', $programCourseId)->first();

                // Datos base del ejecutivo y programa
                $executiveProgramData = [
                    'sales_executive_id' => $executiveId,
                    'sales_executive_name' => $executiveInfo->sales_executive_name ?? 'Sin Asignar',
                    'program_course_id' => $programCourseId,
                    'program_id' => $programInfo->program_id ?? null,
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

                // Asegurar continuidad mensual: rellenar meses faltantes entre el primero y el último
                if ($executiveProgramData['months']->isNotEmpty()) {
                    // Orden preliminar para identificar rangos
                    $sortedForRange = $executiveProgramData['months']->sortBy(function ($m) {
                        return ((int)$m['year'] * 100) + (int)$m['month'];
                    })->values();

                    $first = $sortedForRange->first();
                    $last = $sortedForRange->last();

                    // Si no hay fechas válidas, omitir
                    if (!empty($first['year']) && !empty($first['month']) && !empty($last['year']) && !empty($last['month'])) {
                        $start = Carbon::createFromDate((int)$first['year'], (int)$first['month'], 1)->startOfMonth();
                        $end = Carbon::createFromDate((int)$last['year'], (int)$last['month'], 1)->startOfMonth();

                        // Índice de meses existentes para no duplicar
                        $existing = $executiveProgramData['months']->keyBy(function ($m) {
                            return sprintf('%04d-%02d', (int)$m['year'], (int)$m['month']);
                        });

                        $cursor = $start->copy();
                        while ($cursor->lte($end)) {
                            $ym = $cursor->format('Y-m');
                            if (!$existing->has($ym)) {
                                $executiveProgramData['months']->push([
                                    // Contadores en cero
                                    'cuotas_no_pagadas_count' => 0,
                                    'cuotas_pagadas_tc_count' => 0,
                                    'cuotas_pagadas_pat_count' => 0,
                                    // Montos en cero
                                    'cuotas_no_pagadas_amount' => 0,
                                    'cuotas_pagadas_tc_amount' => 0,
                                    'cuotas_pagadas_pat_amount' => 0,
                                    // Totales
                                    'total_cuotas_count' => 0,
                                    'total_amount' => 0,
                                    // Metadatos del mes
                                    'year_month' => $ym,
                                    'month_name' => $cursor->locale('es')->translatedFormat('F Y'),
                                    'month_short' => $cursor->locale('es')->translatedFormat('M'),
                                    'year' => (int)$cursor->format('Y'),
                                    'month' => (int)$cursor->format('n'),
                                ]);
                            }
                            $cursor->addMonth();
                        }
                    }
                }
                // Ordenar meses cronológicamente (más antiguo primero)
                $executiveProgramData['months'] = $executiveProgramData['months']->sortBy(function ($month) {
                    // Usar año * 100 + mes para evitar problemas
                    $year = (int) $month['year'];
                    $monthNum = (int) $month['month'];
                    $sortValue = ($year * 100) + $monthNum;
                    return $sortValue;
                })->values(); // Reindexar después del ordenamiento

                if ($executiveProgramData['months']->isNotEmpty()) {
                    // Convertir Collection a array para el frontend
                    $executiveProgramData['months'] = $executiveProgramData['months']->values()->toArray();
                    $result->push($executiveProgramData);
                }
            }
        }

        // Ordenar grupos por su mes más antiguo (YYYYMM asc) usando clave numérica para evitar orden lexicográfico incorrecto
        $sorted = $result->sortBy(function ($group) {
            $firstMonth = $group['months'][0] ?? null;
            $year = (int)($firstMonth['year'] ?? 0);
            $month = (int)($firstMonth['month'] ?? 0);
            return ($year * 100) + $month;
        })->values(); // Reindexar

        return $sorted->values();
    }

    private function calculateMonthStats(Collection $monthData): array
    {
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
