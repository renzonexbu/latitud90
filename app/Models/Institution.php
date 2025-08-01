<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'address',
        'city',
        'region',
        'phone',
        'email',
        'website',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the participants that belong to this institution.
     */
    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Scope a query to only include active institutions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full name of the institution.
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }
} 