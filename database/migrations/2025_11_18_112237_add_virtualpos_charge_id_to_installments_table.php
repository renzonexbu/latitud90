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
        Schema::table('installments', function (Blueprint $table) {
            // Agregar campo para guardar el ID del charge de VirtualPos
            $table->string('virtualpos_charge_id')->nullable()->after('installment_number');

            // Agregar campo booleano para identificar si fue pagada
            $table->boolean('is_paid')->default(false)->after('status');

            // Índice para búsquedas por charge_id
            $table->index('virtualpos_charge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex(['virtualpos_charge_id']);
            $table->dropColumn(['virtualpos_charge_id', 'is_paid']);
        });
    }
};
