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
        Schema::create('participant_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->boolean('previous_status'); // Estado anterior (true = activo, false = inactivo)
            $table->boolean('new_status'); // Nuevo estado (true = activo, false = inactivo)
            $table->text('comment')->nullable(); // Comentario del administrador
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null'); // Usuario que hizo el cambio
            $table->timestamps();

            // Índices para mejorar consultas
            $table->index('participant_id');
            $table->index('changed_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_status_history');
    }
};
