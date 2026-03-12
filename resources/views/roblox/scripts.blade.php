@php
    $title = __('Roblox Scripts');
    $navLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pilih Server', 'href' => route('guilds.select')],
        ['label' => 'Discord Setup', 'href' => route('discord.setup')],
        ['label' => 'VIP Title Setup', 'href' => route('vip-title.setup')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            :root {
                --studio-accent: #8df27a;
                --studio-accent-2: #63f4ff;
                --studio-accent-3: #ffe18b;
            }

            .script-library-grid {
                display: grid;
                gap: 1rem;
                margin-top: 1.2rem;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
                'activeHref' => route('roblox.scripts.index'),
                'ctaHref' => route('roblox.scripts.index'),
                'ctaLabel' => 'Script Library',
                'brandTitle' => 'LYVA Studio',
                'brandCopy' => 'Library script Roblox yang sekarang terasa lebih modern, rapi, dan nyaman dipakai admin.',
            ])

            <section class="studio-hero" data-studio-hover>
                <div class="studio-hero-grid">
                    <div>
                        <span class="studio-kicker">Roblox Script Library</span>
                        <h2>Script siap <span>preview, copy, dan deploy</span></h2>
                        <p>Semua file penting untuk Roblox saya tampilkan seperti library internal. Admin bisa preview dulu, lalu download versi yang sudah sinkron dengan backend Laravel tanpa harus buka folder project manual.</p>

                        <div class="studio-stats-grid">
                            <article class="studio-stat" data-studio-hover>
                                <span class="studio-label">Scripts</span>
                                <strong>{{ count($scripts) }}</strong>
                                <p class="studio-copy">File Roblox yang tersedia di library project ini.</p>
                            </article>
                            <article class="studio-stat" data-studio-hover>
                                <span class="studio-label">Backend</span>
                                <strong>Sync</strong>
                                <p class="studio-copy">Download file ikut memakai config backend yang aktif.</p>
                            </article>
                            <article class="studio-stat" data-studio-hover>
                                <span class="studio-label">Workflow</span>
                                <strong>Fast</strong>
                                <p class="studio-copy">Preview lalu download tanpa lompat ke folder source.</p>
                            </article>
                            <article class="studio-stat" data-studio-hover>
                                <span class="studio-label">Studio</span>
                                <strong>Ready</strong>
                                <p class="studio-copy">Cocok untuk tempel cepat ke Roblox Studio.</p>
                            </article>
                        </div>
                    </div>

                    <aside class="studio-stack">
                        <article class="studio-card" data-studio-hover>
                            <div class="studio-panel-header">
                                <div>
                                    <span class="studio-label">How To Use</span>
                                    <h3 style="margin-top:.75rem;">Alur kerja paling cepat</h3>
                                </div>
                                <span class="studio-pill">3 Step</span>
                            </div>

                            <div class="studio-list-grid" style="margin-top:0;">
                                <article class="studio-card" data-studio-hover>
                                    <strong>1. Pilih script yang dibutuhkan</strong>
                                    <p class="studio-copy" style="margin-top:.45rem;">Semua script diberi label dan deskripsi agar admin cepat menemukan file yang tepat.</p>
                                </article>
                                <article class="studio-card" data-studio-hover>
                                    <strong>2. Preview isi file</strong>
                                    <p class="studio-copy" style="margin-top:.45rem;">Cek dulu isi script jika ingin memastikan config, endpoint, atau flow yang dipakai.</p>
                                </article>
                                <article class="studio-card" data-studio-hover>
                                    <strong>3. Download lalu publish</strong>
                                    <p class="studio-copy" style="margin-top:.45rem;">Ambil file yang sudah sinkron dengan project ini dan tempel ke Roblox Studio.</p>
                                </article>
                            </div>
                        </article>
                    </aside>
                </div>
            </section>

            <section class="script-library-grid">
                @foreach ($scripts as $script)
                    <article class="studio-card" data-studio-hover>
                        <span class="studio-label">{{ $script['filename'] }}</span>
                        <h3 style="margin-top:.95rem;">{{ $script['label'] }}</h3>
                        <p class="studio-copy" style="margin-top:.7rem;">{{ $script['description'] }}</p>

                        <div class="studio-actions">
                            <a href="{{ route('roblox.scripts.show', $script['slug']) }}" class="studio-button-ghost">Preview</a>
                            <a href="{{ route('roblox.scripts.download', $script['slug']) }}" class="studio-button">Download</a>
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </body>
</html>
