<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'buy_order',
        'session_id',
        'token',
        'authorization_code',
        'response_code',
        'vci',
        'transaction_date',
        'accounting_date',
        'card_number',
        'card_type',
        'installments_number',
        'status',
        'gateway_response',
        'commerce_code',
        'amount',
        'balance',
        'error_message'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'gateway_response' => 'json',
        'transaction_date' => 'datetime',
        'accounting_date' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsAuthorizedAttribute()
    {
        return $this->status === 'authorized';
    }

    public function getIsFailedAttribute()
    {
        return $this->status === 'failed';
    }

    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount, 0, ',', '.');
    }
}
