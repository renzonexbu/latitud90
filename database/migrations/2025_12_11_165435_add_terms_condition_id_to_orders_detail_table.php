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
        Schema::table('orders_detail', function (Blueprint $table) {
            $table->foreignId('terms_condition_id')
                ->nullable()
                ->after('terms_accepted_at')
                ->constrained('terms_conditions')
                ->nullOnDelete()
                ->comment('ID de la versión de T&C aceptada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders_detail', function (Blueprint $table) {
            $table->dropForeign(['terms_condition_id']);
            $table->dropColumn('terms_condition_id');
        });
    }
};
