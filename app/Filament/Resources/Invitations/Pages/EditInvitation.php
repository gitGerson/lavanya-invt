<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Filament\Resources\Invitations\Pages\Concerns\SyncInvitationAssets;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvitation extends EditRecord
{
    use SyncInvitationAssets;

    protected static string $resource = InvitationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record->loadMissing([
            'couple.coupleImage',
            'people.photo',
            'galleryItems.image',
            'giftAccounts.qr',
            'music.audio',
        ]);

        $bride = $record->people->firstWhere('role', 'bride');
        $groom = $record->people->firstWhere('role', 'groom');

        if ($record->couple?->coupleImage?->storage === 'local') {
            $data['couple_image'] = $record->couple?->coupleImage?->path;
        }

        $data['bride_name'] = $bride?->name;
        $data['bride_title'] = $bride?->title;
        $data['bride_father_name'] = $bride?->father_name;
        $data['bride_mother_name'] = $bride?->mother_name;
        $data['bride_instagram_handle'] = $bride?->instagram_handle;
        if ($bride?->photo?->storage === 'local') {
            $data['bride_photo'] = $bride?->photo?->path;
        }

        $data['groom_name'] = $groom?->name;
        $data['groom_title'] = $groom?->title;
        $data['groom_father_name'] = $groom?->father_name;
        $data['groom_mother_name'] = $groom?->mother_name;
        $data['groom_instagram_handle'] = $groom?->instagram_handle;
        if ($groom?->photo?->storage === 'local') {
            $data['groom_photo'] = $groom?->photo?->path;
        }

        $audio = $record->music?->audio;
        if ($audio?->storage === 'local') {
            $data['music_audio'] = $audio->path;
        }

        $data['galleryItems'] = $record->galleryItems
            ->map(fn ($item) => [
                'id' => $item->id,
                'image' => $item->image?->storage === 'local' ? $item->image?->path : null,
            ])
            ->values()
            ->all();

        $data['giftAccounts'] = $record->giftAccounts
            ->map(fn ($account) => [
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'account_holder' => $account->account_holder,
                'qr_image' => $account->qr?->storage === 'local' ? $account->qr?->path : null,
            ])
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->stripInvitationFormData($data);
    }

    protected function afterSave(): void
    {
        $this->syncInvitationAssets($this->record, $this->data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
