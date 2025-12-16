<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuardianUserParticipant extends Model
{
    use HasFactory;

    protected $table = 'guardian_user_participant';

    protected $fillable = [
        'guardian_user_id',
        'participant_id',
        'can_pay',
    ];

    protected function casts(): array
    {
        return [
            'can_pay' => 'boolean',
        ];
    }

    /**
     * Relacion con GuardianUser
     */
    public function guardianUser()
    {
        return $this->belongsTo(GuardianUser::class, 'guardian_user_id');
    }

    /**
     * Relacion con Participant
     */
    public function participant()
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }
}
