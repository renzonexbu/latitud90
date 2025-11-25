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
            $table->json('buyer_data')->nullable()->after('api_response')
                ->comment('Datos del comprador para crear OrderDetails posteriores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_subscriptions', function (Blueprint $table) {
            $table->dropColumn('buyer_data');
        });
    }
};
