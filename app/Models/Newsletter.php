<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory;

    protected $table = 'newsletter';

    protected $fillable = [
        'email',
        'is_active',
        'subscribed_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
    ];

    // Campos calculados para la interfaz
    public function getStatusTextAttribute()
    {
        if ($this->is_active) {
            return 'Activo';
        } else {
            return 'Inactivo';
        }
    }

    public function getStatusChipClassAttribute()
    {
        if ($this->is_active) {
            return 'bg-[#4b8d7f]'; // Verde para activo
        } else {
            return 'bg-gray-300'; // Gris para inactivo
        }
    }
}
