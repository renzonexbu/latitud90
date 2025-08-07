<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $table = 'orders_detail';

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'payment_mode_id',
        'payment_gateway_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'document_type_id',
        'document_number',
        'billing_address',
        'billing_city',
        'billing_country',
        'billing_postal_code',
        'terms_accepted',
        'marketing_accepted',
        'installment_number',
        'amount',
        'due_date',
        'is_paid',
        'paid_at',
        'status',
        'transaction_id',
        'gateway_response'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'terms_accepted' => 'boolean',
        'marketing_accepted' => 'boolean',
        'installment_number' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function documentType()
    {
        return $this->belongsTo(Document::class, 'document_type_id')->withDefault();
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getIsOverdueAttribute()
    {
        return !$this->is_paid && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return $this->due_date->diffInDays(now());
    }

    public function markAsPaid($transactionId = null, $gatewayResponse = null)
    {
        $this->update([
            'is_paid' => true,
            'paid_at' => now(),
            'status' => 'paid',
            'transaction_id' => $transactionId,
            'gateway_response' => $gatewayResponse
        ]);

        // Verificar si toda la orden está pagada
        if ($this->order->isFullyPaid()) {
            $this->order->update(['status' => 'paid']);
        }
    }
}
