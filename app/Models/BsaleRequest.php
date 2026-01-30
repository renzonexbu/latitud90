<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BsaleRequest extends Model
{
    protected $table = 'bsale_requests';

    protected $fillable = [
        'payment_id',
        'order_detail_id',
        'status',
        'document_type',
        'request_data',
        'response_data',
        'bsale_document_id',
        'bsale_number',
        'bsale_token',
        'bsale_url',
        'error_message',
        'error_code',
        'attempts',
        'max_attempts',
        'scheduled_at',
        'processing_started_at',
        'processed_at',
        'last_attempt_at',
        'source',
        'metadata',
        'processed_by',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processed_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
    ];

    // Estados posibles
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SKIPPED = 'skipped';

    // Tipos de documento
    const DOC_TYPE_BOLETA = 'B2';
    const DOC_TYPE_NOTA_CREDITO = 'NC';

    /**
     * Relación con Payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Relación con OrderDetail
     */
    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    /**
     * Usuario que procesó la solicitud
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope para solicitudes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para solicitudes listas para procesar
     */
    public function scopeReadyToProcess($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where('attempts', '<', \DB::raw('max_attempts'));
    }

    /**
     * Scope para solicitudes fallidas
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope para solicitudes completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Verificar si puede reintentar
     */
    public function canRetry(): bool
    {
        return $this->attempts < $this->max_attempts
            && in_array($this->status, [self::STATUS_PENDING, self::STATUS_FAILED]);
    }

    /**
     * Verificar si está en proceso
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Verificar si está completado
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Verificar si falló
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Marcar como en proceso
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processing_started_at' => now(),
            'attempts' => $this->attempts + 1,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Marcar como completado
     */
    public function markAsCompleted(array $responseData, ?string $documentId = null, ?string $number = null, ?string $token = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'response_data' => $responseData,
            'bsale_document_id' => $documentId,
            'bsale_number' => $number,
            'bsale_token' => $token,
            'processed_at' => now(),
            'error_message' => null,
            'error_code' => null,
        ]);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed(string $errorMessage, ?string $errorCode = null, ?array $responseData = null): void
    {
        $status = $this->attempts >= $this->max_attempts
            ? self::STATUS_FAILED
            : self::STATUS_PENDING; // Vuelve a pending para reintentar

        $this->update([
            'status' => $status,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'response_data' => $responseData,
            'processing_started_at' => null,
        ]);
    }

    /**
     * Marcar como omitido (ej: ya existe boleta)
     */
    public function markAsSkipped(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'error_message' => $reason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Cancelar solicitud
     */
    public function cancel(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'processed_by' => $userId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Resetear para reintentar
     */
    public function resetForRetry(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
            'error_message' => null,
            'error_code' => null,
            'processing_started_at' => null,
            'processed_at' => null,
        ]);
    }

    /**
     * Obtener etiqueta de estado legible
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_PROCESSING => 'Procesando',
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_FAILED => 'Fallido',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_SKIPPED => 'Omitido',
            default => $this->status,
        };
    }

    /**
     * Obtener clase CSS para el estado
     */
    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PROCESSING => 'bg-blue-100 text-blue-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-800',
            self::STATUS_SKIPPED => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Crear solicitud desde un Payment
     */
    public static function createFromPayment(
        Payment $payment,
        string $documentType = self::DOC_TYPE_BOLETA,
        string $source = 'payment_confirmation',
        ?array $metadata = null
    ): self {
        return self::create([
            'payment_id' => $payment->id,
            'order_detail_id' => $payment->order_detail_id,
            'status' => self::STATUS_PENDING,
            'document_type' => $documentType,
            'source' => $source,
            'metadata' => $metadata,
            'scheduled_at' => now(),
        ]);
    }

    /**
     * Verificar si ya existe una solicitud pendiente o completada para un payment
     */
    public static function existsForPayment(int $paymentId, string $documentType = self::DOC_TYPE_BOLETA): bool
    {
        return self::where('payment_id', $paymentId)
            ->where('document_type', $documentType)
            ->whereIn('status', [
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
                self::STATUS_COMPLETED,
            ])
            ->exists();
    }

    /**
     * Obtener solicitud activa para un payment
     */
    public static function getActiveForPayment(int $paymentId, string $documentType = self::DOC_TYPE_BOLETA): ?self
    {
        return self::where('payment_id', $paymentId)
            ->where('document_type', $documentType)
            ->whereIn('status', [
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
                self::STATUS_COMPLETED,
            ])
            ->first();
    }
}
