@php
    $title = __('VIP Title Setup');
    $workspaceName = $managedGuild['name'] ?? 'Server aktif';
    $activeMaps = collect($settings)->where('is_active', true)->count();
    $paidMaps = collect($settings)->filter(fn ($setting) => (int) ($setting->title_price_idr ?? 0) > 0)->count();
    $scriptProtectedMaps = collect($settings)->filter(fn ($setting) => count($setting->script_access_role_ids ?? []) > 0)->count();
    $vipParticles = range(1, 14);
    $setupSignals = [
        ['label' => 'Maps Active', 'value' => count($settings) > 0 ? (int) round(($activeMaps / max(1, count($settings))) * 100) : 0, 'tone' => 'var(--studio-accent)'],
        ['label' => 'Paid Flow', 'value' => count($settings) > 0 ? (int) round(($paidMaps / max(1, count($settings))) * 100) : 0, 'tone' => 'var(--studio-accent-3)'],
        ['label' => 'Script Access', 'value' => count($settings) > 0 ? (int) round(($scriptProtectedMaps / max(1, count($settings))) * 100) : 0, 'tone' => 'var(--studio-accent-2)'],
        ['label' => 'Claims Queue', 'value' => min(100, max(10, count($claims) * 14)), 'tone' => '#ff9bb0'],
    ];
    $adminNotes = [
        ['title' => 'Map key harus konsisten', 'copy' => 'Map key dipakai bareng oleh bot Discord, backend Laravel, dan script Roblox. Jangan ubah sembarangan setelah live.'],
        ['title' => 'Harga otomatis aktifkan flow beli', 'copy' => 'Kalau harga IDR diisi, panel bot akan menampilkan Beli Title tanpa mematikan Claim Title untuk user VIP.'],
        ['title' => 'Role akses script untuk keamanan', 'copy' => 'Batasi tombol Script Roblox hanya ke role tertentu supaya API key dan snippet tidak diambil sembarang orang.'],
    ];
@endphp

<x-portfolio.shell :title="$title" active-key="vip-title" search-placeholder="Cari map, API key, script role, harga title">
    <x-slot:head>
        <style>
            :root {
                --studio-accent: #6fe6ff;
                --studio-accent-2: #7df7c4;
                --studio-accent-3: #ffc57d;
                --studio-danger: #ff8c9d;
            }

            .vip-map-grid {
                display: grid;
                gap: 1rem;
            }

            .vip-hero {
                position: relative;
                isolation: isolate;
                overflow: hidden;
            }

            .vip-particles {
                position: absolute;
                inset: 0;
                pointer-events: none;
                z-index: 0;
                overflow: hidden;
            }

            .vip-particle {
                position: absolute;
                width: var(--size);
                height: var(--size);
                top: var(--top);
                left: var(--left);
                border-radius: 999px;
                background: radial-gradient(circle, rgba(111, 230, 255, .22), rgba(111, 230, 255, 0));
                border: 1px solid rgba(111, 230, 255, .16);
                animation: vipFloat var(--duration) ease-in-out infinite;
                animation-delay: var(--delay);
            }

            .vip-hero-glow {
                position: absolute;
                inset: auto auto -5rem -4rem;
                width: 18rem;
                height: 18rem;
                border-radius: 999px;
                background: radial-gradient(circle, rgba(125, 247, 196, .16), rgba(125, 247, 196, 0) 72%);
                filter: blur(10px);
                animation: vipPulse 10s ease-in-out infinite;
            }

            .vip-signal-card,
            .vip-map-card {
                position: relative;
                overflow: hidden;
                border-radius: 1.45rem;
                border: 1px solid var(--studio-line);
                background:
                    radial-gradient(circle at top right, rgba(111, 230, 255, .1), transparent 34%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
                padding: 1rem;
            }

            .vip-signal-card::before,
            .vip-map-card::before {
                content: "";
                position: absolute;
                inset: 0 auto auto 0;
                width: 100%;
                height: 1px;
                background: linear-gradient(90deg, rgba(111, 230, 255, .62), rgba(125, 247, 196, 0));
            }

            .vip-map-card > * + * {
                margin-top: 1rem;
            }

            .vip-map-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            .vip-map-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.6rem;
                margin-top: 0.7rem;
            }

            .vip-map-footer {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .vip-signal-bars,
            .vip-note-grid,
            .vip-bottom-grid {
                display: grid;
                gap: 1rem;
            }

            .vip-signal-bars {
                margin-top: 1rem;
            }

            .vip-signal-row {
                display: grid;
                gap: .45rem;
            }

            .vip-signal-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
            }

            .vip-signal-track {
                height: 10px;
                border-radius: 999px;
                overflow: hidden;
                background: rgba(255,255,255,.08);
            }

            .vip-signal-track span {
                display: block;
                width: var(--value);
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--tone), rgba(255,255,255,.92));
                box-shadow: 0 0 16px color-mix(in srgb, var(--tone) 45%, transparent);
                transform-origin: left center;
                transform: scaleX(0);
                animation: vipGrow 1.2s cubic-bezier(.22, 1, .36, 1) forwards;
            }

            .vip-snapshot-grid {
                display: grid;
                gap: .9rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .vip-snapshot-item,
            .vip-note-card {
                padding: 1rem 1.05rem;
                border-radius: 1.2rem;
                border: 1px solid rgba(255,255,255,.06);
                background: rgba(255,255,255,.03);
            }

            .vip-snapshot-item strong {
                display: block;
                margin-top: .65rem;
                font: 700 1.6rem/1 var(--studio-display);
            }

            .vip-api-box {
                padding: 1rem 1.05rem;
                border-radius: 1.1rem;
                border: 1px solid rgba(111, 230, 255, .18);
                background: linear-gradient(180deg, rgba(111, 230, 255, .08), rgba(255,255,255,.02));
                font-family: var(--studio-mono);
                word-break: break-all;
                box-shadow: inset 0 0 0 1px rgba(255,255,255,.02);
            }

            .vip-snippet-box {
                border-radius: 1.2rem;
                overflow: hidden;
                border: 1px solid rgba(255,255,255,.06);
            }

            .vip-snippet-box .studio-code {
                margin: 0;
                max-height: 18rem;
            }

            .vip-bottom-grid {
                grid-template-columns: 1.05fr .95fr;
            }

            .vip-note-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            @keyframes vipFloat {
                0%, 100% {
                    transform: translate3d(0, 0, 0) scale(1);
                    opacity: .18;
                }

                50% {
                    transform: translate3d(0, -16px, 0) scale(1.08);
                    opacity: .74;
                }
            }

            @keyframes vipPulse {
                0%, 100% {
                    transform: scale(1);
                    opacity: .84;
                }

                50% {
                    transform: scale(1.1);
                    opacity: 1;
                }
            }

            @keyframes vipGrow {
                to {
                    transform: scaleX(1);
                }
            }

            @media (max-width: 980px) {
                .vip-bottom-grid,
                .vip-note-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 720px) {
                .vip-snapshot-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        @include('partials.studio-workspace-style')
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('roblox.scripts.index') }}" wire:navigate class="portfolio-shell-action">Buka Roblox Scripts</a>
    </x-slot:headerActions>

    @if (session('status'))
        <section class="studio-notice" data-studio-hover>
            {{ session('status') }}
        </section>
    @endif

    <section class="studio-hero vip-hero" data-studio-hover>
        <div class="vip-particles" aria-hidden="true">
            <div class="vip-hero-glow"></div>
            @foreach ($vipParticles as $particle)
                <span
                    class="vip-particle"
                    style="--size: {{ 0.42 + (($particle % 4) * 0.24) }}rem; --top: {{ 8 + (($particle * 7) % 74) }}%; --left: {{ 4 + (($particle * 10) % 90) }}%; --delay: -{{ $particle * 0.3 }}s; --duration: {{ 7 + ($particle % 5) }}s;"
                ></span>
            @endforeach
        </div>

        <div class="studio-hero-grid">
            <div>
                <span class="studio-kicker">VIP Title Control</span>
                <h2>Setup map <span>sekali lalu tinggal jalan</span></h2>
                <p>Panel ini saya rapikan supaya admin bisa atur map, gamepass, harga, role akses script, dan snippet Roblox dari satu tempat. Flow user di bot tetap sederhana, tapi panel admin sekarang jauh lebih jelas dan nyaman dipindai.</p>

                <div class="studio-stats-grid">
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Maps</span>
                        <strong>{{ count($settings) }}</strong>
                        <p class="studio-copy">Total map VIP Title yang sudah didaftarkan.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Active</span>
                        <strong>{{ collect($settings)->where('is_active', true)->count() }}</strong>
                        <p class="studio-copy">Map aktif yang siap dipakai panel bot sekarang.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Claims</span>
                        <strong>{{ count($claims) }}</strong>
                        <p class="studio-copy">Claim terbaru yang masih terlihat di queue admin.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Scripts</span>
                        <strong>Sync</strong>
                        <p class="studio-copy">Snippet config langsung nyambung ke backend project ini.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Workspace</span>
                        <strong>{{ $workspaceName }}</strong>
                        <p class="studio-copy">Config di halaman ini sekarang terkunci ke guild aktif milik akun Discord kamu.</p>
                    </article>
                </div>
            </div>

            <aside class="studio-stack">
                <article class="studio-card vip-signal-card" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Workflow</span>
                            <h3 style="margin-top:.75rem;">Alur setup tercepat</h3>
                        </div>
                        <span class="studio-pill">4 Step</span>
                    </div>

                    <div class="studio-list-grid" style="margin-top:0;">
                        <article class="studio-card" data-studio-hover>
                            <strong>1. Tambah map dan map key</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">Buat identitas map yang dipakai bot dan Roblox saat claim/pull.</p>
                        </article>
                        <article class="studio-card" data-studio-hover>
                            <strong>2. Atur gamepass dan harga</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">Panel bot bisa otomatis menampilkan Claim Title, Beli Title, atau keduanya.</p>
                        </article>
                        <article class="studio-card" data-studio-hover>
                            <strong>3. Batasi akses script</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">Hanya role Discord tertentu yang bisa ambil file Roblox siap pakai.</p>
                        </article>
                        <article class="studio-card" data-studio-hover>
                            <strong>4. Copy snippet Roblox</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">Admin tinggal tempel config yang sudah kebentuk otomatis di bawah.</p>
                        </article>
                    </div>
                </article>

                <article class="studio-card vip-signal-card" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Config Signal</span>
                            <h3 style="margin-top:.75rem;">Map key dan API key readiness</h3>
                        </div>
                        <span class="studio-pill">Live</span>
                    </div>

                    <div class="vip-snapshot-grid">
                        <article class="vip-snapshot-item" data-studio-hover>
                            <span class="studio-label">Protected scripts</span>
                            <strong>{{ $scriptProtectedMaps }}</strong>
                            <p class="studio-copy" style="margin:.5rem 0 0;">Map yang sudah dibatasi role akses script Discord.</p>
                        </article>
                        <article class="vip-snapshot-item" data-studio-hover>
                            <span class="studio-label">Paid maps</span>
                            <strong>{{ $paidMaps }}</strong>
                            <p class="studio-copy" style="margin:.5rem 0 0;">Map yang sudah punya flow beli title via IDR.</p>
                        </article>
                    </div>

                    <div class="vip-signal-bars">
                        @foreach ($setupSignals as $signal)
                            <div class="vip-signal-row">
                                <div class="vip-signal-meta">
                                    <span>{{ $signal['label'] }}</span>
                                    <strong>{{ $signal['value'] }}%</strong>
                                </div>
                                <div class="vip-signal-track">
                                    <span style="--value: {{ $signal['value'] }}%; --tone: {{ $signal['tone'] }};"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </aside>
        </div>
    </section>

    <section class="studio-panel-grid">
        <section class="studio-panel" data-studio-hover>
            <div class="studio-panel-header">
                <div>
                    <span class="studio-label">Tambah Map</span>
                    <h3 style="margin-top:.75rem;">Map VIP Title baru</h3>
                    <p class="studio-copy" style="margin-top:.45rem;">Form tambah map saya rapikan jadi lebih lega, jelas, dan enak dipakai di mobile maupun desktop.</p>
                </div>
                <span class="studio-pill">Create</span>
            </div>

            <form method="POST" action="{{ route('vip-title.setup.store') }}" class="studio-stack">
                @csrf

                <div class="studio-field">
                    <label for="name">Nama map</label>
                    <input id="name" class="studio-input" name="name" value="{{ old('name') }}" placeholder="Mount Xyra" required>
                </div>

                <div class="studio-field">
                    <label for="map_key">Map key</label>
                    <input id="map_key" class="studio-input" name="map_key" value="{{ old('map_key') }}" placeholder="mountxyra" required>
                    <small>Gunakan huruf kecil tanpa spasi. Nilai ini dipakai bot Discord dan script Roblox.</small>
                </div>

                <div class="studio-form-grid">
                    <div class="studio-field">
                        <label for="gamepass_id">Gamepass ID</label>
                        <input id="gamepass_id" class="studio-input" name="gamepass_id" type="number" min="0" value="{{ old('gamepass_id') }}" placeholder="1700114697" required>
                    </div>
                    <div class="studio-field">
                        <label>Mode claim</label>
                        <input class="studio-input" value="Otomatis dari harga" readonly>
                        <small>Claim Title untuk user VIP tetap ada. Kalau harga IDR diisi, bot menambahkan tombol Beli Title.</small>
                    </div>
                </div>

                <div class="studio-form-grid">
                    <div class="studio-field">
                        <label for="title_slot">Title slot</label>
                        <input id="title_slot" class="studio-input" name="title_slot" type="number" min="1" max="10" value="{{ old('title_slot', 10) }}" required>
                    </div>
                    <div class="studio-field">
                        <label for="title_price_idr">Harga title (IDR)</label>
                        <input id="title_price_idr" class="studio-input" name="title_price_idr" type="number" min="1000" value="{{ old('title_price_idr') }}" placeholder="15000">
                    </div>
                </div>

                <div class="studio-form-grid">
                    <div class="studio-field">
                        <label for="payment_expiry_minutes">Expiry pembayaran (menit)</label>
                        <input id="payment_expiry_minutes" class="studio-input" name="payment_expiry_minutes" type="number" min="5" max="1440" value="{{ old('payment_expiry_minutes', 60) }}">
                    </div>
                    <div class="studio-field">
                        <label for="button_label">Label tombol bot</label>
                        <input id="button_label" class="studio-input" name="button_label" value="{{ old('button_label') }}" placeholder="Beli Title Sekarang">
                    </div>
                </div>

                <div class="studio-field">
                    <label for="place_ids">Allowed Place IDs</label>
                    <textarea id="place_ids" class="studio-textarea" name="place_ids" rows="3" placeholder="76880221507840, 1234567890">{{ old('place_ids') }}</textarea>
                </div>

                <div class="studio-field">
                    <label for="script_access_role_ids">Role akses script Discord</label>
                    <textarea id="script_access_role_ids" class="studio-textarea" name="script_access_role_ids" rows="3" placeholder="123456789012345678, 987654321098765432">{{ old('script_access_role_ids') }}</textarea>
                    <small>Isi Role ID Discord yang boleh klik tombol <span class="studio-inline-code">Script Roblox</span>. Admin tetap bisa akses.</small>
                </div>

                <div class="studio-field">
                    <label for="notes">Catatan</label>
                    <textarea id="notes" class="studio-textarea" name="notes" rows="3" placeholder="Map utama public release">{{ old('notes') }}</textarea>
                </div>

                <label class="studio-checkbox">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                    <span>Aktifkan map ini supaya langsung siap dipakai untuk panel bot dan claim VIP Title.</span>
                </label>

                <div class="studio-actions">
                    <button type="submit" class="studio-button">Tambah map dan generate API key</button>
                </div>
            </form>
        </section>

        <section class="studio-panel" data-studio-hover>
            <div class="studio-panel-header">
                <div>
                    <span class="studio-label">Map Configs</span>
                    <h3 style="margin-top:.75rem;">Map VIP Title yang sudah ada</h3>
                    <p class="studio-copy" style="margin-top:.45rem;">Setiap kartu saya rapikan biar update field, copy API key, dan ambil snippet terasa lebih cepat dan tidak sumpek.</p>
                </div>
                <span class="studio-pill">{{ count($settings) }} Maps</span>
            </div>

            <div class="vip-map-grid">
                @forelse ($settings as $setting)
                    <article class="vip-map-card" data-studio-hover>
                        <div class="vip-map-head">
                            <div>
                                <h3 style="font-size:1.3rem;">{{ $setting->name }}</h3>
                                <div class="vip-map-meta">
                                    <span class="studio-chip">Key {{ $setting->map_key }}</span>
                                    <span class="studio-chip">Gamepass {{ $setting->gamepass_id }}</span>
                                    <span class="studio-chip">Slot {{ $setting->title_slot }}</span>
                                    <span class="studio-chip">{{ ($setting->title_price_idr ?? 0) > 0 ? 'Claim + Beli Title' : 'Claim Title' }}</span>
                                </div>
                            </div>
                            <span class="studio-badge {{ $setting->is_active ? 'studio-badge-ok' : 'studio-badge-off' }}">
                                {{ $setting->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('vip-title.setup.update', $setting) }}" class="studio-stack">
                            @csrf
                            @method('PUT')

                            <div class="studio-form-grid">
                                <div class="studio-field">
                                    <label>Nama map</label>
                                    <input class="studio-input" name="name" value="{{ $setting->name }}" required>
                                </div>
                                <div class="studio-field">
                                    <label>Map key</label>
                                    <input class="studio-input" name="map_key" value="{{ $setting->map_key }}" required>
                                </div>
                            </div>

                            <div class="studio-form-grid">
                                <div class="studio-field">
                                    <label>Gamepass ID</label>
                                    <input class="studio-input" name="gamepass_id" type="number" min="0" value="{{ $setting->gamepass_id }}" required>
                                </div>
                                <div class="studio-field">
                                    <label>Mode claim</label>
                                    <input class="studio-input" value="{{ ($setting->title_price_idr ?? 0) > 0 ? 'Hybrid: Claim + Beli Title' : 'Claim Title saja' }}" readonly>
                                </div>
                            </div>

                            <div class="studio-form-grid">
                                <div class="studio-field">
                                    <label>Title slot</label>
                                    <input class="studio-input" name="title_slot" type="number" min="1" max="10" value="{{ $setting->title_slot }}" required>
                                </div>
                                <div class="studio-field">
                                    <label>Harga title (IDR)</label>
                                    <input class="studio-input" name="title_price_idr" type="number" min="1000" value="{{ $setting->title_price_idr }}">
                                </div>
                            </div>

                            <div class="studio-form-grid">
                                <div class="studio-field">
                                    <label>Expiry pembayaran (menit)</label>
                                    <input class="studio-input" name="payment_expiry_minutes" type="number" min="5" max="1440" value="{{ $setting->payment_expiry_minutes ?? 60 }}">
                                </div>
                                <div class="studio-field">
                                    <label>Label tombol bot</label>
                                    <input class="studio-input" name="button_label" value="{{ $setting->button_label }}">
                                </div>
                            </div>

                            <div class="studio-field">
                                <label>Allowed Place IDs</label>
                                <textarea class="studio-textarea" name="place_ids" rows="3">{{ implode(', ', $setting->place_ids ?? []) }}</textarea>
                            </div>

                            <div class="studio-field">
                                <label>Role akses script Discord</label>
                                <textarea class="studio-textarea" name="script_access_role_ids" rows="3">{{ implode(', ', $setting->script_access_role_ids ?? []) }}</textarea>
                            </div>

                            <div class="studio-field">
                                <label>Catatan</label>
                                <textarea class="studio-textarea" name="notes" rows="3">{{ $setting->notes }}</textarea>
                            </div>

                            <label class="studio-checkbox">
                                <input type="checkbox" name="is_active" value="1" @checked($setting->is_active)>
                                <span>Map ini aktif untuk panel bot dan claim VIP Title.</span>
                            </label>

                            <div class="studio-field">
                                <label>API key Roblox</label>
                                <div class="vip-api-box">{{ $setting->api_key }}</div>
                            </div>

                            <div class="studio-field">
                                <label>Snippet Roblox</label>
                                <div class="vip-snippet-box"><div class="studio-code">CLAIM_MODE = "{{ ($setting->title_price_idr ?? 0) > 0 ? 'hybrid' : 'vip_gamepass' }}"
BUTTON_LABEL = "{{ $setting->button_label ?: 'Beli Title' }}"
TITLE_PRICE_IDR = {{ $setting->title_price_idr ?? 0 }}
PAYMENT_EXPIRY_MINUTES = {{ $setting->payment_expiry_minutes ?? 60 }}
VIP_GAMEPASS_ID = {{ $setting->gamepass_id }}
VIP_TITLE_MAP_KEY = "{{ $setting->map_key }}"
VIP_TITLE_BACKEND_URL = "{{ $appUrl }}"
VIP_TITLE_API_KEY = "{{ $setting->api_key }}"
VIP_TITLE_SLOT = {{ $setting->title_slot }}
VIP_TITLE_ALLOWED_PLACE_IDS = [{{ implode(', ', $setting->place_ids ?? []) }}]</div></div>
                            </div>

                            <div class="studio-actions">
                                <button type="submit" class="studio-button">Simpan perubahan</button>
                            </div>
                        </form>

                        <div class="vip-map-footer">
                            <form method="POST" action="{{ route('vip-title.setup.regenerate-key', $setting) }}">
                                @csrf
                                <button type="submit" class="studio-button-ghost">Generate API key baru</button>
                            </form>
                            <form method="POST" action="{{ route('vip-title.setup.destroy', $setting) }}" onsubmit="return confirm('Hapus map ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="studio-button-danger">Hapus map</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="studio-empty">
                        <strong>Belum ada map VIP Title</strong>
                        <p class="studio-copy" style="margin-top:.45rem;">Tambahkan map pertama dari form sebelah kiri supaya panel bot dan script Roblox langsung punya konfigurasi dasar.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </section>

    <section class="studio-panel" data-studio-hover>
        <div class="studio-panel-header">
            <div>
                <span class="studio-label">Claim Queue</span>
                <h3 style="margin-top:.75rem;">Recent VIP Title claims</h3>
                <p class="studio-copy" style="margin-top:.45rem;">Queue claim saya rapikan biar admin lebih cepat memantau request terbaru dari Discord dan Roblox.</p>
            </div>
            <span class="studio-pill">Live Queue</span>
        </div>

        <div class="studio-table-wrap">
            <table class="studio-table">
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
                            <td><span class="studio-inline-code">{{ $claim->map_key }}</span></td>
                            <td>{{ $claim->roblox_username }}</td>
                            <td>{{ $claim->requested_title }}</td>
                            <td>
                                <span class="studio-badge {{ in_array($claim->status, ['pending', 'applied'], true) ? 'studio-badge-ok' : 'studio-badge-off' }}">
                                    {{ $claim->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="studio-muted" style="text-align:center;">Belum ada claim title.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="vip-bottom-grid">
        <section class="studio-panel" data-studio-hover>
            <div class="studio-panel-header">
                <div>
                    <span class="studio-label">Admin Notes</span>
                    <h3 style="margin-top:.75rem;">Catatan penting untuk map key dan API key</h3>
                    <p class="studio-copy" style="margin-top:.45rem;">Saya isi bagian bawah ini supaya halaman tidak terasa cuma form panjang, tapi juga jadi panduan admin saat pegang config VIP Title.</p>
                </div>
                <span class="studio-pill">Guide</span>
            </div>

            <div class="vip-note-grid">
                @foreach ($adminNotes as $index => $note)
                    <article class="vip-note-card" data-studio-hover>
                        <span class="studio-note" style="margin-top:0;">Note {{ $index + 1 }}</span>
                        <strong style="display:block;margin-top:.6rem;">{{ $note['title'] }}</strong>
                        <p class="studio-copy" style="margin:.5rem 0 0;">{{ $note['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="studio-panel" data-studio-hover>
            <div class="studio-panel-header">
                <div>
                    <span class="studio-label">Quick Snapshot</span>
                    <h3 style="margin-top:.75rem;">Ringkasan VIP key dan akses script</h3>
                    <p class="studio-copy" style="margin-top:.45rem;">Panel ini membantu admin lihat kondisi config tanpa harus baca semua kartu map satu per satu.</p>
                </div>
                <span class="studio-pill">Summary</span>
            </div>

            <div class="studio-stack">
                <article class="vip-note-card" data-studio-hover>
                    <span class="studio-label">Maps aktif</span>
                    <strong style="display:block;margin-top:.6rem;font-size:1.35rem;">{{ $activeMaps }} dari {{ count($settings) }}</strong>
                    <p class="studio-copy" style="margin:.5rem 0 0;">Map aktif ini yang dipakai panel bot saat kirim tombol claim, beli, ubah title, dan script Roblox.</p>
                </article>

                <article class="vip-note-card" data-studio-hover>
                    <span class="studio-label">API key rotation</span>
                    <p class="studio-copy" style="margin:.55rem 0 0;">Kalau API key bocor atau script salah tangan, langsung generate ulang dari kartu map terkait agar endpoint Roblox lama berhenti dipakai.</p>
                </article>

                <article class="vip-note-card" data-studio-hover>
                    <span class="studio-label">Script access role</span>
                    <p class="studio-copy" style="margin:.55rem 0 0;">Saat role akses script diisi, hanya admin dan role yang kamu tentukan yang bisa ambil file Roblox dari panel Discord.</p>
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
                        const rotateY = ((x / rect.width) - 0.5) * 6;
                        const rotateX = (0.5 - (y / rect.height)) * 5;

                        card.style.setProperty('--mx', `${(x / rect.width) * 100}%`);
                        card.style.setProperty('--my', `${(y / rect.height) * 100}%`);
                        card.style.transform = `perspective(1400px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-5px)`;
                    });

                    card.addEventListener('pointerleave', reset);
                    card.addEventListener('pointercancel', reset);
                });
            })();
        </script>
    </x-slot:scripts>
</x-portfolio.shell>
