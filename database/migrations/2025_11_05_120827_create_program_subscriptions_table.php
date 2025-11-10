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
        Schema::create('program_subscriptions', function (Blueprint $table) {
            $table->id();

            // Relación con participante y programa
            $table->unsignedBigInteger('participant_id');
            $table->unsignedBigInteger('program_id');

            // Datos de VirtualPos
            $table->string('virtualpos_subscription_id')->unique()->nullable();
            $table->string('virtualpos_plan_id')->nullable();
            $table->string('plan_name')->nullable();

            // Estado de la suscripción
            // SUSCRIBIENDO, ACTIVA, SUSCRIPCION_FALLIDA, CANCELADA, FINALIZADA
            $table->enum('status', ['SUSCRIBIENDO', 'ACTIVA', 'SUSCRIPCION_FALLIDA', 'CANCELADA', 'FINALIZADA'])
                ->default('SUSCRIBIENDO');

            // Información de pago
            $table->decimal('amount', 10, 2);
            $table->enum('currency', ['CLP', 'UF'])->default('CLP');
            $table->enum('automatic_renewal', ['T', 'F'])->default('T');

            // Método de pago (guardado como JSON)
            $table->json('payment_method')->nullable();

            // Fechas
            $table->timestamp('subscription_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // Canal y service_id
            $table->string('channel')->default('WEB');
            $table->string('service_id')->nullable();

            // Programa de cobros (guardado como JSON)
            $table->json('charge_program')->nullable();

            // Información del cliente (guardado como JSON)
            $table->json('client_data')->nullable();

            // Link de cambio de tarjeta
            $table->text('card_change_link')->nullable();
            $table->timestamp('card_change_link_generated_at')->nullable();

            // Respuesta completa de la API (para debugging)
            $table->json('api_response')->nullable();

            // Foreign keys
            $table->foreign('participant_id')->references('id')->on('participants')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('virtualpos_subscription_id');
            $table->index('status');
            $table->index(['participant_id', 'program_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_subscriptions');
    }
};
