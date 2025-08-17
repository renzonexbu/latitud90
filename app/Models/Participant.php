<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
    use HasFactory, SoftDeletes;

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
        'address',
        'dietary_restrictions',
        'medical_conditions',
        'status',
        'registration_date'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'datetime',
    ];

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function medicalConditions()
    {
        return $this->hasMany(MedicalCondition::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'participant_course')
                    ->withPivot([
                        'education_level',
                        'year',
                        'grade',
                        'shift',
                        'status',
                        'individual_price',
                        'price_adjustments',
                        'adjustment_reason'
                    ])
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Order::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Totales deben calcularse desde el pivote y pagos; se eliminaron campos locales de pago
} 