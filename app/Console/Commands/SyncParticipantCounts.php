<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\ProgramCourse;

class SyncParticipantCounts extends Command
{
    protected $signature = 'participants:sync {--dry-run : Solo mostrar lo que se haría sin ejecutar cambios}';
    protected $description = 'Sincroniza los conteos de participantes entre participant_course y participant_program';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Modo DRY-RUN: No se ejecutarán cambios reales.');
            $this->newLine();
        }

        $this->info('Analizando inconsistencias entre participant_course y participant_program...');
        $this->newLine();

        $courses = Course::with(['programCourses'])->get();

        $totalRemoved = 0;
        $totalCourses = 0;
        $inconsistentCourses = [];

        foreach ($courses as $course) {
            $programCourse = $course->programCourses->first();

            if (!$programCourse) {
                continue;
            }

            // Participantes en participant_course (tabla pivot del curso)
            $participantsInCourse = DB::table('participant_course')
                ->where('course_id', $course->id)
                ->pluck('participant_id')
                ->toArray();

            // Participantes en participant_program (tabla de inscripciones al programa)
            $participantsInProgram = DB::table('participant_program')
                ->where('program_id', $programCourse->id)
                ->pluck('participant_id')
                ->toArray();

            // Encontrar participantes que están en course pero NO en program
            $orphanedParticipants = array_diff($participantsInCourse, $participantsInProgram);

            if (count($orphanedParticipants) > 0) {
                $totalCourses++;
                $inconsistentCourses[] = [
                    'course_id' => $course->id,
                    'course_name' => $course->course_display,
                    'institution' => $course->institution->name ?? 'N/A',
                    'in_course' => count($participantsInCourse),
                    'in_program' => count($participantsInProgram),
                    'orphaned' => count($orphanedParticipants),
                    'orphaned_ids' => $orphanedParticipants,
                ];

                if (!$dryRun) {
                    // Eliminar participantes huérfanos de participant_course
                    $deleted = DB::table('participant_course')
                        ->where('course_id', $course->id)
                        ->whereIn('participant_id', $orphanedParticipants)
                        ->delete();

                    $totalRemoved += $deleted;
                } else {
                    $totalRemoved += count($orphanedParticipants);
                }
            }
        }

        // Mostrar resultados
        if (count($inconsistentCourses) === 0) {
            $this->info('No se encontraron inconsistencias. Todas las tablas están sincronizadas.');
            return Command::SUCCESS;
        }

        $this->table(
            ['Curso ID', 'Curso', 'Institución', 'En Course', 'En Program', 'Huérfanos'],
            collect($inconsistentCourses)->map(fn($c) => [
                $c['course_id'],
                substr($c['course_name'], 0, 20),
                substr($c['institution'], 0, 20),
                $c['in_course'],
                $c['in_program'],
                $c['orphaned'],
            ])
        );

        $this->newLine();

        if ($dryRun) {
            $this->warn("Se encontraron {$totalCourses} curso(s) con inconsistencias.");
            $this->warn("Se eliminarían {$totalRemoved} registro(s) huérfanos de participant_course.");
            $this->newLine();
            $this->info('Ejecuta sin --dry-run para aplicar los cambios.');
        } else {
            $this->info("Sincronización completada.");
            $this->info("Cursos corregidos: {$totalCourses}");
            $this->info("Registros eliminados de participant_course: {$totalRemoved}");
        }

        return Command::SUCCESS;
    }
}
