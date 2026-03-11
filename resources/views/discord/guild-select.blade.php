<x-layouts::app :title="__('Pilih Server')">
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-[linear-gradient(135deg,_#0b1220_0%,_#16325a_45%,_#1f7ab9_100%)] p-6 text-white shadow-[0_24px_90px_rgba(15,23,42,0.25)]">
            <div class="space-y-4">
                <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-100">
                    Discord Server Picker
                </span>
                <div>
                    <h1 class="font-display text-4xl font-bold tracking-tight">Pilih server Discord yang mau kamu manage.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-sky-50/90">
                        Hanya server yang bot-nya sudah dikenal sistem dan yang bisa kamu kelola yang ditampilkan di sini.
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($guilds as $guild)
                <form method="POST" action="{{ route('guilds.select.store', $guild['id']) }}" class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                    @csrf
                    <div class="flex items-center gap-4">
                        @if ($guild['icon_url'])
                            <img src="{{ $guild['icon_url'] }}" alt="{{ $guild['name'] }}" class="h-14 w-14 rounded-2xl object-cover">
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 text-lg font-bold text-zinc-700">
                                {{ strtoupper(substr($guild['name'], 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-zinc-950">{{ $guild['name'] }}</h2>
                            <p class="mt-1 text-sm text-zinc-500">Guild ID: {{ $guild['id'] }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($guild['owner'])
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Owner</span>
                        @endif
                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">Manageable</span>
                    </div>
                    <button class="mt-5 w-full rounded-2xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white">
                        Manage server ini
                    </button>
                </form>
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-[1.75rem] border border-dashed border-zinc-300 bg-white px-6 py-10 text-center text-sm text-zinc-500 shadow-sm">
                    Belum ada server yang cocok. Pastikan bot sudah masuk ke server dan guild itu sudah terdaftar di sistem.
                </div>
            @endforelse
        </section>
    </div>
</x-layouts::app>
