<?php

namespace App\Services;

use App\Models\Invitation;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class TemplateRenderer
{
    protected array $templateRules = [
        'template-1' => [
            'requires' => [
                'couple',
                'people',
                'eventSection',
                'events',
                'galleryItems',
                'map',
                'rsvp',
                'giftSection',
                'giftAccounts',
                'music',
            ],
        ],
    ];

    public function renderBySlug(string $slug, bool $useCache = true): ViewContract
    {
        return $this->renderBySlugWithStatuses($slug, ['draft', 'published'], $useCache);
    }

    public function renderPublicBySlug(string $slug, bool $useCache = true): ViewContract
    {
        return $this->renderBySlugWithStatuses($slug, ['published'], $useCache);
    }

    public function renderPreviewBySlug(string $slug, bool $useCache = false): ViewContract
    {
        return $this->renderBySlugWithStatuses($slug, ['draft', 'published'], $useCache);
    }

    public function renderBySlugWithStatuses(string $slug, array $statuses, bool $useCache = true): ViewContract
    {
        $invitation = $this->loadBySlugAndStatuses($slug, $statuses);

        return $this->renderInvitation($invitation, $useCache);
    }

    public function renderInvitation(Invitation $invitation, bool $useCache = true): ViewContract
    {
        $this->ensureDefaults($invitation);
        $invitation->loadMissing($this->relations());

        $this->validateForTemplate($invitation);

        $view = $this->resolveView($invitation);

        if (! View::exists($view)) {
            abort(404, 'Template view not found: ' . $view);
        }

        $payload = $useCache
            ? $this->cachedPayload($invitation)
            : $this->buildPayload($invitation);

        return view($view, $payload);
    }

    public function loadBySlugAndStatuses(string $slug, array $statuses): Invitation
    {
        $invitation = Invitation::query()
            ->where('slug', $slug)
            ->whereIn('status', $statuses)
            ->with($this->relations())
            ->first();

        if (! $invitation) {
            throw new ModelNotFoundException("Invitation not found for slug: {$slug}");
        }

        return $invitation;
    }

    public function relations(): array
    {
        return [
            'template',

            'couple.coupleImage',
            'people.photo',

            'eventSection',
            'events',

            'galleryItems.image',

            'map',
            'rsvp',

            'giftSection',
            'giftAccounts.qr',

            'wishSection',
            'wishSamples',
            'guestbookEntries',
            'rsvpResponses',

            'music.audio',
        ];
    }

    public function resolveView(Invitation $invitation): string
    {
        $code = $invitation->template?->code;

        if (! $code) {
            abort(404, 'Invitation template not set.');
        }

        return 'templates.' . $code;
    }

    public function ensureDefaults(Invitation $invitation): void
    {
        $invitation->couple()->firstOrCreate([]);
        $invitation->eventSection()->firstOrCreate([]);
        $invitation->map()->firstOrCreate([]);
        $invitation->rsvp()->firstOrCreate([]);
        $invitation->giftSection()->firstOrCreate([]);
        $invitation->wishSection()->firstOrCreate([]);
        $invitation->music()->firstOrCreate([]);

        $invitation->people()->firstOrCreate(['role' => 'bride']);
        $invitation->people()->firstOrCreate(['role' => 'groom']);
    }

    public function validateForTemplate(Invitation $invitation): void
    {
        $code = $invitation->template?->code;
        if (! $code) {
            return;
        }

        $rules = $this->templateRules[$code] ?? null;
        if (! $rules) {
            return;
        }

        $missing = [];

        foreach (($rules['requires'] ?? []) as $rel) {
            $value = data_get($invitation, $rel);

            if (is_null($value)) {
                $missing[] = $rel;
            }
        }

        // Optional strict mode:
        // if (! empty($missing)) {
        //     abort(422, 'Invitation data incomplete: ' . implode(', ', $missing));
        // }
    }

    public function buildPayload(Invitation $invitation): array
    {
        $bride = $invitation->people->firstWhere('role', 'bride');
        $groom = $invitation->people->firstWhere('role', 'groom');

        return [
            'invitation' => $invitation,
            'bride' => $bride,
            'groom' => $groom,
            'dto' => $this->buildDto($invitation, $bride, $groom),
            'fields' => $this->buildFieldMap($invitation, $bride, $groom),
        ];
    }

    public function cachedPayload(Invitation $invitation): array
    {
        $key = "invitation:{$invitation->id}:payload:v1";

        $cached = Cache::remember($key, now()->addMinutes(10), function () use ($invitation) {
            $bride = $invitation->people->firstWhere('role', 'bride');
            $groom = $invitation->people->firstWhere('role', 'groom');

            return [
                'dto' => $this->buildDto($invitation, $bride, $groom),
                'fields' => $this->buildFieldMap($invitation, $bride, $groom),
            ];
        });

        return array_merge(
            [
                'invitation' => $invitation,
                'bride' => $invitation->people->firstWhere('role', 'bride'),
                'groom' => $invitation->people->firstWhere('role', 'groom'),
            ],
            $cached
        );
    }

    public function buildDto(Invitation $invitation, $bride = null, $groom = null): array
    {
        $bride ??= $invitation->people->firstWhere('role', 'bride');
        $groom ??= $invitation->people->firstWhere('role', 'groom');

        return [
            'meta' => [
                'id' => $invitation->id,
                'slug' => $invitation->slug,
                'title' => $invitation->title,
                'timezone' => $invitation->timezone,
                'locale' => $invitation->locale,
                'status' => $invitation->status,
                'template' => $invitation->template?->code,
            ],
            'couple' => [
                'tagline' => $invitation->couple?->couple_tagline,
                'name_1' => $invitation->couple?->couple_name_1,
                'name_2' => $invitation->couple?->couple_name_2,
                'date_display' => $invitation->couple?->wedding_date_display,
                'image' => $invitation->couple?->coupleImage?->publicUrl(),
            ],
            'bride' => [
                'name' => $bride?->name,
                'title' => $bride?->title,
                'father' => $bride?->father_name,
                'mother' => $bride?->mother_name,
                'instagram' => $bride?->instagram_handle,
                'photo' => $bride?->photo?->publicUrl(),
            ],
            'groom' => [
                'name' => $groom?->name,
                'title' => $groom?->title,
                'father' => $groom?->father_name,
                'mother' => $groom?->mother_name,
                'instagram' => $groom?->instagram_handle,
                'photo' => $groom?->photo?->publicUrl(),
            ],
            'event_section' => [
                'title' => $invitation->eventSection?->section_title,
                'default_location_url' => $invitation->eventSection?->default_location_url,
            ],
            'events' => $invitation->events
                ->sortBy('sort_order')
                ->map(fn ($e) => [
                    'title' => $e->title,
                    'date_display' => $e->event_date_display,
                    'time_display' => $e->event_time_display,
                    'date' => optional($e->event_date)->toDateString(),
                    'start_time' => $e->start_time,
                    'end_time' => $e->end_time,
                    'location_text' => $e->location_text,
                    'location_url' => $e->location_url,
                    'sort_order' => $e->sort_order,
                ])
                ->values()
                ->all(),
            'gallery' => $invitation->galleryItems
                ->sortBy('sort_order')
                ->map(fn ($g) => [
                    'sort_order' => $g->sort_order,
                    'image' => $g->image?->publicUrl(),
                ])
                ->values()
                ->all(),
            'map' => [
                'title' => $invitation->map?->map_section_title,
                'address' => $invitation->map?->map_address,
                'embed_src' => $invitation->map?->map_embed_src,
                'location_url' => $invitation->map?->map_location_url,
            ],
            'rsvp' => [
                'title' => $invitation->rsvp?->rsvp_title,
                'subtitle' => $invitation->rsvp?->rsvp_subtitle,
                'message' => $invitation->rsvp?->rsvp_message,
                'hosts' => $invitation->rsvp?->rsvp_hosts,
            ],
            'gifts' => [
                'title' => $invitation->giftSection?->gift_title,
                'subtitle' => $invitation->giftSection?->gift_subtitle,
                'accounts' => $invitation->giftAccounts
                    ->sortBy('sort_order')
                    ->map(fn ($a) => [
                        'bank' => $a->bank_name,
                        'number' => $a->account_number,
                        'holder' => $a->account_holder,
                        'qr' => $a->qr?->publicUrl(),
                        'sort_order' => $a->sort_order,
                    ])
                    ->values()
                    ->all(),
            ],
            'wishes' => [
                'title' => $invitation->wishSection?->wish_title,
                'samples' => $invitation->wishSamples
                    ->sortBy('sort_order')
                    ->map(fn ($w) => [
                        'name' => $w->name,
                        'address' => $w->address,
                        'message' => $w->message,
                        'sort_order' => $w->sort_order,
                    ])
                    ->values()
                    ->all(),
                'guestbook' => $invitation->guestbookEntries
                    ->take(20)
                    ->map(fn ($g) => [
                        'name' => $g->guest_name,
                        'address' => $g->guest_address,
                        'message' => $g->message,
                        'attendance' => $g->attendance,
                        'created_at' => $g->created_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all(),
            ],
            'music' => [
                'url' => $invitation->music?->audio?->publicUrl(),
                'autoplay' => (bool) ($invitation->music?->autoplay ?? true),
                'loop' => (bool) ($invitation->music?->loop_audio ?? true),
            ],
        ];
    }

    public function buildFieldMap(Invitation $invitation, $bride = null, $groom = null): array
    {
        $bride ??= $invitation->people->firstWhere('role', 'bride');
        $groom ??= $invitation->people->firstWhere('role', 'groom');

        return [
            'couple_tagline' => $invitation->couple?->couple_tagline,
            'couple_name_1' => $invitation->couple?->couple_name_1,
            'couple_name_2' => $invitation->couple?->couple_name_2,
            'wedding_date' => $invitation->couple?->wedding_date_display,
            'couple_image' => $invitation->couple?->coupleImage?->publicUrl(),

            'bride_name' => $bride?->name,
            'bride_title' => $bride?->title,
            'bride_father' => $bride?->father_name,
            'bride_mother' => $bride?->mother_name,
            'bride_ig' => $bride?->instagram_handle,
            'bride_photo' => $bride?->photo?->publicUrl(),

            'groom_name' => $groom?->name,
            'groom_title' => $groom?->title,
            'groom_father' => $groom?->father_name,
            'groom_mother' => $groom?->mother_name,
            'groom_ig' => $groom?->instagram_handle,
            'groom_photo' => $groom?->photo?->publicUrl(),

            'event_section_title' => $invitation->eventSection?->section_title,
            'event_location_url' => $invitation->eventSection?->default_location_url,

            'map_title' => $invitation->map?->map_section_title,
            'map_address' => $invitation->map?->map_address,
            'map_embed_src' => $invitation->map?->map_embed_src,
            'map_location_url' => $invitation->map?->map_location_url,

            'rsvp_title' => $invitation->rsvp?->rsvp_title,
            'rsvp_subtitle' => $invitation->rsvp?->rsvp_subtitle,
            'rsvp_message' => $invitation->rsvp?->rsvp_message,
            'rsvp_hosts' => $invitation->rsvp?->rsvp_hosts,

            'gift_title' => $invitation->giftSection?->gift_title,
            'gift_subtitle' => $invitation->giftSection?->gift_subtitle,

            'wish_title' => $invitation->wishSection?->wish_title,

            'music_url' => $invitation->music?->audio?->publicUrl(),
        ];
    }

    public function forgetCache(Invitation $invitation): void
    {
        Cache::forget("invitation:{$invitation->id}:payload:v1");
    }
}
