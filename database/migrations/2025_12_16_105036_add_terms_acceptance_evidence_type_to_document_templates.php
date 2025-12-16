<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el ENUM para agregar el nuevo tipo
        DB::statement("ALTER TABLE document_templates MODIFY COLUMN type ENUM('contract', 'payment_receipt', 'terms_acceptance_evidence') NOT NULL COMMENT 'Tipo de documento'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a los valores originales (cuidado: esto fallará si hay registros con el nuevo tipo)
        DB::statement("ALTER TABLE document_templates MODIFY COLUMN type ENUM('contract', 'payment_receipt') NOT NULL COMMENT 'Tipo de documento'");
    }
};
