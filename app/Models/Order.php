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
        'order_number',
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
} 