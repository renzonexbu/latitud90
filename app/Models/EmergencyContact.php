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
        'document_type',
        'document_number',
        'country',
        'birth_date',
        'address'
    ];

    protected $casts = [
        'birth_date' => 'date'
    ];

    public function participants()
    {
        return $this->belongsToMany(Participant::class, 'participants_emergency_contact')
                    ->withTimestamps();
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
} 