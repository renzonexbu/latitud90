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
        Schema::table('program_courses', function (Blueprint $table) {
            // Días mínimos entre el último cobro y la fecha de salida (default 30 días)
            $table->integer('min_days_before_departure')->default(30)->after('subscription_max_months');

            // Si el primer cobro de la suscripción es inmediato (true) o diferido al siguiente ciclo (false)
            $table->boolean('immediate_first_charge')->default(true)->after('min_days_before_departure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_courses', function (Blueprint $table) {
            $table->dropColumn(['min_days_before_departure', 'immediate_first_charge']);
        });
    }
};
