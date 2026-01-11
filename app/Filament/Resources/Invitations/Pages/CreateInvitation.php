<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Filament\Resources\Invitations\Pages\Concerns\SyncInvitationAssets;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitation extends CreateRecord
{
    use SyncInvitationAssets;

    protected static string $resource = InvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->stripInvitationFormData($data);
    }

    protected function afterCreate(): void
    {
        $this->syncInvitationAssets($this->record, $this->data);
    }
}
