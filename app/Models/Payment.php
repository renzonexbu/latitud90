<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_detail_id',
        'payment_gateway_id',
        'payment_option_id',
        'buy_order',
        'session_id',
        'token',
        'external_payment_id',
        'authorization_code',
        'response_code',
        'vci',
        'transaction_date',
        'accounting_date',
        'card_number',
        'card_type',
        'installments_number',
        'status',
        'gateway_response',
        'raw_notification',
        'commerce_code',
        'payment_code',
        'amount',
        'currency',
        'balance',
        'error_message',
        'email_sent',
        'bsale_document_id',
        'bsale_number',
        'bsale_token',
        'document_type'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'gateway_response' => 'array',
        'raw_notification' => 'array',
        'transaction_date' => 'datetime:America/Santiago',
        'accounting_date' => 'datetime:America/Santiago',
        'installments_number' => 'integer',
        'email_sent' => 'boolean'
    ];

    protected $appends = [
        'participant_name',
        'program_name',
        'institution_name',
        'transaction_date_formatted'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class, 'payment_option_id');
    }

    /**
     * Relación para acceder al participante a través de orderDetail -> order -> participant
     */
    public function passenger()
    {
        return $this->hasOneThrough(
            Participant::class,
            OrderDetail::class,
            'id', // Clave foránea en order_details
            'id', // Clave foránea en participants
            'order_detail_id', // Clave local en payments
            'order_id' // Clave local en order_details
        )->join('orders', 'order_details.order_id', '=', 'orders.id')
         ->join('participants', 'orders.participant_id', '=', 'participants.id')
         ->select('participants.*');
    }

    /**
     * Relación más directa para acceder al participante
     */
    public function participant()
    {
        return $this->hasOneThrough(
            Participant::class,
            Order::class,
            'id', // Clave foránea en orders
            'id', // Clave foránea en participants
            'order_id', // Clave local en payments
            'participant_id' // Clave local en orders
        );
    }

    /**
     * Relación para acceder al programa
     */
    public function program()
    {
        return $this->hasOneThrough(
            Program::class,
            Order::class,
            'id', // Clave foránea en orders
            'id', // Clave foránea en programs
            'order_id', // Clave local en payments
            'program_id' // Clave local en orders
        );
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsAuthorizedAttribute()
    {
        return $this->status === 'authorized';
    }

    public function getIsFailedAttribute()
    {
        return $this->status === 'failed';
    }

    /**
     * Accessor para transaction_date que asegura formato correcto manteniendo timezone
     */
    public function getTransactionDateFormattedAttribute()
    {
        if (!$this->transaction_date) {
            return null;
        }
        
        try {
            // Mantener el timezone America/Santiago pero formatear para JSON
            return $this->transaction_date->setTimezone('America/Santiago')->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Obtener el nombre completo del participante
     */
    public function getParticipantNameAttribute()
    {
        if ($this->order && $this->order->participant) {
            return $this->order->participant->full_name;
        }
        return 'N/A';
    }

    /**
     * Obtener el nombre del programa
     */
    public function getProgramNameAttribute()
    {
        if ($this->order && $this->order->program) {
            return $this->order->program->name;
        }
        return 'N/A';
    }

    /**
     * Obtener el nombre de la institución
     */
    public function getInstitutionNameAttribute()
    {
        if ($this->order && $this->order->program && $this->order->program->course && $this->order->program->course->institution) {
            return $this->order->program->course->institution->name;
        }
        return 'N/A';
    }

    /**
     * Determinar el tipo de documento automáticamente
     * B2 = Boleta, BC = Nota de crédito, FF = Factura, AC = Reserva
     */
    public function determineDocumentType(): string
    {
        try {
            // Si es una devolución (refund), siempre es BC (Nota de crédito)
            if ($this->paymentOption && $this->paymentOption->gateway_code === 'refund') {
                return 'BC';
            }
            
            // Si no hay paymentOption cargado, intentar cargarlo
            if (!$this->relationLoaded('paymentOption') && $this->payment_option_id) {
                $this->load('paymentOption');
                if ($this->paymentOption && $this->paymentOption->gateway_code === 'refund') {
                    return 'BC';
                }
            }

            // Cargar la relación program si no está cargada
            if (!$this->relationLoaded('program')) {
                $this->load('program');
            }

            // Si no hay programa directamente, intentar obtenerlo a través de order
            if (!$this->program && !$this->relationLoaded('order')) {
                $this->load('order.program');
            }

            $program = $this->program ?? $this->order?->program;

            // Si no hay programa, por defecto B2 (Boleta)
            if (!$program) {
                return 'B2';
            }

            $currentYear = now()->year;
            $programYear = $program->departure_date ? $program->departure_date->year : $currentYear;

            // Si la fecha de salida del programa es en el año posterior (reserva)
            if ($programYear > $currentYear) {
                return 'AC'; // Reserva
            }

            // Si es el mismo año, decidir entre Boleta (B2) o Factura (FF)
            // Por defecto usamos Boleta, pero se puede extender la lógica según necesidades
            return 'B2'; // Boleta
            
        } catch (\Exception $e) {
            // En caso de error, retornar Boleta por defecto
            \Log::warning('Error determining document type for payment', [
                'payment_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 'B2';
        }
    }

    /**
     * Boot method para configurar timezone automáticamente y tipo de documento
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            // Asegurar que las fechas se guarden en timezone de Santiago
            $payment->created_at = now()->setTimezone('America/Santiago');
            $payment->updated_at = now()->setTimezone('America/Santiago');
            
            // Determinar tipo de documento automáticamente si no está establecido
            if (!$payment->document_type) {
                $payment->document_type = $payment->determineDocumentType();
            }
        });

        static::updating(function ($payment) {
            // Asegurar que updated_at se guarde en timezone de Santiago
            $payment->updated_at = now()->setTimezone('America/Santiago');
            
            // Determinar tipo de documento automáticamente cuando:
            // 1. El estado cambia a approved/completed/paid
            // 2. Cambia el payment_option_id
            // 3. No tiene document_type asignado
            $shouldDetermineDocumentType = 
                (!$payment->document_type) || // No tiene tipo asignado
                ($payment->isDirty(['status']) && in_array($payment->status, ['approved', 'completed', 'paid'])) || // Estado cambia a aprobado
                ($payment->isDirty(['payment_option_id'])); // Cambia la opción de pago
                
            if ($shouldDetermineDocumentType) {
                $payment->document_type = $payment->determineDocumentType();
            }
        });
    }
}
