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
            // Contador de intentos de envío de email
            $table->unsignedTinyInteger('email_attempts')->default(0)->after('email_sent_at');
            // Último mensaje de error al intentar enviar email
            $table->text('email_last_error')->nullable()->after('email_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['email_attempts', 'email_last_error']);
        });
    }
};
