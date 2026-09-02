<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El formulario del link de pago internacional captura Región y Comuna como
 * texto libre: los pagadores extranjeros no pertenecen a ninguna región ni
 * comuna chilena, y region_id / comune_id son claves foráneas a las tablas
 * `regions` y `comunes` (solo Chile).
 *
 * La migración 2026_08_17_154800 ya dejó esas FK aceptando NULL; acá se agregan
 * las columnas de texto donde guardar lo que el pagador escriba.
 * Pedido de Carmen (2026-08-28).
 */
return new class extends Migration
{
    private const TABLES = ['frequent_client', 'guardian_users'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableSchema) use ($table) {
                if (!Schema::hasColumn($table, 'region_text')) {
                    $tableSchema->string('region_text', 150)->nullable()->after('region_id');
                }
                if (!Schema::hasColumn($table, 'comune_text')) {
                    $tableSchema->string('comune_text', 150)->nullable()->after('comune_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableSchema) use ($table) {
                foreach (['region_text', 'comune_text'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $tableSchema->dropColumn($column);
                    }
                }
            });
        }
    }
};
