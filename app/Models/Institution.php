<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'razon_social',
        'rut',
        'type',
        'address',
        'phone',
        'email',
        'website',
        'active',
        'created_by'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the participants that belong to this institution.
     */
    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Get the courses that belong to this institution.
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get the programs that belong to this institution.
     */
    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active institutions.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the full name of the institution.
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }
} 