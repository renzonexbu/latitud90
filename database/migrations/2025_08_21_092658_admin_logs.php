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
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // Usuario que realizó la acción
            $table->string('user_name')->nullable(); // Nombre del usuario
            $table->string('user_email')->nullable(); // Email del usuario
            
            // Detalles de la acción
            $table->string('action')->index(); // create, update, delete, view, export, etc.
            $table->string('module')->index(); // participants, programs, payments, reports, etc.
            $table->string('resource_type')->nullable(); // Modelo afectado
            $table->unsignedBigInteger('resource_id')->nullable(); // ID del recurso afectado
            
            // Datos de la acción
            $table->text('description')->nullable(); // Descripción de la acción
            $table->json('old_values')->nullable(); // Valores anteriores (para updates)
            $table->json('new_values')->nullable(); // Valores nuevos
            $table->json('additional_data')->nullable(); // Datos adicionales
            
            // Información de la sesión
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            
            // Información de la petición
            $table->string('request_method')->nullable(); // GET, POST, PUT, DELETE
            $table->string('request_url')->nullable();
            $table->json('request_data')->nullable(); // Datos de la petición
            
            // Timestamps
            $table->timestamps();
            
            // Índices adicionales
            $table->index(['created_at', 'module']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
    }
};
