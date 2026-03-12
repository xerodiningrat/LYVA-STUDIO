@php
    $title = __('Discord Setup');
    $readyChecks = collect($setupChecks)->where('ready', true)->count();
    $totalChecks = max(1, count($setupChecks));
    $checkCompletion = (int) round(($readyChecks / $totalChecks) * 100);
    $signalBars = [
        ['label' => 'Checks', 'value' => $checkCompletion, 'tone' => 'var(--studio-accent)'],
        ['label' => 'Commands', 'value' => min(100, max(20, count($commands) * 24)), 'tone' => '#8bd7ff'],
        ['label' => 'Features', 'value' => min(100, max(20, count($features) * 16)), 'tone' => 'var(--studio-accent-2)'],
        ['label' => 'Endpoint', 'value' => filled($interactionUrl ?? null) ? 92 : 24, 'tone' => '#ffd27e'],
    ];
    $discordParticles = range(1, 12);
@endphp

<x-portfolio.shell :title="$title" active-key="discord" search-placeholder="Cari Discord setup, endpoint, invite, command">
    <x-slot:head>
        <style>
            :root {
                --studio-accent: #79e7ff;
                --studio-accent-2: #82ffbf;
                --studio-accent-3: #ffc77b;
            }

            .discord-hero {
                position: relative;
                isolation: isolate;
                overflow: hidden;
            }

            .discord-particles {
                position: absolute;
                inset: 0;
                pointer-events: none;
                z-index: 0;
                overflow: hidden;
            }

            .discord-particle {
                position: absolute;
                width: var(--size);
                height: var(--size);
                top: var(--top);
                left: var(--left);
                border-radius: 999px;
                background: radial-gradient(circle, rgba(121, 231, 255, .24), rgba(121, 231, 255, 0));
                border: 1px solid rgba(121, 231, 255, .18);
                animation: discordFloat var(--duration) ease-in-out infinite;
                animation-delay: var(--delay);
            }

            .discord-hero-glow {
                position: absolute;
                inset: auto -4rem -5rem auto;
                width: 18rem;
                height: 18rem;
                border-radius: 999px;
                background: radial-gradient(circle, rgba(130, 255, 191, .18), rgba(130, 255, 191, 0) 72%);
                filter: blur(12px);
                animation: discordPulse 10s ease-in-out infinite;
            }

            .discord-signal-card,
            .discord-check-card,
            .discord-link-card,
            .discord-command-card {
                position: relative;
                overflow: hidden;
                background:
                    radial-gradient(circle at top right, rgba(121, 231, 255, .1), transparent 35%),
                    linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.018));
            }

            .discord-signal-card::before,
            .discord-check-card::before,
            .discord-link-card::before,
            .discord-command-card::before {
                content: "";
                position: absolute;
                inset: 0 auto auto 0;
                width: 100%;
                height: 1px;
                background: linear-gradient(90deg, rgba(121, 231, 255, .65), rgba(130, 255, 191, 0));
            }

            .discord-signal-grid,
            .discord-links-grid {
                display: grid;
                gap: 1rem;
            }

            .discord-links-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .discord-signal-bars {
                display: grid;
                gap: .85rem;
                margin-top: 1rem;
            }

            .discord-signal-bar {
                display: grid;
                gap: .45rem;
            }

            .discord-signal-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                font-size: .9rem;
            }

            .discord-signal-track {
                height: 10px;
                border-radius: 999px;
                overflow: hidden;
                background: rgba(255,255,255,.08);
            }

            .discord-signal-track span {
                display: block;
                width: var(--value);
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--tone), rgba(255,255,255,.92));
                box-shadow: 0 0 16px color-mix(in srgb, var(--tone) 45%, transparent);
                transform-origin: left center;
                transform: scaleX(0);
                animation: discordGrow 1.2s cubic-bezier(.22, 1, .36, 1) forwards;
            }

            .discord-completion {
                display: grid;
                gap: .9rem;
                padding: 1rem;
                border-radius: 1.2rem;
                border: 1px solid rgba(255,255,255,.06);
                background: rgba(255,255,255,.03);
            }

            .discord-completion strong {
                font: 700 clamp(1.8rem, 4vw, 2.6rem)/1 var(--studio-display);
            }

            .discord-check-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }

            .discord-check-card {
                min-height: 100%;
            }

            .discord-check-card p {
                word-break: break-word;
            }

            .discord-check-status {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                padding: .45rem .72rem;
                border-radius: 999px;
                font-size: .72rem;
                font-weight: 800;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .discord-check-status::before {
                content: "";
                width: .55rem;
                height: .55rem;
                border-radius: 999px;
                background: currentColor;
                box-shadow: 0 0 16px currentColor;
            }

            .discord-check-status.is-ready {
                color: #82ffbf;
                background: rgba(130, 255, 191, .12);
            }

            .discord-check-status.is-check {
                color: #ffc77b;
                background: rgba(255, 199, 123, .12);
            }

            .discord-link-card {
                min-height: 100%;
            }

            .discord-link-card .studio-code {
                min-height: 5.5rem;
            }

            .discord-feature-grid {
                display: grid;
                gap: .9rem;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }

            .discord-command-list {
                display: grid;
                gap: 1rem;
            }

            .discord-command-card {
                display: grid;
                gap: .8rem;
                padding: 1rem 1.05rem;
                border-radius: 1.3rem;
                border: 1px solid rgba(255,255,255,.06);
            }

            .discord-command-card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .8rem;
            }

            .discord-command-index {
                display: inline-grid;
                place-items: center;
                width: 2rem;
                height: 2rem;
                border-radius: 999px;
                background: linear-gradient(135deg, var(--studio-accent), var(--studio-accent-2));
                color: #04101d;
                font: 800 .82rem/1 var(--studio-display);
                box-shadow: 0 0 18px rgba(121, 231, 255, .22);
            }

            .discord-command-card .studio-code {
                margin: 0;
            }

            @keyframes discordFloat {
                0%, 100% {
                    transform: translate3d(0, 0, 0) scale(1);
                    opacity: .18;
                }

                50% {
                    transform: translate3d(0, -16px, 0) scale(1.08);
                    opacity: .76;
                }
            }

            @keyframes discordPulse {
                0%, 100% {
                    transform: scale(1);
                    opacity: .82;
                }

                50% {
                    transform: scale(1.12);
                    opacity: 1;
                }
            }

            @keyframes discordGrow {
                to {
                    transform: scaleX(1);
                }
            }

            @media (max-width: 860px) {
                .discord-links-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        @include('partials.studio-workspace-style')
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('dashboard') }}" wire:navigate class="portfolio-shell-action">Kembali ke Dashboard</a>
    </x-slot:headerActions>

    <section class="studio-hero discord-hero" data-studio-hover>
        <div class="discord-particles" aria-hidden="true">
            <div class="discord-hero-glow"></div>
            @foreach ($discordParticles as $particle)
                <span
                    class="discord-particle"
                    style="--size: {{ 0.42 + (($particle % 4) * 0.22) }}rem; --top: {{ 7 + (($particle * 8) % 78) }}%; --left: {{ 5 + (($particle * 12) % 88) }}%; --delay: -{{ $particle * 0.32 }}s; --duration: {{ 7 + ($particle % 5) }}s;"
                ></span>
            @endforeach
        </div>

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
                <article class="studio-card discord-signal-card" data-studio-hover>
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

                <article class="studio-card discord-signal-card" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Discord Runtime</span>
                            <h3 style="margin-top:.75rem;">Progress setup dalam satu panel</h3>
                        </div>
                        <span class="studio-pill">Live</span>
                    </div>

                    <div class="discord-completion">
                        <span class="studio-label">Setup completion</span>
                        <strong>{{ $checkCompletion }}%</strong>
                        <p class="studio-copy" style="margin:0;">{{ $readyChecks }} dari {{ count($setupChecks) }} environment checks sudah siap dipakai.</p>
                    </div>

                    <div class="discord-signal-bars">
                        @foreach ($signalBars as $signal)
                            <div class="discord-signal-bar">
                                <div class="discord-signal-meta">
                                    <span>{{ $signal['label'] }}</span>
                                    <strong>{{ $signal['value'] }}%</strong>
                                </div>
                                <div class="discord-signal-track">
                                    <span style="--value: {{ $signal['value'] }}%; --tone: {{ $signal['tone'] }};"></span>
                                </div>
                            </div>
                        @endforeach
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

            <div class="discord-check-grid">
                @foreach ($setupChecks as $check)
                    <article class="studio-card discord-check-card" data-studio-hover>
                        <div class="studio-row-between">
                            <div>
                                <strong style="display:block;font-size:1rem;">{{ $check['label'] }}</strong>
                                <p class="studio-copy" style="margin-top:.45rem;word-break:break-word;">{{ $check['value'] }}</p>
                            </div>
                            <span class="discord-check-status {{ $check['ready'] ? 'is-ready' : 'is-check' }}">
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

                <div class="discord-links-grid">
                    <div class="studio-field studio-card discord-link-card" data-studio-hover>
                        <label>Invite URL</label>
                        <div class="studio-code">{{ $inviteUrl ?? 'Isi DISCORD_APPLICATION_ID dulu.' }}</div>
                    </div>
                    <div class="studio-field studio-card discord-link-card" data-studio-hover>
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

                <div class="discord-feature-grid">
                    @foreach ($features as $feature)
                        <article class="studio-card" data-studio-hover>
                            <strong>{{ $feature }}</strong>
                        </article>
                    @endforeach
                </div>
            </section>
        </aside>
    </section>

    <section class="studio-panel" data-studio-hover>
        <div class="studio-panel-header">
            <div>
                <span class="studio-label">Terminal Steps</span>
                <h3 style="margin-top:.75rem;">Command siap jalankan</h3>
                <p class="studio-copy" style="margin-top:.45rem;">Saya rapikan jadi stack yang nyaman di-desktop maupun mobile, jadi admin tinggal copy step yang dibutuhkan.</p>
            </div>
            <span class="studio-pill">CLI</span>
        </div>

        <div class="discord-command-list">
            @foreach ($commands as $index => $command)
                <article class="discord-command-card" data-studio-hover>
                    <div class="discord-command-card-header">
                        <div style="display:flex;align-items:center;gap:.8rem;">
                            <span class="discord-command-index">{{ $index + 1 }}</span>
                            <strong>Step {{ $index + 1 }}</strong>
                        </div>
                        <span class="studio-pill">CLI</span>
                    </div>
                    <div class="studio-code">{{ $command }}</div>
                </article>
            @endforeach
        </div>
    </section>

    <x-slot:scripts>
        <script>
            (() => {
                const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (prefersReducedMotion || window.innerWidth < 960) {
                    return;
                }

                document.querySelectorAll('[data-studio-hover]').forEach((card) => {
                    const reset = () => {
                        card.style.transform = '';
                        card.style.setProperty('--mx', '50%');
                        card.style.setProperty('--my', '50%');
                    };

                    card.addEventListener('pointermove', (event) => {
                        const rect = card.getBoundingClientRect();
                        const x = event.clientX - rect.left;
                        const y = event.clientY - rect.top;
                        const rotateY = ((x / rect.width) - 0.5) * 6;
                        const rotateX = (0.5 - (y / rect.height)) * 5;

                        card.style.setProperty('--mx', `${(x / rect.width) * 100}%`);
                        card.style.setProperty('--my', `${(y / rect.height) * 100}%`);
                        card.style.transform = `perspective(1400px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-5px)`;
                    });

                    card.addEventListener('pointerleave', reset);
                    card.addEventListener('pointercancel', reset);
                });
            })();
        </script>
    </x-slot:scripts>
</x-portfolio.shell>
