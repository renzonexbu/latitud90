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
        Schema::create('installment_restructure', function (Blueprint $table) {
            $table->id();
            
            // Relación con el plan de cuotas
            $table->foreignId('installment_plan_id')->constrained('installment_plans')->onDelete('cascade');
            
            // Usuario que realizó la restructuración
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            
            // Información del participante y programa
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
            
            // Estado anterior
            $table->integer('old_total_installments');
            $table->decimal('old_total_amount', 10, 2);
            $table->integer('old_paid_installments');
            $table->integer('old_pending_installments');
            $table->decimal('old_paid_amount', 10, 2);
            $table->decimal('old_remaining_balance', 10, 2);
            
            // Estado nuevo
            $table->integer('new_total_installments');
            $table->decimal('new_total_amount', 10, 2);
            $table->integer('new_paid_installments');
            $table->integer('new_pending_installments');
            $table->decimal('new_paid_amount', 10, 2);
            $table->decimal('new_remaining_balance', 10, 2);
            
            // Detalles de la restructuración
            $table->text('reason');
            $table->integer('installments_deleted');
            $table->integer('installments_created');
            $table->enum('restructure_type', ['manual', 'payment_presencial', 'refund', 'discount'])->default('manual');
            
            // Información adicional
            $table->json('old_installments_data')->nullable(); // Datos de las cuotas anteriores
            $table->json('new_installments_data')->nullable(); // Datos de las nuevas cuotas
            $table->text('notes')->nullable();
            
            // Información de la sesión
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['installment_plan_id']);
            $table->index(['participant_id']);
            $table->index(['program_id']);
            $table->index(['user_id']);
            $table->index(['created_at']);
            $table->index(['restructure_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_restructure');
    }
};
