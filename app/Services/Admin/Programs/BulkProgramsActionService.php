<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use Illuminate\Support\Facades\Storage;

class BulkProgramsActionService
{
    public function execute(string $action, array $programIds): string
    {
        $programsQuery = Program::whereIn('id', $programIds);

        switch ($action) {
            case 'activate':
                $programsQuery->update(['active' => true]);
                return 'Programas activados exitosamente.';
            case 'deactivate':
                $programsQuery->update(['active' => false]);
                return 'Programas desactivados exitosamente.';
            case 'delete':
                $programs = $programsQuery->get();
                foreach ($programs as $program) {
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
                $programsQuery->delete();
                return 'Programas eliminados exitosamente.';
        }

        return 'Acción no válida';
    }
}


