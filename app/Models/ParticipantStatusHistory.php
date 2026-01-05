<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantStatusHistory extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'participant_status_history';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'participant_id',
        'previous_status',
        'new_status',
        'comment',
        'changed_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'previous_status' => 'boolean',
        'new_status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the participant that owns the status history.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get the user that made the change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
