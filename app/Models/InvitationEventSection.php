<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationEventSection extends Model
{
    protected $table = 'invitation_event_section';
    protected $primaryKey = 'invitation_id';
    public $incrementing = false;

    protected $fillable = [
        'invitation_id',
        'section_title',
        'default_location_url',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
