<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Models\Asset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class InvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Invitation')
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
                        ]),

                    Step::make('Couple')
                        ->schema([
                            Section::make('Couple')
                                ->relationship('couple')
                                ->mutateRelationshipDataBeforeCreateUsing(fn(array $data) => Arr::except($data, ['couple_image']))
                                ->mutateRelationshipDataBeforeSaveUsing(fn(array $data) => Arr::except($data, ['couple_image']))
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('couple_tagline')->maxLength(255),
                                        TextInput::make('wedding_date_display')->maxLength(150),
                                    ]),
                                    Grid::make(2)->schema([
                                        TextInput::make('couple_name_1')->maxLength(150),
                                        TextInput::make('couple_name_2')->maxLength(150),
                                    ]),
                                    Hidden::make('couple_image_asset_id'),
                                    FileUpload::make('couple_image')
                                        ->label('Couple Image')
                                        ->disk('public')
                                        ->directory('invitations')
                                        ->image()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                                            \Log::info('invitation_couple_image_after_state_updated', [
                                                'state' => $state,
                                                'invitation_id' => $livewire->getRecord()?->id,
                                            ]);

                                            if (blank($state)) {
                                                $set('couple_image_asset_id', null);
                                                return;
                                            }

                                            $path = is_array($state) ? (reset($state) ?: null) : $state;
                                            if (!$path || !is_string($path)) {
                                                return;
                                            }

                                            $invitationId = $livewire->getRecord()?->id;
                                            if (!$invitationId) {
                                                return;
                                            }

                                            $asset = Asset::firstOrCreate(
                                                [
                                                    'invitation_id' => $invitationId,
                                                    'storage' => 'local',
                                                    'disk' => 'public',
                                                    'path' => $path,
                                                ],
                                                [
                                                    'kind' => 'image',
                                                    'category' => 'section_image',
                                                    'url' => null,
                                                    'mime' => null,
                                                    'alt_text' => 'Couple Image',
                                                    'meta' => null,
                                                ]
                                            );

                                            $set('couple_image_asset_id', $asset->id);
                                        })
                                        ->afterStateHydrated(function (FileUpload $component, $state, $record) {
                                            if (!$record?->couple_image_asset_id) {
                                                return;
                                            }

                                            $asset = Asset::find($record->couple_image_asset_id);

                                            if ($asset?->storage === 'local' && $asset->disk === 'public' && $asset->path) {
                                                $component->state($asset->path);
                                            }
                                        }),
                                ]),
                        ]),

                    Step::make('Bride & Groom')
                        ->schema([
                            Section::make('Bride & Groom')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Section::make('Bride')
                                            ->schema(self::personFields('bride')),
                                        Section::make('Groom')
                                            ->schema(self::personFields('groom')),
                                    ]),
                                ]),
                        ]),

                    Step::make('Events & Map')
                        ->schema([
                            Section::make('Event Section')
                                ->relationship('eventSection')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('section_title')->label('Section Title')->maxLength(150),
                                        TextInput::make('default_location_url')->label('Default Location URL'),
                                    ]),
                                ]),

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

                            Section::make('Map')
                                ->relationship('map')
                                ->schema([
                                    TextInput::make('map_section_title')->maxLength(150),
                                    Textarea::make('map_address')->rows(2),
                                    TextInput::make('map_embed_src')->label('Google Maps Embed SRC'),
                                    TextInput::make('map_location_url')->label('Google Maps Link'),
                                ]),
                        ]),

                    Step::make('Gallery')
                        ->schema([
                            Section::make('Gallery')
                                ->schema([
                                    Repeater::make('galleryItems')
                                        ->relationship()
                                        ->orderColumn('sort_order')
                                        ->mutateRelationshipDataBeforeCreateUsing(fn(array $data) => Arr::except($data, ['image']))
                                        ->mutateRelationshipDataBeforeSaveUsing(fn(array $data) => Arr::except($data, ['image']))
                                        ->schema([
                                            Hidden::make('id'),
                                            Hidden::make('image_asset_id')
                                                ->afterStateHydrated(function (Hidden $component, $state, $record) {
                                                    if ($record?->image_asset_id) {
                                                        $component->state($record->image_asset_id);
                                                    }
                                                })
                                                ->required(),
                                            FileUpload::make('image')
                                                ->label('Image')
                                                ->disk('public')
                                                ->directory('invitations/gallery')
                                                ->image()
                                                ->dehydrated(false)
                                                ->required(fn (Get $get) => blank($get('id'))) // item baru wajib upload
                                                ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                                                    if (blank($state)) {
                                                        return;
                                                    }

                                                    $path = is_array($state) ? (reset($state) ?: null) : $state;
                                                    if (!$path || !is_string($path)) {
                                                        return;
                                                    }

                                                    $invitationId = $livewire->getRecord()?->id;
                                                    if (!$invitationId) {
                                                        return;
                                                    }

                                                    $asset = Asset::firstOrCreate(
                                                        [
                                                            'invitation_id' => $invitationId,
                                                            'storage' => 'local',
                                                            'disk' => 'public',
                                                            'path' => $path,
                                                        ],
                                                        [
                                                            'kind' => 'image',
                                                            'category' => 'gallery_image',
                                                            'url' => null,
                                                            'mime' => null,
                                                            'alt_text' => 'Gallery Image',
                                                            'meta' => null,
                                                        ]
                                                    );

                                                    $set('image_asset_id', $asset->id);
                                                })
                                                ->afterStateHydrated(function (FileUpload $component, $state, $record) {
                                                    if (!$record?->image_asset_id) {
                                                        return;
                                                    }

                                                    $asset = Asset::find($record->image_asset_id);

                                                    if ($asset?->storage === 'local' && $asset->disk === 'public' && $asset->path) {
                                                        $component->state($asset->path);
                                                    }
                                                }),

                                            Placeholder::make('note')
                                                ->content('Uploaded images are stored in assets and linked to gallery items.'),
                                        ])
                                        ->addActionLabel('Add Image'),
                                ]),
                        ]),

                    Step::make('RSVP & Gifts')
                        ->schema([
                            Section::make('RSVP')
                                ->relationship('rsvp')
                                ->schema([
                                    TextInput::make('rsvp_title')->maxLength(150),
                                    TextInput::make('rsvp_subtitle')->maxLength(255),
                                    Textarea::make('rsvp_message')->rows(3),
                                    TextInput::make('rsvp_hosts')->maxLength(255),
                                ]),

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
                                        ->mutateRelationshipDataBeforeCreateUsing(fn(array $data) => Arr::except($data, ['qr_image']))
                                        ->mutateRelationshipDataBeforeSaveUsing(fn(array $data) => Arr::except($data, ['qr_image']))
                                        ->schema([
                                            Hidden::make('id'),
                                            Hidden::make('qr_asset_id')
                                                ->afterStateHydrated(function (Hidden $component, $state, $record) {
                                                    if (!$state && $record?->qr_asset_id) {
                                                        $component->state($record->qr_asset_id);
                                                    }
                                                }),
                                            Grid::make(2)->schema([
                                                TextInput::make('bank_name')->maxLength(100),
                                                TextInput::make('account_number')->maxLength(100),
                                            ]),
                                            TextInput::make('account_holder')->maxLength(150),
                                            FileUpload::make('qr_image')
                                                ->label('QR Image')
                                                ->disk('public')
                                                ->directory('invitations/qr')
                                                ->image()
                                                ->dehydrated(false)
                                                ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                                                    if (blank($state)) {
                                                        $set('qr_asset_id', null);
                                                        return;
                                                    }

                                                    $path = is_array($state) ? (reset($state) ?: null) : $state;
                                                    if (!$path || !is_string($path)) {
                                                        return;
                                                    }

                                                    $invitationId = $livewire->getRecord()?->id;
                                                    if (!$invitationId) {
                                                        return;
                                                    }

                                                    $asset = Asset::firstOrCreate(
                                                        [
                                                            'invitation_id' => $invitationId,
                                                            'storage' => 'local',
                                                            'disk' => 'public',
                                                            'path' => $path,
                                                        ],
                                                        [
                                                            'kind' => 'image',
                                                            'category' => 'other',
                                                            'url' => null,
                                                            'mime' => null,
                                                            'alt_text' => 'Gift QR',
                                                            'meta' => null,
                                                        ]
                                                    );

                                                    $set('qr_asset_id', $asset->id);
                                                })
                                                ->afterStateHydrated(function (FileUpload $component, $state, $record) {
                                                    if (!$record?->qr_asset_id) {
                                                        return;
                                                    }

                                                    $asset = Asset::find($record->qr_asset_id);

                                                    if ($asset?->storage === 'local' && $asset->disk === 'public' && $asset->path) {
                                                        $component->state($asset->path);
                                                    }
                                                }),
                                        ])
                                        ->addActionLabel('Add Bank Account'),
                                ]),
                        ]),

                    Step::make('Music')
                        ->schema([
                            Section::make('Music')
                                ->relationship('music')
                                ->mutateRelationshipDataBeforeCreateUsing(fn(array $data) => Arr::except($data, ['music_audio']))
                                ->mutateRelationshipDataBeforeSaveUsing(fn(array $data) => Arr::except($data, ['music_audio']))
                                ->schema([
                                    Toggle::make('autoplay')->default(true),
                                    Toggle::make('loop_audio')->default(true),
                                    Hidden::make('audio_asset_id'),
                                    FileUpload::make('music_audio')
                                        ->label('Audio File')
                                        ->disk('public')
                                        ->directory('invitations/audio')
                                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'])
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                                            if (blank($state)) {
                                                $set('audio_asset_id', null);
                                                return;
                                            }

                                            $path = is_array($state) ? (reset($state) ?: null) : $state;
                                            if (!$path || !is_string($path)) {
                                                return;
                                            }

                                            $invitationId = $livewire->getRecord()?->id;
                                            if (!$invitationId) {
                                                return;
                                            }

                                            $asset = Asset::firstOrCreate(
                                                [
                                                    'invitation_id' => $invitationId,
                                                    'storage' => 'local',
                                                    'disk' => 'public',
                                                    'path' => $path,
                                                ],
                                                [
                                                    'kind' => 'audio',
                                                    'category' => 'music',
                                                    'url' => null,
                                                    'mime' => null,
                                                    'alt_text' => 'Invitation Music',
                                                    'meta' => null,
                                                ]
                                            );

                                            $set('audio_asset_id', $asset->id);
                                        })
                                        ->afterStateHydrated(function (FileUpload $component, $state, $record) {
                                            if (!$record?->audio_asset_id) {
                                                return;
                                            }

                                            $asset = Asset::find($record->audio_asset_id);

                                            if ($asset?->storage === 'local' && $asset->disk === 'public' && $asset->path) {
                                                $component->state($asset->path);
                                            }
                                        }),
                                ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    protected static function personFields(string $role): array
    {
        return [
            Placeholder::make($role . '_role_note')->content("Editing {$role} info"),
            TextInput::make("{$role}_name")->label('Name')->dehydrated(false),
            TextInput::make("{$role}_title")->label('Title')->dehydrated(false),
            TextInput::make("{$role}_father_name")->label('Father')->dehydrated(false),
            TextInput::make("{$role}_mother_name")->label('Mother')->dehydrated(false),
            TextInput::make("{$role}_instagram_handle")->label('Instagram')->dehydrated(false),
            FileUpload::make("{$role}_photo")
                ->label('Photo')
                ->disk('public')
                ->directory('invitations/people')
                ->image(),
        ];
    }
}
