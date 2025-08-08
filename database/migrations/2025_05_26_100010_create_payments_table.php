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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Relaciones
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('order_detail_id')->constrained('orders_detail')->onDelete('cascade');
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods');
            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes');

            // Identificadores de la transacción
            $table->string('buy_order')->nullable();            // Para Transbank
            $table->string('session_id')->nullable();           // Para Transbank
            $table->string('token')->nullable();                // Para Transbank
            $table->string('external_payment_id')->nullable();  // Para Khipu u otros (payment_id, etc.)

            // Respuesta de la pasarela
            $table->string('authorization_code')->nullable();   // Código de autorización
            $table->string('response_code')->nullable();        // Código de respuesta
            $table->string('vci')->nullable();                  // VCI (Voucher de Compra Internet)
            $table->datetime('transaction_date')->nullable();   // Fecha de transacción
            $table->datetime('accounting_date')->nullable();    // Fecha contable

            // Detalles de la tarjeta (enmascarados)
            $table->string('card_number')->nullable();          // Últimos 4 dígitos
            $table->string('card_type')->nullable();            // Tipo de tarjeta
            $table->integer('installments_number')->nullable(); // Número de cuotas

            // Estado del pago
            // Usamos string para flexibilizar (e.g. approved, rejected, refunded, etc.)
            $table->string('status', 32)->default('pending');

            // Respuesta completa de la pasarela (JSON)
            $table->json('gateway_response')->nullable();
            $table->json('raw_notification')->nullable();

            // Campos adicionales
            $table->string('commerce_code')->nullable();        // Código de comercio
            $table->decimal('amount', 10, 2);                   // Monto del pago
            $table->char('currency', 3)->default('CLP');        // Moneda
            $table->decimal('balance', 10, 2)->nullable();      // Saldo restante (para cuotas)
            $table->text('error_message')->nullable();          // Mensaje de error si falla

            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['order_detail_id', 'status']);
            $table->index(['buy_order']);
            $table->index(['token']);
            $table->index(['external_payment_id']);
            $table->index(['authorization_code']);
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
