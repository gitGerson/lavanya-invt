<?php

namespace App\Filament\Resources\Invitations\Pages\Concerns;

use App\Models\Invitation;

trait SyncInvitationAssets
{
    protected function stripInvitationFormData(array $data): array
    {
        foreach ([
            'couple_image',
            'bride_name',
            'bride_title',
            'bride_father_name',
            'bride_mother_name',
            'bride_instagram_handle',
            'bride_photo',
            'groom_name',
            'groom_title',
            'groom_father_name',
            'groom_mother_name',
            'groom_instagram_handle',
            'groom_photo',
            'music_audio',
        ] as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    protected function syncInvitationAssets(Invitation $record, array $data): void
    {
        $record->loadMissing([
            'assets',
            'couple',
            'people',
            'galleryItems',
            'giftAccounts',
            'music',
        ]);

        $this->syncCoupleImage($record, $data);
        $this->syncPeople($record, $data);
        $this->syncGallery($record, $data);
        $this->syncGiftAccounts($record, $data);
        $this->syncMusic($record, $data);
    }

    protected function syncCoupleImage(Invitation $record, array $data): void
    {
        $path = $data['couple_image'] ?? null;
        if (! $path) {
            return;
        }

        $asset = $record->assets()->updateOrCreate(
            ['category' => 'section_image', 'kind' => 'image', 'disk' => 'public', 'path' => $path],
            ['storage' => 'local', 'alt_text' => 'Couple Image']
        );

        $record->couple()->firstOrCreate([])->update([
            'couple_image_asset_id' => $asset->id,
        ]);
    }

    protected function syncPeople(Invitation $record, array $data): void
    {
        $this->syncPerson($record, $data, 'bride');
        $this->syncPerson($record, $data, 'groom');
    }

    protected function syncPerson(Invitation $record, array $data, string $role): void
    {
        $person = $record->people()->firstOrCreate(['role' => $role]);

        $person->update([
            'name' => $data["{$role}_name"] ?? null,
            'title' => $data["{$role}_title"] ?? null,
            'father_name' => $data["{$role}_father_name"] ?? null,
            'mother_name' => $data["{$role}_mother_name"] ?? null,
            'instagram_handle' => $data["{$role}_instagram_handle"] ?? null,
        ]);

        $photoPath = $data["{$role}_photo"] ?? null;
        if (! $photoPath) {
            return;
        }

        $asset = $record->assets()->updateOrCreate(
            ['category' => 'section_image', 'kind' => 'image', 'disk' => 'public', 'path' => $photoPath],
            ['storage' => 'local', 'alt_text' => ucfirst($role) . ' Photo']
        );

        $person->update([
            'photo_asset_id' => $asset->id,
        ]);
    }

    protected function syncGallery(Invitation $record, array $data): void
    {
        $galleryRows = $data['galleryItems'] ?? [];
        if (empty($galleryRows)) {
            return;
        }

        foreach ($galleryRows as $index => $row) {
            $path = $row['image'] ?? null;
            if (! $path) {
                continue;
            }

            $asset = $record->assets()->updateOrCreate(
                ['category' => 'gallery_image', 'kind' => 'image', 'disk' => 'public', 'path' => $path],
                ['storage' => 'local', 'alt_text' => 'Gallery ' . ($index + 1)]
            );

            $itemId = $row['id'] ?? null;
            $item = $itemId
                ? $record->galleryItems()->whereKey($itemId)->first()
                : null;
            $item = $item ?? $record->galleryItems()->create([
                'sort_order' => $index + 1,
                'image_asset_id' => $asset->id,
            ]);

            $item->update([
                'sort_order' => $index + 1,
                'image_asset_id' => $asset->id,
            ]);
        }
    }

    protected function syncGiftAccounts(Invitation $record, array $data): void
    {
        $accountRows = $data['giftAccounts'] ?? [];
        if (empty($accountRows)) {
            return;
        }

        foreach ($accountRows as $index => $row) {
            $path = $row['qr_image'] ?? null;
            if (! $path) {
                continue;
            }

            $accountId = $row['id'] ?? null;
            $account = $accountId
                ? $record->giftAccounts()->whereKey($accountId)->first()
                : null;

            if (! $account) {
                $account = $record->giftAccounts()->create([
                    'sort_order' => $index + 1,
                ]);
            }

            $asset = $record->assets()->updateOrCreate(
                ['category' => 'other', 'kind' => 'image', 'disk' => 'public', 'path' => $path],
                ['storage' => 'local', 'alt_text' => 'Gift QR']
            );

            $account->update([
                'qr_asset_id' => $asset->id,
            ]);
        }
    }

    protected function syncMusic(Invitation $record, array $data): void
    {
        $path = $data['music_audio'] ?? null;

        if (! $path) {
            return;
        }

        $assetData = [
            'kind' => 'audio',
            'category' => 'music',
            'alt_text' => 'Invitation Music',
            'storage' => 'local',
            'disk' => 'public',
            'path' => $path,
        ];

        $asset = $record->assets()->updateOrCreate(
            [
                'category' => $assetData['category'],
                'kind' => $assetData['kind'],
                'disk' => $assetData['disk'],
                'path' => $assetData['path'],
            ],
            $assetData
        );

        $record->music()->firstOrCreate([])->update([
            'audio_asset_id' => $asset->id,
        ]);
    }
}
