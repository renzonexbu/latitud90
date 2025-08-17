<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantProgram extends Model
{
    use HasFactory;

    protected $table = 'participant_program';

    protected $fillable = [
        'participant_id',
        'program_id',
        'enrollment_code',
        'individual_price',
        'status'
    ];

    protected $casts = [
        'individual_price' => 'decimal:2'
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function discounts()
    {
        return $this->hasMany(ParticipantProgramDiscount::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function programOrders()
    {
        return $this->hasMany(Order::class, 'participant_program_id');
    }
}
