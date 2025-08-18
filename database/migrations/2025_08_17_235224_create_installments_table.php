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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            
            // Relación con el plan de cuotas
            $table->foreignId('installment_plan_id')->constrained('installment_plans')->onDelete('cascade');
            
            // Información de la cuota
            $table->integer('installment_number'); // Número de cuota (1, 2, 3...)
            $table->decimal('amount', 10, 2); // Monto de esta cuota específica
            $table->date('due_date'); // Fecha de vencimiento
            $table->enum('status', ['pending', 'overdue', 'paid', 'cancelled'])->default('pending');
            
            // Información de pago (cuando se pague)
            $table->datetime('paid_at')->nullable(); // Fecha cuando se pagó
            $table->foreignId('payment_order_id')->nullable()->constrained('orders')->onDelete('set null'); // Orden del pago
            $table->foreignId('payment_order_detail_id')->nullable()->constrained('orders_detail')->onDelete('set null'); // Detalle del pago
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null'); // Registro del pago
            
            // Campos adicionales
            $table->text('notes')->nullable(); // Notas específicas de la cuota
            $table->datetime('adjusted_at')->nullable(); // Fecha de ajuste si se modificó
            $table->text('adjustment_reason')->nullable(); // Razón del ajuste
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['installment_plan_id', 'installment_number']);
            $table->index(['status', 'due_date']);
            $table->index(['due_date']);
            $table->index(['payment_order_id']);
            $table->index(['payment_id']);
            
            // Índice único para evitar duplicados
            $table->unique(['installment_plan_id', 'installment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
