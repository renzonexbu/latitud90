<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'institution_id',
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
        'year',
        'final_payment_date',
        'seller_name',
        'sales_executive_id',
        'enable_total_payment',
        'enable_lat90_payment',
        'lat90_max_installments',
        // Campos de descuento
        'discount_type',
        'discount_value',
        'course_id',
        'created_by',
        'active'
    ];

    protected $casts = [
        'departure_date' => 'date',
        'final_payment_date' => 'date',
        'trip_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'enable_total_payment' => 'boolean',
        'enable_lat90_payment' => 'boolean',
        'active' => 'boolean'
    ];

    protected $appends = [
        'itinerary_file_url',
        'travel_assistance_coverage_url',
        'equipment_list_url',
        'images'
    ];

    // Relaciones para métodos de pago
    public function totalPaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'total_payment_method_id');
    }

    public function lat90PaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'lat90_payment_method_id');
    }

    // Relaciones existentes (mantener compatibilidad)
    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        // Esta relación no se usa directamente, se accede a través de course->participants()
        return $this->course->participants() ?? collect();
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
            Log::info('No hay images_folder para el programa', ['program_id' => $this->id]);
            return [];
        }

        // Construir la ruta correcta para las imágenes (están en storage/app/public)
        $relativePath = str_replace('public/', '', $this->images_folder);
        $path = storage_path('app/public/' . $relativePath);
        
        Log::info('Buscando imágenes en ruta', [
            'program_id' => $this->id,
            'images_folder' => $this->images_folder,
            'relative_path' => $relativePath,
            'full_path' => $path,
            'path_exists' => is_dir($path),
            'storage_path' => storage_path('app/public'),
            'public_path' => public_path()
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
                'url' => asset('storage/' . $relativePath . '/' . $filename),
                'path' => $relativePath . '/' . $filename
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
