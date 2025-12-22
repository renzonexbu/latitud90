<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualPosPlan extends Model
{
    protected $table = 'virtualpos_plans';

    protected $fillable = [
        'virtualpos_plan_id',
        'program_course_id',
        'participant_id',
        'code',
        'name',
        'description',
        'trip_price',
        'original_price',
        'discount_amount',
        'discount_type',
        'discount_reason',
        'monthly_amount',
        'max_installments',
        'immediate_first_charge',
        'currency',
        'frequency_type',
        'plan_type',
        'is_active',
        'api_response',
        'created_by',
    ];

    protected $casts = [
        'trip_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'max_installments' => 'integer',
        'immediate_first_charge' => 'boolean',
        'is_active' => 'boolean',
        'api_response' => 'array',
    ];

    /**
     * ProgramCourse asociado a este plan
     */
    public function programCourse(): BelongsTo
    {
        return $this->belongsTo(ProgramCourse::class);
    }

    /**
     * Participante asociado (solo para planes personalizados)
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Usuario que creó el plan
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Suscripciones creadas con este plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProgramSubscription::class, 'virtualpos_plan_id', 'virtualpos_plan_id');
    }

    /**
     * Verificar si es un plan personalizado (tiene participante)
     */
    public function isPersonalized(): bool
    {
        return !is_null($this->participant_id);
    }

    /**
     * Verificar si es un plan general (sin participante)
     */
    public function isGeneral(): bool
    {
        return is_null($this->participant_id);
    }

    /**
     * Buscar plan por su ID de VirtualPos
     */
    public static function findByVirtualPosId(string $virtualposPlanId): ?self
    {
        return static::where('virtualpos_plan_id', $virtualposPlanId)->first();
    }

    /**
     * Buscar plan personalizado para un participante en un program_course
     */
    public static function findForParticipant(int $participantId, int $programCourseId): ?self
    {
        return static::where('participant_id', $participantId)
            ->where('program_course_id', $programCourseId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Obtener el plan a usar para un participante (personalizado si existe, o el general)
     */
    public static function getPlanForParticipant(int $participantId, int $programCourseId): ?self
    {
        // Primero buscar plan personalizado
        $personalizedPlan = static::findForParticipant($participantId, $programCourseId);

        if ($personalizedPlan) {
            return $personalizedPlan;
        }

        // Si no hay personalizado, buscar el plan general del program_course
        return static::where('program_course_id', $programCourseId)
            ->whereNull('participant_id')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Scope para planes activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para planes generales (sin participante)
     */
    public function scopeGeneral($query)
    {
        return $query->whereNull('participant_id');
    }

    /**
     * Scope para planes personalizados (con participante)
     */
    public function scopePersonalized($query)
    {
        return $query->whereNotNull('participant_id');
    }
}
