<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

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
        'registration_date',
        'is_active',
        'last_no_payment_reminder_sent_at'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'datetime',
        'is_active' => 'boolean',
        'last_no_payment_reminder_sent_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $appends = [
        'full_name',
        'formatted_first_name',
        'formatted_last_name'
    ];

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /**
     * Relacion con guardian users (apoderados)
     */
    public function guardianUsers()
    {
        return $this->belongsToMany(
            GuardianUser::class,
            'guardian_user_participant',
            'participant_id',
            'guardian_user_id'
        )
        ->withPivot(['can_pay'])
        ->withTimestamps();
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

    /**
     * Relación con ProgramCourse (instancias específicas de programas)
     * participant_program.program_id -> program_courses.id
     */
    public function programCourses()
    {
        return $this->belongsToMany(ProgramCourse::class, 'participant_program', 'participant_id', 'program_id')
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

    public function statusHistory()
    {
        return $this->hasMany(ParticipantStatusHistory::class)->orderBy('created_at', 'desc');
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
     * Accessor para formatted_first_name - compatibilidad con frontend
     */
    public function getFormattedFirstNameAttribute()
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
     * Accessor para formatted_last_name - compatibilidad con frontend
     */
    public function getFormattedLastNameAttribute()
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

    /**
     * Obtener etiqueta amigable para el estado del participante
     *
     * @param string|null $status El estado a traducir (si es null, usa el estado del participante)
     * @return string
     */
    public function getStatusLabel(?string $status = null): string
    {
        $statusValue = $status ?? $this->status;

        return match($statusValue) {
            'pending_payment' => 'Pendiente de Pago',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            default => ucfirst(str_replace('_', ' ', $statusValue ?? 'N/A'))
        };
    }

    /**
     * Obtener etiqueta amigable para el estado del programa del participante
     *
     * @param string $status
     * @return string
     */
    public static function getProgramStatusLabel(string $status): string
    {
        return match($status) {
            'pending_payment' => 'Pendiente de Pago',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Liberado',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }

    // Totales deben calcularse desde el pivote y pagos; se eliminaron campos locales de pago
} 