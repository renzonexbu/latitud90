<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermCondition extends Model
{
    use HasFactory;

    protected $table = 'terms_conditions';

    protected $fillable = [
        'title',
        'version',
        'effective_date',
        'content',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'effective_date' => 'date',
    ];

    /**
     * Scope para obtener solo los términos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar por posición
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc');
    }

    /**
     * Obtener la versión activa actual de los T&C
     * Retorna el registro más reciente que esté activo
     */
    public static function getCurrentVersion(): ?self
    {
        return static::active()
            ->orderBy('effective_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Relación con los detalles de órdenes que aceptaron esta versión
     */
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'terms_condition_id');
    }
}
