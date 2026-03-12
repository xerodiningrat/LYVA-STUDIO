@php
    $title = __('Discord Setup');
    $navLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pilih Server', 'href' => route('guilds.select')],
        ['label' => 'VIP Title Setup', 'href' => route('vip-title.setup')],
        ['label' => 'Roblox Scripts', 'href' => route('roblox.scripts.index')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            :root {
                --studio-accent: #79e7ff;
                --studio-accent-2: #82ffbf;
                --studio-accent-3: #ffc77b;
            }
        </style>
        @include('partials.studio-workspace-style')
    </head>
    <body>
        <div class="studio-shell">
            <div class="studio-glow-a"></div>
            <div class="studio-glow-b"></div>
            <div class="studio-glow-c"></div>

            @include('partials.studio-topbar', [
                'navLinks' => $navLinks,
                'activeHref' => route('discord.setup'),
                'ctaHref' => route('discord.setup'),
                'ctaLabel' => 'Discord Setup',
                'brandTitle' => 'LYVA Studio',
                'brandCopy' => 'Panel Discord setup yang lebih bersih, interaktif, dan gampang dipakai saat onboarding bot.',
            ])

            <section class="studio-hero" data-studio-hover>
                <div class="studio-hero-grid">
                    <div>
                        <span class="studio-kicker">Discord Command Setup</span>
                        <h2>Deploy bot <span>lebih cepat dan lebih jelas</span></h2>
                        <p>Semua langkah penting untuk mengaktifkan bot Discord sekarang dirapikan ke satu tempat. Admin bisa cek environment, copy endpoint, invite bot, dan register command tanpa lompat-lompat panel.</p>

                        <div class="studio-stats-grid">
                            <article class="studio-stat" data-studio-hover>
                                <span class="studio-label">Checks</span>
                                <strong>{{ count($setupChecks) }}</strong>
                                <p class="studio-copy">Titik environment yang dipantau sebelum bot live.</p>
                            </article>
                            <article class="studio-stat" data-studio-hover>
                                <span class="studio-label">Commands</span>
                                <strong>{{ count($commands) }}</strong>
                                <p class="studio-copy">Command terminal siap copy untuk deploy.</p>
                            </article>
                            <article class="studio-stat" data-studio-hover>
                                <span class="studio-label">Features</span>
                                <strong>{{ count($features) }}</strong>
                                <p class="studio-copy">Workflow Discord yang sudah disiapkan.</p>
                            </article>
                            <article class="studio-stat" data-studio-hover>
                                <span class="studio-label">Status</span>
                                <strong>Ready</strong>
                                <p class="studio-copy">Invite, endpoint, dan register flow ada di halaman ini.</p>
                            </article>
                        </div>
                    </div>

                    <aside class="studio-stack">
                        <article class="studio-card" data-studio-hover>
                            <div class="studio-panel-header">
                                <div>
                                    <span class="studio-label">Quick Flow</span>
                                    <h3 style="margin-top:.75rem;">Urutan setup paling aman</h3>
                                    <p class="studio-copy" style="margin-top:.5rem;">Urutan ini saya rapikan supaya admin baru pun bisa langsung ikuti tanpa bingung.</p>
                                </div>
                                <span class="studio-pill">4 Step</span>
                            </div>
                            <div class="studio-list-grid" style="margin-top:0;">
                                <article class="studio-card" data-studio-hover>
                                    <strong>1. Invite bot ke server</strong>
                                    <p class="studio-copy" style="margin-top:.45rem;">Pastikan bot masuk ke guild yang benar sebelum konfigurasi lain dimulai.</p>
                                </article>
                                <article class="studio-card" data-studio-hover>
                                    <strong>2. Isi interaction endpoint</strong>
                                    <p class="studio-copy" style="margin-top:.45rem;">Gunakan URL publik dari Laravel agar interaksi slash command diterima Discord.</p>
                                </article>
                                <article class="studio-card" data-studio-hover>
                                    <strong>3. Register ulang slash command</strong>
                                    <p class="studio-copy" style="margin-top:.45rem;">Jalankan command register sesudah env dan endpoint sudah beres.</p>
                                </article>
                                <article class="studio-card" data-studio-hover>
                                    <strong>4. Cek semua env</strong>
                                    <p class="studio-copy" style="margin-top:.45rem;">Verifikasi token, secret, URL, dan status runtime sebelum dipakai user.</p>
                                </article>
                            </div>
                        </article>
                    </aside>
                </div>
            </section>

            <section class="studio-panel-grid">
                <section class="studio-panel" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Config Status</span>
                            <h3 style="margin-top:.75rem;">Environment checks</h3>
                            <p class="studio-copy" style="margin-top:.45rem;">Ringkasan env yang paling sering jadi sumber error saat setup Discord.</p>
                        </div>
                        <span class="studio-pill">Runtime</span>
                    </div>

                    <div class="studio-list-grid">
                        @foreach ($setupChecks as $check)
                            <article class="studio-card" data-studio-hover>
                                <div class="studio-row-between">
                                    <div>
                                        <strong style="display:block;font-size:1rem;">{{ $check['label'] }}</strong>
                                        <p class="studio-copy" style="margin-top:.45rem;word-break:break-word;">{{ $check['value'] }}</p>
                                    </div>
                                    <span class="studio-badge {{ $check['ready'] ? 'studio-badge-ok' : 'studio-badge-warn' }}">
                                        {{ $check['ready'] ? 'Ready' : 'Check' }}
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <aside class="studio-stack">
                    <section class="studio-panel" data-studio-hover>
                        <div class="studio-panel-header">
                            <div>
                                <span class="studio-label">Links</span>
                                <h3 style="margin-top:.75rem;">Invite dan endpoint penting</h3>
                            </div>
                            <span class="studio-pill">Public URLs</span>
                        </div>

                        <div class="studio-stack">
                            <div class="studio-field">
                                <label>Invite URL</label>
                                <div class="studio-code">{{ $inviteUrl ?? 'Isi DISCORD_APPLICATION_ID dulu.' }}</div>
                            </div>
                            <div class="studio-field">
                                <label>Interaction Endpoint URL</label>
                                <div class="studio-code">{{ $interactionUrl ?? 'Isi APP_URL dulu.' }}</div>
                            </div>
                        </div>

                        <p class="studio-help" style="margin-top:1rem;">Discord hanya menerima endpoint publik. Jangan pakai <span class="studio-inline-code">localhost</span> atau <span class="studio-inline-code">127.0.0.1</span> untuk production.</p>
                    </section>

                    <section class="studio-panel" data-studio-hover>
                        <div class="studio-panel-header">
                            <div>
                                <span class="studio-label">Command Set</span>
                                <h3 style="margin-top:.75rem;">Fitur slash command</h3>
                                <p class="studio-copy" style="margin-top:.45rem;">Slash commands yang sudah disiapkan sekarang ditampilkan dalam grid yang lebih rapi dan gampang dipindai.</p>
                            </div>
                            <span class="studio-pill">Bot Ready</span>
                        </div>

                        <div class="studio-chip-grid">
                            @foreach ($features as $feature)
                                <article class="studio-card" data-studio-hover>
                                    <strong>{{ $feature }}</strong>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </aside>
            </section>

            <section class="studio-panel" data-studio-hover style="margin-top:1.2rem;">
                <div class="studio-panel-header">
                    <div>
                        <span class="studio-label">Terminal Steps</span>
                        <h3 style="margin-top:.75rem;">Command siap jalankan</h3>
                        <p class="studio-copy" style="margin-top:.45rem;">Saya rapikan jadi stack yang nyaman di-desktop maupun mobile, jadi admin tinggal copy step yang dibutuhkan.</p>
                    </div>
                    <span class="studio-pill">CLI</span>
                </div>

                <div class="studio-list-grid">
                    @foreach ($commands as $command)
                        <div class="studio-code">{{ $command }}</div>
                    @endforeach
                </div>
            </section>
        </div>
    </body>
</html>
