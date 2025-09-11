<?php

namespace App\Services\Admin\Reports\BsaleDocuments;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Carbon\Carbon;

class BsaleZipDownloadService
{
    /**
     * Create a zip file with all BSale documents based on filters
     */
    public function createZipDownload(array $filters = []): array
    {
        try {
            $year = $filters['year'] ?? null;
            $search = $filters['search'] ?? null;
            
            // Get all BSale documents based on filters
            $documents = $this->getBsaleDocuments($year, $search);
            
            if ($documents->isEmpty()) {
                throw new \Exception('No se encontraron documentos BSale para descargar');
            }

            // Create temporary zip file
            $zipFileName = $this->generateZipFileName($year, $search);
            $tempZipPath = storage_path('app/temp/' . $zipFileName);
            
            // Ensure temp directory exists
            if (!file_exists(dirname($tempZipPath))) {
                mkdir(dirname($tempZipPath), 0755, true);
            }

            // Crear ZIP usando el mismo método que SoftlandZipExporter (que funciona)
            $zip = new ZipArchive();
            $result = $zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            
            if ($result !== TRUE) {
                throw new \Exception('No se pudo crear el archivo ZIP: ' . $result);
            }

            // Crear archivo de texto de prueba
            $testContent = "Archivo de prueba BSale ZIP generado el " . now('America/Santiago')->format('Y-m-d H:i:s') . "\n";
            $testContent .= "Documentos BSale encontrados: " . $documents->count() . "\n";
            $testContent .= "Lista de documentos:\n";
            foreach ($documents as $doc) {
                $testContent .= "- {$doc['filename']} (Año: {$doc['year']})\n";
            }
            
            // Agregar archivo de texto de prueba al ZIP (mismo método que SoftlandZipExporter)
            $zip->addFromString('archivo_prueba_bsale_zip.txt', $testContent);

            // Add documents to zip usando el mismo método que SoftlandZipExporter
            $addedCount = 0;
            foreach ($documents as $document) {
                $filePath = Storage::path($document['path']);
                
                if (file_exists($filePath) && is_readable($filePath)) {
                    // Validate file integrity before adding to ZIP
                    if ($this->validatePdfFile($filePath)) {
                        // Read file content into memory (mismo método que SoftlandZipExporter)
                        $fileContent = file_get_contents($filePath);
                        
                        if ($fileContent !== false && strlen($fileContent) > 0) {
                            // Organize files in folders by year within the zip
                            $zipEntryName = $document['year'] ? 
                                $document['year'] . '/' . $document['filename'] : 
                                $document['filename'];
                            
                            // Use addFromString igual que SoftlandZipExporter
                            $zip->addFromString($zipEntryName, $fileContent);
                            $addedCount++;
                        } else {
                            Log::warning("No se pudo leer el contenido del archivo BSale: {$document['filename']}");
                        }
                    } else {
                        Log::warning("Archivo BSale corrupto o inválido: {$document['filename']}");
                    }
                } else {
                    Log::warning("Archivo BSale no accesible: {$document['filename']}");
                }
            }

            $zip->close();

            Log::info("BSale ZIP created successfully", [
                'zip_file' => $zipFileName,
                'documents_count' => $addedCount,
                'filters' => $filters
            ]);

            return [
                'success' => true,
                'zip_path' => $tempZipPath,
                'zip_filename' => $zipFileName,
                'documents_count' => $addedCount,
                'total_size' => filesize($tempZipPath)
            ];

        } catch (\Exception $e) {
            Log::error('Error creating BSale ZIP download: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get BSale documents based on filters
     */
    private function getBsaleDocuments(?string $year = null, ?string $search = null)
    {
        $documents = collect();
        $basePath = 'bsale_documents';
        
        // If year is specified, look in that year's folder
        if ($year) {
            $yearPath = $basePath . '/' . $year;
            if (Storage::exists($yearPath)) {
                $files = Storage::files($yearPath);
                foreach ($files as $file) {
                    $documents->push($this->formatBsaleDocument($file));
                }
            }
        } else {
            // Get all years
            $yearFolders = Storage::directories($basePath);
            foreach ($yearFolders as $yearFolder) {
                $files = Storage::files($yearFolder);
                foreach ($files as $file) {
                    $documents->push($this->formatBsaleDocument($file));
                }
            }
        }

        // Filter by search term if provided
        if ($search) {
            $documents = $documents->filter(function ($doc) use ($search) {
                return stripos($doc['filename'], $search) !== false ||
                       stripos($doc['bsale_number'], $search) !== false ||
                       stripos($doc['payment_id'], $search) !== false;
            });
        }

        return $documents->sortBy('filename');
    }

    /**
     * Format BSale document information
     */
    private function formatBsaleDocument(string $filePath): array
    {
        $filename = basename($filePath);
        
        // Extract info from filename: bsale_{bsale_number}_payment_{payment_id}.pdf
        $bsaleNumber = '';
        $paymentId = '';
        
        if (preg_match('/bsale_(.+?)_payment_(\d+)\.pdf/', $filename, $matches)) {
            $bsaleNumber = $matches[1];
            $paymentId = $matches[2];
        }

        return [
            'filename' => $filename,
            'path' => $filePath,
            'bsale_number' => $bsaleNumber,
            'payment_id' => $paymentId,
            'year' => dirname($filePath) !== 'bsale_documents' ? basename(dirname($filePath)) : '',
        ];
    }

    /**
     * Generate zip filename based on filters
     */
    private function generateZipFileName(?string $year = null, ?string $search = null): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        
        if ($year && $search) {
            $searchSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $search);
            return "bsale_documentos_{$year}_{$searchSafe}_{$timestamp}.zip";
        } elseif ($year) {
            return "bsale_documentos_{$year}_{$timestamp}.zip";
        } elseif ($search) {
            $searchSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $search);
            return "bsale_documentos_busqueda_{$searchSafe}_{$timestamp}.zip";
        } else {
            return "bsale_documentos_todos_{$timestamp}.zip";
        }
    }

    /**
     * Clean up temporary zip files older than 1 hour
     */
    public function cleanupTempFiles(): void
    {
        try {
            $tempDir = storage_path('app/temp');
            
            if (!is_dir($tempDir)) {
                return;
            }

            $files = glob($tempDir . '/bsale_documentos_*.zip');
            $oneHourAgo = time() - 3600;

            foreach ($files as $file) {
                if (filemtime($file) < $oneHourAgo) {
                    unlink($file);
                    Log::info('Cleaned up old BSale ZIP file: ' . basename($file));
                }
            }
        } catch (\Exception $e) {
            Log::error('Error cleaning up BSale ZIP temp files: ' . $e->getMessage());
        }
    }

    /**
     * Validate PDF file integrity
     */
    private function validatePdfFile(string $filePath): bool
    {
        try {
            // Check if file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                return false;
            }

            // Check file size (should be at least 1KB for a valid PDF)
            $fileSize = filesize($filePath);
            if ($fileSize < 1024) {
                return false;
            }

            // Read first few bytes to check PDF header
            $handle = fopen($filePath, 'rb');
            if (!$handle) {
                return false;
            }

            $header = fread($handle, 4);
            fclose($handle);

            // Check if it starts with PDF header
            if ($header !== '%PDF') {
                return false;
            }

            // Additional check: try to read the file content
            $content = file_get_contents($filePath);
            if ($content === false || strlen($content) !== $fileSize) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("Error validating PDF file {$filePath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
