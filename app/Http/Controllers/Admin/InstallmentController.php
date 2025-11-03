<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Services\Admin\Installments\InstallmentRepaymentService;
use App\Services\Admin\Installments\InstallmentRecalculationService;
use App\Modules\Installments\Contracts\InstallmentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class InstallmentController extends Controller
{
    protected $installmentRepaymentService;
    protected $installmentRecalculationService;
    protected InstallmentServiceInterface $installmentService;

    public function __construct(
        InstallmentRepaymentService $installmentRepaymentService,
        InstallmentRecalculationService $installmentRecalculationService,
        InstallmentServiceInterface $installmentService
    ) {
        $this->installmentRepaymentService = $installmentRepaymentService;
        $this->installmentRecalculationService = $installmentRecalculationService;
        $this->installmentService = $installmentService;
    }

    /**
     * Reestructurar un plan de cuotas
     */
    public function restructure(Request $request)
    {
        try {
            $request->validate([
                'installment_plan_id' => 'required|exists:installment_plans,id',
                'new_total_installments' => 'required|integer|min:1',
                'reason' => 'required|string|max:500',
            ]);

            $installmentPlanId = $request->input('installment_plan_id');
            $newTotalInstallments = $request->input('new_total_installments');
            $reason = $request->input('reason');

            // Usar el nuevo módulo de Installments si está habilitado
            if ($this->installmentService->isEnabled()) {
                $success = $this->installmentService->restructurePlan(
                    $installmentPlanId,
                    $newTotalInstallments,
                    $reason
                );

                if (!$success) {
                    throw new Exception('No se pudo reestructurar el plan de cuotas');
                }

                $result = ['success' => true];
            } else {
                // Fallback al servicio antiguo si el módulo está deshabilitado
                $result = $this->installmentRepaymentService->repactInstallments(
                    $installmentPlanId,
                    $newTotalInstallments,
                    $reason
                );
            }

            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cuotas reestructuradas exitosamente',
                    'data' => $result
                ]);
            }

            // Para Inertia.js, redirigir con mensaje de éxito
            return redirect()->back()->with('success', 'Cuotas reestructuradas exitosamente');

        } catch (Exception $e) {
            Log::error('Error al reestructurar cuotas: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            // Si es una petición AJAX, devolver JSON con error
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al reestructurar las cuotas: ' . $e->getMessage()
                ], 500);
            }

            // Para Inertia.js, redirigir con mensaje de error
            return redirect()->back()->withErrors([
                'restructure' => 'Error al reestructurar las cuotas: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Recalcular cuotas después de aplicar un descuento
     */
    public function recalculateAfterDiscount(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'installment_plan_id' => 'required|exists:installment_plans,id',
            ]);

            $installmentPlanId = $request->input('installment_plan_id');

            // Usar el nuevo módulo de Installments si está habilitado
            if ($this->installmentService->isEnabled()) {
                $success = $this->installmentService->recalculateAfterDiscount($installmentPlanId);

                if (!$success) {
                    throw new Exception('No se pudo recalcular el plan de cuotas');
                }

                $result = ['success' => true];
            } else {
                // Fallback al servicio antiguo si el módulo está deshabilitado
                $result = $this->installmentRecalculationService->recalculateInstallmentsAfterDiscount(
                    $installmentPlanId
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Cuotas recalculadas exitosamente después de aplicar descuento',
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Error al recalcular cuotas después del descuento: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al recalcular las cuotas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un plan de cuotas
     */
    public function show(InstallmentPlan $installmentPlan): JsonResponse
    {
        try {
            $installmentPlan->load([
                'installments' => function ($query) {
                    $query->orderBy('installment_number', 'asc');
                },
                'order',
                'program',
                'participant'
            ]);

            // Contar cuotas por estado
            $stats = [
                'total' => $installmentPlan->installments->count(),
                'paid' => $installmentPlan->installments->where('status', 'paid')->count(),
                'pending' => $installmentPlan->installments->where('status', 'pending')->count(),
                'overdue' => $installmentPlan->installments->where('status', 'overdue')->count(),
                'cancelled' => $installmentPlan->installments->where('status', 'cancelled')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'installment_plan' => $installmentPlan,
                    'stats' => $stats
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al mostrar plan de cuotas: ' . $e->getMessage(), [
                'installment_plan_id' => $installmentPlan->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al cargar el plan de cuotas'
            ], 500);
        }
    }

    /**
     * Listar cuotas de un plan específico
     */
    public function listInstallments(InstallmentPlan $installmentPlan): JsonResponse
    {
        try {
            $installments = $installmentPlan->installments()
                ->with(['paymentOrder', 'paymentOrderDetail', 'payment'])
                ->orderBy('installment_number', 'asc')
                ->get();

            // Formatear datos para el frontend
            $formattedInstallments = $installments->map(function ($installment) {
                return [
                    'id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'amount' => $installment->amount,
                    'due_date' => $installment->due_date,
                    'status' => $installment->status,
                    'paid_at' => $installment->paid_at,
                    'payment_id' => $installment->payment_id,
                    'payment_order_id' => $installment->payment_order_id,
                    'payment_order_detail_id' => $installment->payment_order_detail_id,
                    'adjusted_at' => $installment->adjusted_at,
                    'adjustment_reason' => $installment->adjustment_reason,
                    'notes' => $installment->notes,
                    'is_paid' => !is_null($installment->payment_id),
                    'payment_details' => $installment->payment ? [
                        'transaction_date' => $installment->payment->transaction_date,
                        'authorization_code' => $installment->payment->authorization_code,
                        'status' => $installment->payment->status,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'installments' => $formattedInstallments,
                    'total_count' => $formattedInstallments->count(),
                    'installment_plan' => [
                        'id' => $installmentPlan->id,
                        'total_amount' => $installmentPlan->total_amount,
                        'total_installments' => $installmentPlan->total_installments,
                        'status' => $installmentPlan->status,
                        'start_date' => $installmentPlan->start_date,
                        'end_date' => $installmentPlan->end_date,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al listar cuotas: ' . $e->getMessage(), [
                'installment_plan_id' => $installmentPlan->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al cargar las cuotas'
            ], 500);
        }
    }
}
