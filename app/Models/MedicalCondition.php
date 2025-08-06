<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalCondition extends Model
{
    use HasFactory;

    protected $table = 'medical_conditions';

    protected $fillable = [
        'description',
        'participant_id'
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
} 