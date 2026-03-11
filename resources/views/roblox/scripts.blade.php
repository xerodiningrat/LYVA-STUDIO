<x-layouts::app :title="__('Roblox Scripts')">
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-[linear-gradient(135deg,_#1f2937_0%,_#14532d_50%,_#65a30d_100%)] p-6 text-white shadow-[0_24px_90px_rgba(20,30,20,0.22)]">
            <div class="space-y-4">
                <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-lime-100">
                    Roblox Script Library
                </span>
                <div>
                    <h1 class="font-display text-4xl font-bold tracking-tight">Ambil script Roblox langsung dari web tanpa buka folder project.</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-lime-50/90">
                        Script di bawah sudah memakai template project ini. Kalau `APP_URL` dan `ROBLOX_INGEST_TOKEN` sudah diisi, file download akan otomatis berisi endpoint dan token yang sama dengan Laravel.
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($scripts as $script)
                <article class="rounded-[1.5rem] border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ $script['filename'] }}</p>
                    <h2 class="mt-2 font-display text-2xl font-bold text-zinc-950">{{ $script['label'] }}</h2>
                    <p class="mt-3 text-sm leading-7 text-zinc-600">{{ $script['description'] }}</p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('roblox.scripts.show', $script['slug']) }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 hover:bg-zinc-950 hover:text-white">
                            Preview
                        </a>
                        <a href="{{ route('roblox.scripts.download', $script['slug']) }}" class="rounded-full bg-lime-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-lime-700">
                            Download
                        </a>
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</x-layouts::app>
