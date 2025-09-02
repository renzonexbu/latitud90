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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('level'); // error, info, warning, debug, critical
            $table->string('message');
            $table->text('context')->nullable(); // JSON con información adicional
            $table->string('file')->nullable(); // Archivo donde ocurrió el error
            $table->integer('line')->nullable(); // Línea donde ocurrió el error
            $table->text('trace')->nullable(); // Stack trace
            $table->string('user_id')->nullable(); // ID del usuario si está autenticado
            $table->string('user_email')->nullable(); // Email del usuario
            $table->string('ip_address')->nullable(); // IP del usuario
            $table->string('user_agent')->nullable(); // User agent
            $table->string('request_method')->nullable(); // GET, POST, etc.
            $table->string('request_url')->nullable(); // URL de la petición
            $table->json('request_data')->nullable(); // Datos de la petición
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index('level');
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
