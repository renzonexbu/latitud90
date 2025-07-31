<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'payment_method_id',
        'payment_mode_id',
        'buyer_first_name',
        'buyer_last_name',
        'buyer_email',
        'buyer_phone',
        'buyer_document_type',
        'buyer_document_number',
        'billing_address',
        'billing_city',
        'billing_country',
        'billing_postal_code',
        'price',
        'discount',
        'total',
        'status',
        'notes',
        'paid_at',
        'order_number'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime'
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getBuyerFullNameAttribute()
    {
        return $this->buyer_first_name . ' ' . $this->buyer_last_name;
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === 'cancelled';
    }
} 