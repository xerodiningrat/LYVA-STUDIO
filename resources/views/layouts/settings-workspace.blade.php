<x-portfolio.shell title="Settings Akun" active-key="settings" search-placeholder="Cari profil, password, 2FA, appearance">
    <x-slot:head>
        <style>
            :root {
                --studio-accent: #79e7ff;
                --studio-accent-2: #82ffbf;
                --studio-accent-3: #ffc77b;
                --studio-danger: #ff8c9d;
            }

            .settings-shell {
                display: grid;
                gap: 1.25rem;
            }

            .settings-form-shell {
                display: grid;
                gap: 1rem;
            }

            .settings-form-grid {
                display: grid;
                gap: 1rem;
            }

            .settings-form-actions {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: .85rem;
                margin-top: .25rem;
            }

            .settings-status {
                display: inline-flex;
                align-items: center;
                gap: .45rem;
                padding: .5rem .8rem;
                border-radius: 999px;
                background: rgba(130, 255, 191, .1);
                color: #82ffbf;
                font-size: .8rem;
                font-weight: 700;
            }

            .settings-status::before {
                content: "";
                width: .48rem;
                height: .48rem;
                border-radius: 999px;
                background: currentColor;
                box-shadow: 0 0 12px currentColor;
            }

            .settings-error {
                color: var(--studio-danger);
                font-size: .82rem;
                line-height: 1.55;
            }

            .settings-danger-panel {
                margin-top: 1.2rem;
                padding-top: 1.2rem;
                border-top: 1px solid rgba(255,255,255,.08);
            }

            .settings-shell-top {
                display: grid;
                gap: 1rem;
                grid-template-columns: 1.1fr .9fr;
            }

            .settings-top-card {
                position: relative;
                overflow: hidden;
            }

            .settings-top-card::before {
                content: "";
                position: absolute;
                inset: 0 auto auto 0;
                width: 100%;
                height: 1px;
                background: linear-gradient(90deg, rgba(121, 231, 255, .62), rgba(130, 255, 191, 0));
            }

            .settings-quick-grid {
                display: grid;
                gap: .9rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .settings-quick-item {
                padding: 1rem 1.05rem;
                border-radius: 1.2rem;
                border: 1px solid rgba(255,255,255,.06);
                background: rgba(255,255,255,.03);
            }

            @media (max-width: 980px) {
                .settings-shell-top,
                .settings-quick-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        @include('partials.studio-workspace-style')
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('dashboard') }}" wire:navigate class="portfolio-shell-action">Kembali ke Dashboard</a>
    </x-slot:headerActions>

    <div class="settings-shell">
        <section class="settings-shell-top">
            <article class="studio-hero settings-top-card" data-studio-hover>
                <div>
                    <span class="studio-kicker">Account Workspace</span>
                    <h2>Settings akun <span>tetap satu tema workspace</span></h2>
                    <p>Halaman profil, password, two-factor, dan appearance sekarang tetap berada di shell dashboard yang sama, jadi tidak terasa lompat ke tampilan lama lagi.</p>
                </div>
            </article>

            <article class="studio-panel settings-top-card" data-studio-hover>
                <div class="studio-panel-header">
                    <div>
                        <span class="studio-label">Quick Notes</span>
                        <h3 style="margin-top:.75rem;">Hal penting untuk akun ini</h3>
                    </div>
                    <span class="studio-pill">Secure</span>
                </div>

                <div class="settings-quick-grid">
                    <article class="settings-quick-item" data-studio-hover>
                        <span class="studio-label">Profile</span>
                        <p class="studio-copy" style="margin:.55rem 0 0;">Perbarui nama dan email utama yang dipakai login dan notifikasi.</p>
                    </article>
                    <article class="settings-quick-item" data-studio-hover>
                        <span class="studio-label">Password</span>
                        <p class="studio-copy" style="margin:.55rem 0 0;">Gunakan password kuat dan ganti secara berkala untuk keamanan akun.</p>
                    </article>
                    <article class="settings-quick-item" data-studio-hover>
                        <span class="studio-label">Two-factor</span>
                        <p class="studio-copy" style="margin:.55rem 0 0;">Aktifkan 2FA kalau ingin lapisan keamanan tambahan saat login.</p>
                    </article>
                    <article class="settings-quick-item" data-studio-hover>
                        <span class="studio-label">Appearance</span>
                        <p class="studio-copy" style="margin:.55rem 0 0;">Atur mode tampilan yang paling nyaman buat workflow harian kamu.</p>
                    </article>
                </div>
            </article>
        </section>

        {{ $slot }}
    </div>
</x-portfolio.shell>
