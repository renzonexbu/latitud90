<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageOptimizationService
{
    protected ImageManager $manager;
    protected int $quality;
    protected ?int $maxWidth;
    protected ?int $maxHeight;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
        $this->quality = 80;
        $this->maxWidth = 1920;
        $this->maxHeight = 1080;
    }

    /**
     * Optimiza y convierte una imagen a WebP
     */
    public function optimizeAndStore(
        UploadedFile $file,
        string $directory,
        ?string $filename = null,
        ?int $quality = null,
        ?int $maxWidth = null,
        ?int $maxHeight = null
    ): string {
        $quality = $quality ?? $this->quality;
        $maxWidth = $maxWidth ?? $this->maxWidth;
        $maxHeight = $maxHeight ?? $this->maxHeight;

        Log::info('ImageOptimizationService: Procesando imagen', [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'directory' => $directory,
        ]);

        // Generar nombre de archivo si no se proporciona
        if (!$filename) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $filename . '_' . time();
        }

        try {
            // Leer la imagen
            $image = $this->manager->read($file->getRealPath());

            // Redimensionar manteniendo proporción si excede dimensiones máximas
            $currentWidth = $image->width();
            $currentHeight = $image->height();

            if ($currentWidth > $maxWidth || $currentHeight > $maxHeight) {
                $image->scaleDown($maxWidth, $maxHeight);
            }

            // Convertir a WebP
            $webpFilename = $filename . '.webp';
            $fullPath = $directory . '/' . $webpFilename;

            // Asegurar que el directorio existe
            Storage::disk('public')->makeDirectory($directory);

            // Codificar como WebP y guardar
            $encoded = $image->toWebp($quality);
            Storage::disk('public')->put($fullPath, $encoded->toString());

            Log::info('ImageOptimizationService: Imagen guardada', [
                'path' => $fullPath,
                'new_size' => strlen($encoded->toString()),
            ]);

            return $fullPath;
        } catch (\Exception $e) {
            Log::error('ImageOptimizationService: Error al procesar imagen', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);
            throw $e;
        }
    }

    /**
     * Optimiza múltiples imágenes
     */
    public function optimizeMultiple(
        array $files,
        string $directory,
        string $prefix = 'image',
        ?int $quality = null,
        ?int $maxWidth = null,
        ?int $maxHeight = null
    ): array {
        $paths = [];
        $timestamp = now()->format('Y_m_d_H_i_s');

        Log::info('ImageOptimizationService: Procesando múltiples imágenes', [
            'total_files' => count($files),
            'directory' => $directory,
            'prefix' => $prefix,
        ]);

        foreach ($files as $index => $file) {
            if ($file && $file->isValid()) {
                $filename = "{$prefix}_{$timestamp}_{$index}";
                $paths[] = $this->optimizeAndStore(
                    $file,
                    $directory,
                    $filename,
                    $quality,
                    $maxWidth,
                    $maxHeight
                );
            } else {
                Log::warning('ImageOptimizationService: Archivo inválido o nulo', [
                    'index' => $index,
                    'file_is_null' => $file === null,
                ]);
            }
        }

        Log::info('ImageOptimizationService: Imágenes procesadas', [
            'total_saved' => count($paths),
        ]);

        return $paths;
    }

    /**
     * Establece la calidad de compresión (1-100)
     */
    public function setQuality(int $quality): self
    {
        $this->quality = max(1, min(100, $quality));
        return $this;
    }

    /**
     * Establece las dimensiones máximas
     */
    public function setMaxDimensions(?int $width, ?int $height): self
    {
        $this->maxWidth = $width;
        $this->maxHeight = $height;
        return $this;
    }
}
