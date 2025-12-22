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
        Schema::create('charge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('installment_id')->nullable()->constrained()->onDelete('set null');
            $table->string('virtualpos_charge_id')->nullable()->comment('ID del cargo en VirtualPos');
            $table->string('original_charge_id')->nullable()->comment('ID del cargo original que falló');
            $table->integer('attempt_number')->default(1)->comment('Número de intento');
            $table->integer('amount')->comment('Monto del intento');
            $table->string('description')->nullable();
            $table->enum('type', ['automatic', 'manual'])->default('automatic')->comment('Tipo de intento');
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->string('failure_reason')->nullable()->comment('Razón del fallo si aplica');
            $table->string('virtualpos_status')->nullable()->comment('Estado retornado por VirtualPos');
            $table->json('api_response')->nullable()->comment('Respuesta completa de la API');
            $table->timestamp('attempted_at')->nullable()->comment('Fecha del intento');
            $table->timestamp('resolved_at')->nullable()->comment('Fecha de resolución (éxito o fallo final)');
            $table->timestamps();

            $table->index(['program_subscription_id', 'status']);
            $table->index(['installment_id', 'attempt_number']);
            $table->index('virtualpos_charge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_attempts');
    }
};
