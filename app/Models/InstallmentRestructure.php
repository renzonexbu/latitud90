<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentRestructure extends Model
{
    use HasFactory;

    protected $table = 'installment_restructure';

    protected $fillable = [
        'installment_plan_id',
        'user_id',
        'user_name',
        'user_email',
        'participant_id',
        'program_id',
        'old_total_installments',
        'old_total_amount',
        'old_paid_installments',
        'old_pending_installments',
        'old_paid_amount',
        'old_remaining_balance',
        'new_total_installments',
        'new_total_amount',
        'new_paid_installments',
        'new_pending_installments',
        'new_paid_amount',
        'new_remaining_balance',
        'reason',
        'installments_deleted',
        'installments_created',
        'restructure_type',
        'old_installments_data',
        'new_installments_data',
        'notes',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_installments_data' => 'array',
        'new_installments_data' => 'array',
        'old_total_amount' => 'decimal:2',
        'old_paid_amount' => 'decimal:2',
        'old_remaining_balance' => 'decimal:2',
        'new_total_amount' => 'decimal:2',
        'new_paid_amount' => 'decimal:2',
        'new_remaining_balance' => 'decimal:2',
    ];

    // Relaciones
    public function installmentPlan()
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    // Scopes para filtros
    public function scopeByType($query, $type)
    {
        return $query->where('restructure_type', $type);
    }

    public function scopeByParticipant($query, $participantId)
    {
        return $query->where('participant_id', $participantId);
    }

    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
