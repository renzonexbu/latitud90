<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DeleteProgramService
{
    use AdminLogging;
    /**
     * Eliminar un programa y sus archivos asociados
     *
     * @param Program $program
     * @return bool
     * @throws \Exception
     */
    public function execute(Program $program): bool
    {
        try {
            // Guardar datos del programa antes de eliminarlo para el log
            $programData = $program->toArray();

            // Eliminar archivos asociados si existen
            $this->deleteProgramFiles($program);

            $program->delete();

            // Log the program deletion
            $this->logDelete(
                'programs',
                'Program',
                $programData['id'],
                "Programa eliminado: {$programData['name']} - {$programData['destination']}",
                $programData,
                [
                    'had_course' => !empty($programData['course_id']),
                    'had_files' => !empty($programData['itinerary_file']) || !empty($programData['travel_assistance_coverage']),
                ]
            );

            Log::info('Programa eliminado exitosamente', [
                'program_id' => $program->id,
                'program_name' => $program->name
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error al eliminar programa', [
                'program_id' => $program->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar archivos asociados al programa
     *
     * @param Program $program
     * @return void
     */
    private function deleteProgramFiles(Program $program): void
    {
        if ($program->itinerary_file) {
            Storage::disk('public')->delete($program->itinerary_file);
        }
        if ($program->travel_assistance_coverage) {
            Storage::disk('public')->delete($program->travel_assistance_coverage);
        }
        if ($program->equipment_list) {
            Storage::disk('public')->delete($program->equipment_list);
        }
    }
}
