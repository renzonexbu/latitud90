<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Modifica la columna grade para:
     * - Agregar opciones F y G
     * - Permitir valores NULL
     */
    public function up(): void
    {
        // MySQL requires raw SQL to modify ENUM columns
        DB::statement("ALTER TABLE courses MODIFY COLUMN grade ENUM('A', 'B', 'C', 'D', 'E', 'F', 'G') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values (non-nullable)
        // Note: This will fail if there are records with F, G, or NULL values
        DB::statement("ALTER TABLE courses MODIFY COLUMN grade ENUM('A', 'B', 'C', 'D', 'E') NOT NULL DEFAULT 'A'");
    }
};
