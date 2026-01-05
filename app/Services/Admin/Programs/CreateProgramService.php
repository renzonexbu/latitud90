<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Services\ImageOptimizationService;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateProgramService
{
    use AdminLogging;

    protected ImageOptimizationService $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }

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
        $programFolder = "programs/{$programId}";

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

        // Procesar imágenes si se proporcionaron (optimizadas y convertidas a WebP)
        if (isset($programData['images']) && is_array($programData['images'])) {
            Log::info('🔍 CreateProgramService - Imágenes recibidas:', [
                'total_images' => count($programData['images']),
                'images_keys' => array_keys($programData['images']),
            ]);

            $imagesDirectory = "{$programFolder}/images";
            $imagePaths = $this->imageService->optimizeMultiple(
                $programData['images'],
                $imagesDirectory,
                "imagen_{$programId}"
            );

            Log::info('🔍 CreateProgramService - Imágenes procesadas:', [
                'total_saved' => count($imagePaths),
                'paths' => $imagePaths,
            ]);

            if (!empty($imagePaths)) {
                $programData['images_folder'] = $imagesDirectory;
            }
        } else {
            Log::warning('⚠️ CreateProgramService - No se recibieron imágenes o no es un array:', [
                'isset' => isset($programData['images']),
                'is_array' => isset($programData['images']) ? is_array($programData['images']) : 'N/A',
            ]);
        }

        return $programData;
    }
}
