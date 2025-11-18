<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cambia las foreign keys de program_id en varias tablas para que apunten
     * a program_courses en lugar de programs, reflejando la nueva arquitectura
     * donde ProgramCourse es el programa real y Program es solo una plantilla.
     */
    public function up(): void
    {
        // 1. Tabla participant_program
        Schema::table('participant_program', function (Blueprint $table) {
            // Eliminar la foreign key existente
            $table->dropForeign(['program_id']);

            // Crear nueva foreign key apuntando a program_courses
            $table->foreign('program_id')
                ->references('id')
                ->on('program_courses')
                ->onDelete('cascade');
        });

        // 2. Tabla orders
        Schema::table('orders', function (Blueprint $table) {
            // Eliminar la foreign key existente
            $table->dropForeign(['program_id']);

            // Crear nueva foreign key apuntando a program_courses
            $table->foreign('program_id')
                ->references('id')
                ->on('program_courses')
                ->onDelete('cascade');
        });

        // 3. Tabla installment_plans
        Schema::table('installment_plans', function (Blueprint $table) {
            // Eliminar la foreign key existente
            $table->dropForeign(['program_id']);

            // Crear nueva foreign key apuntando a program_courses
            $table->foreign('program_id')
                ->references('id')
                ->on('program_courses')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revertir participant_program
        Schema::table('participant_program', function (Blueprint $table) {
            $table->dropForeign(['program_id']);

            $table->foreign('program_id')
                ->references('id')
                ->on('programs')
                ->onDelete('cascade');
        });

        // 2. Revertir orders
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['program_id']);

            $table->foreign('program_id')
                ->references('id')
                ->on('programs')
                ->onDelete('cascade');
        });

        // 3. Revertir installment_plans
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->dropForeign(['program_id']);

            $table->foreign('program_id')
                ->references('id')
                ->on('programs')
                ->onDelete('cascade');
        });
    }
};
