<div>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-4 flex items-center gap-3">
            <x-filament::button type="submit">
                Save Invitation
            </x-filament::button>
            {{ $this->cancelAction }}
        </div>
    </form>

    <x-filament-actions::modals />
</div>
