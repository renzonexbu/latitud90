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
            // Eliminar foreign keys
            $table->dropForeign(['participant_id']);
            $table->dropForeign(['program_id']);

            // Hacer columnas nullable
            $table->unsignedBigInteger('participant_id')->nullable()->change();
            $table->unsignedBigInteger('program_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_subscriptions', function (Blueprint $table) {
            // Revertir a NOT NULL
            $table->unsignedBigInteger('participant_id')->nullable(false)->change();
            $table->unsignedBigInteger('program_id')->nullable(false)->change();

            // Re-agregar foreign keys
            $table->foreign('participant_id')->references('id')->on('participants')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
        });
    }
};
