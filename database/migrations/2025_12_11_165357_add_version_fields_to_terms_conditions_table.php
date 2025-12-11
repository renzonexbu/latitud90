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
        Schema::table('terms_conditions', function (Blueprint $table) {
            $table->string('version', 20)->nullable()->after('title')->comment('Versión de los T&C (ej: 1.0, 2.0)');
            $table->date('effective_date')->nullable()->after('version')->comment('Fecha de entrada en vigencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terms_conditions', function (Blueprint $table) {
            $table->dropColumn(['version', 'effective_date']);
        });
    }
};
