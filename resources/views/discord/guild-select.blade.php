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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&family=Orbitron:wght@500;700;800&family=Oxanium:wght@500;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css'])
        <style>
            :root{color-scheme:dark;--bg:#03050d;--panel:rgba(8,14,29,.8);--line:rgba(118,224,255,.14);--text:#f4f7ff;--muted:#9eacc8;--primary:#68f0ff;--secondary:#6f86ff;--accent:#7cffb2;--shadow:0 30px 80px rgba(0,0,0,.55);--mono:'JetBrains Mono',monospace;--display:'Orbitron',sans-serif;--display-alt:'Oxanium',sans-serif;--body:'Inter',sans-serif}
            *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;min-height:100vh;font-family:var(--body);color:var(--text);overflow-x:hidden;background:radial-gradient(circle at 18% 16%,rgba(88,114,255,.1),transparent 26%),radial-gradient(circle at 84% 12%,rgba(104,240,255,.08),transparent 24%),linear-gradient(180deg,#02040a 0%,#040916 42%,#03050d 100%)}a{text-decoration:none;color:inherit}.page{position:relative;isolation:isolate;min-height:100vh}.shell{width:min(1380px,calc(100% - 56px));margin:0 auto}
            .grid::before,.grid::after{content:"";position:fixed;inset:0;pointer-events:none}.grid::before{background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:48px 48px;opacity:.24;mask-image:linear-gradient(180deg,rgba(0,0,0,.92),transparent 96%)}.grid::after{height:140px;background:linear-gradient(180deg,transparent,rgba(255,255,255,.05),transparent);animation:scan 6s linear infinite}@keyframes scan{0%{transform:translateY(-150px)}100%{transform:translateY(calc(100vh + 150px))}}
            .kana-rain{position:fixed;inset:0;z-index:-1;overflow:hidden;pointer-events:none;opacity:.22}.kana-stream{position:absolute;top:-20vh;display:flex;flex-direction:column;gap:10px;color:rgba(244,247,255,.28);font:700 17px/1 var(--mono);text-shadow:0 0 10px rgba(255,255,255,.12);animation:kanaFall linear infinite}@keyframes kanaFall{0%{transform:translateY(-25vh)}100%{transform:translateY(125vh)}}
            .header{position:sticky;top:0;z-index:40;padding-top:10px}.nav{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:14px 22px;border-radius:24px;background:rgba(5,10,24,.92);border:1px solid rgba(104,240,255,.1)}.brand{display:flex;align-items:center;gap:14px}.mark{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;font:800 12px var(--display);letter-spacing:.08em;color:#04111e;background:linear-gradient(135deg,var(--primary),var(--accent))}.brand strong{display:block;font:800 12px/1.1 var(--display);letter-spacing:.12em;text-transform:uppercase}.brand span{display:block;margin-top:3px;color:var(--muted);font-size:11px}
            .nav-actions{display:flex;gap:10px;flex-wrap:wrap}.pill{padding:9px 14px;border-radius:999px;border:1px solid rgba(104,240,255,.14);background:rgba(11,19,38,.52);color:var(--muted);font-size:12px;font-weight:600}.pill.primary{color:#04111e;background:linear-gradient(135deg,var(--primary),var(--secondary));border-color:transparent}
            .hero{padding:28px 0 34px}.hero-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.88fr);gap:28px;align-items:start}.eyebrow{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;border-radius:999px;border:1px solid rgba(104,240,255,.18);background:rgba(11,19,38,.62);color:var(--primary);font:700 12px var(--mono);letter-spacing:.12em;text-transform:uppercase}.eyebrow::before{content:"";width:8px;height:8px;border-radius:999px;background:var(--accent);box-shadow:0 0 16px rgba(124,255,178,.8)}h1{margin:14px 0 14px;font:800 clamp(3rem,8vw,5.8rem)/.9 var(--display-alt);letter-spacing:.08em;text-transform:uppercase}.glow{background:linear-gradient(90deg,var(--primary),#8be8ff,var(--accent));-webkit-background-clip:text;background-clip:text;color:transparent}.lead{max-width:720px;margin:0;color:var(--muted);font-size:17px;line-height:1.85}
            .stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:24px}.stat{padding:18px;border-radius:24px;background:var(--panel);border:1px solid var(--line);box-shadow:var(--shadow)}.stat strong{display:block;font:800 28px var(--display);letter-spacing:.06em}.stat span{display:block;margin-top:6px;color:var(--muted);font-size:13px}
            .monitor{padding:22px;border-radius:30px;background:linear-gradient(180deg,rgba(13,20,42,.94),rgba(9,16,34,.94));border:1px solid rgba(104,240,255,.1);box-shadow:0 0 0 1px rgba(104,240,255,.08),0 0 36px rgba(104,240,255,.14),var(--shadow)}.monitor-shell{border-radius:24px;background:linear-gradient(180deg,rgba(5,10,22,.96),rgba(7,14,30,.94));border:2px solid rgba(0,255,170,.72);overflow:hidden}.monitor-head{display:flex;align-items:center;gap:14px;padding:16px 18px;border-bottom:1px solid rgba(104,240,255,.12);font:700 13px var(--mono);letter-spacing:.14em;text-transform:uppercase}.monitor-grid{display:grid;gap:16px;padding:18px}.monitor-card{padding:18px;border-radius:18px;background:rgba(11,19,38,.76);border:1px solid rgba(104,240,255,.08)}.monitor-label{display:block;color:var(--muted);font:700 14px var(--mono);margin-bottom:12px}.monitor-value{display:block;margin-bottom:14px;color:var(--accent);font:800 28px var(--display-alt);letter-spacing:.08em}.monitor-bar{height:8px;border-radius:999px;background:rgba(104,240,255,.08);overflow:hidden}.monitor-bar span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#00ff88,#00d4ff)}
            .section{padding:12px 0 80px}.section-head{display:flex;justify-content:space-between;align-items:end;gap:18px;margin-bottom:28px}.section-head small{display:block;color:var(--primary);font:700 12px var(--mono);letter-spacing:.16em;text-transform:uppercase;margin-bottom:10px}.section-head h2{margin:0;font:800 clamp(1.8rem,4vw,3rem)/1.05 var(--display);letter-spacing:.06em;text-transform:uppercase}.section-head p{max-width:700px;margin:10px 0 0;color:var(--muted);line-height:1.8}
            .guild-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.guild-card{padding:22px;border-radius:28px;background:linear-gradient(180deg,rgba(8,14,29,.88),rgba(6,11,23,.88));border:1px solid rgba(104,240,255,.08);box-shadow:var(--shadow)}.guild-top{display:flex;align-items:center;gap:14px}.guild-icon{width:64px;height:64px;border-radius:20px;display:grid;place-items:center;background:linear-gradient(135deg,rgba(104,240,255,.2),rgba(111,134,255,.24));border:1px solid rgba(104,240,255,.14);overflow:hidden}.guild-icon img{width:100%;height:100%;object-fit:cover}.guild-icon span{font:800 22px var(--display);color:#dffaff}.guild-name{min-width:0}.guild-name strong{display:block;font:800 22px var(--display-alt);letter-spacing:.04em}.guild-name small{display:block;margin-top:6px;color:var(--muted);font:700 12px var(--mono);word-break:break-all}.guild-badges{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}.badge{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:999px;background:rgba(104,240,255,.08);border:1px solid rgba(104,240,255,.16);color:var(--primary);font:700 11px var(--mono);letter-spacing:.08em;text-transform:uppercase}.badge.owner{background:rgba(124,255,178,.09);border-color:rgba(124,255,178,.22);color:var(--accent)}.guild-copy{margin-top:18px;color:var(--muted);font-size:14px;line-height:1.8}.guild-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}.cta{display:inline-flex;align-items:center;justify-content:center;padding:16px 20px;border-radius:18px;font-size:14px;font-weight:700;border:1px solid rgba(104,240,255,.16);background:rgba(11,19,38,.58);color:var(--text);cursor:pointer;min-width:180px}.cta.primary{color:#04111e;background:linear-gradient(135deg,var(--primary),var(--secondary));border-color:transparent}
            .empty{padding:34px;border-radius:28px;background:var(--panel);border:1px dashed rgba(104,240,255,.18);color:var(--muted);text-align:center;line-height:1.8}
            @media (max-width:1080px){.hero-grid,.guild-grid,.stats{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
            @media (max-width:780px){.nav,.section-head{flex-direction:column;align-items:flex-start}.shell{width:min(100% - 28px,1380px)}.stats{grid-template-columns:1fr}}
        </style>
    </head>
    <body>
        <div class="page">
            <div class="grid"></div>
            <div class="kana-rain" id="kanaRain"></div>

            <header class="header">
                <div class="shell">
                    <div class="nav">
                        <a href="{{ route('home') }}" class="brand">
                            <div class="mark">LY</div>
                            <div>
                                <strong>LYVA Studio</strong>
                                <span>Discord bot control surface untuk tim Roblox</span>
                            </div>
                        </a>
                        <div class="nav-actions">
                            <a href="{{ route('home') }}" class="pill">Kembali</a>
                            <a href="{{ route('dashboard') }}" class="pill primary">Dashboard</a>
                        </div>
                    </div>
                </div>
            </header>

            <main>
                <section class="hero">
                    <div class="shell">
                        <div class="hero-grid">
                            <div>
                                <span class="eyebrow">Discord Server Picker</span>
                                <h1>Pilih <span class="glow">Server</span> yang Mau Kamu Kelola</h1>
                                <p class="lead">
                                    Setelah login Discord, kamu tinggal pilih guild yang bot-nya sudah masuk dan memang kamu punya akses kelola.
                                    Setelah itu dashboard akan fokus ke server yang kamu pilih, jadi workflow admin terasa lebih rapi dan tidak campur-campur.
                                </p>

                                <div class="stats">
                                    <div class="stat">
                                        <strong>{{ $guilds->count() }}</strong>
                                        <span>Server bisa dikelola</span>
                                    </div>
                                    <div class="stat">
                                        <strong>{{ auth()->user()->discord_username ?? auth()->user()->name }}</strong>
                                        <span>Akun Discord aktif</span>
                                    </div>
                                    <div class="stat">
                                        <strong>1</strong>
                                        <span>Server aktif setelah dipilih</span>
                                    </div>
                                </div>
                            </div>

                            <div class="monitor">
                                <div class="monitor-shell">
                                    <div class="monitor-head">Dasbor Akses Guild</div>
                                    <div class="monitor-grid">
                                        <div class="monitor-card">
                                            <span class="monitor-label">Status OAuth</span>
                                            <span class="monitor-value">SYNCED</span>
                                            <div class="monitor-bar"><span style="width: 100%"></span></div>
                                        </div>
                                        <div class="monitor-card">
                                            <span class="monitor-label">Guild Management</span>
                                            <span class="monitor-value">READY</span>
                                            <div class="monitor-bar"><span style="width: 92%"></span></div>
                                        </div>
                                        <div class="monitor-card">
                                            <span class="monitor-label">Bot Access</span>
                                            <span class="monitor-value">FILTERED</span>
                                            <div class="monitor-bar"><span style="width: 88%"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section">
                    <div class="shell">
                        <div class="section-head">
                            <div>
                                <small>Guild List</small>
                                <h2>Server Discord Tersedia</h2>
                                <p>Semua kartu di bawah adalah server yang sudah lolos filter akses. Klik satu server untuk menjadikannya workspace aktif.</p>
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

                                    <div class="guild-badges">
                                        <span class="badge">Manageable</span>
                                        @if ($guild['owner'])
                                            <span class="badge owner">Owner</span>
                                        @endif
                                    </div>

                                    <p class="guild-copy">
                                        Pilih server ini untuk membuka panel operasional, setup Discord bot, VIP title tools, dan workflow Roblox yang terkait dengan guild ini.
                                    </p>

                                    <div class="guild-actions">
                                        <button class="cta primary" type="submit">Kelola server ini</button>
                                    </div>
                                </form>
                            @empty
                                <div class="empty">
                                    Belum ada server yang cocok ditampilkan. Pastikan bot sudah masuk ke server Discord dan guild itu sudah terdaftar di sistem backend.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <script>
            (() => {
                const kanaRain = document.getElementById('kanaRain');
                if (!kanaRain) return;

                const glyphs = 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンセキュリティ'.split('');
                for (let i = 0; i < 18; i += 1) {
                    const stream = document.createElement('div');
                    stream.className = 'kana-stream';
                    stream.style.left = `${Math.random() * 100}%`;
                    stream.style.animationDuration = `${8 + Math.random() * 8}s`;
                    stream.style.animationDelay = `${Math.random() * 4}s`;
                    stream.style.opacity = `${0.18 + Math.random() * 0.34}`;

                    const length = 10 + Math.floor(Math.random() * 12);
                    for (let j = 0; j < length; j += 1) {
                        const char = document.createElement('span');
                        char.textContent = glyphs[Math.floor(Math.random() * glyphs.length)];
                        stream.appendChild(char);
                    }

                    kanaRain.appendChild(stream);
                }
            })();
        </script>
    </body>
</html>
