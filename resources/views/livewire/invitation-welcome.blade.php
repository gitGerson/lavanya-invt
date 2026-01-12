<div class="w-full flex flex-col items-center gap-8">
    <div class="flex flex-col items-center gap-4">
        <img src="{{ asset('logo.jpg') }}" alt="Logo" class="h-16 w-auto drop-shadow-[0_6px_12px_rgba(0,0,0,0.5)]" />
        <div class="text-xs uppercase golden-title">Invitation Builder</div>
    </div>
    @if (auth()->check())
        <div class="w-full flex justify-center">
            <div class="force-mobile">
                @livewire('invitation-wizard', ['record' => $invitation], key('invitation-wizard-' . ($invitation?->id ?? 'new')))
            </div>
        </div>
    @else
        <div class="w-full max-w-md space-y-6">
            <div class="rounded-2xl golden-outline bg-white/80 p-6 space-y-4">
                @if ($authMode === 'register')
                    <form wire:submit.prevent="register" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Name</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model.defer="registerName" />
                            </x-filament::input.wrapper>
                            @error('registerName')
                                <div class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Email</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="email" wire:model.defer="registerEmail" />
                            </x-filament::input.wrapper>
                            @error('registerEmail')
                                <div class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Phone</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="tel" wire:model.defer="registerPhone" />
                            </x-filament::input.wrapper>
                            @error('registerPhone')
                                <div class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Password</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="password" wire:model.defer="registerPassword" />
                            </x-filament::input.wrapper>
                            @error('registerPassword')
                                <div class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Confirm Password</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="password" wire:model.defer="registerPasswordConfirmation" />
                            </x-filament::input.wrapper>
                            @error('registerPasswordConfirmation')
                                <div class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-filament::button type="submit" class="w-full">
                            Create Account
                        </x-filament::button>
                    </form>
                    <div class="text-sm text-gray-600">
                        Already have an account?
                        <button
                            type="button"
                            wire:click="setAuthMode('login')"
                            class="font-medium text-amber-600 hover:underline"
                        >
                            Login
                        </button>
                    </div>
                @else
                    <form wire:submit.prevent="login" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Email</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="email" wire:model.defer="loginEmail" />
                            </x-filament::input.wrapper>
                            @error('loginEmail')
                                <div class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-gray-700">Password</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="password" wire:model.defer="loginPassword" />
                            </x-filament::input.wrapper>
                            @error('loginPassword')
                                <div class="text-xs text-danger-600 dark:text-danger-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-filament::button type="submit" class="w-full">
                            Login
                        </x-filament::button>
                    </form>
                    <div class="text-sm text-gray-600">
                        Don't have an account?
                        <button
                            type="button"
                            wire:click="setAuthMode('register')"
                            class="font-medium text-amber-600 hover:underline"
                        >
                            Create one
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>



