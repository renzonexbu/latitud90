<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\Document;

class FrequentClient extends Model
{
    use HasFactory;

    protected $table = 'frequent_client';

    protected $fillable = [
        'full_name',
        'document_id',
        'document',
        'email',
        'phone_code',
        'phone',
        'country_id',
        'region_id',
        'comune_id',
        'terms_accepted',
        'marketing_accepted',
        'last_used_at',
        'usage_count',
    ];

    protected $casts = [
        'terms_accepted' => 'boolean',
        'marketing_accepted' => 'boolean',
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    /**
     * Relación con el tipo de documento
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Relación con el país
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Relación con la región
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Relación con la comuna
     */
    public function comune(): BelongsTo
    {
        return $this->belongsTo(Comune::class, 'comune_id');
    }

    /**
     * Buscar cliente frecuente por documento
     */
    public static function findByDocument(int $documentId, string $document): ?self
    {
        return static::where('document_id', $documentId)
            ->where('document', $document)
            ->first();
    }

    /**
     * Incrementar contador de uso
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Almacenar datos del formulario de pago con formato correcto
     */
    public static function storeFromPaymentForm(array $data): self
    {
        // Obtener el tipo de documento para determinar el formato
        $documentType = Document::find($data['document_id']);
        $documentNumber = $data['document'];

        // Formatear el número de documento según el tipo
        if ($documentType && strtolower($documentType->name) === 'rut') {
            // Escapar RUT: quitar puntos y guiones
            $documentNumber = preg_replace('/[.-]/', '', $documentNumber);
        } elseif ($documentType && strtolower($documentType->name) === 'pasaporte') {
            // Pasaporte en mayúsculas
            $documentNumber = strtoupper($documentNumber);
        }

        // Crear o actualizar el cliente frecuente
        $client = static::updateOrCreate(
            [
                'document_id' => $data['document_id'],
                'document' => $documentNumber,
            ],
            [
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone_code' => $data['phone_code'],
                'phone' => $data['phone'],
                'country_id' => $data['country_id'],
                'region_id' => $data['region_id'],
                'comune_id' => $data['comune_id'],
                'terms_accepted' => $data['terms_accepted'],
                'marketing_accepted' => $data['marketing_accepted'],
                'last_used_at' => now(),
                'usage_count' => DB::raw('usage_count + 1'),
            ]
        );

        return $client;
    }
}
