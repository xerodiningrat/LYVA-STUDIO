<x-layouts::app :title="__('Discord Setup')">
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-[linear-gradient(135deg,_#0f172a_0%,_#1d4ed8_45%,_#38bdf8_100%)] p-6 text-white shadow-[0_24px_90px_rgba(15,23,42,0.25)]">
            <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                <div class="space-y-4">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-100">
                        Discord Command Setup
                    </span>
                    <div>
                        <h1 class="font-display text-4xl font-bold tracking-tight">Satu halaman untuk invite bot, register command, dan cek endpoint interaction.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-sky-50/90">
                            Pakai global commands kalau mau bot langsung usable di server mana pun setelah di-invite. Pakai guild mode hanya kalau perlu testing cepat.
                        </p>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-white/15 bg-white/10 p-5 backdrop-blur">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-100">Quick paths</p>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                            1. Invite bot pakai URL di bawah.
                        </div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                            2. Isi Interaction Endpoint URL di Discord Developer Portal.
                        </div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                            3. Jalankan register command global.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
            <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Config status</p>
                    <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Environment checks</h2>
                </div>

                <div class="mt-5 space-y-3">
                    @foreach ($setupChecks as $check)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.35rem] border border-zinc-200 p-4">
                            <div>
                                <h3 class="font-semibold text-zinc-950">{{ $check['label'] }}</h3>
                                <p class="mt-1 break-all text-sm text-zinc-500">{{ $check['value'] }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $check['ready'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                {{ $check['ready'] ? 'Ready' : 'Check' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Links</p>
                    <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Invite and interaction URLs</h2>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                        <p class="text-sm font-semibold text-zinc-950">Invite URL</p>
                        <p class="mt-2 break-all text-sm leading-7 text-zinc-600">{{ $inviteUrl ?? 'Isi DISCORD_APPLICATION_ID dulu.' }}</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-zinc-200 bg-zinc-50 p-4">
                        <p class="text-sm font-semibold text-zinc-950">Interactions Endpoint URL</p>
                        <p class="mt-2 break-all text-sm leading-7 text-zinc-600">{{ $interactionUrl ?? 'Isi APP_URL dulu.' }}</p>
                        <p class="mt-2 text-xs text-zinc-500">Discord butuh URL publik. `localhost` atau `127.0.0.1` tidak bisa dipanggil dari Discord.</p>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Terminal steps</p>
                    <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Commands to run</h2>
                </div>

                <div class="mt-5 space-y-3">
                    @foreach ($commands as $command)
                        <div class="rounded-[1.35rem] border border-zinc-200 bg-stone-950 px-4 py-3 font-mono text-sm text-stone-100">
                            {{ $command }}
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-[1.75rem] border border-zinc-200 bg-white p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">Registered command set</p>
                    <h2 class="mt-1 font-display text-2xl font-bold text-zinc-950">Slash commands yang sudah disiapkan</h2>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach ($features as $feature)
                        <div class="rounded-[1.25rem] border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-semibold text-zinc-800">
                            {{ $feature }}
                        </div>
                    @endforeach
                </div>
            </article>
        </section>
    </div>
</x-layouts::app>
