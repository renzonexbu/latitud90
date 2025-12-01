<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Actualiza los registros en participant_program que tienen program_id
     * apuntando a programs.id para que apunten a program_courses.id
     */
    public function up(): void
    {
        // Obtener todos los registros de participant_program donde program_id
        // corresponde a un programs.id en lugar de program_courses.id
        $participantPrograms = DB::table('participant_program as pp')
            ->select('pp.id', 'pp.participant_id', 'pp.program_id')
            ->get();

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($participantPrograms as $pp) {
            // Verificar si el program_id ya apunta a program_courses
            $existsInProgramCourses = DB::table('program_courses')
                ->where('id', $pp->program_id)
                ->exists();

            if ($existsInProgramCourses) {
                // Ya está correcto, saltar
                $skippedCount++;
                continue;
            }

            // El program_id apunta a programs, necesitamos encontrar el program_course correcto
            // Buscar a través de course_participant -> courses -> program_courses
            $correctProgramCourse = DB::table('course_participant as cp')
                ->join('courses as c', 'c.id', '=', 'cp.course_id')
                ->join('program_courses as pgc', 'pgc.course_id', '=', 'c.id')
                ->where('cp.participant_id', $pp->participant_id)
                ->where('pgc.program_id', $pp->program_id) // Verificar que sea el mismo programa template
                ->select('pgc.id as program_course_id')
                ->first();

            if ($correctProgramCourse) {
                DB::table('participant_program')
                    ->where('id', $pp->id)
                    ->update(['program_id' => $correctProgramCourse->program_course_id]);

                $updatedCount++;
                Log::info('participant_program actualizado', [
                    'pp_id' => $pp->id,
                    'participant_id' => $pp->participant_id,
                    'old_program_id' => $pp->program_id,
                    'new_program_course_id' => $correctProgramCourse->program_course_id,
                ]);
            } else {
                // No se encontró un program_course correspondiente
                Log::warning('No se encontró program_course para participant_program', [
                    'pp_id' => $pp->id,
                    'participant_id' => $pp->participant_id,
                    'program_id' => $pp->program_id,
                ]);
            }
        }

        Log::info('Migración de datos participant_program completada', [
            'total' => $participantPrograms->count(),
            'updated' => $updatedCount,
            'skipped_already_correct' => $skippedCount,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se puede revertir automáticamente sin guardar los valores originales
        // Se requeriría una tabla de respaldo para esto
    }
};
