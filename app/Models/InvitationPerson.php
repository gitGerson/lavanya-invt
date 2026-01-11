<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationPerson extends Model
{
    protected $table = 'invitation_people';

    protected $fillable = [
        'invitation_id',
        'role', // bride | groom
        'name',
        'title',
        'father_name',
        'mother_name',
        'instagram_handle',
        'photo_asset_id',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'photo_asset_id');
    }
}
