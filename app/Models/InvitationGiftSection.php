<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationGiftSection extends Model
{
    protected $table = 'invitation_gift_section';
    protected $primaryKey = 'invitation_id';
    public $incrementing = false;

    protected $fillable = [
        'invitation_id',
        'gift_title',
        'gift_subtitle',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
