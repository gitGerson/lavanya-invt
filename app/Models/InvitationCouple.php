<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationCouple extends Model
{
    protected $table = 'invitation_couple';
    protected $primaryKey = 'invitation_id';
    public $incrementing = false;

    protected $fillable = [
        'invitation_id',
        'couple_tagline',
        'couple_name_1',
        'couple_name_2',
        'wedding_date_display',
        'couple_image_asset_id',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function coupleImage(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'couple_image_asset_id');
    }
}
