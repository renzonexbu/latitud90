<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GuardianEmergencyContact extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'guardian_emergency_contact';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'guardian_user_id',
        'emergency_contact_id',
        'invitation_code',
        'invitation_status',
        'invitation_sent_at',
        'invitation_accepted_at',
        'invitation_expires_at',
        'is_primary',
        'can_pay',
        'can_view_documents',
        'can_view_itinerary',
        'can_receive_notifications',
        'can_update_emergency_contact',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'invitation_sent_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'is_primary' => 'boolean',
            'can_pay' => 'boolean',
            'can_view_documents' => 'boolean',
            'can_view_itinerary' => 'boolean',
            'can_receive_notifications' => 'boolean',
            'can_update_emergency_contact' => 'boolean',
        ];
    }

    /**
     * Relación con GuardianUser
     */
    public function guardianUser()
    {
        return $this->belongsTo(GuardianUser::class, 'guardian_user_id');
    }

    /**
     * Relación con EmergencyContact
     */
    public function emergencyContact()
    {
        return $this->belongsTo(EmergencyContact::class, 'emergency_contact_id');
    }

    /**
     * Relación con el User admin que creó la invitación
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generar código único de invitación
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('invitation_code', $code)->exists());

        return $code;
    }

    /**
     * Verificar si la invitación está expirada
     */
    public function isExpired(): bool
    {
        if (!$this->invitation_expires_at) {
            return false;
        }

        return now()->isAfter($this->invitation_expires_at);
    }

    /**
     * Verificar si la invitación está pendiente
     */
    public function isPending(): bool
    {
        return $this->invitation_status === 'pending' && !$this->isExpired();
    }

    /**
     * Verificar si la invitación fue aceptada
     */
    public function isAccepted(): bool
    {
        return $this->invitation_status === 'accepted';
    }

    /**
     * Marcar invitación como expirada
     */
    public function markAsExpired(): void
    {
        $this->update(['invitation_status' => 'expired']);
    }

    /**
     * Aceptar invitación y vincular con usuario
     */
    public function accept(GuardianUser $user): void
    {
        $this->update([
            'guardian_user_id' => $user->id,
            'invitation_status' => 'accepted',
            'invitation_accepted_at' => now(),
        ]);
    }

    /**
     * Scope: Solo invitaciones pendientes
     */
    public function scopePending($query)
    {
        return $query->where('invitation_status', 'pending')
            ->where(function ($q) {
                $q->whereNull('invitation_expires_at')
                  ->orWhere('invitation_expires_at', '>', now());
            });
    }

    /**
     * Scope: Solo invitaciones aceptadas
     */
    public function scopeAccepted($query)
    {
        return $query->where('invitation_status', 'accepted');
    }

    /**
     * Scope: Solo invitaciones expiradas
     */
    public function scopeExpired($query)
    {
        return $query->where('invitation_status', 'expired')
            ->orWhere(function ($q) {
                $q->where('invitation_status', 'pending')
                  ->whereNotNull('invitation_expires_at')
                  ->where('invitation_expires_at', '<=', now());
            });
    }

    /**
     * Scope: Solo apoderados principales
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Verificar expiración antes de guardar
        static::saving(function ($model) {
            if ($model->isExpired() && $model->invitation_status === 'pending') {
                $model->invitation_status = 'expired';
            }
        });
    }
}
