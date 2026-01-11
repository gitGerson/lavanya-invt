# Guide (#5) — Publish Validation To-Do (Checklist + Patch Plan)
Laravel 12 + Filament v4 + Pattern A

Goal:
- Prevent “Published” invitations that render broken pages
- Provide a systematic checklist
- Provide patch actions inside Filament (validate + fix)

This guide is a **to-do guide**: what to check + how to patch.

---

## A) Minimum Data Checklist (Recommended)

### 1) Invitation base
- [ ] `template_id` set
- [ ] `slug` unique and not empty
- [ ] `timezone` set (default OK)

Patch:
- enforce required fields in Filament form

### 2) Couple
- [ ] `couple_name_1` not empty
- [ ] `couple_name_2` not empty
- [ ] `wedding_date_display` filled (or normalized date)
- [ ] couple image asset exists (optional but recommended)

Patch:
- show validation error when publish
- add placeholder fallback in template

### 3) Bride & Groom
- [ ] bride row exists (`role=bride`)
- [ ] groom row exists (`role=groom`)
- [ ] bride name, groom name filled
- [ ] photos optional (but recommended)

Patch:
- auto-create rows on invitation create (you already do)
- block publish if names missing

### 4) Events
- [ ] at least 1 event exists
- [ ] event title not empty
- [ ] event date/time display OR normalized date/time exists
- [ ] location text filled
- [ ] map link exists (either per event or default)

Patch:
- ensure repeater has at least 1 item
- add “default location url” in eventSection

### 5) Map
- [ ] map address filled (recommended)
- [ ] either embed src OR location url exists

Patch:
- allow map section to hide (future guide #4)
- or require it if template requires

### 6) RSVP / Guestbook / Gifts / Gallery / Music
These are template-dependent:
- [ ] RSVP section config exists (title/subtitle optional)
- [ ] Gift accounts optional but if gift section enabled: require at least 1 account
- [ ] Gallery optional but if template expects: require >= 1 image
- [ ] Music optional but if template uses: require audio

Patch:
- create templateRules in TemplateRenderer
- add publish validation based on template code

---

## B) Implement “Publish Guard” (Filament action)

Approach:
- Change status via a custom action “Publish”
- Action runs validation checks first
- If fail: show notification with missing items

### B1) Add a custom action on Edit page

`app/Filament/Resources/InvitationResource/Pages/EditInvitation.php`

```php
use Filament\Actions\Action;
use Filament\Notifications\Notification;

protected function getHeaderActions(): array
{
    return [
        Action::make('publish')
            ->label('Publish')
            ->requiresConfirmation()
            ->action(function () {
                $missing = $this->validatePublishReady($this->record);

                if (!empty($missing)) {
                    Notification::make()
                        ->title('Cannot publish: incomplete data')
                        ->body("Missing: " . implode(', ', $missing))
                        ->danger()
                        ->send();

                    return;
                }

                $this->record->update(['status' => 'published']);

                Notification::make()
                    ->title('Published')
                    ->success()
                    ->send();
            }),
    ];
}
```

### B2) Implement `validatePublishReady()`

```php
protected function validatePublishReady($invitation): array
{
    $invitation->loadMissing([
        'template',
        'couple',
        'people',
        'eventSection',
        'events',
        'map',
        'giftAccounts',
        'galleryItems',
        'music',
    ]);

    $missing = [];

    if (!$invitation->template) $missing[] = 'template';
    if (!$invitation->slug) $missing[] = 'slug';

    if (!$invitation->couple?->couple_name_1) $missing[] = 'couple_name_1';
    if (!$invitation->couple?->couple_name_2) $missing[] = 'couple_name_2';

    $bride = $invitation->people->firstWhere('role','bride');
    $groom = $invitation->people->firstWhere('role','groom');
    if (!$bride?->name) $missing[] = 'bride_name';
    if (!$groom?->name) $missing[] = 'groom_name';

    if ($invitation->events->isEmpty()) $missing[] = 'events';

    // Minimal map requirement (if your templates need it)
    if (!($invitation->map?->map_embed_src || $invitation->map?->map_location_url)) {
        $missing[] = 'map';
    }

    // Add template-dependent checks later (future guide #4)
    return $missing;
}
```

---

## C) Patch Plan (How to fix fast)

1) Missing couple names → Fill in Filament > Couple tab
2) Missing bride/groom names → Fill in Filament > Bride/Groom section
3) No events → Add at least 1 event
4) Map missing → Fill embed src or google maps link
5) Gifts missing (if required by template) → Add bank account + QR
6) Gallery missing (if required) → Add gallery images
7) Music missing (if required) → Upload audio / set asset

---

## D) “Ready to publish” indicator (optional)
Add a computed badge in table:
- Ready ✅ / Not Ready ❌

Implementation idea:
- compute missing count quickly in a model accessor or query scope
- show badge color in Filament table

---

## E) Keep templates safe even if incomplete
Even with publish validation, still add Blade fallbacks:
- null-safe `?->`
- `@forelse` for lists
- placeholder text

This prevents edge cases from breaking the page.
