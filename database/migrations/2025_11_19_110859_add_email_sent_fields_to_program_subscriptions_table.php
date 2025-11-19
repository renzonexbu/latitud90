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
            $table->boolean('email_sent')->default(false)->after('status')->comment('Indica si se envió el email de confirmación de suscripción');
            $table->timestamp('email_sent_at')->nullable()->after('email_sent')->comment('Fecha y hora en que se envió el email de confirmación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['email_sent', 'email_sent_at']);
        });
    }
};
