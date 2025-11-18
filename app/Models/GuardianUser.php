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
     * Obtener todos los participantes asociados a este usuario
     */
    public function participants()
    {
        return $this->hasManyThrough(
            Participant::class,
            GuardianEmergencyContact::class,
            'guardian_user_id', // FK en guardian_emergency_contact
            'id', // FK en participants
            'id', // PK en guardian_users
            'emergency_contact_id' // PK en emergency_contact
        );
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
        // Buscar emergency_contacts del participante
        $emergencyContacts = \App\Models\EmergencyContact::where('participant_id', $participantId)->get();

        // Log completo de comparación
        \Illuminate\Support\Facades\Log::info('🔍 DIAGNÓSTICO canPayFor()', [
            'guardian_email' => $this->email,
            'guardian_id' => $this->id,
            'participant_id_buscado' => $participantId,
            'total_emergency_contacts' => $emergencyContacts->count(),
            'emergency_contacts_detalle' => $emergencyContacts->map(function($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'coincide_con_guardian' => $contact->email === $this->email ? 'SÍ ✅' : 'NO ❌'
                ];
            })->toArray()
        ]);

        // Verificar si el email del guardian coincide con algún emergency_contact del participante
        $result = $emergencyContacts->contains('email', $this->email);

        \Illuminate\Support\Facades\Log::info('🔍 RESULTADO canPayFor()', [
            'resultado' => $result ? 'TRUE ✅' : 'FALSE ❌'
        ]);

        return $result;
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
