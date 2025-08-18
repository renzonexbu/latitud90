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

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
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
}
