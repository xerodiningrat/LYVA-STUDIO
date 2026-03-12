<x-portfolio.shell title="Pilih Server" active-key="guilds" search-placeholder="Cari server Discord yang ingin dikelola">
    <x-slot:head>
        <style>
            :root {
                --guild-primary: #68f0ff;
                --guild-secondary: #6f86ff;
                --guild-accent: #7cffb2;
                --guild-muted: #9eacc8;
                --guild-line: rgba(118,224,255,.14);
            }

            .guild-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 16px;
            }

            .guild-card {
                padding: 20px;
                border-radius: 24px;
                background: linear-gradient(180deg, rgba(8,14,29,.9), rgba(6,11,23,.9));
                border: 1px solid rgba(104,240,255,.08);
                box-shadow: 0 30px 80px rgba(0,0,0,.35);
            }

            .guild-top {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            .guild-icon {
                width: 62px;
                height: 62px;
                border-radius: 18px;
                display: grid;
                place-items: center;
                background: linear-gradient(135deg, rgba(104,240,255,.2), rgba(111,134,255,.24));
                border: 1px solid rgba(104,240,255,.14);
                overflow: hidden;
            }

            .guild-icon img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .guild-icon span {
                font: 800 22px "Space Grotesk", sans-serif;
                color: #dffaff;
            }

            .guild-name strong {
                display: block;
                font: 800 20px/1.1 "Space Grotesk", sans-serif;
                letter-spacing: .04em;
            }

            .guild-name small {
                display: block;
                margin-top: 6px;
                color: var(--guild-muted);
                font: 700 11px "JetBrains Mono", monospace;
                word-break: break-all;
            }

            .guild-meta {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin-top: 16px;
            }

            .guild-badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 9px 11px;
                border-radius: 999px;
                background: rgba(104,240,255,.08);
                border: 1px solid rgba(104,240,255,.16);
                color: var(--guild-primary);
                font: 700 10px "JetBrains Mono", monospace;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .guild-badge.guild-owner {
                background: rgba(124,255,178,.09);
                border-color: rgba(124,255,178,.22);
                color: var(--guild-accent);
            }

            .guild-copy {
                margin-top: 16px;
                color: var(--guild-muted);
                font-size: 14px;
                line-height: 1.75;
            }

            .guild-actions {
                margin-top: 18px;
            }

            .guild-cta {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                padding: 15px 18px;
                border-radius: 16px;
                font-size: 14px;
                font-weight: 700;
                border: 1px solid rgba(104,240,255,.16);
                background: rgba(11,19,38,.58);
                color: #f4f7ff;
                cursor: pointer;
            }

            .guild-cta.guild-cta-primary {
                color: #04111e;
                background: linear-gradient(135deg, var(--guild-primary), var(--guild-secondary));
                border-color: transparent;
            }

            .guild-empty {
                padding: 32px;
                border-radius: 24px;
                background: rgba(8,14,29,.72);
                border: 1px dashed rgba(104,240,255,.18);
                color: var(--guild-muted);
                text-align: center;
                line-height: 1.8;
            }
        </style>
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('dashboard') }}" wire:navigate class="portfolio-shell-action">Buka Dashboard</a>
    </x-slot:headerActions>

    <section class="studio-hero" data-studio-hover>
        <div class="studio-hero-grid">
            <div>
                <span class="studio-kicker">Guild Picker</span>
                <h2>Pilih server <span>yang benar-benar aktif</span></h2>
                <p>Yang tampil di bawah adalah server yang kamu punya akses kelola. Server dengan badge <strong>Bot Joined</strong> sudah siap langsung dibuka dashboard-nya.</p>

                <div class="studio-stats-grid">
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Detected</span>
                        <strong>{{ count($guilds) }}</strong>
                        <p class="studio-copy">Total server yang terdeteksi dari akun Discord kamu.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Ready</span>
                        <strong>{{ count($joinedGuilds) }}</strong>
                        <p class="studio-copy">Server yang sudah siap langsung dibuka dashboard-nya.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Flow</span>
                        <strong>Manage</strong>
                        <p class="studio-copy">Pilih server yang tepat sebelum setup Discord, VIP Title, dan Roblox.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Access</span>
                        <strong>Owner/Admin</strong>
                        <p class="studio-copy">Hanya guild yang bisa kamu kelola yang ditampilkan di sini.</p>
                    </article>
                </div>
            </div>

            <aside class="studio-stack">
                <article class="studio-card" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Quick Notes</span>
                            <h3 style="margin-top:.75rem;">Cara pilih server yang aman</h3>
                        </div>
                        <span class="studio-pill">3 Step</span>
                    </div>
                    <div class="studio-list-grid" style="margin-top:0;">
                        <article class="studio-card" data-studio-hover>
                            <strong>1. Pilih guild yang benar</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">Pastikan server yang kamu buka memang server produksi atau workspace yang mau dikelola.</p>
                        </article>
                        <article class="studio-card" data-studio-hover>
                            <strong>2. Cek status Bot Joined</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">Kalau bot belum masuk, guild itu belum bisa dipakai untuk workflow utama dashboard.</p>
                        </article>
                        <article class="studio-card" data-studio-hover>
                            <strong>3. Lanjut ke setup</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">Sesudah pilih server, semua page setup akan otomatis mengacu ke guild aktif itu.</p>
                        </article>
                    </div>
                </article>
            </aside>
        </div>
    </section>

    <section class="guild-grid">
        @forelse ($guilds as $guild)
            <form method="POST" action="{{ route('guilds.select.store', $guild['id']) }}" class="guild-card">
                @csrf
                <div class="guild-top">
                    <div class="guild-icon">
                        @if ($guild['icon_url'])
                            <img src="{{ $guild['icon_url'] }}" alt="{{ $guild['name'] }}">
                        @else
                            <span>{{ strtoupper(substr($guild['name'], 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="guild-name">
                        <strong>{{ $guild['name'] }}</strong>
                        <small>{{ $guild['id'] }}</small>
                    </div>
                </div>

                <div class="guild-meta">
                    <span class="guild-badge">Manageable</span>
                    <span class="guild-badge {{ $guild['bot_joined'] ? '' : 'guild-owner' }}">{{ $guild['bot_joined'] ? 'Bot Joined' : 'Bot Missing' }}</span>
                    @if (! empty($guild['persisted']))
                        <span class="guild-badge">Restored</span>
                    @endif
                    @if ($guild['owner'])
                        <span class="guild-badge guild-owner">Owner</span>
                    @endif
                </div>

                <p class="guild-copy">
                    @if (! empty($guild['persisted']))
                        Server ini dipulihkan dari pilihan terakhir yang tersimpan di akunmu. Kalau daftar guild Discord belum muncul lagi, login ulang Discord nanti akan menyegarkan nama dan daftar server terbarunya.
                    @elseif ($guild['bot_joined'])
                        Pilih server ini untuk membuka dashboard, setup Discord bot, VIP title tools, dan workflow Roblox yang terkait langsung dengan guild ini.
                    @else
                        Kamu punya akses kelola di server ini, tapi bot LYVA belum terdeteksi masuk. Masukkan bot dulu supaya server ini bisa dipilih dari dashboard.
                    @endif
                </p>

                <div class="guild-actions">
                    <button class="guild-cta {{ $guild['bot_joined'] ? 'guild-cta-primary' : '' }}" type="submit" @disabled(! $guild['bot_joined'])>
                        {{ $guild['bot_joined'] ? 'Kelola server ini' : 'Bot belum masuk' }}
                    </button>
                </div>
            </form>
        @empty
            <div class="guild-empty">
                Belum ada server yang bisa kamu kelola dengan akun Discord ini. Pastikan akunmu punya akses Manage Server atau Administrator di server tujuan.
            </div>
        @endforelse
    </section>

    @if (count($guilds) > 0 && count($joinedGuilds) === 0)
        <div class="guild-empty">
            Server kamu terdeteksi, tapi bot LYVA belum ada di server-server itu. Invite bot ke server yang kamu kelola, lalu login ulang Discord supaya statusnya ikut ter-refresh.
        </div>
    @endif
</x-portfolio.shell>
