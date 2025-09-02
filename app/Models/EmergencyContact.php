<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    use HasFactory;

    protected $table = 'emergency_contact';

    protected $fillable = [
        'name',
        'email',
        'document_type',
        'document_number',
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

    public function country()
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }
} 