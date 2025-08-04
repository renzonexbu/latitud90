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
        'course_id',
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

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function participants()
    {
        return $this->hasManyThrough(Participant::class, Course::class, 'program_id', 'course_id', 'id', 'id');
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

    /**
     * Get the full URL for the itinerary file
     */
    public function getItineraryFileUrlAttribute()
    {
        return $this->itinerary_file ? asset('storage/' . $this->itinerary_file) : null;
    }

    /**
     * Get the full URL for the travel assistance coverage file
     */
    public function getTravelAssistanceCoverageUrlAttribute()
    {
        return $this->travel_assistance_coverage ? asset('storage/' . $this->travel_assistance_coverage) : null;
    }

    /**
     * Get the full URL for the equipment list file
     */
    public function getEquipmentListUrlAttribute()
    {
        return $this->equipment_list ? asset('storage/' . $this->equipment_list) : null;
    }

    /**
     * Get all images from the images folder
     */
    public function getImagesAttribute()
    {
        if (!$this->images_folder) {
            Log::info('No hay images_folder para el programa', ['program_id' => $this->id]);
            return [];
        }

        $path = storage_path('app/public/' . $this->images_folder);
        Log::info('Buscando imágenes en ruta', [
            'program_id' => $this->id,
            'images_folder' => $this->images_folder,
            'full_path' => $path,
            'path_exists' => is_dir($path)
        ]);
        
        if (!is_dir($path)) {
            Log::warning('La carpeta de imágenes no existe', [
                'program_id' => $this->id,
                'path' => $path
            ]);
            return [];
        }

        $files = glob($path . '/*');
        Log::info('Archivos encontrados en la carpeta', [
            'program_id' => $this->id,
            'files_count' => count($files),
            'files' => $files
        ]);
        
        $images = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            $images[] = [
                'filename' => $filename,
                'url' => asset('storage/' . $this->images_folder . '/' . $filename),
                'path' => $this->images_folder . '/' . $filename
            ];
        }

        Log::info('Imágenes procesadas', [
            'program_id' => $this->id,
            'images_count' => count($images),
            'images' => $images
        ]);

        return $images;
    }
}
