<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class DeleteProgramService
{
    use AdminLogging;

    /**
     * Verificar si la plantilla puede ser eliminada
     *
     * @param Program $program
     * @return array ['can_delete' => bool, 'message' => string, 'courses_count' => int]
     */
    public function canDelete(Program $program): array
    {
        $coursesCount = $program->programCourses()->count();

        if ($coursesCount > 0) {
            return [
                'can_delete' => false,
                'message' => "No se puede eliminar esta plantilla porque está siendo utilizada por {$coursesCount} curso(s).",
                'courses_count' => $coursesCount,
            ];
        }

        return [
            'can_delete' => true,
            'message' => 'La plantilla puede ser eliminada.',
            'courses_count' => 0,
        ];
    }

    /**
     * Eliminar un programa y sus archivos asociados
     *
     * @param Program $program
     * @return bool
     * @throws \Exception
     */
    public function execute(Program $program): bool
    {
        // Verificar si se puede eliminar
        $canDeleteResult = $this->canDelete($program);
        if (!$canDeleteResult['can_delete']) {
            throw new \Exception($canDeleteResult['message']);
        }

        try {
            // Guardar datos del programa antes de eliminarlo para el log
            $programData = $program->toArray();

            // Eliminar archivos PDF asociados si existen
            $this->deleteProgramFiles($program);

            // Eliminar carpeta de imágenes si existe
            $this->deleteProgramImages($program);

            $program->delete();

            // Log the program deletion
            $this->logDelete(
                'programs',
                'ProgramTemplate',
                $programData['id'],
                "Plantilla de programa eliminada: {$programData['name']}",
                $programData,
                [
                    'had_files' => !empty($programData['itinerary_file']) || !empty($programData['travel_assistance_coverage']),
                    'had_images' => !empty($programData['images_folder']),
                ]
            );

            Log::info('Plantilla de programa eliminada exitosamente', [
                'program_id' => $programData['id'],
                'program_name' => $programData['name']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error al eliminar plantilla de programa', [
                'program_id' => $program->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar archivos PDF asociados al programa
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

    /**
     * Eliminar carpeta de imágenes del programa
     *
     * @param Program $program
     * @return void
     */
    private function deleteProgramImages(Program $program): void
    {
        if (!$program->images_folder) {
            return;
        }

        // Construir la ruta correcta para las imágenes
        $relativePath = str_replace('public/', '', $program->images_folder);
        $fullPath = storage_path('app/public/' . $relativePath);

        if (File::isDirectory($fullPath)) {
            File::deleteDirectory($fullPath);
            Log::info('Carpeta de imágenes eliminada', [
                'program_id' => $program->id,
                'path' => $fullPath
            ]);
        }
    }
}
