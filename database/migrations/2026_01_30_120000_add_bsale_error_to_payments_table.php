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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('bsale_error', 255)->nullable()->after('bsale_token')
                ->comment('Error de BSale si falló la generación (ej: client_blocked)');
            $table->string('bsale_error_code', 50)->nullable()->after('bsale_error')
                ->comment('Código de error de BSale (ej: cli_005)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['bsale_error', 'bsale_error_code']);
        });
    }
};
