<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramFeature extends Model
{
    use HasFactory;

    protected $table = 'programs_features';

    protected $fillable = [
        'program_id',
        'feature_id',
        'type'
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
