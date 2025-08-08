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
        'payment_method_id',
        'payment_mode_id',
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
        'accounting_date' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
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
