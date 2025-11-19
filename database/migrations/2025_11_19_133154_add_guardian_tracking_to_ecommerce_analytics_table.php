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
        Schema::table('ecommerce_analytics', function (Blueprint $table) {
            $table->timestamp('guardian_register_at')->nullable()->after('confirmation_view_at');
            $table->timestamp('guardian_login_at')->nullable()->after('guardian_register_at');
            $table->string('guardian_email')->nullable()->after('guardian_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_analytics', function (Blueprint $table) {
            $table->dropColumn(['guardian_register_at', 'guardian_login_at', 'guardian_email']);
        });
    }
};
