<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;

class GetFilesService
{
    use AdminLogging;
    /**
     * Obtener archivos e imágenes de un programa
     *
     * @param Program $program
     * @return array
     */
    public function execute(Program $program): array
    {
        // Log the files view
        $this->logView(
            'programs',
            'ProgramFiles',
            $program->id,
            "Archivos consultados para programa: {$program->name} ({$program->code})",
            [
                'program_name' => $program->name,
                'program_code' => $program->code,
                'has_itinerary' => !empty($program->itinerary_file_url),
                'has_travel_assistance' => !empty($program->travel_assistance_coverage_url),
                'has_equipment_list' => !empty($program->equipment_list_url),
                'images_count' => $program->images ? count($program->images) : 0,
            ]
        );

        return [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'itinerary_file' => $program->itinerary_file_url,
                'travel_assistance_coverage' => $program->travel_assistance_coverage_url,
                'equipment_list' => $program->equipment_list_url,
                'images' => $program->images,
            ]
        ];
    }
}
