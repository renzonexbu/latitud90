<?php

namespace App\Services\Admin\Reports\Executives;

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
                // Calcular descuentos para este participant_program específico
                // Se hace por separado para evitar duplicación por JOINs en la consulta principal
                $basePrice = (float) $row->price;
                $discounts = DB::table('participant_program_discounts')
                    ->where('participant_program_id', $row->participant_program_id)
                    ->selectRaw('
                        COALESCE(SUM(CASE WHEN discount_type = "scholarship" THEN COALESCE(amount, (? * percent / 100)) ELSE 0 END), 0) as scholarship,
                        COALESCE(SUM(CASE WHEN discount_type = "released" THEN COALESCE(amount, (? * percent / 100)) ELSE 0 END), 0) as released,
                        COALESCE(SUM(CASE WHEN discount_type = "discount" THEN COALESCE(amount, (? * percent / 100)) ELSE 0 END), 0) as simple_discounts
                    ', [$basePrice, $basePrice, $basePrice])
                    ->first();

                $scholarship = (float) ($discounts->scholarship ?? 0);
                $released = (float) ($discounts->released ?? 0);
                $simpleDiscounts = (float) ($discounts->simple_discounts ?? 0);

                // Construir nombre en formato: "Apellido1 Apellido2 Nombre1 Nombre2"
                $participantName = trim(implode(' ', array_filter([
                    $row->first_last_name,
                    $row->second_last_name,
                    $row->first_name,
                    $row->second_name
                ]))) ?: 'N/A';

                // Convertir a Capital Case (primera letra de cada palabra en mayúscula)
                $participantName = ucwords(strtolower($participantName));

                // Si no hay programa seleccionado, agregar el código del programa al nombre
                if (!$programCourse && $row->program_code) {
                    $participantName = "[{$row->program_code}] {$participantName}";
                }
                // El precio mostrado ya incluye los descuentos simples (es el nuevo precio base)
                $price = $basePrice - $simpleDiscounts;

                // Usar el program_course_id del registro si no hay programa específico seleccionado
                $rowProgramCourseId = $programCourseId ?? $row->program_course_id;

                // Calcular cuotas pagadas y vencidas solo para pagos completed
                $orderIds = \App\Models\Order::where('participant_id', $row->participant_id)
                    ->where('program_id', $rowProgramCourseId)
                    ->pluck('id')->all();

                // Calcular aportes (pagos con report_code 'AP')
                // IMPORTANTE: Los aportes son contribuciones adicionales que NO reducen la deuda
                // Se muestran en la columna APORTE/BECA pero NO se restan del "Por Pagar"
                $aporteAmount = 0;
                if (!empty($orderIds)) {
                    $aporteAmount = (float) \App\Models\Payment::whereIn('order_id', $orderIds)
                        ->whereIn('status', ['approved', 'completed'])
                        ->whereHas('paymentOption', function($q) {
                            $q->where('report_code', 'AP');
                        })
                        ->sum('amount');
                }

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
                            ->whereIn('status', ['approved', 'completed'])
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
                // IMPORTANTE: NO restar aportes porque son contribuciones adicionales, NO reducen la deuda
                $porPagar = max($price - $abono - $scholarship - $released, 0);

                // Ajuste para participantes DE BAJA:
                // - Por Pagar siempre es $0
                // - Precio = lo que abonaron (si no pagaron todo) o $0 (si pagaron todo)
                $displayPrice = $price;
                if (!$row->is_active) {
                    $porPagar = 0;
                    // Si pagaron todo el monto del programa, precio = 0
                    // Si no pagaron todo, precio = lo que abonaron
                    if ($abono >= $price) {
                        $displayPrice = 0;
                    } else {
                        $displayPrice = $abono;
                    }
                }

                // Obtener la forma de pago basada en el report_code de payment_options
                $paymentMethod = 'N/A';
                if (!empty($orderIds)) {
                    $lastPayment = \App\Models\Payment::whereIn('order_id', $orderIds)
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
                    'scholarship' => $scholarship + $aporteAmount, // Mostrar becas + aportes en columna APORTE/BECA
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
                'documentSearch' => $filters['documentSearch'] ?? "",
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


