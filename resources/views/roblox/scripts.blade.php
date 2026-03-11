@php
    $title = __('Roblox Scripts');
    $navLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pilih Server', 'href' => route('guilds.select')],
        ['label' => 'Discord Setup', 'href' => route('discord.setup')],
        ['label' => 'VIP Title Setup', 'href' => route('vip-title.setup')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            :root { color-scheme: dark; --ops-bg:#020815; --ops-panel:rgba(7,16,34,.84); --ops-line:rgba(108,255,146,.14); --ops-text:#eef4ff; --ops-muted:#93a5c7; --ops-lime:#9dff71; --ops-cyan:#63f4ff; --ops-violet:#8a95ff; --ops-shadow:0 28px 80px rgba(0,0,0,.36); --ops-display:"Orbitron","Oxanium",ui-sans-serif,sans-serif; --ops-mono:"JetBrains Mono",ui-monospace,SFMono-Regular,monospace; }
            * { box-sizing:border-box; }
            body { margin:0; min-height:100vh; background:radial-gradient(circle at 15% 0%, rgba(99,244,255,.1), transparent 26%),radial-gradient(circle at 84% 9%, rgba(157,255,113,.1), transparent 24%),linear-gradient(180deg,#020612 0%,#030816 100%); color:var(--ops-text); font-family:"Instrument Sans",ui-sans-serif,system-ui,sans-serif; }
            body::before { content:""; position:fixed; inset:0; pointer-events:none; background-image:linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px); background-size:46px 46px; opacity:.14; mask-image:linear-gradient(180deg, rgba(0,0,0,.9), transparent 96%); }
            a { color:inherit; text-decoration:none; }
            .shell { max-width:1400px; margin:0 auto; padding:1.25rem; }
            .topbar,.hero,.card,.mini { border:1px solid var(--ops-line); background:var(--ops-panel); box-shadow:var(--ops-shadow); backdrop-filter:blur(18px); }
            .topbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.15rem; border-radius:1.6rem; }
            .brand { display:flex; gap:.9rem; align-items:center; }
            .mark { width:3rem; height:3rem; display:grid; place-items:center; border-radius:1rem; background:linear-gradient(135deg,var(--ops-cyan),var(--ops-lime)); color:#04111c; font:800 .92rem/1 var(--ops-display); letter-spacing:.12em; text-transform:uppercase; }
            .brand h1,.hero h2,.card h3 { margin:0; font-family:var(--ops-display); letter-spacing:.08em; text-transform:uppercase; }
            .brand p,.hero p,.muted { color:var(--ops-muted); }
            .brand p { margin:.2rem 0 0; font-size:.82rem; }
            .nav { display:flex; flex-wrap:wrap; gap:.75rem; }
            .nav a,.primary,.btn { border-radius:999px; border:1px solid rgba(99,244,255,.14); background:rgba(255,255,255,.04); padding:.72rem 1rem; font-weight:700; }
            .primary,.btn-download { background:linear-gradient(135deg, rgba(157,255,113,.88), rgba(99,244,255,.88)); color:#04111c; }
            .hero,.card,.mini { position:relative; overflow:hidden; }
            .hero::after,.card::after,.mini::after { content:""; position:absolute; inset:0; pointer-events:none; background:radial-gradient(circle at top right, rgba(157,255,113,.14), transparent 34%); }
            .hero { margin-top:1.25rem; border-radius:2rem; padding:1.4rem; }
            .hero-grid,.stats-grid,.script-grid { display:grid; gap:1.2rem; }
            .hero-grid { grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr); align-items:start; }
            .stats-grid { grid-template-columns:repeat(3,minmax(0,1fr)); margin-top:1.1rem; }
            .script-grid { margin-top:1.25rem; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); }
            .kicker,.label { display:inline-flex; align-items:center; gap:.55rem; border-radius:999px; border:1px solid rgba(157,255,113,.16); padding:.46rem .8rem; font:.72rem/1 var(--ops-mono); letter-spacing:.18em; text-transform:uppercase; color:var(--ops-lime); }
            .kicker::before { content:""; width:.46rem; height:.46rem; border-radius:999px; background:var(--ops-cyan); box-shadow:0 0 14px rgba(99,244,255,.8); }
            .hero h2 { margin-top:1rem; font-size:clamp(2.4rem,5vw,4.7rem); line-height:.92; }
            .hero h2 span { display:block; background:linear-gradient(90deg,var(--ops-lime),#f2ffd5,var(--ops-cyan)); -webkit-background-clip:text; background-clip:text; color:transparent; }
            .hero p { margin-top:1rem; max-width:48rem; line-height:1.85; }
            .mini { border-radius:1.3rem; padding:1rem; background:rgba(255,255,255,.04); }
            .mini strong { display:block; font-family:var(--ops-display); font-size:1.8rem; }
            .card { border-radius:1.5rem; padding:1.15rem; }
            .card p { line-height:1.8; }
            .actions { display:flex; flex-wrap:wrap; gap:.7rem; margin-top:1rem; }
            .btn { display:inline-flex; align-items:center; justify-content:center; }
            .btn-preview { color:#edf4ff; }
            .btn-download { border:0; }
            @media (max-width:1100px) { .hero-grid,.stats-grid { grid-template-columns:1fr; } }
        </style>
    </head>
    <body>
        <div class="shell">
            <header class="topbar">
                <div class="brand">
                    <div class="mark">LY</div>
                    <div>
                        <h1>LYVA Studio</h1>
                        <p>Library script Roblox yang sekarang tampil lebih proper dan enak dipakai admin.</p>
                    </div>
                </div>
                <nav class="nav">
                    @foreach ($navLinks as $link)
                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                    <a href="{{ route('roblox.scripts.index') }}" class="primary">Script Library</a>
                </nav>
            </header>

            <section class="hero">
                <div class="hero-grid">
                    <div>
                        <span class="kicker">Roblox script library</span>
                        <h2>Script siap <span>tempel dan deploy</span></h2>
                        <p>Halaman ini sekarang dibuat seperti library operasional, jadi admin bisa langsung lihat daftar script, preview isi file, dan download versi yang sudah sinkron dengan backend Laravel tanpa ngerasa buka halaman mentah.</p>
                        <div class="stats-grid">
                            <div class="mini">
                                <span class="label">Scripts</span>
                                <strong>{{ count($scripts) }}</strong>
                                <span class="muted">File yang tersedia di library.</span>
                            </div>
                            <div class="mini">
                                <span class="label">Backend</span>
                                <strong>SYNC</strong>
                                <span class="muted">APP_URL dan token bisa ikut ke file download.</span>
                            </div>
                            <div class="mini">
                                <span class="label">Workflow</span>
                                <strong>FAST</strong>
                                <span class="muted">Preview lalu download tanpa buka folder project.</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <span class="label">How to use</span>
                        <h3 style="margin-top:1rem;">Alur pakai paling cepat</h3>
                        <p style="margin-top:1rem;" class="muted">Pilih script yang dibutuhkan, preview isi file kalau ingin cek dulu, lalu download versi yang sudah memakai config project ini. Cocok buat admin yang mau langsung tempel ke Roblox Studio tanpa ribet cari file manual.</p>
                    </div>
                </div>
            </section>

            <section class="script-grid">
                @foreach ($scripts as $script)
                    <article class="card">
                        <span class="label">{{ $script['filename'] }}</span>
                        <h3 style="margin-top:1rem;">{{ $script['label'] }}</h3>
                        <p class="muted" style="margin-top:.8rem;">{{ $script['description'] }}</p>
                        <div class="actions">
                            <a href="{{ route('roblox.scripts.show', $script['slug']) }}" class="btn btn-preview">Preview</a>
                            <a href="{{ route('roblox.scripts.download', $script['slug']) }}" class="btn btn-download">Download</a>
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </body>
</html>
