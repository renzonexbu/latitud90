<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;

class GetPaymentsService
{
    use AdminLogging;
    /**
     * Obtener pagos con filtros y estadísticas
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $query = Payment::with([
            'order',
            'order.participant.documentType',
            'order.programCourse.course.institution',
            'orderDetail.country',
            'orderDetail.region',
            'orderDetail.city',
            'paymentGateway',
            'paymentOption'
        ]);

        // Excluir pagos de suscripciones (las suscripciones tienen su propia vista)
        $query->whereHas('order', function ($q) {
            $q->where('order_number', 'NOT LIKE', 'SUB-%');
        });

        // Aplicar filtros
        $this->applyFilters($query, $request);

        $payments = $query->latest()->paginate(15);

        // Obtener las cuotas de suscripciones
        $installments = $this->getInstallmentsAsPayments($request);

        // Transformar payments a array y combinar con installments
        $paymentsArray = $payments->items();
        $combinedPayments = array_merge($paymentsArray, $installments->toArray());

        // Reemplazar los items de la paginación con los combinados
        $payments = new \Illuminate\Pagination\LengthAwarePaginator(
            $combinedPayments,
            count($combinedPayments),
            15,
            $payments->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Obtener estadísticas
        $stats = $this->getStats();

        // Obtener program_courses (planes específicos) para los filtros
        $programs = \App\Models\ProgramCourse::where('active', true)
            ->with('program:id,destination')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'program_id'])
            ->map(function ($programCourse) {
                return [
                    'id' => $programCourse->id,
                    'name' => $programCourse->name,
                    'code' => $programCourse->code,
                    'destination' => $programCourse->program->destination ?? ''
                ];
            });

        // Log the payments list view
        $this->logView(
            'payments',
            'PaymentList',
            0, // No specific resource ID for list views
            "Lista de pagos consultada - Total: {$payments->total()} registros",
            [
                'total_payments' => $payments->total(),
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'filters_applied' => $request->only(['participant_name', 'payment_status', 'program_id', 'payment_method', 'date_from', 'date_to']),
                'stats' => $stats,
            ]
        );

        return [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => $request->only(['participant_name', 'payment_status', 'program_id', 'payment_method', 'date_from', 'date_to']),
            'programs' => $programs
        ];
    }

    /**
     * Aplicar filtros a la consulta
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro de búsqueda por nombre del participante
        if ($request->participant_name) {
            $query->whereHas('order.participant', function ($q) use ($request) {
                $q->where('first_last_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('second_last_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('first_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('second_name', 'like', '%' . $request->participant_name . '%');
            });
        }

        // Filtro de estado del pago
        if ($request->payment_status && $request->payment_status !== 'all') {
            $query->where('status', $request->payment_status);
        }

        // Filtro de programa (program_id ahora apunta a program_courses)
        if ($request->program_id) {
            $query->whereHas('order.programCourse', function ($q) use ($request) {
                $q->where('id', $request->program_id);
            });
        }

        // Filtro de método de pago (gateway)
        if ($request->payment_method && $request->payment_method !== 'all') {
            $query->whereHas('paymentGateway', function ($q) use ($request) {
                $q->where('code', $request->payment_method);
            });
        }

        // Filtro de fecha desde
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtro de fecha hasta
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filtros legacy para compatibilidad
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                  ->orWhere('buy_order', 'like', '%' . $request->search . '%')
                  ->orWhere('authorization_code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('order.participant', function ($sq) use ($request) {
                      $sq->where('first_last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('second_last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('second_name', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('order.programCourse', function ($sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->gateway) {
            $query->whereHas('paymentGateway', function ($q) use ($request) {
                $q->where('code', $request->gateway);
            });
        }
    }

    /**
     * Obtener cuotas de suscripciones transformadas como pagos
     *
     * @param Request $request
     * @return \Illuminate\Support\Collection
     */
    private function getInstallmentsAsPayments(Request $request)
    {
        $query = \App\Models\Installment::with([
            'installmentPlan.participant.documentType',
        ]);

        // Aplicar filtros similares a los de pagos
        $this->applyInstallmentFilters($query, $request);

        $installments = $query->latest()->take(15)->get();

        // Transformar installments al formato de pagos
        return $installments->map(function ($installment) {
            $plan = $installment->installmentPlan;
            $participant = $plan->participant;

            // Obtener el program_course desde program_id
            $programCourse = \App\Models\ProgramCourse::with('course.institution')->find($plan->program_id);

            // Obtener el enrollment_code desde participant_program
            $participantProgram = null;
            $enrollmentCode = 'N/A';

            if ($programCourse) {
                $participantProgram = \DB::table('participant_program')
                    ->where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->program_id)
                    ->first();

                $enrollmentCode = $participantProgram?->enrollment_code ?? 'N/A';
            }

            // Obtener la suscripción para obtener los datos del comprador enviados a VirtualPos
            $subscription = \App\Models\ProgramSubscription::where('participant_id', $participant->id)
                ->where('program_id', $plan->program_id)
                ->first();

            // Obtener client_data de la suscripción (datos enviados a VirtualPos)
            $clientData = $subscription?->client_data ?? [];

            // Obtener buyer_data de la suscripción (datos del comprador)
            $buyerData = $subscription?->buyer_data ?? [];

            return (object) [
                'id' => $enrollmentCode,
                'is_installment' => true,
                'installment_id' => $installment->id,
                'installment_number' => $installment->installment_number,
                'amount' => $installment->amount,
                'status' => $this->mapInstallmentStatus($installment->status, $installment->is_paid),
                'created_at' => $installment->created_at,
                'email_sent' => false, // Las cuotas de suscripción no envían email automático
                'client_data' => $clientData, // Datos del comprador enviados a VirtualPos
                'order' => (object) [
                    'id' => $plan->order_id ?? null,
                    'order_number' => 'SUB-' . $plan->id,
                    'participant' => $participant,
                    'buyer_first_name' => $buyerData['first_name'] ?? null,
                    'buyer_first_last_name' => $buyerData['first_last_name'] ?? null,
                    'program_course' => $programCourse ? (object) [
                        'name' => $programCourse->name,
                        'course' => (object) [
                            'institution' => $programCourse->course->institution ?? null
                        ]
                    ] : null,
                ],
                'payment_gateway' => (object) [
                    'name' => 'VirtualPos',
                    'code' => 'virtualpos',
                ],
                'due_date' => $installment->due_date,
                'paid_at' => $installment->paid_at,
                'virtualpos_charge_id' => $installment->virtualpos_charge_id,
            ];
        });
    }

    /**
     * Aplicar filtros a la consulta de installments
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyInstallmentFilters($query, Request $request): void
    {
        // Filtro de búsqueda por nombre del participante
        if ($request->participant_name) {
            $query->whereHas('installmentPlan.participant', function ($q) use ($request) {
                $q->where('first_last_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('second_last_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('first_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('second_name', 'like', '%' . $request->participant_name . '%');
            });
        }

        // Filtro de estado del pago
        if ($request->payment_status && $request->payment_status !== 'all') {
            if ($request->payment_status === 'completed') {
                $query->where('is_paid', true);
            } elseif ($request->payment_status === 'pending') {
                $query->where('status', 'pending');
            } elseif ($request->payment_status === 'failed') {
                $query->where('status', 'cancelled');
            }
        }

        // Filtro de programa
        if ($request->program_id) {
            $query->whereHas('installmentPlan', function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            });
        }

        // Filtro de método de pago - solo mostrar si selecciona VirtualPos
        if ($request->payment_method && $request->payment_method !== 'all' && $request->payment_method !== 'virtualpos') {
            // Si el filtro no es VirtualPos, excluir todos los installments
            $query->whereRaw('1 = 0');
        }

        // Filtro de fecha desde
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtro de fecha hasta
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    /**
     * Mapear estados de installments a estados de payments
     *
     * @param string $status
     * @param bool $isPaid
     * @return string
     */
    private function mapInstallmentStatus(string $status, bool $isPaid): string
    {
        if ($isPaid) {
            return 'completed';
        }

        return match($status) {
            'pending' => 'pending',
            'paid' => 'completed',
            'cancelled' => 'failed',
            'overdue' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Obtener estadísticas de pagos
     *
     * @return array
     */
    private function getStats(): array
    {
        // Estadísticas básicas
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $completed = Payment::where('status', 'completed')->count();
        $pending = Payment::where('status', 'pending')->count();
        $failed = Payment::where('status', 'failed')->count();
        $authorized = Payment::where('status', 'authorized')->count();

        // Distribución por tipo de pago
        $paymentTypeDistribution = $this->getPaymentTypeDistribution();

        // Métodos de pago detallados
        $paymentMethodsDetailed = $this->getPaymentMethodsDetailed();

        return [
            'total_revenue' => $totalRevenue,
            'completed' => $completed,
            'pending' => $pending,
            'failed' => $failed,
            'authorized' => $authorized,
            'payment_type_distribution' => $paymentTypeDistribution,
            'payment_methods_detailed' => $paymentMethodsDetailed,
        ];
    }

    /**
     * Obtener distribución por tipo de pago
     *
     * @return array
     */
    private function getPaymentTypeDistribution(): array
    {
        $distribution = [];

        // 1. Pagos Totales (Full) - órdenes con payment_type = 'total'
        $fullPayments = Payment::where('status', 'completed')
            ->whereHas('order', function ($q) {
                $q->where('payment_type', 'total')
                  ->where('order_number', 'NOT LIKE', 'SUB-%');
            })
            ->selectRaw('COUNT(*) as count, SUM(amount) as total')
            ->first();

        $distribution[] = [
            'label' => 'Pago Total (Full)',
            'count' => $fullPayments->count ?? 0,
            'total' => $fullPayments->total ?? 0,
        ];

        // 2. Suscripciones (cuotas de VirtualPos)
        // Usar status = 'paid' porque is_paid puede no estar sincronizado
        $subscriptionCount = \App\Models\Installment::where('status', 'paid')->count();
        $subscriptionTotal = \App\Models\Installment::where('status', 'paid')->sum('amount');

        $distribution[] = [
            'label' => 'Suscripciones',
            'count' => $subscriptionCount,
            'total' => $subscriptionTotal,
        ];

        // 3. Pagos Presenciales
        $presentialPayments = Payment::where('status', 'completed')
            ->whereHas('paymentGateway', function ($q) {
                $q->where('code', 'presencial');
            })
            ->selectRaw('COUNT(*) as count, SUM(amount) as total')
            ->first();

        $distribution[] = [
            'label' => 'Pagos Presenciales',
            'count' => $presentialPayments->count ?? 0,
            'total' => $presentialPayments->total ?? 0,
        ];

        // 4. Devoluciones (montos negativos)
        $refunds = Payment::where('status', 'completed')
            ->where('amount', '<', 0)
            ->selectRaw('COUNT(*) as count, SUM(ABS(amount)) as total')
            ->first();

        $distribution[] = [
            'label' => 'Devoluciones',
            'count' => $refunds->count ?? 0,
            'total' => $refunds->total ?? 0,
        ];

        return $distribution;
    }

    /**
     * Obtener métodos de pago detallados
     * Agrupa por tipo de pago específico (opción de pago) en lugar de solo por gateway
     *
     * @return array
     */
    private function getPaymentMethodsDetailed(): array
    {
        $methods = [];

        // Obtener pagos completados agrupados por opción de pago
        // Excluir suscripciones (mode = 'subscription') porque se cuentan desde installments
        $paymentsByOption = Payment::where('status', 'completed')
            ->where('amount', '>', 0) // Excluir devoluciones
            ->with('paymentOption')
            ->get()
            ->filter(function ($payment) {
                // Excluir pagos con payment_option de tipo subscription
                return $payment->paymentOption?->mode !== 'subscription';
            })
            ->groupBy(function ($payment) {
                return $payment->paymentOption?->label ?? 'Sin especificar';
            });

        foreach ($paymentsByOption as $optionLabel => $payments) {
            $methods[] = [
                'label' => $optionLabel,
                'count' => $payments->count(),
                'total' => $payments->sum('amount'),
                'percentage' => 0, // Se calculará en el frontend
            ];
        }

        // Agregar cuotas de suscripciones (VirtualPos) desde installments
        // Usar status = 'paid' porque is_paid puede no estar sincronizado
        $subscriptionInstallments = \App\Models\Installment::where('status', 'paid')->count();
        $subscriptionTotal = \App\Models\Installment::where('status', 'paid')->sum('amount');

        if ($subscriptionInstallments > 0) {
            $methods[] = [
                'label' => 'Suscripción mensual (VirtualPos)',
                'count' => $subscriptionInstallments,
                'total' => $subscriptionTotal,
                'percentage' => 0,
            ];
        }

        // Ordenar por total de mayor a menor
        usort($methods, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $methods;
    }
}
