<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    use HasFactory;

    protected $table = 'emergency_contact';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'code_phone',
        'phone',
        'country',
        'birth_date',
        'address',
        'relationship',
        'participant_id'
    ];

    protected $casts = [
        'birth_date' => 'date'
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
} 