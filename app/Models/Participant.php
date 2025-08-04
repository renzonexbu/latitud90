<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
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
        'registration_date',
        'individual_price',
        'price_adjustments',
        'adjustment_reason'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'datetime',
        'individual_price' => 'decimal:2',
        'price_adjustments' => 'decimal:2'
    ];

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function medicalConditions()
    {
        return $this->hasMany(MedicalCondition::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
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

    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getPendingAmountAttribute()
    {
        return ($this->individual_price + $this->price_adjustments) - $this->total_paid;
    }
} 