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
        Schema::create('guardian_users', function (Blueprint $table) {
            $table->id();

            // Autenticación
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();

            // Datos de identificación (del formulario de registro)
            $table->foreignId('document_id')->constrained('document')->comment('Tipo de documento (RUT, DNI, Pasaporte)');
            $table->string('document', 50)->comment('Número de documento');
            $table->string('name')->comment('Nombre completo');

            // Datos de contacto
            $table->string('phone_code', 10)->default('+56')->comment('Código de país del teléfono');
            $table->string('phone', 20)->comment('Número de teléfono');

            // Datos de ubicación
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('region_id')->constrained('regions');
            $table->foreignId('comune_id')->constrained('comunes');

            // Estado de la cuenta
            $table->enum('status', ['pending_payment', 'active', 'suspended'])->default('active');
            $table->timestamp('email_verified_at')->nullable();

            // Preferencias
            $table->string('language', 2)->default('es');
            $table->string('timezone')->default('America/Santiago');

            // Auditoría
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('email');
            $table->index('document');
            $table->index('status');
            $table->index(['document_id', 'document']); // Para búsquedas por tipo y número de documento
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardian_users');
    }
};
