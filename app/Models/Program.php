<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'destination',
        'departure_date',
        'trip_description',
        'images_folder',
        'pillars',
        'itinerary_description',
        'itinerary_file',
        'travel_assistance_coverage',
        'equipment_list',
        'trip_price',
        'final_payment_date',
        'seller_name',
        'payment_mode_id',
        'active'
    ];

    protected $casts = [
        'departure_date' => 'date',
        'final_payment_date' => 'date',
        'trip_price' => 'decimal:2',
        'active' => 'boolean'
    ];

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
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
