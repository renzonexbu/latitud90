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
        'amount',
        'currency',
        'balance',
        'error_message',
        'email_sent',
        'bsale_document_id',
        'bsale_number',
        'bsale_token'
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
        'institution_name'
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
     * Boot method para configurar timezone automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            // Asegurar que las fechas se guarden en timezone de Santiago
            $payment->created_at = now()->setTimezone('America/Santiago');
            $payment->updated_at = now()->setTimezone('America/Santiago');
        });

        static::updating(function ($payment) {
            // Asegurar que updated_at se guarde en timezone de Santiago
            $payment->updated_at = now()->setTimezone('America/Santiago');
        });
    }
}
