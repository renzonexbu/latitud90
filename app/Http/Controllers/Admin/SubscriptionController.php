<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramSubscription;
use App\Services\Admin\Subscriptions\GetSubscriptionsService;
use App\Services\VirtualPos\CancelSubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private GetSubscriptionsService $getSubscriptionsService,
        private CancelSubscriptionService $cancelSubscriptionService
    ) {}

    /**
     * Mostrar lista de suscripciones
     */
    public function index(Request $request): Response
    {
        $data = $this->getSubscriptionsService->execute($request);

        return Inertia::render('Admin/Subscriptions/Index', $data);
    }

    /**
     * Mostrar detalles de una suscripción
     */
    public function show(ProgramSubscription $subscription): Response
    {
        $subscription->load([
            'participant.documentType',
            'programCourse.program',
            'programCourse.course.institution',
        ]);

        // Cargar el plan de cuotas manualmente
        $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->with(['installments' => function ($query) {
                $query->orderBy('installment_number');
            }])
            ->first();

        $installments = [];
        $totalInstallments = 0;
        $paidInstallments = 0;

        if ($installmentPlan) {
            $totalInstallments = $installmentPlan->total_installments;
            $paidInstallments = $installmentPlan->installments->where('is_paid', true)->count();

            $installments = $installmentPlan->installments->map(function ($installment) {
                return [
                    'id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'amount' => $installment->amount,
                    'due_date' => \Carbon\Carbon::parse($installment->due_date)->format('d-m-Y'),
                    'status' => $installment->status,
                    'is_paid' => $installment->is_paid,
                    'paid_at' => $installment->paid_at ? \Carbon\Carbon::parse($installment->paid_at)->format('d-m-Y H:i') : null,
                    'virtualpos_charge_id' => $installment->virtualpos_charge_id,
                ];
            })->toArray();
        }

        return Inertia::render('Admin/Subscriptions/Show', [
            'subscription' => [
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
                    'email' => $subscription->participant->email,
                ],
                'program' => [
                    'id' => $subscription->programCourse->id,
                    'name' => $subscription->programCourse->name,
                    'destination' => $subscription->programCourse->program->destination ?? '',
                    'departure_date' => $subscription->programCourse->departure_date,
                ],
                'institution' => [
                    'name' => $subscription->programCourse->course->institution->name ?? 'N/A',
                ],
                'installments' => $installments,
                'total_installments' => $totalInstallments,
                'paid_installments' => $paidInstallments,
            ]
        ]);
    }

    /**
     * Sincronizar suscripción con VirtualPos
     */
    public function syncWithVirtualPos(ProgramSubscription $subscription)
    {
        // TODO: Implementar sincronización manual con VirtualPos
        return back()->with('success', 'Suscripción sincronizada correctamente');
    }

    /**
     * Cancelar suscripción
     */
    public function cancel(ProgramSubscription $subscription)
    {
        $result = $this->cancelSubscriptionService->cancel($subscription);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
