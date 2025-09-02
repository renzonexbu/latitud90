<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    use HasFactory;

    protected $table = 'admin_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'module',
        'resource_type',
        'resource_id',
        'description',
        'old_values',
        'new_values',
        'additional_data',
        'ip_address',
        'user_agent',
        'session_id',
        'request_method',
        'request_url',
        'request_data'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'additional_data' => 'array',
        'request_data' => 'array',
    ];

    // Relación con el usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes para filtros
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Métodos helper
    public function getActionColorAttribute()
    {
        return match($this->action) {
            'create' => 'bg-green-500',
            'update' => 'bg-blue-500',
            'delete' => 'bg-red-500',
            'view' => 'bg-gray-500',
            'export' => 'bg-purple-500',
            'login' => 'bg-indigo-500',
            'logout' => 'bg-yellow-500',
            default => 'bg-gray-400'
        };
    }

    public function getActionTextAttribute()
    {
        return match($this->action) {
            'create' => 'Crear',
            'update' => 'Actualizar',
            'delete' => 'Eliminar',
            'view' => 'Ver',
            'export' => 'Exportar',
            'login' => 'Iniciar Sesión',
            'logout' => 'Cerrar Sesión',
            default => ucfirst($this->action)
        };
    }
}
