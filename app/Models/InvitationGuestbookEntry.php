<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationGuestbookEntry extends Model
{
    protected $table = 'invitation_guestbook_entries';

    protected $fillable = [
        'invitation_id',
        'guest_name',
        'guest_address',
        'message',
        'attendance',
        'ip_address',
        'user_agent',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
