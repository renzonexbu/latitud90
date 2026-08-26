<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * decimal(10,2) topa en 99.999.999,99. El programa C0064 vale 168.000.000
 * (programas pagados por una empresa/institución para el grupo completo),
 * lo que rompía el guardado con SQLSTATE[22003] Out of range.
 *
 * Se ensancha a decimal(15,2) toda la cadena del monto: precio del programa →
 * precio por participante → orden → detalle → pago → cuotas. Si solo se
 * ensancha trip_price, el error reaparece al inscribir o al registrar el pago.
 *
 * SQL crudo porque el proyecto no tiene doctrine/dbal (requisito de ->change()).
 */
return new class extends Migration
{
    /**
     * [tabla, columna, sufijo con nullability/default original]
     */
    private const COLUMNS = [
        ['program_courses', 'trip_price', 'NOT NULL'],
        ['program_courses', 'discount_value', 'NULL'],

        ['participant_course', 'individual_price', 'NULL'],
        ['participant_course', 'price_adjustments', "NOT NULL DEFAULT '0.00'"],

        ['participant_program', 'individual_price', 'NULL'],
        ['participant_program_discounts', 'amount', 'NULL'],

        ['orders', 'total_amount', 'NOT NULL'],
        ['orders', 'discount', "NOT NULL DEFAULT '0.00'"],
        ['orders', 'final_amount', 'NOT NULL'],
        ['orders', 'total_amount_fiscal', 'NULL'],

        ['order_details', 'base_amount', 'NULL'],
        ['order_details', 'discount_amount', "NOT NULL DEFAULT '0.00'"],
        ['order_details', 'amount', 'NULL'],

        ['payments', 'amount', 'NOT NULL'],
        ['payments', 'installment_amount', 'NULL'],
        ['payments', 'balance', 'NULL'],

        ['installment_plans', 'total_amount', 'NOT NULL'],
        ['installments', 'amount', 'NOT NULL'],
        ['program_subscriptions', 'amount', 'NOT NULL'],

        ['ecommerce_analytics', 'payment_amount', 'NULL'],

        ['installment_restructure', 'old_total_amount', 'NOT NULL'],
        ['installment_restructure', 'old_paid_amount', 'NOT NULL'],
        ['installment_restructure', 'old_remaining_balance', 'NOT NULL'],
        ['installment_restructure', 'new_total_amount', 'NOT NULL'],
        ['installment_restructure', 'new_paid_amount', 'NOT NULL'],
        ['installment_restructure', 'new_remaining_balance', 'NOT NULL'],
    ];

    public function up(): void
    {
        $this->applyPrecision('decimal(15,2)');
    }

    /**
     * El rollback solo es seguro si ningún monto supera 99.999.999,99;
     * las columnas con valores mayores se dejan intactas.
     */
    public function down(): void
    {
        $this->applyPrecision('decimal(10,2)', true);
    }

    private function applyPrecision(string $type, bool $checkOverflow = false): void
    {
        foreach (self::COLUMNS as [$table, $column, $modifiers]) {
            if (!$this->columnExists($table, $column)) {
                continue;
            }

            if ($checkOverflow) {
                $max = DB::table($table)->max($column);
                if ($max !== null && (float) $max > 99999999.99) {
                    continue;
                }
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$type} {$modifiers}");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->exists();
    }
};
