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
        Schema::create('frequent_client', function (Blueprint $table) {
            $table->id();
            
            // Datos personales
            $table->string('full_name');
            $table->unsignedBigInteger('document_id');
            $table->string('document');
            $table->string('email');
            $table->string('phone_code', 10);
            $table->string('phone');
            
            // Datos de ubicación
            $table->unsignedBigInteger('country_id');
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('comune_id');
            
            // Acuerdos
            $table->boolean('terms_accepted')->default(false);
            $table->boolean('marketing_accepted')->default(false);
            
            // Metadatos
            $table->timestamp('last_used_at')->nullable();
            $table->integer('usage_count')->default(1);
            
            $table->timestamps();
            
            // Índices para búsquedas eficientes
            $table->index('document');
            $table->index('email');
            $table->index('last_used_at');
            
            // Clave única para evitar duplicados por documento
            $table->unique(['document_id', 'document']);
            
            // Relaciones con otras tablas
            $table->foreign('document_id')->references('id')->on('document')->onDelete('cascade');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->foreign('comune_id')->references('id')->on('comunes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frequent_client');
    }
};
