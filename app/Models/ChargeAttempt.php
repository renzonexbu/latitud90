<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeAttempt extends Model
{
    protected $fillable = [
        'program_subscription_id',
        'installment_id',
        'virtualpos_charge_id',
        'original_charge_id',
        'attempt_number',
        'amount',
        'description',
        'type',
        'status',
        'failure_reason',
        'virtualpos_status',
        'api_response',
        'attempted_at',
        'resolved_at',
    ];

    protected $casts = [
        'api_response' => 'array',
        'attempted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Relación con la suscripción
     */
    public function programSubscription(): BelongsTo
    {
        return $this->belongsTo(ProgramSubscription::class);
    }

    /**
     * Relación con la cuota
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    /**
     * Verificar si el intento fue exitoso
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Verificar si el intento falló
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Verificar si el intento está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Marcar como exitoso
     */
    public function markAsSuccess(string $chargeId = null, array $apiResponse = null): void
    {
        $this->update([
            'status' => 'success',
            'virtualpos_charge_id' => $chargeId ?? $this->virtualpos_charge_id,
            'api_response' => $apiResponse ?? $this->api_response,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed(string $reason, string $virtualposStatus = null, array $apiResponse = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'virtualpos_status' => $virtualposStatus,
            'api_response' => $apiResponse ?? $this->api_response,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Obtener el número de intentos para una cuota específica
     */
    public static function getAttemptCountForInstallment(int $subscriptionId, int $installmentId): int
    {
        return static::where('program_subscription_id', $subscriptionId)
            ->where('installment_id', $installmentId)
            ->count();
    }

    /**
     * Obtener el número de intentos para un cargo original
     */
    public static function getAttemptCountForOriginalCharge(int $subscriptionId, string $originalChargeId): int
    {
        return static::where('program_subscription_id', $subscriptionId)
            ->where('original_charge_id', $originalChargeId)
            ->count();
    }

    /**
     * Verificar si se puede hacer un nuevo reintento
     */
    public static function canRetry(int $subscriptionId, string $originalChargeId, int $maxAttempts = 3): bool
    {
        $attempts = static::getAttemptCountForOriginalCharge($subscriptionId, $originalChargeId);
        return $attempts < $maxAttempts;
    }
}
