@php
    $title = __('Penarikan');
    $wallet = $walletSummary ?? [];
    $formatIdr = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $minimumWithdrawalAmount = max(1, (int) (($wallet['withdrawalFee'] ?? 2500) + 1));
    $maximumWithdrawalAmount = max($minimumWithdrawalAmount, (int) ($wallet['availableBalance'] ?? 0));
@endphp

<x-portfolio.shell :title="$title" active-key="withdrawals" search-placeholder="Cari riwayat penarikan, bank, rekening, status">
    <x-slot:head>
        <style>
            :root {
                --studio-accent: #7ed5ff;
                --studio-accent-2: #8df2a6;
                --studio-accent-3: #ffc77b;
                --studio-danger: #ff8f9d;
            }

            .withdrawal-status-stack {
                display: grid;
                gap: 0.7rem;
            }

            .withdrawal-status-item {
                padding: 0.9rem 1rem;
                border-radius: 1rem;
                border: 1px solid rgba(255,255,255,.06);
                background: rgba(255,255,255,.03);
            }
        </style>
        @include('partials.studio-workspace-style')
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('dashboard.wallet.earnings') }}" wire:navigate class="portfolio-shell-action">Buka Penghasilan</a>
    </x-slot:headerActions>

    @if (session('wallet_status'))
        <section class="studio-notice" data-studio-hover>
            {{ session('wallet_status') }}
        </section>
    @endif
    @if ($errors->has('withdrawal'))
        <section class="studio-notice" data-studio-hover style="background: color-mix(in srgb, var(--studio-danger) 14%, transparent);">
            {{ $errors->first('withdrawal') }}
        </section>
    @endif

    <section class="studio-hero" data-studio-hover>
        <div class="studio-hero-grid">
            <div>
                <span class="studio-kicker">VIP Title Withdrawals</span>
                <h2>Ajukan penarikan <span>dengan data bank lengkap</span></h2>
                <p>Halaman ini khusus untuk request penarikan. Admin sekarang wajib isi nama bank, nomor rekening, dan atas nama supaya tim payout tidak perlu tanya ulang manual.</p>

                <div class="studio-stats-grid">
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Ready Balance</span>
                        <strong>{{ $formatIdr($wallet['availableBalance'] ?? 0) }}</strong>
                        <p class="studio-copy">Saldo yang sudah matang dan belum kepakai penarikan lain.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Processing</span>
                        <strong>{{ $formatIdr($wallet['processingWithdrawalBalance'] ?? 0) }}</strong>
                        <p class="studio-copy">Nominal request yang masih dalam masa proses 1 hari.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Ready Requests</span>
                        <strong>{{ $formatIdr($wallet['readyWithdrawalBalance'] ?? 0) }}</strong>
                        <p class="studio-copy">Nominal request yang sudah berubah ke status siap tarik.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Fee Tarik</span>
                        <strong>{{ $formatIdr($wallet['withdrawalFee'] ?? 0) }}</strong>
                        <p class="studio-copy">Biaya penarikan otomatis yang dipotong per request.</p>
                    </article>
                </div>
            </div>

            <aside class="studio-panel" data-studio-hover>
                <div class="studio-panel-header">
                    <div>
                        <span class="studio-label">Withdrawal Form</span>
                        <h3 style="margin-top:.75rem;">Ajukan penarikan baru</h3>
                    </div>
                    <span class="studio-pill">{{ $managedGuild['name'] ?? 'Guild aktif' }}</span>
                </div>

                <form method="POST" action="{{ route('dashboard.wallet.withdrawals.store') }}" class="studio-stack">
                    @csrf

                    <div class="studio-field">
                        <label for="amount">Nominal penarikan</label>
                        <input id="amount" class="studio-input" type="number" name="amount" min="{{ $minimumWithdrawalAmount }}" max="{{ $maximumWithdrawalAmount }}" step="1" value="{{ old('amount', max(0, (int) ($wallet['availableBalance'] ?? 0))) }}" placeholder="Contoh 50000">
                        @error('amount')
                            <small style="color:var(--studio-danger)">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="studio-field">
                        <label for="bank_code">Nama bank</label>
                        <select id="bank_code" class="studio-input" name="bank_code">
                            <option value="">Pilih bank tujuan</option>
                            @foreach (($bankOptions ?? []) as $bank)
                                <option value="{{ $bank['code'] }}" @selected(old('bank_code') === $bank['code'])>{{ $bank['label'] }}</option>
                            @endforeach
                        </select>
                        @error('bank_code')
                            <small style="color:var(--studio-danger)">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="studio-field">
                        <label for="account_number">Nomor rekening</label>
                        <input id="account_number" class="studio-input" type="text" name="account_number" value="{{ old('account_number') }}" placeholder="1234567890">
                        @error('account_number')
                            <small style="color:var(--studio-danger)">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="studio-field">
                        <label for="account_holder_name">Atas nama rekening</label>
                        <input id="account_holder_name" class="studio-input" type="text" name="account_holder_name" value="{{ old('account_holder_name', auth()->user()?->name) }}" placeholder="Nama pemilik rekening">
                        @error('account_holder_name')
                            <small style="color:var(--studio-danger)">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="studio-actions">
                        <button type="submit" class="studio-button" @disabled(($wallet['availableBalance'] ?? 0) <= ($wallet['withdrawalFee'] ?? 0))>Ajukan penarikan</button>
                    </div>
                    <p class="studio-help">Nominal dipotong biaya tarik {{ $formatIdr($wallet['withdrawalFee'] ?? 0) }}. Setelah request dibuat, status processing berjalan 1 hari lalu berubah menjadi ready.</p>
                </form>
            </aside>
        </div>
    </section>

    <section class="studio-panel" data-studio-hover>
        <div class="studio-panel-header">
            <div>
                <span class="studio-label">Status Guide</span>
                <h3 style="margin-top:.75rem;">Arti status penarikan</h3>
            </div>
            <span class="studio-pill">3 Step</span>
        </div>

        <div class="withdrawal-status-stack">
            <article class="withdrawal-status-item">
                <strong>Processing</strong>
                <p class="studio-copy" style="margin-top:.35rem;">Request baru dibuat dan sedang menunggu masa proses 1 hari.</p>
            </article>
            <article class="withdrawal-status-item">
                <strong>Ready</strong>
                <p class="studio-copy" style="margin-top:.35rem;">Request sudah siap dibayar manual. Di tahap ini tombol <span class="studio-inline-code">Tandai Sudah Dibayar</span> akan muncul.</p>
            </article>
            <article class="withdrawal-status-item">
                <strong>Completed</strong>
                <p class="studio-copy" style="margin-top:.35rem;">Dana sudah dibayarkan ke rekening tujuan dan request dinyatakan selesai.</p>
            </article>
        </div>
    </section>

    <section class="studio-panel" data-studio-hover>
        <div class="studio-panel-header">
            <div>
                <span class="studio-label">Withdrawal History</span>
                <h3 style="margin-top:.75rem;">Riwayat penarikan terbaru</h3>
                <p class="studio-copy" style="margin-top:.45rem;">Riwayat request sekarang menampilkan detail bank tujuan supaya payout lebih rapi dan mudah dilacak.</p>
            </div>
            <span class="studio-pill">{{ count($wallet['recentWithdrawals'] ?? []) }} Request</span>
        </div>

        <div class="studio-table-wrap">
            <table class="studio-table">
                <thead>
                    <tr>
                        <th>Requested</th>
                        <th>Gross</th>
                        <th>Net</th>
                        <th>Bank</th>
                        <th>Atas Nama</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($wallet['recentWithdrawals'] ?? []) as $withdrawal)
                        <tr>
                            <td>{{ optional($withdrawal['requestedAt'] ?? null)?->diffForHumans() ?? '-' }}</td>
                            <td>{{ $formatIdr($withdrawal['grossAmount'] ?? 0) }}</td>
                            <td>{{ $formatIdr($withdrawal['netAmount'] ?? 0) }}</td>
                            <td>
                                {{ $withdrawal['bankName'] ?? '-' }}
                                @if (! empty($withdrawal['accountNumber']))
                                    <div class="studio-muted" style="margin-top:.3rem;">{{ $withdrawal['accountNumber'] }}</div>
                                @endif
                            </td>
                            <td>{{ $withdrawal['accountHolderName'] ?? '-' }}</td>
                            <td>
                                <span class="studio-badge {{ ($withdrawal['status'] ?? '') === 'completed' ? 'studio-badge-ok' : (($withdrawal['status'] ?? '') === 'ready' ? 'studio-badge-warn' : 'studio-badge-off') }}">
                                    {{ $withdrawal['status'] ?? '-' }}
                                </span>
                                @if (($withdrawal['status'] ?? '') === 'processing' && ! empty($withdrawal['readyAt']))
                                    <div class="studio-muted" style="margin-top:.35rem;">Siap {{ optional($withdrawal['readyAt'])?->diffForHumans() }}</div>
                                @elseif (($withdrawal['status'] ?? '') === 'completed' && ! empty($withdrawal['completedAt']))
                                    <div class="studio-muted" style="margin-top:.35rem;">Dibayar {{ optional($withdrawal['completedAt'])?->diffForHumans() }}</div>
                                @endif
                            </td>
                            <td>
                                @if (($withdrawal['status'] ?? '') === 'ready')
                                    <form method="POST" action="{{ route('dashboard.wallet.withdrawals.complete', $withdrawal['id']) }}">
                                        @csrf
                                        <button type="submit" class="studio-button-ghost">Tandai Sudah Dibayar</button>
                                    </form>
                                @else
                                    <span class="studio-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="studio-muted" style="text-align:center;">Belum ada request penarikan untuk server ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-portfolio.shell>
