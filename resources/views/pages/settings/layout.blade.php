@php
    $settingsTabs = [
        ['label' => __('Profile'), 'href' => route('profile.edit'), 'active' => request()->routeIs('profile.edit')],
        ['label' => __('Password'), 'href' => route('user-password.edit'), 'active' => request()->routeIs('user-password.edit')],
        ['label' => __('Appearance'), 'href' => route('appearance.edit'), 'active' => request()->routeIs('appearance.edit')],
    ];

    if (Laravel\Fortify\Features::canManageTwoFactorAuthentication()) {
        array_splice($settingsTabs, 2, 0, [[
            'label' => __('Two-factor auth'),
            'href' => route('two-factor.show'),
            'active' => request()->routeIs('two-factor.show'),
        ]]);
    }
@endphp

<div class="studio-panel" data-studio-hover>
    <div class="studio-panel-header">
        <div>
            <span class="studio-label">Account Settings</span>
            <h3 style="margin-top:.75rem;">{{ $heading ?? '' }}</h3>
            <p class="studio-copy" style="margin-top:.45rem;">{{ $subheading ?? '' }}</p>
        </div>
        <span class="studio-pill">Workspace</span>
    </div>

    <div class="studio-actions" style="margin-top:0;">
        @foreach ($settingsTabs as $tab)
            <a
                href="{{ $tab['href'] }}"
                wire:navigate
                class="{{ $tab['active'] ? 'studio-button' : 'studio-button-ghost' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    <div class="studio-card" data-studio-hover style="margin-top:1rem;">
        {{ $slot }}
    </div>
</div>
