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
        // Hacer campos opcionales en la tabla participants
        Schema::table('participants', function (Blueprint $table) {
            // Campos que ahora serán opcionales
            $table->date('birth_date')->nullable()->change();
            $table->string('gender')->nullable()->change();
        });

        // Hacer campos opcionales en la tabla emergency_contact
        Schema::table('emergency_contact', function (Blueprint $table) {
            // Campos que ahora serán opcionales
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('document_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios en participants
        Schema::table('participants', function (Blueprint $table) {
            $table->date('birth_date')->nullable(false)->change();
            $table->string('gender')->nullable(false)->change();
        });

        // Revertir cambios en emergency_contact
        Schema::table('emergency_contact', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('document_number')->nullable(false)->change();
        });
    }
};
