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
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('program_subscription_id')->nullable()->after('order_id');

            $table->foreign('program_subscription_id')
                ->references('id')
                ->on('program_subscriptions')
                ->onDelete('set null');

            $table->index('program_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->dropForeign(['program_subscription_id']);
            $table->dropIndex(['program_subscription_id']);
            $table->dropColumn('program_subscription_id');
        });
    }
};
