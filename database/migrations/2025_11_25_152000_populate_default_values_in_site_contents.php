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
        // Actualizar registros existentes: copiar value a default_value donde sea null
        DB::table('site_contents')
            ->whereNull('default_value')
            ->update([
                'default_value' => DB::raw('value'),
                'use_default' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es necesario revertir esto
    }
};
