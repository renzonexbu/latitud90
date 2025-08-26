<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Storage;

class BulkProgramsActionService
{
    use AdminLogging;
    public function execute(string $action, array $programIds): string
    {
        $programsQuery = Program::whereIn('id', $programIds);

        switch ($action) {
            case 'activate':
                $programs = $programsQuery->get();
                $programsQuery->update(['active' => true]);
                
                // Log bulk activation
                foreach ($programs as $program) {
                    $this->logStatusChange(
                        'programs',
                        'Program',
                        $program->id,
                        'inactive',
                        'active',
                        "Programa activado en acción masiva: {$program->name} ({$program->code})",
                        [
                            'program_name' => $program->name,
                            'program_code' => $program->code,
                            'action_type' => 'bulk_activate',
                            'affected_programs_count' => count($programIds),
                        ]
                    );
                }
                return 'Programas activados exitosamente.';
            case 'deactivate':
                $programs = $programsQuery->get();
                $programsQuery->update(['active' => false]);
                
                // Log bulk deactivation
                foreach ($programs as $program) {
                    $this->logStatusChange(
                        'programs',
                        'Program',
                        $program->id,
                        'active',
                        'inactive',
                        "Programa desactivado en acción masiva: {$program->name} ({$program->code})",
                        [
                            'program_name' => $program->name,
                            'program_code' => $program->code,
                            'action_type' => 'bulk_deactivate',
                            'affected_programs_count' => count($programIds),
                        ]
                    );
                }
                return 'Programas desactivados exitosamente.';
            case 'delete':
                $programs = $programsQuery->get();
                
                // Log bulk deletion before deleting
                foreach ($programs as $program) {
                    $this->logDelete(
                        'programs',
                        'Program',
                        $program->id,
                        "Programa eliminado en acción masiva: {$program->name} ({$program->code})",
                        $program->toArray(),
                        [
                            'program_name' => $program->name,
                            'program_code' => $program->code,
                            'action_type' => 'bulk_delete',
                            'affected_programs_count' => count($programIds),
                            'files_deleted' => [
                                'itinerary_file' => $program->itinerary_file,
                                'travel_assistance_coverage' => $program->travel_assistance_coverage,
                                'equipment_list' => $program->equipment_list,
                            ],
                        ]
                    );
                }
                
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


