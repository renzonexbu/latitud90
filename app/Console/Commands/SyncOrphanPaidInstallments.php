<?php

namespace App\Console\Commands;

use App\Models\Installment;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use App\Models\ProgramSubscription;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Helpers\PaymentDocumentTypeHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOrphanPaidInstallments extends Command
{
    protected $signature = 'subscriptions:sync-orphan-payments
                            {--dry-run : Solo mostrar lo que se haría sin ejecutar cambios}';

    protected $description = 'Crea registros de Payment para cuotas marcadas como pagadas que no tienen Payment asociado';

    protected VirtualPosSubscriptionService $virtualPosService;

    public function __construct(VirtualPosSubscriptionService $virtualPosService)
    {
        parent::__construct();
        $this->virtualPosService = $virtualPosService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('=== Sincronización de Cuotas Pagadas sin Payment ===');
        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se ejecutarán cambios');
        }

        // Buscar cuotas pagadas con virtualpos_charge_id pero sin Payment
        $orphanInstallments = Installment::where(function ($q) {
                $q->where('status', 'paid')->orWhere('is_paid', true);
            })
            ->whereNotNull('virtualpos_charge_id')
            ->get()
            ->filter(function ($installment) {
                // Filtrar solo las que no tienen Payment
                return !Payment::where('external_payment_id', $installment->virtualpos_charge_id)->exists();
            });

        $this->info("Cuotas pagadas sin Payment: {$orphanInstallments->count()}");

        if ($orphanInstallments->isEmpty()) {
            $this->info('No hay cuotas huérfanas que procesar.');
            return Command::SUCCESS;
        }

        $created = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($orphanInstallments->count());
        $bar->start();

        foreach ($orphanInstallments as $installment) {
            try {
                $result = $this->processInstallment($installment, $dryRun);
                if ($result) {
                    $created++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error en cuota {$installment->id}: {$e->getMessage()}");
                Log::error('SyncOrphanPaidInstallments: Error', [
                    'installment_id' => $installment->id,
                    'error' => $e->getMessage()
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('=== RESUMEN ===');
        $this->info("Payments creados: {$created}");
        if ($errors > 0) {
            $this->error("Errores: {$errors}");
        }

        return Command::SUCCESS;
    }

    protected function processInstallment(Installment $installment, bool $dryRun): bool
    {
        $chargeId = $installment->virtualpos_charge_id;

        // Obtener el plan de cuotas y la suscripción
        $installmentPlan = $installment->installmentPlan;
        if (!$installmentPlan) {
            $this->warn("Cuota {$installment->id} sin plan de cuotas");
            return false;
        }

        $subscription = ProgramSubscription::where('participant_id', $installmentPlan->participant_id)
            ->where('program_id', $installmentPlan->program_id)
            ->first();

        if (!$subscription) {
            $this->warn("Cuota {$installment->id} sin suscripción asociada");
            return false;
        }

        // Obtener datos del charge desde VirtualPos
        $charge = null;
        try {
            $chargeData = $this->virtualPosService->getCharge($chargeId);
            $charge = $chargeData['charge'] ?? $chargeData;
        } catch (\Exception $e) {
            // Si no se puede obtener, usar datos mínimos
            $charge = [
                'id' => $chargeId,
                'amount' => $installment->amount,
                'charge_date' => $installment->paid_at ?? $installment->due_date,
                'description' => "Cargo {$installment->installment_number} de {$installmentPlan->total_installments}",
            ];
        }

        if ($dryRun) {
            $this->newLine();
            $this->line("  [DRY-RUN] Crearía Payment para cuota #{$installment->installment_number} - charge: {$chargeId} - monto: {$installment->amount}");
            return true;
        }

        DB::beginTransaction();
        try {
            // 1. Obtener o crear Order
            $order = $this->getOrCreateOrder($subscription);

            // 2. Crear o actualizar OrderDetail
            $orderDetail = $this->createOrderDetail($order, $charge, $installment, $subscription);

            // 3. Crear Payment
            $payment = $this->createPayment($orderDetail, $charge, $subscription);

            // 4. Actualizar la cuota con las referencias
            $installment->update([
                'payment_order_id' => $order->id,
                'payment_order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
            ]);

            DB::commit();

            Log::info('SyncOrphanPaidInstallments: Payment creado', [
                'installment_id' => $installment->id,
                'payment_id' => $payment->id,
                'charge_id' => $chargeId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getOrCreateOrder(ProgramSubscription $subscription): Order
    {
        $order = Order::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->whereIn('status', ['pending', 'paid', 'partial', 'processing'])
            ->first();

        if (!$order) {
            $participantProgram = DB::table('participant_program')
                ->where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->first();

            $programCourse = $subscription->programCourse;
            $courseId = $programCourse ? $programCourse->course_id : null;

            $order = Order::create([
                'participant_id' => $subscription->participant_id,
                'program_id' => $subscription->program_id,
                'subscription_id' => $subscription->id,
                'course_id' => $courseId,
                'participant_program_id' => $participantProgram->id ?? null,
                'order_number' => 'SUB-' . str_pad($subscription->id, 8, '0', STR_PAD_LEFT),
                'session_id' => $subscription->virtualpos_subscription_id,
                'total_amount' => 0,
                'discount' => 0,
                'final_amount' => $subscription->total_amount ?? 0,
                'total_installments' => $subscription->installments ?? 0,
                'payment_type' => 'monthly',
                'status' => 'pending',
                'notes' => 'Orden de suscripción VirtualPOS',
            ]);
        }

        return $order;
    }

    protected function createOrderDetail(Order $order, array $charge, Installment $installment, ProgramSubscription $subscription): OrderDetail
    {
        $amount = $charge['amount'] ?? $installment->amount;
        $gateway = PaymentGateway::where('code', 'virtualpos')->first();
        $paymentOption = PaymentOption::where('code', 'subscription_virtualpos')->first();

        // Verificar si ya existe OrderDetail para esta cuota
        $existingOrderDetail = OrderDetail::where('order_id', $order->id)
            ->where('installment_number', $installment->installment_number)
            ->first();

        if ($existingOrderDetail) {
            $existingOrderDetail->update([
                'status' => 'paid',
                'is_paid' => true,
                'paid_at' => $installment->paid_at ?? now(),
            ]);
            return $existingOrderDetail;
        }

        // Obtener datos del comprador
        $buyerData = $this->getBuyerData($subscription, $order);

        $orderDetail = OrderDetail::create(array_merge([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption?->id,
            'payment_gateway_id' => $gateway?->id,
            'base_amount' => $amount,
            'discount_amount' => 0,
            'amount' => $amount,
            'installment_number' => $installment->installment_number,
            'status' => 'paid',
            'is_paid' => true,
            'paid_at' => $installment->paid_at ?? now(),
            'due_date' => $installment->due_date,
        ], $buyerData));

        $order->increment('total_amount', $amount);

        return $orderDetail;
    }

    protected function createPayment(OrderDetail $orderDetail, array $charge, ProgramSubscription $subscription): Payment
    {
        $gateway = PaymentGateway::where('code', 'virtualpos')->first();
        $paymentOption = PaymentOption::where('code', 'subscription_virtualpos')->first();

        $paymentData = $charge['payment'] ?? [];
        $orderData = $paymentData['order'] ?? [];
        $paymentMethod = $subscription->payment_method ?? [];

        $cardBrand = $paymentData['card_type'] ?? ($paymentMethod['brand'] ?? null);
        $cardLast4 = $orderData['card_number'] ?? ($paymentData['card_number'] ?? ($paymentMethod['last4'] ?? null));
        $authCode = $orderData['auth_code'] ?? ($paymentData['auth_code'] ?? null);

        return Payment::create([
            'order_id' => $orderDetail->order_id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $gateway?->id,
            'payment_option_id' => $paymentOption?->id,
            'external_payment_id' => $charge['id'],
            'buy_order' => $subscription->virtualpos_subscription_id,
            'token' => $charge['id'],
            'status' => 'completed',
            'payment_source' => 'subscription',
            'amount' => $charge['amount'] ?? $orderDetail->amount,
            'currency' => 'CLP',
            'installments_number' => $orderData['installment_number'] ?? 1,
            'installment_amount' => $orderData['installment_amount'] ?? ($charge['amount'] ?? $orderDetail->amount),
            'transaction_date' => $this->parseDate($charge),
            'accounting_date' => $this->parseDate($charge),
            'authorization_code' => $authCode,
            'response_code' => '0',
            'card_type' => $cardBrand,
            'card_number' => $cardLast4,
            'commerce_code' => config('services.virtualpos.commerce_code'),
            'gateway_response' => $charge,
            'raw_notification' => $charge,
            'email_sent' => true, // Marcar como enviado para no enviar emails
            'email_sent_at' => now(),
            'balance' => 0,
            'document_type' => PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
        ]);
    }

    protected function getBuyerData(ProgramSubscription $subscription, Order $order): array
    {
        $savedBuyerData = $subscription->buyer_data ?? [];

        if (!empty($savedBuyerData) && isset($savedBuyerData['email'])) {
            $buyerFullName = trim(
                ($savedBuyerData['first_name'] ?? '') . ' ' .
                ($savedBuyerData['second_name'] ?? '') . ' ' .
                ($savedBuyerData['first_last_name'] ?? '') . ' ' .
                ($savedBuyerData['second_last_name'] ?? '')
            );
            $buyerFullName = preg_replace('/\s+/', ' ', $buyerFullName);

            return [
                'name' => $buyerFullName ?: ($savedBuyerData['name'] ?? 'N/A'),
                'email' => $savedBuyerData['email'],
                'document_number' => $savedBuyerData['original_document_number'] ?? $savedBuyerData['document_number'] ?? null,
            ];
        }

        $participant = $order->participant;
        if ($participant) {
            return [
                'name' => $participant->full_name,
                'email' => $participant->email,
                'document_number' => $participant->document_number,
            ];
        }

        return ['name' => 'N/A', 'email' => 'N/A'];
    }

    protected function parseDate(array $charge): ?Carbon
    {
        $paymentData = $charge['payment'] ?? [];

        if (isset($paymentData['authorized_at'])) {
            try {
                return Carbon::parse($paymentData['authorized_at']);
            } catch (\Exception $e) {}
        }

        if (isset($charge['charge_date'])) {
            try {
                return Carbon::parse($charge['charge_date']);
            } catch (\Exception $e) {}
        }

        return now();
    }
}
