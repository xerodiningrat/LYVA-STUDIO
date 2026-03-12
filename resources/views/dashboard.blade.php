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
    $grossSalesValue = (int) ($wallet['grossSalesTotal'] ?? 0);
    $adminFeeValue = (int) ($wallet['adminFeeTotal'] ?? 0);
    $netSalesValue = (int) ($wallet['netSalesTotal'] ?? 0);
    $frozenBalanceValue = (int) ($wallet['frozenBalance'] ?? 0);
    $availableBalanceValue = (int) ($wallet['availableBalance'] ?? 0);
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
        ['label' => 'Penghasilan', 'href' => route('dashboard.wallet.earnings')],
        ['label' => 'Penarikan', 'href' => route('dashboard.wallet.withdrawals.index')],
        ['label' => 'Roblox Scripts', 'href' => route('roblox.scripts.index')],
    ];
    $signalSeries = [
        ['label' => 'Ops', 'value' => $healthScore, 'tone' => 'var(--studio-accent)'],
        ['label' => 'Roblox', 'value' => min(100, 20 + ($trackedGamesCount * 12)), 'tone' => '#8bd7ff'],
        ['label' => 'Discord', 'value' => min(100, 20 + ($webhookCount * 16)), 'tone' => 'var(--studio-accent-2)'],
        ['label' => 'Wallet', 'value' => min(100, max(12, (int) round(($availableBalanceValue / max(1, $netSalesValue ?: 1)) * 100))), 'tone' => '#ffd27e'],
        ['label' => 'Support', 'value' => max(12, min(100, 100 - (($alertCount * 10) + ($reportCount * 9)))), 'tone' => '#ff9bba'],
    ];
    $chartWidth = 420;
    $chartHeight = 194;
    $chartPaddingX = 28;
    $chartPaddingY = 22;
    $chartCount = max(1, count($signalSeries));
    $chartStepX = $chartCount > 1 ? (($chartWidth - ($chartPaddingX * 2)) / ($chartCount - 1)) : 0;
    $signalPoints = collect($signalSeries)->values()->map(function (array $point, int $index) use ($chartHeight, $chartPaddingY, $chartPaddingX, $chartStepX) {
        $x = $chartPaddingX + ($chartStepX * $index);
        $y = ($chartHeight - $chartPaddingY) - (($chartHeight - ($chartPaddingY * 2)) * ($point['value'] / 100));

        return ['x' => round($x, 2), 'y' => round($y, 2)] + $point;
    });
    $signalPolyline = $signalPoints->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');
    $signalArea = $signalPolyline.' '.($chartWidth - $chartPaddingX).','.($chartHeight - $chartPaddingY).' '.$chartPaddingX.','.($chartHeight - $chartPaddingY);
    $walletCircleRadius = 46;
    $walletCircleCircumference = 2 * pi() * $walletCircleRadius;
    $walletReadyRatio = min(100, max(0, (int) round(($availableBalanceValue / max(1, $grossSalesValue ?: 1)) * 100)));
    $walletFrozenRatio = min(100, max(0, (int) round(($frozenBalanceValue / max(1, $grossSalesValue ?: 1)) * 100)));
    $walletReadyOffset = $walletCircleCircumference - (($walletReadyRatio / 100) * $walletCircleCircumference);
    $walletFrozenOffset = $walletCircleCircumference - (($walletFrozenRatio / 100) * $walletCircleCircumference);
    $particles = range(1, 16);
    $focusItems = [
        ['label' => 'Webhook health', 'value' => $webhookCount, 'hint' => $webhookCount > 0 ? 'Webhook aktif siap kirim alert dan deploy log.' : 'Belum ada webhook aktif di workspace ini.'],
        ['label' => 'Alert backlog', 'value' => $alertCount, 'hint' => $alertCount > 0 ? 'Masih ada alert operasional yang perlu ditutup.' : 'Tidak ada alert terbuka, kondisi cukup aman.'],
        ['label' => 'Bug queue', 'value' => $reportCount, 'hint' => $reportCount > 0 ? 'Report player terbaru menunggu ditindak.' : 'Belum ada report baru dari player.'],
        ['label' => 'Race desk', 'value' => $raceCount, 'hint' => $raceCount > 0 ? 'Event komunitas aktif masih berjalan.' : 'Belum ada event race aktif saat ini.'],
    ];
    $actionBoard = [
        ['title' => 'Server aktif', 'copy' => 'Pastikan workspace yang aktif memang guild yang sedang kamu kelola sebelum edit setup apa pun.'],
        ['title' => 'VIP wallet', 'copy' => 'Cek penghasilan dan request penarikan dari halaman wallet kalau ada transaksi baru yang masuk.'],
        ['title' => 'Support flow', 'copy' => 'Kalau report mulai naik, buka panel support lebih dulu supaya queue tidak menumpuk.'],
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
                gap: 1.2rem;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .dashboard-ops-grid {
                display: grid;
                gap: 1.25rem;
                grid-template-columns: 1.45fr .8fr;
            }

            .dashboard-metric {
                display: flex;
                flex-wrap: wrap;
                gap: 0.95rem;
            }

            .dashboard-metric .studio-stat {
                flex: 1 1 180px;
            }

            .dashboard-hero {
                position: relative;
                isolation: isolate;
            }

            .dashboard-hero-grid {
                gap: 1.4rem;
            }

            .dashboard-particles {
                position: absolute;
                inset: 0;
                pointer-events: none;
                overflow: hidden;
                z-index: 0;
            }

            .dashboard-particle {
                position: absolute;
                width: var(--size);
                height: var(--size);
                top: var(--top);
                left: var(--left);
                border-radius: 999px;
                border: 1px solid rgba(123, 223, 255, .22);
                background: radial-gradient(circle, rgba(123, 223, 255, .22), rgba(123, 223, 255, 0));
                box-shadow: 0 0 18px rgba(123, 223, 255, .12);
                animation: dashboardFloat var(--duration) ease-in-out infinite;
                animation-delay: var(--delay);
                opacity: .72;
            }

            .dashboard-orb {
                position: absolute;
                inset: auto auto -4rem -3rem;
                width: 17rem;
                height: 17rem;
                border-radius: 999px;
                background: radial-gradient(circle, rgba(121, 231, 255, .18), rgba(121, 231, 255, 0) 70%);
                filter: blur(6px);
                z-index: 0;
                animation: dashboardPulse 9s ease-in-out infinite;
            }

            .dashboard-orb::after {
                content: "";
                position: absolute;
                right: -6rem;
                top: -4rem;
                width: 12rem;
                height: 12rem;
                border-radius: inherit;
                background: radial-gradient(circle, rgba(130, 255, 191, .16), rgba(130, 255, 191, 0) 72%);
            }

            .dashboard-card {
                position: relative;
                overflow: hidden;
                min-height: 100%;
                transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease;
                background:
                    radial-gradient(circle at top right, rgba(123, 223, 255, .11), transparent 34%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.015));
            }

            .dashboard-card::before,
            .dashboard-chart-card::before,
            .dashboard-wallet-visual::before {
                content: "";
                position: absolute;
                inset: 0 auto auto 0;
                width: 100%;
                height: 1px;
                background: linear-gradient(90deg, rgba(121, 231, 255, .55), rgba(130, 255, 191, 0));
                opacity: .8;
            }

            .dashboard-card:hover,
            .dashboard-chart-card:hover,
            .dashboard-wallet-visual:hover {
                box-shadow: 0 24px 48px rgba(2, 10, 20, .34);
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
                width: var(--value);
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--studio-accent), var(--studio-accent-2));
                box-shadow: 0 0 18px rgba(123, 223, 255, .26);
                transform-origin: left center;
                transform: scaleX(0);
                animation: dashboardGrow 1.25s cubic-bezier(.22, 1, .36, 1) forwards;
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
                gap: 1rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-data-grid {
                display: grid;
                gap: 1.2rem;
                grid-template-columns: minmax(0, 1.1fr) minmax(320px, .82fr);
            }

            .dashboard-chart-card {
                padding: 1.2rem;
                background:
                    radial-gradient(circle at top right, rgba(255, 199, 123, .11), transparent 34%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.015));
            }

            .dashboard-chart-stage {
                position: relative;
                margin-top: 1rem;
                padding: 1rem;
                border-radius: 1.35rem;
                border: 1px solid rgba(255,255,255,.07);
                background: linear-gradient(180deg, rgba(255,255,255,.045), rgba(255,255,255,.02));
                overflow: hidden;
            }

            .dashboard-chart-stage::before {
                content: "";
                position: absolute;
                inset: 0;
                background-image: linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
                background-size: 100% 25%, 14% 100%;
                opacity: .32;
                pointer-events: none;
            }

            .dashboard-chart-svg {
                position: relative;
                z-index: 1;
                width: 100%;
                height: auto;
                overflow: visible;
            }

            .dashboard-chart-area {
                fill: url(#dashboardSignalFill);
                opacity: .55;
            }

            .dashboard-chart-line {
                fill: none;
                stroke: url(#dashboardSignalStroke);
                stroke-width: 4;
                stroke-linecap: round;
                stroke-linejoin: round;
                stroke-dasharray: 720;
                stroke-dashoffset: 720;
                animation: dashboardTrace 1.8s ease forwards;
            }

            .dashboard-chart-node {
                fill: #03111f;
                stroke: rgba(123, 223, 255, .88);
                stroke-width: 3;
                transform-origin: center;
                animation: dashboardNodePulse 2.4s ease-in-out infinite;
            }

            .dashboard-chart-node:nth-of-type(2n) {
                animation-delay: .35s;
            }

            .dashboard-axis-labels,
            .dashboard-chart-legend {
                display: grid;
                gap: 0.75rem;
            }

            .dashboard-axis-labels {
                margin-top: .9rem;
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            .dashboard-axis-label {
                display: grid;
                gap: .28rem;
            }

            .dashboard-axis-label strong {
                font: 700 .8rem/1 var(--studio-display);
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .dashboard-chart-legend {
                margin-top: 1rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-chart-legend-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .8rem;
                padding: .8rem .9rem;
                border-radius: 1rem;
                border: 1px solid rgba(255,255,255,.06);
                background: rgba(255,255,255,.03);
            }

            .dashboard-chart-legend-item span {
                display: inline-flex;
                align-items: center;
                gap: .55rem;
                color: var(--studio-muted);
                font-size: .9rem;
            }

            .dashboard-chart-legend-item i {
                width: .72rem;
                height: .72rem;
                border-radius: 999px;
                display: inline-block;
                background: var(--tone);
                box-shadow: 0 0 18px color-mix(in srgb, var(--tone) 45%, transparent);
            }

            .dashboard-activity-list {
                display: grid;
                gap: 0.95rem;
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
                gap: 0.75rem;
            }

            .dashboard-wallet-visual {
                padding: 1.2rem;
                background:
                    radial-gradient(circle at top left, rgba(130, 255, 191, .12), transparent 32%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.015));
            }

            .dashboard-ring-layout {
                display: grid;
                gap: 1rem;
                grid-template-columns: 168px minmax(0, 1fr);
                align-items: center;
            }

            .dashboard-ring {
                position: relative;
                width: 168px;
                aspect-ratio: 1;
                display: grid;
                place-items: center;
                margin-inline: auto;
            }

            .dashboard-ring svg {
                width: 100%;
                height: 100%;
                transform: rotate(-90deg);
                overflow: visible;
            }

            .dashboard-ring-track {
                fill: none;
                stroke: rgba(255,255,255,.08);
                stroke-width: 12;
            }

            .dashboard-ring-progress,
            .dashboard-ring-progress-secondary {
                fill: none;
                stroke-width: 12;
                stroke-linecap: round;
                stroke-dasharray: {{ $walletCircleCircumference }};
                transform-origin: center;
            }

            .dashboard-ring-progress {
                stroke: url(#dashboardRingPrimary);
                stroke-dashoffset: {{ $walletCircleCircumference }};
                animation: dashboardRingPrimary 1.7s cubic-bezier(.22, 1, .36, 1) forwards;
            }

            .dashboard-ring-progress-secondary {
                stroke: rgba(255, 199, 123, .88);
                stroke-dashoffset: {{ $walletCircleCircumference }};
                animation: dashboardRingSecondary 1.7s cubic-bezier(.22, 1, .36, 1) forwards;
            }

            .dashboard-ring-center {
                position: absolute;
                inset: 0;
                display: grid;
                place-items: center;
                text-align: center;
                padding: 2rem;
            }

            .dashboard-ring-center strong {
                display: block;
                font: 700 1.5rem/1 var(--studio-display);
            }

            .dashboard-ring-center span {
                color: var(--studio-muted);
                font-size: .82rem;
            }

            .dashboard-distribution {
                display: grid;
                gap: .8rem;
            }

            .dashboard-distribution-item {
                display: flex;
                justify-content: space-between;
                gap: .9rem;
                padding: .85rem .95rem;
                border-radius: 1rem;
                background: rgba(255,255,255,.03);
                border: 1px solid rgba(255,255,255,.06);
            }

            .dashboard-distribution-item div {
                display: grid;
                gap: .28rem;
            }

            .dashboard-distribution-item strong {
                font: 700 .98rem/1.1 var(--studio-display);
            }

            .dashboard-distribution-item em {
                align-self: center;
                font-style: normal;
                color: var(--studio-muted);
                font-size: .84rem;
            }

            .dashboard-section-gap {
                display: grid;
                gap: 1.25rem;
            }

            .dashboard-bottom-grid {
                display: grid;
                gap: 1.25rem;
                grid-template-columns: 1.05fr .95fr;
            }

            .dashboard-focus-grid,
            .dashboard-action-grid {
                display: grid;
                gap: 1rem;
            }

            .dashboard-focus-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-focus-item,
            .dashboard-action-item {
                position: relative;
                overflow: hidden;
                padding: 1rem 1.05rem;
                border-radius: 1.25rem;
                border: 1px solid rgba(255,255,255,.06);
                background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
            }

            .dashboard-focus-item strong {
                display: block;
                margin-top: .75rem;
                font: 700 1.7rem/1 var(--studio-display);
            }

            .dashboard-action-item {
                display: grid;
                gap: .45rem;
            }

            @keyframes dashboardFloat {
                0%, 100% {
                    transform: translate3d(0, 0, 0) scale(1);
                    opacity: .18;
                }

                50% {
                    transform: translate3d(0, -18px, 0) scale(1.08);
                    opacity: .82;
                }
            }

            @keyframes dashboardPulse {
                0%, 100% {
                    transform: scale(1);
                    opacity: .85;
                }

                50% {
                    transform: scale(1.12);
                    opacity: 1;
                }
            }

            @keyframes dashboardGrow {
                to {
                    transform: scaleX(1);
                }
            }

            @keyframes dashboardTrace {
                to {
                    stroke-dashoffset: 0;
                }
            }

            @keyframes dashboardNodePulse {
                0%, 100% {
                    transform: scale(1);
                    filter: drop-shadow(0 0 0 rgba(123, 223, 255, 0));
                }

                50% {
                    transform: scale(1.16);
                    filter: drop-shadow(0 0 10px rgba(123, 223, 255, .45));
                }
            }

            @keyframes dashboardRingPrimary {
                to {
                    stroke-dashoffset: {{ round($walletReadyOffset, 2) }};
                }
            }

            @keyframes dashboardRingSecondary {
                to {
                    stroke-dashoffset: {{ round($walletFrozenOffset, 2) }};
                }
            }

            @media (max-width: 1180px) {
                .dashboard-bottom-grid,
                .dashboard-data-grid,
                .dashboard-ops-grid {
                    grid-template-columns: 1fr;
                }

                .dashboard-ring-layout {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 720px) {
                .dashboard-chart-legend,
                .dashboard-focus-grid,
                .dashboard-mini-grid {
                    grid-template-columns: 1fr;
                }

                .dashboard-axis-labels {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (max-width: 560px) {
                .dashboard-card-grid {
                    grid-template-columns: 1fr;
                }

                .dashboard-particles {
                    opacity: .5;
                }
            }
        </style>
        @include('partials.studio-workspace-style')
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('vip-title.setup') }}" wire:navigate class="portfolio-shell-action">VIP Setup</a>
    </x-slot:headerActions>

    <section class="studio-hero dashboard-hero" data-studio-hover>
        <div class="dashboard-particles" aria-hidden="true">
            <div class="dashboard-orb"></div>
            @foreach ($particles as $particle)
                <span
                    class="dashboard-particle"
                    style="--size: {{ 0.4 + (($particle % 4) * 0.22) }}rem; --top: {{ 6 + (($particle * 7) % 78) }}%; --left: {{ 4 + (($particle * 11) % 90) }}%; --delay: -{{ $particle * 0.35 }}s; --duration: {{ 8 + ($particle % 5) }}s;"
                ></span>
            @endforeach
        </div>

        <div class="studio-hero-grid dashboard-hero-grid">
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

                <article class="dashboard-chart-card" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Ops Signal</span>
                            <h3 style="margin-top:.75rem;">Grafik operasional live workspace</h3>
                        </div>
                        <span class="studio-pill">Animated</span>
                    </div>

                    <div class="dashboard-chart-stage">
                        <svg class="dashboard-chart-svg" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Grafik operasional workspace">
                            <defs>
                                <linearGradient id="dashboardSignalFill" x1="0%" x2="0%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="rgba(123, 223, 255, 0.45)" />
                                    <stop offset="100%" stop-color="rgba(123, 223, 255, 0)" />
                                </linearGradient>
                                <linearGradient id="dashboardSignalStroke" x1="0%" x2="100%" y1="0%" y2="0%">
                                    <stop offset="0%" stop-color="#79e7ff" />
                                    <stop offset="50%" stop-color="#82ffbf" />
                                    <stop offset="100%" stop-color="#ffc77b" />
                                </linearGradient>
                            </defs>
                            <polygon class="dashboard-chart-area" points="{{ $signalArea }}" />
                            <polyline class="dashboard-chart-line" points="{{ $signalPolyline }}" />
                            @foreach ($signalPoints as $point)
                                <circle class="dashboard-chart-node" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5.5" />
                            @endforeach
                        </svg>
                    </div>

                    <div class="dashboard-axis-labels">
                        @foreach ($signalSeries as $signal)
                            <div class="dashboard-axis-label">
                                <strong>{{ $signal['label'] }}</strong>
                                <span class="studio-note" style="margin-top:0;">{{ $signal['value'] }}%</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="dashboard-chart-legend">
                        @foreach ($signalSeries as $signal)
                            <article class="dashboard-chart-legend-item">
                                <span><i style="--tone: {{ $signal['tone'] }}"></i>{{ $signal['label'] }}</span>
                                <strong>{{ $signal['value'] }}%</strong>
                            </article>
                        @endforeach
                    </div>
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
                        <div class="dashboard-progress"><span style="--value: {{ max(8, min(100, (int) $card['progress'])) }}%"></span></div>
                        <div class="dashboard-card-footer">
                            <span>{{ $card['footer'] }}</span>
                            @if ($card['title'] === 'VIP Title Wallet')
                                <a href="{{ route('dashboard.wallet.earnings') }}" wire:navigate class="studio-button-ghost">Lihat Penghasilan</a>
                            @endif
                        </div>
                    </article>
                @endforeach

                <article class="studio-card dashboard-card" data-studio-hover id="wallet-card">
                    <span class="studio-label">Wallet Action</span>
                    <h3 style="margin-top:.9rem;">Request Penarikan</h3>
                    <p class="studio-copy" style="margin-top:.55rem;">Total jual {{ $grossSalesAmount }}, saldo siap tarik {{ $availableBalanceAmount }}, biaya tarik {{ $formatIdr($wallet['withdrawalFee'] ?? 0) }}.</p>
                    <span class="dashboard-card-value">{{ $availableBalanceAmount }}</span>
                    <div class="dashboard-progress"><span style="--value: {{ min(100, max(8, (int) round((($wallet['availableBalance'] ?? 0) / max(1, ($wallet['maturedBalance'] ?? 1))) * 100))) }}%"></span></div>
                    <div class="dashboard-inline-actions" style="margin-top:1rem;">
                        <a href="{{ route('dashboard.wallet.earnings') }}" wire:navigate class="studio-button-ghost">Halaman Penghasilan</a>
                        <a href="{{ route('dashboard.wallet.withdrawals.index') }}" wire:navigate class="studio-button">Halaman Penarikan</a>
                    </div>
                    <p class="studio-help" style="margin-top:1rem;">Pengajuan penarikan sekarang punya halaman sendiri dan wajib isi nama bank, nomor rekening, dan atas nama.</p>
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

            <div class="dashboard-section-gap">
                <article class="dashboard-wallet-visual" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Revenue Pulse</span>
                            <h3 style="margin-top:.75rem;">VIP Title Wallet</h3>
                        </div>
                        <span class="studio-pill">Graph</span>
                    </div>

                    <div class="dashboard-ring-layout">
                        <div class="dashboard-ring">
                            <svg viewBox="0 0 140 140" role="img" aria-label="Distribusi wallet">
                                <defs>
                                    <linearGradient id="dashboardRingPrimary" x1="0%" x2="100%" y1="0%" y2="0%">
                                        <stop offset="0%" stop-color="#79e7ff" />
                                        <stop offset="100%" stop-color="#82ffbf" />
                                    </linearGradient>
                                </defs>
                                <circle class="dashboard-ring-track" cx="70" cy="70" r="{{ $walletCircleRadius }}" />
                                <circle class="dashboard-ring-progress-secondary" cx="70" cy="70" r="{{ $walletCircleRadius }}" />
                                <circle class="dashboard-ring-progress" cx="70" cy="70" r="{{ $walletCircleRadius }}" />
                            </svg>
                            <div class="dashboard-ring-center">
                                <div>
                                    <strong>{{ $availableBalanceAmount }}</strong>
                                    <span>saldo siap tarik</span>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-distribution">
                            <article class="dashboard-distribution-item">
                                <div>
                                    <span class="studio-label">Net Sales</span>
                                    <strong>{{ $netSalesAmount }}</strong>
                                </div>
                                <em>setelah fee admin</em>
                            </article>
                            <article class="dashboard-distribution-item">
                                <div>
                                    <span class="studio-label">Frozen 2 Hari</span>
                                    <strong>{{ $frozenBalanceAmount }}</strong>
                                </div>
                                <em>{{ $walletFrozenRatio }}% of gross</em>
                            </article>
                            <article class="dashboard-distribution-item">
                                <div>
                                    <span class="studio-label">Ready to Withdraw</span>
                                    <strong>{{ $availableBalanceAmount }}</strong>
                                </div>
                                <em>{{ $walletReadyRatio }}% of gross</em>
                            </article>
                        </div>
                    </div>
                </article>

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
            </div>
        </aside>
    </section>

    <section class="dashboard-bottom-grid">
        <section class="studio-panel" data-studio-hover>
            <div class="studio-panel-header">
                <div>
                    <span class="studio-label">Focus Queue</span>
                    <h3 style="margin-top:.75rem;">Area yang paling perlu dilihat sekarang</h3>
                    <p class="studio-copy" style="margin-top:.45rem;">Bagian ini saya isi biar dashboard tidak terasa kosong dan admin langsung tahu titik perhatian utamanya.</p>
                </div>
                <span class="studio-pill">Ops</span>
            </div>

            <div class="dashboard-focus-grid">
                @foreach ($focusItems as $item)
                    <article class="dashboard-focus-item" data-studio-hover>
                        <span class="studio-label">{{ $item['label'] }}</span>
                        <strong>{{ $item['value'] }}</strong>
                        <p class="studio-copy" style="margin:.55rem 0 0;">{{ $item['hint'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="studio-panel" data-studio-hover>
            <div class="studio-panel-header">
                <div>
                    <span class="studio-label">Action Board</span>
                    <h3 style="margin-top:.75rem;">Checklist cepat untuk admin</h3>
                    <p class="studio-copy" style="margin-top:.45rem;">Supaya sisi kanan bawah tidak kosong, saya ubah jadi panel arahan cepat yang tetap relevan dengan workspace ini.</p>
                </div>
                <span class="studio-pill">Guide</span>
            </div>

            <div class="dashboard-action-grid">
                @foreach ($actionBoard as $index => $item)
                    <article class="dashboard-action-item" data-studio-hover>
                        <span class="studio-note" style="margin-top:0;">Step {{ $index + 1 }}</span>
                        <strong>{{ $item['title'] }}</strong>
                        <p class="studio-copy" style="margin:0;">{{ $item['copy'] }}</p>
                    </article>
                @endforeach

                <article class="dashboard-action-item" data-studio-hover>
                    <span class="studio-note" style="margin-top:0;">Workspace Hint</span>
                    <strong>{{ $serverName }}</strong>
                    <p class="studio-copy" style="margin:0;">Guild aktif ini tetap jadi konteks utama untuk panel setup, script, penghasilan, dan penarikan.</p>
                </article>
            </div>
        </aside>
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
                        const rotateY = ((x / rect.width) - 0.5) * 7;
                        const rotateX = (0.5 - (y / rect.height)) * 6;

                        card.style.setProperty('--mx', `${(x / rect.width) * 100}%`);
                        card.style.setProperty('--my', `${(y / rect.height) * 100}%`);
                        card.style.transform = `perspective(1400px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
                    });

                    card.addEventListener('pointerleave', reset);
                    card.addEventListener('pointercancel', reset);
                });
            })();
        </script>
    </x-slot:scripts>
</x-portfolio.shell>
