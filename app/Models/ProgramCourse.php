<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProgramCourse extends Model
{
    protected $table = 'program_courses';

    protected $fillable = [
        'program_id',
        'course_id',
        'code',
        'name',
        'destination',
        'departure_date',
        'trip_price',
        'final_payment_date',
        'year',
        'seller_name',
        'sales_executive_id',
        'enable_total_payment',
        'enable_subscription_payment',
        'subscription_max_months',
        'min_days_before_departure',
        'immediate_first_charge',
        'virtualpos_plan_id',
        'discount_type',
        'discount_value',
        'itinerary_file',
        'travel_assistance_coverage',
        'equipment_list',
        'status',
        'active',
        'created_by',
    ];

    protected $appends = [
        'itinerary_file_url',
        'travel_assistance_coverage_url',
        'equipment_list_url',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'final_payment_date' => 'date',
        'trip_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'year' => 'integer',
        'subscription_max_months' => 'integer',
        'min_days_before_departure' => 'integer',
        'immediate_first_charge' => 'boolean',
        'enable_total_payment' => 'boolean',
        'enable_subscription_payment' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Relación con Program (plantilla)
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Relación con Course (grupo/curso)
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Relación con SalesExecutive
     */
    public function salesExecutive(): BelongsTo
    {
        return $this->belongsTo(SalesExecutive::class);
    }

    /**
     * Relación con el usuario que creó el plan
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con opciones de pago disponibles para este curso
     */
    public function paymentOptions(): BelongsToMany
    {
        return $this->belongsToMany(PaymentOption::class, 'program_course_payment_option')
            ->withPivot('enabled')
            ->withTimestamps();
    }

    /**
     * Get the full URL for the itinerary file
     */
    public function getItineraryFileUrlAttribute(): ?string
    {
        if (!$this->itinerary_file) return null;

        $path = str_replace('public/', '', $this->itinerary_file);
        return asset('storage/' . $path);
    }

    /**
     * Get the full URL for the travel assistance coverage file
     */
    public function getTravelAssistanceCoverageUrlAttribute(): ?string
    {
        if (!$this->travel_assistance_coverage) return null;

        $path = str_replace('public/', '', $this->travel_assistance_coverage);
        return asset('storage/' . $path);
    }

    /**
     * Get the full URL for the equipment list file
     */
    public function getEquipmentListUrlAttribute(): ?string
    {
        if (!$this->equipment_list) return null;

        $path = str_replace('public/', '', $this->equipment_list);
        return asset('storage/' . $path);
    }
}
