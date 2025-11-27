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
            $table->string('itinerary_file')->nullable()->after('discount_value');
            $table->string('travel_assistance_coverage')->nullable()->after('itinerary_file');
            $table->string('equipment_list')->nullable()->after('travel_assistance_coverage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_courses', function (Blueprint $table) {
            $table->dropColumn(['itinerary_file', 'travel_assistance_coverage', 'equipment_list']);
        });
    }
};
