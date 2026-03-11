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
                    radial-gradient(circle at top left, rgba(104, 240, 255, 0.12), transparent 24%),
                    radial-gradient(circle at 82% 18%, rgba(84, 115, 255, 0.16), transparent 24%),
                    linear-gradient(180deg, #040b1b 0%, #020816 100%);
                color: #eef4ff;
            }

            .ops-sidebar-shell [data-flux-sidebar-nav] {
                gap: 1rem;
            }

            .ops-sidebar-shell::before {
                content: "";
                position: absolute;
                inset: 0;
                background-image:
                    linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
                background-size: 64px 64px;
                opacity: 0.05;
                pointer-events: none;
            }

            .ops-sidebar-shell::after {
                content: "";
                position: absolute;
                inset: 1rem auto 1rem 0;
                width: 1px;
                background: linear-gradient(180deg, transparent, rgba(104, 240, 255, 0.2), transparent);
                opacity: 0.7;
                pointer-events: none;
            }

            .ops-sidebar-panel,
            .ops-sidebar-footer {
                position: relative;
                overflow: hidden;
                border: 1px solid rgba(114, 143, 255, 0.12);
                background: linear-gradient(180deg, rgba(8, 17, 38, 0.72), rgba(5, 12, 28, 0.56));
                box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
                backdrop-filter: blur(16px);
            }

            .ops-sidebar-panel {
                padding: 0.95rem;
            }

            .ops-sidebar-footer {
                padding: 0.9rem;
            }

            .ops-sidebar-panel::before,
            .ops-sidebar-footer::before {
                content: "";
                position: absolute;
                inset: 0 0 auto;
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(104, 240, 255, 0.34), transparent);
                opacity: 0.7;
            }

            .ops-sidebar-header {
                position: relative;
                padding-top: 0;
            }

            .ops-sidebar-guild {
                margin-top: 0.9rem;
                border-radius: 1.4rem;
                border: 1px solid rgba(104, 240, 255, 0.12);
                background:
                    linear-gradient(180deg, rgba(12, 26, 56, 0.88), rgba(7, 15, 31, 0.82));
                padding: 1rem;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
            }

            .ops-sidebar-eyebrow,
            .ops-sidebar-block-title {
                font-family: "JetBrains Mono", ui-monospace, monospace;
                text-transform: uppercase;
                letter-spacing: 0.14em;
            }

            .ops-sidebar-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                border-radius: 999px;
                border: 1px solid rgba(104, 240, 255, 0.18);
                background: rgba(104, 240, 255, 0.08);
                padding: 0.4rem 0.68rem;
                font-size: 0.56rem;
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
                font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif;
                font-size: 1.08rem;
                line-height: 1.15;
                letter-spacing: 0.03em;
                color: #f4f8ff;
            }

            .ops-sidebar-meta {
                margin-top: 0.42rem;
                font-family: "JetBrains Mono", ui-monospace, monospace;
                font-size: 0.62rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                color: #8ea4cb;
            }

            .ops-sidebar-mini {
                display: inline-flex;
                margin-top: 0.85rem;
                border-radius: 999px;
                background: linear-gradient(135deg, rgba(21, 87, 74, 0.82), rgba(18, 68, 55, 0.72));
                color: #8fffd1;
                padding: 0.38rem 0.72rem;
                font-family: "JetBrains Mono", ui-monospace, monospace;
                font-size: 0.56rem;
                font-weight: 700;
                letter-spacing: 0.12em;
            }

            .ops-sidebar-block-title {
                margin: 0 0 0.9rem;
                font-size: 0.66rem;
                font-weight: 700;
                color: #92a7cc;
            }

            .ops-sidebar-shell [data-flux-sidebar-brand] {
                border-radius: 1.1rem;
                padding-inline: 0.9rem;
                min-height: 3.4rem;
                background: linear-gradient(180deg, rgba(11, 22, 46, 0.92), rgba(8, 18, 39, 0.72));
                border: 1px solid rgba(114, 143, 255, 0.12);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
            }

            .ops-sidebar-shell [data-flux-sidebar-brand] > div:last-child {
                font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif;
                font-size: 0.96rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #f4f8ff;
            }

            .ops-sidebar-shell [data-flux-sidebar-group] {
                display: block !important;
                gap: 0 !important;
            }

            .ops-sidebar-shell [data-flux-sidebar-group] > * + * {
                margin-top: 0.62rem;
            }

            .ops-sidebar-shell [data-flux-sidebar-item] {
                position: relative;
                min-height: auto !important;
                align-items: center !important;
                gap: 0.78rem !important;
                border-radius: 1rem !important;
                padding: 0.85rem 0.92rem !important;
                white-space: normal !important;
                border: 1px solid rgba(255, 255, 255, 0.02) !important;
                background: rgba(255, 255, 255, 0.02) !important;
                transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
            }

            .ops-sidebar-shell [data-flux-sidebar-item]:hover {
                transform: translateY(-1px);
                border-color: rgba(104, 240, 255, 0.1) !important;
                background: rgba(255, 255, 255, 0.05) !important;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            }

            .ops-sidebar-shell [data-flux-sidebar-item] > div:first-child {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                border-radius: 0.78rem;
                background: linear-gradient(180deg, rgba(14, 30, 62, 0.95), rgba(10, 19, 40, 0.82));
                border: 1px solid rgba(117, 141, 212, 0.16);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
            }

            .ops-sidebar-shell [data-flux-sidebar-item] [data-flux-icon] {
                flex-shrink: 0;
                width: 0.9rem;
                height: 0.9rem;
                margin-top: 0;
                color: #9cb5dc;
            }

            .ops-sidebar-shell [data-flux-sidebar-item] [data-content] {
                display: block !important;
                flex: 1 1 auto;
                overflow: visible !important;
                white-space: normal !important;
                font-size: 0.97rem;
                font-weight: 700;
                line-height: 1.15;
                color: #edf4ff;
            }

            .ops-sidebar-link-note {
                display: block;
                margin-top: 0.3rem;
                font-family: "Instrument Sans", ui-sans-serif, sans-serif;
                font-size: 0.7rem;
                font-weight: 500;
                line-height: 1.35;
                letter-spacing: 0.01em;
                text-transform: none;
                white-space: normal;
                color: #7f93b9;
            }

            .ops-sidebar-shell [data-flux-sidebar-item][data-current] {
                border-color: rgba(104, 240, 255, 0.24) !important;
                background: linear-gradient(135deg, rgba(38, 74, 127, 0.72), rgba(17, 34, 64, 0.88)) !important;
                color: #f4f8ff !important;
                box-shadow: inset 0 0 0 1px rgba(104, 240, 255, 0.08), 0 14px 30px rgba(0, 0, 0, 0.2);
            }

            .ops-sidebar-shell [data-flux-sidebar-item][data-current] > div:first-child {
                background: linear-gradient(180deg, rgba(104, 240, 255, 0.22), rgba(82, 129, 255, 0.18));
                border-color: rgba(104, 240, 255, 0.22);
            }

            .ops-sidebar-shell [data-flux-sidebar-item][data-current] [data-flux-icon],
            .ops-sidebar-shell [data-flux-sidebar-item][data-current] .ops-sidebar-link-note,
            .ops-sidebar-shell [data-flux-sidebar-item][data-current] [data-content] {
                color: #f4f8ff !important;
            }

            .ops-sidebar-shell [data-flux-sidebar-profile] {
                margin-top: 1rem;
                border-radius: 1.15rem;
                border: 1px solid rgba(114, 143, 255, 0.12);
                background: linear-gradient(180deg, rgba(10, 20, 42, 0.9), rgba(7, 15, 30, 0.84));
                padding: 0.55rem 0.62rem;
                box-shadow: 0 14px 30px rgba(0, 0, 0, 0.16);
            }

            .ops-sidebar-shell [data-flux-sidebar-profile] span {
                font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif;
                font-size: 0.88rem;
                font-weight: 600;
                letter-spacing: 0.02em;
                color: #edf4ff !important;
            }

            .ops-sidebar-shell [data-flux-sidebar-profile] [data-flux-icon] {
                color: #8ea4cb !important;
            }

            @media (max-width: 768px) {
                .ops-sidebar-shell {
                    width: min(20.5rem, 90vw);
                }

                .ops-sidebar-shell [data-flux-sidebar-nav] {
                    gap: 0.9rem;
                }

                .ops-sidebar-panel,
                .ops-sidebar-footer {
                    border-radius: 1.3rem !important;
                    padding: 0.95rem !important;
                }

                .ops-sidebar-shell [data-flux-sidebar-item] {
                    padding: 0.82rem 0.88rem !important;
                }

                .ops-sidebar-shell [data-flux-sidebar-item] [data-content] {
                    font-size: 0.93rem;
                }

                .ops-sidebar-link-note {
                    font-size: 0.68rem;
                }
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
                            <span class="ops-sidebar-link-note">Control room utama</span>
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="server-stack" :href="route('guilds.select')" :current="request()->routeIs('guilds.select*')" wire:navigate>
                            {{ __('Pilih Server') }}
                            <span class="ops-sidebar-link-note">Pilih workspace server aktif</span>
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('discord.setup')" :current="request()->routeIs('discord.setup')" wire:navigate>
                            {{ __('Discord Setup') }}
                            <span class="ops-sidebar-link-note">Webhook, OAuth, dan command</span>
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="sparkles" :href="route('vip-title.setup')" :current="request()->routeIs('vip-title.setup*')" wire:navigate>
                            {{ __('VIP Title Setup') }}
                            <span class="ops-sidebar-link-note">Kunci map, API, dan gamepass</span>
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="code-bracket-square" :href="route('roblox.scripts.index')" :current="request()->routeIs('roblox.scripts.*')" wire:navigate>
                            {{ __('Roblox Scripts') }}
                            <span class="ops-sidebar-link-note">Snippet siap pakai di game</span>
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </div>
            </flux:sidebar.nav>

            <flux:spacer class="hidden lg:block" />

            <flux:sidebar.nav>
                <div class="ops-sidebar-footer w-full rounded-[1.5rem] p-3">
                    <p class="ops-sidebar-block-title">Shortcuts</p>
                    <flux:sidebar.item icon="home" :href="route('home')">
                        {{ __('Landing Page') }}
                        <span class="ops-sidebar-link-note">Balik ke halaman utama</span>
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" href="https://create.roblox.com/docs" target="_blank">
                        {{ __('Roblox Docs') }}
                        <span class="ops-sidebar-link-note">Dokumentasi resmi Roblox</span>
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
