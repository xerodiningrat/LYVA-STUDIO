<x-layouts::app :title="$script['label']">
    <div class="space-y-6">
        <section class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ $script['filename'] }}</p>
                <h1 class="mt-2 font-display text-4xl font-bold text-zinc-950">{{ $script['label'] }}</h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-zinc-600">{{ $script['description'] }}</p>
            </div>

            <a href="{{ route('roblox.scripts.download', $script['slug']) }}" class="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800">
                Download File
            </a>
        </section>

        <section class="overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-stone-950 shadow-sm">
            <pre class="overflow-x-auto p-5 text-sm leading-7 text-stone-100"><code>{{ $content }}</code></pre>
        </section>
    </div>
</x-layouts::app>
