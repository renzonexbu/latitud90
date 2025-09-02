<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'level',
        'message',
        'context',
        'file',
        'line',
        'trace',
        'user_id',
        'user_email',
        'ip_address',
        'user_agent',
        'request_method',
        'request_url',
        'request_data',
    ];

    protected $casts = [
        'context' => 'array',
        'request_data' => 'array',
        'line' => 'integer',
    ];

    // Scopes para filtrar logs
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeErrors($query)
    {
        return $query->where('level', 'error');
    }

    public function scopeInfo($query)
    {
        return $query->where('level', 'info');
    }

    public function scopeWarnings($query)
    {
        return $query->where('level', 'warning');
    }

    public function scopeCritical($query)
    {
        return $query->where('level', 'critical');
    }

    // Accessors para formatear datos
    public function getLevelColorAttribute()
    {
        $colors = [
            'error' => 'red',
            'info' => 'blue',
            'warning' => 'yellow',
            'debug' => 'gray',
            'critical' => 'purple',
        ];

        return $colors[$this->level] ?? 'gray';
    }

    public function getLevelTextAttribute()
    {
        $texts = [
            'error' => 'Error',
            'info' => 'Información',
            'warning' => 'Advertencia',
            'debug' => 'Debug',
            'critical' => 'Crítico',
        ];

        return $texts[$this->level] ?? ucfirst($this->level);
    }

    public function getFormattedContextAttribute()
    {
        if (empty($this->context)) {
            return 'N/A';
        }

        return json_encode($this->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function getFormattedRequestDataAttribute()
    {
        if (empty($this->request_data)) {
            return 'N/A';
        }

        return json_encode($this->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
