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
        Schema::table('site_contents', function (Blueprint $table) {
            // Punto focal para mobile (valores de 0 a 100, representan porcentaje)
            $table->decimal('focal_point_mobile_x', 5, 2)->nullable()->default(50.00)->after('value');
            $table->decimal('focal_point_mobile_y', 5, 2)->nullable()->default(50.00)->after('focal_point_mobile_x');

            // Punto focal para desktop (valores de 0 a 100, representan porcentaje)
            $table->decimal('focal_point_desktop_x', 5, 2)->nullable()->default(50.00)->after('focal_point_mobile_y');
            $table->decimal('focal_point_desktop_y', 5, 2)->nullable()->default(50.00)->after('focal_point_desktop_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropColumn([
                'focal_point_mobile_x',
                'focal_point_mobile_y',
                'focal_point_desktop_x',
                'focal_point_desktop_y',
            ]);
        });
    }
};
