<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;

class GetFilesService
{
    /**
     * Obtener archivos e imágenes de un programa
     *
     * @param Program $program
     * @return array
     */
    public function execute(Program $program): array
    {
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
