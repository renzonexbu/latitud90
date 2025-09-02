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
        Schema::create('emergency_contact', function (Blueprint $table) {
            $table->id();
            // Información del apoderado
            $table->string('name'); // Nombre completo del apoderado
            $table->string('email'); // Correo electrónico del apoderado
            $table->foreignId('document_type')->default(1)->constrained('document'); // Tipo de documento del apoderado
            $table->string('document_number'); // Número de documento del apoderado
            
            // Información adicional del contacto
            $table->string('code_phone')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('relationship')->default('Familiar');
            
            // Relación con el participante
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->timestamps();
            
            // Índices
            $table->index(['document_number']); // Para búsquedas por documento
            $table->index(['email']); // Para búsquedas por email
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_contact');
    }
};
