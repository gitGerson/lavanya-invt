<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationRsvpResponse extends Model
{
    protected $fillable = [
        'invitation_id',
        'guest_name',
        'phone',
        'attendance',
        'pax',
        'note',
        'ip_address',
        'user_agent',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
