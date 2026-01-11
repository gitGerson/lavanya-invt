# Pattern A (Structured DB) Guide — Dynamic Wedding Templates (Laravel 12)

This guide assumes you already have:
- **Migrations** + **Models** implemented (Template, Invitation, Asset, InvitationCouple, InvitationPerson, InvitationEvent*, Gallery, Map, RSVP, Gift*, Wish*, Music, etc.)
- One or more Blade templates in: `resources/views/templates/{template-code}.blade.php`

Goal: render any template dynamically using **structured invitation data** (Pattern A), not key-value fields.

---

## 1) Mental Model

Pattern A means:
- Your database tables represent **real domain objects** (couple, people, events, gallery, map, gifts, wishes, music).
- Each template reads from the same relationships (or a compatible subset).
- Each invitation chooses a template via `invitations.template_id`.

At render time, you load:
- `Invitation` + relations
- then render a Blade file based on `Template.code`.

---

## 2) Folder Structure

Recommended:

```
app/
  Models/
    Template.php
    Invitation.php
    Asset.php
    InvitationCouple.php
    InvitationPerson.php
    InvitationEvent.php
    InvitationEventSection.php
    InvitationGalleryItem.php
    InvitationMap.php
    InvitationRsvp.php
    InvitationGiftSection.php
    InvitationGiftAccount.php
    InvitationWishSection.php
    InvitationWishSample.php
    InvitationGuestbookEntry.php
    InvitationMusic.php

  Http/Controllers/
    InvitationPublicController.php

resources/views/
  templates/
    template-1.blade.php
    template-2.blade.php
  invitation/
    not-found.blade.php (optional)
```

---

## 3) Data Loading (Eager Load Everything You Need)

### 3.1 Route

`routes/web.php`

```php
use App\Http\Controllers\InvitationPublicController;

Route::get('/inv/{slug}', [InvitationPublicController::class, 'show'])
    ->name('invitation.show');
```

### 3.2 Controller

`app/Http/Controllers/InvitationPublicController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;

class InvitationPublicController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $invitation = Invitation::query()
            ->where('slug', $slug)
            ->whereIn('status', ['draft','published']) // adjust if needed
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

                // optionally all assets if you want global usage:
                // 'assets',
            ])
            ->firstOrFail();

        // Resolve Blade view from template code: "template-1" => "templates.template-1"
        $view = 'templates.' . $invitation->template->code;

        if (! view()->exists($view)) {
            // Optional fallback view
            return view('invitation.not-found', compact('invitation'));
        }

        return view($view, [
            'invitation' => $invitation,
            'bride' => $invitation->people->firstWhere('role', 'bride'),
            'groom' => $invitation->people->firstWhere('role', 'groom'),
        ]);
    }
}
```

---

## 4) What Data Exists (Canonical Shape)

Inside your Blade, assume:

### Invitation base
- `$invitation->slug`
- `$invitation->title`
- `$invitation->timezone`
- `$invitation->locale`

### Template
- `$invitation->template->code`

### Couple section (hasOne)
- `$invitation->couple->couple_tagline`
- `$invitation->couple->couple_name_1`
- `$invitation->couple->couple_name_2`
- `$invitation->couple->wedding_date_display`
- `$invitation->couple->coupleImage?->publicUrl()` (Asset helper)

### People (hasMany)
- `$bride` and `$groom` (computed in controller)
- `$bride->name`, `$bride->title`, `$bride->father_name`, `$bride->mother_name`, `$bride->instagram_handle`
- `$bride->photo?->publicUrl()`
- same for groom

### Events
- `$invitation->eventSection->section_title`
- `$invitation->eventSection->default_location_url`
- `$invitation->events` (ordered by `sort_order`)
  - `$event->title`
  - `$event->event_date_display` (or `$event->event_date`)
  - `$event->event_time_display` (or `$event->start_time / end_time`)
  - `$event->location_text`
  - `$event->location_url` (optional per event)

### Gallery
- `$invitation->galleryItems` (ordered)
  - `$item->image->publicUrl()`

### Map
- `$invitation->map->map_section_title`
- `$invitation->map->map_address`
- `$invitation->map->map_embed_src`
- `$invitation->map->map_location_url`

### RSVP
- `$invitation->rsvp->rsvp_title`
- `$invitation->rsvp->rsvp_subtitle`
- `$invitation->rsvp->rsvp_message`
- `$invitation->rsvp->rsvp_hosts`

### Gifts
- `$invitation->giftSection->gift_title`
- `$invitation->giftSection->gift_subtitle`
- `$invitation->giftAccounts` (ordered)
  - `$acc->bank_name`
  - `$acc->account_number`
  - `$acc->account_holder`
  - `$acc->qr?->publicUrl()`

### Wishes
Option A (template samples):
- `$invitation->wishSection->wish_title`
- `$invitation->wishSamples` (ordered)

Option B (real guestbook):
- `$invitation->guestbookEntries`

### Music
- `$invitation->music->audio?->publicUrl()`
- `$invitation->music->autoplay`
- `$invitation->music->loop_audio`

---

## 5) Making Blade Template Dynamic (Practical Replacements)

### 5.1 Replace hardcoded text

Before:
```html
<h1 data-field="couple_name_1">Alin</h1>
```

After:
```blade
<h1>{{ $invitation->couple->couple_name_1 }}</h1>
```

### 5.2 Replace hardcoded images

Before:
```html
<img src="https://api....jpg">
```

After:
```blade
<img
  src="{{ $invitation->couple->coupleImage?->publicUrl() }}"
  alt="{{ $invitation->couple->couple_name_1 }} & {{ $invitation->couple->couple_name_2 }}"
>
```

### 5.3 Bride/Groom cards

```blade
@php
  $bride = $bride ?? $invitation->people->firstWhere('role', 'bride');
  $groom = $groom ?? $invitation->people->firstWhere('role', 'groom');
@endphp

<div class="person">
  <img src="{{ $bride?->photo?->publicUrl() }}" alt="{{ $bride?->name }}">
  <h3>{{ $bride?->name }}</h3>
  <p>{{ $bride?->title }}</p>
  <p>Putri dari {{ $bride?->father_name }} & {{ $bride?->mother_name }}</p>

  @if($bride?->instagram_handle)
    <a href="https://instagram.com/{{ ltrim($bride->instagram_handle, '@') }}">
      {{ $bride->instagram_handle }}
    </a>
  @endif
</div>
```

### 5.4 Events list (supports 2 or more events)

```blade
<h2>{{ $invitation->eventSection?->section_title }}</h2>

@foreach($invitation->events as $event)
  <div class="event-card">
    <h3>{{ $event->title }}</h3>
    <div>{{ $event->event_date_display }}</div>
    <div>{{ $event->event_time_display }}</div>
    <div>{{ $event->location_text }}</div>

    @php
      $loc = $event->location_url ?: $invitation->eventSection?->default_location_url;
    @endphp

    @if($loc)
      <a href="{{ $loc }}" target="_blank" rel="noopener">Lihat Lokasi</a>
    @endif
  </div>
@endforeach
```

### 5.5 Gallery

```blade
<div class="gallery">
  @foreach($invitation->galleryItems as $item)
    <img src="{{ $item->image->publicUrl() }}" alt="Gallery {{ $loop->iteration }}">
  @endforeach
</div>
```

### 5.6 Maps iframe

```blade
<h2>{{ $invitation->map?->map_section_title }}</h2>
<p>{{ $invitation->map?->map_address }}</p>

@if($invitation->map?->map_embed_src)
  <iframe
    src="{{ $invitation->map->map_embed_src }}"
    width="100%"
    height="450"
    style="border:0;"
    loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"
  ></iframe>
@endif

@if($invitation->map?->map_location_url)
  <a href="{{ $invitation->map->map_location_url }}" target="_blank" rel="noopener">
    Buka di Google Maps
  </a>
@endif
```

### 5.7 Music

```blade
@if($invitation->music?->audio?->publicUrl())
  <audio
    src="{{ $invitation->music->audio->publicUrl() }}"
    @if($invitation->music->autoplay) autoplay @endif
    @if($invitation->music->loop_audio) loop @endif
    controls
  ></audio>
@endif
```

---

## 6) Validation & Defaults (Important)

### 6.1 Avoid template breaking if data is missing
Use `?->` and fallback strings:

```blade
{{ $invitation->couple?->couple_name_1 ?? 'Nama Mempelai' }}
```

### 6.2 Ensure the “hasOne” rows exist
When creating an invitation, create the related records too:
- invitation_couple
- invitation_event_section
- invitation_map
- invitation_rsvp
- invitation_gift_section
- invitation_wish_section
- invitation_music

This prevents `null` everywhere.

---

## 7) Admin UI Strategy (Pattern A)

In your admin panel (Filament / custom):
- One **Invitation** form (base info)
- Tabs/Sections:
  - Couple
  - Bride & Groom
  - Events (+ section title + list)
  - Gallery (sortable list)
  - Map (embed src + address)
  - RSVP
  - Gifts (accounts list)
  - Wishes (samples + guestbook)
  - Music

Uploads:
- Save uploaded files into `assets` with `storage=local`, `disk=public`, `path=...`
- Link asset IDs (e.g. `photo_asset_id`) in related tables.

---

## 8) Template Compatibility Rules (Pattern A)

To keep templates interchangeable:
- **Do not invent new fields per template** unless you add proper columns/tables.
- Each template must use the canonical objects:
  - couple / people / events / gallery / map / rsvp / gifts / wishes / music
- If a template needs extra visuals, store them in `assets`:
  - `category=background|separator|frame|other`
  - then reference via a config or additional relationship if necessary.

---

## 9) Example Render Flow

1. User hits: `/inv/alin-aldi`
2. Controller loads invitation + relations (eager load)
3. Resolve view: `templates.template-1`
4. Blade reads from `$invitation` objects
5. Page renders with dynamic data

---

## 10) Quick Checklist

- [ ] `templates.code` matches the blade filename in `resources/views/templates/`
- [ ] Controller eager-loads all required relations
- [ ] All hardcoded strings/images replaced with `$invitation->...`
- [ ] Gallery/events use loops
- [ ] Use null-safe operators `?->` for optional content
- [ ] Seed/create default `hasOne` rows for new invitations
