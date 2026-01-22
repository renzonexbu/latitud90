<?php

namespace App\Console\Commands;

use App\Models\ProgramSubscription;
use App\Models\Participant;
use App\Models\ProgramCourse;
use App\Models\Order;
use App\Models\InstallmentPlan;
use App\Models\VirtualPosPlan;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Jobs\SyncSubscriptionPaymentsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecoverOrphanSubscription extends Command
{
    protected $signature = 'subscription:recover {virtualpos_id : ID de la suscripción en VirtualPos}';
    protected $description = 'Recupera una suscripción huérfana de VirtualPos y la registra en la BD local';

    public function handle()
    {
        $virtualPosId = $this->argument('virtualpos_id');

        $this->info("Recuperando suscripción: {$virtualPosId}");

        // Verificar si ya existe en BD
        $existing = ProgramSubscription::where('virtualpos_subscription_id', $virtualPosId)->first();
        if ($existing) {
            $this->error("La suscripción ya existe en BD con ID: {$existing->id}");
            return 1;
        }

        // Obtener datos de VirtualPos
        $virtualPosService = new VirtualPosSubscriptionService();
        $response = $virtualPosService->getSubscription($virtualPosId);

        if (!$response || ($response['status'] ?? '') !== 'OK') {
            $this->error("No se pudo obtener la suscripción de VirtualPos");
            $this->error(json_encode($response, JSON_PRETTY_PRINT));
            return 1;
        }

        $subscriptionData = $response['suscription'] ?? [];

        $this->info("Estado en VirtualPos: " . ($subscriptionData['status'] ?? 'N/A'));
        $this->info("Plan: " . ($subscriptionData['plan_name'] ?? 'N/A'));
        $this->info("Cliente: " . ($subscriptionData['client']['email'] ?? 'N/A'));

        // Extraer IDs del service_id (formato: SUB_{program_course_id}_{participant_id}_{timestamp})
        $serviceId = $subscriptionData['service_id'] ?? '';
        $parts = explode('_', $serviceId);

        if (count($parts) < 3) {
            $this->error("service_id no tiene el formato esperado: {$serviceId}");
            return 1;
        }

        $programCourseId = (int) $parts[1];
        $participantId = (int) $parts[2];

        $this->info("Program Course ID: {$programCourseId}");
        $this->info("Participant ID: {$participantId}");

        // Verificar que existan
        $participant = Participant::find($participantId);
        if (!$participant) {
            $this->error("Participante no encontrado con ID: {$participantId}");
            return 1;
        }

        $programCourse = ProgramCourse::find($programCourseId);
        if (!$programCourse) {
            $this->error("ProgramCourse no encontrado con ID: {$programCourseId}");
            return 1;
        }

        $this->info("Participante: {$participant->full_name}");
        $this->info("Programa: {$programCourse->name}");

        // Mostrar charges
        $chargeProgram = $subscriptionData['charge_program'] ?? [];
        $this->info("\nCharges en VirtualPos:");
        foreach ($chargeProgram as $index => $charge) {
            $this->info("  Cuota " . ($index + 1) . ": {$charge['status']} - \${$charge['amount']}");
        }

        if (!$this->confirm('¿Desea crear la suscripción en la BD local?')) {
            $this->info('Operación cancelada');
            return 0;
        }

        DB::beginTransaction();

        try {
            // Calcular total
            $totalAmount = array_sum(array_column($chargeProgram, 'amount'));
            $installments = count($chargeProgram);
            $firstInstallmentAmount = $chargeProgram[0]['amount'] ?? $totalAmount;

            // Obtener plan de VirtualPos
            $virtualPosPlan = VirtualPosPlan::where('virtualpos_plan_id', $subscriptionData['plan_id'] ?? '')->first();

            // Construir buyer_data desde client de VirtualPos
            $clientData = $subscriptionData['client'] ?? [];
            $buyerData = [
                'email' => $clientData['email'] ?? '',
                'first_name' => $clientData['first_name'] ?? '',
                'second_name' => '',
                'first_last_name' => $clientData['last_name'] ?? '',
                'second_last_name' => '',
                'document_number' => $clientData['social_id'] ?? '',
                'document_type' => 'RUT',
                'phone' => $clientData['phone'] ?? '',
                'code_phone' => '+56',
            ];

            // Crear suscripción
            $subscription = ProgramSubscription::create([
                'participant_id' => $participantId,
                'program_id' => $programCourseId,
                'virtualpos_subscription_id' => $virtualPosId,
                'virtualpos_plan_id' => $subscriptionData['plan_id'] ?? null,
                'plan_name' => $subscriptionData['plan_name'] ?? $programCourse->name,
                'status' => $subscriptionData['status'] ?? 'ACTIVA',
                'amount' => $firstInstallmentAmount,
                'total_amount' => $totalAmount,
                'installments' => $installments,
                'currency' => 'CLP',
                'automatic_renewal' => 'F',
                'subscription_date' => $subscriptionData['suscription_date'] ?? now(),
                'channel' => 'WEB',
                'service_id' => $serviceId,
                'payment_method' => $subscriptionData['payment_method'] ?? null,
                'charge_program' => $chargeProgram,
                'client_data' => $clientData,
                'buyer_data' => $buyerData,
                'api_response' => $response,
            ]);

            $this->info("ProgramSubscription creada con ID: {$subscription->id}");

            // Crear orden
            $order = Order::create([
                'participant_id' => $participantId,
                'program_id' => $programCourseId,
                'order_number' => 'SUB-' . str_pad($subscription->id, 8, '0', STR_PAD_LEFT),
                'total_amount' => $totalAmount,
                'final_amount' => $totalAmount,
                'total_installments' => $installments,
                'status' => 'processing',
                'payment_type' => 'monthly',
            ]);

            $this->info("Order creada con ID: {$order->id}");

            // Crear plan de cuotas
            $installmentPlan = InstallmentPlan::create([
                'order_id' => $order->id,
                'program_id' => $programCourseId,
                'participant_id' => $participantId,
                'total_amount' => $totalAmount,
                'total_installments' => $installments,
                'payment_type' => 'monthly',
                'status' => 'active',
                'start_date' => now(),
                'notes' => 'Plan de suscripción VirtualPos (recuperado) - ' . $installments . ' cuotas',
            ]);

            $this->info("InstallmentPlan creado con ID: {$installmentPlan->id}");

            // Estados aprobados
            $approvedStatuses = ['pagado', 'procesando', 'aprobado', 'approved', 'paid', 'success'];

            // Crear cuotas
            foreach ($chargeProgram as $index => $charge) {
                $chargeStatus = strtolower($charge['status'] ?? '');
                $status = in_array($chargeStatus, $approvedStatuses) ? 'paid' : 'pending';
                $isPaid = $status === 'paid';

                $installmentPlan->installments()->create([
                    'installment_number' => $index + 1,
                    'virtualpos_charge_id' => $charge['id'],
                    'amount' => $charge['amount'],
                    'due_date' => $charge['charge_date'],
                    'status' => $status,
                    'is_paid' => $isPaid,
                    'paid_at' => $isPaid ? now() : null,
                ]);

                $this->info("  Installment " . ($index + 1) . " creada: {$status}");
            }

            DB::commit();

            $this->info("\n✓ Suscripción recuperada exitosamente");

            // Ejecutar job de sincronización para procesar pagos y enviar emails
            if ($this->confirm('¿Ejecutar sincronización de pagos ahora? (creará Payment y enviará emails)')) {
                $this->info("Ejecutando SyncSubscriptionPaymentsJob...");

                try {
                    SyncSubscriptionPaymentsJob::dispatchSync($subscription->id);
                    $this->info("✓ Job ejecutado exitosamente");

                    // Verificar si se creó el Payment
                    $payment = \App\Models\Payment::where('external_payment_id', 'like', 'cid_%')
                        ->whereHas('orderDetail', function($q) use ($order) {
                            $q->where('order_id', $order->id);
                        })
                        ->first();

                    if ($payment) {
                        $this->info("✓ Payment creado con ID: {$payment->id}");
                        $this->info("  Email enviado: " . ($payment->email_sent ? 'Sí' : 'No'));
                    } else {
                        $this->warn("⚠ No se detectó Payment aún");
                    }
                } catch (\Exception $e) {
                    $this->error("Error en job: " . $e->getMessage());
                }
            }

            Log::info('Suscripción huérfana recuperada', [
                'virtualpos_id' => $virtualPosId,
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
            ]);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            Log::error('Error recuperando suscripción huérfana', [
                'virtualpos_id' => $virtualPosId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
