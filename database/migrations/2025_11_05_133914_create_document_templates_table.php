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
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['contract', 'payment_receipt'])->comment('Tipo de documento');
            $table->string('name')->comment('Nombre descriptivo de la plantilla');
            $table->longText('content')->comment('Contenido HTML de la plantilla');
            $table->json('variables')->nullable()->comment('Variables disponibles para usar en la plantilla');
            $table->boolean('is_active')->default(false)->comment('Indica si esta plantilla está activa');
            $table->integer('version')->default(1)->comment('Versión de la plantilla');
            $table->foreignId('parent_template_id')->nullable()->constrained('document_templates')->onDelete('set null')->comment('ID de la plantilla padre (para versiones)');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('Usuario que creó la plantilla');
            $table->text('notes')->nullable()->comment('Notas sobre los cambios realizados');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['type', 'is_active']);
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
