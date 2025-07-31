<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalCondition extends Model
{
    use HasFactory;

    protected $table = 'medical_conditions';

    protected $fillable = [
        'description'
    ];

    public function participants()
    {
        return $this->belongsToMany(Participant::class, 'participants_medical_conditions')
                    ->withTimestamps();
    }
} 