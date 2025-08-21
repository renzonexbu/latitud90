<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_last_name',
        'second_last_name',
        'first_name',
        'second_name',
        'email',
        'code_phone',
        'phone',
        'document_type',
        'document_number',
        'country',
        'birth_date',
        'nationality',
        'gender',
        'address',
        'dietary_restrictions',
        'intolerances',
        'allergies',
        'status',
        'registration_date'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'first_name',
        'last_name'
    ];

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function medicalConditions()
    {
        return $this->hasMany(MedicalCondition::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }

    public function documentType()
    {
        return $this->belongsTo(Document::class, 'document_type');
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

    public function courseOrders()
    {
        return $this->hasMany(Order::class, 'participant_id')->whereNotNull('course_id');
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'participant_program')
                    ->withPivot([
                        'enrollment_code',
                        'individual_price',
                        'status'
                    ])
                    ->withTimestamps();
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Order::class);
    }

    public function programDiscounts()
    {
        return $this->hasManyThrough(ParticipantProgramDiscount::class, ParticipantProgram::class);
    }

    public function getFullNameAttribute()
    {
        $parts = [];
        
        // Construir nombre completo usando el orden correcto: nombres primero, luego apellidos
        if ($this->first_name) {
            $parts[] = $this->capitalizeWords($this->first_name);
        }
        if ($this->second_name) {
            $parts[] = $this->capitalizeWords($this->second_name);
        }
        if ($this->first_last_name) {
            $parts[] = $this->capitalizeWords($this->first_last_name);
        }
        if ($this->second_last_name) {
            $parts[] = $this->capitalizeWords($this->second_last_name);
        }
        
        return !empty($parts) ? implode(' ', $parts) : 'N/A';
    }

    /**
     * Accessor para first_name - compatibilidad con frontend
     */
    public function getFirstNameAttribute()
    {
        $names = [];
        if ($this->attributes['first_name']) {
            $names[] = $this->capitalizeWords($this->attributes['first_name']);
        }
        if ($this->attributes['second_name']) {
            $names[] = $this->capitalizeWords($this->attributes['second_name']);
        }
        return implode(' ', $names);
    }

    /**
     * Accessor para last_name - compatibilidad con frontend
     */
    public function getLastNameAttribute()
    {
        $lastNames = [];
        if ($this->attributes['first_last_name']) {
            $lastNames[] = $this->capitalizeWords($this->attributes['first_last_name']);
        }
        if ($this->attributes['second_last_name']) {
            $lastNames[] = $this->capitalizeWords($this->attributes['second_last_name']);
        }
        return implode(' ', $lastNames);
    }

    /**
     * Aplicar CapitalCase a un string
     */
    private function capitalizeWords(string $text): string
    {
        return ucwords(strtolower(trim($text)));
    }

    // Totales deben calcularse desde el pivote y pagos; se eliminaron campos locales de pago
} 