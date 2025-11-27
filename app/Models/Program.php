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
        'created_by',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = [
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
