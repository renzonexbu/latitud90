<?php

namespace App\Services\Admin\Reports;

use App\Models\Payment;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Order;
use App\Traits\AdminLogging;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsSummaryService
{
    use AdminLogging;
    public function getSummary(array $filters = [])
    {
        $dateFrom = $filters['dateFrom'] ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['dateTo'] ?? Carbon::now()->format('Y-m-d');
        $programId = $filters['programId'] ?? null;

        $summary = [
            'dailyPayments' => $this->getDailyPaymentsSummary($dateFrom, $dateTo, $programId),
            'consolidatedPayments' => $this->getConsolidatedPaymentsSummary($dateFrom, $dateTo, $programId),
            'paymentSchedule' => $this->getPaymentScheduleSummary($dateFrom, $dateTo, $programId),
            'partialAccount' => $this->getPartialAccountSummary($dateFrom, $dateTo, $programId),
            'general' => $this->getGeneralSummary($dateFrom, $dateTo, $programId)
        ];

        // Log the summary generation
        $this->logExport(
            'reports',
            "Resumen de reportes generado: {$dateFrom} - {$dateTo}",
            [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'program_id' => $programId,
                'summary_modules' => array_keys($summary),
                'report_type' => 'summary',
            ]
        );

        return $summary;
    }

    private function getDailyPaymentsSummary($dateFrom, $dateTo, $programId)
    {
        $query = Payment::with(['order.program', 'order.participant'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        if ($programId) {
            $query->whereHas('order.program', function ($q) use ($programId) {
                $q->where('id', $programId);
            });
        }

        $payments = $query->get();

        return [
            'totalPayments' => $payments->count(),
            'totalAmount' => $payments->sum('amount'),
            'averageAmount' => $payments->count() > 0 ? round($payments->sum('amount') / $payments->count(), 2) : 0,
            'paymentsToday' => $payments->where('created_at', '>=', Carbon::today())->count(),
            'amountToday' => $payments->where('created_at', '>=', Carbon::today())->sum('amount'),
            'topPaymentMethods' => $this->getTopPaymentMethods($payments),
            'dailyTrend' => $this->getDailyTrend($payments)
        ];
    }

    private function getConsolidatedPaymentsSummary($dateFrom, $dateTo, $programId)
    {
        $query = Payment::with(['order.program', 'order.participant'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        if ($programId) {
            $query->whereHas('order.program', function ($q) use ($programId) {
                $q->where('id', $programId);
            });
        }

        $payments = $query->get();

        // Agrupar por programa
        $byProgram = $payments->groupBy('order.program.id');

        return [
            'totalPrograms' => $byProgram->count(),
            'totalTransactions' => $payments->count(),
            'totalAmount' => $payments->sum('amount'),
            'averagePerProgram' => $byProgram->count() > 0 ? round($payments->sum('amount') / $byProgram->count(), 2) : 0,
            'topPrograms' => $this->getTopPrograms($payments),
            'paymentDistribution' => $this->getPaymentDistribution($payments)
        ];
    }

    private function getPaymentScheduleSummary($dateFrom, $dateTo, $programId)
    {
        $query = Order::with(['program', 'participant'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($programId) {
            $query->where('program_id', $programId);
        }

        $orders = $query->get();

        // Simular cuotas (en un sistema real esto vendría de una tabla de cuotas)
        $totalScheduled = $orders->count() * 3; // Asumiendo 3 cuotas por orden
        $totalPaid = $orders->where('status', 'completed')->count() * 3;
        $totalPending = $totalScheduled - $totalPaid;

        return [
            'totalScheduled' => $totalScheduled,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'completionRate' => $totalScheduled > 0 ? round(($totalPaid / $totalScheduled) * 100, 2) : 0,
            'overduePayments' => $this->getOverduePayments($orders),
            'upcomingPayments' => $this->getUpcomingPayments($orders)
        ];
    }

    private function getPartialAccountSummary($dateFrom, $dateTo, $programId)
    {
        $query = Participant::with(['programs'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($programId) {
            $query->whereHas('programs', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        $participants = $query->get();

        return [
            'totalParticipants' => $participants->count(),
            'activeParticipants' => $participants->where('status', 'active')->count(),
            'totalBalance' => $this->calculateTotalBalance($participants),
            'averageBalance' => $participants->count() > 0 ? round($this->calculateTotalBalance($participants) / $participants->count(), 2) : 0,
            'balanceDistribution' => $this->getBalanceDistribution($participants),
            'recentActivity' => $this->getRecentActivity($participants)
        ];
    }

    private function getGeneralSummary($dateFrom, $dateTo, $programId)
    {
        $payments = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        $participants = Participant::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($programId) {
            $payments->whereHas('order.program', function ($q) use ($programId) {
                $q->where('id', $programId);
            });
            $participants->whereHas('programs', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        $totalRevenue = $payments->sum('amount');
        $totalParticipants = $participants->count();

        return [
            'totalRevenue' => $totalRevenue,
            'totalParticipants' => $totalParticipants,
            'averageOrderValue' => $totalParticipants > 0 ? round($totalRevenue / $totalParticipants, 2) : 0,
            'conversionRate' => $this->calculateConversionRate($dateFrom, $dateTo, $programId),
            'growthRate' => $this->calculateGrowthRate($dateFrom, $dateTo, $programId)
        ];
    }

    private function getTopPaymentMethods($payments)
    {
        return $payments->groupBy('payment_gateway_id')
            ->map(function ($group) {
                $firstPayment = $group->first();
                $gatewayName = 'Desconocido';
                
                if ($firstPayment->paymentGateway) {
                    $gatewayName = $firstPayment->paymentGateway->name ?? $firstPayment->paymentGateway->code ?? 'Desconocido';
                }
                
                return [
                    'gateway' => $gatewayName,
                    'count' => $group->count(),
                    'amount' => $group->sum('amount')
                ];
            })
            ->sortByDesc('amount')
            ->take(3)
            ->values();
    }

    private function getDailyTrend($payments)
    {
        return $payments->groupBy(function ($payment) {
            return $payment->created_at->format('Y-m-d');
        })
        ->map(function ($group) {
            return [
                'date' => $group->first()->created_at->format('Y-m-d'),
                'count' => $group->count(),
                'amount' => $group->sum('amount')
            ];
        })
        ->sortBy('date')
        ->values();
    }

    private function getTopPrograms($payments)
    {
        return $payments->groupBy('order.program.id')
            ->map(function ($group) {
                return [
                    'name' => $group->first()->order->program->name ?? 'Sin nombre',
                    'count' => $group->count(),
                    'amount' => $group->sum('amount')
                ];
            })
            ->sortByDesc('amount')
            ->take(5)
            ->values();
    }

    private function getPaymentDistribution($payments)
    {
        $total = $payments->sum('amount');
        
        return $payments->groupBy('payment_gateway_id')
            ->map(function ($group) use ($total) {
                $firstPayment = $group->first();
                $gatewayName = 'Desconocido';
                
                if ($firstPayment->paymentGateway) {
                    $gatewayName = $firstPayment->paymentGateway->name ?? $firstPayment->paymentGateway->code ?? 'Desconocido';
                }
                
                return [
                    'gateway' => $gatewayName,
                    'percentage' => $total > 0 ? round(($group->sum('amount') / $total) * 100, 2) : 0
                ];
            })
            ->values();
    }

    private function getOverduePayments($orders)
    {
        // Simulación de pagos vencidos
        return $orders->where('status', 'pending')->count();
    }

    private function getUpcomingPayments($orders)
    {
        // Simulación de próximos pagos
        return $orders->where('status', 'pending')->count();
    }

    private function calculateTotalBalance($participants)
    {
        // Simulación del balance total
        return $participants->count() * 1000; // Valor simulado
    }

    private function getBalanceDistribution($participants)
    {
        // Simulación de distribución de balances
        return [
            ['range' => '0-500', 'count' => $participants->count() * 0.3],
            ['range' => '501-1000', 'count' => $participants->count() * 0.4],
            ['range' => '1001+', 'count' => $participants->count() * 0.3]
        ];
    }

    private function getRecentActivity($participants)
    {
        return $participants->where('created_at', '>=', Carbon::now()->subDays(7))->count();
    }

    private function calculateConversionRate($dateFrom, $dateTo, $programId)
    {
        // Simulación de tasa de conversión
        return 75.5; // Porcentaje simulado
    }

    private function calculateGrowthRate($dateFrom, $dateTo, $programId)
    {
        // Simulación de tasa de crecimiento
        return 12.3; // Porcentaje simulado
    }

    public function getPartialAccountData(array $filters): array
    {
        $dateFrom = $filters['dateFrom'] ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['dateTo'] ?? Carbon::now()->format('Y-m-d');
        $programId = $filters['programId'] ?? null;
        $participantId = $filters['participantId'] ?? null;

        // Obtener estados de cuenta parciales
        $partialAccounts = $this->getPartialAccountRecords($dateFrom, $dateTo, $programId, $participantId);

        return [
            'partialAccounts' => $partialAccounts
        ];
    }

    private function getPartialAccountRecords($dateFrom, $dateTo, $programId, $participantId)
    {
        $query = Order::with([
            'participant.emergencyContacts',
            'program.salesExecutive',
            'payments.paymentGateway',
            'installmentPlan.installments',
            'participantProgram'
        ])
        ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($programId) {
            $query->where('program_id', $programId);
        }

        if ($participantId) {
            $query->where('participant_id', $participantId);
        }

        $orders = $query->get();

        return $orders->map(function ($order) {
            $participant = $order->participant;
            $program = $order->program;
            $payments = $order->payments;
            
            // Obtener código de inscripción del participantProgram
            $enrollmentCode = 'N/A';
            if ($order->participantProgram) {
                $enrollmentCode = $order->participantProgram->enrollment_code ?? 'N/A';
            } else {
                // Si no hay participantProgram en la orden, buscar directamente en la tabla
                $participantProgram = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->first();
                if ($participantProgram) {
                    $enrollmentCode = $participantProgram->enrollment_code ?? 'N/A';
                }
            }
            
            // Obtener datos del apoderado (emergency contact)
            $apoderado = $participant->emergencyContacts->first();
            $apoderadoName = $apoderado ? ucwords(strtolower(trim($apoderado->name))) : 'No disponible';
            $apoderadoEmail = $apoderado ? $apoderado->email : 'No disponible';
            $apoderadoPhone = $apoderado ? $apoderado->phone : 'No disponible';
            
            // Calcular totales correctamente
            $totalAmount = (float) ($order->total_amount ?? 0); // Precio original
            $totalDiscounts = (float) ($order->discount ?? 0); // Descuentos aplicados
            $netAmount = (float) ($order->final_amount ?? $totalAmount); // Monto final después de descuentos
            $totalPaid = (float) $payments->whereIn('status', ['approved', 'completed', 'paid'])->sum('amount');
            $pendingAmount = max($netAmount - $totalPaid, 0);
            $progressPercentage = $netAmount > 0 ? round(($totalPaid / $netAmount) * 100, 2) : 0;
            
            // Determinar estado correctamente
            $status = 'pending';
            if ($totalPaid >= $netAmount) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partial';
            }

            // Obtener historial de pagos
            $paymentHistory = $payments->whereIn('status', ['approved', 'completed', 'paid'])->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'date' => $payment->transaction_date ?? $payment->created_at,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->paymentGateway->name ?? 'N/A',
                    'status' => 'Pagado',
                    'authorization_code' => $payment->authorization_code,
                    'transaction_id' => $payment->transaction_id
                ];
            });

            // Obtener próximos vencimientos
            $upcomingPayments = collect();
            if ($order->installmentPlan) {
                $upcomingPayments = $order->installmentPlan->installments->where('status', 'pending')->map(function ($installment) {
                    return [
                        'id' => $installment->id,
                        'installment_number' => $installment->installment_number,
                        'due_date' => $installment->due_date,
                        'amount' => (float) $installment->amount,
                        'status' => 'Pendiente'
                    ];
                });
            }

            // Obtener descuentos aplicados
            $discountsDetail = [];
            if ($totalDiscounts > 0) {
                $discountsDetail[] = [
                    'comment' => 'Descuento aplicado',
                    'type' => 'fixed',
                    'value' => (float) $totalDiscounts
                ];
            }

            return [
                'id' => $order->id,
                'participant_name' => $this->formatParticipantName($participant),
                'participant_document' => $participant->document_number,
                'participant_email' => $participant->email,
                'participant_phone' => $participant->phone,
                'apoderado_name' => $apoderadoName,
                'apoderado_email' => $apoderadoEmail,
                'apoderado_phone' => $apoderadoPhone,
                'program_name' => $program->name,
                'program_departure_date' => $program->departure_date,
                'enrollment_code' => $enrollmentCode,
                'sales_executive_name' => $program->salesExecutive->name ?? 'N/A',
                'total_amount' => (float) $totalAmount,
                'total_discounts' => (float) $totalDiscounts,
                'net_amount' => (float) $netAmount,
                'total_paid' => (float) $totalPaid,
                'pending_amount' => (float) $pendingAmount,
                'progress_percentage' => $progressPercentage,
                'discounts_detail' => $discountsDetail,
                'payment_history' => $paymentHistory,
                'upcoming_payments' => $upcomingPayments,
                'status' => $status
            ];
        });
    }

    private function calculateParticipantBalance($participant)
    {
        // Simulación del balance del participante
        return $participant->orders->sum('total_amount') - $participant->orders->sum('paid_amount');
    }

    private function getParticipantPayments($participant, $dateFrom, $dateTo)
    {
        return $participant->orders()
            ->with('payments')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get()
            ->pluck('payments')
            ->flatten();
    }

    /**
     * Formatear nombre del participante en Title Case
     */
    private function formatParticipantName($participant)
    {
        $parts = [];
        
        if ($participant->first_name) {
            $parts[] = ucwords(strtolower(trim($participant->first_name)));
        }
        if ($participant->second_name) {
            $parts[] = ucwords(strtolower(trim($participant->second_name)));
        }
        if ($participant->first_last_name) {
            $parts[] = ucwords(strtolower(trim($participant->first_last_name)));
        }
        if ($participant->second_last_name) {
            $parts[] = ucwords(strtolower(trim($participant->second_last_name)));
        }
        
        return implode(' ', $parts);
    }
}
