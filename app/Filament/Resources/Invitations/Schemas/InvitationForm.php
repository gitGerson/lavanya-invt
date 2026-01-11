<?php

namespace App\Filament\Resources\Invitations\Schemas;

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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                        FileUpload::make('couple_image')
                            ->label('Couple Image')
                            ->disk('public')
                            ->directory('invitations')
                            ->image()
                            ->dehydrated(false),
                    ]),

                Section::make('Bride & Groom')
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('Bride')
                                ->schema(self::personFields('bride')),
                            Section::make('Groom')
                                ->schema(self::personFields('groom')),
                        ]),
                    ]),

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

                Section::make('Gallery')
                    ->schema([
                        Repeater::make('galleryItems')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->schema([
                                Hidden::make('id'),
                            FileUpload::make('image')
                                ->label('Image')
                                ->disk('public')
                                ->directory('invitations/gallery')
                                ->image()
                                ->dehydrated(false),
                            Placeholder::make('note')
                                ->content('Uploaded images are stored in assets and linked to gallery items.'),
                            ])
                            ->addActionLabel('Add Image'),
                    ]),

                Section::make('Map')
                    ->relationship('map')
                    ->schema([
                        TextInput::make('map_section_title')->maxLength(150),
                        Textarea::make('map_address')->rows(2),
                        TextInput::make('map_embed_src')->label('Google Maps Embed SRC'),
                        TextInput::make('map_location_url')->label('Google Maps Link'),
                    ]),

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
                            ->schema([
                                Hidden::make('id'),
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
                                    ->dehydrated(false),
                            ])
                            ->addActionLabel('Add Bank Account'),
                    ]),

                Section::make('Music')
                    ->relationship('music')
                    ->schema([
                    Toggle::make('autoplay')->default(true),
                    Toggle::make('loop_audio')->default(true),
                    FileUpload::make('music_audio')
                        ->label('Audio File')
                        ->disk('public')
                        ->directory('invitations/audio')
                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'])
                        ->dehydrated(false),
                    ]),
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
                ->image()
                ->dehydrated(false),
        ];
    }
}
