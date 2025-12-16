<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'content',
        'variables',
        'is_active',
        'version',
        'parent_template_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    /**
     * Tipos de documentos disponibles
     */
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_PAYMENT_RECEIPT = 'payment_receipt';
    public const TYPE_TERMS_ACCEPTANCE_EVIDENCE = 'terms_acceptance_evidence';

    /**
     * Obtener la plantilla activa por tipo
     */
    public static function getActiveByType(string $type): ?self
    {
        return self::where('type', $type)
            ->where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Crear una nueva versión de esta plantilla
     */
    public function createNewVersion(array $data, ?int $userId = null): self
    {
        // Desactivar todas las versiones anteriores del mismo tipo
        self::where('type', $this->type)
            ->update(['is_active' => false]);

        // Crear la nueva versión
        return self::create([
            'type' => $this->type,
            'name' => $data['name'] ?? $this->name,
            'content' => $data['content'],
            'variables' => $data['variables'] ?? $this->variables,
            'is_active' => true,
            'version' => $this->version + 1,
            'parent_template_id' => $this->id,
            'created_by' => $userId,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Reemplazar variables en el contenido con los datos proporcionados
     */
    public function render(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value ?? '', $content);
        }

        return $content;
    }

    /**
     * Relación con el usuario creador
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con la plantilla padre
     */
    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'parent_template_id');
    }

    /**
     * Relación con las versiones hijas
     */
    public function childTemplates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class, 'parent_template_id');
    }

    /**
     * Obtener todas las versiones de esta plantilla
     */
    public function getAllVersions()
    {
        // Si esta es una versión hija, obtener desde el padre
        $rootId = $this->parent_template_id ?? $this->id;

        return self::where('id', $rootId)
            ->orWhere('parent_template_id', $rootId)
            ->orderBy('version', 'desc')
            ->get();
    }
}
