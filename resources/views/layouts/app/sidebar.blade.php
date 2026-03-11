<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#020816] text-white">
        @php
            $managedGuild = session('managed_guild');
            $currentUser = auth()->user();
        @endphp

        <style>
            .ops-sidebar-shell {
                position: relative;
                background:
                    radial-gradient(circle at top left, rgba(104, 240, 255, 0.08), transparent 26%),
                    radial-gradient(circle at bottom right, rgba(139, 148, 255, 0.12), transparent 28%),
                    linear-gradient(180deg, rgba(4, 10, 24, 0.98), rgba(2, 7, 18, 0.98));
                color: #eef4ff;
            }

            .ops-sidebar-shell::before {
                content: "";
                position: absolute;
                inset: 0;
                background-image:
                    linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
                background-size: 44px 44px;
                opacity: 0.16;
                pointer-events: none;
            }

            .ops-sidebar-panel,
            .ops-sidebar-footer {
                position: relative;
                border: 1px solid rgba(104, 240, 255, 0.1);
                background: rgba(8, 16, 34, 0.72);
                box-shadow: 0 18px 44px rgba(0, 0, 0, 0.28);
                backdrop-filter: blur(16px);
            }

            .ops-sidebar-header {
                position: relative;
                padding-top: 0.2rem;
            }

            .ops-sidebar-guild {
                margin-top: 1rem;
                border-radius: 1.25rem;
                border: 1px solid rgba(104, 240, 255, 0.12);
                background: rgba(255, 255, 255, 0.04);
                padding: 0.95rem;
            }

            .ops-sidebar-eyebrow,
            .ops-sidebar-meta,
            .ops-sidebar-mini,
            .ops-sidebar-link-note,
            .ops-sidebar-block-title {
                font-family: "JetBrains Mono", ui-monospace, monospace;
                text-transform: uppercase;
                letter-spacing: 0.14em;
            }

            .ops-sidebar-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 0.55rem;
                border-radius: 999px;
                border: 1px solid rgba(104, 240, 255, 0.14);
                background: rgba(255, 255, 255, 0.04);
                padding: 0.45rem 0.7rem;
                font-size: 0.64rem;
                font-weight: 700;
                color: #68f0ff;
            }

            .ops-sidebar-eyebrow::before {
                content: "";
                width: 0.46rem;
                height: 0.46rem;
                border-radius: 999px;
                background: #76ffb8;
                box-shadow: 0 0 14px rgba(118, 255, 184, 0.9);
            }

            .ops-sidebar-guild strong {
                display: block;
                margin-top: 0.8rem;
                font-size: 1rem;
                color: #f4f8ff;
            }

            .ops-sidebar-meta {
                margin-top: 0.35rem;
                font-size: 0.62rem;
                font-weight: 700;
                color: #8ea4cb;
            }

            .ops-sidebar-mini {
                display: inline-flex;
                margin-top: 0.7rem;
                border-radius: 999px;
                background: rgba(118, 255, 184, 0.12);
                color: #76ffb8;
                padding: 0.36rem 0.6rem;
                font-size: 0.58rem;
                font-weight: 700;
            }

            .ops-sidebar-block-title {
                margin: 0 0 0.8rem;
                font-size: 0.74rem;
                font-weight: 700;
                color: #8ea4cb;
            }

            .ops-sidebar-shell [data-flux-sidebar-brand] {
                border-radius: 1.1rem;
                padding-inline: 0.85rem;
                min-height: 3.25rem;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(104, 240, 255, 0.08);
            }

            .ops-sidebar-shell [data-flux-sidebar-brand] > div:last-child {
                font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif;
                font-size: 0.98rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #f4f8ff;
            }

            .ops-sidebar-shell [data-flux-sidebar-group] {
                gap: 0.45rem !important;
            }

            .ops-sidebar-shell [data-flux-sidebar-item] {
                min-height: auto !important;
                align-items: flex-start !important;
                gap: 0.8rem !important;
                border-radius: 1rem !important;
                padding: 0.9rem 0.95rem !important;
                white-space: normal !important;
                transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
            }

            .ops-sidebar-shell [data-flux-sidebar-item]:hover {
                transform: translateY(-2px);
                border-color: rgba(104, 240, 255, 0.18) !important;
                background: rgba(255, 255, 255, 0.05) !important;
            }

            .ops-sidebar-shell [data-flux-sidebar-item] [data-flux-icon] {
                flex-shrink: 0;
                width: 1rem;
                height: 1rem;
                margin-top: 0.12rem;
                color: #9cb4db;
            }

            .ops-sidebar-shell [data-flux-sidebar-item] [data-content] {
                display: block !important;
                flex: 1 1 auto;
                overflow: visible !important;
                white-space: normal !important;
                font-size: 1rem;
                font-weight: 600;
                line-height: 1.2;
                color: #edf4ff;
            }

            .ops-sidebar-link-note {
                display: block;
                margin-top: 0.32rem;
                font-size: 0.58rem;
                line-height: 1.45;
                letter-spacing: 0.16em;
                white-space: normal;
                color: #6f86a9;
            }

            .ops-sidebar-shell [data-flux-sidebar-item][data-current] {
                border-color: rgba(104, 240, 255, 0.28) !important;
                background: linear-gradient(135deg, rgba(104, 240, 255, 0.18), rgba(111, 134, 255, 0.14)) !important;
                color: #f4f8ff !important;
                box-shadow: inset 0 0 0 1px rgba(104, 240, 255, 0.14), 0 10px 22px rgba(0, 0, 0, 0.22);
            }

            .ops-sidebar-shell [data-flux-sidebar-item][data-current] [data-flux-icon],
            .ops-sidebar-shell [data-flux-sidebar-item][data-current] .ops-sidebar-link-note,
            .ops-sidebar-shell [data-flux-sidebar-item][data-current] [data-content] {
                color: #f4f8ff !important;
            }
        </style>

        <flux:sidebar sticky collapsible="mobile" class="ops-sidebar-shell border-e border-[rgba(104,240,255,0.1)]">
            <flux:sidebar.header>
                <div class="ops-sidebar-header w-full space-y-4">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <div class="ops-sidebar-guild">
                        <span class="ops-sidebar-eyebrow">Ops surface</span>
                        <strong>{{ $managedGuild['name'] ?? 'Belum pilih server' }}</strong>
                        <div class="ops-sidebar-meta">{{ $managedGuild['id'] ?? 'Guild belum dipilih' }}</div>
                        <span class="ops-sidebar-mini">{{ $managedGuild ? 'Scoped dashboard' : 'Global mode' }}</span>
                    </div>
                </div>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <div class="ops-sidebar-panel w-full rounded-[1.6rem] p-3">
                    <p class="ops-sidebar-block-title">Platform</p>
                    <flux:sidebar.group class="grid gap-1">
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                            <span class="ops-sidebar-link-note">Control center utama</span>
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="server-stack" :href="route('guilds.select')" :current="request()->routeIs('guilds.select*')" wire:navigate>
                            {{ __('Pilih Server') }}
                            <span class="ops-sidebar-link-note">Scope panel per guild</span>
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('discord.setup')" :current="request()->routeIs('discord.setup')" wire:navigate>
                            {{ __('Discord Setup') }}
                            <span class="ops-sidebar-link-note">Webhook, OAuth, command sync</span>
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="sparkles" :href="route('vip-title.setup')" :current="request()->routeIs('vip-title.setup*')" wire:navigate>
                            {{ __('VIP Title Setup') }}
                            <span class="ops-sidebar-link-note">Map key, API key, gamepass</span>
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="code-bracket-square" :href="route('roblox.scripts.index')" :current="request()->routeIs('roblox.scripts.*')" wire:navigate>
                            {{ __('Roblox Scripts') }}
                            <span class="ops-sidebar-link-note">File siap tempel ke game</span>
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </div>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <div class="ops-sidebar-footer w-full rounded-[1.5rem] p-3">
                    <p class="ops-sidebar-block-title">Shortcuts</p>
                    <flux:sidebar.item icon="home" :href="route('home')">
                        {{ __('Landing Page') }}
                        <span class="ops-sidebar-link-note">Kembali ke halaman utama</span>
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" href="https://create.roblox.com/docs" target="_blank">
                        {{ __('Roblox Docs') }}
                        <span class="ops-sidebar-link-note">Referensi resmi Roblox</span>
                    </flux:sidebar.item>
                </div>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="$currentUser->name" />
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="$currentUser->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="$currentUser->name"
                                    :initials="$currentUser->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ $currentUser->name }}</flux:heading>
                                    <flux:text class="truncate">{{ $currentUser->email }}</flux:text>
                                    @if ($managedGuild)
                                        <flux:text class="truncate text-[11px] uppercase tracking-[0.18em] text-cyan-300">
                                            {{ $managedGuild['name'] }}
                                        </flux:text>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
