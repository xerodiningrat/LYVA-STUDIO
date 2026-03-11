@php
    $title = __('Dashboard');
    $activeGuild = $managedGuild ?? null;
    $currentUser = auth()->user();
    $quickLinks = [
        ['label' => 'Pilih Server', 'href' => route('guilds.select'), 'copy' => 'Ganti server Discord yang sedang kamu kelola dari panel ini.'],
        ['label' => 'VIP Title Setup', 'href' => route('vip-title.setup'), 'copy' => 'Atur map key, gamepass, API key, dan snippet Roblox dari dashboard.'],
        ['label' => 'Discord Setup', 'href' => route('discord.setup'), 'copy' => 'Rapikan command, webhook, dan koneksi bot per server.'],
        ['label' => 'Roblox Scripts', 'href' => route('roblox.scripts.index'), 'copy' => 'Ambil file Roblox yang siap tempel dan sinkron dengan backend.'],
    ];
    $statusCards = [
        ['label' => 'Bot Routing', 'value' => ($stats[1]['value'] ?? 0) > 0 ? 'ONLINE' : 'IDLE', 'detail' => ($stats[1]['value'] ?? 0) . ' webhook aktif siap kirim alert.', 'progress' => min(100, max(18, (($stats[1]['value'] ?? 0) * 18) + 22)), 'tone' => 'cyan'],
        ['label' => 'Issue Queue', 'value' => ($stats[2]['value'] ?? 0) > 0 ? 'MONITOR' : 'CLEAR', 'detail' => ($stats[2]['value'] ?? 0) . ' insiden terbuka masih perlu tindakan.', 'progress' => min(100, max(20, (($stats[2]['value'] ?? 0) * 16) + 18)), 'tone' => 'amber'],
        ['label' => 'Reports Desk', 'value' => ($stats[3]['value'] ?? 0) > 0 ? 'ACTIVE' : 'QUIET', 'detail' => ($stats[3]['value'] ?? 0) . ' laporan player menunggu review.', 'progress' => min(100, max(16, (($stats[3]['value'] ?? 0) * 15) + 20)), 'tone' => 'emerald'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            :root { color-scheme: dark; --ops-bg:#020816; --ops-panel:rgba(7,16,34,.82); --ops-line:rgba(104,240,255,.12); --ops-line-strong:rgba(104,240,255,.22); --ops-text:#f3f7ff; --ops-muted:#95a6c9; --ops-cyan:#68f0ff; --ops-emerald:#76ffb8; --ops-violet:#8b94ff; --ops-amber:#ffbf6f; --ops-shadow:0 30px 90px rgba(0,0,0,.38); --ops-mono:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,monospace; --ops-display:"Orbitron","Oxanium",ui-sans-serif,sans-serif; }
            * { box-sizing:border-box; }
            body { margin:0; min-height:100vh; background:radial-gradient(circle at 14% 10%, rgba(104,240,255,.09), transparent 24%),radial-gradient(circle at 84% 8%, rgba(139,148,255,.11), transparent 26%),linear-gradient(180deg,#030814 0%,#020612 100%); color:var(--ops-text); font-family:"Inter",ui-sans-serif,system-ui,sans-serif; }
            body::before { content:""; position:fixed; inset:0; pointer-events:none; background-image:linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px); background-size:44px 44px; opacity:.16; mask-image:linear-gradient(180deg, rgba(0,0,0,.9), transparent 96%); }
            a { color:inherit; text-decoration:none; }
            .ops-shell { display:grid; grid-template-columns:320px minmax(0,1fr); min-height:100vh; }
            .ops-sidebar { position:sticky; top:0; height:100vh; padding:1.25rem 1rem; border-right:1px solid rgba(104,240,255,.1); background:radial-gradient(circle at top left, rgba(104,240,255,.08), transparent 26%),radial-gradient(circle at bottom right, rgba(139,148,255,.12), transparent 28%),linear-gradient(180deg, rgba(4,10,24,.98), rgba(2,7,18,.98)); overflow-y:auto; }
            .ops-brand,.ops-nav-card,.ops-shortcuts,.ops-user-card,.ops-panel,.ops-hero,.ops-metric,.ops-status-card,.ops-list-item,.ops-mini-card,.ops-action-card { border:1px solid var(--ops-line); background:var(--ops-panel); box-shadow:var(--ops-shadow); }
            .ops-brand,.ops-nav-card,.ops-shortcuts,.ops-user-card { position:relative; overflow:hidden; border-radius:1.5rem; backdrop-filter:blur(16px); }
            .ops-brand { padding:1rem; background:linear-gradient(180deg, rgba(9,21,43,.94), rgba(5,13,28,.92)); }
            .ops-brand-mark { width:3rem; height:3rem; display:grid; place-items:center; border-radius:1rem; background:linear-gradient(135deg,var(--ops-cyan),var(--ops-emerald)); color:#04111e; font:800 .9rem/1 var(--ops-display); letter-spacing:.12em; text-transform:uppercase; }
            .ops-brand-head { display:flex; gap:.9rem; align-items:center; }
            .ops-brand-title { margin:0; font-family:var(--ops-display); font-size:1rem; letter-spacing:.08em; text-transform:uppercase; }
            .ops-brand-copy { margin:.25rem 0 0; color:var(--ops-muted); font-size:.82rem; line-height:1.6; }
            .ops-guild-box { margin-top:1rem; border-radius:1.2rem; border:1px solid rgba(104,240,255,.12); background:rgba(255,255,255,.04); padding:.95rem; }
            .ops-kicker,.ops-chip,.ops-nav-note,.ops-block-title,.ops-stat-label,.ops-list-label { font-family:var(--ops-mono); letter-spacing:.16em; text-transform:uppercase; }
            .ops-kicker { display:inline-flex; align-items:center; gap:.55rem; border-radius:999px; border:1px solid rgba(104,240,255,.14); background:rgba(255,255,255,.04); padding:.45rem .7rem; font-size:.64rem; font-weight:700; color:var(--ops-cyan); }
            .ops-kicker::before { content:""; width:.46rem; height:.46rem; border-radius:999px; background:var(--ops-emerald); box-shadow:0 0 14px rgba(118,255,184,.9); }
            .ops-guild-box strong { display:block; margin-top:.8rem; font-size:1rem; }
            .ops-guild-meta { margin-top:.35rem; color:var(--ops-muted); font-size:.7rem; font-weight:700; }
            .ops-chip { display:inline-flex; margin-top:.7rem; border-radius:999px; background:rgba(118,255,184,.12); color:var(--ops-emerald); padding:.36rem .6rem; font-size:.58rem; font-weight:700; }
            .ops-sidebar-stack { display:grid; gap:1rem; margin-top:1rem; }
            .ops-nav-card,.ops-shortcuts,.ops-user-card { padding:1rem; }
            .ops-block-title { margin:0 0 .8rem; font-size:.72rem; font-weight:700; color:var(--ops-muted); }
            .ops-nav-list { display:grid; gap:.45rem; }
            .ops-nav-link { display:block; border-radius:1rem; border:1px solid rgba(104,240,255,.08); background:rgba(255,255,255,.02); padding:.8rem .9rem; transition:transform .18s ease, border-color .18s ease, background .18s ease; }
            .ops-nav-link:hover,.ops-nav-link.is-active { transform:translateY(-2px); border-color:rgba(104,240,255,.26); background:linear-gradient(135deg, rgba(104,240,255,.14), rgba(111,134,255,.12)); }
            .ops-nav-link strong { display:block; font-size:.96rem; }
            .ops-nav-note { display:block; margin-top:.28rem; color:#6f86a9; font-size:.56rem; }
            .ops-user-card strong { display:block; font-size:.96rem; }
            .ops-user-card p { margin:.35rem 0 0; color:var(--ops-muted); font-size:.8rem; line-height:1.65; }
            .ops-logout { margin-top:.9rem; width:100%; border:0; border-radius:1rem; background:linear-gradient(135deg, rgba(104,240,255,.14), rgba(111,134,255,.12)); color:var(--ops-text); padding:.8rem 1rem; font-weight:700; cursor:pointer; }
            .ops-main { padding:1.5rem; }
            .ops-main-stack,.ops-left,.ops-right,.ops-list,.ops-action-grid { display:grid; gap:1.5rem; }
            .ops-hero,.ops-panel { position:relative; overflow:hidden; border-radius:2rem; padding:1.35rem; background:linear-gradient(180deg, rgba(9,21,43,.94), rgba(5,13,28,.92)); }
            .ops-hero::after,.ops-panel::after,.ops-status-card::after,.ops-mini-card::after,.ops-action-card::after { content:""; position:absolute; inset:0; background:radial-gradient(circle at top right, rgba(104,240,255,.16), transparent 34%); pointer-events:none; }
            .ops-hero { background:radial-gradient(circle at top right, rgba(104,240,255,.14), transparent 24%),radial-gradient(circle at bottom left, rgba(139,148,255,.16), transparent 28%),linear-gradient(135deg, rgba(7,18,40,.95), rgba(5,12,26,.96)); }
            .ops-hero-grid,.ops-grid,.ops-stats-grid,.ops-status-grid { display:grid; gap:1.2rem; }
            .ops-hero-grid { grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr); align-items:start; margin-top:1rem; }
            .ops-grid { grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr); }
            .ops-stats-grid { grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); }
            .ops-status-grid { grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); }
            .ops-title { margin:0; font-family:var(--ops-display); font-size:clamp(2.3rem,5vw,4.8rem); line-height:.92; letter-spacing:.08em; text-transform:uppercase; }
            .ops-title span { display:block; background:linear-gradient(90deg,var(--ops-cyan),#a1eeff,var(--ops-emerald)); -webkit-background-clip:text; background-clip:text; color:transparent; }
            .ops-copy,.ops-panel-copy,.ops-status-detail,.ops-list-item p,.ops-stat-copy,.ops-mini-card p,.ops-action-card p { color:var(--ops-muted); line-height:1.8; }
            .ops-copy { margin:1rem 0 0; max-width:46rem; font-size:.98rem; }
            .ops-meta { display:flex; flex-wrap:wrap; gap:.8rem; margin-top:1.35rem; }
            .ops-meta .ops-chip { margin-top:0; background:rgba(255,255,255,.04); color:#d7e6ff; }
            .ops-action-grid { align-content:start; }
            .ops-action-card,.ops-mini-card,.ops-status-card,.ops-list-item,.ops-metric { position:relative; overflow:hidden; border-radius:1.45rem; padding:1rem; background:rgba(3,11,24,.8); border:1px solid var(--ops-line); box-shadow:var(--ops-shadow); }
            .ops-action-card { text-decoration:none; transition:transform .18s ease, border-color .18s ease; }
            .ops-action-card:hover { transform:translateY(-3px); border-color:var(--ops-line-strong); }
            .ops-action-card strong,.ops-panel-header h2,.ops-list-item h3,.ops-mini-card h3 { color:var(--ops-text); }
            .ops-action-card strong,.ops-list-item h3,.ops-mini-card h3 { display:block; font-size:1rem; }
            .ops-action-card p,.ops-mini-card p { margin:.45rem 0 0; font-size:.84rem; }
            .ops-metric { background:linear-gradient(180deg, rgba(10,20,42,.92), rgba(5,13,28,.92)); }
            .ops-stat-label,.ops-panel-kicker,.ops-list-label { font-size:.68rem; color:var(--ops-muted); font-weight:700; }
            .ops-stat-value,.ops-status-value { font-family:var(--ops-display); letter-spacing:.07em; }
            .ops-stat-value { margin-top:.75rem; font-size:2.2rem; line-height:1; }
            .ops-stat-copy { margin-top:.55rem; font-size:.82rem; }
            .ops-panel-header,.ops-status-top,.ops-list-head,.ops-guild-row { display:flex; justify-content:space-between; gap:1rem; align-items:start; }
            .ops-panel-header { margin-bottom:1rem; }
            .ops-panel-header h2 { margin:.2rem 0 0; font-size:1.35rem; font-weight:700; }
            .ops-panel-copy { margin:.65rem 0 0; font-size:.88rem; }
            .ops-panel-kicker { display:block; color:var(--ops-cyan); }
            .ops-panel-pill,.ops-badge { border-radius:999px; padding:.46rem .72rem; font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
            .ops-panel-pill { border:1px solid rgba(104,240,255,.16); background:rgba(255,255,255,.05); color:#dfe9ff; }
            .ops-status-value { margin-top:.7rem; font-size:1.9rem; }
            .ops-status-detail { margin-top:.5rem; font-size:.8rem; }
            .ops-progress { margin-top:.85rem; height:.42rem; border-radius:999px; background:rgba(255,255,255,.06); overflow:hidden; }
            .ops-progress > span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--ops-cyan),var(--ops-emerald)); box-shadow:0 0 18px rgba(104,240,255,.35); }
            .ops-list-item p { margin:.55rem 0 0; font-size:.84rem; }
            .ops-badge-cyan { background:rgba(104,240,255,.12); color:var(--ops-cyan); }
            .ops-badge-emerald { background:rgba(118,255,184,.12); color:var(--ops-emerald); }
            .ops-badge-violet { background:rgba(139,148,255,.14); color:#bcc2ff; }
            .ops-badge-amber { background:rgba(255,191,111,.14); color:var(--ops-amber); }
            .ops-badge-rose { background:rgba(255,126,126,.14); color:#ff8d8d; }
            .ops-badge-zinc { background:rgba(255,255,255,.08); color:#dbe3f4; }
            .ops-list-label { margin-top:.7rem; display:inline-block; color:#adc2e8; }
            .ops-guild-row { padding:.8rem .9rem; border-radius:1rem; background:rgba(255,255,255,.04); color:var(--ops-muted); font-size:.83rem; }
            .ops-guild-row strong { color:var(--ops-text); }
            .ops-mobile-top { display:none; }
            @media (max-width:1100px) { .ops-shell,.ops-hero-grid,.ops-grid { grid-template-columns:1fr; } .ops-sidebar { display:none; } .ops-mobile-top { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem; } }
            @media (max-width:720px) { .ops-main { padding:1rem; } .ops-stats-grid,.ops-status-grid { grid-template-columns:1fr; } .ops-hero,.ops-panel { border-radius:1.5rem; padding:1rem; } }
        </style>
    </head>
    <body>
        <div class="ops-shell">
            <aside class="ops-sidebar">
                <div class="ops-brand">
                    <div class="ops-brand-head">
                        <div class="ops-brand-mark">LY</div>
                        <div>
                            <h1 class="ops-brand-title">LYVA Studio</h1>
                            <p class="ops-brand-copy">Control center Discord + Roblox untuk tim yang mau panel admin terasa seperti produk.</p>
                        </div>
                    </div>

                    <div class="ops-guild-box">
                        <span class="ops-kicker">Ops surface</span>
                        <strong>{{ $activeGuild['name'] ?? 'Belum pilih server' }}</strong>
                        <div class="ops-guild-meta">{{ $activeGuild['id'] ?? 'Guild belum dipilih' }}</div>
                        <span class="ops-chip">{{ $activeGuild ? 'Scoped dashboard' : 'Global mode' }}</span>
                    </div>
                </div>

                <div class="ops-sidebar-stack">
                    <section class="ops-nav-card">
                        <p class="ops-block-title">Platform</p>
                        <div class="ops-nav-list">
                            <a href="{{ route('dashboard') }}" class="ops-nav-link is-active">
                                <strong>Dashboard</strong>
                                <span class="ops-nav-note">Control center utama</span>
                            </a>
                            <a href="{{ route('guilds.select') }}" class="ops-nav-link">
                                <strong>Pilih Server</strong>
                                <span class="ops-nav-note">Scope panel per guild</span>
                            </a>
                            <a href="{{ route('discord.setup') }}" class="ops-nav-link">
                                <strong>Discord Setup</strong>
                                <span class="ops-nav-note">Webhook, OAuth, command sync</span>
                            </a>
                            <a href="{{ route('vip-title.setup') }}" class="ops-nav-link">
                                <strong>VIP Title Setup</strong>
                                <span class="ops-nav-note">Map key, API key, gamepass</span>
                            </a>
                            <a href="{{ route('roblox.scripts.index') }}" class="ops-nav-link">
                                <strong>Roblox Scripts</strong>
                                <span class="ops-nav-note">File siap tempel ke game</span>
                            </a>
                        </div>
                    </section>

                    <section class="ops-shortcuts">
                        <p class="ops-block-title">Shortcuts</p>
                        <div class="ops-nav-list">
                            <a href="{{ route('home') }}" class="ops-nav-link">
                                <strong>Landing Page</strong>
                                <span class="ops-nav-note">Kembali ke halaman utama</span>
                            </a>
                            <a href="https://create.roblox.com/docs" target="_blank" class="ops-nav-link">
                                <strong>Roblox Docs</strong>
                                <span class="ops-nav-note">Referensi resmi Roblox</span>
                            </a>
                        </div>
                    </section>

                    <section class="ops-user-card">
                        <p class="ops-block-title">Session</p>
                        <strong>{{ $currentUser->name }}</strong>
                        <p>{{ $currentUser->email }}</p>
                        @if ($activeGuild)
                            <p>Managing: {{ $activeGuild['name'] }}</p>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="ops-logout">Log out</button>
                        </form>
                    </section>
                </div>
            </aside>
            <main class="ops-main">
                <div class="ops-mobile-top">
                    <div>
                        <strong>{{ $activeGuild['name'] ?? 'Belum pilih server' }}</strong>
                        <div style="margin-top: 0.25rem; color: var(--ops-muted); font-size: 0.82rem;">{{ $activeGuild['id'] ?? 'Guild belum dipilih' }}</div>
                    </div>
                    <a href="{{ route('guilds.select') }}" class="ops-panel-pill">Ganti server</a>
                </div>

                <div class="ops-main-stack">
                    @unless ($hasBotTables)
                        <section class="ops-panel">
                            <div class="ops-panel-header">
                                <div>
                                    <span class="ops-panel-kicker">Fallback Mode</span>
                                    <h2>Tabel bot masih pakai data contoh</h2>
                                    <p class="ops-panel-copy">Jalankan <code>php artisan migrate</code> di VPS supaya panel ini membaca data operasional asli dari Discord, webhook, alerts, dan report queue.</p>
                                </div>
                                <span class="ops-panel-pill">Migration needed</span>
                            </div>
                        </section>
                    @endunless

                    <section class="ops-hero">
                        <span class="ops-kicker">Discord Control Surface</span>
                        <div class="ops-hero-grid">
                            <div>
                                <h2 class="ops-title">Dasbor<span>operasional server</span></h2>
                                <p class="ops-copy">Panel ini sekarang difokuskan buat jadi control room utama tim: status bot, alert operasional, webhook Discord, VIP title setup, dan surface kerja per server yang jauh lebih rapi dibanding dashboard lama.</p>
                                <div class="ops-meta">
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
                            <div class="ops-action-grid">
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
                                        <span class="ops-stat-label">{{ $stat['label'] }}</span>
                                        <div class="ops-stat-value">{{ str_pad((string) $stat['value'], 2, '0', STR_PAD_LEFT) }}</div>
                                        <p class="ops-stat-copy">{{ $stat['hint'] }}</p>
                                    </article>
                                @endforeach
                            </section>

                            <section class="ops-panel">
                                <div class="ops-panel-header">
                                    <div>
                                        <span class="ops-panel-kicker">Status Grid</span>
                                        <h2>Operational heartbeat</h2>
                                        <p class="ops-panel-copy">Ringkasan cepat buat tahu kondisi bot, queue, dan beban kerja tim tanpa perlu lompat ke halaman lain.</p>
                                    </div>
                                    <span class="ops-panel-pill">Live summary</span>
                                </div>
                                <div class="ops-status-grid">
                                    @foreach ($statusCards as $card)
                                        <article class="ops-status-card">
                                            <div class="ops-status-top">
                                                <span class="ops-stat-label">{{ $card['label'] }}</span>
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
                                        <span class="ops-panel-kicker">Race Desk</span>
                                        <h2>Community race events</h2>
                                        <p class="ops-panel-copy">Event balap komunitas yang lagi aktif, draft, atau masih buka registrasi untuk server yang kamu pilih.</p>
                                    </div>
                                    <span class="ops-panel-pill">Discord admin flow</span>
                                </div>
                                <div class="ops-list">
                                    @foreach ($races as $race)
                                        <article class="ops-list-item">
                                            <div class="ops-list-head">
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
                        </div>

                        <div class="ops-right">
                            <section class="ops-panel">
                                <div class="ops-panel-header">
                                    <div>
                                        <span class="ops-panel-kicker">Active Surface</span>
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
                                        <span class="ops-panel-kicker">Discord Delivery</span>
                                        <h2>Webhook health</h2>
                                    </div>
                                    <span class="ops-panel-pill">3 feeds</span>
                                </div>
                                <div class="ops-list">
                                    @foreach ($webhooks as $webhook)
                                        <article class="ops-list-item">
                                            <div class="ops-list-head">
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

                            <section class="ops-main-stack">
                                <article class="ops-mini-card">
                                    <span class="ops-panel-kicker">VIP title tooling</span>
                                    <h3>Setup map tanpa ribet</h3>
                                    <p>Panel VIP Title sekarang jadi jalur utama untuk generate config map, API key, gamepass mapping, dan snippet Roblox siap tempel.</p>
                                </article>
                                <article class="ops-mini-card">
                                    <span class="ops-panel-kicker">Next layer</span>
                                    <h3>Dashboard server-aware</h3>
                                    <p>Flow ini sudah siap dibawa ke level berikutnya: semua modul discope per guild biar user tinggal pilih server lalu manage semuanya dari satu tempat.</p>
                                </article>
                            </section>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
