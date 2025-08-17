<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PaymentOption;

class OrderDetail extends Model
{
    use HasFactory;

    protected $table = 'orders_detail';

    protected $fillable = [
        'order_id',
        'payment_option_id',
        'payment_gateway_id',
        'name',
        'email',
        'country',
        'region',
        'city',
        'code_phone',
        'phone',
        'document_type',
        'document_number',
        'billing_address',
        'billing_city',
        'billing_country',
        'billing_postal_code',
        'terms_accepted',
        'marketing_accepted',
        'terms_accepted_confirmation',
        'installment_number',
        'base_amount',
        'discount_amount',
        'amount',
        'due_date',
        'is_paid',
        'paid_at',
        'status',
        'transaction_id',
        'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'is_paid' => 'boolean',
        'terms_accepted' => 'boolean',
        'marketing_accepted' => 'boolean',
        'terms_accepted_confirmation' => 'boolean',
        'gateway_response' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentOption::class, 'payment_option_id');
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region');
    }

    public function city()
    {
        return $this->belongsTo(Comune::class, 'city');
    }

    public function documentType()
    {
        return $this->belongsTo(Document::class, 'document_type');
    }
}
