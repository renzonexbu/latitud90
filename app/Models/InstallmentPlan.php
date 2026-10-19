<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'program_subscription_id',
        'program_id',
        'participant_id',
        'total_amount',
        'total_installments',
        'payment_type',
        'status',
        'notes',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_installments' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relaciones
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * program_id apunta a program_courses.id (arquitectura nueva).
     * Esta relación devuelve el ProgramCourse correcto.
     */
    public function programCourse(): BelongsTo
    {
        return $this->belongsTo(ProgramCourse::class, 'program_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function programSubscription(): BelongsTo
    {
        return $this->belongsTo(ProgramSubscription::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    // Métodos útiles
    public function getPaidInstallmentsCountAttribute(): int
    {
        return $this->installments()->where('status', 'paid')->count();
    }

    public function getPendingInstallmentsCountAttribute(): int
    {
        return $this->installments()->where('status', 'pending')->count();
    }

    public function getOverdueInstallmentsCountAttribute(): int
    {
        return $this->installments()->where('status', 'overdue')->count();
    }

    public function getNextPendingInstallmentAttribute()
    {
        return $this->installments()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();
    }

    public function isCompleted(): bool
    {
        return $this->getPaidInstallmentsCountAttribute() === $this->total_installments;
    }

    /**
     * Cierra los planes de cuotas de una suscripcion dada de baja.
     *
     * Un plan que queda en 'active' tras la baja deja al portal ofreciendo la
     * siguiente cuota PAT en vez del saldo por pagar. Lo hacia la baja desde el
     * admin pero no la que llega por sincronizacion con VirtualPos, asi que la
     * logica vive aca y la usan los dos (Liliam 2026-10-19).
     */
    public static function closeForSubscription(ProgramSubscription $subscription): int
    {
        $plans = static::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($subscription) {
                $q->where('program_subscription_id', $subscription->id)
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'active')
                         ->whereNull('program_subscription_id');
                  });
            })
            ->get();

        foreach ($plans as $plan) {
            $plan->update(['status' => 'cancelled']);
            $plan->installments()
                ->whereIn('status', ['pending', 'overdue'])
                ->update(['status' => 'cancelled']);
        }

        return $plans->count();
    }
}
