<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_detail_id',
        'payment_gateway_id',
        'payment_option_id',
        'buy_order',
        'session_id',
        'token',
        'external_payment_id',
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
        'raw_notification',
        'commerce_code',
        'amount',
        'currency',
        'balance',
        'error_message'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'gateway_response' => 'array',
        'raw_notification' => 'array',
        'transaction_date' => 'datetime',
        'accounting_date' => 'datetime',
        'installments_number' => 'integer'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class, 'payment_option_id');
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
