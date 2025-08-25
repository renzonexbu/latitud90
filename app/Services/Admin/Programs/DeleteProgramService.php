<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DeleteProgramService
{
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
            // Eliminar archivos asociados si existen
            $this->deleteProgramFiles($program);

            $program->delete();

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
