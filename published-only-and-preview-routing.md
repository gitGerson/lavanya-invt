# Guide #3 — Published-only Public View + Admin Preview Route
Laravel 12 + TemplateRenderer + Filament v4

Goal:
- Public route `/inv/{slug}` shows **published only**
- Admin preview route `/preview/inv/{slug}` shows **draft + published**
- Preview is protected (only logged-in admins)

This guide assumes you already have `TemplateRenderer` and a public controller.

---

## A) Update TemplateRenderer to support “modes”

Instead of hardcoding statuses, add **two methods**:
- `renderPublicBySlug()` → published only
- `renderPreviewBySlug()` → draft + published

Edit: `app/Services/TemplateRenderer.php`

### A1) Add these methods

```php
public function renderPublicBySlug(string $slug, bool $useCache = true)
{
    return $this->renderBySlugWithStatuses($slug, ['published'], $useCache);
}

public function renderPreviewBySlug(string $slug, bool $useCache = false)
{
    // typically disable cache for preview to always reflect latest edits
    return $this->renderBySlugWithStatuses($slug, ['draft', 'published'], $useCache);
}
```

### A2) Add a generic helper

```php
public function renderBySlugWithStatuses(string $slug, array $statuses, bool $useCache = true)
{
    $invitation = $this->loadBySlugAndStatuses($slug, $statuses);
    return $this->renderInvitation($invitation, $useCache);
}

public function loadBySlugAndStatuses(string $slug, array $statuses): Invitation
{
    $invitation = Invitation::query()
        ->where('slug', $slug)
        ->whereIn('status', $statuses)
        ->with($this->relations())
        ->firstOrFail();

    return $invitation;
}
```

> After this change, you can remove/ignore `allowedStatuses` property if you want.

---

## B) Public Controller vs Preview Controller

### B1) Public controller: published only
`app/Http/Controllers/InvitationPublicController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\TemplateRenderer;

class InvitationPublicController extends Controller
{
    public function show(string $slug, TemplateRenderer $renderer)
    {
        return $renderer->renderPublicBySlug($slug, useCache: true);
    }
}
```

### B2) Preview controller: draft + published
Create: `app/Http/Controllers/InvitationPreviewController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\TemplateRenderer;

class InvitationPreviewController extends Controller
{
    public function show(string $slug, TemplateRenderer $renderer)
    {
        return $renderer->renderPreviewBySlug($slug, useCache: false);
    }
}
```

---

## C) Routes

`routes/web.php`

```php
use App\Http\Controllers\InvitationPublicController;
use App\Http\Controllers\InvitationPreviewController;

// Public - published only
Route::get('/inv/{slug}', [InvitationPublicController::class, 'show'])
    ->name('invitation.show');

// Preview - admins only
Route::get('/preview/inv/{slug}', [InvitationPreviewController::class, 'show'])
    ->name('invitation.preview')
    ->middleware(['auth']);
```

### C1) About the middleware choice (Filament)
Most Filament setups authenticate with the **web guard**, so protecting preview routes with `auth` is typically enough, but make sure your app has a proper login route configured. citeturn0search0

If your project does not have a Laravel `login` route (because Filament handles auth routes), you may need to ensure your authentication redirect points to Filament’s login page. citeturn0search0turn0search5

---

## D) Add Preview Button in Filament Table

In `InvitationResource::table()` actions:

```php
Tables\Actions\Action::make('preview')
  ->label('Preview')
  ->url(fn ($record) => route('invitation.preview', $record->slug))
  ->openUrlInNewTab(),
```

Add also a “Public View” button:

```php
Tables\Actions\Action::make('public')
  ->label('Public')
  ->url(fn ($record) => route('invitation.show', $record->slug))
  ->openUrlInNewTab(),
```

---

## E) Optional: show Draft watermark in preview

In TemplateRenderer payload, add:

```php
'isPreview' => true,
```

Then in preview controller pass it, or add a method to merge payload.

In Blade:

```blade
@if(!empty($isPreview))
  <div class="fixed top-4 right-4 z-50 px-3 py-1 bg-yellow-300 text-black rounded">
    PREVIEW
  </div>
@endif
```

---

## F) Optional: prevent indexing preview URLs
Add meta:

```blade
@if(!empty($isPreview))
  <meta name="robots" content="noindex,nofollow">
@endif
```

---

## G) Checklist
- [ ] Public route only shows published
- [ ] Preview route shows draft + published and is auth-protected
- [ ] Filament “Preview” action points to preview route
- [ ] Preview does not cache (recommended)
