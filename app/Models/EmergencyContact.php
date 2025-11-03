<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    use HasFactory;

    protected $table = 'emergency_contact';

    protected $fillable = [
        'name',
        'email',
        'document_type',
        'document_number',
        'code_phone',
        'phone',
        'country',
        'birth_date',
        'address',
        'relationship',
        'participant_id'
    ];

    protected $casts = [
        'birth_date' => 'date'
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Relación con guardian_emergency_contact (pivote)
     */
    public function guardianLinks()
    {
        return $this->hasMany(GuardianEmergencyContact::class, 'emergency_contact_id');
    }

    /**
     * Relación con guardian_users a través de la pivote
     */
    public function guardianUsers()
    {
        return $this->belongsToMany(
            GuardianUser::class,
            'guardian_emergency_contact',
            'emergency_contact_id',
            'guardian_user_id'
        )
        ->withPivot([
            'invitation_code',
            'invitation_status',
            'is_primary',
            'can_pay',
            'can_view_documents',
            'can_view_itinerary',
            'can_receive_notifications',
            'can_update_emergency_contact',
            'invitation_sent_at',
            'invitation_accepted_at',
        ])
        ->withTimestamps();
    }

    /**
     * Verificar si tiene usuarios registrados
     */
    public function hasRegisteredUsers(): bool
    {
        return $this->guardianLinks()
            ->where('invitation_status', 'accepted')
            ->exists();
    }

    /**
     * Obtener el apoderado principal
     */
    public function primaryGuardian()
    {
        return $this->guardianLinks()
            ->where('is_primary', true)
            ->where('invitation_status', 'accepted')
            ->first();
    }
} 