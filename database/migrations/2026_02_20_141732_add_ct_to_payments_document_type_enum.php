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
        // Agregar CT al enum de document_type
        DB::statement("ALTER TABLE payments MODIFY COLUMN document_type ENUM('B2','VC','FF','AC','RA','CT') NULL");

        // Actualizar pagos existentes con payment_option CT que tenían document_type B2
        DB::table('payments')
            ->where('payment_option_id', function ($query) {
                $query->select('id')
                    ->from('payment_options')
                    ->where('code', 'presential_credit_temp')
                    ->limit(1);
            })
            ->where('document_type', 'B2')
            ->update(['document_type' => 'CT']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir pagos CT a B2
        DB::table('payments')
            ->where('document_type', 'CT')
            ->update(['document_type' => 'B2']);

        // Quitar CT del enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN document_type ENUM('B2','VC','FF','AC','RA') NULL");
    }
};
