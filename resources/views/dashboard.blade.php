@php
$title=__('Dashboard');$activeGuild=$managedGuild??null;$currentUser=auth()->user();$serverName=$activeGuild['name']??'Belum pilih server';$guildId=$activeGuild['id']??'-';
$quickLinks=[
['label'=>'Pilih Server','href'=>route('guilds.select'),'copy'=>'Ganti server Discord yang sedang kamu kelola dari panel ini.'],
['label'=>'VIP Title Setup','href'=>route('vip-title.setup'),'copy'=>'Atur map key, gamepass, API key, dan snippet Roblox dari dashboard.'],
['label'=>'Discord Setup','href'=>route('discord.setup'),'copy'=>'Rapikan command, webhook, dan koneksi bot per server.'],
['label'=>'Roblox Scripts','href'=>route('roblox.scripts.index'),'copy'=>'Ambil file Roblox yang siap tempel dan sinkron dengan backend.'],
];
$statusCards=[
['label'=>'Bot Routing','value'=>($stats[1]['value']??0)>0?'ONLINE':'IDLE','detail'=>($stats[1]['value']??0).' webhook aktif siap kirim alert.','progress'=>min(100,max(18,(($stats[1]['value']??0)*18)+22)),'tone'=>'cyan'],
['label'=>'Issue Queue','value'=>($stats[2]['value']??0)>0?'MONITOR':'CLEAR','detail'=>($stats[2]['value']??0).' insiden terbuka masih perlu tindakan.','progress'=>min(100,max(20,(($stats[2]['value']??0)*16)+18)),'tone'=>'amber'],
['label'=>'Reports Desk','value'=>($stats[3]['value']??0)>0?'ACTIVE':'QUIET','detail'=>($stats[3]['value']??0).' laporan player menunggu review.','progress'=>min(100,max(16,(($stats[3]['value']??0)*15)+20)),'tone'=>'emerald'],
];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
@include('partials.head')
<style>
:root{color-scheme:dark;--panel:rgba(7,16,34,.82);--line:rgba(104,240,255,.12);--strong:rgba(104,240,255,.28);--text:#f3f7ff;--muted:#95a6c9;--cyan:#68f0ff;--emerald:#76ffb8;--violet:#8b94ff;--amber:#ffbf6f;--shadow:0 30px 90px rgba(0,0,0,.38);--soft:0 16px 40px rgba(0,0,0,.26);--mono:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,monospace;--display:"Orbitron","Oxanium",ui-sans-serif,sans-serif}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 12% 8%,rgba(104,240,255,.1),transparent 24%),radial-gradient(circle at 84% 8%,rgba(139,148,255,.12),transparent 24%),linear-gradient(180deg,#020612 0%,#020815 100%);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,sans-serif}body:before{content:"";position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:44px 44px;opacity:.14;mask-image:linear-gradient(180deg,rgba(0,0,0,.9),transparent 96%)}a{text-decoration:none;color:inherit}.shell{display:grid;grid-template-columns:332px minmax(0,1fr);min-height:100vh}.sidebar{position:sticky;top:0;height:100vh;padding:1.25rem 1rem;border-right:1px solid rgba(104,240,255,.1);background:radial-gradient(circle at top left,rgba(104,240,255,.08),transparent 26%),radial-gradient(circle at bottom right,rgba(139,148,255,.12),transparent 28%),linear-gradient(180deg,rgba(4,10,24,.98),rgba(2,7,18,.98));overflow-y:auto}.main{padding:1.45rem 1.65rem}.stack,.list,.actions,.right,.left{display:grid;gap:1.5rem}.card,.hero,.panel,.brand,.navcard,.usercard,.shortcut{border:1px solid var(--line);background:var(--panel);box-shadow:var(--shadow);position:relative;overflow:hidden}.brand,.navcard,.usercard,.shortcut,.card,.panel,.hero{border-radius:1.6rem}.brand,.navcard,.usercard,.shortcut{padding:1rem}.hero,.panel{padding:1.4rem}.hero{min-height:540px;background:radial-gradient(circle at top right,rgba(104,240,255,.14),transparent 24%),radial-gradient(circle at bottom left,rgba(139,148,255,.16),transparent 28%),linear-gradient(135deg,rgba(7,18,40,.95),rgba(5,12,26,.96))}.card:after,.panel:after,.hero:after,.brand:after,.navcard:after,.usercard:after,.shortcut:after{content:"";position:absolute;inset:0;background:radial-gradient(circle at top right,rgba(104,240,255,.16),transparent 34%);pointer-events:none}.brandhead{display:flex;gap:.9rem;align-items:center}.mark{width:3rem;height:3rem;display:grid;place-items:center;border-radius:1rem;background:linear-gradient(135deg,var(--cyan),var(--emerald));color:#04111e;font:800 .9rem/1 var(--display);letter-spacing:.12em;text-transform:uppercase}.brand h1,.title,.panel h2,.spotlight h3{margin:0;font-family:var(--display);text-transform:uppercase;letter-spacing:.08em}.brand p,.copy,.muted,.panel p,.card p{color:var(--muted);line-height:1.8}.guildbox{margin-top:1rem;border-radius:1.25rem;border:1px solid rgba(104,240,255,.12);background:linear-gradient(135deg,rgba(255,255,255,.05),rgba(255,255,255,.02));padding:1rem}.guildbox strong{display:block;margin-top:.8rem;font-size:1rem}.guildmeta{margin-top:.35rem;color:var(--muted);font-size:.7rem;font-weight:700}.kicker,.chip,.block,.statlabel,.listlabel,.navnote{font-family:var(--mono);letter-spacing:.16em;text-transform:uppercase}.kicker{display:inline-flex;align-items:center;gap:.55rem;border-radius:999px;border:1px solid rgba(104,240,255,.14);background:rgba(255,255,255,.04);padding:.45rem .7rem;font-size:.64rem;font-weight:700;color:var(--cyan)}.kicker:before{content:"";width:.46rem;height:.46rem;border-radius:999px;background:var(--emerald);box-shadow:0 0 14px rgba(118,255,184,.9)}.chip{display:inline-flex;margin-top:.7rem;border-radius:999px;background:rgba(118,255,184,.12);color:var(--emerald);padding:.36rem .6rem;font-size:.58rem;font-weight:700}.block{margin:0 0 .8rem;font-size:.72rem;font-weight:700;color:var(--muted)}.navlist{display:grid;gap:.55rem}.navlink{display:block;border-radius:1rem;border:1px solid rgba(104,240,255,.08);background:rgba(255,255,255,.02);padding:.92rem 1rem;transition:.2s transform,.2s border-color,.2s background,.2s box-shadow}.navlink:hover,.navlink.active{transform:translateY(-3px);border-color:rgba(104,240,255,.26);background:linear-gradient(135deg,rgba(104,240,255,.14),rgba(111,134,255,.12));box-shadow:var(--soft)}.navlink strong{display:block;font-size:1rem}.navnote{display:block;margin-top:.34rem;color:#6f86a9;font-size:.56rem}.logout{margin-top:.9rem;width:100%;border:0;border-radius:1rem;background:linear-gradient(135deg,rgba(104,240,255,.14),rgba(111,134,255,.12));color:var(--text);padding:.86rem 1rem;font-weight:700;cursor:pointer}.mobiletop{display:none}.heroGrid,.grid,.stats,.status,.orbitgrid{display:grid;gap:1.2rem}.heroGrid{grid-template-columns:minmax(0,1.06fr) minmax(360px,.94fr);align-items:stretch;min-height:420px;margin-top:1rem}.grid{grid-template-columns:minmax(0,1.15fr) minmax(360px,.9fr)}.stats{grid-template-columns:repeat(auto-fit,minmax(190px,1fr))}.status{grid-template-columns:repeat(auto-fit,minmax(190px,1fr))}.orbitgrid{grid-template-columns:repeat(2,minmax(0,1fr))}.title{font-size:clamp(2.8rem,5.5vw,5.2rem);line-height:.9}.title span{display:block;background:linear-gradient(90deg,var(--cyan),#a1eeff,var(--emerald));-webkit-background-clip:text;background-clip:text;color:transparent}.copy{margin:1rem 0 0;max-width:46rem;font-size:1rem}.meta{display:flex;flex-wrap:wrap;gap:.8rem;margin-top:1.35rem}.meta .chip{margin-top:0;background:rgba(255,255,255,.04);color:#d7e6ff}.spotlight{display:grid;gap:1rem;align-content:start;background:linear-gradient(180deg,rgba(8,20,42,.98),rgba(6,14,31,.95));border-radius:2rem;padding:1.4rem;border:1px solid var(--line);box-shadow:var(--shadow)}.spothead{display:flex;justify-content:space-between;gap:1rem;align-items:start}.spotlight h3{font-size:1.55rem}.pill,.badge{border-radius:999px;padding:.46rem .72rem;font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.pill{border:1px solid rgba(104,240,255,.16);background:rgba(255,255,255,.05);color:#dfe9ff}.focus{display:grid;gap:1rem;border-radius:1.55rem;padding:1rem;border:1px solid rgba(104,240,255,.12);background:linear-gradient(145deg,rgba(255,255,255,.05),rgba(255,255,255,.02))}.focustop{display:flex;justify-content:space-between;gap:1rem;align-items:center}.core{display:grid;grid-template-columns:96px minmax(0,1fr);gap:1rem;align-items:center}.orbit{position:relative;width:96px;height:96px;border-radius:999px;border:1px solid rgba(104,240,255,.2);background:radial-gradient(circle at center,rgba(104,240,255,.22),rgba(104,240,255,.04) 42%,transparent 43%);box-shadow:inset 0 0 30px rgba(104,240,255,.12)}.orbit:before,.orbit:after{content:"";position:absolute;border-radius:999px;inset:10px;border:1px solid rgba(118,255,184,.16)}.orbit:after{inset:28px;border-color:rgba(139,148,255,.18)}.orbitdot{position:absolute;top:12px;right:16px;width:12px;height:12px;border-radius:999px;background:var(--emerald);box-shadow:0 0 18px rgba(118,255,184,.9)}.servername{font-family:var(--display);font-size:1.26rem;letter-spacing:.06em;text-transform:uppercase}.serverid{margin-top:.45rem;color:var(--muted);font:700 .74rem/1.8 var(--mono);letter-spacing:.08em}.serverstrip{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.8rem}.serverstat{border-radius:1.2rem;padding:.95rem;border:1px solid rgba(104,240,255,.08);background:rgba(255,255,255,.03)}.serverstat span{display:block;color:var(--muted);font:700 .62rem/1 var(--mono);text-transform:uppercase;letter-spacing:.14em}.serverstat strong{display:block;margin-top:.55rem;font-size:1rem}.actions{grid-template-columns:repeat(2,minmax(0,1fr));align-content:start}.card{min-height:152px;display:flex;flex-direction:column;justify-content:space-between;padding:1rem;transition:.2s transform,.2s border-color,.2s box-shadow}.card:hover{transform:translateY(-4px) scale(1.01);border-color:var(--strong);box-shadow:0 20px 48px rgba(0,0,0,.36)}.card:before{content:"Open";align-self:flex-start;border-radius:999px;border:1px solid rgba(104,240,255,.14);padding:.38rem .62rem;color:var(--cyan);font:700 .62rem/1 var(--mono);letter-spacing:.12em;text-transform:uppercase;background:rgba(255,255,255,.03)}.metric{min-height:190px;background:linear-gradient(180deg,rgba(10,20,42,.92),rgba(5,13,28,.92));padding:1rem}.statlabel,.panelkick,.listlabel{font-size:.68rem;color:var(--muted);font-weight:700}.statvalue,.statusvalue{font-family:var(--display);letter-spacing:.07em}.statvalue{margin-top:.75rem;font-size:2.35rem;line-height:1}.statcopy{margin-top:.55rem;font-size:.82rem;color:var(--muted);line-height:1.8}.panelhead,.statusTop,.listhead,.guildrow{display:flex;justify-content:space-between;gap:1rem;align-items:start}.panelhead{margin-bottom:1rem}.panel h2{margin:.2rem 0 0;font-size:1.42rem}.panelcopy{margin:.65rem 0 0;font-size:.88rem;color:var(--muted);line-height:1.8}.panelkick{display:block;color:var(--cyan)}.statuscard{min-height:210px;padding:1rem}.statusvalue{margin-top:.7rem;font-size:1.9rem}.statusdetail{margin-top:.5rem;font-size:.8rem;color:var(--muted);line-height:1.8}.progress{margin-top:.85rem;height:.42rem;border-radius:999px;background:rgba(255,255,255,.06);overflow:hidden}.progress span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--cyan),var(--emerald));box-shadow:0 0 18px rgba(104,240,255,.35)}.listitem{min-height:154px;padding:1rem}.listitem p{margin:.55rem 0 0;font-size:.84rem;color:var(--muted)}.badge.cyan{background:rgba(104,240,255,.12);color:var(--cyan)}.badge.emerald{background:rgba(118,255,184,.12);color:var(--emerald)}.badge.violet{background:rgba(139,148,255,.14);color:#bcc2ff}.badge.amber{background:rgba(255,191,111,.14);color:var(--amber)}.badge.zinc{background:rgba(255,255,255,.08);color:#dbe3f4}.listlabel{margin-top:.7rem;display:inline-block;color:#adc2e8}.guildrows{display:grid;gap:.85rem}.guildrow{padding:.95rem 1rem;border-radius:1rem;background:rgba(255,255,255,.04);color:var(--muted);font-size:.83rem}.guildrow strong{color:var(--text)}.mini{min-height:160px;padding:1rem;background:linear-gradient(180deg,rgba(10,20,42,.92),rgba(5,13,28,.92));border:1px solid var(--line);border-radius:1.45rem;box-shadow:var(--shadow);position:relative;overflow:hidden}.mini:after{content:"";position:absolute;inset:0;background:radial-gradient(circle at top right,rgba(104,240,255,.16),transparent 34%);pointer-events:none}.mini p{margin:.55rem 0 0;color:var(--muted);line-height:1.8}.mini h3{margin:.15rem 0 0;font-size:1rem}.mini .panelkick{margin-bottom:.35rem}@media (max-width:1100px){.shell,.heroGrid,.grid{grid-template-columns:1fr}.sidebar{display:none}.mobiletop{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}.actions,.orbitgrid,.serverstrip{grid-template-columns:1fr}.hero{min-height:auto}.core{grid-template-columns:1fr}.orbit{margin:0 auto}}@media (max-width:720px){.main{padding:1rem}.stats,.status{grid-template-columns:1fr}.hero,.panel,.spotlight{border-radius:1.5rem;padding:1rem}}
</style>
</head>
<body>
<div class="shell">
<aside class="sidebar">
    <div class="brand">
        <div class="brandhead">
            <div class="mark">LY</div>
            <div>
                <h1 class="brandtitle">LYVA Studio</h1>
                <p class="brandcopy">Control center Discord + Roblox untuk tim yang mau panel admin terasa seperti produk.</p>
            </div>
        </div>
        <div class="guildbox">
            <span class="kicker">Ops surface</span>
            <strong>{{ $serverName }}</strong>
            <div class="guildmeta">{{ $guildId }}</div>
            <span class="chip">{{ $activeGuild ? 'Scoped dashboard' : 'Global mode' }}</span>
        </div>
    </div>
    <div class="stack">
        <section class="navcard">
            <p class="block">Platform</p>
            <div class="navlist">
                <a href="{{ route('dashboard') }}" class="navlink active"><strong>Dashboard</strong><span class="navnote">Control center utama</span></a>
                <a href="{{ route('guilds.select') }}" class="navlink"><strong>Pilih Server</strong><span class="navnote">Scope panel per guild</span></a>
                <a href="{{ route('discord.setup') }}" class="navlink"><strong>Discord Setup</strong><span class="navnote">Webhook, OAuth, command sync</span></a>
                <a href="{{ route('vip-title.setup') }}" class="navlink"><strong>VIP Title Setup</strong><span class="navnote">Map key, API key, gamepass</span></a>
                <a href="{{ route('roblox.scripts.index') }}" class="navlink"><strong>Roblox Scripts</strong><span class="navnote">File siap tempel ke game</span></a>
            </div>
        </section>
        <section class="shortcut">
            <p class="block">Shortcuts</p>
            <div class="navlist">
                <a href="{{ route('home') }}" class="navlink"><strong>Landing Page</strong><span class="navnote">Kembali ke halaman utama</span></a>
                <a href="https://create.roblox.com/docs" target="_blank" class="navlink"><strong>Roblox Docs</strong><span class="navnote">Referensi resmi Roblox</span></a>
            </div>
        </section>
        <section class="usercard">
            <p class="block">Session</p>
            <strong>{{ $currentUser->name }}</strong>
            <p>{{ $currentUser->email }}</p>
            @if ($activeGuild)<p>Managing: {{ $activeGuild['name'] }}</p>@endif
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout">Log out</button></form>
        </section>
    </div>
</aside>
<main class="main">
    <div class="mobiletop">
        <div><strong>{{ $serverName }}</strong><div class="muted" style="margin-top:.25rem;font-size:.82rem;">{{ $guildId }}</div></div>
        <a href="{{ route('guilds.select') }}" class="pill">Ganti server</a>
    </div>
    <div class="stack">
        @unless ($hasBotTables)
        <section class="panel">
            <div class="panelhead">
                <div>
                    <span class="panelkick">Fallback Mode</span>
                    <h2>Tabel bot masih pakai data contoh</h2>
                    <p class="panelcopy">Jalankan <code>php artisan migrate</code> di VPS supaya panel ini membaca data operasional asli dari Discord, webhook, alerts, dan report queue.</p>
                </div>
                <span class="pill">Migration needed</span>
            </div>
        </section>
        @endunless
        <section class="hero">
            <span class="kicker">Discord Control Surface</span>
            <div class="heroGrid">
                <div>
                    <h2 class="title">Dasbor<span>operasional server</span></h2>
                    <p class="copy">Panel ini sekarang difokuskan buat jadi control room utama tim: status bot, alert operasional, webhook Discord, VIP title setup, dan surface kerja per server yang jauh lebih rapi dibanding dashboard lama.</p>
                    <div class="meta">
                        @if ($activeGuild)
                        <span class="chip">Server aktif: {{ $serverName }}</span>
                        <span class="chip">Guild ID {{ $guildId }}</span>
                        @else
                        <span class="chip">Belum pilih server aktif</span>
                        @endif
                        <span class="chip">{{ count($stats) }} modul dipantau</span>
                        <span class="chip">Laravel + Discord sync</span>
                    </div>
                </div>
                <section class="spotlight">
                    <div class="spothead">
                        <div>
                            <span class="panelkick">Server spotlight</span>
                            <h3>Interactive ops deck</h3>
                            <p class="ops-command-copy">Panel kanan sekarang dibuat jadi pusat aksi cepat buat pindah server, buka setup, dan lihat identitas guild aktif tanpa terasa datar atau gepeng.</p>
                        </div>
                        <a href="{{ route('guilds.select') }}" class="pill">Ganti server</a>
                    </div>
                    <div class="focus">
                        <div class="focustop">
                            <span class="badge emerald">{{ $activeGuild ? 'Scoped dashboard' : 'Global mode' }}</span>
                            <span class="badge violet">Ready</span>
                        </div>
                        <div class="core">
                            <div class="orbit"><span class="orbitdot"></span></div>
                            <div>
                                <div class="servername">{{ $serverName }}</div>
                                <div class="serverid">Guild ID · {{ $guildId }}</div>
                            </div>
                        </div>
                        <div class="serverstrip">
                            <div class="serverstat"><span>Scope</span><strong>{{ $activeGuild ? 'Locked' : 'Unset' }}</strong></div>
                            <div class="serverstat"><span>Modules</span><strong>{{ count($stats) }}</strong></div>
                            <div class="serverstat"><span>Status</span><strong>Synced</strong></div>
                        </div>
                    </div>
                    <div class="actions">
                        @foreach ($quickLinks as $link)
                        <a href="{{ $link['href'] }}" class="card"><strong>{{ $link['label'] }}</strong><p>{{ $link['copy'] }}</p></a>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>
        <div class="grid">
            <div class="left">
                <section class="stats">
                    @foreach ($stats as $stat)
                    <article class="metric"><span class="statlabel">{{ $stat['label'] }}</span><div class="statvalue">{{ str_pad((string) $stat['value'], 2, '0', STR_PAD_LEFT) }}</div><p class="statcopy">{{ $stat['hint'] }}</p></article>
                    @endforeach
                </section>
                <section class="panel">
                    <div class="panelhead">
                        <div><span class="panelkick">Status Grid</span><h2>Operational heartbeat</h2><p class="panelcopy">Ringkasan cepat buat tahu kondisi bot, queue, dan beban kerja tim tanpa perlu lompat ke halaman lain.</p></div>
                        <span class="pill">Live summary</span>
                    </div>
                    <div class="status">
                        @foreach ($statusCards as $card)
                        <article class="statuscard">
                            <div class="statusTop"><span class="statlabel">{{ $card['label'] }}</span><span class="badge {{ $card['tone'] }}">{{ $card['tone'] }}</span></div>
                            <div class="statusvalue">{{ $card['value'] }}</div>
                            <p class="statusdetail">{{ $card['detail'] }}</p>
                            <div class="progress"><span style="width: {{ $card['progress'] }}%;"></span></div>
                        </article>
                        @endforeach
                    </div>
                </section>
                <section class="panel">
                    <div class="panelhead">
                        <div><span class="panelkick">Race Desk</span><h2>Community race events</h2><p class="panelcopy">Event balap komunitas yang lagi aktif, draft, atau masih buka registrasi untuk server yang kamu pilih.</p></div>
                        <span class="pill">Discord admin flow</span>
                    </div>
                    <div class="list">
                        @foreach ($races as $race)
                        <article class="listitem">
                            <div class="listhead">
                                <div><h3>#{{ $race->id }} {{ $race->title }}</h3><p>{{ $race->participants_count }}/{{ $race->max_players }} player · Entry {{ $race->entry_fee_robux }} R$</p></div>
                                <span class="badge cyan">{{ str_replace('_', ' ', ucfirst($race->status)) }}</span>
                            </div>
                            <span class="listlabel">Race queue</span>
                        </article>
                        @endforeach
                    </div>
                </section>
            </div>
            <div class="right">
                <section class="panel">
                    <div class="panelhead">
                        <div><span class="panelkick">Active Surface</span><h2>Server focus</h2><p class="panelcopy">Semua automation dan panel setup nanti mengacu ke server Discord yang dipilih di sini.</p></div>
                        <a href="{{ route('guilds.select') }}" class="pill">Ganti server</a>
                    </div>
                    <div class="guildrows">
                        <div class="guildrow"><span>Server</span><strong>{{ $serverName }}</strong></div>
                        <div class="guildrow"><span>Guild ID</span><strong>{{ $guildId }}</strong></div>
                        <div class="guildrow"><span>Status panel</span><strong>{{ $activeGuild ? 'Scoped' : 'Global' }}</strong></div>
                    </div>
                </section>
                <section class="panel">
                    <div class="panelhead">
                        <div><span class="panelkick">Discord Delivery</span><h2>Webhook health</h2></div>
                        <span class="pill">3 feeds</span>
                    </div>
                    <div class="list">
                        @foreach ($webhooks as $webhook)
                        <article class="listitem">
                            <div class="listhead">
                                <div><h3>{{ $webhook->name }}</h3><p>{{ $webhook->channel_name }}</p></div>
                                <span class="badge {{ $webhook->is_active ? 'emerald' : 'zinc' }}">{{ $webhook->is_active ? 'Active' : 'Paused' }}</span>
                            </div>
                            <span class="listlabel">Last delivery {{ optional($webhook->last_delivered_at)->diffForHumans() ?? 'belum pernah kirim' }}</span>
                        </article>
                        @endforeach
                    </div>
                </section>
                <section class="panel">
                    <div class="panelhead">
                        <div><span class="panelkick">Ops forecast</span><h2>Next action stack</h2><p class="panelcopy">Blok ini sengaja dibuat lebih tinggi dan berlapis supaya sisi kanan tidak terasa kosong dan gepeng.</p></div>
                        <span class="pill">Queue view</span>
                    </div>
                    <div class="orbitgrid">
                        <article class="mini"><span class="panelkick">VIP title tooling</span><h3>Setup map tanpa ribet</h3><p>Panel VIP Title sekarang jadi jalur utama untuk generate config map, API key, gamepass mapping, dan snippet Roblox siap tempel.</p></article>
                        <article class="mini"><span class="panelkick">Next layer</span><h3>Dashboard server-aware</h3><p>Flow ini sudah siap dibawa ke level berikutnya: semua modul discope per guild biar user tinggal pilih server lalu manage semuanya dari satu tempat.</p></article>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
</div>
</body>
</html>
