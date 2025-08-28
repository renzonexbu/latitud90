<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantProgramDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_program_id',
        'percent',
        'amount',
        'discount_type',
        'comment',
        'approved_by'
    ];

    protected $casts = [
        'percent' => 'decimal:2',
        'amount' => 'decimal:2'
    ];

    public function participantProgram()
    {
        return $this->belongsTo(ParticipantProgram::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
