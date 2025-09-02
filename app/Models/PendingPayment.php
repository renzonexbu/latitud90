<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_detail_id',
        'gateway_type',
        'gateway_data',
        'status',
        'error_message',
        'attempts',
        'last_attempt_at',
        'confirmed_at',
    ];

    protected $casts = [
        'gateway_data' => 'array',
        'last_attempt_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    public function incrementAttempts()
    {
        $this->increment('attempts');
        $this->update(['last_attempt_at' => now()]);
    }

    public function markAsConfirmed()
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function isMaxAttemptsReached()
    {
        return $this->attempts >= 5;
    }
}
