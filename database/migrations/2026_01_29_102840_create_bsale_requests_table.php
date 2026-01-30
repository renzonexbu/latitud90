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
        Schema::create('bsale_requests', function (Blueprint $table) {
            $table->id();

            // Relación con payment
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('order_detail_id')->nullable()->constrained('orders_detail')->onDelete('set null');

            // Estado de la solicitud
            $table->enum('status', [
                'pending',      // Pendiente de procesar
                'processing',   // En proceso
                'completed',    // Completado exitosamente
                'failed',       // Falló (puede reintentar)
                'cancelled',    // Cancelado manualmente
                'skipped'       // Omitido (ej: ya existe boleta)
            ])->default('pending')->index();

            // Tipo de documento a generar
            $table->enum('document_type', ['B2', 'NC'])->default('B2'); // B2=Boleta, NC=Nota Crédito

            // Datos de la solicitud (enviados a BSale)
            $table->json('request_data')->nullable();

            // Respuesta de BSale
            $table->json('response_data')->nullable();

            // Datos del documento generado
            $table->string('bsale_document_id')->nullable()->index();
            $table->string('bsale_number')->nullable()->index(); // Folio
            $table->string('bsale_token')->nullable(); // Token para descargar PDF
            $table->string('bsale_url')->nullable(); // URL del documento

            // Control de errores y reintentos
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);

            // Timestamps de procesamiento
            $table->timestamp('scheduled_at')->nullable(); // Cuándo se debe procesar
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();

            // Metadata adicional
            $table->string('source')->nullable(); // 'payment_confirmation', 'manual', 'sync', etc.
            $table->json('metadata')->nullable(); // Datos adicionales

            // Usuario que procesó (si fue manual)
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['status', 'scheduled_at']);
            $table->index(['payment_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bsale_requests');
    }
};
