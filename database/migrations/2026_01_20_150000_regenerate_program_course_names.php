<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Regenera los nombres de todos los program_courses usando el destination
     * del propio program_course (no del template/program).
     */
    public function up(): void
    {
        // Obtener todos los program_courses con sus relaciones
        $programCourses = DB::table('program_courses as pc')
            ->join('courses as c', 'pc.course_id', '=', 'c.id')
            ->join('institutions as i', 'c.institution_id', '=', 'i.id')
            ->join('programs as p', 'pc.program_id', '=', 'p.id')
            ->select([
                'pc.id',
                'pc.destination',
                'pc.departure_date',
                'i.name as institution_name',
                'c.education_level',
                'c.course_number',
                'c.grade',
                'p.name as program_name',
            ])
            ->get();

        foreach ($programCourses as $pc) {
            $name = $this->generateProgramCourseName($pc);

            DB::table('program_courses')
                ->where('id', $pc->id)
                ->update(['name' => $name]);
        }

        Log::info('Regenerated program_course names', [
            'total_updated' => $programCourses->count()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se puede revertir - los nombres originales se han perdido
    }

    /**
     * Generar el nombre del program_course con el formato correcto.
     */
    private function generateProgramCourseName($pc): string
    {
        $institutionName = $pc->institution_name;
        $level = $this->mapEducationLevel($pc->education_level);
        $num = $pc->course_number;
        $grade = $pc->grade;
        $destination = $pc->destination;
        $departureDate = $pc->departure_date;

        // Construir la parte del curso
        $coursePart = $num ? $num . '° ' . $level : $level;
        if ($grade) {
            $coursePart .= ' ' . strtoupper($grade);
        }

        // Obtener el año de la fecha de salida
        $year = $departureDate ? date('Y', strtotime($departureDate)) : date('Y');

        // Si tenemos todos los datos, generar el nombre completo
        if ($institutionName && $coursePart && $destination && $year) {
            return sprintf(
                '%s - %s - %s - %d',
                $institutionName,
                $coursePart,
                $destination,
                $year
            );
        }

        // Fallback: usar nombre de plantilla con año
        return $pc->program_name . ' - ' . $year;
    }

    /**
     * Mapear el nivel educativo a su abreviatura.
     */
    private function mapEducationLevel(string $level): string
    {
        $map = [
            'basica' => 'básico',
            'básica' => 'básico',
            'media' => 'medio',
            'prebásica' => 'prebásico',
            'prebasica' => 'prebásico',
        ];

        $lowerLevel = strtolower($level);
        return $map[$lowerLevel] ?? $level;
    }
};
