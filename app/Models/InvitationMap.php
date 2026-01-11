<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationMap extends Model
{
    protected $table = 'invitation_map';
    protected $primaryKey = 'invitation_id';
    public $incrementing = false;

    protected $fillable = [
        'invitation_id',
        'map_section_title',
        'map_address',
        'map_embed_src',
        'map_location_url',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
