<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationRsvp extends Model
{
    protected $table = 'invitation_rsvp';
    protected $primaryKey = 'invitation_id';
    public $incrementing = false;

    protected $fillable = [
        'invitation_id',
        'rsvp_title',
        'rsvp_subtitle',
        'rsvp_message',
        'rsvp_hosts',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
