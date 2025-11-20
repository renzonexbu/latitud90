<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'participant_id',
        'program_id',
        'virtualpos_subscription_id',
        'virtualpos_plan_id',
        'plan_name',
        'status',
        'amount',
        'currency',
        'automatic_renewal',
        'payment_method',
        'subscription_date',
        'cancelled_at',
        'finished_at',
        'channel',
        'service_id',
        'charge_program',
        'processed_failed_charge_ids',
        'client_data',
        'card_change_link',
        'card_change_link_generated_at',
        'api_response',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_method' => 'array',
        'charge_program' => 'array',
        'processed_failed_charge_ids' => 'array',
        'client_data' => 'array',
        'api_response' => 'array',
        'subscription_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'finished_at' => 'datetime',
        'card_change_link_generated_at' => 'datetime',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
    ];

    /**
     * Relación con el participante
     */
    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Relación con el programa (program_id apunta a program_courses)
     */
    public function program()
    {
        return $this->belongsTo(ProgramCourse::class, 'program_id');
    }

    /**
     * Alias para la relación con el programa
     */
    public function programCourse()
    {
        return $this->belongsTo(ProgramCourse::class, 'program_id');
    }

    /**
     * Relación con el plan de cuotas
     * Nota: Esta relación usa participant_id y program_id para encontrar el plan correcto
     */
    public function installmentPlan()
    {
        return $this->hasOne(InstallmentPlan::class, 'participant_id', 'participant_id')
            ->where('program_id', $this->program_id);
    }

    /**
     * Método auxiliar para obtener el plan de cuotas con eager loading
     */
    public function getInstallmentPlanAttribute()
    {
        if (!$this->relationLoaded('installmentPlanRelation')) {
            return InstallmentPlan::where('participant_id', $this->participant_id)
                ->where('program_id', $this->program_id)
                ->first();
        }
        return $this->getRelation('installmentPlanRelation');
    }

    /**
     * Scope para suscripciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVA');
    }

    /**
     * Scope para suscripciones canceladas
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'CANCELADA');
    }

    /**
     * Verificar si la suscripción está activa
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVA';
    }

    /**
     * Verificar si la suscripción fue cancelada
     */
    public function isCancelled(): bool
    {
        return $this->status === 'CANCELADA';
    }

    /**
     * Verificar si la suscripción falló
     */
    public function isFailed(): bool
    {
        return $this->status === 'SUSCRIPCION_FALLIDA';
    }

    /**
     * Obtener los cobros pendientes
     */
    public function getPendingCharges(): array
    {
        if (!$this->charge_program) {
            return [];
        }

        return array_filter($this->charge_program, function ($charge) {
            return isset($charge['status']) && $charge['status'] === 'pending';
        });
    }

    /**
     * Obtener los cobros pagados
     */
    public function getPaidCharges(): array
    {
        if (!$this->charge_program) {
            return [];
        }

        return array_filter($this->charge_program, function ($charge) {
            return isset($charge['status']) && $charge['status'] === 'paid';
        });
    }
}
