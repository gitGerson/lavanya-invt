# Guide — Passing Invitation Data to Dynamic Blade Templates (Pattern A)  
Laravel 12 + Filament v4 + Structured DB (Pattern A)

This guide focuses on **how to load invitation data from your Pattern A tables and pass it to the correct Blade template view**, after your:
- migrations ✅
- models ✅
- Filament form/resource ✅
- template blade(s) ✅

You will end up with a clean, repeatable rendering pipeline like:

`/inv/{slug}` → load Invitation + relations → resolve template view → transform data (optional) → `return view(...)`

---

## 1) The Rendering Goal

You want:
- A **public route** that renders an invitation: `/inv/{slug}`
- It automatically chooses the correct Blade template based on:
  - `invitations.template_id` → `templates.code`
- The Blade view receives a consistent, predictable dataset:
  - `$invitation` (Eloquent model)  
  - plus helpful derived variables:
    - `$bride`, `$groom`
    - `$assets` (optional)
    - `$viewData` (optional array DTO)

---

## 2) Recommended Approach (Three Layers)

### Layer A — Controller: request/response only
- Resolves slug
- Calls a “loader/renderer service”
- Returns view

### Layer B — Loader/Service: eager-load relations + compute helpers
- Single source of truth for what gets loaded
- Contains defaults/fallback policy

### Layer C — Blade: simple rendering only
- No DB calls
- No heavy transformations
- Only display logic

---

## 3) Route Setup

### 3.1 Simple route
`routes/web.php`

```php
use App\Http\Controllers\InvitationPublicController;

Route::get('/inv/{slug}', [InvitationPublicController::class, 'show'])
  ->name('invitation.show');
```

### 3.2 Route model binding (optional)
If you prefer binding by slug:

`app/Providers/RouteServiceProvider.php` or inside route:

```php
Route::get('/inv/{invitation:slug}', [InvitationPublicController::class, 'show'])
  ->name('invitation.show');
```

Then controller receives `Invitation $invitation` automatically.

---

## 4) Create a Dedicated Loader Service (Highly Recommended)

Create: `app/Services/InvitationViewData.php`

```php
<?php

namespace App\Services;

use App\Models\Invitation;

class InvitationViewData
{
    /**
     * Eager-load everything needed for templates.
     */
    public function loadForPublic(string $slug): Invitation
    {
        return Invitation::query()
            ->where('slug', $slug)
            ->whereIn('status', ['draft', 'published']) // adjust if you want published only
            ->with([
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

                // optional:
                // 'assets',
            ])
            ->firstOrFail();
    }

    /**
     * Resolve the blade view name from the template code.
     */
    public function resolveView(Invitation $invitation): string
    {
        return 'templates.' . $invitation->template->code;
    }

    /**
     * Provide extra helper variables to the view.
     */
    public function buildPayload(Invitation $invitation): array
    {
        $bride = $invitation->people->firstWhere('role', 'bride');
        $groom = $invitation->people->firstWhere('role', 'groom');

        return [
            'invitation' => $invitation,
            'bride' => $bride,
            'groom' => $groom,
        ];
    }
}
```

---

## 5) Public Controller That Uses the Service

Create: `app/Http/Controllers/InvitationPublicController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\InvitationViewData;
use Illuminate\Http\Request;

class InvitationPublicController extends Controller
{
    public function show(Request $request, string $slug, InvitationViewData $viewData)
    {
        $invitation = $viewData->loadForPublic($slug);

        $view = $viewData->resolveView($invitation);

        if (! view()->exists($view)) {
            abort(404, 'Template view not found.');
        }

        return view($view, $viewData->buildPayload($invitation));
    }
}
```

✅ Now all templates get the **same consistent variables**.

---

## 6) What the Blade Should Expect

Inside `resources/views/templates/template-1.blade.php` you’ll use:

### 6.1 Basic
- `$invitation->title`
- `$invitation->slug`

### 6.2 Couple
- `$invitation->couple?->couple_name_1`
- `$invitation->couple?->coupleImage?->publicUrl()`

### 6.3 Bride/Groom helpers
- `$bride?->name`
- `$groom?->photo?->publicUrl()`

### 6.4 Collections
- `$invitation->events` (ordered)
- `$invitation->galleryItems` (ordered)
- `$invitation->giftAccounts` (ordered)
- `$invitation->guestbookEntries` (latest)

> IMPORTANT: Do not query DB from Blade; everything should be in `$invitation` already.

---

## 7) “Null-safe” Defaults (So Templates Don’t Break)

In Blade, always assume something can be missing:

```blade
<h1>{{ $invitation->couple?->couple_name_1 ?? 'Nama Mempelai' }}</h1>

@if($invitation->couple?->coupleImage?->publicUrl())
  <img src="{{ $invitation->couple->coupleImage->publicUrl() }}" alt="">
@endif
```

For lists, use `@forelse`:

```blade
@forelse($invitation->events as $event)
  <div>{{ $event->title }}</div>
@empty
  <div class="text-sm opacity-70">Event belum diisi.</div>
@endforelse
```

---

## 8) Handling Assets Properly (URL/local)

Your `Asset::publicUrl()` helper already solves this:

- `storage=url` → returns `url`
- `storage=local` → `Storage::disk(disk)->url(path)`

So, Blade should never care how the asset is stored:

```blade
<img src="{{ $bride?->photo?->publicUrl() }}" alt="{{ $bride?->name }}">
```

---

## 9) Optional: Convert Eloquent to a “DTO-like array” for templates

Some teams prefer passing a structured array (less coupling to DB schema).  
You can extend the service with a `toArray()` method.

### 9.1 Example DTO payload (recommended if you support many templates)

Add in `InvitationViewData`:

```php
public function buildDto(Invitation $invitation): array
{
    $bride = $invitation->people->firstWhere('role', 'bride');
    $groom = $invitation->people->firstWhere('role', 'groom');

    return [
        'meta' => [
            'slug' => $invitation->slug,
            'title' => $invitation->title,
            'timezone' => $invitation->timezone,
            'status' => $invitation->status,
            'template' => $invitation->template->code,
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
        'events' => $invitation->events->map(fn($e) => [
            'title' => $e->title,
            'date_display' => $e->event_date_display,
            'time_display' => $e->event_time_display,
            'date' => optional($e->event_date)->toDateString(),
            'start_time' => $e->start_time,
            'end_time' => $e->end_time,
            'location_text' => $e->location_text,
            'location_url' => $e->location_url,
            'sort_order' => $e->sort_order,
        ])->values()->all(),
        'gallery' => $invitation->galleryItems->map(fn($g) => [
            'sort_order' => $g->sort_order,
            'image' => $g->image?->publicUrl(),
        ])->values()->all(),
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
            'accounts' => $invitation->giftAccounts->map(fn($a) => [
                'bank' => $a->bank_name,
                'number' => $a->account_number,
                'holder' => $a->account_holder,
                'qr' => $a->qr?->publicUrl(),
                'sort_order' => $a->sort_order,
            ])->values()->all(),
        ],
        'wishes' => [
            'title' => $invitation->wishSection?->wish_title,
            'samples' => $invitation->wishSamples->map(fn($w) => [
                'name' => $w->name,
                'address' => $w->address,
                'message' => $w->message,
                'sort_order' => $w->sort_order,
            ])->values()->all(),
            'guestbook' => $invitation->guestbookEntries->take(20)->map(fn($g) => [
                'name' => $g->guest_name,
                'address' => $g->guest_address,
                'message' => $g->message,
                'attendance' => $g->attendance,
                'created_at' => $g->created_at?->toDateTimeString(),
            ])->values()->all(),
        ],
        'music' => [
            'url' => $invitation->music?->audio?->publicUrl(),
            'autoplay' => $invitation->music?->autoplay ?? true,
            'loop' => $invitation->music?->loop_audio ?? true,
        ],
    ];
}
```

Then in controller:

```php
$dto = $viewData->buildDto($invitation);
return view($view, ['invitation' => $invitation, 'dto' => $dto, 'bride' => $bride, 'groom' => $groom]);
```

In Blade, you can use either `$invitation` or `$dto`.

---

## 10) Compatibility Layer for `data-field="..."` Templates (Optional)

If your template still uses `data-field="couple_name_1"` concept and you want a dictionary:

Add a method:

```php
public function buildFieldMap(Invitation $invitation): array
{
    $bride = $invitation->people->firstWhere('role', 'bride');
    $groom = $invitation->people->firstWhere('role', 'groom');

    return [
        'couple_tagline' => $invitation->couple?->couple_tagline,
        'couple_name_1' => $invitation->couple?->couple_name_1,
        'couple_name_2' => $invitation->couple?->couple_name_2,
        'wedding_date' => $invitation->couple?->wedding_date_display,

        'bride_name' => $bride?->name,
        'bride_title' => $bride?->title,
        'bride_father' => $bride?->father_name,
        'bride_mother' => $bride?->mother_name,
        'bride_ig' => $bride?->instagram_handle,

        'groom_name' => $groom?->name,
        'groom_title' => $groom?->title,
        'groom_father' => $groom?->father_name,
        'groom_mother' => $groom?->mother_name,
        'groom_ig' => $groom?->instagram_handle,

        'event_section_title' => $invitation->eventSection?->section_title,
        'event_location_url' => $invitation->eventSection?->default_location_url,

        // etc...
    ];
}
```

Pass to view:

```php
$fields = $viewData->buildFieldMap($invitation);
return view($view, compact('invitation', 'fields', 'bride', 'groom'));
```

Now you can do:

```blade
{{ $fields['couple_name_1'] ?? '' }}
```

---

## 11) Preview Links from Filament (Nice UX)

In Filament table actions, add “Preview”:

```php
->actions([
    Tables\Actions\Action::make('preview')
        ->label('Preview')
        ->url(fn ($record) => route('invitation.show', $record->slug))
        ->openUrlInNewTab(),
])
```

If you want only published view:
- restrict route to `published`
- create another route `/preview/inv/{slug}` for admins only

---

## 12) Performance Tips (Important for many visitors)

### 12.1 Cache the DTO for public view
Caching helps if your invitation page is static.

In controller:

```php
$dto = cache()->remember(
  "invitation:{$invitation->id}:dto",
  now()->addMinutes(10),
  fn () => $viewData->buildDto($invitation)
);
```

Invalidate cache when invitation updated:
- easiest: in `Invitation` model `saved` event → `cache()->forget(...)`

### 12.2 Use eager loading always
Never do N+1 in Blade.

---

## 13) Security Notes

- Ensure any content that might contain HTML is sanitized if you allow rich text input.
- When printing user content in Blade, always prefer:
  - `{{ }}` escaping
- Only use `{!! !!}` for trusted HTML and sanitize input in admin.

---

## 14) Quick Checklist

- [ ] Route exists: `/inv/{slug}`
- [ ] Controller loads invitation with `with([...])`
- [ ] View resolves via `templates.{code}`
- [ ] Blade uses `$invitation` (and `$bride/$groom` helpers)
- [ ] No DB queries inside Blade
- [ ] Assets use `$asset->publicUrl()`
- [ ] Optional DTO/field-map if needed
- [ ] Preview action in Filament works

---

## 15) Minimal “Working Example”

**Controller**:

```php
$invitation = $viewData->loadForPublic($slug);
$view = $viewData->resolveView($invitation);

return view($view, $viewData->buildPayload($invitation));
```

**Blade**:

```blade
<h1>{{ $invitation->couple?->couple_name_1 }}</h1>
<img src="{{ $invitation->couple?->coupleImage?->publicUrl() }}" alt="">
```

That’s it.

---

If you want next, I can provide:
- a ready-to-copy `TemplateRenderer` service that:
  - auto-creates missing relations (safety)
  - validates “required sections” per template
  - merges background/separator assets per template
  - returns `$dto` + `$fields` consistently
