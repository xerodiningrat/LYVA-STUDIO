@php
    use Illuminate\Support\Str;

    $title = __('Dashboard');
    $activeGuild = $managedGuild ?? null;
    $serverName = $activeGuild['name'] ?? 'Belum pilih server';
    $guildId = $activeGuild['id'] ?? 'Guild belum dipilih';
    $trackedGamesCount = is_numeric($stats[0]['value'] ?? null) ? (int) $stats[0]['value'] : 0;
    $webhookCount = is_numeric($stats[1]['value'] ?? null) ? (int) $stats[1]['value'] : 0;
    $alertCount = is_numeric($stats[2]['value'] ?? null) ? (int) $stats[2]['value'] : 0;
    $reportCount = is_numeric($stats[3]['value'] ?? null) ? (int) $stats[3]['value'] : 0;
    $raceCount = is_numeric($stats[4]['value'] ?? null) ? (int) $stats[4]['value'] : 0;
    $wallet = $walletSummary ?? [];
    $formatIdr = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $grossSalesAmount = $formatIdr($wallet['grossSalesTotal'] ?? 0);
    $adminFeeAmount = $formatIdr($wallet['adminFeeTotal'] ?? 0);
    $netSalesAmount = $formatIdr($wallet['netSalesTotal'] ?? 0);
    $frozenBalanceAmount = $formatIdr($wallet['frozenBalance'] ?? 0);
    $availableBalanceAmount = $formatIdr($wallet['availableBalance'] ?? 0);
    $minimumWithdrawalAmount = max(1, (int) (($wallet['withdrawalFee'] ?? 2500) + 1));
    $maximumWithdrawalAmount = max($minimumWithdrawalAmount, (int) ($wallet['availableBalance'] ?? 0));
    $healthScore = max(18, min(98, 92 - ($alertCount * 6) - ($reportCount * 3) + ($webhookCount * 2)));
    $cards = [
        ['title' => 'Roblox Discord Ops', 'subtitle' => 'Control room utama untuk server aktif.', 'value' => $healthScore.'%', 'progress' => $healthScore, 'meta' => $serverName, 'footer' => 'Guild '.$guildId],
        ['title' => 'Tracked experiences', 'subtitle' => 'Universe dan place yang terhubung.', 'value' => (string) $trackedGamesCount, 'progress' => min(100, 18 + ($trackedGamesCount * 12)), 'meta' => 'Roblox', 'footer' => $stats[0]['hint'] ?? 'Tracked games'],
        ['title' => 'Active webhooks', 'subtitle' => 'Webhook Discord yang siap kirim event.', 'value' => (string) $webhookCount, 'progress' => min(100, 18 + ($webhookCount * 16)), 'meta' => 'Discord', 'footer' => $stats[1]['hint'] ?? 'Active webhooks'],
        ['title' => 'VIP Title Wallet', 'subtitle' => 'Gross '.$grossSalesAmount.' | siap tarik '.$availableBalanceAmount.'.', 'value' => $availableBalanceAmount, 'progress' => min(100, max(10, (int) round((($wallet['availableBalance'] ?? 0) / max(1, ($wallet['netSalesTotal'] ?? 1))) * 100))), 'meta' => 'Wallet', 'footer' => 'VIP Title Wallet'],
        ['title' => 'Alert pressure', 'subtitle' => 'Insiden operasional yang masih terbuka.', 'value' => (string) $alertCount, 'progress' => min(100, max(10, $alertCount * 20)), 'meta' => 'Ops', 'footer' => $stats[2]['hint'] ?? 'Open alerts'],
        ['title' => 'Player and bug reports', 'subtitle' => 'Queue laporan user dan bug terbaru.', 'value' => (string) $reportCount, 'progress' => min(100, max(10, $reportCount * 18)), 'meta' => 'Support', 'footer' => $stats[3]['hint'] ?? 'Pending reports'],
        ['title' => 'Community race desk', 'subtitle' => 'Event balap yang sedang buka registrasi.', 'value' => (string) $raceCount, 'progress' => min(100, max(10, $raceCount * 22)), 'meta' => 'Community', 'footer' => $stats[4]['hint'] ?? 'Race events'],
    ];
    $activityItems = collect()
        ->merge(collect($alerts)->map(fn ($alert) => ['initials' => 'AL', 'name' => 'Alert: '.$alert->title, 'line' => Str::limit((string) $alert->message, 110), 'time' => optional($alert->occurred_at)->diffForHumans() ?? 'baru saja']))
        ->merge(collect($reports)->map(fn ($report) => ['initials' => 'RP', 'name' => 'Report: '.$report->reported_player_name, 'line' => Str::limit((string) $report->summary, 110), 'time' => optional($report->created_at)->diffForHumans() ?? 'baru saja']))
        ->merge(collect($webhooks)->map(fn ($webhook) => ['initials' => 'WH', 'name' => 'Webhook: '.$webhook->name, 'line' => 'Channel '.$webhook->channel_name.' | '.($webhook->is_active ? 'active' : 'paused'), 'time' => optional($webhook->updated_at)->diffForHumans() ?? 'baru saja']))
        ->merge(collect($wallet['recentWithdrawals'] ?? [])->map(fn ($withdrawal) => ['initials' => 'WD', 'name' => 'Withdrawal: '.$formatIdr($withdrawal['grossAmount'] ?? 0), 'line' => 'Status '.($withdrawal['status'] ?? 'unknown').' | net '.$formatIdr($withdrawal['netAmount'] ?? 0), 'time' => optional($withdrawal['requestedAt'] ?? null)->diffForHumans() ?? 'baru saja']))
        ->take(7)
        ->values();
    $quickLinks = [
        ['label' => 'Pilih Server', 'href' => route('guilds.select')],
        ['label' => 'Discord Setup', 'href' => route('discord.setup')],
        ['label' => 'VIP Title Setup', 'href' => route('vip-title.setup')],
        ['label' => 'Roblox Scripts', 'href' => route('roblox.scripts.index')],
    ];
@endphp

<x-portfolio.shell :title="$title" active-key="dashboard" search-placeholder="Cari workspace, wallet, report, setup">
    <x-slot:head>
        <style>
            :root {
                --studio-accent: #79e7ff;
                --studio-accent-2: #82ffbf;
                --studio-accent-3: #ffc77b;
                --studio-danger: #ff8c9d;
            }

            .dashboard-card-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .dashboard-ops-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: 1.45fr .8fr;
            }

            .dashboard-metric {
                display: flex;
                flex-wrap: wrap;
                gap: 0.8rem;
            }

            .dashboard-metric .studio-stat {
                flex: 1 1 180px;
            }

            .dashboard-card {
                position: relative;
                overflow: hidden;
            }

            .dashboard-card-value {
                display: block;
                margin-top: 1rem;
                font-family: var(--studio-display);
                font-size: clamp(1.55rem, 3vw, 2.15rem);
                line-height: 1;
            }

            .dashboard-progress {
                width: 100%;
                height: 8px;
                margin-top: 1rem;
                border-radius: 999px;
                overflow: hidden;
                background: rgba(255,255,255,.08);
            }

            .dashboard-progress span {
                display: block;
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--studio-accent), var(--studio-accent-2));
            }

            .dashboard-card-footer {
                display: flex;
                justify-content: space-between;
                gap: 0.8rem;
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid rgba(255,255,255,.06);
                color: var(--studio-muted);
                font-size: 0.84rem;
            }

            .dashboard-mini-grid {
                display: grid;
                gap: 0.85rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-activity-list {
                display: grid;
                gap: 0.8rem;
            }

            .dashboard-activity-item {
                display: flex;
                gap: 0.9rem;
                align-items: flex-start;
                padding: 0.95rem 1rem;
                border-radius: 1.2rem;
                border: 1px solid rgba(255,255,255,.06);
                background: rgba(255,255,255,.03);
            }

            .dashboard-activity-avatar {
                width: 2.4rem;
                height: 2.4rem;
                border-radius: 999px;
                display: grid;
                place-items: center;
                flex-shrink: 0;
                background: linear-gradient(135deg, var(--studio-accent), var(--studio-accent-2));
                color: #04111e;
                font: 800 0.8rem/1 var(--studio-display);
            }

            .dashboard-inline-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.7rem;
            }

            @media (max-width: 1180px) {
                .dashboard-ops-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 720px) {
                .dashboard-mini-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        @include('partials.studio-workspace-style')
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('vip-title.setup') }}" wire:navigate class="portfolio-shell-action">VIP Setup</a>
    </x-slot:headerActions>

    <section class="studio-hero" data-studio-hover>
        <div class="studio-hero-grid">
            <div>
                <span class="studio-kicker">Workspace Overview</span>
                <h2>Roblox Discord Ops <span>tetap dalam satu shell</span></h2>
                <p>Semua panel utama, setup Discord, VIP Title, script Roblox, dan wallet sekarang memakai visual workspace yang sama. Jadi saat pindah halaman, kesannya tetap satu dashboard yang utuh.</p>

                <div class="dashboard-metric">
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Server</span>
                        <strong>{{ $serverName }}</strong>
                        <p class="studio-copy">Guild aktif {{ $guildId }}</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Health</span>
                        <strong>{{ $healthScore }}%</strong>
                        <p class="studio-copy">Skor operasional dari webhook, alert, dan report.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">VIP Title Wallet</span>
                        <strong>{{ $availableBalanceAmount }}</strong>
                        <p class="studio-copy">Saldo siap tarik yang bisa diajukan dari dashboard.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Player and bug reports</span>
                        <strong>{{ $reportCount }}</strong>
                        <p class="studio-copy">Queue laporan terbaru yang masih perlu dilihat tim.</p>
                    </article>
                </div>
            </div>

            <aside class="studio-stack">
                <article class="studio-card" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Quick Access</span>
                            <h3 style="margin-top:.75rem;">Pindah page tetap satu tema</h3>
                        </div>
                        <span class="studio-pill">Workspace</span>
                    </div>
                    <div class="dashboard-inline-actions">
                        @foreach ($quickLinks as $quickLink)
                            <a href="{{ $quickLink['href'] }}" wire:navigate class="studio-button-ghost">{{ $quickLink['label'] }}</a>
                        @endforeach
                    </div>
                    <p class="studio-copy" style="margin-top:1rem;">Link internal di workspace ini sekarang memakai navigasi app-like, jadi perpindahan page tidak terasa reload penuh browser.</p>
                </article>
            </aside>
        </div>
    </section>

    <section class="dashboard-ops-grid">
        <section class="studio-panel" data-studio-hover>
            <div class="studio-panel-header">
                <div>
                    <span class="studio-label">Overview Cards</span>
                    <h3 style="margin-top:.75rem;">Roblox Discord Ops</h3>
                    <p class="studio-copy" style="margin-top:.45rem;">Ringkasan utama sekarang dibawa ke shell yang sama dengan semua halaman setup.</p>
                </div>
                <span class="studio-pill">{{ now()->translatedFormat('F d') }}</span>
            </div>

            <div class="dashboard-card-grid">
                @foreach ($cards as $card)
                    <article class="studio-card dashboard-card" data-studio-hover>
                        <span class="studio-label">{{ $card['meta'] }}</span>
                        <h3 style="margin-top:.9rem;">{{ $card['title'] }}</h3>
                        <p class="studio-copy" style="margin-top:.55rem;">{{ $card['subtitle'] }}</p>
                        <span class="dashboard-card-value">{{ $card['value'] }}</span>
                        <div class="dashboard-progress"><span style="width: {{ max(8, min(100, (int) $card['progress'])) }}%"></span></div>
                        <div class="dashboard-card-footer">
                            <span>{{ $card['footer'] }}</span>
                            @if ($card['title'] === 'VIP Title Wallet')
                                <a href="#wallet-card" class="studio-button-ghost">Request Penarikan</a>
                            @endif
                        </div>
                    </article>
                @endforeach

                <article class="studio-card dashboard-card" data-studio-hover id="wallet-card">
                    <span class="studio-label">Wallet Action</span>
                    <h3 style="margin-top:.9rem;">Request Penarikan</h3>
                    <p class="studio-copy" style="margin-top:.55rem;">Total jual {{ $grossSalesAmount }}, saldo siap tarik {{ $availableBalanceAmount }}, biaya tarik {{ $formatIdr($wallet['withdrawalFee'] ?? 0) }}.</p>
                    <span class="dashboard-card-value">{{ $availableBalanceAmount }}</span>
                    <div class="dashboard-progress"><span style="width: {{ min(100, max(8, (int) round((($wallet['availableBalance'] ?? 0) / max(1, ($wallet['maturedBalance'] ?? 1))) * 100))) }}%"></span></div>
                    @if (session('wallet_status'))
                        <div class="studio-notice">{{ session('wallet_status') }}</div>
                    @endif
                    @if ($errors->has('amount'))
                        <div class="studio-notice" style="background: color-mix(in srgb, var(--studio-danger) 14%, transparent);">{{ $errors->first('amount') }}</div>
                    @endif
                    <form method="POST" action="{{ route('dashboard.wallet.withdrawals.store') }}" class="studio-stack">
                        @csrf
                        <div class="studio-field">
                            <label for="amount">Nominal penarikan</label>
                            <input id="amount" class="studio-input" type="number" name="amount" min="{{ $minimumWithdrawalAmount }}" max="{{ $maximumWithdrawalAmount }}" step="1" value="{{ old('amount', max(0, (int) ($wallet['availableBalance'] ?? 0))) }}" placeholder="Contoh 50000">
                        </div>
                        <div class="studio-actions">
                            <button type="submit" class="studio-button" @disabled(($wallet['availableBalance'] ?? 0) <= ($wallet['withdrawalFee'] ?? 0))>Ajukan penarikan</button>
                        </div>
                        <p class="studio-help">VIP Title Wallet akan diproses 1 hari, lalu statusnya masuk siap ditarik manual.</p>
                    </form>
                </article>
            </div>
        </section>

        <aside class="studio-panel" data-studio-hover>
            <div class="studio-panel-header">
                <div>
                    <span class="studio-label">Live Panel</span>
                    <h3 style="margin-top:.75rem;">Player and bug reports</h3>
                </div>
                <span class="studio-pill">Activity</span>
            </div>

            <div class="dashboard-mini-grid">
                <article class="studio-card" data-studio-hover>
                    <strong>VIP Title Wallet</strong>
                    <p class="studio-copy" style="margin-top:.55rem;">Total penjualan {{ $grossSalesAmount }}, fee admin {{ $adminFeeAmount }}, net {{ $netSalesAmount }}</p>
                </article>
                <article class="studio-card" data-studio-hover>
                    <strong>Request Penarikan</strong>
                    <p class="studio-copy" style="margin-top:.55rem;">{{ count($wallet['recentWithdrawals'] ?? []) }} request terakhir | siap {{ collect($wallet['recentWithdrawals'] ?? [])->where('status', 'ready')->count() }} | beku {{ $frozenBalanceAmount }}</p>
                </article>
            </div>

            <div class="dashboard-activity-list">
                @forelse ($activityItems as $item)
                    <article class="dashboard-activity-item">
                        <span class="dashboard-activity-avatar">{{ $item['initials'] }}</span>
                        <div>
                            <strong>{{ $item['name'] }}</strong>
                            <p class="studio-copy" style="margin:.35rem 0 0;">{{ $item['line'] }}</p>
                            <span class="studio-note">{{ $item['time'] }}</span>
                        </div>
                    </article>
                @empty
                    <article class="dashboard-activity-item">
                        <span class="dashboard-activity-avatar">LY</span>
                        <div>
                            <strong>Belum ada activity</strong>
                            <p class="studio-copy" style="margin:.35rem 0 0;">Saat alert, report, webhook, race, atau withdrawal mulai bergerak, panel ini akan otomatis terisi.</p>
                        </div>
                    </article>
                @endforelse
            </div>
        </aside>
    </section>
</x-portfolio.shell>
