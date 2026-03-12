@php
    $title = __('Penghasilan');
    $wallet = $walletSummary ?? [];
    $formatIdr = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
@endphp

<x-portfolio.shell :title="$title" active-key="earnings" search-placeholder="Cari transaksi, buyer, map, penghasilan">
    <x-slot:head>
        <style>
            :root {
                --studio-accent: #7ef3c8;
                --studio-accent-2: #78d8ff;
                --studio-accent-3: #ffd27f;
            }
        </style>
        @include('partials.studio-workspace-style')
    </x-slot:head>

    <x-slot:headerActions>
        <a href="{{ route('dashboard.wallet.withdrawals.index') }}" wire:navigate class="portfolio-shell-action">Buka Penarikan</a>
    </x-slot:headerActions>

    <section class="studio-hero" data-studio-hover>
        <div class="studio-hero-grid">
            <div>
                <span class="studio-kicker">VIP Title Earnings</span>
                <h2>Penghasilan server <span>dipisah lebih jelas</span></h2>
                <p>Halaman ini khusus untuk melihat penjualan, buyer, saldo beku, saldo matang, dan transaksi terbaru per server Discord yang sedang aktif.</p>

                <div class="studio-stats-grid">
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Gross Sales</span>
                        <strong>{{ $formatIdr($wallet['grossSalesTotal'] ?? 0) }}</strong>
                        <p class="studio-copy">Total semua pembayaran title yang berhasil masuk.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Admin Fee</span>
                        <strong>{{ $formatIdr($wallet['adminFeeTotal'] ?? 0) }}</strong>
                        <p class="studio-copy">Akumulasi fee admin Rp5.000 per transaksi.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Net Sales</span>
                        <strong>{{ $formatIdr($wallet['netSalesTotal'] ?? 0) }}</strong>
                        <p class="studio-copy">Total penghasilan bersih sebelum freeze dan penarikan.</p>
                    </article>
                    <article class="studio-stat" data-studio-hover>
                        <span class="studio-label">Buyers</span>
                        <strong>{{ $wallet['buyersCount'] ?? 0 }}</strong>
                        <p class="studio-copy">Jumlah buyer unik yang pernah checkout title di server ini.</p>
                    </article>
                </div>
            </div>

            <aside class="studio-stack">
                <article class="studio-card" data-studio-hover>
                    <div class="studio-panel-header">
                        <div>
                            <span class="studio-label">Balance States</span>
                            <h3 style="margin-top:.75rem;">Kondisi saldo saat ini</h3>
                        </div>
                        <span class="studio-pill">{{ $managedGuild['name'] ?? 'Guild aktif' }}</span>
                    </div>
                    <div class="studio-list-grid" style="margin-top:0;">
                        <article class="studio-card" data-studio-hover>
                            <strong>Saldo beku 2 hari</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">{{ $formatIdr($wallet['frozenBalance'] ?? 0) }}</p>
                        </article>
                        <article class="studio-card" data-studio-hover>
                            <strong>Saldo matang</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">{{ $formatIdr($wallet['maturedBalance'] ?? 0) }}</p>
                        </article>
                        <article class="studio-card" data-studio-hover>
                            <strong>Siap ditarik</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">{{ $formatIdr($wallet['availableBalance'] ?? 0) }}</p>
                        </article>
                        <article class="studio-card" data-studio-hover>
                            <strong>Transaksi paid</strong>
                            <p class="studio-copy" style="margin-top:.45rem;">{{ $wallet['paidTransactionsCount'] ?? 0 }} transaksi</p>
                        </article>
                    </div>
                </article>
            </aside>
        </div>
    </section>

    <section class="studio-panel" data-studio-hover>
        <div class="studio-panel-header">
            <div>
                <span class="studio-label">Payment Feed</span>
                <h3 style="margin-top:.75rem;">Transaksi penghasilan terbaru</h3>
                <p class="studio-copy" style="margin-top:.45rem;">Semua payment paid yang masuk untuk server ini ditampilkan di sini lengkap dengan buyer, map, gross, net, dan status freeze.</p>
            </div>
            <span class="studio-pill">{{ $wallet['paidTransactionsCount'] ?? 0 }} Paid</span>
        </div>

        <div class="studio-table-wrap">
            <table class="studio-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Buyer</th>
                        <th>Map</th>
                        <th>Gross</th>
                        <th>Net</th>
                        <th>Frozen Until</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($wallet['recentPayments'] ?? []) as $payment)
                        <tr>
                            <td><span class="studio-inline-code">{{ $payment['merchantOrderId'] }}</span></td>
                            <td>{{ $payment['buyer'] ?? '-' }}</td>
                            <td>{{ $payment['mapKey'] ?? '-' }}</td>
                            <td>{{ $formatIdr($payment['amount'] ?? 0) }}</td>
                            <td>{{ $formatIdr($payment['sellerNetAmount'] ?? 0) }}</td>
                            <td>{{ optional($payment['frozenUntil'] ?? null)?->diffForHumans() ?? 'Siap' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="studio-muted" style="text-align:center;">Belum ada penghasilan title untuk server ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-portfolio.shell>
