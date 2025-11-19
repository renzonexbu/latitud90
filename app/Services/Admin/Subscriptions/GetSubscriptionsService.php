<?php

namespace App\Services\Admin\Subscriptions;

use App\Models\ProgramSubscription;
use App\Models\ProgramCourse;
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
            // Cargar el plan de cuotas manualmente usando participant_id y program_id
            $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->with('installments')
                ->first();

            $totalInstallments = $installmentPlan ? $installmentPlan->total_installments : 0;
            $paidInstallments = $installmentPlan ? $installmentPlan->installments->where('is_paid', true)->count() : 0;
            $totalAmount = $installmentPlan ? $installmentPlan->installments->sum('amount') : 0;
            $paidAmount = $installmentPlan ? $installmentPlan->installments->where('is_paid', true)->sum('amount') : 0;

            return [
                'id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'status' => $subscription->status,
                'payment_method' => $subscription->payment_method,
                'created_at' => $subscription->created_at->toDateString(),
                'participant' => [
                    'id' => $subscription->participant->id,
                    'name' => $subscription->participant->full_name,
                    'document' => $subscription->participant->document_number,
                    'document_type' => $subscription->participant->documentType?->name ?? 'N/A',
                ],
                'program' => [
                    'id' => $subscription->programCourse->id,
                    'name' => $subscription->programCourse->name,
                    'destination' => $subscription->programCourse->program->destination ?? '',
                ],
                'institution' => [
                    'name' => $subscription->programCourse->course->institution->name ?? 'N/A',
                ],
                'total_installments' => $totalInstallments,
                'paid_installments' => $paidInstallments,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $totalAmount - $paidAmount,
                'payment_percentage' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0,
            ];
        });

        // Obtener estadísticas
        $stats = $this->getStats();

        // Obtener program_courses para los filtros
        $programs = ProgramCourse::where('active', true)
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
                'filters_applied' => $request->only(['participant_name', 'subscription_status', 'program_id', 'date_from', 'date_to']),
                'stats' => $stats,
            ]
        );

        return [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
            'filters' => $request->only(['participant_name', 'subscription_status', 'program_id', 'date_from', 'date_to']),
            'programs' => $programs
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

        // Filtro de programa
        if ($request->program_id) {
            $query->where('program_id', $request->program_id);
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
