<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Filament\Resources\Invitations\Pages\Concerns\SyncInvitationAssets;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitation extends CreateRecord
{
    use SyncInvitationAssets;

    protected array $rawFormState = [];

    protected static string $resource = InvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->rawFormState = $this->form->getRawState();

        return $this->stripInvitationFormData($data);
    }

    protected function afterCreate(): void
    {
        $this->syncInvitationAssets($this->record, $this->rawFormState);
    }
}
