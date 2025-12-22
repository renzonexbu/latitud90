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
            $table->timestamp('last_synced_at')->nullable()->after('api_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_subscriptions', function (Blueprint $table) {
            $table->dropColumn('last_synced_at');
        });
    }
};
