<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'program_id',
        'course_id',
        'participant_program_id',
        'total_amount',
        'discount',
        'final_amount',
        'total_installments',
        'payment_type',
        'status',
        'notes',
        'order_number',
        'session_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'total_installments' => 'integer',
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function participantProgram()
    {
        return $this->belongsTo(ParticipantProgram::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function installmentPlan()
    {
        return $this->hasOne(InstallmentPlan::class);
    }

    /**
     * Recalcula y persiste el estado de la orden en base al progreso de pago.
     * Reglas:
     * - payment_type === 'total': pagada si el único detalle está pagado; si no, pending
     * - payment_type === 'monthly':
     *   - 0 cuotas pagadas => pending
     *   - 1..(n-1) cuotas pagadas => processing
     *   - n cuotas pagadas => paid
     */
    public function refreshStatus(): void
    {
        $this->loadMissing('orderDetails');

        // Sin detalles, mantener estado actual para evitar falsos positivos
        if ($this->orderDetails->isEmpty()) {
            return;
        }

        if ($this->payment_type === 'total') {
            $allPaid = $this->orderDetails->every(function ($detail) {
                return (bool) ($detail->is_paid ?? false);
            });
            $this->status = $allPaid ? 'paid' : 'pending';
            $this->save();
            return;
        }

        // Mensual
        $paidCount = $this->orderDetails->where('is_paid', true)->count();
        $totalInstallments = (int) ($this->total_installments ?? $this->orderDetails->count());

        if ($paidCount <= 0) {
            $this->status = 'pending';
        } elseif ($paidCount < $totalInstallments) {
            $this->status = 'processing';
        } else {
            $this->status = 'paid';
        }

        $this->save();
    }
} 