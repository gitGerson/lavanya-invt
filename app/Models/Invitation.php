<?php

namespace App\Models;

use App\Services\TemplateRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invitation extends Model
{
    protected $fillable = [
        'template_id',
        'slug',
        'title',
        'timezone',
        'locale',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Invitation $invitation): void {
            $invitation->couple()->firstOrCreate([]);
            $invitation->eventSection()->firstOrCreate([]);
            $invitation->map()->firstOrCreate([]);
            $invitation->rsvp()->firstOrCreate([]);
            $invitation->giftSection()->firstOrCreate([]);
            $invitation->wishSection()->firstOrCreate([]);
            $invitation->music()->firstOrCreate([]);

            $invitation->people()->firstOrCreate(['role' => 'bride']);
            $invitation->people()->firstOrCreate(['role' => 'groom']);
        });

        static::saved(function (Invitation $invitation): void {
            app(TemplateRenderer::class)->forgetCache($invitation);
        });

        static::deleted(function (Invitation $invitation): void {
            app(TemplateRenderer::class)->forgetCache($invitation);
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function couple(): HasOne
    {
        return $this->hasOne(InvitationCouple::class, 'invitation_id');
    }

    public function people(): HasMany
    {
        return $this->hasMany(InvitationPerson::class, 'invitation_id');
    }

    public function eventSection(): HasOne
    {
        return $this->hasOne(InvitationEventSection::class, 'invitation_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InvitationEvent::class, 'invitation_id')
            ->orderBy('sort_order');
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(InvitationGalleryItem::class, 'invitation_id')
            ->orderBy('sort_order');
    }

    public function map(): HasOne
    {
        return $this->hasOne(InvitationMap::class, 'invitation_id');
    }

    public function rsvp(): HasOne
    {
        return $this->hasOne(InvitationRsvp::class, 'invitation_id');
    }

    public function rsvpResponses(): HasMany
    {
        return $this->hasMany(InvitationRsvpResponse::class, 'invitation_id')
            ->latest();
    }

    public function giftSection(): HasOne
    {
        return $this->hasOne(InvitationGiftSection::class, 'invitation_id');
    }

    public function giftAccounts(): HasMany
    {
        return $this->hasMany(InvitationGiftAccount::class, 'invitation_id')
            ->orderBy('sort_order');
    }

    public function wishSection(): HasOne
    {
        return $this->hasOne(InvitationWishSection::class, 'invitation_id');
    }

    public function wishSamples(): HasMany
    {
        return $this->hasMany(InvitationWishSample::class, 'invitation_id')
            ->orderBy('sort_order');
    }

    public function guestbookEntries(): HasMany
    {
        return $this->hasMany(InvitationGuestbookEntry::class, 'invitation_id')
            ->latest();
    }

    public function music(): HasOne
    {
        return $this->hasOne(InvitationMusic::class, 'invitation_id');
    }

    // Convenience helpers
    public function bride(): ?InvitationPerson
    {
        return $this->people->firstWhere('role', 'bride');
    }

    public function groom(): ?InvitationPerson
    {
        return $this->people->firstWhere('role', 'groom');
    }
}
