<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\ProgramSubscription;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Helpers\PaymentDocumentTypeHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncRejectedSubscriptionPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:sync-rejected
                            {--dry-run : Solo mostrar lo que se haría sin ejecutar cambios}
                            {--subscription= : ID de suscripción específica a sincronizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los pagos rechazados de suscripciones que no están en la tabla payments';

    protected VirtualPosSubscriptionService $virtualPosService;

    public function __construct(VirtualPosSubscriptionService $virtualPosService)
    {
        parent::__construct();
        $this->virtualPosService = $virtualPosService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $subscriptionId = $this->option('subscription');

        $this->info('=== Sincronización de Pagos Rechazados de Suscripciones ===');
        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se ejecutarán cambios');
        }

        // Obtener suscripciones activas
        $query = ProgramSubscription::whereIn('status', ['ACTIVA', 'FINALIZADA', 'CANCELADA'])
            ->whereNotNull('virtualpos_subscription_id');

        if ($subscriptionId) {
            $query->where('id', $subscriptionId);
        }

        $subscriptions = $query->get();

        $this->info("Suscripciones a procesar: {$subscriptions->count()}");

        $totalProcessed = 0;
        $totalCreated = 0;
        $totalSkipped = 0;
        $errors = [];

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->processSubscription($subscription, $dryRun);
                $totalProcessed++;
                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];
            } catch (\Exception $e) {
                $errors[] = [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ];
                Log::error('SyncRejectedPayments: Error procesando suscripción', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('=== RESUMEN ===');
        $this->info("Suscripciones procesadas: {$totalProcessed}");
        $this->info("Pagos rechazados creados: {$totalCreated}");
        $this->info("Pagos ya existentes (omitidos): {$totalSkipped}");

        if (count($errors) > 0) {
            $this->error("Errores: " . count($errors));
            foreach ($errors as $error) {
                $this->error("  - Suscripción {$error['subscription_id']}: {$error['error']}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Procesar una suscripción individual
     */
    protected function processSubscription(ProgramSubscription $subscription, bool $dryRun): array
    {
        $created = 0;
        $skipped = 0;

        // Obtener charges desde VirtualPos
        try {
            $subscriptionData = $this->virtualPosService->getSubscription($subscription->virtualpos_subscription_id);
            // Los charges vienen bajo suscription.charge_program
            $innerData = $subscriptionData['suscription'] ?? $subscriptionData;
            $charges = $innerData['charge_program'] ?? [];
        } catch (\Exception $e) {
            Log::warning('SyncRejectedPayments: No se pudieron obtener charges', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'error' => $e->getMessage()
            ]);
            return ['created' => 0, 'skipped' => 0];
        }

        // Filtrar solo charges rechazados
        $rejectedCharges = array_filter($charges, function ($charge) {
            $status = strtolower($charge['status'] ?? '');
            return $status === 'rechazado';
        });

        foreach ($rejectedCharges as $charge) {
            $chargeId = $charge['id'] ?? null;
            if (!$chargeId) continue;

            // Verificar si ya existe un Payment con este charge_id
            if (Payment::where('external_payment_id', $chargeId)->exists()) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY-RUN] Crearía payment rechazado para charge {$chargeId} - monto: {$charge['amount']}");
                $created++;
                continue;
            }

            // Crear el payment rechazado
            try {
                DB::beginTransaction();

                $order = $this->getOrCreateOrder($subscription);
                $installmentNumber = $this->extractInstallmentNumber($charge['description'] ?? '');
                $orderDetail = $this->createRejectedOrderDetail($order, $charge, $installmentNumber, $subscription);
                $payment = $this->createRejectedPayment($orderDetail, $charge, $subscription);

                DB::commit();

                Log::info('SyncRejectedPayments: Payment rechazado creado', [
                    'subscription_id' => $subscription->id,
                    'charge_id' => $chargeId,
                    'payment_id' => $payment->id
                ]);

                $created++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('SyncRejectedPayments: Error al crear payment', [
                    'subscription_id' => $subscription->id,
                    'charge_id' => $chargeId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Obtener o crear Order para la suscripción
     */
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

            $finalAmount = $subscription->total_amount ?? 0;
            $order = Order::create([
                'participant_id' => $subscription->participant_id,
                'program_id' => $subscription->program_id,
                'course_id' => $courseId,
                'participant_program_id' => $participantProgram->id ?? null,
                'order_number' => 'SUB-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'total_amount' => $finalAmount,
                'discount' => 0,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'payment_type' => 'monthly',
                'total_installments' => $subscription->total_installments ?? 0,
                'notes' => 'Orden creada para suscripción VirtualPos (sync)'
            ]);
        }

        return $order;
    }

    /**
     * Crear OrderDetail para cargo rechazado
     */
    protected function createRejectedOrderDetail(Order $order, array $charge, int $installmentNumber, ProgramSubscription $subscription): OrderDetail
    {
        $amount = $charge['amount'] ?? 0;
        $gateway = PaymentGateway::where('code', 'virtualpos')->first();
        $paymentOption = PaymentOption::where('code', 'subscription_virtualpos')->first();

        $buyerData = [];
        $savedBuyerData = $subscription->buyer_data ?? [];

        if (!empty($savedBuyerData) && isset($savedBuyerData['email'])) {
            $buyerFullName = trim(
                ($savedBuyerData['name'] ?? '') . ' ' .
                ($savedBuyerData['last_name'] ?? '') . ' ' .
                ($savedBuyerData['second_last_name'] ?? '')
            );
            $buyerFullName = preg_replace('/\s+/', ' ', $buyerFullName);

            $buyerData = [
                'name' => $buyerFullName,
                'email' => $savedBuyerData['email'],
                'document_number' => $savedBuyerData['original_document_number'] ?? $savedBuyerData['document_number'] ?? null,
            ];
        } else {
            $participant = $order->participant;
            if ($participant) {
                $buyerData = [
                    'name' => $participant->full_name,
                    'email' => $participant->email,
                    'document_number' => $participant->document_number,
                ];
            }
        }

        return OrderDetail::create(array_merge([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption?->id,
            'payment_gateway_id' => $gateway?->id,
            'base_amount' => $amount,
            'discount_amount' => 0,
            'amount' => $amount,
            'installment_number' => $installmentNumber,
            'status' => 'rejected',
            'is_paid' => false,
            'paid_at' => null,
            'due_date' => isset($charge['charge_date']) ? Carbon::parse($charge['charge_date']) : now(),
        ], $buyerData));
    }

    /**
     * Crear Payment rechazado
     */
    protected function createRejectedPayment(OrderDetail $orderDetail, array $charge, ProgramSubscription $subscription): Payment
    {
        $gateway = PaymentGateway::where('code', 'virtualpos')->first();
        $paymentOption = PaymentOption::where('code', 'subscription_virtualpos')->first();

        $paymentData = $charge['payment'] ?? [];
        $orderData = $paymentData['order'] ?? [];
        $paymentMethod = $subscription->payment_method ?? [];

        $cardBrand = $paymentData['card_type'] ?? ($paymentMethod['brand'] ?? null);
        $cardLast4 = $orderData['card_number'] ?? ($paymentData['card_number'] ?? ($paymentMethod['last4'] ?? null));
        $errorMessage = $paymentData['error_message'] ?? $charge['error_message'] ?? 'Cargo rechazado por VirtualPos';

        return Payment::create([
            'order_id' => $orderDetail->order_id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $gateway?->id,
            'payment_option_id' => $paymentOption?->id,
            'external_payment_id' => $charge['id'],
            'buy_order' => $subscription->virtualpos_subscription_id ?? null,
            'session_id' => null,
            'token' => $charge['id'],
            'status' => 'rejected',
            'payment_source' => 'subscription', // Identificar como pago de suscripción
            'amount' => $charge['amount'] ?? 0,
            'currency' => 'CLP',
            'installments_number' => $orderData['installment_number'] ?? ($paymentData['installments_number'] ?? 1),
            'installment_amount' => $orderData['installment_amount'] ?? ($paymentData['installment_amount'] ?? ($charge['amount'] ?? 0)),
            'transaction_date' => $this->parseTransactionDate($charge),
            'accounting_date' => $this->parseTransactionDate($charge),
            'authorization_code' => null,
            'response_code' => $paymentData['response_code'] ?? '-1',
            'vci' => $paymentData['vci'] ?? null,
            'card_type' => $cardBrand,
            'card_number' => $cardLast4,
            'commerce_code' => config('services.virtualpos.commerce_code') ?? null,
            'gateway_response' => $charge,
            'raw_notification' => $charge,
            'error_message' => $errorMessage,
            'email_sent' => false,
            'balance' => $charge['amount'] ?? 0,
            'document_type' => PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
        ]);
    }

    /**
     * Extraer número de cuota desde descripción
     */
    protected function extractInstallmentNumber(string $description): int
    {
        if (preg_match('/[Cc]uota\s*(\d+)/i', $description, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/[Cc]argo\s*(\d+)/i', $description, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/(\d+)\s+de\s+\d+/i', $description, $matches)) {
            return (int) $matches[1];
        }
        return 1;
    }

    /**
     * Parsear fecha de transacción
     */
    protected function parseTransactionDate(array $charge): ?Carbon
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
