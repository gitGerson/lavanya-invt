# Guide — Generate Seeder for Invitations (Pattern A, Structured DB)
Laravel 12 + Filament v4 + Dynamic Templates

This guide gives you a **complete, repeatable** way to seed:
- Templates
- Invitations
- All Pattern A relations (hasOne + hasMany)
- Optional: assets (images/QR/audio), gallery, gift accounts
- Optional: guestbook entries + RSVP responses (public submissions tables)

The goal is: after `php artisan migrate:fresh --seed`, you can immediately open:
- `/inv/{slug}` (published)
- `/preview/inv/{slug}` (draft)

---

## 0) Assumptions / Model names

Adjust names if your project differs.

Models (examples):
- `Template`
- `Invitation`
- `Asset`
- `InvitationCouple`
- `InvitationPerson`
- `InvitationEventSection`
- `InvitationEvent`
- `InvitationGalleryItem`
- `InvitationMap`
- `InvitationRsvp` (section config)
- `InvitationGiftSection`
- `InvitationGiftAccount`
- `InvitationWishSection`
- `InvitationWishSample`
- `InvitationGuestbookEntry`
- `InvitationMusic`
- `InvitationRsvpResponse` (responses table from Guide #2)

Relationships (examples):
- `Invitation::template()`
- `Invitation::couple()`, `people()`, `eventSection()`, `events()`, `galleryItems()`, `map()`, `rsvp()`
- `Invitation::giftSection()`, `giftAccounts()`, `wishSection()`, `wishSamples()`, `guestbookEntries()`, `music()`
- Each *_asset_id points to `assets.id`

---

## 1) Create Factories (Recommended)

Using factories makes seeders clean.

Generate factories:

```bash
php artisan make:factory TemplateFactory --model=Template
php artisan make:factory InvitationFactory --model=Invitation
php artisan make:factory AssetFactory --model=Asset
php artisan make:factory InvitationEventFactory --model=InvitationEvent
php artisan make:factory InvitationGalleryItemFactory --model=InvitationGalleryItem
php artisan make:factory InvitationGiftAccountFactory --model=InvitationGiftAccount
php artisan make:factory InvitationGuestbookEntryFactory --model=InvitationGuestbookEntry
php artisan make:factory InvitationRsvpResponseFactory --model=InvitationRsvpResponse
```

> For hasOne tables, you can either create factories OR create records directly inside a seeder.  
> In this guide: we create hasOne rows directly in the seeder (simpler).

---

## 2) TemplateFactory

`database/factories/TemplateFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => Str::title($name),
            'code' => 'template-' . $this->faker->unique()->numberBetween(1, 20),
            'status' => 'active',
            'description' => $this->faker->sentence(),
        ];
    }
}
```

You can override codes to match your real blade files later.

---

## 3) InvitationFactory

`database/factories/InvitationFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        $slug = Str::slug($this->faker->unique()->words(2, true)) . '-' . $this->faker->numberBetween(10, 999);

        return [
            'template_id' => Template::query()->inRandomOrder()->value('id'),
            'slug' => $slug,
            'title' => Str::title(str_replace('-', ' ', $slug)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'status' => $this->faker->randomElement(['draft', 'published']),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
```

---

## 4) AssetFactory (URL-based or local path)

`database/factories/AssetFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'kind' => 'image', // image|audio|video|file
            'category' => 'seed',
            'storage' => 'url', // url|local
            'disk' => null,      // e.g. public
            'path' => null,      // storage path for local
            'url' => $this->faker->imageUrl(1200, 800, 'people', true),
            'alt_text' => $this->faker->sentence(3),
        ];
    }

    public function audioUrl(): static
    {
        return $this->state(fn () => [
            'kind' => 'audio',
            'category' => 'music',
            'storage' => 'url',
            'url' => 'https://example.com/sample.mp3', // replace if needed
        ]);
    }

    public function qrUrl(): static
    {
        return $this->state(fn () => [
            'kind' => 'image',
            'category' => 'qr',
            'storage' => 'url',
            'url' => $this->faker->imageUrl(600, 600, 'abstract', true),
        ]);
    }
}
```

> In dev you can keep using URLs. If you want local assets, set `storage=local`, `disk=public`, `path=...`.

---

## 5) Create the Seeder: TemplatesSeeder

`php artisan make:seeder TemplatesSeeder`

`database/seeders/TemplatesSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // IMPORTANT: codes must match blade files in resources/views/templates/{code}.blade.php
        $templates = [
            ['name' => 'Template 1', 'code' => 'template-1', 'status' => 'active', 'description' => 'Default template 1'],
            // add more when you have them:
            // ['name' => 'Template 2', 'code' => 'template-2', ...],
        ];

        foreach ($templates as $t) {
            Template::query()->updateOrCreate(['code' => $t['code']], $t);
        }
    }
}
```

---

## 6) Create the Seeder: InvitationsSeeder (Pattern A complete)

Run:
```bash
php artisan make:seeder InvitationsSeeder
```

`database/seeders/InvitationsSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Invitation;
use App\Models\InvitationCouple;
use App\Models\InvitationEvent;
use App\Models\InvitationEventSection;
use App\Models\InvitationGalleryItem;
use App\Models\InvitationGiftAccount;
use App\Models\InvitationGiftSection;
use App\Models\InvitationGuestbookEntry;
use App\Models\InvitationMap;
use App\Models\InvitationMusic;
use App\Models\InvitationPerson;
use App\Models\InvitationRsvp;
use App\Models\InvitationRsvpResponse;
use App\Models\InvitationWishSection;
use App\Models\InvitationWishSample;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvitationsSeeder extends Seeder
{
    public function run(): void
    {
        $templateId = Template::query()->where('code', 'template-1')->value('id');

        // Create a few invitations: 2 published, 1 draft
        $records = [
            ['slug' => 'alin-aldi', 'status' => 'published', 'title' => 'Alin & Aldi Wedding'],
            ['slug' => 'rani-rizky', 'status' => 'published', 'title' => 'Rani & Rizky Wedding'],
            ['slug' => 'preview-sample', 'status' => 'draft', 'title' => 'Preview Sample Wedding'],
        ];

        foreach ($records as $r) {
            $invitation = Invitation::query()->updateOrCreate(
                ['slug' => $r['slug']],
                [
                    'template_id' => $templateId,
                    'title' => $r['title'],
                    'timezone' => 'Asia/Jakarta',
                    'locale' => 'id',
                    'status' => $r['status'],
                ]
            );

            // ---- Assets ----
            $coupleImage = Asset::factory()->create([
                'category' => 'couple_image',
                'kind' => 'image',
                'storage' => 'url',
                'url' => "https://picsum.photos/seed/{$invitation->slug}-couple/1200/800",
            ]);

            $bridePhoto = Asset::factory()->create([
                'category' => 'person_photo',
                'kind' => 'image',
                'storage' => 'url',
                'url' => "https://picsum.photos/seed/{$invitation->slug}-bride/900/900",
            ]);

            $groomPhoto = Asset::factory()->create([
                'category' => 'person_photo',
                'kind' => 'image',
                'storage' => 'url',
                'url' => "https://picsum.photos/seed/{$invitation->slug}-groom/900/900",
            ]);

            $musicAudio = Asset::factory()->create([
                'category' => 'music',
                'kind' => 'audio',
                'storage' => 'url',
                'url' => 'https://example.com/sample.mp3',
            ]);

            // ---- hasOne rows ----
            InvitationCouple::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'couple_tagline' => 'The Wedding of',
                    'couple_name_1' => Str::title(explode('-', $invitation->slug)[0]),
                    'couple_name_2' => Str::title(explode('-', $invitation->slug)[1] ?? 'Partner'),
                    'wedding_date_display' => 'Sabtu, 20 Januari 2026',
                    'couple_image_asset_id' => $coupleImage->id,
                ]
            );

            InvitationEventSection::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'section_title' => 'Acara',
                    'default_location_url' => 'https://maps.google.com/?q=Jakarta',
                ]
            );

            InvitationMap::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'map_section_title' => 'Lokasi',
                    'map_address' => 'Jakarta, Indonesia',
                    'map_location_url' => 'https://maps.google.com/?q=Jakarta',
                    'map_embed_src' => 'https://www.google.com/maps?q=Jakarta&output=embed',
                ]
            );

            InvitationRsvp::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'rsvp_title' => 'RSVP',
                    'rsvp_subtitle' => 'Konfirmasi Kehadiran',
                    'rsvp_message' => 'Mohon konfirmasi kehadiran Anda.',
                    'rsvp_hosts' => 'Keluarga Besar Mempelai',
                ]
            );

            InvitationGiftSection::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'gift_title' => 'Wedding Gift',
                    'gift_subtitle' => 'Doa restu Anda sangat berarti.',
                ]
            );

            InvitationWishSection::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'wish_title' => 'Ucapan & Doa',
                ]
            );

            InvitationMusic::query()->updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'audio_asset_id' => $musicAudio->id,
                    'autoplay' => true,
                    'loop_audio' => true,
                ]
            );

            // ---- People (bride & groom) ----
            InvitationPerson::query()->updateOrCreate(
                ['invitation_id' => $invitation->id, 'role' => 'bride'],
                [
                    'name' => 'Alin',
                    'title' => 'Putri Pertama',
                    'father_name' => 'Bapak Bride',
                    'mother_name' => 'Ibu Bride',
                    'instagram_handle' => '@bride',
                    'photo_asset_id' => $bridePhoto->id,
                ]
            );

            InvitationPerson::query()->updateOrCreate(
                ['invitation_id' => $invitation->id, 'role' => 'groom'],
                [
                    'name' => 'Aldi',
                    'title' => 'Putra Pertama',
                    'father_name' => 'Bapak Groom',
                    'mother_name' => 'Ibu Groom',
                    'instagram_handle' => '@groom',
                    'photo_asset_id' => $groomPhoto->id,
                ]
            );

            // ---- Events (2 items) ----
            InvitationEvent::query()->updateOrCreate(
                ['invitation_id' => $invitation->id, 'sort_order' => 1],
                [
                    'title' => 'Akad Nikah',
                    'event_date_display' => 'Sabtu, 20 Januari 2026',
                    'event_time_display' => '08:00 - 10:00',
                    'location_text' => 'Masjid Utama, Jakarta',
                    'location_url' => 'https://maps.google.com/?q=Masjid+Jakarta',
                ]
            );

            InvitationEvent::query()->updateOrCreate(
                ['invitation_id' => $invitation->id, 'sort_order' => 2],
                [
                    'title' => 'Resepsi',
                    'event_date_display' => 'Sabtu, 20 Januari 2026',
                    'event_time_display' => '11:00 - 14:00',
                    'location_text' => 'Gedung Serbaguna, Jakarta',
                    'location_url' => 'https://maps.google.com/?q=Gedung+Jakarta',
                ]
            );

            // ---- Gallery (6 images) ----
            // Create assets + link them into gallery items
            InvitationGalleryItem::query()->where('invitation_id', $invitation->id)->delete();

            for ($i = 1; $i <= 6; $i++) {
                $img = Asset::factory()->create([
                    'category' => 'gallery_image',
                    'kind' => 'image',
                    'storage' => 'url',
                    'url' => "https://picsum.photos/seed/{$invitation->slug}-gallery-{$i}/1200/800",
                ]);

                InvitationGalleryItem::query()->create([
                    'invitation_id' => $invitation->id,
                    'image_asset_id' => $img->id,
                    'sort_order' => $i,
                ]);
            }

            // ---- Gifts (2 accounts) ----
            InvitationGiftAccount::query()->where('invitation_id', $invitation->id)->delete();

            $qr1 = Asset::factory()->qrUrl()->create();
            $qr2 = Asset::factory()->qrUrl()->create();

            InvitationGiftAccount::query()->create([
                'invitation_id' => $invitation->id,
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder' => 'Alin',
                'qr_asset_id' => $qr1->id,
                'sort_order' => 1,
            ]);

            InvitationGiftAccount::query()->create([
                'invitation_id' => $invitation->id,
                'bank_name' => 'Mandiri',
                'account_number' => '9876543210',
                'account_holder' => 'Aldi',
                'qr_asset_id' => $qr2->id,
                'sort_order' => 2,
            ]);

            // ---- Wish samples (optional; shown before real guestbook entries) ----
            InvitationWishSample::query()->where('invitation_id', $invitation->id)->delete();

            InvitationWishSample::query()->create([
                'invitation_id' => $invitation->id,
                'name' => 'Sahabat',
                'address' => 'Jakarta',
                'message' => 'Selamat menempuh hidup baru, semoga bahagia selalu!',
                'sort_order' => 1,
            ]);

            InvitationWishSample::query()->create([
                'invitation_id' => $invitation->id,
                'name' => 'Keluarga',
                'address' => 'Bandung',
                'message' => 'Semoga menjadi keluarga sakinah mawaddah warahmah.',
                'sort_order' => 2,
            ]);

            // ---- Guestbook entries (real public-like data) ----
            InvitationGuestbookEntry::query()->where('invitation_id', $invitation->id)->delete();

            for ($i = 1; $i <= 5; $i++) {
                InvitationGuestbookEntry::query()->create([
                    'invitation_id' => $invitation->id,
                    'guest_name' => "Tamu {$i}",
                    'guest_address' => 'Indonesia',
                    'message' => "Ucapan ke-{$i}: Semoga lancar sampai hari H!",
                    'attendance' => 'yes',
                ]);
            }

            // ---- RSVP responses (if you created responses table) ----
            // If model/table doesn't exist in your project, remove this block.
            if (class_exists(InvitationRsvpResponse::class)) {
                InvitationRsvpResponse::query()->where('invitation_id', $invitation->id)->delete();

                InvitationRsvpResponse::query()->create([
                    'invitation_id' => $invitation->id,
                    'guest_name' => 'Budi',
                    'phone' => '08123456789',
                    'attendance' => 'yes',
                    'pax' => 2,
                    'note' => 'Sampai jumpa di acara!',
                ]);

                InvitationRsvpResponse::query()->create([
                    'invitation_id' => $invitation->id,
                    'guest_name' => 'Sari',
                    'phone' => '08987654321',
                    'attendance' => 'maybe',
                    'pax' => 1,
                    'note' => 'Akan konfirmasi lagi.',
                ]);
            }
        }
    }
}
```

---

## 7) Register Seeders in DatabaseSeeder

Edit: `database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TemplatesSeeder::class,
            InvitationsSeeder::class,
        ]);
    }
}
```

---

## 8) Run Seeds

```bash
php artisan migrate:fresh --seed
```

Test:
- open `/inv/alin-aldi` (published)
- open `/preview/inv/preview-sample` (draft) (requires auth if you protected preview route)

---

## 9) Tips & Common Pitfalls

### 9.1 Template code must match blade filename
If blade is `resources/views/templates/template-1.blade.php`, then:
- `templates.code = template-1`

### 9.2 Slug uniqueness
Make sure seeders use unique slugs; use `updateOrCreate` to re-run safely.

### 9.3 Avoid duplicates for hasMany
For gallery/gifts/guestbook/RSVP responses, it’s common to:
- delete old rows first (safe for seeding)
- then insert fresh

### 9.4 URLs vs local assets
- URL seeding is easiest
- Local seeding needs files placed in `storage/app/public/...` and `storage:link`

---

## 10) Optional: Add Filament “Seed Demo Data” Button (Later)

If you want a Filament action that seeds demo data per invitation, you can create a service method that:
- takes an invitation id
- creates missing rows + sample assets/events/gallery
This is useful for quickly testing new templates.

---

## 11) Minimal “Smoke Test” Checklist

After seeding:
- [ ] `/inv/{slug}` loads without errors
- [ ] couple names render
- [ ] bride/groom render with photos
- [ ] events appear (2)
- [ ] gallery shows images (6)
- [ ] map section loads iframe
- [ ] gifts show bank accounts + QR
- [ ] guestbook shows entries
- [ ] music loads (if template uses it)
