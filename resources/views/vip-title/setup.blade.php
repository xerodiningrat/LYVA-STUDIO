<x-layouts::app :title="__('VIP Title Setup')">
    <div class="space-y-6">
        @if (session('status'))
            <section class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                {{ session('status') }}
            </section>
        @endif

        <section class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-[linear-gradient(135deg,_#0b1220_0%,_#17345b_45%,_#2170b8_100%)] p-6 text-white shadow-[0_24px_90px_rgba(15,23,42,0.25)]">
            <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                <div class="space-y-4">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-100">
                        VIP Title Control
                    </span>
                    <div>
                        <h1 class="font-display text-4xl font-bold tracking-tight">Satu tempat untuk atur map key, gamepass, API key Roblox, dan snippet script yang siap pakai.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-sky-50/90">
                            Admin cukup isi data map sekali di sini. Setelah itu user Discord tinggal fokus ke username Roblox dan custom title, tanpa perlu mikirin token atau konfigurasi mentah.
                        </p>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-white/15 bg-white/10 p-5 backdrop-blur">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-100">Flow yang lebih simpel</p>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-2xl bg-white/10 px-4 py-3">1. Admin tambah map dan gamepass di web.</div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3">2. Dashboard generate API key khusus Roblox.</div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3">3. Admin tempel snippet config ke script map.</div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3">4. User claim title tanpa bingung setting teknis.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Tambah map</p>
                    <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">New VIP title map</h2>
                </div>

                <form method="POST" action="{{ route('vip-title.setup.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-zinc-900">Nama map</label>
                        <input name="name" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm" placeholder="Mount Xyra" required>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-zinc-900">Map key</label>
                        <input name="map_key" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 font-mono text-sm" placeholder="mountxyra" required>
                        <p class="mt-2 text-xs text-zinc-500">Gunakan huruf kecil tanpa spasi. Ini yang dipakai bot dan Roblox script.</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-zinc-900">Gamepass ID</label>
                            <input name="gamepass_id" type="number" min="0" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm" placeholder="1700114697" required>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-zinc-900">Title slot</label>
                            <input name="title_slot" type="number" min="1" max="10" value="10" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm" required>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-zinc-900">Allowed Place IDs</label>
                        <textarea name="place_ids" rows="3" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm" placeholder="76880221507840, 1234567890"></textarea>
                        <p class="mt-2 text-xs text-zinc-500">Opsional. Pisahkan dengan koma atau spasi.</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-zinc-900">Catatan</label>
                        <textarea name="notes" rows="3" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm" placeholder="Map utama public release"></textarea>
                    </div>
                    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 text-sm text-zinc-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-zinc-300">
                        Aktifkan map ini untuk claim VIP title
                    </label>
                    <button class="rounded-2xl bg-zinc-950 px-5 py-3 text-sm font-semibold text-white">Tambah map dan generate API key</button>
                </form>
            </article>

            <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Map configs</p>
                    <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Configured VIP title maps</h2>
                </div>

                <div class="mt-5 space-y-5">
                    @forelse ($settings as $setting)
                        <div class="rounded-[1.5rem] border border-zinc-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-zinc-950">{{ $setting->name }}</h3>
                                    <p class="mt-1 text-sm text-zinc-500">Map key: <code>{{ $setting->map_key }}</code> • Gamepass: {{ $setting->gamepass_id }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $setting->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                    {{ $setting->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <form method="POST" action="{{ route('vip-title.setup.update', $setting) }}" class="mt-4 space-y-4">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-900">Nama map</label>
                                        <input name="name" value="{{ $setting->name }}" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm" required>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-900">Map key</label>
                                        <input name="map_key" value="{{ $setting->map_key }}" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 font-mono text-sm" required>
                                    </div>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-900">Gamepass ID</label>
                                        <input name="gamepass_id" type="number" min="0" value="{{ $setting->gamepass_id }}" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm" required>
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-zinc-900">Title slot</label>
                                        <input name="title_slot" type="number" min="1" max="10" value="{{ $setting->title_slot }}" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-900">Allowed Place IDs</label>
                                    <textarea name="place_ids" rows="2" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm">{{ implode(', ', $setting->place_ids ?? []) }}</textarea>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-zinc-900">Catatan</label>
                                    <textarea name="notes" rows="2" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm">{{ $setting->notes }}</textarea>
                                </div>
                                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 text-sm text-zinc-700">
                                    <input type="checkbox" name="is_active" value="1" @checked($setting->is_active) class="rounded border-zinc-300">
                                    Aktif
                                </label>

                                <div class="rounded-[1.25rem] border border-zinc-200 bg-zinc-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">API key Roblox</p>
                                    <p class="mt-2 break-all font-mono text-sm text-zinc-800">{{ $setting->api_key }}</p>
                                </div>

                                <div class="rounded-[1.25rem] border border-zinc-200 bg-stone-950 p-4 text-xs text-stone-100">
<pre class="whitespace-pre-wrap">VIP_GAMEPASS_ID = {{ $setting->gamepass_id }}
VIP_TITLE_MAP_KEY = "{{ $setting->map_key }}"
VIP_TITLE_BACKEND_URL = "{{ $appUrl }}"
VIP_TITLE_API_KEY = "{{ $setting->api_key }}"
VIP_TITLE_SLOT = {{ $setting->title_slot }}</pre>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button class="rounded-2xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white">Simpan perubahan</button>
                                </div>
                            </form>

                            <div class="mt-3 flex flex-wrap gap-3">
                                <form method="POST" action="{{ route('vip-title.setup.regenerate-key', $setting) }}">
                                    @csrf
                                    <button class="rounded-2xl border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800">Generate API key baru</button>
                                </form>
                                <form method="POST" action="{{ route('vip-title.setup.destroy', $setting) }}" onsubmit="return confirm('Hapus map ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-2xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700">Hapus map</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-zinc-300 px-5 py-8 text-sm text-zinc-500">
                            Belum ada map VIP title. Tambahkan map pertama dari form di kiri.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Claim queue</p>
                <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Recent VIP title claims</h2>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-zinc-500">
                        <tr class="border-b border-zinc-200">
                            <th class="px-3 py-3 font-semibold">Requested</th>
                            <th class="px-3 py-3 font-semibold">Map</th>
                            <th class="px-3 py-3 font-semibold">Username</th>
                            <th class="px-3 py-3 font-semibold">Title</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($claims as $claim)
                            <tr class="border-b border-zinc-100">
                                <td class="px-3 py-3 text-zinc-500">{{ optional($claim->requested_at)->diffForHumans() ?? '-' }}</td>
                                <td class="px-3 py-3 font-mono text-zinc-800">{{ $claim->map_key }}</td>
                                <td class="px-3 py-3 text-zinc-800">{{ $claim->roblox_username }}</td>
                                <td class="px-3 py-3 text-zinc-950">{{ $claim->requested_title }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">{{ $claim->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-zinc-500">Belum ada claim title.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
