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