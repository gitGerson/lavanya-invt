# Guide #2 — Public Interactions (RSVP + Guestbook/Wishes)
Laravel 12 + Pattern A + Blade Templates

This guide implements **public-side submissions** for:
- RSVP responses
- Guestbook / wishes entries

It covers:
- Routes
- Controllers + FormRequests
- DB tables (if missing)
- Spam protection (basic)
- How to show success/errors in Blade
- How to display entries in the template

> Assumption: you already have a public invitation page at `/inv/{slug}` rendered by TemplateRenderer.

---

## A) Data Model (Tables)

### A1) Guestbook / Wishes
If you already have a table like `invitation_guestbook_entries`, skip this section.

Recommended columns:
- `id`
- `invitation_id` (FK)
- `guest_name` (string)
- `guest_address` (string, nullable)
- `message` (text)
- `attendance` (enum/nullable: `yes|no|maybe`) — optional
- `ip_address` (string, nullable) — optional
- `user_agent` (string, nullable) — optional
- `created_at`, `updated_at`

### A2) RSVP responses (often NOT in schema yet)
Your `invitation_rsvps` table is usually **section config** (title/subtitle/message), not responses.

Create a new table: `invitation_rsvp_responses`

**Migration:**
`database/migrations/xxxx_xx_xx_xxxxxx_create_invitation_rsvp_responses_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invitation_rsvp_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();

            $table->string('guest_name', 150);
            $table->string('phone', 50)->nullable();
            $table->enum('attendance', ['yes', 'no', 'maybe'])->default('yes');
            $table->unsignedSmallInteger('pax')->default(1); // number of attendees
            $table->text('note')->nullable();

            // anti-abuse / audit
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['invitation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_rsvp_responses');
    }
};
```

**Model:**
`app/Models/InvitationRsvpResponse.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationRsvpResponse extends Model
{
    protected $fillable = [
        'invitation_id',
        'guest_name',
        'phone',
        'attendance',
        'pax',
        'note',
        'ip_address',
        'user_agent',
    ];

    public function invitation()
    {
        return $this->belongsTo(Invitation::class);
    }
}
```

**Relation in `Invitation` model:**
```php
public function rsvpResponses()
{
    return $this->hasMany(InvitationRsvpResponse::class);
}
```

---

## B) Routes

`routes/web.php`

```php
use App\Http\Controllers\InvitationInteractionController;

Route::post('/inv/{slug}/rsvp', [InvitationInteractionController::class, 'storeRsvp'])
    ->name('invitation.rsvp.store')
    ->middleware(['throttle:20,1']); // 20 requests / minute

Route::post('/inv/{slug}/guestbook', [InvitationInteractionController::class, 'storeGuestbook'])
    ->name('invitation.guestbook.store')
    ->middleware(['throttle:20,1']);
```

> You can tune throttling based on your traffic.

---

## C) Controller

Create: `app/Http/Controllers/InvitationInteractionController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestbookRequest;
use App\Http\Requests\StoreRsvpRequest;
use App\Models\Invitation;
use App\Models\InvitationGuestbookEntry;
use App\Models\InvitationRsvpResponse;
use Illuminate\Http\RedirectResponse;

class InvitationInteractionController extends Controller
{
    protected function findPublishedInvitation(string $slug): Invitation
    {
        return Invitation::query()
            ->where('slug', $slug)
            ->where('status', 'published') // public interaction only for published
            ->firstOrFail();
    }

    public function storeGuestbook(StoreGuestbookRequest $request, string $slug): RedirectResponse
    {
        $invitation = $this->findPublishedInvitation($slug);

        InvitationGuestbookEntry::create([
            'invitation_id' => $invitation->id,
            'guest_name' => $request->string('guest_name'),
            'guest_address' => $request->string('guest_address')->toString() ?: null,
            'message' => $request->string('message'),
            'attendance' => $request->input('attendance'), // optional

            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return back()->with('success', 'Terima kasih! Ucapanmu sudah terkirim.');
    }

    public function storeRsvp(StoreRsvpRequest $request, string $slug): RedirectResponse
    {
        $invitation = $this->findPublishedInvitation($slug);

        InvitationRsvpResponse::create([
            'invitation_id' => $invitation->id,
            'guest_name' => $request->string('guest_name'),
            'phone' => $request->input('phone'),
            'attendance' => $request->input('attendance', 'yes'),
            'pax' => (int) $request->input('pax', 1),
            'note' => $request->input('note'),

            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return back()->with('success', 'RSVP kamu sudah tersimpan. Terima kasih!');
    }
}
```

---

## D) Form Requests (Validation + Honeypot)

### D1) Honeypot approach (simple)
Add one hidden input in forms: `website` (must remain empty).
We validate it as `max:0` to catch bots.

Create: `app/Http/Requests/StoreGuestbookRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:150'],
            'guest_address' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'attendance' => ['nullable', 'in:yes,no,maybe'],

            // honeypot (hidden field)
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }
}
```

Create: `app/Http/Requests/StoreRsvpRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'attendance' => ['required', 'in:yes,no,maybe'],
            'pax' => ['required', 'integer', 'min:1', 'max:10'],
            'note' => ['nullable', 'string', 'max:1000'],

            // honeypot
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }
}
```

---

## E) Blade: Forms + Showing Messages

### E1) Flash message block (once per page)
Put near top of template:

```blade
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
  <div class="alert alert-danger">
    <ul>
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
```

### E2) Guestbook form

```blade
<form method="POST" action="{{ route('invitation.guestbook.store', $invitation->slug) }}">
  @csrf

  <input type="text" name="guest_name" placeholder="Nama" required>
  <input type="text" name="guest_address" placeholder="Alamat (opsional)">

  <textarea name="message" placeholder="Tulis ucapan..." required></textarea>

  <select name="attendance">
    <option value="">Kehadiran (opsional)</option>
    <option value="yes">Hadir</option>
    <option value="no">Tidak</option>
    <option value="maybe">Mungkin</option>
  </select>

  {{-- Honeypot --}}
  <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

  <button type="submit">Kirim Ucapan</button>
</form>
```

### E3) RSVP form

```blade
<form method="POST" action="{{ route('invitation.rsvp.store', $invitation->slug) }}">
  @csrf

  <input type="text" name="guest_name" placeholder="Nama" required>
  <input type="text" name="phone" placeholder="No. HP (opsional)">

  <select name="attendance" required>
    <option value="yes">Hadir</option>
    <option value="no">Tidak Hadir</option>
    <option value="maybe">Mungkin</option>
  </select>

  <input type="number" name="pax" value="1" min="1" max="10" required>

  <textarea name="note" placeholder="Catatan (opsional)"></textarea>

  {{-- Honeypot --}}
  <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

  <button type="submit">Kirim RSVP</button>
</form>
```

---

## F) Display Data in Template

### F1) Show guestbook entries (latest 10)
If TemplateRenderer already loads `guestbookEntries`, just loop:

```blade
@php
  $entries = $invitation->guestbookEntries->sortByDesc('created_at')->take(10);
@endphp

@forelse($entries as $entry)
  <div class="guestbook-item">
    <strong>{{ $entry->guest_name }}</strong>
    @if($entry->guest_address)<span> • {{ $entry->guest_address }}</span>@endif
    <div>{{ $entry->message }}</div>
    <small>{{ $entry->created_at?->format('d M Y H:i') }}</small>
  </div>
@empty
  <div class="text-sm opacity-70">Belum ada ucapan.</div>
@endforelse
```

### F2) Show RSVP summary (optional)
If you created `rsvpResponses()` relation and eager-loaded it in renderer, you can show totals:

```blade
@php
  $yes = $invitation->rsvpResponses->where('attendance','yes')->sum('pax');
  $no = $invitation->rsvpResponses->where('attendance','no')->count();
@endphp

<div>Total hadir: {{ $yes }}</div>
<div>Tidak hadir: {{ $no }}</div>
```

---

## G) Production Notes

1) **Only accept submissions for published invitations**
- enforced in controller (`status = published`)

2) **Rate limit**
- already done via `throttle:20,1`

3) **Spam**
- honeypot in requests
- optionally add minimum time-to-submit (timestamp hidden field) later

4) **Privacy**
- consider truncating user agent / not storing phone if not needed

---

## H) Next optional upgrades
- AJAX submit + live update guestbook without refresh
- Websockets (if you want live guestbook)
- reCAPTCHA / Turnstile
- moderation flag (`is_approved`) for guestbook
