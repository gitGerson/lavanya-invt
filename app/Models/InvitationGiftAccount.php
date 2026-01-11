<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationGiftAccount extends Model
{
    protected $table = 'invitation_gift_accounts';

    protected $fillable = [
        'invitation_id',
        'sort_order',
        'bank_name',
        'account_number',
        'account_holder',
        'qr_asset_id',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function qr(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'qr_asset_id');
    }
}
