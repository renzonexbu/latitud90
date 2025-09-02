<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramRequirement extends Model
{
    use HasFactory;

    protected $table = 'programs_requirements';

    protected $fillable = [
        'program_id',
        'requirement_id',
        'type'
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }
}
