<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationGalleryItem extends Model
{
    protected $table = 'invitation_gallery_items';

    protected $fillable = [
        'invitation_id',
        'sort_order',
        'image_asset_id',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'image_asset_id');
    }
}
