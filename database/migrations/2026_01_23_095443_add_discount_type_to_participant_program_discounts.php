<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega el tipo 'discount' al enum discount_type para diferenciar
     * descuentos simples (que reducen el precio) de becas/aportes (que aparecen en reportes)
     */
    public function up(): void
    {
        // Modificar el enum para incluir 'discount' además de 'scholarship' y 'released'
        DB::statement("ALTER TABLE participant_program_discounts MODIFY COLUMN discount_type ENUM('scholarship', 'released', 'discount') DEFAULT 'discount'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Antes de revertir, cambiar los valores 'discount' a 'scholarship' para no perder datos
        DB::statement("UPDATE participant_program_discounts SET discount_type = 'scholarship' WHERE discount_type = 'discount'");

        // Volver al enum original
        DB::statement("ALTER TABLE participant_program_discounts MODIFY COLUMN discount_type ENUM('scholarship', 'released') DEFAULT 'scholarship'");
    }
};
