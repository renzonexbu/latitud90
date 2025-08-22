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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            // Nombres y apellidos
            $table->string('first_last_name'); // Primer apellido
            $table->string('second_last_name')->nullable(); // Segundo apellido
            $table->string('first_name'); // Primer nombre
            $table->string('second_name')->nullable(); // Segundo nombre
            
            // Documento
            $table->foreignId('document_type')->default(1)->constrained('document'); // Tipo de documento (RUT, Pasaporte, etc.)
            $table->string('document_number'); // Número de documento
            
            // Información personal
            $table->date('birth_date'); // Fecha de nacimiento
            $table->string('nationality'); // Nacionalidad
            $table->enum('gender', ['Masculino', 'Femenino']); // Sexo
            
            // Información de contacto
            $table->string('email');
            $table->string('code_phone');
            $table->string('phone');
            $table->string('country');
            $table->text('address')->nullable();
            
            // Información médica y alimentaria
            $table->text('dietary_restrictions')->nullable(); // Restricción alimenticia
            $table->text('intolerances')->nullable(); // Intolerancia
            $table->text('allergies')->nullable(); // Alergias
            
            // Campos de sistema
            $table->enum('status', ['pending_payment', 'confirmed', 'cancelled']);
            $table->datetime('registration_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices
            $table->index(['document_number']); // Para búsquedas por documento
            $table->index(['email']); // Para búsquedas por email
            $table->index(['created_at']); // Para filtros por fecha
            $table->index(['status', 'created_at']); // Para filtros combinados
        });

        // Tabla pivote para la relación muchos a muchos entre participants y courses
        Schema::create('participant_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('education_level')->nullable();
            $table->string('year')->nullable();
            $table->string('grade')->nullable();
            $table->string('shift')->nullable();
            $table->enum('status', ['pending_payment', 'confirmed', 'cancelled'])->default('pending_payment');
            $table->decimal('individual_price', 10, 2)->nullable();
            $table->decimal('price_adjustments', 10, 2)->default(0);
            $table->text('adjustment_reason')->nullable();
            $table->timestamps();
            
            // Índice único para evitar duplicados
            $table->unique(['participant_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_course');
        Schema::dropIfExists('participants');
    }
};
