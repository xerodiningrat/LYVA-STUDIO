<x-layouts::app :title="__('Dashboard')">
    <div class="space-y-6">
        @unless ($hasBotTables)
            <section class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                Tabel bot Roblox belum tersedia, jadi dashboard memakai data contoh. Jalankan <code>php artisan migrate</code> untuk mengaktifkan penyimpanan data asli.
            </section>
        @endunless

        <section class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-[linear-gradient(135deg,_#18100b_0%,_#302116_50%,_#5a351c_100%)] p-6 text-white shadow-[0_24px_90px_rgba(23,17,10,0.28)]">
            <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                <div class="space-y-4">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-stone-200">
                        Roblox Discord Ops
                    </span>
                    <div>
                        <h1 class="font-display text-4xl font-bold tracking-tight">Command center untuk sales, deploy, server health, dan community moderation.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-200">
                            Dashboard ini belum terhubung ke API Roblox, tapi struktur datanya sudah disiapkan untuk webhook Discord, revenue log, incident tracking, dan player reports.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($stats as $stat)
                        <article class="rounded-[1.5rem] border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-300">{{ $stat['label'] }}</p>
                            <p class="mt-3 font-display text-4xl font-bold">{{ str_pad((string) $stat['value'], 2, '0', STR_PAD_LEFT) }}</p>
                            <p class="mt-2 text-sm leading-6 text-stone-300">{{ $stat['hint'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-6">
                <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Community race desk</p>
                            <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Race events</h2>
                        </div>
                        <span class="rounded-full bg-[#eef6ff] px-3 py-1 text-xs font-semibold text-[#215caa]">Discord admin flow</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($races as $race)
                            <div class="rounded-[1.35rem] border border-zinc-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold text-zinc-950">#{{ $race->id }} {{ $race->title }}</h3>
                                        <p class="mt-1 text-sm text-zinc-500">
                                            {{ $race->participants_count }}/{{ $race->max_players }} player • Entry {{ $race->entry_fee_robux }} R$
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">
                                        {{ str_replace('_', ' ', ucfirst($race->status)) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Game status</p>
                            <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Tracked experiences</h2>
                        </div>
                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">Sync overview</span>
                    </div>

                    <div class="mt-5 grid gap-4">
                        @foreach ($games as $game)
                            @php
                                $statusClasses = match ($game->status) {
                                    'healthy' => 'bg-emerald-100 text-emerald-700',
                                    'monitoring' => 'bg-amber-100 text-amber-700',
                                    'degraded' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-zinc-100 text-zinc-700',
                                };
                            @endphp
                            <div class="rounded-[1.5rem] border border-zinc-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-zinc-950">{{ $game->name }}</h3>
                                        <p class="mt-1 text-sm text-zinc-500">
                                            Universe {{ $game->universe_id }} • Place {{ $game->place_id }}
                                        </p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ ucfirst($game->status) }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm text-zinc-600">
                                    Last sync {{ optional($game->last_synced_at)->diffForHumans() ?? 'belum ada sync' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Incident feed</p>
                            <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Alerts and automation</h2>
                        </div>
                        <span class="rounded-full bg-[#fff1e8] px-3 py-1 text-xs font-semibold text-[#a64b13]">Discord ready</span>
                    </div>

                    <div class="mt-5 space-y-4">
                        @foreach ($alerts as $alert)
                            @php
                                $severityClasses = match ($alert->severity) {
                                    'critical' => 'border-rose-200 bg-rose-50',
                                    'warning' => 'border-amber-200 bg-amber-50',
                                    default => 'border-sky-200 bg-sky-50',
                                };
                            @endphp
                            <div class="rounded-[1.5rem] border p-4 {{ $severityClasses }}">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold text-zinc-950">{{ $alert->title }}</h3>
                                        <p class="mt-1 text-sm text-zinc-600">{{ $alert->message }}</p>
                                    </div>
                                    <div class="text-right text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">
                                        <div>{{ $alert->source }}</div>
                                        <div class="mt-1">{{ $alert->status }}</div>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs font-medium text-zinc-500">
                                    {{ optional($alert->occurred_at)->diffForHumans() ?? 'waiting for event' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>

            <div class="space-y-6">
                <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Discord webhooks</p>
                            <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Channel delivery health</h2>
                        </div>
                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">3 feeds</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($webhooks as $webhook)
                            <div class="rounded-[1.35rem] border border-zinc-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold text-zinc-950">{{ $webhook->name }}</h3>
                                        <p class="mt-1 text-sm text-zinc-500">{{ $webhook->channel_name }}</p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $webhook->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                        {{ $webhook->is_active ? 'Active' : 'Paused' }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm text-zinc-600">
                                    Last delivery {{ optional($webhook->last_delivered_at)->diffForHumans() ?? 'belum pernah kirim' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Moderation queue</p>
                            <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Player and bug reports</h2>
                        </div>
                        <span class="rounded-full bg-[#eef6ff] px-3 py-1 text-xs font-semibold text-[#215caa]">Admin panel seed</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($reports as $report)
                            <div class="rounded-[1.35rem] border border-zinc-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold text-zinc-950">{{ $report->category }} • {{ $report->reported_player_name }}</h3>
                                        <p class="mt-1 text-sm text-zinc-500">Reporter: {{ $report->reporter_name }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">{{ ucfirst($report->priority) }}</span>
                                        <span class="rounded-full bg-[#fff5e8] px-3 py-1 text-xs font-semibold text-[#a64b13]">{{ ucfirst($report->status) }}</span>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-zinc-600">{{ $report->summary }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-layouts::app>
