<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pilih Server Discord - LYVA Studio</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&family=Orbitron:wght@500;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css'])
        <style>
            :root{color-scheme:dark;--bg:#03050d;--panel:rgba(8,14,29,.84);--line:rgba(118,224,255,.14);--text:#f4f7ff;--muted:#9eacc8;--primary:#68f0ff;--secondary:#6f86ff;--accent:#7cffb2;--shadow:0 30px 80px rgba(0,0,0,.45);--mono:'JetBrains Mono',monospace;--display:'Orbitron',sans-serif;--body:'Inter',sans-serif}
            *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:var(--body);color:var(--text);background:radial-gradient(circle at 18% 16%,rgba(88,114,255,.1),transparent 26%),radial-gradient(circle at 84% 12%,rgba(104,240,255,.08),transparent 24%),linear-gradient(180deg,#02040a 0%,#040916 42%,#03050d 100%)}a{text-decoration:none;color:inherit}
            body::before{content:"";position:fixed;inset:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:46px 46px;opacity:.16;mask-image:linear-gradient(180deg,rgba(0,0,0,.92),transparent 96%)}
            .shell{width:min(1220px,calc(100% - 40px));margin:0 auto}
            .topbar{padding:18px 0 10px}
            .nav{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:14px 18px;border-radius:24px;background:rgba(5,10,24,.92);border:1px solid rgba(104,240,255,.1)}
            .brand{display:flex;align-items:center;gap:14px}.mark{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;font:800 12px var(--display);letter-spacing:.08em;color:#04111e;background:linear-gradient(135deg,var(--primary),var(--accent))}.brand strong{display:block;font:800 12px/1.1 var(--display);letter-spacing:.12em;text-transform:uppercase}.brand span{display:block;margin-top:3px;color:var(--muted);font-size:11px}
            .nav-actions{display:flex;gap:10px;flex-wrap:wrap}.pill{padding:9px 14px;border-radius:999px;border:1px solid rgba(104,240,255,.14);background:rgba(11,19,38,.52);color:var(--muted);font-size:12px;font-weight:600}.pill.primary{color:#04111e;background:linear-gradient(135deg,var(--primary),var(--secondary));border-color:transparent}
            .page{padding:18px 0 50px}
            .head{display:flex;justify-content:space-between;align-items:end;gap:18px;margin-bottom:22px}.head small{display:block;color:var(--primary);font:700 12px var(--mono);letter-spacing:.16em;text-transform:uppercase;margin-bottom:10px}.head h1{margin:0;font:800 clamp(1.8rem,4vw,3rem)/1.05 var(--display);letter-spacing:.06em;text-transform:uppercase}.head p{max-width:700px;margin:10px 0 0;color:var(--muted);line-height:1.8}
            .summary{display:flex;gap:12px;flex-wrap:wrap}.chip{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:999px;background:rgba(104,240,255,.08);border:1px solid rgba(104,240,255,.14);color:var(--primary);font:700 11px var(--mono);letter-spacing:.08em;text-transform:uppercase}
            .guild-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
            .guild-card{padding:20px;border-radius:24px;background:linear-gradient(180deg,rgba(8,14,29,.9),rgba(6,11,23,.9));border:1px solid rgba(104,240,255,.08);box-shadow:var(--shadow)}
            .guild-top{display:flex;align-items:center;gap:14px}.guild-icon{width:62px;height:62px;border-radius:18px;display:grid;place-items:center;background:linear-gradient(135deg,rgba(104,240,255,.2),rgba(111,134,255,.24));border:1px solid rgba(104,240,255,.14);overflow:hidden}.guild-icon img{width:100%;height:100%;object-fit:cover}.guild-icon span{font:800 22px var(--display);color:#dffaff}
            .guild-name strong{display:block;font:800 20px/1.1 var(--display);letter-spacing:.04em}.guild-name small{display:block;margin-top:6px;color:var(--muted);font:700 11px var(--mono);word-break:break-all}
            .guild-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}.badge{display:inline-flex;align-items:center;gap:8px;padding:9px 11px;border-radius:999px;background:rgba(104,240,255,.08);border:1px solid rgba(104,240,255,.16);color:var(--primary);font:700 10px var(--mono);letter-spacing:.08em;text-transform:uppercase}.badge.owner{background:rgba(124,255,178,.09);border-color:rgba(124,255,178,.22);color:var(--accent)}
            .guild-copy{margin-top:16px;color:var(--muted);font-size:14px;line-height:1.75}
            .guild-actions{margin-top:18px}.cta{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:15px 18px;border-radius:16px;font-size:14px;font-weight:700;border:1px solid rgba(104,240,255,.16);background:rgba(11,19,38,.58);color:var(--text);cursor:pointer}.cta.primary{color:#04111e;background:linear-gradient(135deg,var(--primary),var(--secondary));border-color:transparent}
            .empty{padding:32px;border-radius:24px;background:var(--panel);border:1px dashed rgba(104,240,255,.18);color:var(--muted);text-align:center;line-height:1.8}
            @media (max-width:780px){.nav,.head{flex-direction:column;align-items:flex-start}.shell{width:min(100% - 24px,1220px)}}
        </style>
    </head>
    <body>
        <header class="topbar">
            <div class="shell">
                <div class="nav">
                    <a href="{{ route('home') }}" class="brand">
                        <div class="mark">LY</div>
                        <div>
                            <strong>LYVA Studio</strong>
                            <span>Pilih server Discord yang benar-benar aktif untuk bot</span>
                        </div>
                    </a>
                    <div class="nav-actions">
                        <a href="{{ route('home') }}" class="pill">Kembali</a>
                        <a href="{{ route('dashboard') }}" class="pill primary">Dashboard</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="page">
            <div class="shell">
                <div class="head">
                    <div>
                        <small>Guild Picker</small>
                        <h1>Pilih Server</h1>
                        <p>Yang tampil di bawah adalah server yang kamu punya akses kelola. Server dengan badge <strong>Bot Joined</strong> sudah siap langsung dibuka dashboard-nya.</p>
                    </div>
                    <div class="summary">
                        <span class="chip">{{ count($guilds) }} server terdeteksi</span>
                        <span class="chip">{{ count($joinedGuilds) }} server siap dikelola</span>
                    </div>
                </div>

                <div class="guild-grid">
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
                                <span class="badge">Manageable</span>
                                <span class="badge {{ $guild['bot_joined'] ? '' : 'owner' }}">{{ $guild['bot_joined'] ? 'Bot Joined' : 'Bot Missing' }}</span>
                                @if ($guild['owner'])
                                    <span class="badge owner">Owner</span>
                                @endif
                            </div>

                            <p class="guild-copy">
                                @if ($guild['bot_joined'])
                                    Pilih server ini untuk membuka dashboard, setup Discord bot, VIP title tools, dan workflow Roblox yang terkait langsung dengan guild ini.
                                @else
                                    Kamu punya akses kelola di server ini, tapi bot LYVA belum terdeteksi masuk. Masukkan bot dulu supaya server ini bisa dipilih dari dashboard.
                                @endif
                            </p>

                            <div class="guild-actions">
                                <button class="cta {{ $guild['bot_joined'] ? 'primary' : '' }}" type="submit" @disabled(! $guild['bot_joined'])>
                                    {{ $guild['bot_joined'] ? 'Kelola server ini' : 'Bot belum masuk' }}
                                </button>
                            </div>
                        </form>
                    @empty
                        <div class="empty">
                            Belum ada server yang bisa kamu kelola dengan akun Discord ini. Pastikan akunmu punya akses Manage Server atau Administrator di server tujuan.
                        </div>
                    @endforelse
                </div>

                @if (count($guilds) > 0 && count($joinedGuilds) === 0)
                    <div class="empty" style="margin-top:16px;">
                        Server kamu terdeteksi, tapi bot LYVA belum ada di server-server itu. Invite bot ke server yang kamu kelola, lalu login ulang Discord supaya statusnya ikut ter-refresh.
                    </div>
                @endif
            </div>
        </main>
    </body>
</html>
