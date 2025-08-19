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
        'grade',
        'final_payment_date',
        'seller_name',
        'sales_executive_id',
        'enable_total_payment',
        'enable_lat90_payment',
        'lat90_max_installments',
        'total_payment_method_id',
        'lat90_payment_method_id',
        // Campos de descuento
        'discount_type',
        'discount_value',
        'course_id',
        'created_by',
        'active',
        'status'
    ];

    protected $casts = [
        'departure_date' => 'date',
        'final_payment_date' => 'date',
        'trip_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'enable_total_payment' => 'boolean',
        'enable_lat90_payment' => 'boolean',
        'active' => 'boolean',
        'status' => 'string'
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

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function salesExecutive()
    {
        return $this->belongsTo(SalesExecutive::class);
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
            Log::info('No hay images_folder para el programa', ['program_id' => $this->id]);
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

    /**
     * Scope para filtrar por status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para programas en reserva
     */
    public function scopeReserva($query)
    {
        return $query->where('status', 'reserva');
    }

    /**
     * Scope para programas realizados
     */
    public function scopeRealizado($query)
    {
        return $query->where('status', 'realizado');
    }

    /**
     * Obtener el label del status
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'reserva' => 'Reserva',
            'realizado' => 'Realizado'
        ];

        return $labels[$this->status] ?? null;
    }

    /**
     * Obtener la clase CSS del status
     */
    public function getStatusClassAttribute()
    {
        $classes = [
            'reserva' => 'bg-yellow-100 text-yellow-800',
            'realizado' => 'bg-blue-100 text-blue-800'
        ];

        return $classes[$this->status] ?? null;
    }
}
