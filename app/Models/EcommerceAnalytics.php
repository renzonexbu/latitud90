<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceAnalytics extends Model
{
    protected $fillable = [
        'session_id',
        'visitor_id',
        'ip_address',
        'user_agent',
        'referrer',
        'participant_rut',
        'participant_id',
        'program_id',
        'program_name',
        'hero_search_at',
        'program_list_view_at',
        'program_detail_view_at',
        'payment_details_view_at',
        'confirmation_view_at',
        'payment_initiated_at',
        'payment_completed_at',
        'payment_failed_at',
        'payment_amount',
        'payment_method',
        'payment_type',
        'installments_count',
        'payment_status',
        'order_number',
        'order_id',
        'order_detail_id',
        'time_to_program_detail',
        'time_to_payment',
        'time_to_completion',
        'funnel_data',
        'user_behavior',
        'notes',
    ];

    protected $casts = [
        'hero_search_at' => 'datetime',
        'program_list_view_at' => 'datetime',
        'program_detail_view_at' => 'datetime',
        'payment_details_view_at' => 'datetime',
        'confirmation_view_at' => 'datetime',
        'payment_initiated_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'payment_failed_at' => 'datetime',
        'payment_amount' => 'decimal:2',
        'installments_count' => 'integer',
        'funnel_data' => 'array',
        'user_behavior' => 'array',
    ];

    // Relaciones
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }

    // Métodos de utilidad
    public function calculateConversionRate($fromStep, $toStep): float
    {
        $fromCount = static::whereNotNull($fromStep)->count();
        $toCount = static::whereNotNull($toStep)->count();
        
        return $fromCount > 0 ? ($toCount / $fromCount) * 100 : 0;
    }

    public function getFunnelData(): array
    {
        return [
            'hero_searches' => static::whereNotNull('hero_search_at')->count(),
            'program_list_views' => static::whereNotNull('program_list_view_at')->count(),
            'program_detail_views' => static::whereNotNull('program_detail_view_at')->count(),
            'payment_details_views' => static::whereNotNull('payment_details_view_at')->count(),
            'confirmation_views' => static::whereNotNull('confirmation_view_at')->count(),
            'payments_initiated' => static::whereNotNull('payment_initiated_at')->count(),
            'payments_completed' => static::whereNotNull('payment_completed_at')->count(),
            'payments_failed' => static::whereNotNull('payment_failed_at')->count(),
        ];
    }
}
