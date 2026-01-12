<?php

namespace App\Livewire;

use App\Filament\Resources\Invitations\Pages\Concerns\SyncInvitationAssets;
use App\Filament\Resources\Invitations\Schemas\InvitationForm;
use App\Models\Invitation;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InvitationWizard extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use SyncInvitationAssets;

    public ?Invitation $record = null;

    public ?array $data = [];

    protected array $rawFormState = [];

    public function mount(?Invitation $record = null): void
    {
        $this->record = $record;

        if (! $this->record && auth()->check()) {
            $this->record = auth()->user()
                ->invitations()
                ->latest()
                ->first();
        }

        if ($this->record) {
            $this->fillFormFromRecord();
            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return InvitationForm::configure($schema)
            ->model($this->record ?? Invitation::class)
            ->statePath('data')
            ->operation($this->record ? 'edit' : 'create');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $this->rawFormState = $this->form->getRawState();
        $data = $this->stripInvitationFormData($data);
        $authUserId = auth()->id();
        if ($authUserId && empty($data['user_id'])) {
            $data['user_id'] = $authUserId;
        }

        if ($this->record) {
            if ($authUserId && ! $this->record->user_id) {
                $this->record->user_id = $authUserId;
            }
            $this->record->fill($data);
            $this->record->save();
        } else {
            $this->record = Invitation::create($data);
        }

        $this->form->model($this->record)->saveRelationships();
        $this->syncInvitationAssets($this->record, $this->rawFormState);
    }

    public function getRecord(): ?Invitation
    {
        return $this->record;
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->livewire($this)
            ->label('Cancel')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (): void {
                if (Auth::check()) {
                    Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();
                }

                $this->redirect('/', navigate: true);
            });
    }

    public function render(): View
    {
        return view('livewire.invitation-wizard');
    }

    protected function fillFormFromRecord(): void
    {
        $record = $this->record->loadMissing([
            'couple.coupleImage',
            'people.photo',
            'galleryItems.image',
            'giftAccounts.qr',
            'music.audio',
        ]);

        $data = $record->attributesToArray();

        $bride = $record->people->firstWhere('role', 'bride');
        $groom = $record->people->firstWhere('role', 'groom');

        if ($record->couple?->coupleImage?->storage === 'local') {
            $path = $record->couple?->coupleImage?->path;
            data_set($data, 'couple.couple_image', $path);
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

        $this->form->fill($data);
    }
}
