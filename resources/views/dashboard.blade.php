@php
    $title = __('Dashboard');
    $activeGuild = $managedGuild ?? null;
    $serverName = $activeGuild['name'] ?? 'Belum pilih server';
    $guildId = $activeGuild['id'] ?? 'Guild belum dipilih';

    $trackedGamesCount = is_numeric($stats[0]['value'] ?? null) ? (int) $stats[0]['value'] : null;
    $webhookCountValue = is_numeric($stats[1]['value'] ?? null) ? (int) $stats[1]['value'] : null;
    $alertCountValue = is_numeric($stats[2]['value'] ?? null) ? (int) $stats[2]['value'] : null;
    $reportCountValue = is_numeric($stats[3]['value'] ?? null) ? (int) $stats[3]['value'] : null;
    $raceCountValue = is_numeric($stats[4]['value'] ?? null) ? (int) $stats[4]['value'] : null;

    $alertCount = $alertCountValue ?? 0;
    $reportCount = $reportCountValue ?? 0;
    $webhookCount = $webhookCountValue ?? 0;
    $raceCount = $raceCountValue ?? 0;
    $activeWebhookCount = collect($webhooks)->where('is_active', true)->count();
    $hasLiveDashboardData = collect([$trackedGamesCount, $webhookCountValue, $alertCountValue, $reportCountValue, $raceCountValue])
        ->contains(fn ($value) => $value !== null && $value > 0);
    $healthScore = $hasLiveDashboardData
        ? max(52, min(98, 92 - ($alertCount * 6) - ($reportCount * 3) + ($activeWebhookCount * 2)))
        : null;

    $displayMetric = fn ($value) => ($value === null || (is_numeric($value) && (int) $value === 0)) ? 'Kosong' : str_pad((string) $value, 2, '0', STR_PAD_LEFT);

    $quickLinks = [
        ['label' => 'Pilih Server', 'href' => route('guilds.select'), 'copy' => 'Pindah guild aktif dan scope modul.', 'tone' => 'cyan'],
        ['label' => 'Discord Setup', 'href' => route('discord.setup'), 'copy' => 'Rapikan webhook, endpoint, dan sync bot.', 'tone' => 'violet'],
        ['label' => 'VIP Title Setup', 'href' => route('vip-title.setup'), 'copy' => 'Kelola map key, gamepass, dan API key.', 'tone' => 'emerald'],
        ['label' => 'Roblox Scripts', 'href' => route('roblox.scripts.index'), 'copy' => 'Ambil file siap tempel ke game.', 'tone' => 'amber'],
    ];

    $statusCards = [
        ['label' => 'Bot routing', 'value' => $webhookCount > 0 ? 'Online' : 'Kosong', 'copy' => $webhookCount > 0 ? $webhookCount.' webhook siap kirim event.' : 'Belum ada webhook aktif.', 'progress' => $webhookCount > 0 ? min(100, max(20, ($webhookCount * 18) + 28)) : 0],
        ['label' => 'Alert pressure', 'value' => $alertCount > 0 ? 'Monitor' : 'Kosong', 'copy' => $alertCount > 0 ? $alertCount.' alert aktif perlu perhatian.' : 'Belum ada alert aktif.', 'progress' => $alertCount > 0 ? min(100, max(24, ($alertCount * 22) + 16)) : 0],
        ['label' => 'Reports desk', 'value' => $reportCount > 0 ? 'Active' : 'Kosong', 'copy' => $reportCount > 0 ? $reportCount.' report menunggu triage.' : 'Belum ada report aktif.', 'progress' => $reportCount > 0 ? min(100, max(18, ($reportCount * 20) + 14)) : 0],
    ];

    $primaryAlert = collect($alerts)->first();
    $primaryReport = collect($reports)->first();
@endphp

<x-layouts::app :title="$title">
    <style>
        @import url('https://fonts.bunny.net/css?family=space-grotesk:500,700|jetbrains-mono:400,600,700');

        .dash {
            --bg: rgba(8, 20, 39, 0.84);
            --bg2: rgba(10, 24, 46, 0.94);
            --line: rgba(123, 223, 255, 0.14);
            --line2: rgba(123, 223, 255, 0.3);
            --text: #eef6ff;
            --muted: #8ea6c8;
            --cyan: #7bdfff;
            --violet: #9da7ff;
            --emerald: #7ff7c4;
            --amber: #ffca7b;
            --rose: #ff8f9d;
            position: relative;
            overflow: hidden;
            padding: .45rem 1rem 1rem;
            color: var(--text);
            font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
        }

        .dash > :not(.orb):not(.particlefield) {
            position: relative;
            z-index: 1;
        }

        .dash::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.11;
            background-image:
                linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: linear-gradient(180deg, rgba(0,0,0,.9), transparent 98%);
        }

        .dash .orb {
            position: absolute;
            border-radius: 999px;
            filter: blur(18px);
            pointer-events: none;
            z-index: 0;
        }

        .dash .orb.a { top: -3rem; left: -2rem; width: 14rem; height: 14rem; background: radial-gradient(circle, rgba(123,223,255,.22), transparent 70%); }
        .dash .orb.b { top: 9rem; right: -5rem; width: 18rem; height: 18rem; background: radial-gradient(circle, rgba(157,167,255,.2), transparent 70%); }
        .dash .orb.c { bottom: 10rem; left: 22%; width: 12rem; height: 12rem; background: radial-gradient(circle, rgba(127,247,196,.12), transparent 70%); }

        .dash .particlefield {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .dash .spark {
            position: absolute;
            width: .2rem;
            height: .2rem;
            border-radius: 999px;
            background: rgba(214, 243, 255, .78);
            box-shadow: 0 0 12px rgba(123,223,255,.42);
            opacity: 0;
            animation: driftStar linear infinite;
        }

        .dash .hero,
        .dash .panel,
        .dash .metric,
        .dash .mini,
        .dash .cta,
        .dash .banner {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, var(--bg), rgba(5,12,24,.94));
            box-shadow: 0 28px 70px rgba(0,0,0,.3);
            backdrop-filter: blur(18px);
        }

        .dash .hero::after,
        .dash .panel::after,
        .dash .metric::after,
        .dash .mini::after,
        .dash .cta::after,
        .dash .banner::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at var(--mx, 50%) var(--my, 50%), rgba(123,223,255,.18), transparent 34%),
                radial-gradient(circle at top right, rgba(157,167,255,.08), transparent 28%);
        }

        .dash .banner { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; padding: 1rem 1.1rem; border-radius: 1.6rem; background: linear-gradient(135deg, rgba(39,20,20,.74), rgba(16,19,35,.94)); }
        .dash .hero { display: grid; grid-template-columns: minmax(0,1.18fr) minmax(320px,.92fr); align-items: start; gap: 1rem; padding: 1rem 1.15rem 1.15rem; border-radius: 2rem; background: radial-gradient(circle at top left, rgba(123,223,255,.14), transparent 32%), radial-gradient(circle at bottom right, rgba(157,167,255,.12), transparent 32%), linear-gradient(135deg, rgba(5,16,32,.98), rgba(6,16,31,.92)); }
        .dash .panel, .dash .metric, .dash .mini { border-radius: 1.5rem; }
        .dash .panel, .dash .metric, .dash .mini, .dash .cta { padding: 1rem; }
        .dash .cta { border-radius: 1.2rem; text-decoration: none; transition: transform .18s ease, border-color .18s ease; }
        .dash .cta:hover, .dash .metric:hover, .dash .mini:hover, .dash .item:hover { transform: translateY(-4px); border-color: var(--line2); }

        .dash .kicker, .dash .chip, .dash .tag, .dash .meta, .dash .label, .dash .note, .dash .pill {
            font-family: "JetBrains Mono", ui-monospace, monospace;
            text-transform: uppercase;
            letter-spacing: .14em;
        }

        .dash .kicker { display: inline-flex; align-items: center; gap: .55rem; border-radius: 999px; border: 1px solid rgba(123,223,255,.18); background: rgba(255,255,255,.04); padding: .48rem .82rem; color: var(--cyan); font-size: .68rem; font-weight: 700; }
        .dash .kicker::before { content: ""; width: .5rem; height: .5rem; border-radius: 999px; background: var(--emerald); box-shadow: 0 0 16px rgba(127,247,196,.9); }
        .dash h1, .dash h2, .dash h3 { margin: 0; font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif; letter-spacing: -.03em; }
        .dash h1 { margin-top: .75rem; max-width: 12ch; font-size: clamp(2.7rem, 5vw, 4.8rem); line-height: .9; }
        .dash h1 span { display: block; background: linear-gradient(90deg, var(--cyan), #d9f7ff 52%, var(--emerald)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .dash p { margin: 0; color: var(--muted); line-height: 1.8; }
        .dash .lead { margin-top: .85rem; max-width: 44rem; }
        .dash .hero-copy { display: grid; gap: 0; }
        .dash .hero-orbit {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(9rem, .8fr);
            gap: 1rem;
            align-items: center;
            margin-top: 1rem;
            padding: 1rem 1.05rem;
            border-radius: 1.45rem;
            border: 1px solid rgba(123,223,255,.1);
            background: linear-gradient(135deg, rgba(9,24,47,.74), rgba(6,14,28,.88));
        }
        .dash .hero-orbit-copy { display: grid; gap: .28rem; }
        .dash .hero-orbit-copy strong { font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif; font-size: 1.15rem; letter-spacing: -.02em; }
        .dash .hero-orbit-copy p { font-size: .92rem; line-height: 1.65; }
        .dash .row, .dash .stats, .dash .grid, .dash .actions, .dash .twins, .dash .subgrid { display: grid; gap: 1rem; }
        .dash .row { margin-top: 1.1rem; display: flex; flex-wrap: wrap; gap: .7rem; }
        .dash .chip, .dash .tag, .dash .pill { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: .45rem .76rem; font-size: .66rem; font-weight: 700; }
        .dash .chip { border: 1px solid rgba(123,223,255,.12); background: rgba(255,255,255,.04); color: #dcebff; }
        .dash .stats { grid-template-columns: repeat(3, minmax(0,1fr)); margin-top: 1.2rem; }
        .dash .label { font-size: .66rem; color: var(--muted); font-weight: 700; }
        .dash .value { display: block; margin-top: .75rem; font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif; font-size: 1.8rem; font-weight: 700; }
        .dash .copy { margin-top: .45rem; font-size: .86rem; }
        .dash .bar { margin-top: .85rem; height: .45rem; overflow: hidden; border-radius: 999px; background: rgba(255,255,255,.06); }
        .dash .bar span { display: block; height: 100%; width: var(--fill, 0%); border-radius: inherit; background: linear-gradient(90deg, var(--cyan), var(--emerald)); box-shadow: 0 0 18px rgba(123,223,255,.35); animation: fill .9s ease both; transform-origin: left center; }
        .dash .stack { display: grid; gap: 1rem; }
        .dash .top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
        .dash .button, .dash .ghost { display: inline-flex; align-items: center; justify-content: center; text-decoration: none; border-radius: 999px; font-weight: 700; transition: transform .18s ease; }
        .dash .button { padding: .75rem 1rem; color: #04111f; background: linear-gradient(135deg, rgba(123,223,255,.92), rgba(157,167,255,.88)); border: 1px solid rgba(123,223,255,.18); }
        .dash .ghost { padding: .62rem .95rem; color: var(--text); background: rgba(255,255,255,.04); border: 1px solid rgba(123,223,255,.16); }
        .dash .button:hover, .dash .ghost:hover { transform: translateY(-2px); }
        .dash .server { display: grid; grid-template-columns: 6.9rem minmax(0,1fr); gap: 1.1rem; align-items: center; padding: 1rem; border-radius: 1.35rem; border: 1px solid rgba(123,223,255,.1); background: linear-gradient(145deg, rgba(255,255,255,.05), rgba(255,255,255,.02)); }
        .dash .radar {
            --orbit-time: 10s;
            position: relative;
            width: 6.9rem;
            height: 6.9rem;
            margin: 0 auto;
            border-radius: 999px;
            border: 1px solid rgba(123,223,255,.18);
            background:
                radial-gradient(circle at center, rgba(123,223,255,.28), rgba(123,223,255,.07) 26%, transparent 27%),
                radial-gradient(circle at center, rgba(127,247,196,.12), transparent 66%);
            box-shadow:
                inset 0 0 34px rgba(123,223,255,.14),
                0 0 40px rgba(123,223,255,.08);
        }
        .dash .radar::before, .dash .radar::after { content: ""; position: absolute; border-radius: 999px; }
        .dash .radar::before { inset: .8rem; border: 1px solid rgba(123,223,255,.14); }
        .dash .radar::after { inset: 1.65rem; border: 1px solid rgba(157,167,255,.16); }
        .dash .core {
            position: absolute;
            inset: 2rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(137, 231, 255, .28), rgba(84, 161, 209, .18) 52%, rgba(14, 29, 53, .8) 72%);
            box-shadow: 0 0 26px rgba(123,223,255,.22);
        }
        .dash .orbit {
            position: absolute;
            inset: 0;
            animation: spin linear infinite;
            transform-origin: center;
        }
        .dash .orbit::before {
            content: "";
            position: absolute;
            top: 50%;
            right: -.22rem;
            width: .82rem;
            height: .82rem;
            border-radius: 999px;
            transform: translateY(-50%);
            background: radial-gradient(circle, rgba(191,255,227,1), rgba(127,247,196,.85) 62%, rgba(127,247,196,0) 68%);
            box-shadow: 0 0 18px rgba(127,247,196,.84);
        }
        .dash .orbit.one { inset: .55rem; animation-duration: 7.8s; }
        .dash .orbit.two { inset: 1.35rem; animation-duration: 10.5s; animation-direction: reverse; }
        .dash .orbit.three { inset: 2.15rem; animation-duration: 6.8s; }
        .dash .orbit.two::before { width: .48rem; height: .48rem; opacity: .76; box-shadow: 0 0 14px rgba(123,223,255,.62); background: radial-gradient(circle, rgba(194,234,255,1), rgba(123,223,255,.8) 60%, rgba(123,223,255,0) 68%); }
        .dash .orbit.three::before { width: .36rem; height: .36rem; opacity: .62; background: radial-gradient(circle, rgba(255,255,255,.94), rgba(157,167,255,.72) 60%, rgba(157,167,255,0) 68%); box-shadow: 0 0 10px rgba(157,167,255,.56); }
        .dash .meta { margin-top: .45rem; font-size: .68rem; color: var(--muted); font-weight: 700; }
        .dash .score { margin-top: .55rem; display: grid; gap: .3rem; }
        .dash .score strong { font-family: "Space Grotesk", "Instrument Sans", ui-sans-serif, sans-serif; font-size: 2.2rem; line-height: 1; }
        .dash .subgrid { grid-template-columns: repeat(3, minmax(0,1fr)); }
        .dash .box { padding: .9rem; border-radius: 1.1rem; border: 1px solid rgba(123,223,255,.08); background: rgba(255,255,255,.03); }
        .dash .actions { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .dash .metrics { display: grid; gap: 1rem; grid-template-columns: repeat(5, minmax(0,1fr)); margin-top: 1.2rem; }
        .dash .metric .value { font-size: clamp(2rem, 3vw, 2.5rem); line-height: 1; }
        .dash .grid { grid-template-columns: repeat(2, minmax(0,1fr)); margin-top: 1.2rem; }
        .dash .item { padding: 1rem; border-radius: 1.2rem; border: 1px solid rgba(123,223,255,.09); background: rgba(255,255,255,.035); transition: transform .18s ease, border-color .18s ease; }
        .dash .itemtop { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
        .dash .listcopy { margin-top: .4rem; font-size: .88rem; }
        .dash .note { display: inline-flex; margin-top: .72rem; font-size: .63rem; color: #abc2e7; font-weight: 700; }
        .dash .tag { color: #f1f7ff; background: rgba(255,255,255,.08); }
        .dash .tag.cyan { background: rgba(123,223,255,.12); color: var(--cyan); }
        .dash .tag.violet { background: rgba(157,167,255,.16); color: #cad0ff; }
        .dash .tag.emerald { background: rgba(127,247,196,.12); color: var(--emerald); }
        .dash .tag.amber { background: rgba(255,202,123,.14); color: var(--amber); }
        .dash .tag.rose { background: rgba(255,143,157,.14); color: var(--rose); }
        .dash .twins { grid-template-columns: repeat(2, minmax(0,1fr)); margin-top: 1rem; }

        @keyframes fill { from { transform: scaleX(.25); opacity: .45; } to { transform: scaleX(1); opacity: 1; } }
        @keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @keyframes driftStar {
            0% { transform: translate3d(0, 105%, 0) scale(.65); opacity: 0; }
            14%, 82% { opacity: .68; }
            100% { transform: translate3d(28px, -18%, 0) scale(1); opacity: 0; }
        }

        @media (max-width: 1180px) {
            .dash .hero, .dash .grid { grid-template-columns: 1fr; }
            .dash .metrics, .dash .stats, .dash .subgrid { grid-template-columns: repeat(2, minmax(0,1fr)); }
        }

        @media (max-width: 760px) {
            .dash { padding: .7rem 0 1rem; }
            .dash .banner, .dash .top, .dash .itemtop { flex-direction: column; }
            .dash h1 { max-width: none; font-size: clamp(2.3rem, 14vw, 3.2rem); }
            .dash .hero-orbit, .dash .server { grid-template-columns: 1fr; }
            .dash .stats, .dash .actions, .dash .metrics, .dash .subgrid, .dash .twins { grid-template-columns: 1fr; }
            .dash .radar { margin: 0 auto; }
        }

        @media (prefers-reduced-motion: reduce) {
            .dash .spark,
            .dash .orbit,
            .dash .dot,
            .dash .bar span,
            .dash .cta,
            .dash .metric,
            .dash .mini,
            .dash .item {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>

    <div class="dash">
        <div class="orb a"></div>
        <div class="orb b"></div>
        <div class="orb c"></div>
        <div class="particlefield" id="dashParticles" aria-hidden="true"></div>

        @unless ($hasLiveDashboardData)
            <section class="banner" data-glow-card>
                <div>
                    <span class="kicker">Dashboard kosong</span>
                    <p class="copy" style="margin-top:.55rem;">Belum ada data live yang masuk ke dashboard ini. Begitu webhook, alert, report, atau event mulai tercatat, panel di bawah akan otomatis menampilkan data asli.</p>
                </div>
                <a href="{{ route('discord.setup') }}" class="ghost">Buka setup</a>
            </section>
        @endunless

        <section class="hero" data-glow-card>
            <div>
                <div class="hero-copy">
                <span class="kicker">Roblox Discord Ops</span>
                <h1>Control room yang <span>lebih rapi dan lebih fokus.</span></h1>
                <p class="lead">Halaman ini saya susun ulang supaya kondisi server aktif, quick action, alert, dan report lebih gampang dipindai. Blok besar yang terasa kosong sekarang dipecah jadi panel kerja yang punya prioritas jelas.</p>
                </div>

                <div class="hero-orbit" data-glow-card>
                    <div class="hero-orbit-copy">
                        <span class="label" style="color:var(--cyan)">Orbital signal</span>
                        <strong>Surface server aktif sekarang terasa lebih hidup.</strong>
                        <p>Saya tambahkan sistem orbit dan partikel supaya dashboard punya gerak halus, tapi tetap fokus ke data dan tombol aksi.</p>
                    </div>
                    <div class="radar" aria-hidden="true">
                        <span class="core"></span>
                        <span class="orbit one"></span>
                        <span class="orbit two"></span>
                        <span class="orbit three"></span>
                    </div>
                </div>

                <div class="row">
                    <span class="chip">Server aktif: {{ $serverName }}</span>
                    <span class="chip">Guild ID: {{ $guildId }}</span>
                    <span class="chip">Tracked game: {{ $trackedGamesCount > 0 ? $trackedGamesCount : 'Kosong' }}</span>
                    <span class="chip">Webhook aktif: {{ $activeWebhookCount > 0 ? $activeWebhookCount : 'Kosong' }}</span>
                </div>

                <div class="stats">
                    @foreach ($statusCards as $card)
                        <article class="mini" data-glow-card>
                            <span class="label">{{ $card['label'] }}</span>
                            <strong class="value">{{ $card['value'] }}</strong>
                            <p class="copy">{{ $card['copy'] }}</p>
                            <div class="bar" style="--fill: {{ $card['progress'] }}%;">
                                <span></span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <aside class="stack">
                <div class="panel" data-glow-card>
                    <div class="top">
                        <div>
                            <span class="label" style="color:var(--cyan)">Server spotlight</span>
                            <h2 style="margin-top:.35rem;">Interactive command deck</h2>
                            <p class="copy">Panel kanan sekarang jadi pusat konteks dan aksi cepat, bukan kartu besar yang terasa kosong.</p>
                        </div>
                        <a href="{{ route('guilds.select') }}" class="ghost">Ganti server</a>
                    </div>

                    <div class="server" style="margin-top:1rem;">
                        <div class="radar" aria-hidden="true">
                            <span class="core"></span>
                            <span class="orbit one"></span>
                            <span class="orbit two"></span>
                            <span class="orbit three"></span>
                        </div>
                        <div>
                            <h3 style="font-size:1.35rem;">{{ $serverName }}</h3>
                            <div class="meta">Guild ID &middot; {{ $guildId }}</div>
                            <div class="score">
                                <span class="label">Workspace health</span>
                                <strong>{{ $healthScore ?? 'Kosong' }}</strong>
                                <p class="copy">
                                    @if ($healthScore)
                                        Skor ini diringkas dari webhook aktif, alert terbuka, dan antrean report yang masih berjalan.
                                    @else
                                        Belum ada sinyal operasional yang cukup untuk menghitung health score.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="subgrid" style="margin-top:1rem;">
                        <div class="box">
                            <span class="label">Scope</span>
                            <strong style="display:block;margin-top:.4rem;">{{ $activeGuild ? 'Locked' : 'Unset' }}</strong>
                        </div>
                        <div class="box">
                            <span class="label">Alerts</span>
                            <strong style="display:block;margin-top:.4rem;">{{ $alertCount > 0 ? $alertCount : 'Kosong' }}</strong>
                        </div>
                        <div class="box">
                            <span class="label">Reports</span>
                            <strong style="display:block;margin-top:.4rem;">{{ $reportCount > 0 ? $reportCount : 'Kosong' }}</strong>
                        </div>
                    </div>
                </div>

                <div class="actions">
                    @foreach ($quickLinks as $link)
                        <a href="{{ $link['href'] }}" class="cta" data-glow-card>
                            <span class="tag {{ $link['tone'] }}">Open</span>
                            <h3 style="margin-top:.8rem;font-size:1.02rem;">{{ $link['label'] }}</h3>
                            <p class="copy">{{ $link['copy'] }}</p>
                        </a>
                    @endforeach
                </div>
            </aside>
        </section>

        <section class="metrics">
            @foreach ($stats as $stat)
                <article class="metric" data-glow-card>
                    <span class="label">{{ $stat['label'] === 'Tracked games' ? 'Tracked experiences' : $stat['label'] }}</span>
                    <strong class="value">{{ $displayMetric($stat['value']) }}</strong>
                    <p class="copy">{{ $stat['hint'] }}</p>
                </article>
            @endforeach
        </section>

        <div class="grid">
            <section class="panel" data-glow-card>
                <div class="top" style="margin-bottom:1rem;">
                    <div>
                        <span class="label" style="color:var(--cyan)">Ops alerts</span>
                        <h2 style="margin-top:.35rem;">Stream insiden yang paling butuh perhatian</h2>
                        <p class="copy">Severity, status, dan waktu kejadian sekarang dipadatkan supaya bisa dibaca dalam sekali scan.</p>
                    </div>
                    <span class="tag {{ $alertCount > 0 ? 'rose' : 'violet' }}">{{ $alertCount > 0 ? 'Need review' : 'Kosong' }}</span>
                </div>

                @if ($primaryAlert)
                    <article class="mini" data-glow-card style="margin-bottom:1rem;">
                        <div class="itemtop">
                            <div>
                                <span class="label" style="color:var(--cyan)">Primary incident</span>
                                <h3 style="margin-top:.45rem;font-size:1.15rem;">{{ $primaryAlert->title }}</h3>
                                <p class="copy">{{ $primaryAlert->message }}</p>
                            </div>
                            <span class="tag {{ $primaryAlert->severity === 'critical' ? 'rose' : ($primaryAlert->severity === 'warning' ? 'amber' : 'cyan') }}">{{ strtoupper($primaryAlert->severity) }}</span>
                        </div>
                        <div class="row" style="margin-top:.9rem;">
                            <span class="chip">Source: {{ $primaryAlert->source }}</span>
                            <span class="chip">Status: {{ ucfirst($primaryAlert->status) }}</span>
                            <span class="chip">{{ optional($primaryAlert->occurred_at)->diffForHumans() }}</span>
                        </div>
                    </article>
                @endif

                <div class="stack">
                    @forelse ($alerts as $alert)
                        <article class="item" data-glow-card>
                            <div class="itemtop">
                                <div>
                                    <h3 style="font-size:1rem;">{{ $alert->title }}</h3>
                                    <p class="listcopy">{{ $alert->message }}</p>
                                </div>
                                <span class="tag {{ $alert->severity === 'critical' ? 'rose' : ($alert->severity === 'warning' ? 'amber' : 'cyan') }}">{{ ucfirst($alert->severity) }}</span>
                            </div>
                            <span class="note">{{ $alert->source }} &middot; {{ ucfirst($alert->status) }} &middot; {{ optional($alert->occurred_at)->diffForHumans() }}</span>
                        </article>
                    @empty
                        <p class="copy">Belum ada alert aktif saat ini.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel" data-glow-card>
                <div class="top" style="margin-bottom:1rem;">
                    <div>
                        <span class="label" style="color:var(--emerald)">Reports desk</span>
                        <h2 style="margin-top:.35rem;">Player and bug reports</h2>
                        <p class="copy">Nama pelapor, target, prioritas, dan status kini lebih jelas tanpa blok yang saling bertabrakan.</p>
                    </div>
                    <span class="tag {{ $reportCount > 0 ? 'amber' : 'violet' }}">{{ $reportCount > 0 ? 'Active queue' : 'Kosong' }}</span>
                </div>

                @if ($primaryReport)
                    <article class="mini" data-glow-card style="margin-bottom:1rem;">
                        <div class="itemtop">
                            <div>
                                <span class="label" style="color:var(--emerald)">Top priority</span>
                                <h3 style="margin-top:.45rem;font-size:1.15rem;">{{ $primaryReport->reported_player_name }}</h3>
                                <p class="copy">{{ $primaryReport->summary }}</p>
                            </div>
                            <span class="tag {{ $primaryReport->priority === 'high' ? 'rose' : ($primaryReport->priority === 'medium' ? 'amber' : 'cyan') }}">{{ strtoupper($primaryReport->priority) }}</span>
                        </div>
                        <div class="row" style="margin-top:.9rem;">
                            <span class="chip">Reporter: {{ $primaryReport->reporter_name }}</span>
                            <span class="chip">Category: {{ $primaryReport->category }}</span>
                            <span class="chip">Status: {{ ucfirst($primaryReport->status) }}</span>
                        </div>
                    </article>
                @endif

                <div class="stack">
                    @forelse ($reports as $report)
                        <article class="item" data-glow-card>
                            <div class="itemtop">
                                <div>
                                    <h3 style="font-size:1rem;">{{ $report->reported_player_name }}</h3>
                                    <p class="listcopy">{{ $report->summary }}</p>
                                </div>
                                <span class="tag {{ $report->priority === 'high' ? 'rose' : ($report->priority === 'medium' ? 'amber' : 'cyan') }}">{{ ucfirst($report->priority) }}</span>
                            </div>
                            <span class="note">{{ $report->reporter_name }} &middot; {{ $report->category }} &middot; {{ ucfirst($report->status) }}</span>
                        </article>
                    @empty
                        <p class="copy">Belum ada report yang masuk.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid">
            <section class="panel" data-glow-card>
                <div class="top" style="margin-bottom:1rem;">
                    <div>
                        <span class="label" style="color:var(--violet)">Discord delivery</span>
                        <h2 style="margin-top:.35rem;">Webhook dan pulse distribusi</h2>
                        <p class="copy">Feed aktif, channel tujuan, dan last delivery sekarang tampil lebih ringkas dan rapih.</p>
                    </div>
                    <span class="tag violet">{{ $activeWebhookCount > 0 ? $activeWebhookCount.' active' : 'Kosong' }}</span>
                </div>

                <div class="stack">
                    @forelse ($webhooks as $webhook)
                        <article class="item" data-glow-card>
                            <div class="itemtop">
                                <div>
                                    <h3 style="font-size:1rem;">{{ $webhook->name }}</h3>
                                    <p class="listcopy">{{ $webhook->channel_name }}</p>
                                </div>
                                <span class="tag {{ $webhook->is_active ? 'emerald' : 'violet' }}">{{ $webhook->is_active ? 'Active' : 'Paused' }}</span>
                            </div>
                            <span class="note">Last delivery &middot; {{ optional($webhook->last_delivered_at)->diffForHumans() ?? 'belum pernah kirim' }}</span>
                        </article>
                    @empty
                        <p class="copy">Belum ada webhook yang tersimpan.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel" data-glow-card>
                <div class="top" style="margin-bottom:1rem;">
                    <div>
                        <span class="label" style="color:var(--amber)">Community flow</span>
                        <h2 style="margin-top:.35rem;">Race desk dan langkah berikutnya</h2>
                        <p class="copy">Bagian bawah kanan sekarang diisi event aktif dan next action supaya panel tidak terasa kosong.</p>
                    </div>
                    <span class="tag amber">{{ $raceCount > 0 ? $raceCount.' open' : 'Kosong' }}</span>
                </div>

                <div class="stack">
                    @forelse ($races as $race)
                        <article class="item" data-glow-card>
                            <div class="itemtop">
                                <div>
                                    <h3 style="font-size:1rem;">#{{ $race->id }} {{ $race->title }}</h3>
                                    <p class="listcopy">{{ $race->participants_count }}/{{ $race->max_players }} player &middot; Entry {{ $race->entry_fee_robux }} R$</p>
                                </div>
                                <span class="tag {{ $race->status === 'registration_open' ? 'emerald' : 'violet' }}">{{ str_replace('_', ' ', ucfirst($race->status)) }}</span>
                            </div>
                            <span class="note">Race queue</span>
                        </article>
                    @empty
                        <p class="copy">Belum ada race event yang aktif.</p>
                    @endforelse
                </div>

                <div class="twins">
                    <article class="mini" data-glow-card>
                        <span class="label" style="color:var(--cyan)">Next move</span>
                        <h3 style="margin-top:.45rem;font-size:1rem;">Buka setup yang relevan lebih cepat</h3>
                        <p class="copy">Kartu aksi di atas sudah saya buat lebih seimbang supaya alur pindah dari dashboard ke setup terasa mulus.</p>
                    </article>
                    <article class="mini" data-glow-card>
                        <span class="label" style="color:var(--emerald)">Server-aware</span>
                        <h3 style="margin-top:.45rem;font-size:1rem;">Semua modul tetap nyambung ke guild aktif</h3>
                        <p class="copy">Begitu server dipilih, konteks halaman ini tetap jelas dan tidak terasa seperti panel generik.</p>
                    </article>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-glow-card]').forEach((card) => {
            const reset = () => {
                card.style.setProperty('--mx', '50%');
                card.style.setProperty('--my', '50%');
            };

            reset();

            card.addEventListener('pointermove', (event) => {
                const rect = card.getBoundingClientRect();
                const x = ((event.clientX - rect.left) / rect.width) * 100;
                const y = ((event.clientY - rect.top) / rect.height) * 100;

                card.style.setProperty('--mx', `${x}%`);
                card.style.setProperty('--my', `${y}%`);
            });

            card.addEventListener('pointerleave', reset);
        });

        const particleRoot = document.getElementById('dashParticles');
        if (particleRoot) {
            for (let i = 0; i < 26; i += 1) {
                const particle = document.createElement('span');
                particle.className = 'spark';
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${55 + (Math.random() * 45)}%`;
                particle.style.animationDuration = `${9 + Math.random() * 8}s`;
                particle.style.animationDelay = `${Math.random() * 7}s`;
                particle.style.opacity = `${0.18 + Math.random() * 0.34}`;
                particleRoot.appendChild(particle);
            }
        }
    </script>
</x-layouts::app>
