@php
    $title = __('Roblox Scripts');
@endphp

<x-portfolio.shell :title="$title" active-key="scripts" search-placeholder="Cari script Roblox, preview, download">
    <x-slot:head>
        <style>
            :root {
                --studio-accent: #8df27a;
                --studio-accent-2: #63f4ff;
                --studio-accent-3: #ffe18b;
            }

            .script-library-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        </style>
        @include('partials.studio-workspace-style')
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('vip-title.setup') }}" class="portfolio-shell-action">Buka VIP Setup</a>
    </x-slot:headerActions>

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
</x-portfolio.shell>
