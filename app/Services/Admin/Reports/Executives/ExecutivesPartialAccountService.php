<?php

namespace App\Services\Admin\Reports\Executives;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExecutivesPartialAccountService
{
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
        $dateFrom = $filters['dateFrom'] ?? Carbon::now('America/Santiago')->subMonth()->format('Y-m-d');
        $dateTo = $filters['dateTo'] ?? Carbon::now('America/Santiago')->format('Y-m-d');
        $from = Carbon::parse($dateFrom, 'America/Santiago')->startOfDay();
        $to = Carbon::parse($dateTo, 'America/Santiago')->endOfDay();

        $items = collect([]);

        // Buscar program_course por ID o por código
        $programCourse = null;
        if ($programId) {
            $programCourse = \App\Models\ProgramCourse::find($programId);
        } elseif ($programCode) {
            $programCourse = \App\Models\ProgramCourse::where('code', $programCode)->first();
        }

        if ($programCourse) {
            $programCourseId = $programCourse->id;

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
                ->where('pp.program_id', $programCourseId)
                ->groupBy('pp.id', 'p.id', 'pr.id', 'p.first_name', 'p.second_name', 'p.first_last_name', 'p.second_last_name', 'pp.individual_price')
                ->select([
                    'pp.id as participant_program_id',
                    'p.id as participant_id',
                    'p.first_name',
                    'p.second_name', 
                    'p.first_last_name',
                    'p.second_last_name',
                    'pp.individual_price',
                    DB::raw('COALESCE(pp.individual_price, 0) as price'),
                    DB::raw('COALESCE(SUM(CASE WHEN pay.amount > 0 AND pay.status = "completed" THEN pay.amount ELSE 0 END), 0) as abono'),
                    DB::raw('COUNT(DISTINCT inst.id) as total_installments'),
                    DB::raw('MAX(CASE WHEN pay.amount > 0 AND pay.status = "completed" THEN pg.name END) as payment_method'),
                    DB::raw('COALESCE(SUM(CASE WHEN ppd.discount_type = "scholarship" THEN COALESCE(ppd.amount, (COALESCE(pp.individual_price, 0) * ppd.percent / 100)) ELSE 0 END), 0) as scholarship'),
                    DB::raw('COALESCE(SUM(CASE WHEN ppd.discount_type = "released" THEN COALESCE(ppd.amount, (COALESCE(pp.individual_price, 0) * ppd.percent / 100)) ELSE 0 END), 0) as released'),
                    DB::raw('COALESCE(SUM(CASE WHEN ppd.discount_type = "discount" THEN COALESCE(ppd.amount, (COALESCE(pp.individual_price, 0) * ppd.percent / 100)) ELSE 0 END), 0) as simple_discounts'),
                ]);

            $results = $query->get();

            foreach ($results as $row) {
                $participantName = trim(implode(' ', array_filter([
                    $row->first_name,
                    $row->second_name,
                    $row->first_last_name,
                    $row->second_last_name
                ]))) ?: 'N/A';

                // Convertir a Capital Case (primera letra de cada palabra en mayúscula)
                $participantName = ucwords(strtolower($participantName));

                $basePrice = (float) $row->price;
                $scholarship = (float) $row->scholarship;
                $released = (float) $row->released;
                $simpleDiscounts = (float) ($row->simple_discounts ?? 0);
                // El precio mostrado ya incluye los descuentos simples (es el nuevo precio base)
                $price = $basePrice - $simpleDiscounts;

                // Calcular cuotas pagadas y vencidas solo para pagos completed
                $orderIds = \App\Models\Order::where('participant_id', $row->participant_id)
                    ->where('program_id', $programCourseId)
                    ->pluck('id')->all();

                // Calcular aportes (pagos con report_code 'AP')
                $aporteAmount = 0;
                if (!empty($orderIds)) {
                    $aporteAmount = (float) \App\Models\Payment::whereIn('order_id', $orderIds)
                        ->where('status', 'completed')
                        ->whereHas('paymentOption', function($q) {
                            $q->where('report_code', 'AP');
                        })
                        ->sum('amount');
                }
                // Sumar aportes a scholarship
                $scholarship += $aporteAmount;

                $paidInstallments = 0;
                $overdueInstallments = 0;
                $totalInstallments = 0;
                $abono = 0;

                if (!empty($orderIds)) {
                    // Obtener el plan de cuotas
                    $installmentPlan = \App\Models\InstallmentPlan::whereIn('order_id', $orderIds)->first();

                    if ($installmentPlan) {
                        $totalInstallments = $installmentPlan->installments()->count();

                        // Contar cuotas pagadas
                        $paidInstallments = $installmentPlan->installments()->where('status', 'paid')->count();

                        // Contar cuotas vencidas (no pagadas y con fecha pasada)
                        $overdueInstallments = $installmentPlan->installments()
                            ->where(function($query) {
                                $query->where('status', 'overdue')
                                      ->orWhere(function($q) {
                                          $q->where('status', 'pending')
                                            ->where('due_date', '<', now());
                                      });
                            })->count();

                        // Calcular abono: suma de los montos de las cuotas pagadas
                        $abono = (float) $installmentPlan->installments()
                            ->where('status', 'paid')
                            ->sum('amount');
                    } else {
                        // Si no hay plan de cuotas, usar el abono directo de pagos (pago único/contado)
                        // Excluir pagos de tipo Aporte (AP) ya que se cuentan en scholarship
                        $abono = (float) \App\Models\Payment::whereIn('order_id', $orderIds)
                            ->where('status', 'completed')
                            ->where(function($q) {
                                $q->whereNull('payment_option_id')
                                  ->orWhereHas('paymentOption', function($sq) {
                                      $sq->where('report_code', '!=', 'AP');
                                  });
                            })
                            ->sum('amount');
                    }
                }

                // Por pagar = Precio (ya con descuentos simples) - Abono - Becas - Liberado
                $porPagar = max($price - $abono - $scholarship - $released, 0);

                $items->push([
                    'student' => $participantName,
                    'price' => $price,
                    'abono' => $abono,
                    'paid_installments' => $paidInstallments,
                    'total_installments' => $totalInstallments,
                    'overdue_installments' => $overdueInstallments,
                    'payment_method' => $row->payment_method ?: 'N/A',
                    'scholarship' => $scholarship,
                    'released' => $released,
                    'balance' => $porPagar,
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


