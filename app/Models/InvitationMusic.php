<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationMusic extends Model
{
    protected $table = 'invitation_music';
    protected $primaryKey = 'invitation_id';
    public $incrementing = false;

    protected $fillable = [
        'invitation_id',
        'audio_asset_id',
        'autoplay',
        'loop_audio',
    ];

    protected $casts = [
        'autoplay' => 'boolean',
        'loop_audio' => 'boolean',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'audio_asset_id');
    }
}
