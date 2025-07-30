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
            $table->foreignId('order_id')->constrained('orders'); // FK a orders
            
            // Campos específicos de Transbank/Webpay Plus
            $table->string('buy_order')->unique(); // Orden de compra única
            $table->string('session_id')->nullable(); // ID de sesión
            $table->string('token')->nullable(); // Token de transacción
            
            // Respuesta de la pasarela
            $table->string('authorization_code')->nullable(); // Código de autorización
            $table->string('response_code')->nullable(); // Código de respuesta
            $table->string('vci')->nullable(); // VCI (Voucher de Compra Internet)
            $table->datetime('transaction_date')->nullable(); // Fecha de transacción
            $table->datetime('accounting_date')->nullable(); // Fecha contable
            
            // Detalles de la tarjeta (enmascarados)
            $table->string('card_number')->nullable(); // Últimos 4 dígitos
            $table->string('card_type')->nullable(); // Tipo de tarjeta
            $table->integer('installments_number')->nullable(); // Número de cuotas
            
            // Estado del pago
            $table->enum('status', [
                'pending',      // Pendiente
                'authorized',   // Autorizado
                'completed',    // Completado
                'failed',       // Fallido
                'reversed',     // Reversado
                'nullified'     // Anulado
            ])->default('pending');
            
            // Respuesta completa de la pasarela (JSON)
            $table->json('gateway_response')->nullable();
            
            // Campos adicionales
            $table->string('commerce_code')->nullable(); // Código de comercio
            $table->decimal('amount', 10, 2); // Monto del pago
            $table->decimal('balance', 10, 2)->nullable(); // Saldo restante (para cuotas)
            $table->text('error_message')->nullable(); // Mensaje de error si falla
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['buy_order', 'status']);
            $table->index(['token', 'status']);
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