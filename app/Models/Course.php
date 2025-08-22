<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'education_level',
        'grade',
        'year',
        'course_number',
        'course_name',
        'contact_email',
        'contact_phone',
        'program_id',
        'end_date',
        'status',
        'students_file_path',
        'students_file_name',
        'total_students',
        'collected_amount',
        'target_amount',
        'created_by'
    ];

    protected $casts = [
        'end_date' => 'date',
        'course_number' => 'integer',
        'collected_amount' => 'decimal:2',
        'target_amount' => 'decimal:2'
    ];

    protected $appends = [
        'course_display',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function participants()
    {
        return $this->belongsToMany(Participant::class, 'participant_course')
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

    public function participantPrograms()
    {
        return $this->hasMany(ParticipantProgram::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFullNameAttribute()
    {
        $courseDisplay = $this->course_display;
        return "{$this->institution->name} - {$courseDisplay}";
    }

    public function getCourseDisplayAttribute(): string
    {
        $level = $this->education_level;
        $num = $this->course_number;
        $grade = $this->grade;
        $name = $this->course_name;

        // Preescolar: solo Kínder (sin Prekínder)
        if ($level === 'preescolar') {
            $label = $name ?: 'Kínder';
            // Agregar grado si existe
            if ($grade) {
                $label .= ' ' . $grade;
            }
            return $label;
        }

        // Universitaria: libre
        if ($level === 'universitaria') {
            $label = $name ?: 'Universitaria';
            // Agregar grado si existe
            if ($grade) {
                $label .= ' ' . $grade;
            }
            return $label;
        }

        // Básica y Media con número
        $suffix = 'º';
        if ($level === 'basica') {
            $label = $num ? ($num . $suffix . ' Básico') : ($name ?: 'Básica');
            // Agregar grado si existe
            if ($grade) {
                $label .= ' ' . $grade;
            }
            return $label;
        }
        if ($level === 'media') {
            $label = $num ? ($num . $suffix . ' Medio') : ($name ?: 'Media');
            // Agregar grado si existe
            if ($grade) {
                $label .= ' ' . $grade;
            }
            return $label;
        }

        $label = $name ?: ucfirst($level);
        // Agregar grado si existe
        if ($grade) {
            $label .= ' ' . $grade;
        }
        return $label;
    }

    public function getPaymentPercentageAttribute()
    {
        if (!$this->program || !$this->program->trip_price) {
            return null; // Retorna null para mostrar "---"
        }
        
        $collectedAmount = $this->collected_amount ?? 0;
        $totalPrice = $this->program->trip_price;
        
        if ($totalPrice <= 0) {
            return null; // Retorna null para mostrar "---"
        }
        
        return round(($collectedAmount / $totalPrice) * 100, 0);
    }

    public function getPaymentPercentageTextAttribute()
    {
        $percentage = $this->payment_percentage;
        
        if ($percentage === null) {
            return '---'; // Solo cuando no hay programa asociado
        }
        
        return $percentage . '%'; // Incluye 0% cuando hay programa pero no hay monto recolectado
    }
} 