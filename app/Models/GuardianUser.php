<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GuardianUser extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'guardian_users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'password',
        'document_id',
        'document',
        'name',
        'phone_code',
        'phone',
        'country_id',
        'region_id',
        'comune_id',
        'status',
        'email_verified_at',
        'email_verification_token',
        'email_verification_expires_at',
        'language',
        'timezone',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relación con el tipo de documento
     */
    public function documentType()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Relación con el país
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Relación con la región
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Relación con la comuna
     */
    public function comune()
    {
        return $this->belongsTo(Comune::class, 'comune_id');
    }

    /**
     * Relación con guardian_emergency_contact (pivote)
     */
    public function guardianLinks()
    {
        return $this->hasMany(GuardianEmergencyContact::class, 'guardian_user_id');
    }

    /**
     * Relación con emergency_contacts a través de la pivote
     */
    public function emergencyContacts()
    {
        return $this->belongsToMany(
            EmergencyContact::class,
            'guardian_emergency_contact',
            'guardian_user_id',
            'emergency_contact_id'
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
     * Relacion directa con participantes (nueva estructura)
     */
    public function participants()
    {
        return $this->belongsToMany(
            Participant::class,
            'guardian_user_participant',
            'guardian_user_id',
            'participant_id'
        )
        ->withPivot(['can_pay'])
        ->withTimestamps();
    }

    /**
     * Relacion con la tabla pivote guardian_user_participant
     */
    public function participantLinks()
    {
        return $this->hasMany(GuardianUserParticipant::class, 'guardian_user_id');
    }

    /**
     * Relación con órdenes de pago
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'guardian_user_id');
    }

    /**
     * Verificar si el usuario puede realizar pagos para un participante
     */
    public function canPayFor(int $participantId): bool
    {
        // Verificar en la nueva tabla pivote guardian_user_participant
        $hasLink = $this->participantLinks()
            ->where('participant_id', $participantId)
            ->where('can_pay', true)
            ->exists();

        if ($hasLink) {
            return true;
        }

        // Fallback: verificar si el email coincide con emergency_contact (compatibilidad)
        return \App\Models\EmergencyContact::where('participant_id', $participantId)
            ->where('email', $this->email)
            ->exists();
    }

    /**
     * Verificar si el usuario puede ver documentos de un participante
     */
    public function canViewDocumentsFor(int $participantId): bool
    {
        return $this->guardianLinks()
            ->whereHas('emergencyContact', function ($query) use ($participantId) {
                $query->where('participant_id', $participantId);
            })
            ->where('invitation_status', 'accepted')
            ->where('can_view_documents', true)
            ->exists();
    }

    /**
     * Activar cuenta después de primer pago exitoso
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Actualizar último login
     */
    public function updateLastLogin(string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Scope: Solo usuarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Usuarios con pago pendiente
     */
    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }
}
