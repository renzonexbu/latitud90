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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants');
            $table->foreignId('payment_method_id')->constrained('payment_methods'); // FK a payment_methods
            $table->foreignId('payment_mode_id')->constrained('payment_modes'); // FK a payment_modes

            // Datos del comprador
            $table->string('buyer_first_name');
            $table->string('buyer_last_name');
            $table->string('buyer_email');
            $table->string('buyer_phone')->nullable();
            $table->string('buyer_document_type')->nullable(); // 'rut', 'cedula', 'pasaporte'
            $table->string('buyer_document_number')->nullable();

            // Dirección de facturación
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_postal_code')->nullable();

            // Información de la orden
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('status', [
                'pending',      // Pendiente de pago
                'processing',   // Procesando pago
                'paid',         // Pagado
                'cancelled',    // Cancelado
                'refunded'      // Reembolsado
            ])->default('pending');

            // Campos adicionales útiles
            $table->text('notes')->nullable(); // Notas adicionales
            $table->datetime('paid_at')->nullable(); // Fecha de pago
            $table->string('order_number')->unique(); // Número de orden único

            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['buyer_email', 'status']);
            $table->index(['order_number']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
