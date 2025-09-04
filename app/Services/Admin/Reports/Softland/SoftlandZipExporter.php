<?php

namespace App\Services\Admin\Reports\Softland;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SoftlandZipExporter
{
    private SoftlandAuxiliaresService $auxiliaresService;
    private AuxiliaresExporter $auxiliaresExporter;
    private SoftlandDataService $softlandDataService;
    private ExcelExporter $excelExporter;

    public function __construct(
        SoftlandAuxiliaresService $auxiliaresService,
        AuxiliaresExporter $auxiliaresExporter,
        SoftlandDataService $softlandDataService,
        ExcelExporter $excelExporter
    ) {
        $this->auxiliaresService = $auxiliaresService;
        $this->auxiliaresExporter = $auxiliaresExporter;
        $this->softlandDataService = $softlandDataService;
        $this->excelExporter = $excelExporter;
    }

    /**
     * Genera un archivo ZIP con ambos archivos Softland
     */
    public function exportToZip(array $filters = [], string $format = 'excel'): string
    {
        try {
            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generar timestamp para nombres únicos
            $timestamp = now()->format('Y-m-d_H-i-s');
            $zipFileName = "softland_completo_{$timestamp}.zip";
            $zipPath = "{$tempDir}/{$zipFileName}";

            // Crear archivo ZIP
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception("No se pudo crear el archivo ZIP: {$zipPath}");
            }

            // 1. Generar archivo de Auxiliares
            $auxiliaresFileName = $this->generateAuxiliaresFile($format, $timestamp);
            if ($auxiliaresFileName && file_exists($auxiliaresFileName)) {
                $auxiliaresContent = file_get_contents($auxiliaresFileName);
                $zip->addFromString(basename($auxiliaresFileName), $auxiliaresContent);
            }

            // 2. Generar archivo de Movimientos Contables
            $movimientosFileName = $this->generateMovimientosFile($filters, $format, $timestamp);
            if ($movimientosFileName && file_exists($movimientosFileName)) {
                $movimientosContent = file_get_contents($movimientosFileName);
                $zip->addFromString(basename($movimientosFileName), $movimientosContent);
            }

            // Cerrar ZIP
            $zip->close();

            // Limpiar archivos temporales individuales
            $this->cleanupTempFiles([$auxiliaresFileName, $movimientosFileName]);

            return $zipPath;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Genera el archivo de auxiliares
     */
    private function generateAuxiliaresFile(string $format, string $timestamp): ?string
    {
        try {
            $fileName = "auxiliares_softland_{$timestamp}." . ($format === 'excel' ? 'xlsx' : 'csv');
            $filePath = storage_path("app/temp/{$fileName}");

            if ($format === 'excel') {
                // Generar Excel de auxiliares usando el método existente
                $response = $this->auxiliaresExporter->exportToExcel();
                
                // Obtener el contenido del StreamedResponse
                ob_start();
                $response->sendContent();
                $content = ob_get_clean();
                
                // Guardar el contenido en el archivo
                file_put_contents($filePath, $content);
            } else {
                // Generar CSV de auxiliares
                $tempCsvPath = $this->auxiliaresExporter->exportToCsv();
                if (file_exists($tempCsvPath)) {
                    // Simplemente usar el archivo temporal como archivo final
                    $filePath = $tempCsvPath;
                }
            }

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Error al generar archivo de auxiliares', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Genera el archivo de movimientos contables
     */
    private function generateMovimientosFile(array $filters, string $format, string $timestamp): ?string
    {
        try {
            $fileName = "movimientos_softland_{$timestamp}." . ($format === 'excel' ? 'xlsx' : 'csv');
            $filePath = storage_path("app/temp/{$fileName}");

            // Generar archivo de movimientos usando el método existente
            $response = $this->excelExporter->export($fileName, $filters, $format);
            
            // Obtener el contenido del StreamedResponse
            ob_start();
            $response->sendContent();
            $content = ob_get_clean();
            
            // Guardar el contenido en el archivo
            file_put_contents($filePath, $content);

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Error al generar archivo de movimientos', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Limpia archivos temporales
     */
    private function cleanupTempFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file && file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Limpia archivos ZIP antiguos (más de 1 hora)
     */
    public function cleanupOldFiles(): void
    {
        try {
            $tempDir = storage_path('app/temp');
            $files = glob("{$tempDir}/softland_completo_*.zip");
            
            foreach ($files as $file) {
                if (filemtime($file) < (time() - 3600)) { // 1 hora
                    unlink($file);
                }
            }
        } catch (\Exception $e) {
            // Silenciar errores de limpieza
        }
    }
}
