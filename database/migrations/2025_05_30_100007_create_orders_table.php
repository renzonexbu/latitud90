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
			$table->foreignId('program_id')->constrained('programs'); // FK a programs
			$table->foreignId('participant_program_id')->nullable()->constrained('participant_program');

            // Información de la orden
            $table->decimal('total_amount', 10, 2); // Monto total de la orden
			$table->decimal('discount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2); // Monto final después de descuentos
            $table->integer('total_installments'); // Número total de cuotas
            $table->enum('payment_type', ['total', 'monthly'])->default('total');
            $table->enum('status', [
                'pending',      // Pendiente de pago
                'processing',   // Procesando pago
                'paid',         // Pagado (todas las cuotas pagadas)
                'cancelled',    // Cancelado
                'refunded'      // Reembolsado
            ])->default('pending');

            // Campos adicionales útiles
            $table->text('notes')->nullable(); // Notas adicionales
            $table->string('order_number')->unique(); // Número de orden único

            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['order_number']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('orders_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained('payment_methods'); // FK a payment_methods
            $table->foreignId('payment_mode_id')->constrained('payment_modes'); // FK a payment_modes
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways'); // FK a payment_gateways

            // Datos del comprador (se mueven aquí desde orders)
            $table->string('name');
            $table->string('email');
            $table->foreignId('country')->nullable()->constrained('countries');
            $table->foreignId('region')->nullable()->constrained('regions');
            $table->foreignId('city')->nullable()->constrained('comunes');
            $table->string('code_phone')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('document_type')->nullable(); // FK a document
            $table->string('document_number')->nullable();

            // Dirección de facturación
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_postal_code')->nullable();

            // Acuerdos
            $table->boolean('terms_accepted')->default(false);
            $table->boolean('marketing_accepted')->default(false);
            $table->boolean('terms_accepted_confirmation')->default(false); // Términos aceptados en confirmación

            // Información de la cuota
            $table->integer('installment_number'); // Número de cuota (1, 2, 3, etc.)
			$table->decimal('base_amount', 10, 2); // Monto full original de esta cuota
			$table->decimal('discount_amount', 10, 2)->default(0); // Monto de descuento aplicado a la cuota
			$table->decimal('amount', 10, 2); // Monto vigente (ajustado) de esta cuota
            $table->date('due_date'); // Fecha de vencimiento
            $table->boolean('is_paid')->default(false); // Si fue pagada o no
            $table->datetime('paid_at')->nullable(); // Fecha cuando se pagó
            $table->enum('status', [
                'pending',      // Pendiente de pago
                'processing',   // Procesando pago
                'paid',         // Pagado
                'overdue',      // Vencida
                'cancelled'     // Cancelada
            ])->default('pending');
			$table->datetime('adjusted_at')->nullable();
			$table->text('adjustment_reason')->nullable();

            // Información de transacción
            $table->string('transaction_id')->nullable(); // ID de transacción del gateway
            $table->text('gateway_response')->nullable(); // Respuesta completa del gateway

            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['order_id', 'installment_number']);
            $table->index(['email', 'status']);
            $table->index(['due_date', 'status']);
            $table->index(['is_paid', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_detail');
        Schema::dropIfExists('orders');
    }
};
