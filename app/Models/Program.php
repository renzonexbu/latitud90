<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'destination',
        'trip_description',
        'images_folder',
        'pillars',
        'itinerary_description',
        'itinerary_file',
        'travel_assistance_coverage',
        'equipment_list',
        'created_by',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = [
        'itinerary_file_url',
        'travel_assistance_coverage_url',
        'equipment_list_url',
        'images'
    ];

    /**
     * Relación con ProgramCourse (planes específicos)
     */
    public function programCourses()
    {
        return $this->hasMany(ProgramCourse::class);
    }

    /**
     * Relación con el usuario que creó la plantilla
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->belongsToMany(Participant::class, 'participant_program')
                    ->withPivot('enrollment_code', 'individual_price', 'status', 'created_at', 'updated_at')
                    ->withTimestamps();
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

    public function paymentOptions()
    {
        return $this->belongsToMany(PaymentOption::class, 'program_payment_option')
                    ->withPivot('enabled')
                    ->withTimestamps();
    }

    public function participantPrograms()
    {
        return $this->hasMany(ParticipantProgram::class);
    }

    public function participantDiscounts()
    {
        return $this->hasManyThrough(ParticipantProgramDiscount::class, ParticipantProgram::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function courseOrders()
    {
        return $this->hasMany(Order::class, 'program_id')->whereNotNull('course_id');
    }

    public function getActiveParticipantsAttribute()
    {
        return $this->participants()->where('status', 'confirmed')->count();
    }

    public function getTotalRevenueAttribute()
    {
        return $this->participants()->sum('individual_price');
    }

    /**
     * Get the full URL for the itinerary file
     */
    public function getItineraryFileUrlAttribute()
    {
        if (!$this->itinerary_file) return null;
        
        // Remover 'public/' del inicio si existe
        $path = str_replace('public/', '', $this->itinerary_file);
        return asset('storage/' . $path);
    }

    /**
     * Get the full URL for the travel assistance coverage file
     */
    public function getTravelAssistanceCoverageUrlAttribute()
    {
        if (!$this->travel_assistance_coverage) return null;
        
        // Remover 'public/' del inicio si existe
        $path = str_replace('public/', '', $this->travel_assistance_coverage);
        return asset('storage/' . $path);
    }

    /**
     * Get the full URL for the equipment list file
     */
    public function getEquipmentListUrlAttribute()
    {
        if (!$this->equipment_list) return null;
        
        // Remover 'public/' del inicio si existe
        $path = str_replace('public/', '', $this->equipment_list);
        return asset('storage/' . $path);
    }

    /**
     * Get all images from the images folder
     */
    public function getImagesAttribute()
    {
        if (!$this->images_folder) {
            return [];
        }

        // Construir la ruta correcta para las imágenes (están en storage/app/public)
        $relativePath = str_replace('public/', '', $this->images_folder);
        $path = storage_path('app/public/' . $relativePath);
        
        
        if (!is_dir($path)) {
            Log::warning('La carpeta de imágenes no existe', [
                'program_id' => $this->id,
                'path' => $path
            ]);
            return [];
        }

        $files = glob($path . '/*');
        
        $images = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            $images[] = [
                'filename' => $filename,
                'url' => asset('storage/' . $relativePath . '/' . $filename),
                'path' => $relativePath . '/' . $filename
            ];
        }
        return $images;
    }

}
