<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'key',
        'type',
        'value',
        'default_value',
        'use_default',
        'label',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_default' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = ['image_url', 'effective_value'];

    /**
     * Obtener el valor efectivo (default o personalizado)
     */
    public function getEffectiveValueAttribute(): ?string
    {
        if ($this->use_default && $this->default_value !== null) {
            return $this->default_value;
        }
        return $this->value;
    }

    /**
     * Obtener URL de imagen si el tipo es image
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->type !== 'image' || empty($this->value)) {
            return null;
        }

        if (str_starts_with($this->value, 'http')) {
            return $this->value;
        }

        return asset('storage/' . $this->value);
    }

    /**
     * Obtener contenido por sección
     */
    public static function getBySection(string $section): array
    {
        return self::where('section', $section)
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->keyBy('key')
            ->toArray();
    }

    /**
     * Obtener todo el contenido agrupado por sección
     * Usa effective_value para respetar el flag use_default
     */
    public static function getAllGroupedBySections(): array
    {
        return self::where('is_active', true)
            ->orderBy('section')
            ->orderBy('order')
            ->get()
            ->groupBy('section')
            ->map(function ($items) {
                return $items->keyBy('key')->map(function ($item) {
                    // Sobrescribir value con effective_value para el frontend
                    $item->value = $item->effective_value;
                    return $item;
                });
            })
            ->toArray();
    }

    /**
     * Obtener valor específico por sección y key
     */
    public static function getValue(string $section, string $key, $default = null): mixed
    {
        $content = self::where('section', $section)
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        return $content ? $content->value : $default;
    }

    /**
     * Actualizar o crear contenido
     */
    public static function setValue(string $section, string $key, string $value, array $extra = []): self
    {
        return self::updateOrCreate(
            ['section' => $section, 'key' => $key],
            array_merge(['value' => $value], $extra)
        );
    }

    /**
     * Secciones disponibles
     */
    public static function getSections(): array
    {
        return [
            'hero' => 'Hero Section',
            'schools' => 'Colegios',
            'transform' => 'Sección Transformamos',
            'experiences' => 'Nuestras Experiencias',
            'courses' => 'Destinos/Banners',
            'program_detail' => 'Detalle de Programa',
            'faq' => 'Preguntas Frecuentes',
            'contact' => 'Contacto / WhatsApp',
            'footer' => 'Footer',
        ];
    }

    /**
     * Tipos de contenido disponibles
     */
    public static function getTypes(): array
    {
        return [
            'text' => 'Texto corto',
            'textarea' => 'Texto largo',
            'html' => 'HTML/Editor',
            'image' => 'Imagen',
        ];
    }
}
