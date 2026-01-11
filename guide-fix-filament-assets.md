# Guide Fix: Upload Filament Tersimpan ke DB (Assets + *_asset_id)

Dokumen ini menjelaskan cara **memperbaiki kasus file sudah ter‐upload ke storage, tapi kolom DB tetap `NULL`** pada resource Invitation kamu (Filament).  
Kasus spesifik: `invitation_couple.couple_image_asset_id` tetap `NULL`, padahal file sudah tersimpan di `storage/app/public/...`.

---

## 0) Ringkasan Akar Masalah

Kamu memakai:

```php
FileUpload::make('couple_image')
    ->dehydrated(false);
```

`dehydrated(false)` artinya **nilai field upload tidak ikut masuk ke payload yang disimpan Filament**.  
Akibatnya, walaupun file sukses tersimpan di disk, **kolom DB yang kamu harapkan terisi tidak pernah di-update**.

Karena skema DB kamu menyimpan **FK ke tabel `assets`** (`couple_image_asset_id`), maka kamu perlu:

1) Upload file ke disk (public)  
2) Buat row `assets` (storage=local, disk=public, path=...)  
3) Set `couple_image_asset_id` = `assets.id`

---

## 1) Target Implementasi

Alur yang benar:

- User upload → file tersimpan: `storage/app/public/invitations/xxx.jpg`
- Buat row `assets`:

  - `invitation_id` = ID Invitation yang sedang diedit
  - `storage` = `local`
  - `disk` = `public`
  - `path` = `invitations/xxx.jpg`
  - `kind` = `image`
  - `category` = `section_image`

- Update row `invitation_couple`:

  - `couple_image_asset_id` = ID asset di atas

---

## 2) Patch: Step "Couple" (Wajib)

### 2.1 Import yang dibutuhkan

Tambahkan di file schema:

```php
use App\Models\Asset;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;
```

### 2.2 Tambahkan Hidden untuk FK asset

Di dalam `Section::make('Couple')->relationship('couple')`, tambahkan:

```php
Hidden::make('couple_image_asset_id'),
```

> Hidden ini yang akan benar-benar tersimpan ke tabel `invitation_couple`.

### 2.3 Tambahkan handler upload → create Asset → set FK

Gunakan patch berikut (copy-paste bagian Couple section):

```php
Section::make('Couple')
    ->relationship('couple')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('couple_tagline')->maxLength(255),
            TextInput::make('wedding_date_display')->maxLength(150),
        ]),
        Grid::make(2)->schema([
            TextInput::make('couple_name_1')->maxLength(150),
            TextInput::make('couple_name_2')->maxLength(150),
        ]),

        // ✅ tersimpan ke DB
        Hidden::make('couple_image_asset_id'),

        // ✅ upload UI (file ke disk), tapi value tidak disimpan langsung
        FileUpload::make('couple_image')
            ->label('Couple Image')
            ->disk('public')
            ->directory('invitations')
            ->image()
            ->dehydrated(false)

            // ✅ saat upload berubah: buat Asset + set FK
            ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                // kalau user clear file
                if (blank($state)) {
                    $set('couple_image_asset_id', null);
                    return;
                }

                // state bisa string path atau array
                $path = is_array($state) ? ($state[0] ?? null) : $state;
                if (! $path || ! is_string($path)) return;

                // Invitation record adalah parent resource
                $invitationId = $livewire->getRecord()?->id;

                $asset = Asset::firstOrCreate(
                    [
                        'invitation_id' => $invitationId,
                        'storage'       => 'local',
                        'disk'          => 'public',
                        'path'          => $path,
                    ],
                    [
                        'kind'     => 'image',
                        'category' => 'section_image',
                        'url'      => null,
                        'mime'     => null,
                        'alt_text' => null,
                        'meta'     => null,
                    ]
                );

                $set('couple_image_asset_id', $asset->id);
            }),
    ]);
```

---

## 3) Preview Saat Edit (Opsional, tapi disarankan)

Agar saat edit form, FileUpload menampilkan file yang sudah tersimpan, tambahkan hook berikut ke FileUpload:

```php
->afterStateHydrated(function (FileUpload $component, $state, $record) {
    if (! $record?->couple_image_asset_id) return;

    $asset = \App\Models\Asset::find($record->couple_image_asset_id);

    if ($asset?->storage === 'local' && $asset->disk === 'public' && $asset->path) {
        // FileUpload preview membutuhkan path string
        $component->state($asset->path);
    }
})
```

---

## 4) Pastikan Model InvitationCouple Bisa Menyimpan FK

Di model `InvitationCouple` pastikan tidak memblok `couple_image_asset_id`:

```php
protected $fillable = [
  'invitation_id',
  'couple_tagline',
  'couple_name_1',
  'couple_name_2',
  'wedding_date_display',
  'couple_image_asset_id',
];
```

Atau gunakan:

```php
protected $guarded = [];
```

---

## 5) Cara Test Cepat

1. Buka Filament Invitation → Step Couple
2. Upload image
3. Klik Save/Next sampai tersimpan
4. Cek DB:

### 5.1 `assets` harus ada row baru
- `invitation_id` = id invitation
- `storage` = `local`
- `disk` = `public`
- `path` = `invitations/...`
- `kind` = `image`
- `category` = `section_image`

### 5.2 `invitation_couple` harus terisi
- `couple_image_asset_id` **tidak null** dan mengarah ke row `assets` di atas

---

## 6) Terapkan Pola yang Sama untuk Upload Lain (Gallery / QR / Music / People)

Karena kamu juga menulis:

```php
->dehydrated(false)
```

pada:
- `galleryItems.image`
- `giftAccounts.qr_image`
- `music.music_audio`
- `bride_photo / groom_photo`

Maka **semua itu juga akan kosong di DB** kecuali kamu:
1) punya kolom FK asset di tabel masing-masing (mis. `image_asset_id`, `qr_asset_id`, `music_asset_id`, dst.)
2) tambah `Hidden::make('..._asset_id')`
3) tambah `afterStateUpdated(...)` untuk membuat Asset dan set FK.

### Mapping category yang disarankan
- Couple image: `section_image`
- Gallery image: `gallery_image`
- Music audio: `music`
- QR image: `other` atau buat category baru `qr` (kalau mau rapi)
- Bride/Groom photo: `section_image` (atau `other`)

---

## 7) Debug Checklist (Kalau masih NULL)

Kalau `couple_image_asset_id` masih `NULL`, cek:

1. Hidden field `couple_image_asset_id` sudah ditambahkan?
2. Closure `afterStateUpdated` kepanggil? (tambahkan log)
   ```php
   logger()->info('afterStateUpdated couple_image', ['state' => $state]);
   ```
3. `$livewire->getRecord()?->id` ada? (harusnya ada pada edit/create Invitation)
4. Model `InvitationCouple` fillable/guarded benar?
5. Relationship `->relationship('couple')` benar mengarah ke `invitation_couple`?

---

## 8) (Opsional) Lengkapi Kolom `mime`

Kalau kamu ingin `mime` terisi, ada dua opsi:

### Opsi A: Biarkan null (paling simpel)
Tidak masalah karena kolom nullable.

### Opsi B: Isi mime via file lokal
Di closure, setelah `$path` didapat:

```php
$fullPath = storage_path('app/public/' . $path);
$mime = is_file($fullPath) ? mime_content_type($fullPath) : null;
```

Lalu isi `mime` saat create Asset.

---

## 9) Catatan Tentang `storage` Kolom Assets

- Untuk file lokal: set `storage = 'local'`, `disk = 'public'`, `path = '...'`, `url = null`
- Untuk file URL: set `storage = 'url'`, `url = 'https://...'`, `disk/path = null`

---

Selesai ✅  
Kalau kamu mau, aku bisa buatkan helper reusable (mis. `assetUploadField(...)`) supaya semua field upload tidak copy-paste dan konsisten.
