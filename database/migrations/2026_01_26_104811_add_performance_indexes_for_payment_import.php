<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índices críticos para optimizar el rendimiento de:
     * - Importación masiva de pagos
     * - Scheduler de envío de emails pendientes
     * - Consultas de suscripciones activas
     * - Consultas de planes de cuotas
     */
    public function up(): void
    {
        // 1. payment_gateways: Index en 'code' para búsquedas rápidas
        // Usado en: ImportManualPaymentsService::processChunk (línea 704)
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->index('code', 'idx_payment_gateways_code');
        });

        // 2. payments: Índices para email tracking y scheduler
        // Usado en: SendPendingPaymentEmails command y email tracking
        Schema::table('payments', function (Blueprint $table) {
            // Para consultas de emails pendientes
            $table->index('email_sent', 'idx_payments_email_sent');

            // Para consultas combinadas de status y email
            $table->index(['status', 'email_sent'], 'idx_payments_status_email_sent');

            // Para consultas de emails con fecha
            $table->index(['email_sent', 'created_at'], 'idx_payments_email_sent_created_at');

            // Para consultas de intentos de email
            $table->index('email_attempts', 'idx_payments_email_attempts');

            // Para el scheduler: status + email_sent + created_at (covering index)
            $table->index(['status', 'email_sent', 'created_at'], 'idx_payments_scheduler_query');

            // Para consultas que ordenan por email_sent_at
            $table->index(['email_sent', 'email_sent_at'], 'idx_payments_email_sent_at');
        });

        // 3. program_subscriptions: Índice compuesto para validación de suscripciones activas
        // Usado en: ImportManualPaymentsService::validateNoActiveSubscription (línea 838)
        Schema::table('program_subscriptions', function (Blueprint $table) {
            // Optimiza: WHERE participant_id = X AND program_id = Y AND status IN ('ACTIVA', 'SUSCRIBIENDO')
            $table->index(['participant_id', 'program_id', 'status'], 'idx_prog_subs_participant_program_status');

            // Para consultas de suscripciones con fechas
            $table->index(['status', 'created_at'], 'idx_prog_subs_status_created_at');
        });

        // 4. installment_plans: Índice compuesto para búsqueda de planes activos
        // Usado en: ImportManualPaymentsService::handleInstallmentRestructure (línea 914)
        Schema::table('installment_plans', function (Blueprint $table) {
            // Optimiza: WHERE participant_id = X AND program_id = Y AND status = 'active'
            $table->index(['participant_id', 'program_id', 'status'], 'idx_inst_plans_participant_program_status');
        });

        // 5. installments: Índice adicional para búsquedas por status
        // Usado en: handleInstallmentRestructure para obtener cuotas pending
        Schema::table('installments', function (Blueprint $table) {
            // Para consultas: WHERE installment_plan_id = X AND status = 'pending'
            $table->index(['installment_plan_id', 'status'], 'idx_installments_plan_status');
        });

        // 6. orders: Índice compuesto adicional para JOIN optimizado con payments
        // Usado en: calculatePaidAmount con JOIN
        Schema::table('orders', function (Blueprint $table) {
            // Para consultas que filtran por participant + program juntos con fecha
            $table->index(['participant_id', 'program_id', 'created_at'], 'idx_orders_participant_program_date');
        });

        // 7. charge_attempts: Índice para consultas de cobros fallidos
        // Usado en: validateNoActiveSubscription (línea 850)
        Schema::table('charge_attempts', function (Blueprint $table) {
            // Para consultas: WHERE program_subscription_id = X AND status = 'failed'
            $table->index(['program_subscription_id', 'status', 'created_at'], 'idx_charge_attempts_sub_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropIndex('idx_payment_gateways_code');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_email_sent');
            $table->dropIndex('idx_payments_status_email_sent');
            $table->dropIndex('idx_payments_email_sent_created_at');
            $table->dropIndex('idx_payments_email_attempts');
            $table->dropIndex('idx_payments_scheduler_query');
            $table->dropIndex('idx_payments_email_sent_at');
        });

        Schema::table('program_subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_prog_subs_participant_program_status');
            $table->dropIndex('idx_prog_subs_status_created_at');
        });

        Schema::table('installment_plans', function (Blueprint $table) {
            $table->dropIndex('idx_inst_plans_participant_program_status');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex('idx_installments_plan_status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_participant_program_date');
        });

        Schema::table('charge_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_charge_attempts_sub_status_date');
        });
    }
};
