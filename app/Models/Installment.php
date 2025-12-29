<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'installment_plan_id',
        'installment_number',
        'virtualpos_charge_id',
        'amount',
        'due_date',
        'status',
        'is_paid',
        'paid_at',
        'payment_order_id',
        'payment_order_detail_id',
        'payment_id',
        'payment_source', // Fuente/método de pago de la cuota
        'notes',
        'adjusted_at',
        'adjustment_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'adjusted_at' => 'datetime',
    ];

    // Relaciones
    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'payment_order_id');
    }

    public function paymentOrderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class, 'payment_order_detail_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // Métodos útiles
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function markAsPaid(int $orderId, int $orderDetailId, int $paymentId): void
    {
        Log::info('Installment: markAsPaid called', [
            'installment_id' => $this->id,
            'installment_number' => $this->installment_number,
            'current_status' => $this->status,
            'order_id' => $orderId,
            'order_detail_id' => $orderDetailId,
            'payment_id' => $paymentId,
        ]);

        $this->update([
            'status' => 'paid',
            'is_paid' => true,
            'paid_at' => now(),
            'payment_order_id' => $orderId,
            'payment_order_detail_id' => $orderDetailId,
            'payment_id' => $paymentId,
            'payment_source' => 'subscription', // Marcar como pago de suscripción automática
        ]);

        Log::info('Installment: markAsPaid completed', [
            'installment_id' => $this->id,
            'new_status' => $this->status,
            'paid_at' => $this->paid_at,
        ]);
    }
}
