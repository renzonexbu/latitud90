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
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            
            // Relaciones principales
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            
            // Información del plan
            $table->decimal('total_amount', 10, 2); // Monto total del programa
            $table->integer('total_installments'); // Número total de cuotas
            $table->enum('payment_type', ['monthly'])->default('monthly');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            
            // Campos adicionales
            $table->text('notes')->nullable(); // Notas del plan
            $table->date('start_date'); // Fecha de inicio del plan
            $table->date('end_date')->nullable(); // Fecha de finalización (cuando se complete)
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['participant_id', 'program_id']);
            $table->index(['status', 'created_at']);
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
