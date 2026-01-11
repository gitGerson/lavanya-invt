<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationWishSample extends Model
{
    protected $table = 'invitation_wish_samples';

    protected $fillable = [
        'invitation_id',
        'sort_order',
        'name',
        'address',
        'message',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
