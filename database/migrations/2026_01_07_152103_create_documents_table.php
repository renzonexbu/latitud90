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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();

            // Tipo de documento
            $table->enum('document_type', [
                'payment_receipt',      // Comprobante de pago
                'contract',             // Contrato de reserva
                'bsale_invoice'         // Boleta Bsale
            ]);

            // Información del archivo
            $table->string('file_path');        // Ruta relativa desde storage/app
            $table->string('file_name');        // Nombre del archivo
            $table->string('original_name')->nullable();  // Nombre original si es diferente
            $table->integer('file_size')->nullable();     // Tamaño en bytes

            // Relaciones con otras tablas
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->foreignId('order_detail_id')->nullable()->constrained('orders_detail')->onDelete('set null');
            $table->foreignId('participant_id')->nullable()->constrained('participants')->onDelete('set null');
            $table->foreignId('program_id')->nullable()->constrained('programs')->onDelete('set null');

            // Información de envío por email
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->string('email_sent_to')->nullable();
            $table->integer('email_send_count')->default(0);  // Contador de envíos

            // Información de generación
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('generated_from')->nullable();  // Origen: 'payment_confirmation', 'manual', etc.

            // Metadata adicional (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índices para mejorar búsquedas
            $table->index(['document_type', 'created_at']);
            $table->index(['payment_id', 'document_type']);
            $table->index(['order_detail_id', 'document_type']);
            $table->index(['participant_id', 'program_id']);
            $table->index('email_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
