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
        Schema::create('virtualpos_plans', function (Blueprint $table) {
            $table->id();

            // ID del plan en VirtualPos (el hash que devuelve la API)
            $table->string('virtualpos_plan_id')->unique();

            // Relación con program_course
            $table->foreignId('program_course_id')->nullable()->constrained('program_courses')->nullOnDelete();

            // Datos del plan enviados a VirtualPos
            $table->string('code')->index(); // Código interno del plan
            $table->string('name'); // Nombre del plan
            $table->text('description')->nullable(); // Descripción del viaje
            $table->decimal('trip_price', 12, 2); // Precio total del viaje
            $table->decimal('monthly_amount', 12, 2); // Monto mensual calculado
            $table->unsignedSmallInteger('max_installments'); // Número máximo de cuotas
            $table->boolean('immediate_first_charge')->default(true); // Si cobra inmediatamente
            $table->string('currency', 3)->default('CLP');
            $table->string('frequency_type')->default('Mensual');
            $table->string('plan_type')->default('PROGRAMA_DE_PAGOS');

            // Estado del plan
            $table->boolean('is_active')->default(true);

            // Respuesta completa de VirtualPos (para debugging)
            $table->json('api_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtualpos_plans');
    }
};
