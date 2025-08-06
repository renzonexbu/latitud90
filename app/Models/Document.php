<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'document';

    protected $fillable = [
        'name',
        'country'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }
} 