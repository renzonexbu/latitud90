<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar columna destination a program_courses
        Schema::table('program_courses', function (Blueprint $table) {
            $table->string('destination')->nullable()->after('name');
        });

        // Migrar datos existentes: copiar destination de programs a program_courses
        DB::statement('
            UPDATE program_courses pc
            INNER JOIN programs p ON pc.program_id = p.id
            SET pc.destination = p.destination
            WHERE pc.destination IS NULL
        ');

        // Agregar índice para búsquedas por destination
        Schema::table('program_courses', function (Blueprint $table) {
            $table->index('destination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_courses', function (Blueprint $table) {
            $table->dropIndex(['destination']);
            $table->dropColumn('destination');
        });
    }
};
