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
        Schema::table('program_subscriptions', function (Blueprint $table) {
            $table->json('processed_failed_charge_ids')->nullable()->after('charge_program');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_subscriptions', function (Blueprint $table) {
            $table->dropColumn('processed_failed_charge_ids');
        });
    }
};
