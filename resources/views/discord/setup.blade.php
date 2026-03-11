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
            :root { color-scheme: dark; --ops-bg:#030817; --ops-panel:rgba(6,16,35,.84); --ops-line:rgba(91,231,255,.14); --ops-text:#eef4ff; --ops-muted:#93a4c6; --ops-cyan:#5be7ff; --ops-emerald:#7dffbc; --ops-violet:#8a91ff; --ops-shadow:0 28px 80px rgba(0,0,0,.35); --ops-display:"Orbitron","Oxanium",ui-sans-serif,sans-serif; --ops-mono:"JetBrains Mono",ui-monospace,SFMono-Regular,monospace; }
            * { box-sizing:border-box; }
            body { margin:0; min-height:100vh; background:radial-gradient(circle at 15% 0%, rgba(91,231,255,.12), transparent 28%),radial-gradient(circle at 85% 8%, rgba(138,145,255,.12), transparent 26%),linear-gradient(180deg,#020612 0%,#030816 100%); color:var(--ops-text); font-family:"Instrument Sans",ui-sans-serif,system-ui,sans-serif; }
            body::before { content:""; position:fixed; inset:0; pointer-events:none; background-image:linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px); background-size:46px 46px; opacity:.14; mask-image:linear-gradient(180deg, rgba(0,0,0,.9), transparent 96%); }
            a { color:inherit; text-decoration:none; }
            .shell { max-width:1400px; margin:0 auto; padding:1.25rem; }
            .topbar,.hero,.panel,.card { border:1px solid var(--ops-line); background:var(--ops-panel); box-shadow:var(--ops-shadow); backdrop-filter:blur(18px); }
            .topbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.15rem; border-radius:1.6rem; }
            .brand { display:flex; gap:.9rem; align-items:center; }
            .brand-mark { width:3rem; height:3rem; display:grid; place-items:center; border-radius:1rem; background:linear-gradient(135deg,var(--ops-cyan),var(--ops-emerald)); color:#04111c; font:800 .92rem/1 var(--ops-display); letter-spacing:.12em; text-transform:uppercase; }
            .brand h1,.hero h2,.panel h3 { margin:0; font-family:var(--ops-display); letter-spacing:.08em; text-transform:uppercase; }
            .brand p,.hero p,.muted { color:var(--ops-muted); }
            .brand p { margin:.2rem 0 0; font-size:.82rem; }
            .nav { display:flex; flex-wrap:wrap; gap:.75rem; }
            .nav a,.topbar .primary { border-radius:999px; border:1px solid rgba(91,231,255,.14); background:rgba(255,255,255,.04); padding:.72rem 1rem; font-weight:700; }
            .topbar .primary { background:linear-gradient(135deg, rgba(91,231,255,.9), rgba(138,145,255,.88)); color:#02101b; }
            .hero { margin-top:1.25rem; border-radius:2rem; padding:1.4rem; position:relative; overflow:hidden; }
            .hero::after,.panel::after,.card::after { content:""; position:absolute; inset:0; pointer-events:none; background:radial-gradient(circle at top right, rgba(91,231,255,.16), transparent 34%); }
            .hero-grid,.content-grid,.stats-grid,.feature-grid { display:grid; gap:1.25rem; }
            .hero-grid { grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr); align-items:start; }
            .content-grid { margin-top:1.25rem; grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
            .stats-grid { grid-template-columns:repeat(3,minmax(0,1fr)); margin-top:1.15rem; }
            .feature-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .hero-kicker,.label { display:inline-flex; align-items:center; gap:.55rem; border-radius:999px; border:1px solid rgba(91,231,255,.16); padding:.46rem .8rem; font:.72rem/1 var(--ops-mono); letter-spacing:.18em; text-transform:uppercase; color:var(--ops-cyan); }
            .hero-kicker::before { content:""; width:.46rem; height:.46rem; border-radius:999px; background:var(--ops-emerald); box-shadow:0 0 14px rgba(125,255,188,.8); }
            .hero h2 { margin-top:1rem; font-size:clamp(2.5rem,5vw,4.9rem); line-height:.92; }
            .hero h2 span { display:block; background:linear-gradient(90deg,var(--ops-cyan),#baf5ff,var(--ops-emerald)); -webkit-background-clip:text; background-clip:text; color:transparent; }
            .hero p { margin-top:1rem; max-width:48rem; line-height:1.8; }
            .panel,.card { position:relative; overflow:hidden; border-radius:1.7rem; padding:1.15rem; }
            .panel-header { display:flex; justify-content:space-between; gap:1rem; align-items:start; margin-bottom:1rem; }
            .panel-header p { margin:.25rem 0 0; color:var(--ops-muted); }
            .pill { border-radius:999px; border:1px solid rgba(91,231,255,.14); background:rgba(255,255,255,.04); padding:.5rem .8rem; font:.68rem/1 var(--ops-mono); letter-spacing:.14em; text-transform:uppercase; color:#dce7ff; }
            .metric { display:grid; gap:.55rem; border-radius:1.35rem; border:1px solid var(--ops-line); background:rgba(255,255,255,.03); padding:1rem; }
            .metric strong { font-family:var(--ops-display); font-size:1.9rem; }
            .status-row,.cmd,.feature { border-radius:1.2rem; border:1px solid var(--ops-line); background:rgba(255,255,255,.03); padding:1rem; }
            .status-row { display:flex; justify-content:space-between; gap:1rem; align-items:center; }
            .badge { border-radius:999px; padding:.42rem .75rem; font-size:.72rem; font-weight:800; }
            .badge.ok { background:rgba(125,255,188,.14); color:var(--ops-emerald); }
            .badge.warn { background:rgba(255,191,111,.14); color:#ffcc8b; }
            .cmd { font-family:var(--ops-mono); color:#f8fbff; background:rgba(4,9,20,.95); }
            .feature { font-weight:700; color:#e8f1ff; }
            @media (max-width:1100px) { .hero-grid,.content-grid { grid-template-columns:1fr; } .stats-grid,.feature-grid { grid-template-columns:1fr; } }
        </style>
    </head>
    <body>
        <div class="shell">
            <header class="topbar">
                <div class="brand">
                    <div class="brand-mark">LY</div>
                    <div>
                        <h1>LYVA Studio</h1>
                        <p>Surface setup Discord yang lebih rapi, cepat, dan tidak bikin bingung user.</p>
                    </div>
                </div>
                <nav class="nav">
                    @foreach ($navLinks as $link)
                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                    <a href="{{ route('dashboard') }}" class="primary">Dashboard</a>
                </nav>
            </header>

            <section class="hero">
                <div class="hero-grid">
                    <div>
                        <span class="hero-kicker">Discord Command Setup</span>
                        <h2>Discord command setup <span>siap deploy</span></h2>
                        <p>Satu tempat untuk invite bot, cek environment, copy endpoint Discord, dan jalankan command register tanpa perlu loncat-loncat panel. Halaman ini sekarang sengaja dibuat fokus ke action yang benar-benar dipakai saat setup bot.</p>
                        <div class="stats-grid">
                            <div class="metric">
                                <span class="label">Checks</span>
                                <strong>{{ count($setupChecks) }}</strong>
                                <span class="muted">Environment point yang dipantau.</span>
                            </div>
                            <div class="metric">
                                <span class="label">Commands</span>
                                <strong>{{ count($commands) }}</strong>
                                <span class="muted">Command terminal siap copy.</span>
                            </div>
                            <div class="metric">
                                <span class="label">Features</span>
                                <strong>{{ count($features) }}</strong>
                                <span class="muted">Slash command yang sudah disiapkan.</span>
                            </div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-header">
                            <div>
                                <span class="label">Quick flow</span>
                                <h3>Urutan setup tercepat</h3>
                            </div>
                            <span class="pill">3 step</span>
                        </div>
                        <div class="feature-grid">
                            <div class="feature">Invite bot ke server tujuan.</div>
                            <div class="feature">Isi interaction endpoint di portal Discord.</div>
                            <div class="feature">Register ulang command biar langsung aktif.</div>
                            <div class="feature">Cek semua env penting sebelum live.</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="content-grid">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <span class="label">Config status</span>
                            <h3>Environment checks</h3>
                            <p>Semua variable penting yang dibutuhkan Discord bot dan interaction endpoint.</p>
                        </div>
                        <span class="pill">Runtime</span>
                    </div>
                    <div class="feature-grid">
                        @foreach ($setupChecks as $check)
                            <div class="status-row">
                                <div>
                                    <strong style="display:block; font-size:1rem;">{{ $check['label'] }}</strong>
                                    <div class="muted" style="margin-top:.35rem; word-break:break-word; font-size:.85rem;">{{ $check['value'] }}</div>
                                </div>
                                <span class="badge {{ $check['ready'] ? 'ok' : 'warn' }}">{{ $check['ready'] ? 'Ready' : 'Check' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <span class="label">Links</span>
                            <h3>Invite dan endpoint</h3>
                            <p>Copy URL penting ini saat onboarding bot ke server Discord baru.</p>
                        </div>
                        <span class="pill">Public URLs</span>
                    </div>
                    <div class="cmd">
                        <strong style="display:block; margin-bottom:.5rem;">Invite URL</strong>
                        {{ $inviteUrl ?? 'Isi DISCORD_APPLICATION_ID dulu.' }}
                    </div>
                    <div class="cmd" style="margin-top:1rem;">
                        <strong style="display:block; margin-bottom:.5rem;">Interaction Endpoint URL</strong>
                        {{ $interactionUrl ?? 'Isi APP_URL dulu.' }}
                    </div>
                    <p class="muted" style="margin-top:1rem; font-size:.84rem;">Discord hanya menerima endpoint publik. Jangan pakai `localhost` atau `127.0.0.1` untuk production setup.</p>
                </section>
            </div>

            <div class="content-grid">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <span class="label">Terminal steps</span>
                            <h3>Commands to run</h3>
                            <p>Urutan command yang biasanya dipakai saat deploy atau register slash command.</p>
                        </div>
                        <span class="pill">CLI</span>
                    </div>
                    <div style="display:grid; gap:.8rem;">
                        @foreach ($commands as $command)
                            <div class="cmd">{{ $command }}</div>
                        @endforeach
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <span class="label">Command set</span>
                            <h3>Slash commands yang sudah disiapkan</h3>
                            <p>Snapshot fitur command yang sudah disiapkan bot untuk workspace ini.</p>
                        </div>
                        <span class="pill">Bot ready</span>
                    </div>
                    <div class="feature-grid">
                        @foreach ($features as $feature)
                            <div class="feature">{{ $feature }}</div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </body>
</html>
