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
        // Modificar el ENUM de document_type para agregar 'RA' (Reverso Administrativo)
        DB::statement("ALTER TABLE payments MODIFY COLUMN document_type ENUM('B2', 'BC', 'FF', 'AC', 'RA') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el cambio eliminando 'RA' del ENUM
        DB::statement("ALTER TABLE payments MODIFY COLUMN document_type ENUM('B2', 'BC', 'FF', 'AC') NULL");
    }
};
