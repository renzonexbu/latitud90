<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega el campo payment_source para identificar el origen del pago:
     * - online: Pago total online (VirtualPos, Khipu, etc.)
     * - subscription: Pago de suscripción PAT (Pago Automático con Tarjeta)
     * - presencial: Pago presencial/manual
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_source', [
                'online',       // Pago total online (VirtualPos, Khipu, Internacional, etc.)
                'subscription', // Pago de suscripción PAT
                'presencial'    // Pago presencial/manual/offline
            ])->nullable()->after('status')->comment('Origen/tipo de pago');

            $table->index('payment_source');
        });

        // Actualizar registros existentes basándose en payment_gateway y payment_option
        // 1. Pagos presenciales: payment_gateway.code = 'presencial'
        DB::statement("
            UPDATE payments p
            JOIN payment_gateways pg ON p.payment_gateway_id = pg.id
            SET p.payment_source = 'presencial'
            WHERE pg.code = 'presencial'
        ");

        // 2. Pagos de suscripción: payment_option.code = 'subscription_virtualpos'
        DB::statement("
            UPDATE payments p
            JOIN payment_options po ON p.payment_option_id = po.id
            SET p.payment_source = 'subscription'
            WHERE po.code = 'subscription_virtualpos'
            AND p.payment_source IS NULL
        ");

        // 3. El resto son pagos online totales
        DB::statement("
            UPDATE payments
            SET payment_source = 'online'
            WHERE payment_source IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_source']);
            $table->dropColumn('payment_source');
        });
    }
};
