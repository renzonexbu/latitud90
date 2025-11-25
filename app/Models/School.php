<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        if (str_starts_with($this->logo, '/')) {
            return $this->logo;
        }

        return asset('storage/' . $this->logo);
    }

    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }
}
