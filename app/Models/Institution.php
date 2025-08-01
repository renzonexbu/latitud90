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
        'phone',
        'email',
        'website',
        'active',
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