<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'program_id',
        'total_amount',
        'discount',
        'final_amount',
        'total_installments',
        'payment_type',
        'status',
        'notes',
        'order_number'
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

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function paidOrderDetails()
    {
        return $this->hasMany(OrderDetail::class)->where('is_paid', true);
    }

    public function pendingOrderDetails()
    {
        return $this->hasMany(OrderDetail::class)->where('is_paid', false);
    }

    public function isFullyPaid()
    {
        return $this->orderDetails()->where('is_paid', false)->count() === 0;
    }

    public function getPaidAmountAttribute()
    {
        return $this->paidOrderDetails()->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->final_amount - $this->paid_amount;
    }
} 