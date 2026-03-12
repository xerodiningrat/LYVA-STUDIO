@php
    $title = __('VIP Title Setup');
    $navLinks = [
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pilih Server', 'href' => route('guilds.select')],
        ['label' => 'Discord Setup', 'href' => route('discord.setup')],
        ['label' => 'Roblox Scripts', 'href' => route('roblox.scripts.index')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            :root { color-scheme: dark; --ops-bg:#020715; --ops-panel:rgba(7,16,34,.84); --ops-line:rgba(92,232,255,.14); --ops-text:#eef4ff; --ops-muted:#91a5c7; --ops-cyan:#5ce8ff; --ops-emerald:#79ffbc; --ops-violet:#8a95ff; --ops-amber:#ffc07d; --ops-rose:#ff8d99; --ops-shadow:0 28px 80px rgba(0,0,0,.36); --ops-display:"Orbitron","Oxanium",ui-sans-serif,sans-serif; --ops-mono:"JetBrains Mono",ui-monospace,SFMono-Regular,monospace; }
            * { box-sizing:border-box; }
            body { margin:0; min-height:100vh; background:radial-gradient(circle at 18% 0%, rgba(92,232,255,.12), transparent 30%),radial-gradient(circle at 84% 9%, rgba(138,149,255,.13), transparent 28%),linear-gradient(180deg,#020612 0%,#030816 100%); color:var(--ops-text); font-family:"Instrument Sans",ui-sans-serif,system-ui,sans-serif; }
            body::before { content:""; position:fixed; inset:0; pointer-events:none; background-image:linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px); background-size:46px 46px; opacity:.14; mask-image:linear-gradient(180deg, rgba(0,0,0,.9), transparent 96%); }
            a { color:inherit; text-decoration:none; }
            .shell { max-width:1480px; margin:0 auto; padding:1.25rem; }
            .topbar,.hero,.panel,.tile,.notice { border:1px solid var(--ops-line); background:var(--ops-panel); box-shadow:var(--ops-shadow); backdrop-filter:blur(18px); }
            .topbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.15rem; border-radius:1.6rem; }
            .brand { display:flex; gap:.9rem; align-items:center; }
            .mark { width:3rem; height:3rem; display:grid; place-items:center; border-radius:1rem; background:linear-gradient(135deg,var(--ops-cyan),var(--ops-emerald)); color:#04111c; font:800 .92rem/1 var(--ops-display); letter-spacing:.12em; text-transform:uppercase; }
            .brand h1,.hero h2,.panel h3 { margin:0; font-family:var(--ops-display); text-transform:uppercase; letter-spacing:.08em; }
            .brand p,.hero p,.muted { color:var(--ops-muted); }
            .brand p { margin:.22rem 0 0; font-size:.82rem; }
            .nav { display:flex; flex-wrap:wrap; gap:.75rem; }
            .nav a,.primary { border-radius:999px; border:1px solid rgba(92,232,255,.14); background:rgba(255,255,255,.04); padding:.72rem 1rem; font-weight:700; }
            .primary { background:linear-gradient(135deg, rgba(92,232,255,.9), rgba(138,149,255,.88)); color:#02101b; }
            .hero,.panel,.tile,.notice { position:relative; overflow:hidden; }
            .hero::after,.panel::after,.tile::after,.notice::after { content:""; position:absolute; inset:0; pointer-events:none; background:radial-gradient(circle at top right, rgba(92,232,255,.15), transparent 34%); }
            .hero { margin-top:1.25rem; border-radius:2rem; padding:1.4rem; }
            .hero-grid,.content-grid,.form-grid,.map-grid,.stats-grid { display:grid; gap:1.2rem; }
            .hero-grid { grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr); align-items:start; }
            .content-grid { margin-top:1.25rem; grid-template-columns:minmax(380px,.9fr) minmax(0,1.1fr); }
            .form-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .map-grid { display:grid; gap:1rem; }
            .stats-grid { grid-template-columns:repeat(4,minmax(0,1fr)); margin-top:1.1rem; }
            .kicker,.label { display:inline-flex; align-items:center; gap:.55rem; border-radius:999px; border:1px solid rgba(92,232,255,.16); padding:.46rem .8rem; font:.72rem/1 var(--ops-mono); letter-spacing:.18em; text-transform:uppercase; color:var(--ops-cyan); }
            .kicker::before { content:""; width:.46rem; height:.46rem; border-radius:999px; background:var(--ops-emerald); box-shadow:0 0 14px rgba(121,255,188,.8); }
            .hero h2 { margin-top:1rem; font-size:clamp(2.4rem,5vw,4.7rem); line-height:.92; }
            .hero h2 span { display:block; background:linear-gradient(90deg,var(--ops-cyan),#baf5ff,var(--ops-emerald)); -webkit-background-clip:text; background-clip:text; color:transparent; }
            .hero p { margin-top:1rem; max-width:48rem; line-height:1.85; }
            .hero-flow { display:grid; gap:.8rem; }
            .tile { border-radius:1.3rem; padding:1rem; background:rgba(255,255,255,.04); }
            .tile strong { display:block; font-size:1rem; }
            .tile p { margin:.4rem 0 0; color:var(--ops-muted); font-size:.84rem; line-height:1.7; }
            .panel { border-radius:1.75rem; padding:1.15rem; }
            .panel-header { display:flex; justify-content:space-between; gap:1rem; align-items:start; margin-bottom:1rem; }
            .panel-header p { margin:.3rem 0 0; color:var(--ops-muted); }
            .pill { border-radius:999px; border:1px solid rgba(92,232,255,.14); background:rgba(255,255,255,.04); padding:.5rem .8rem; font:.68rem/1 var(--ops-mono); letter-spacing:.14em; text-transform:uppercase; color:#dce7ff; }
            .metric { border-radius:1.3rem; border:1px solid var(--ops-line); background:rgba(255,255,255,.04); padding:1rem; }
            .metric strong { display:block; margin-top:.6rem; font-family:var(--ops-display); font-size:1.8rem; }
            .notice { margin-top:1rem; border-radius:1.3rem; padding:1rem; background:rgba(121,255,188,.08); }
            label { display:block; font-size:.9rem; font-weight:700; color:#eef4ff; }
            input,textarea { width:100%; margin-top:.5rem; border-radius:1rem; border:1px solid rgba(92,232,255,.12); background:rgba(255,255,255,.04); color:var(--ops-text); padding:.9rem 1rem; font:inherit; }
            input::placeholder,textarea::placeholder { color:#7286a8; }
            .checkbox-row { display:flex; gap:.7rem; align-items:flex-start; padding:1rem; border-radius:1rem; border:1px solid rgba(92,232,255,.12); background:rgba(255,255,255,.03); }
            .checkbox-row input { width:auto; margin-top:.15rem; accent-color:var(--ops-cyan); }
            .actions { display:flex; flex-wrap:wrap; gap:.75rem; margin-top:1rem; }
            button { border:0; cursor:pointer; }
            .btn-primary,.btn-secondary,.btn-danger { border-radius:999px; padding:.8rem 1rem; font-weight:800; }
            .btn-primary { background:linear-gradient(135deg, rgba(92,232,255,.9), rgba(138,149,255,.88)); color:#02101b; }
            .btn-secondary { background:rgba(255,255,255,.06); color:var(--ops-text); border:1px solid rgba(92,232,255,.14); }
            .btn-danger { background:rgba(255,141,153,.12); color:var(--ops-rose); border:1px solid rgba(255,141,153,.22); }
            .map-card { border-radius:1.45rem; border:1px solid var(--ops-line); background:rgba(255,255,255,.03); padding:1rem; }
            .map-head { display:flex; justify-content:space-between; gap:1rem; align-items:start; }
            .map-head h4 { margin:0; font-size:1.2rem; color:#f7fbff; }
            .api-box,.code-box { border-radius:1rem; border:1px solid rgba(92,232,255,.12); padding:1rem; }
            .api-box { background:rgba(255,255,255,.04); word-break:break-all; font-family:var(--ops-mono); }
            .code-box { background:rgba(4,9,20,.95); color:#f9fbff; font-family:var(--ops-mono); white-space:pre-wrap; font-size:.82rem; }
            .status-badge { border-radius:999px; padding:.42rem .75rem; font-size:.72rem; font-weight:800; }
            .status-on { background:rgba(121,255,188,.14); color:var(--ops-emerald); }
            .status-off { background:rgba(255,255,255,.08); color:#dce7ff; }
            table { width:100%; border-collapse:collapse; }
            th,td { padding:.9rem .8rem; text-align:left; border-bottom:1px solid rgba(255,255,255,.08); }
            th { color:var(--ops-muted); font:.72rem/1 var(--ops-mono); letter-spacing:.14em; text-transform:uppercase; }
            td { color:#edf4ff; }
            @media (max-width:1180px) { .hero-grid,.content-grid { grid-template-columns:1fr; } .stats-grid,.form-grid { grid-template-columns:1fr; } }
        </style>
    </head>
    <body>
        <div class="shell">
            <header class="topbar">
                <div class="brand">
                    <div class="mark">LY</div>
                    <div>
                        <h1>LYVA Studio</h1>
                        <p>VIP title control panel yang lebih gampang dipakai admin dan lebih enak dilihat.</p>
                    </div>
                </div>
                <nav class="nav">
                    @foreach ($navLinks as $link)
                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                    <a href="{{ route('vip-title.setup') }}" class="primary">VIP Setup</a>
                </nav>
            </header>

            @if (session('status'))
                <section class="notice">{{ session('status') }}</section>
            @endif

            <section class="hero">
                <div class="hero-grid">
                    <div>
                        <span class="kicker">VIP title control</span>
                        <h2>Setup map <span>sekali jadi</span></h2>
                        <p>Halaman ini sekarang difokuskan buat admin yang ingin atur map key, gamepass, API key Roblox, dan snippet script dari satu panel yang rapi. User claim title tinggal fokus ke username dan title, tanpa dipaksa lihat konfigurasi mentah.</p>
                        <div class="stats-grid">
                            <div class="metric">
                                <span class="label">Maps</span>
                                <strong>{{ count($settings) }}</strong>
                                <span class="muted">Map VIP title yang sudah terdaftar.</span>
                            </div>
                            <div class="metric">
                                <span class="label">Claims</span>
                                <strong>{{ count($claims) }}</strong>
                                <span class="muted">Claim terbaru yang tampil di queue.</span>
                            </div>
                            <div class="metric">
                                <span class="label">Active</span>
                                <strong>{{ collect($settings)->where('is_active', true)->count() }}</strong>
                                <span class="muted">Map aktif yang siap dipakai.</span>
                            </div>
                            <div class="metric">
                                <span class="label">Script</span>
                                <strong>Ready</strong>
                                <span class="muted">Snippet Roblox langsung tersedia.</span>
                            </div>
                        </div>
                    </div>
                    <div class="hero-flow">
                        <div class="tile"><strong>1. Tambah map</strong><p>Isi nama map, map key, gamepass, dan slot title.</p></div>
                        <div class="tile"><strong>2. Generate API key</strong><p>Setiap map punya token Roblox sendiri yang siap dipakai.</p></div>
                        <div class="tile"><strong>3. Tempel snippet</strong><p>Copy config ke script map tanpa edit banyak bagian lain.</p></div>
                        <div class="tile"><strong>4. User tinggal claim</strong><p>Sesudah setup, flow user jadi jauh lebih simpel.</p></div>
                    </div>
                </div>
            </section>

            <div class="content-grid">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <span class="label">Tambah map</span>
                            <h3>New VIP title map</h3>
                            <p>Bikin konfigurasi map baru dari sini dan generate API key otomatis.</p>
                        </div>
                        <span class="pill">Create</span>
                    </div>
                    <form method="POST" action="{{ route('vip-title.setup.store') }}" style="display:grid; gap:1rem;">
                        @csrf
                        <div>
                            <label>Nama map</label>
                            <input name="name" placeholder="Mount Xyra" required>
                        </div>
                        <div>
                            <label>Map key</label>
                            <input name="map_key" placeholder="mountxyra" required>
                            <div class="muted" style="margin-top:.45rem; font-size:.82rem;">Gunakan huruf kecil tanpa spasi. Ini yang dipakai Discord bot dan Roblox.</div>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label>Gamepass ID</label>
                                <input name="gamepass_id" type="number" min="0" placeholder="1700114697" required>
                            </div>
                            <div>
                                <label>Mode claim</label>
                                <input name="claim_mode" placeholder="vip_gamepass atau duitku" value="vip_gamepass" required>
                                <div class="muted" style="margin-top:.45rem; font-size:.82rem;">Isi <code>vip_gamepass</code> untuk VIP biasa atau <code>duitku</code> untuk title berbayar.</div>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label>Title slot</label>
                                <input name="title_slot" type="number" min="1" max="10" value="10" required>
                            </div>
                            <div>
                                <label>Harga title (IDR)</label>
                                <input name="title_price_idr" type="number" min="1000" placeholder="15000">
                            </div>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label>Expiry pembayaran (menit)</label>
                                <input name="payment_expiry_minutes" type="number" min="5" max="1440" value="60">
                            </div>
                            <div>
                                <label>Label tombol bot</label>
                                <input name="button_label" placeholder="Beli Title Sekarang">
                            </div>
                        </div>
                        <div>
                            <label>Allowed Place IDs</label>
                            <textarea name="place_ids" rows="3" placeholder="76880221507840, 1234567890"></textarea>
                        </div>
                        <div>
                            <label>Role akses script Discord</label>
                            <textarea name="script_access_role_ids" rows="3" placeholder="123456789012345678, 987654321098765432"></textarea>
                            <div class="muted" style="margin-top:.45rem; font-size:.82rem;">Role ID Discord yang boleh klik tombol <code>Script Roblox</code>. Pisahkan dengan koma. Admin tetap bisa akses.</div>
                        </div>
                        <div>
                            <label>Catatan</label>
                            <textarea name="notes" rows="3" placeholder="Map utama public release"></textarea>
                        </div>
                        <label class="checkbox-row">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Aktifkan map ini supaya langsung bisa dipakai untuk claim VIP title.</span>
                        </label>
                        <div class="actions">
                            <button class="btn-primary">Tambah map dan generate API key</button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <span class="label">Map configs</span>
                            <h3>Configured VIP title maps</h3>
                            <p>Update map, regenerate API key, dan copy snippet Roblox langsung dari sini.</p>
                        </div>
                        <span class="pill">{{ count($settings) }} maps</span>
                    </div>
                    <div class="map-grid">
                        @forelse ($settings as $setting)
                            <article class="map-card">
                                <div class="map-head">
                                    <div>
                                        <h4>{{ $setting->name }}</h4>
                                        <div class="muted" style="margin-top:.35rem; font-size:.85rem;">Map key: <code>{{ $setting->map_key }}</code> · Gamepass: {{ $setting->gamepass_id }}</div>
                                    </div>
                                    <span class="status-badge {{ $setting->is_active ? 'status-on' : 'status-off' }}">{{ $setting->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>

                                <form method="POST" action="{{ route('vip-title.setup.update', $setting) }}" style="display:grid; gap:1rem; margin-top:1rem;">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-grid">
                                        <div>
                                            <label>Nama map</label>
                                            <input name="name" value="{{ $setting->name }}" required>
                                        </div>
                                        <div>
                                            <label>Map key</label>
                                            <input name="map_key" value="{{ $setting->map_key }}" required>
                                        </div>
                                    </div>
                                    <div class="form-grid">
                                        <div>
                                            <label>Gamepass ID</label>
                                            <input name="gamepass_id" type="number" min="0" value="{{ $setting->gamepass_id }}" required>
                                        </div>
                                        <div>
                                            <label>Mode claim</label>
                                            <input name="claim_mode" value="{{ $setting->claim_mode ?? 'vip_gamepass' }}" required>
                                        </div>
                                    </div>
                                    <div class="form-grid">
                                        <div>
                                            <label>Title slot</label>
                                            <input name="title_slot" type="number" min="1" max="10" value="{{ $setting->title_slot }}" required>
                                        </div>
                                        <div>
                                            <label>Harga title (IDR)</label>
                                            <input name="title_price_idr" type="number" min="1000" value="{{ $setting->title_price_idr }}">
                                        </div>
                                    </div>
                                    <div class="form-grid">
                                        <div>
                                            <label>Expiry pembayaran (menit)</label>
                                            <input name="payment_expiry_minutes" type="number" min="5" max="1440" value="{{ $setting->payment_expiry_minutes ?? 60 }}">
                                        </div>
                                        <div>
                                            <label>Label tombol bot</label>
                                            <input name="button_label" value="{{ $setting->button_label }}">
                                        </div>
                                    </div>
                                    <div>
                                        <label>Allowed Place IDs</label>
                                        <textarea name="place_ids" rows="2">{{ implode(', ', $setting->place_ids ?? []) }}</textarea>
                                    </div>
                                    <div>
                                        <label>Role akses script Discord</label>
                                        <textarea name="script_access_role_ids" rows="2">{{ implode(', ', $setting->script_access_role_ids ?? []) }}</textarea>
                                    </div>
                                    <div>
                                        <label>Catatan</label>
                                        <textarea name="notes" rows="2">{{ $setting->notes }}</textarea>
                                    </div>
                                    <label class="checkbox-row">
                                        <input type="checkbox" name="is_active" value="1" @checked($setting->is_active)>
                                        <span>Map ini aktif untuk claim VIP title.</span>
                                    </label>
                                    <div class="api-box">
                                        <div class="label" style="margin-bottom:.7rem;">API key Roblox</div>
                                        {{ $setting->api_key }}
                                    </div>
                                    <div class="code-box">CLAIM_MODE = "{{ $setting->claim_mode ?? 'vip_gamepass' }}"
BUTTON_LABEL = "{{ $setting->button_label ?: ($setting->claim_mode === 'duitku' ? 'Beli Title' : 'Claim Title') }}"
TITLE_PRICE_IDR = {{ $setting->title_price_idr ?? 0 }}
PAYMENT_EXPIRY_MINUTES = {{ $setting->payment_expiry_minutes ?? 60 }}
VIP_GAMEPASS_ID = {{ $setting->gamepass_id }}
VIP_TITLE_MAP_KEY = "{{ $setting->map_key }}"
VIP_TITLE_BACKEND_URL = "{{ $appUrl }}"
VIP_TITLE_API_KEY = "{{ $setting->api_key }}"
VIP_TITLE_SLOT = {{ $setting->title_slot }}
VIP_TITLE_ALLOWED_PLACE_IDS = [{{ implode(', ', $setting->place_ids ?? []) }}]</div>
                                    <div class="actions">
                                        <button class="btn-primary">Simpan perubahan</button>
                                    </div>
                                </form>

                                <div class="actions">
                                    <form method="POST" action="{{ route('vip-title.setup.regenerate-key', $setting) }}">
                                        @csrf
                                        <button class="btn-secondary">Generate API key baru</button>
                                    </form>
                                    <form method="POST" action="{{ route('vip-title.setup.destroy', $setting) }}" onsubmit="return confirm('Hapus map ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger">Hapus map</button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <div class="tile">
                                <strong>Belum ada map VIP title</strong>
                                <p>Tambahkan map pertama dari form di kiri supaya flow claim bisa langsung dipakai.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="panel" style="margin-top:1.25rem;">
                <div class="panel-header">
                    <div>
                        <span class="label">Claim queue</span>
                        <h3>Recent VIP title claims</h3>
                        <p>Monitor claim terbaru dan status apply dari backend ke Roblox.</p>
                    </div>
                    <span class="pill">Live queue</span>
                </div>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Requested</th>
                                <th>Map</th>
                                <th>Username</th>
                                <th>Title</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($claims as $claim)
                                <tr>
                                    <td>{{ optional($claim->requested_at)->diffForHumans() ?? '-' }}</td>
                                    <td><code>{{ $claim->map_key }}</code></td>
                                    <td>{{ $claim->roblox_username }}</td>
                                    <td>{{ $claim->requested_title }}</td>
                                    <td><span class="status-badge status-off">{{ $claim->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="muted" style="text-align:center;">Belum ada claim title.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </body>
</html>
