<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.settings-workspace')] #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="settings-form-shell">
            <div class="settings-form-grid">
                <div class="studio-field">
                    <label for="profile_name">{{ __('Name') }}</label>
                    <input id="profile_name" wire:model="name" class="studio-input" type="text" required autofocus autocomplete="name">
                    @error('name')
                        <span class="settings-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="studio-field">
                    <label for="profile_email">{{ __('Email') }}</label>
                    <input id="profile_email" wire:model="email" class="studio-input" type="email" required autocomplete="email">
                    @error('email')
                        <span class="settings-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="studio-field">
                @if ($this->hasUnverifiedEmail)
                    <div class="studio-notice" data-studio-hover>
                        <p class="studio-copy" style="margin:0;">
                            {{ __('Your email address is unverified.') }}

                            <button type="button" class="studio-button-ghost" wire:click.prevent="resendVerificationNotification" style="margin-left:.65rem;">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="studio-copy" style="margin:.75rem 0 0;color:#82ffbf;">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="settings-form-actions">
                <button type="submit" class="studio-button" data-test="update-profile-button">
                    {{ __('Save changes') }}
                </button>

                <x-action-message class="settings-status" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
