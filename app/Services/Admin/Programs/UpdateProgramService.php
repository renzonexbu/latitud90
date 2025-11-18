<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UpdateProgramService
{
    use AdminLogging;

    /**
     * Execute the program template update.
     * Programs are now just templates - no course/payment/participant logic here.
     */
    public function execute(array $programData, Program $program): Program
    {
        // Capturar datos originales antes de la actualización para el logging
        $originalData = $program->toArray();

        try {
            DB::beginTransaction();

            // Procesar los pilares solo si alguno fue enviado desde el frontend
            $hasAnyPillarInput = array_key_exists('pilar_1', $programData)
                || array_key_exists('pilar_2', $programData)
                || array_key_exists('pilar_3', $programData)
                || array_key_exists('pilar_4', $programData);

            if ($hasAnyPillarInput) {
                $pillars = [];
                if (isset($programData['pilar_1']) && $programData['pilar_1'] !== '') {
                    $pillars[] = $programData['pilar_1'];
                }
                if (isset($programData['pilar_2']) && $programData['pilar_2'] !== '') {
                    $pillars[] = $programData['pilar_2'];
                }
                if (isset($programData['pilar_3']) && $programData['pilar_3'] !== '') {
                    $pillars[] = $programData['pilar_3'];
                }
                if (isset($programData['pilar_4']) && $programData['pilar_4'] !== '') {
                    $pillars[] = $programData['pilar_4'];
                }
                $programData['pillars'] = implode(', ', $pillars);
            }

            // Borrar archivos/imágenes marcados para eliminar
            $this->deleteMarkedFilesAndImages($programData, $program);

            // Procesar nuevos archivos antes de actualizar el programa
            $processedData = $this->processFiles($programData, $program);

            // Preparar datos para actualización (solo campos del template)
            $updateData = [];

            // Campos básicos del template
            if (isset($programData['name'])) {
                $updateData['name'] = $programData['name'];
            }
            if (isset($programData['destination'])) {
                $updateData['destination'] = $programData['destination'];
            }
            if (isset($programData['pillars'])) {
                $updateData['pillars'] = $programData['pillars'];
            }
            if (isset($programData['active'])) {
                $updateData['active'] = $programData['active'];
            }

            // Archivos procesados
            if (isset($processedData['images_folder'])) {
                $updateData['images_folder'] = $processedData['images_folder'];
            }
            if (isset($processedData['itinerary_file_path'])) {
                $updateData['itinerary_file'] = $processedData['itinerary_file_path'];
            }
            if (isset($processedData['coverage_file_path'])) {
                $updateData['travel_assistance_coverage'] = $processedData['coverage_file_path'];
            }
            if (isset($processedData['equipment_file_path'])) {
                $updateData['equipment_list'] = $processedData['equipment_file_path'];
            }

            // Actualizar el programa
            $program->update($updateData);

            DB::commit();

            // Log the program template update
            $this->logUpdate(
                'programs',
                'Program',
                $program->id,
                "Plantilla de programa actualizada: {$program->name} - {$program->destination}",
                $originalData,
                $program->toArray()
            );

            return $program->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar plantilla de programa', [
                'program_id' => $program->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Delete marked files and images.
     */
    private function deleteMarkedFilesAndImages(array $programData, Program $program): void
    {
        // Eliminar archivos PDF marcados
        if (!empty($programData['filesToDelete']) && is_array($programData['filesToDelete'])) {
            foreach ($programData['filesToDelete'] as $fileType) {
                $fieldName = match ($fileType) {
                    'itinerary' => 'itinerary_file',
                    'coverage' => 'travel_assistance_coverage',
                    'equipment' => 'equipment_list',
                    default => null,
                };

                if ($fieldName && $program->$fieldName) {
                    Storage::disk('public')->delete($program->$fieldName);
                    $program->update([$fieldName => null]);
                }
            }
        }

        // Eliminar imágenes marcadas
        if (!empty($programData['imagesToDelete']) && is_array($programData['imagesToDelete'])) {
            $images = $program->images ?? [];

            foreach ($programData['imagesToDelete'] as $imageIndex) {
                if (isset($images[$imageIndex])) {
                    $imagePath = $images[$imageIndex]['path'] ?? null;
                    if ($imagePath) {
                        Storage::disk('public')->delete($imagePath);
                    }
                }
            }
        }
    }

    /**
     * Process uploaded files and store them.
     */
    private function processFiles(array $programData, Program $program): array
    {
        $programId = $program->id;
        $timestamp = now()->format('Y_m_d_H_i_s');
        $programFolder = "public/programs/{$programId}";
        $processedData = [];

        // Procesar archivo de itinerario
        if (isset($programData['itinerary_file']) && $programData['itinerary_file']) {
            // Eliminar archivo anterior si existe
            if ($program->itinerary_file) {
                Storage::disk('public')->delete($program->itinerary_file);
            }

            $pdfPath = "{$programFolder}/pdfs/itinerario_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['itinerary_file']->storeAs($pdfPath, null, 'public');
            $processedData['itinerary_file_path'] = $fullPath;
        }

        // Procesar archivo de cobertura
        if (isset($programData['coverage_file']) && $programData['coverage_file']) {
            // Eliminar archivo anterior si existe
            if ($program->travel_assistance_coverage) {
                Storage::disk('public')->delete($program->travel_assistance_coverage);
            }

            $pdfPath = "{$programFolder}/pdfs/cobertura_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['coverage_file']->storeAs($pdfPath, null, 'public');
            $processedData['coverage_file_path'] = $fullPath;
        }

        // Procesar archivo de lista de equipo
        if (isset($programData['equipment_file']) && $programData['equipment_file']) {
            // Eliminar archivo anterior si existe
            if ($program->equipment_list) {
                Storage::disk('public')->delete($program->equipment_list);
            }

            $pdfPath = "{$programFolder}/pdfs/equipo_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['equipment_file']->storeAs($pdfPath, null, 'public');
            $processedData['equipment_file_path'] = $fullPath;
        }

        // Procesar nuevas imágenes si se proporcionaron
        if (isset($programData['images']) && is_array($programData['images'])) {
            $existingImages = $program->images ?? [];

            foreach ($programData['images'] as $index => $image) {
                if ($image && $image->isValid()) {
                    $imagePath = "{$programFolder}/images/imagen_{$programId}_{$timestamp}_{$index}.{$image->getClientOriginalExtension()}";
                    $fullPath = $image->storeAs($imagePath, null, 'public');
                }
            }

            // Mantener la carpeta de imágenes
            $processedData['images_folder'] = $programFolder . '/images';
        }

        return $processedData;
    }
}
