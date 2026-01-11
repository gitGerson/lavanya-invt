<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'kind',
        'category',
        'storage',
        'url',
        'disk',
        'path',
        'mime',
        'alt_text',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * Get a usable public URL regardless of storage type.
     */
    public function publicUrl(): ?string
    {
        if ($this->storage === 'url') {
            return $this->url;
        }

        if ($this->storage === 'local' && $this->disk && $this->path) {
            return \Storage::disk($this->disk)->url($this->path);
        }

        return null;
    }
}
