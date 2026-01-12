<?php

namespace App\Livewire;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InvitationWelcome extends Component
{
    public string $authMode = 'login';

    public string $loginEmail = '';
    public string $loginPassword = '';

    public string $registerName = '';
    public string $registerEmail = '';
    public string $registerPhone = '';
    public string $registerPassword = '';
    public string $registerPasswordConfirmation = '';

    public function render(): View
    {
        return view('livewire.invitation-welcome', [
            'invitation' => $this->getInvitation(),
        ]);
    }

    public function setAuthMode(string $mode): void
    {
        if (! in_array($mode, ['login', 'register'], true)) {
            return;
        }

        $this->authMode = $mode;
        $this->resetValidation();
    }

    public function login(): void
    {
        $this->validate([
            'loginEmail' => ['required', 'email'],
            'loginPassword' => ['required'],
        ]);

        $credentials = [
            'email' => $this->loginEmail,
            'password' => $this->loginPassword,
        ];

        if (! Auth::attempt($credentials, remember: true)) {
            $this->addError('loginEmail', 'These credentials do not match our records.');
            return;
        }

        session()->regenerate();
        $this->resetAuthInputs();
    }

    public function register(): void
    {
        $this->validate([
            'registerName' => ['required', 'string', 'max:255'],
            'registerEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'registerPhone' => ['required', 'string', 'max:30'],
            'registerPassword' => ['required', 'string', 'min:8'],
            'registerPasswordConfirmation' => ['required', 'same:registerPassword'],
        ]);

        $user = User::create([
            'name' => $this->registerName,
            'email' => $this->registerEmail,
            'phone' => $this->registerPhone,
            'password' => $this->registerPassword,
        ]);

        Auth::login($user, remember: true);
        session()->regenerate();
        $this->resetAuthInputs();
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->resetAuthInputs();
        $this->authMode = 'login';
    }

    protected function getInvitation(): ?Invitation
    {
        if (! Auth::check()) {
            return null;
        }

        return Auth::user()
            ->invitations()
            ->latest()
            ->first();
    }

    protected function resetAuthInputs(): void
    {
        $this->reset([
            'loginEmail',
            'loginPassword',
            'registerName',
            'registerEmail',
            'registerPhone',
            'registerPassword',
            'registerPasswordConfirmation',
        ]);
        $this->resetValidation();
    }
}
