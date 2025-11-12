<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar 'subscription' al ENUM de la columna mode
        DB::statement("ALTER TABLE payment_options MODIFY COLUMN mode ENUM('full', 'lat90', 'subscription', 'presential', 'refund') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover 'subscription' del ENUM de la columna mode
        DB::statement("ALTER TABLE payment_options MODIFY COLUMN mode ENUM('full', 'lat90', 'presential', 'refund') NOT NULL");
    }
};
