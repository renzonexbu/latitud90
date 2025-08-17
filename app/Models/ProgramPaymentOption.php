<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramPaymentOption extends Model
{
    use HasFactory;

    protected $table = 'program_payment_option';

    protected $fillable = [
        'program_id',
        'payment_option_id',
        'enabled'
    ];

    protected $casts = [
        'enabled' => 'boolean'
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }
}
