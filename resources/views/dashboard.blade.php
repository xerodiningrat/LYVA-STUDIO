<x-layouts::app :title="__('Dashboard')">
    @php
        $activeGuild = $managedGuild ?? null;
        $quickLinks = [
            ['label' => 'Pilih Server', 'href' => route('guilds.select'), 'tone' => 'cyan', 'copy' => 'Ganti server Discord yang sedang kamu kelola dari panel ini.'],
            ['label' => 'VIP Title Setup', 'href' => route('vip-title.setup'), 'tone' => 'emerald', 'copy' => 'Atur map key, gamepass, API key, dan snippet Roblox dari dashboard.'],
            ['label' => 'Discord Setup', 'href' => route('discord.setup'), 'tone' => 'violet', 'copy' => 'Rapikan command, webhook, dan koneksi bot per server.'],
            ['label' => 'Roblox Scripts', 'href' => route('roblox.scripts.index'), 'tone' => 'amber', 'copy' => 'Ambil file Roblox yang siap tempel dan sinkron dengan backend.'],
        ];

        $statusCards = [
            [
                'label' => 'Bot Routing',
                'value' => ($stats[1]['value'] ?? 0) > 0 ? 'ONLINE' : 'IDLE',
                'detail' => ($stats[1]['value'] ?? 0) . ' webhook aktif siap kirim alert.',
                'progress' => min(100, max(18, (($stats[1]['value'] ?? 0) * 18) + 22)),
                'tone' => 'cyan',
            ],
            [
                'label' => 'Issue Queue',
                'value' => ($stats[2]['value'] ?? 0) > 0 ? 'MONITOR' : 'CLEAR',
                'detail' => ($stats[2]['value'] ?? 0) . ' insiden terbuka masih perlu tindakan.',
                'progress' => min(100, max(20, (($stats[2]['value'] ?? 0) * 16) + 18)),
                'tone' => 'amber',
            ],
            [
                'label' => 'Reports Desk',
                'value' => ($stats[3]['value'] ?? 0) > 0 ? 'ACTIVE' : 'QUIET',
                'detail' => ($stats[3]['value'] ?? 0) . ' laporan player menunggu review.',
                'progress' => min(100, max(16, (($stats[3]['value'] ?? 0) * 15) + 20)),
                'tone' => 'emerald',
            ],
        ];
    @endphp

    <style>
        .ops-dashboard {
            --dash-panel: rgba(7, 16, 34, 0.82);
            --dash-panel-strong: rgba(9, 21, 43, 0.94);
            --dash-line: rgba(104, 240, 255, 0.12);
            --dash-line-strong: rgba(104, 240, 255, 0.22);
            --dash-text: #f3f7ff;
            --dash-muted: #95a6c9;
            --dash-cyan: #68f0ff;
            --dash-emerald: #76ffb8;
            --dash-violet: #8b94ff;
            --dash-amber: #ffbf6f;
            --dash-shadow: 0 30px 90px rgba(0, 0, 0, 0.38);
            --dash-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
            --dash-display: "Orbitron", "Oxanium", ui-sans-serif, sans-serif;
            position: relative;
            padding: 1.75rem;
            color: var(--dash-text);
        }

        .ops-dashboard::before,
        .ops-dashboard::after {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -1;
        }

        .ops-dashboard::before {
            background:
                radial-gradient(circle at 16% 14%, rgba(104, 240, 255, 0.11), transparent 22%),
                radial-gradient(circle at 82% 10%, rgba(139, 148, 255, 0.12), transparent 24%),
                linear-gradient(180deg, rgba(3, 8, 22, 0.98), rgba(2, 6, 18, 0.98));
        }

        .ops-dashboard::after {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 46px 46px;
            opacity: 0.18;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.9), transparent 96%);
        }

        .ops-stack,
        .ops-left,
        .ops-right,
        .ops-list,
        .ops-guild-box,
        .ops-hero-actions {
            display: grid;
            gap: 1.5rem;
        }

        .ops-hero,
        .ops-panel,
        .ops-metric,
        .ops-status-card,
        .ops-list-item,
        .ops-mini-card,
        .ops-action-card {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--dash-line);
            background: var(--dash-panel);
            box-shadow: var(--dash-shadow);
        }

        .ops-hero,
        .ops-panel {
            background: linear-gradient(180deg, rgba(9, 21, 43, 0.94), rgba(5, 13, 28, 0.92));
        }

        .ops-hero::after,
        .ops-panel::after,
        .ops-status-card::after,
        .ops-mini-card::after,
        .ops-action-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(104, 240, 255, 0.16), transparent 34%);
            pointer-events: none;
        }

        .ops-hero {
            border-radius: 2rem;
            padding: 1.6rem;
            background:
                radial-gradient(circle at top right, rgba(104, 240, 255, 0.14), transparent 24%),
                radial-gradient(circle at bottom left, rgba(139, 148, 255, 0.16), transparent 28%),
                linear-gradient(135deg, rgba(7, 18, 40, 0.95), rgba(5, 12, 26, 0.96));
        }

        .ops-eyebrow,
        .ops-chip,
        .ops-metric-label,
        .ops-kicker,
        .ops-list-label {
            font-family: var(--dash-mono);
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .ops-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(104, 240, 255, 0.18);
            background: rgba(9, 21, 43, 0.7);
            padding: 0.6rem 0.9rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--dash-cyan);
        }

        .ops-eyebrow::before {
            content: "";
            width: 0.52rem;
            height: 0.52rem;
            border-radius: 999px;
            background: var(--dash-emerald);
            box-shadow: 0 0 14px rgba(118, 255, 184, 0.9);
        }

        .ops-hero-grid,
        .ops-grid,
        .ops-stats-grid,
        .ops-status-grid,
        .ops-quick-grid {
            display: grid;
            gap: 1.25rem;
        }

        .ops-hero-grid {
            grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr);
            margin-top: 1rem;
        }

        .ops-grid {
            grid-template-columns: minmax(0, 1.18fr) minmax(320px, 0.82fr);
        }

        .ops-stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        }

        .ops-status-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .ops-quick-grid {
            grid-template-columns: 1fr;
        }

        .ops-hero-title {
            margin: 0;
            font-family: var(--dash-display);
            font-size: clamp(2.4rem, 5vw, 4.7rem);
            line-height: 0.92;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .ops-hero-title span {
            display: block;
            background: linear-gradient(90deg, var(--dash-cyan), #a1eeff, var(--dash-emerald));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .ops-lead,
        .ops-panel-copy,
        .ops-status-detail,
        .ops-list-item p,
        .ops-metric-hint,
        .ops-mini-card p {
            color: var(--dash-muted);
            line-height: 1.8;
        }

        .ops-lead {
            margin: 1rem 0 0;
            max-width: 46rem;
            font-size: 0.98rem;
        }

        .ops-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-top: 1.35rem;
        }

        .ops-chip,
        .ops-panel-pill,
        .ops-badge {
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .ops-chip {
            border: 1px solid rgba(104, 240, 255, 0.14);
            background: rgba(255, 255, 255, 0.04);
            padding: 0.55rem 0.8rem;
            color: #d7e6ff;
        }

        .ops-action-card {
            border-radius: 1.5rem;
            padding: 1rem 1.05rem;
            text-decoration: none;
            transition: transform 0.18s ease, border-color 0.18s ease;
        }

        .ops-action-card:hover {
            transform: translateY(-3px);
            border-color: var(--dash-line-strong);
        }

        .ops-action-card strong,
        .ops-panel-header h2,
        .ops-list-item h3,
        .ops-mini-card h3 {
            color: var(--dash-text);
        }

        .ops-action-card strong,
        .ops-list-item h3,
        .ops-mini-card h3 {
            display: block;
            font-size: 1rem;
        }

        .ops-action-card p,
        .ops-mini-card p {
            margin: 0.45rem 0 0;
            font-size: 0.84rem;
        }

        .ops-metric {
            border-radius: 1.55rem;
            padding: 1rem 1rem 1.1rem;
            background: linear-gradient(180deg, rgba(10, 20, 42, 0.92), rgba(5, 13, 28, 0.92));
        }

        .ops-metric-label,
        .ops-kicker,
        .ops-list-label {
            font-size: 0.68rem;
            color: var(--dash-muted);
            font-weight: 700;
        }

        .ops-metric-value,
        .ops-status-value {
            font-family: var(--dash-display);
            letter-spacing: 0.07em;
        }

        .ops-metric-value {
            margin-top: 0.75rem;
            font-size: 2.2rem;
            line-height: 1;
        }

        .ops-metric-hint {
            margin-top: 0.55rem;
            font-size: 0.82rem;
        }

        .ops-panel {
            border-radius: 1.8rem;
            padding: 1.15rem;
        }

        .ops-panel-header,
        .ops-status-top,
        .ops-list-item-title,
        .ops-guild-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: start;
        }

        .ops-panel-header {
            margin-bottom: 1rem;
        }

        .ops-panel-header h2 {
            margin: 0.2rem 0 0;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .ops-panel-copy {
            margin: 0.65rem 0 0;
            font-size: 0.88rem;
        }

        .ops-kicker {
            display: block;
            color: var(--dash-cyan);
        }

        .ops-panel-pill {
            flex-shrink: 0;
            align-self: start;
            border: 1px solid rgba(104, 240, 255, 0.16);
            background: rgba(255, 255, 255, 0.05);
            padding: 0.5rem 0.75rem;
            color: #dfe9ff;
            text-decoration: none;
        }

        .ops-status-card,
        .ops-mini-card,
        .ops-list-item {
            border-radius: 1.45rem;
            padding: 1rem;
            background: rgba(3, 11, 24, 0.8);
        }

        .ops-status-value {
            margin-top: 0.7rem;
            font-size: 1.9rem;
        }

        .ops-status-detail {
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .ops-progress {
            margin-top: 0.85rem;
            height: 0.42rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }

        .ops-progress > span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--dash-cyan), var(--dash-emerald));
            box-shadow: 0 0 18px rgba(104, 240, 255, 0.35);
        }

        .ops-list-item p {
            margin: 0.55rem 0 0;
            font-size: 0.84rem;
        }

        .ops-badge {
            flex-shrink: 0;
            padding: 0.42rem 0.7rem;
        }

        .ops-badge-cyan { background: rgba(104, 240, 255, 0.12); color: var(--dash-cyan); }
        .ops-badge-emerald { background: rgba(118, 255, 184, 0.12); color: var(--dash-emerald); }
        .ops-badge-violet { background: rgba(139, 148, 255, 0.14); color: #bcc2ff; }
        .ops-badge-amber { background: rgba(255, 191, 111, 0.14); color: var(--dash-amber); }
        .ops-badge-rose { background: rgba(255, 126, 126, 0.14); color: #ff8d8d; }
        .ops-badge-zinc { background: rgba(255, 255, 255, 0.08); color: #dbe3f4; }

        .ops-list-label {
            margin-top: 0.7rem;
            display: inline-block;
            color: #adc2e8;
        }

        .ops-guild-row {
            padding: 0.8rem 0.9rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.04);
            color: var(--dash-muted);
            font-size: 0.83rem;
        }

        .ops-guild-row strong {
            color: var(--dash-text);
        }

        @media (max-width: 1100px) {
            .ops-hero-grid,
            .ops-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .ops-dashboard {
                padding: 1rem;
            }

            .ops-stats-grid,
            .ops-status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="ops-dashboard">
        <div class="ops-stack">
            @unless ($hasBotTables)
                <section class="ops-panel">
                    <div class="ops-panel-header">
                        <div>
                            <span class="ops-kicker">Fallback Mode</span>
                            <h2>Tabel bot masih pakai data contoh</h2>
                            <p class="ops-panel-copy">Jalankan <code>php artisan migrate</code> di VPS supaya panel ini membaca data operasional asli dari Discord, webhook, alerts, dan report queue.</p>
                        </div>
                        <span class="ops-panel-pill">Migration needed</span>
                    </div>
                </section>
            @endunless

            <section class="ops-hero">
                <span class="ops-eyebrow">Discord Control Surface</span>
                <div class="ops-hero-grid">
                    <div>
                        <h1 class="ops-hero-title">Dasbor<span>operasional server</span></h1>
                        <p class="ops-lead">Panel ini sekarang difokuskan buat jadi control room utama tim: status bot, alert operasional, webhook Discord, VIP title setup, dan surface kerja per server yang jauh lebih rapi dibanding dashboard lama.</p>
                        <div class="ops-hero-meta">
                            @if (! empty($activeGuild))
                                <span class="ops-chip">Server aktif: {{ $activeGuild['name'] }}</span>
                                <span class="ops-chip">Guild ID {{ $activeGuild['id'] }}</span>
                            @else
                                <span class="ops-chip">Belum pilih server aktif</span>
                            @endif
                            <span class="ops-chip">{{ count($stats) }} modul dipantau</span>
                            <span class="ops-chip">Laravel + Discord sync</span>
                        </div>
                    </div>
                    <div class="ops-hero-actions">
                        @foreach ($quickLinks as $link)
                            <a href="{{ $link['href'] }}" class="ops-action-card">
                                <strong>{{ $link['label'] }}</strong>
                                <p>{{ $link['copy'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="ops-grid">
                <div class="ops-left">
                    <section class="ops-stats-grid">
                        @foreach ($stats as $stat)
                            <article class="ops-metric">
                                <span class="ops-metric-label">{{ $stat['label'] }}</span>
                                <div class="ops-metric-value">{{ str_pad((string) $stat['value'], 2, '0', STR_PAD_LEFT) }}</div>
                                <p class="ops-metric-hint">{{ $stat['hint'] }}</p>
                            </article>
                        @endforeach
                    </section>

                    <section class="ops-panel">
                        <div class="ops-panel-header">
                            <div>
                                <span class="ops-kicker">Status Grid</span>
                                <h2>Operational heartbeat</h2>
                                <p class="ops-panel-copy">Ringkasan cepat buat tahu kondisi bot, queue, dan beban kerja tim tanpa perlu lompat ke halaman lain.</p>
                            </div>
                            <span class="ops-panel-pill">Live summary</span>
                        </div>
                        <div class="ops-status-grid">
                            @foreach ($statusCards as $card)
                                <article class="ops-status-card">
                                    <div class="ops-status-top">
                                        <span class="ops-metric-label">{{ $card['label'] }}</span>
                                        <span class="ops-badge ops-badge-{{ $card['tone'] }}">{{ $card['tone'] }}</span>
                                    </div>
                                    <div class="ops-status-value">{{ $card['value'] }}</div>
                                    <p class="ops-status-detail">{{ $card['detail'] }}</p>
                                    <div class="ops-progress"><span style="width: {{ $card['progress'] }}%;"></span></div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="ops-panel">
                        <div class="ops-panel-header">
                            <div>
                                <span class="ops-kicker">Race Desk</span>
                                <h2>Community race events</h2>
                                <p class="ops-panel-copy">Event balap komunitas yang lagi aktif, draft, atau masih buka registrasi untuk server yang kamu pilih.</p>
                            </div>
                            <span class="ops-panel-pill">Discord admin flow</span>
                        </div>
                        <div class="ops-list">
                            @foreach ($races as $race)
                                <article class="ops-list-item">
                                    <div class="ops-list-item-title">
                                        <div>
                                            <h3>#{{ $race->id }} {{ $race->title }}</h3>
                                            <p>{{ $race->participants_count }}/{{ $race->max_players }} player · Entry {{ $race->entry_fee_robux }} R$</p>
                                        </div>
                                        <span class="ops-badge ops-badge-cyan">{{ str_replace('_', ' ', ucfirst($race->status)) }}</span>
                                    </div>
                                    <span class="ops-list-label">Race queue</span>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="ops-panel">
                        <div class="ops-panel-header">
                            <div>
                                <span class="ops-kicker">Incident Feed</span>
                                <h2>Alerts and automation</h2>
                                <p class="ops-panel-copy">Monitor deploy error, revenue anomaly, dan trigger server supaya admin cepat gerak dari satu surface.</p>
                            </div>
                            <span class="ops-panel-pill">Discord ready</span>
                        </div>
                        <div class="ops-list">
                            @foreach ($alerts as $alert)
                                @php
                                    $alertTone = match ($alert->severity) {
                                        'critical' => 'rose',
                                        'warning' => 'amber',
                                        default => 'cyan',
                                    };
                                @endphp
                                <article class="ops-list-item">
                                    <div class="ops-list-item-title">
                                        <div>
                                            <h3>{{ $alert->title }}</h3>
                                            <p>{{ $alert->message }}</p>
                                        </div>
                                        <span class="ops-badge ops-badge-{{ $alertTone }}">{{ $alert->status }}</span>
                                    </div>
                                    <span class="ops-list-label">{{ $alert->source }} · {{ optional($alert->occurred_at)->diffForHumans() ?? 'waiting for event' }}</span>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="ops-right">
                    <section class="ops-panel">
                        <div class="ops-panel-header">
                            <div>
                                <span class="ops-kicker">Active Surface</span>
                                <h2>Server focus</h2>
                                <p class="ops-panel-copy">Semua automation dan panel setup nanti mengacu ke server Discord yang dipilih di sini.</p>
                            </div>
                            <a href="{{ route('guilds.select') }}" class="ops-panel-pill">Ganti server</a>
                        </div>
                        <div class="ops-guild-box">
                            <div class="ops-guild-row"><span>Server</span><strong>{{ $activeGuild['name'] ?? 'Belum dipilih' }}</strong></div>
                            <div class="ops-guild-row"><span>Guild ID</span><strong>{{ $activeGuild['id'] ?? '-' }}</strong></div>
                            <div class="ops-guild-row"><span>Status panel</span><strong>{{ ! empty($activeGuild) ? 'Scoped' : 'Global' }}</strong></div>
                        </div>
                    </section>

                    <section class="ops-panel">
                        <div class="ops-panel-header">
                            <div>
                                <span class="ops-kicker">Discord Delivery</span>
                                <h2>Webhook health</h2>
                            </div>
                            <span class="ops-panel-pill">3 feeds</span>
                        </div>
                        <div class="ops-list">
                            @foreach ($webhooks as $webhook)
                                <article class="ops-list-item">
                                    <div class="ops-list-item-title">
                                        <div>
                                            <h3>{{ $webhook->name }}</h3>
                                            <p>{{ $webhook->channel_name }}</p>
                                        </div>
                                        <span class="ops-badge {{ $webhook->is_active ? 'ops-badge-emerald' : 'ops-badge-zinc' }}">{{ $webhook->is_active ? 'Active' : 'Paused' }}</span>
                                    </div>
                                    <span class="ops-list-label">Last delivery {{ optional($webhook->last_delivered_at)->diffForHumans() ?? 'belum pernah kirim' }}</span>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="ops-panel">
                        <div class="ops-panel-header">
                            <div>
                                <span class="ops-kicker">Game Tracking</span>
                                <h2>Connected experiences</h2>
                            </div>
                            <span class="ops-panel-pill">Universe sync</span>
                        </div>
                        <div class="ops-list">
                            @foreach ($games as $game)
                                @php
                                    $gameTone = match ($game->status) {
                                        'healthy' => 'emerald',
                                        'monitoring' => 'amber',
                                        'degraded' => 'rose',
                                        default => 'zinc',
                                    };
                                @endphp
                                <article class="ops-list-item">
                                    <div class="ops-list-item-title">
                                        <div>
                                            <h3>{{ $game->name }}</h3>
                                            <p>Universe {{ $game->universe_id }} · Place {{ $game->place_id }}</p>
                                        </div>
                                        <span class="ops-badge ops-badge-{{ $gameTone }}">{{ ucfirst($game->status) }}</span>
                                    </div>
                                    <span class="ops-list-label">Last sync {{ optional($game->last_synced_at)->diffForHumans() ?? 'belum ada sync' }}</span>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="ops-panel">
                        <div class="ops-panel-header">
                            <div>
                                <span class="ops-kicker">Moderation Desk</span>
                                <h2>Player & bug reports</h2>
                            </div>
                            <span class="ops-panel-pill">Admin queue</span>
                        </div>
                        <div class="ops-list">
                            @foreach ($reports as $report)
                                <article class="ops-list-item">
                                    <div class="ops-list-item-title">
                                        <div>
                                            <h3>{{ $report->category }} · {{ $report->reported_player_name }}</h3>
                                            <p>Reporter: {{ $report->reporter_name }}</p>
                                        </div>
                                        <span class="ops-badge ops-badge-violet">{{ ucfirst($report->priority) }}</span>
                                    </div>
                                    <p>{{ $report->summary }}</p>
                                    <span class="ops-list-label">{{ ucfirst($report->status) }}</span>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="ops-quick-grid">
                        <article class="ops-mini-card">
                            <span class="ops-kicker">VIP title tooling</span>
                            <h3>Setup map tanpa ribet</h3>
                            <p>Panel VIP Title sekarang jadi jalur utama untuk generate config map, API key, gamepass mapping, dan snippet Roblox siap tempel.</p>
                        </article>
                        <article class="ops-mini-card">
                            <span class="ops-kicker">Next layer</span>
                            <h3>Dashboard server-aware</h3>
                            <p>Flow ini sudah siap dibawa ke level berikutnya: semua modul discope per guild biar user tinggal pilih server lalu manage semuanya dari satu tempat.</p>
                        </article>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
