<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            // Agregar columna para identificar la fuente/método de pago de la cuota
            $table->enum('payment_source', [
                'subscription',      // Pago automático vía suscripción VirtualPos
                'manual_cash',       // Pago presencial en efectivo
                'manual_transfer',   // Transferencia bancaria manual
                'manual_online',     // Pago online manual (sin suscripción)
                'manual_check',      // Pago con cheque
                'manual_other'       // Otro método manual
            ])->nullable()->after('payment_id')->comment('Método/fuente de pago de la cuota');

            // Agregar índice para búsquedas rápidas por fuente de pago
            $table->index('payment_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex(['payment_source']);
            $table->dropColumn('payment_source');
        });
    }
};
