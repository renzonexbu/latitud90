<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos para almacenar rutas de documentos generados (Contrato, Anticipo)
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Ruta del archivo PDF del contrato de reserva
            $table->string('contract_path')->nullable()->after('bsale_token');
            // Ruta del archivo PDF del comprobante de anticipo
            $table->string('receipt_path')->nullable()->after('contract_path');
            // Tipos de documento generados (JSON array: ['B2'], ['CR', 'AC'], ['AC'])
            $table->json('generated_document_types')->nullable()->after('receipt_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['contract_path', 'receipt_path', 'generated_document_types']);
        });
    }
};
