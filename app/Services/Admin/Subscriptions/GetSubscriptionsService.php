<?php

namespace App\Services\Admin\Subscriptions;

use App\Models\ProgramSubscription;
use App\Models\VirtualPosPlan;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;

class GetSubscriptionsService
{
    use AdminLogging;

    /**
     * Obtener suscripciones con filtros y estadísticas
     */
    public function execute(Request $request): array
    {
        $query = ProgramSubscription::with([
            'participant.documentType',
            'programCourse.program',
            'programCourse.course.institution',
        ]);

        // Aplicar filtros
        $this->applyFilters($query, $request);

        $subscriptions = $query->latest()->paginate(15);

        // Transformar datos para el frontend
        $subscriptions->getCollection()->transform(function ($subscription) {
            // Cargar el plan de cuotas usando la relación directa program_subscription_id
            // Esto evita confusiones cuando un participante tiene múltiples suscripciones al mismo programa
            $installmentPlan = \App\Models\InstallmentPlan::where('program_subscription_id', $subscription->id)
                ->with('installments')
                ->first();

            // Fallback para datos antiguos que no tienen program_subscription_id
            if (!$installmentPlan) {
                $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id)
                    ->whereNull('program_subscription_id') // Solo planes huérfanos
                    ->with('installments')
                    ->first();
            }

            $totalInstallments = $installmentPlan ? $installmentPlan->total_installments : 0;
            $paidInstallments = $installmentPlan ? $installmentPlan->installments->where('status', 'paid')->count() : 0;
            $totalAmount = $installmentPlan ? $installmentPlan->installments->sum('amount') : 0;
            $paidAmount = $installmentPlan ? $installmentPlan->installments->where('status', 'paid')->sum('amount') : 0;

            // Obtener la última cuota pagada
            $lastPaidInstallment = $installmentPlan
                ? $installmentPlan->installments->where('status', 'paid')->sortByDesc('paid_at')->first()
                : null;
            $lastPaidAmount = $lastPaidInstallment ? $lastPaidInstallment->amount : null;

            // Obtener información del plan de VirtualPos
            $virtualPosPlan = VirtualPosPlan::where('virtualpos_plan_id', $subscription->virtualpos_plan_id)->first();
            $planInfo = null;

            if ($virtualPosPlan) {
                $planInfo = [
                    'id' => $virtualPosPlan->id,
                    'name' => $virtualPosPlan->name,
                    'is_personalized' => $virtualPosPlan->isPersonalized(),
                    'discount_type' => $virtualPosPlan->discount_type,
                    'discount_reason' => $virtualPosPlan->discount_reason,
                    'discount_amount' => $virtualPosPlan->discount_amount,
                    'original_price' => $virtualPosPlan->original_price,
                    'trip_price' => $virtualPosPlan->trip_price,
                    'monthly_amount' => $virtualPosPlan->monthly_amount,
                ];
            }

            // Obtener datos del pagador (buyer) desde buyer_data de la suscripción
            $buyerData = $subscription->buyer_data;
            $buyerName = 'N/A';
            if ($buyerData && is_array($buyerData)) {
                $buyerFirstName = $buyerData['first_name'] ?? '';
                $buyerLastName = $buyerData['first_last_name'] ?? '';
                $buyerName = trim("{$buyerFirstName} {$buyerLastName}") ?: 'N/A';
            }

            return [
                'id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'status' => $subscription->status,
                'payment_method' => $subscription->payment_method,
                'created_at' => $subscription->created_at->toDateString(),
                'participant' => $subscription->participant ? [
                    'id' => $subscription->participant->id,
                    'name' => $subscription->participant->full_name,
                    'document' => $subscription->participant->document_number,
                    'document_type' => $subscription->participant->documentType?->name ?? 'N/A',
                ] : [
                    'id' => null,
                    'name' => 'N/A',
                    'document' => 'N/A',
                    'document_type' => 'N/A',
                ],
                'buyer' => [
                    'name' => $buyerName,
                ],
                'program' => $subscription->programCourse ? [
                    'id' => $subscription->programCourse->id,
                    'name' => $subscription->programCourse->name,
                    'code' => $subscription->programCourse->code ?? 'N/A',
                    'destination' => $subscription->programCourse->program->destination ?? '',
                ] : [
                    'id' => null,
                    'name' => 'N/A',
                    'code' => 'N/A',
                    'destination' => '',
                ],
                'institution' => [
                    'name' => $subscription->programCourse?->course?->institution?->name ?? 'N/A',
                ],
                'plan' => $planInfo,
                'total_installments' => $totalInstallments,
                'paid_installments' => $paidInstallments,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $totalAmount - $paidAmount,
                'payment_percentage' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0,
                'last_paid_amount' => $lastPaidAmount,
            ];
        });

        // Obtener estadísticas
        $stats = $this->getStats();

        // Log the subscriptions list view
        $this->logView(
            'subscriptions',
            'SubscriptionList',
            0,
            "Lista de suscripciones consultada - Total: {$subscriptions->total()} registros",
            [
                'total_subscriptions' => $subscriptions->total(),
                'current_page' => $subscriptions->currentPage(),
                'per_page' => $subscriptions->perPage(),
                'filters_applied' => $request->only(['participant_name', 'subscription_status', 'program_code', 'date_from', 'date_to']),
                'stats' => $stats,
            ]
        );

        return [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
            'filters' => $request->only(['participant_name', 'subscription_status', 'program_code', 'date_from', 'date_to']),
        ];
    }

    /**
     * Aplicar filtros a la consulta
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro de búsqueda por nombre del participante
        if ($request->participant_name) {
            $query->whereHas('participant', function ($q) use ($request) {
                $q->where('first_last_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('second_last_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('first_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('second_name', 'like', '%' . $request->participant_name . '%');
            });
        }

        // Filtro de estado de la suscripción
        if ($request->subscription_status && $request->subscription_status !== 'all') {
            $query->where('status', $request->subscription_status);
        }

        // Filtro de programa por código
        if ($request->program_code) {
            $query->whereHas('programCourse', function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->program_code . '%');
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
    }

    /**
     * Obtener estadísticas de suscripciones
     */
    private function getStats(): array
    {
        return [
            'total' => ProgramSubscription::count(),
            'active' => ProgramSubscription::where('status', 'ACTIVA')->count(),
            'subscribing' => ProgramSubscription::whereIn('status', ['SUSCRIBIENDO', 'PENDIENTE'])->count(),
            'cancelled' => ProgramSubscription::whereIn('status', ['CANCELADA', 'RECHAZADA'])->count(),
        ];
    }
}
