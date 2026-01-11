<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationEvent extends Model
{
    protected $table = 'invitation_events';

    protected $fillable = [
        'invitation_id',
        'sort_order',
        'title',
        'event_date_display',
        'event_time_display',
        'event_date',
        'start_time',
        'end_time',
        'location_text',
        'location_url',
    ];

    protected $casts = [
        'event_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time'   => 'datetime:H:i:s',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
