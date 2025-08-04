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
        'year',
        'grade',
        'shift',
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
        'collected_amount' => 'decimal:2',
        'target_amount' => 'decimal:2'
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
        return $this->hasMany(Participant::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFullNameAttribute()
    {
        return "{$this->institution->name} - {$this->education_level} {$this->grade}° {$this->shift}";
    }
} 