<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="settings-danger-panel">
    <div class="studio-field">
        <label>{{ __('Delete account') }}</label>
        <p class="studio-copy" style="margin:0;">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <button type="button" class="studio-button-danger" data-test="delete-user-button">
            {{ __('Delete account') }}
        </button>
    </flux:modal.trigger>

    <livewire:pages::settings.delete-user-modal />
</section>
