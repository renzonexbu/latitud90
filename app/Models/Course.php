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
        'status',
        'students_file_path',
        'students_file_name',
        'total_students',
        'created_by'
    ];

    protected $casts = [
        'course_number' => 'integer',
        'total_students' => 'integer',
    ];

    protected $appends = [
        'course_display',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Relación con ProgramCourse (planes específicos)
     */
    public function programCourses()
    {
        return $this->hasMany(ProgramCourse::class);
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

} 