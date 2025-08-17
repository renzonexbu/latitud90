<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantCourse extends Model
{
    use HasFactory;

    protected $table = 'participant_course';

    protected $fillable = [
        'participant_id',
        'course_id',
        'education_level',
        'year',
        'grade',
        'shift',
        'status',
        'individual_price',
        'price_adjustments',
        'adjustment_reason'
    ];

    protected $casts = [
        'individual_price' => 'decimal:2',
        'price_adjustments' => 'decimal:2'
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'participant_id')->where('course_id', $this->course_id);
    }
}
