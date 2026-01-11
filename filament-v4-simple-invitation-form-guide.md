# Filament v4 Guide — Simple Invitation Form (Pattern A, Structured DB)

This guide shows how to build a **simple** admin form in **Filament v4** to manage your **Invitation** using your Pattern A tables/models (already migrated + models exist).

Scope:
- One Filament **Resource**: `InvitationResource`
- A “simple” form that edits:
  - Invitation base info
  - Couple (hasOne)
  - Bride + Groom (hasMany but fixed roles)
  - Events (hasMany)
  - Gallery (hasMany)
  - Map (hasOne)
  - RSVP (hasOne)
  - Gift accounts (hasMany)
  - Music (hasOne)
- Minimal assumptions, clean + practical patterns

> Notes:
> - Filament v4 uses the same general concepts as v3: Resources, Pages, Forms, Tables.
> - For **hasOne** sections, the cleanest UX is: create defaults on invitation creation (so the relation exists), then use relationship form bindings.
> - For uploads, store files into your `assets` table; for “simple”, we’ll use URL inputs first. You can upgrade to FileUpload later.

---

## 0) Prerequisites

- `Template`, `Invitation`, `Asset`, `InvitationCouple`, `InvitationPerson`, `InvitationEventSection`, `InvitationEvent`, `InvitationGalleryItem`, `InvitationMap`, `InvitationRsvp`, `InvitationGiftSection`, `InvitationGiftAccount`, `InvitationWishSection`, `InvitationMusic` models exist.
- You can render `/inv/{slug}` publicly already (optional but ideal).

---

## 1) Create Filament Resource

Run:

```bash
php artisan make:filament-resource Invitation
```

This generates:
- `app/Filament/Resources/InvitationResource.php`
- `app/Filament/Resources/InvitationResource/Pages/*`

---

## 2) Make sure relations exist on create (IMPORTANT)

Because your Pattern A uses multiple `hasOne` tables, you want them created automatically when an Invitation is created.

### Option A (Recommended): add a model observer in `Invitation` model

In `app/Models/Invitation.php`:

```php
protected static function booted(): void
{
    static::created(function (Invitation $invitation) {
        $invitation->couple()->firstOrCreate([]);
        $invitation->eventSection()->firstOrCreate([]);
        $invitation->map()->firstOrCreate([]);
        $invitation->rsvp()->firstOrCreate([]);
        $invitation->giftSection()->firstOrCreate([]);
        $invitation->wishSection()->firstOrCreate([]);
        $invitation->music()->firstOrCreate([]);

        // Ensure bride + groom rows exist (fixed roles)
        $invitation->people()->firstOrCreate(['role' => 'bride']);
        $invitation->people()->firstOrCreate(['role' => 'groom']);
    });
}
```

This ensures the Filament form doesn’t hit null relation issues.

---

## 3) Build the Form (Simple & Structured)

Open: `app/Filament/Resources/InvitationResource.php`

### 3.1 Imports you’ll likely need

```php
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Placeholder;
```

### 3.2 Main `form()` skeleton

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Invitation')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('template_id')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->required(),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in the public URL: /inv/{slug}'),

                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('title')->maxLength(200),
                        TextInput::make('timezone')->default('Asia/Jakarta')->maxLength(64),
                    ]),
                ]),

            // Couple (hasOne)
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
                    TextInput::make('couple_image_url')
                        ->label('Couple Image URL')
                        ->helperText('Simple mode: save URL, later upgrade to uploads.')
                        ->dehydrated(false),
                ]),

            // People: bride & groom (fixed roles)
            Section::make('Bride & Groom')
                ->schema([
                    Grid::make(2)->schema([
                        Section::make('Bride')
                            ->schema(self::personFields(role: 'bride')),
                        Section::make('Groom')
                            ->schema(self::personFields(role: 'groom')),
                    ]),
                ]),

            // Event Section title + default location url
            Section::make('Event Section')
                ->relationship('eventSection')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('section_title')->label('Section Title')->maxLength(150),
                        TextInput::make('default_location_url')->label('Default Location URL'),
                    ]),
                ]),

            // Events repeater (hasMany)
            Section::make('Events')
                ->schema([
                    Repeater::make('events')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('title')->required()->maxLength(150),
                                TextInput::make('location_text')->label('Location')->maxLength(255),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('event_date_display')->label('Date (Display)')->maxLength(150),
                                TextInput::make('event_time_display')->label('Time (Display)')->maxLength(150),
                            ]),
                            Grid::make(3)->schema([
                                DatePicker::make('event_date')->label('Date (Normalized)'),
                                TimePicker::make('start_time')->seconds(false),
                                TimePicker::make('end_time')->seconds(false),
                            ]),
                            TextInput::make('location_url')->label('Location URL'),
                        ])
                        ->defaultItems(2)
                        ->addActionLabel('Add Event'),
                ]),

            // Gallery (hasMany)
            Section::make('Gallery')
                ->schema([
                    Repeater::make('galleryItems')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->schema([
                            TextInput::make('image_url')
                                ->label('Image URL')
                                ->dehydrated(false),
                            Placeholder::make('note')
                                ->content('Simple mode: store gallery image URLs in assets and link them to galleryItems.image_asset_id. See section 4.'),
                        ])
                        ->addActionLabel('Add Image'),
                ]),

            // Map (hasOne)
            Section::make('Map')
                ->relationship('map')
                ->schema([
                    TextInput::make('map_section_title')->maxLength(150),
                    Textarea::make('map_address')->rows(2),
                    TextInput::make('map_embed_src')->label('Google Maps Embed SRC'),
                    TextInput::make('map_location_url')->label('Google Maps Link'),
                ]),

            // RSVP (hasOne)
            Section::make('RSVP')
                ->relationship('rsvp')
                ->schema([
                    TextInput::make('rsvp_title')->maxLength(150),
                    TextInput::make('rsvp_subtitle')->maxLength(255),
                    Textarea::make('rsvp_message')->rows(3),
                    TextInput::make('rsvp_hosts')->maxLength(255),
                ]),

            // Gifts
            Section::make('Gift Section')
                ->relationship('giftSection')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('gift_title')->maxLength(150),
                        TextInput::make('gift_subtitle')->maxLength(255),
                    ]),
                ]),

            Section::make('Gift Accounts')
                ->schema([
                    Repeater::make('giftAccounts')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('bank_name')->maxLength(100),
                                TextInput::make('account_number')->maxLength(100),
                            ]),
                            TextInput::make('account_holder')->maxLength(150),
                            TextInput::make('qr_url')->label('QR Image URL')->dehydrated(false),
                        ])
                        ->addActionLabel('Add Bank Account'),
                ]),

            // Music (hasOne)
            Section::make('Music')
                ->relationship('music')
                ->schema([
                    Toggle::make('autoplay')->default(true),
                    Toggle::make('loop_audio')->default(true),
                    TextInput::make('music_url')->label('Audio URL')->dehydrated(false),
                    TextInput::make('music_local_path')->label('Local Path (public disk)')->dehydrated(false),
                ]),
        ]);
}
```

### 3.3 Helper method for Bride/Groom fields (fixed role)

Add this inside `InvitationResource`:

```php
protected static function personFields(string $role): array
{
    // This binds to a record in invitation_people with role='bride' or 'groom'
    // We'll implement it by editing via custom state handling in section 3.4
    return [
        Placeholder::make('role_note')->content("Editing {$role} info"),
        TextInput::make("{$role}_name")->label('Name')->dehydrated(false),
        TextInput::make("{$role}_title")->label('Title')->dehydrated(false),
        TextInput::make("{$role}_father_name")->label('Father')->dehydrated(false),
        TextInput::make("{$role}_mother_name")->label('Mother')->dehydrated(false),
        TextInput::make("{$role}_instagram_handle")->label('Instagram')->dehydrated(false),
        TextInput::make("{$role}_photo_url")->label('Photo URL')->dehydrated(false),
    ];
}
```

### 3.4 “Simple” Bride/Groom binding (how it saves)

Because bride/groom are stored as two rows in `invitation_people`, a simple way is:
- Use `mutateFormDataBeforeFill()` to put bride/groom data into the form state
- Use `mutateFormDataBeforeSave()` (or page hooks) to write back into the `people` relation

Edit the **Edit page**:  
`app/Filament/Resources/InvitationResource/Pages/EditInvitation.php`

```php
protected function mutateFormDataBeforeFill(array $data): array
{
    $record = $this->record->loadMissing('people.photo');

    $bride = $record->people->firstWhere('role', 'bride');
    $groom = $record->people->firstWhere('role', 'groom');

    // Flatten into form fields
    $data['bride_name'] = $bride?->name;
    $data['bride_title'] = $bride?->title;
    $data['bride_father_name'] = $bride?->father_name;
    $data['bride_mother_name'] = $bride?->mother_name;
    $data['bride_instagram_handle'] = $bride?->instagram_handle;
    $data['bride_photo_url'] = $bride?->photo?->publicUrl();

    $data['groom_name'] = $groom?->name;
    $data['groom_title'] = $groom?->title;
    $data['groom_father_name'] = $groom?->father_name;
    $data['groom_mother_name'] = $groom?->mother_name;
    $data['groom_instagram_handle'] = $groom?->instagram_handle;
    $data['groom_photo_url'] = $groom?->photo?->publicUrl();

    return $data;
}

protected function mutateFormDataBeforeSave(array $data): array
{
    // We remove flattened fields so Filament doesn't try to save them to invitations table
    foreach ([
        'bride_name','bride_title','bride_father_name','bride_mother_name','bride_instagram_handle','bride_photo_url',
        'groom_name','groom_title','groom_father_name','groom_mother_name','groom_instagram_handle','groom_photo_url',
    ] as $key) {
        unset($data[$key]);
    }

    return $data;
}

protected function afterSave(): void
{
    $record = $this->record->loadMissing(['people', 'assets']);

    // Bride
    $bride = $record->people()->firstOrCreate(['role' => 'bride']);
    $bride->update([
        'name' => $this->data['bride_name'] ?? null,
        'title' => $this->data['bride_title'] ?? null,
        'father_name' => $this->data['bride_father_name'] ?? null,
        'mother_name' => $this->data['bride_mother_name'] ?? null,
        'instagram_handle' => $this->data['bride_instagram_handle'] ?? null,
    ]);

    // Groom
    $groom = $record->people()->firstOrCreate(['role' => 'groom']);
    $groom->update([
        'name' => $this->data['groom_name'] ?? null,
        'title' => $this->data['groom_title'] ?? null,
        'father_name' => $this->data['groom_father_name'] ?? null,
        'mother_name' => $this->data['groom_mother_name'] ?? null,
        'instagram_handle' => $this->data['groom_instagram_handle'] ?? null,
    ]);

    // NOTE: Photo assets should be created in `assets` table and linked via photo_asset_id.
    // For a "simple form", you can keep photo URLs externally and store as Asset(storage=url).
}
```

> Same approach works for: couple image url, gallery urls, gift qr url, music url/path.

---

## 4) “Simple Mode” Asset Handling (URL-based)

Your schema expects assets stored in `assets` and linked by `*_asset_id`.
For a simple admin form, start with:
- Store image/audio URLs into `assets` (`storage=url`)
- Update the foreign keys on related tables (e.g. `photo_asset_id`, `image_asset_id`)

### Example: Save Couple Image URL afterSave()

Inside `afterSave()` (EditInvitation page):

```php
$coupleUrl = $this->data['couple_image_url'] ?? null;

if ($coupleUrl) {
    $asset = $record->assets()->updateOrCreate(
        ['category' => 'section_image', 'kind' => 'image', 'url' => $coupleUrl],
        ['storage' => 'url', 'alt_text' => 'Couple Image']
    );

    $record->couple()->update(['couple_image_asset_id' => $asset->id]);
}
```

### Example: Gallery URLs

You can create assets per URL and link them:

```php
// Suppose your form collects URLs in a repeater or separate UI.
// For each url:
$asset = $record->assets()->create([
  'kind' => 'image',
  'category' => 'gallery_image',
  'storage' => 'url',
  'url' => $url,
]);

$record->galleryItems()->create([
  'sort_order' => $sort,
  'image_asset_id' => $asset->id,
]);
```

---

## 5) Table (List View)

In `InvitationResource::table()` show:
- slug
- template
- status
- updated_at
- public preview link

Example column:

```php
Tables\Columns\TextColumn::make('slug')
    ->searchable()
    ->copyable(),

Tables\Columns\TextColumn::make('template.name')->label('Template'),

Tables\Columns\BadgeColumn::make('status')
    ->colors([
        'warning' => 'draft',
        'success' => 'published',
        'gray' => 'archived',
    ]),
```

Action (open preview):
- `url(fn ($record) => route('invitation.show', $record->slug))`

---

## 6) Minimal “Create” experience

When creating a new invitation:
- require template_id
- require slug
- afterCreate, your model `booted()` will create related rows automatically (recommended)

---

## 7) Upgrade path (Later)

Once simple mode is stable:
- Replace URL inputs with `FileUpload`
- On upload:
  - store to disk (`public`)
  - create `assets` row with `storage=local`, `disk=public`, `path=...`
  - set `*_asset_id` fields

---

## 8) Debug Checklist

- If you see `null` relationship errors in the form:
  - Confirm the invitation has rows in hasOne tables.
  - Add the `Invitation::booted()` auto-create block (section 2).
- If repeaters don’t sort:
  - Ensure `orderColumn('sort_order')` exists and table has `sort_order`.
- If images don’t show:
  - Confirm `Asset::publicUrl()` returns correct URL.
  - Confirm `storage/url/disk/path` fields match your asset row.

---

## 9) Result

With this resource:
- You can create an invitation and fill structured data.
- Your public route `/inv/{slug}` renders the selected Blade template dynamically.
- You can extend this into a full CMS without switching to key-value fields.
