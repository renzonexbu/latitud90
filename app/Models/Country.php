<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'name',
        'code'
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'country', 'code');
    }

    public function isChile(): bool
    {
        return strtoupper((string) $this->code) === 'CL';
    }

    public static function chileId(): ?int
    {
        return static::where('code', 'CL')->value('id');
    }

    /**
     * Países para formularios: Chile primero, luego el resto alfabético.
     */
    public static function forSelect()
    {
        return static::query()
            ->orderByRaw("CASE WHEN code = 'CL' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }
} 