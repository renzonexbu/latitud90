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
        Schema::table('programs', function (Blueprint $table) {
            // Agregar nuevos campos para configuración de pagos
            $table->boolean('enable_total_payment')->default(false)->after('seller_name');
            $table->foreignId('total_payment_method_id')->nullable()->constrained('payment_methods')->after('enable_total_payment');
            $table->boolean('enable_lat90_payment')->default(false)->after('total_payment_method_id');
            $table->foreignId('lat90_payment_method_id')->nullable()->constrained('payment_methods')->after('enable_lat90_payment');
            $table->integer('lat90_max_installments')->nullable()->after('lat90_payment_method_id');
            
            // Agregar índices para optimizar consultas
            $table->index(['active', 'departure_date']);
            $table->index(['destination']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            // Remover índices
            $table->dropIndex(['active', 'departure_date']);
            $table->dropIndex(['destination']);
            
            // Remover campos de configuración de pagos
            $table->dropForeign(['total_payment_method_id']);
            $table->dropForeign(['lat90_payment_method_id']);
            $table->dropColumn([
                'enable_total_payment',
                'total_payment_method_id',
                'enable_lat90_payment',
                'lat90_payment_method_id',
                'lat90_max_installments'
            ]);
        });
    }
};
