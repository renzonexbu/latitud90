<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateProgramService
{
    use AdminLogging;

    /**
     * Execute the program template creation.
     * Programs are now just templates - no course/payment/participant logic here.
     */
    public function execute(array $programData): Program
    {
        try {
            DB::beginTransaction();

            // Procesar los pilares como string separado por comas
            $pillars = [];
            if (!empty($programData['pilar_1'])) {
                $pillars[] = $programData['pilar_1'];
            }
            if (!empty($programData['pilar_2'])) {
                $pillars[] = $programData['pilar_2'];
            }
            if (!empty($programData['pilar_3'])) {
                $pillars[] = $programData['pilar_3'];
            }
            if (!empty($programData['pilar_4'])) {
                $pillars[] = $programData['pilar_4'];
            }
            $programData['pillars'] = implode(', ', $pillars);

            // Crear el programa (plantilla) sin archivos por ahora
            $program = Program::create([
                'name' => $programData['name'],
                'destination' => $programData['destination'],
                'trip_description' => null, // Campo eliminado del frontend
                'images_folder' => null, // Se actualizará después
                'pillars' => $programData['pillars'] ?? null,
                'itinerary_description' => null, // Campo eliminado del frontend
                'itinerary_file' => null, // Se actualizará después
                'travel_assistance_coverage' => null, // Se actualizará después
                'equipment_list' => null, // Se actualizará después
                'created_by' => auth()->id(),
                'active' => $programData['active'] ?? true,
            ]);

            // Procesar archivos después de crear el programa para poder usar su ID
            $processedData = $this->processFiles($programData, $program);

            // Actualizar el programa con las rutas de los archivos
            $program->update([
                'images_folder' => $processedData['images_folder'] ?? null,
                'itinerary_file' => $processedData['itinerary_file_path'] ?? null,
                'travel_assistance_coverage' => $processedData['coverage_file_path'] ?? null,
                'equipment_list' => $processedData['equipment_file_path'] ?? null,
            ]);

            DB::commit();

            // Log the program template creation
            $this->logCreate(
                'programs',
                'Program',
                $program->id,
                "Plantilla de programa creada: {$program->name} - {$program->destination}",
                $program->toArray(),
                [
                    'has_files' => !empty($processedData['images_folder']) || !empty($processedData['itinerary_file_path']),
                ]
            );

            return $program;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear plantilla de programa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process uploaded files and store them.
     */
    private function processFiles(array $programData, Program $program): array
    {
        $programId = $program->id;
        $timestamp = now()->format('Y_m_d_H_i_s');

        // Crear la carpeta base del programa
        $programFolder = "public/programs/{$programId}";

        // Procesar archivo de itinerario
        if (isset($programData['itinerary_file']) && $programData['itinerary_file']) {
            $pdfPath = "{$programFolder}/pdfs/itinerario_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['itinerary_file']->storeAs($pdfPath, null, 'public');
            $programData['itinerary_file_path'] = $fullPath;
        }

        // Procesar archivo de cobertura
        if (isset($programData['coverage_file']) && $programData['coverage_file']) {
            $pdfPath = "{$programFolder}/pdfs/cobertura_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['coverage_file']->storeAs($pdfPath, null, 'public');
            $programData['coverage_file_path'] = $fullPath;
        }

        // Procesar archivo de lista de equipo
        if (isset($programData['equipment_file']) && $programData['equipment_file']) {
            $pdfPath = "{$programFolder}/pdfs/equipo_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['equipment_file']->storeAs($pdfPath, null, 'public');
            $programData['equipment_file_path'] = $fullPath;
        }

        // Procesar imágenes si se proporcionaron
        if (isset($programData['images']) && is_array($programData['images'])) {
            $imagePaths = [];
            foreach ($programData['images'] as $index => $image) {
                if ($image && $image->isValid()) {
                    $imagePath = "{$programFolder}/images/imagen_{$programId}_{$timestamp}_{$index}.{$image->getClientOriginalExtension()}";
                    $fullPath = $image->storeAs($imagePath, null, 'public');
                    $imagePaths[] = $fullPath;
                }
            }
            if (!empty($imagePaths)) {
                // Guardar solo la ruta de la carpeta de imágenes
                $programData['images_folder'] = $programFolder . '/images';
            }
        }

        return $programData;
    }
}
