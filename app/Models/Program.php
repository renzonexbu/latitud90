<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'service_type_id',
        'destination',
        'departure_date',
        'return_date',
        'duration_days',
        'capacity',
        'base_price',
        'includes',
        'excludes',
        'requirements',
        'itinerary',
        'image_url',
        'active'
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'base_price' => 'decimal:2',
        'active' => 'boolean'
    ];

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function commercialExecutive()
    {
        return $this->belongsTo(User::class, 'commercial_executive_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'programs_features')
                    ->withPivot('type')
                    ->withTimestamps();
    }

    public function requirements()
    {
        return $this->belongsToMany(Requirement::class, 'programs_requirements')
                    ->withPivot('type')
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, Participant::class);
    }

    public function getActiveParticipantsAttribute()
    {
        return $this->participants()->where('status', 'confirmed')->count();
    }

    public function getTotalRevenueAttribute()
    {
        return $this->participants()->sum('individual_price');
    }
}
