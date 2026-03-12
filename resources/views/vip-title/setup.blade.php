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

            .vip-map-card {
                border-radius: 1.45rem;
                border: 1px solid var(--studio-line);
                background: rgba(255, 255, 255, 0.035);
                padding: 1rem;
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
        </style>
        @include('partials.studio-workspace-style')
    </head>
    <body>
        <div class="studio-shell">
            <div class="studio-glow-a"></div>
            <div class="studio-glow-b"></div>
            <div class="studio-glow-c"></div>

            @include('partials.studio-topbar', [
                'navLinks' => $navLinks,
                'activeHref' => route('vip-title.setup'),
                'ctaHref' => route('vip-title.setup'),
                'ctaLabel' => 'VIP Setup',
                'brandTitle' => 'LYVA Studio',
                'brandCopy' => 'Workspace VIP Title yang sekarang lebih rapih, modern, dan jauh lebih enak dipakai admin.',
            ])

            @if (session('status'))
                <section class="studio-notice" data-studio-hover>
                    {{ session('status') }}
                </section>
            @endif

            <section class="studio-hero" data-studio-hover>
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
                        </div>
                    </div>

                    <aside class="studio-stack">
                        <article class="studio-card" data-studio-hover>
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
                                        <div class="studio-api studio-surface">{{ $setting->api_key }}</div>
                                    </div>

                                    <div class="studio-field">
                                        <label>Snippet Roblox</label>
                                        <div class="studio-code">CLAIM_MODE = "{{ ($setting->title_price_idr ?? 0) > 0 ? 'hybrid' : 'vip_gamepass' }}"
BUTTON_LABEL = "{{ $setting->button_label ?: 'Beli Title' }}"
TITLE_PRICE_IDR = {{ $setting->title_price_idr ?? 0 }}
PAYMENT_EXPIRY_MINUTES = {{ $setting->payment_expiry_minutes ?? 60 }}
VIP_GAMEPASS_ID = {{ $setting->gamepass_id }}
VIP_TITLE_MAP_KEY = "{{ $setting->map_key }}"
VIP_TITLE_BACKEND_URL = "{{ $appUrl }}"
VIP_TITLE_API_KEY = "{{ $setting->api_key }}"
VIP_TITLE_SLOT = {{ $setting->title_slot }}
VIP_TITLE_ALLOWED_PLACE_IDS = [{{ implode(', ', $setting->place_ids ?? []) }}]</div>
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

            <section class="studio-panel" data-studio-hover style="margin-top:1.2rem;">
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
        </div>
    </body>
</html>
