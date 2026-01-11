# Guide — Passing Invitation Data to Dynamic Blade Templates (Pattern A)  
Laravel 12 + Filament v4 + Structured DB (Pattern A)

This guide explains a **production-ready** method to pass data from your Pattern A database into the correct Blade template view using a reusable **TemplateRenderer** service.

Assumptions (already done):
- migrations ✅
- models ✅
- Filament v4 resource/form ✅
- Blade templates ✅ in `resources/views/templates/{template-code}.blade.php`

End result:
- Public URL: `/inv/{slug}` loads the invitation + relations and renders the right template automatically.
- Every template receives consistent variables:  
  **`$invitation`, `$bride`, `$groom`, `$dto`, `$fields`**

---

## 1) Why use a TemplateRenderer?

A TemplateRenderer gives you:
- One place to define **what relations to eager-load**
- One place to define **template view resolution** (`templates.{code}`)
- Automatic **default row creation** for Pattern A `hasOne` relations (so templates never crash)
- Optional **validation** per template
- Optional **caching** for fast public pages
- A consistent payload for all templates (Eloquent + DTO + flat map)

---

## 2) Route Setup (Public)

`routes/web.php`

```php
use App\Http\Controllers\InvitationPublicController;

Route::get('/inv/{slug}', [InvitationPublicController::class, 'show'])
  ->name('invitation.show');
```

(Optional) If you want published-only on public routes, you will configure this inside the renderer.

---

## 3) Create the TemplateRenderer Service (Core)

Create: `app/Services/TemplateRenderer.php`

> This file is the “single source of truth” for loading + rendering templates.

```php
<?php

namespace App\Services;

use App\Models\Invitation;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class TemplateRenderer
{
    /**
     * Change this if you want public route to show ONLY published invitations.
     * Example: ['published']
     */
    protected array $allowedStatuses = ['draft', 'published'];

    /**
     * Optional: minimal validation rules per template.
     * Add more templates here as you create them.
     */
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

    /**
     * Render invitation by slug (common for public route).
     */
    public function renderBySlug(string $slug, bool $useCache = true): ViewContract
    {
        $invitation = $this->loadForPublic($slug);

        return $this->renderInvitation($invitation, $useCache);
    }

    /**
     * Render already-loaded invitation model.
     */
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

    /**
     * Load invitation for public rendering.
     */
    public function loadForPublic(string $slug): Invitation
    {
        $invitation = Invitation::query()
            ->where('slug', $slug)
            ->whereIn('status', $this->allowedStatuses)
            ->with($this->relations())
            ->first();

        if (! $invitation) {
            throw new ModelNotFoundException("Invitation not found for slug: {$slug}");
        }

        return $invitation;
    }

    /**
     * Eager-load list used everywhere.
     */
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

            'music.audio',
        ];
    }

    /**
     * Resolve blade view from template code: templates.template-1
     */
    public function resolveView(Invitation $invitation): string
    {
        $code = $invitation->template?->code;

        if (! $code) {
            abort(404, 'Invitation template not set.');
        }

        return 'templates.' . $code;
    }

    /**
     * Ensure Pattern A hasOne rows exist + bride/groom rows exist.
     * Prevents null relation errors in Blade.
     */
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

    /**
     * Optional validation: check required relationships exist.
     * If you want strict enforcement, enable abort.
     */
    public function validateForTemplate(Invitation $invitation): void
    {
        $code = $invitation->template?->code;
        if (! $code) return;

        $rules = $this->templateRules[$code] ?? null;
        if (! $rules) return;

        $missing = [];

        foreach (($rules['requires'] ?? []) as $rel) {
            $value = data_get($invitation, $rel);

            if (is_null($value)) {
                $missing[] = $rel;
                continue;
            }

            // If you want strict empty-collection checks, uncomment:
            // if ($value instanceof \Illuminate\Support\Collection && $value->isEmpty()) {
            //     $missing[] = $rel;
            // }
        }

        // Strict mode (optional):
        // if (! empty($missing)) {
        //     abort(422, 'Invitation data incomplete: ' . implode(', ', $missing));
        // }
    }

    /**
     * Build the payload passed into Blade.
     */
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

    /**
     * Cache dto + fields (Eloquent models are passed fresh).
     */
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

    /**
     * Structured DTO for templates (stable shape).
     */
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

    /**
     * Flat map for legacy / data-field-based templates.
     */
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

    /**
     * Clear cached payload when invitation updated.
     */
    public function forgetCache(Invitation $invitation): void
    {
        Cache::forget("invitation:{$invitation->id}:payload:v1");
    }
}
```

---

## 4) Public Controller Using TemplateRenderer

Create / update: `app/Http/Controllers/InvitationPublicController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\TemplateRenderer;

class InvitationPublicController extends Controller
{
    public function show(string $slug, TemplateRenderer $renderer)
    {
        return $renderer->renderBySlug($slug, useCache: true);
    }
}
```

This is now the only code you need in the controller.

---

## 5) What Data the Template Receives

Every template view gets:

- `$invitation` (Eloquent model; relations eager-loaded)
- `$bride`, `$groom` (helper models)
- `$dto` (stable structured array)
- `$fields` (flat key map)

So templates can choose how to access data:

### Eloquent style
```blade
{{ $invitation->couple?->couple_name_1 }}
```

### DTO style (recommended for cross-template stability)
```blade
{{ $dto['couple']['name_1'] ?? '' }}
```

### Field-map style (for old templates)
```blade
{{ $fields['couple_name_1'] ?? '' }}
```

---

## 6) Cache Invalidation (Very Important)

If you use caching in `TemplateRenderer`, clear cache when data changes.

### Option A: In Invitation model `booted()`

`app/Models/Invitation.php`

```php
use App\Services\TemplateRenderer;

protected static function booted(): void
{
    static::saved(function (Invitation $invitation) {
        app(TemplateRenderer::class)->forgetCache($invitation);
    });

    static::deleted(function (Invitation $invitation) {
        app(TemplateRenderer::class)->forgetCache($invitation);
    });
}
```

> If you already used `booted()` to auto-create defaults, merge them in the same method.

---

## 7) Filament Preview Button (Nice UX)

In your `InvitationResource::table()` actions:

```php
Tables\Actions\Action::make('preview')
    ->label('Preview')
    ->url(fn ($record) => route('invitation.show', $record->slug))
    ->openUrlInNewTab(),
```

If public route should be **published-only**, create a separate preview route for admins:
- `/preview/inv/{slug}` → allow `draft + published` and require auth.

---

## 8) Recommended Template Safety Patterns (Blade)

Use null-safe checks + `@forelse` so templates don’t crash:

```blade
<h1>{{ $dto['couple']['name_1'] ?? 'Nama Mempelai' }}</h1>

@forelse($dto['events'] ?? [] as $event)
  <div>{{ $event['title'] }}</div>
@empty
  <div class="text-sm opacity-70">Event belum diisi.</div>
@endforelse
```

---

## 9) Checklist

- [ ] `templates.code` matches file `resources/views/templates/{code}.blade.php`
- [ ] `/inv/{slug}` route exists and hits controller
- [ ] Controller uses `TemplateRenderer`
- [ ] TemplateRenderer eager-loads everything needed
- [ ] TemplateRenderer creates defaults (hasOne + bride/groom)
- [ ] Blade uses `$invitation` or `$dto` or `$fields` without DB queries
- [ ] Cache invalidation is added (if caching enabled)

---

## 10) Minimal Working Example

**Controller**
```php
return $renderer->renderBySlug($slug, useCache: true);
```

**Blade**
```blade
<h1>{{ $dto['couple']['name_1'] ?? '' }}</h1>
<img src="{{ $dto['couple']['image'] ?? '' }}" alt="">
```

That’s the full pipeline.
