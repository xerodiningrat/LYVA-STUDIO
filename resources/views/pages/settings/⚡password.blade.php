<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.settings-workspace')] #[Title('Password settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Password settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="settings-form-shell">
            <div class="settings-form-grid">
                <div class="studio-field">
                    <label for="current_password">{{ __('Current password') }}</label>
                    <input id="current_password" wire:model="current_password" class="studio-input" type="password" required autocomplete="current-password">
                    @error('current_password')
                        <span class="settings-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="studio-field">
                    <label for="new_password">{{ __('New password') }}</label>
                    <input id="new_password" wire:model="password" class="studio-input" type="password" required autocomplete="new-password">
                    @error('password')
                        <span class="settings-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="studio-field">
                    <label for="password_confirmation">{{ __('Confirm password') }}</label>
                    <input id="password_confirmation" wire:model="password_confirmation" class="studio-input" type="password" required autocomplete="new-password">
                </div>
            </div>

            <div class="settings-form-actions">
                <button type="submit" class="studio-button" data-test="update-password-button">
                    {{ __('Save password') }}
                </button>

                <x-action-message class="settings-status" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
