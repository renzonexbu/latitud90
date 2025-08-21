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
        Schema::create('ecommerce_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable()->index(); // ID de sesión del usuario
            $table->text('visitor_id')->nullable(); // ID único del visitante (nullable para no causar problemas)
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable(); // De dónde viene el usuario
            
            // Datos del participante
            $table->string('participant_rut')->nullable()->index();
            $table->unsignedBigInteger('participant_id')->nullable()->index();
            
            // Datos del programa
            $table->unsignedBigInteger('program_id')->nullable()->index();
            $table->string('program_name')->nullable();
            
            // Puntos de conversión del flujo
            $table->timestamp('hero_search_at')->nullable(); // Buscador en hero
            $table->timestamp('program_list_view_at')->nullable(); // Vista de lista de programas
            $table->timestamp('program_detail_view_at')->nullable(); // Vista de detalle de programa
            $table->timestamp('payment_details_view_at')->nullable(); // Vista de detalles de pago
            $table->timestamp('confirmation_view_at')->nullable(); // Vista de confirmación
            $table->timestamp('payment_initiated_at')->nullable(); // Inicio de pago
            $table->timestamp('payment_completed_at')->nullable(); // Pago completado
            $table->timestamp('payment_failed_at')->nullable(); // Pago fallido
            
            // Datos del pago
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->string('payment_method')->nullable(); // transbank, khipu, etc.
            $table->string('payment_status')->nullable(); // pending, completed, failed
            $table->string('order_number')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('order_detail_id')->nullable()->index();
            
            // Métricas de tiempo
            $table->integer('time_to_program_detail')->nullable(); // Segundos desde hero hasta detalle
            $table->integer('time_to_payment')->nullable(); // Segundos desde detalle hasta pago
            $table->integer('time_to_completion')->nullable(); // Segundos desde pago hasta completado
            
            // Datos adicionales
            $table->json('funnel_data')->nullable(); // Datos adicionales del funnel
            $table->json('user_behavior')->nullable(); // Comportamiento del usuario
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Índices adicionales
            $table->index(['created_at', 'program_id']);
            $table->index(['session_id', 'created_at']);
            $table->index(['participant_rut', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_analytics');
    }
};
