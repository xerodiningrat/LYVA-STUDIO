@php
    use Illuminate\Support\Str;

    $title = __('Dashboard');
    $activeGuild = $managedGuild ?? null;
    $serverName = $activeGuild['name'] ?? 'Belum pilih server';
    $guildId = $activeGuild['id'] ?? 'Guild belum dipilih';
    $trackedGamesCount = is_numeric($stats[0]['value'] ?? null) ? (int) $stats[0]['value'] : 0;
    $webhookCount = is_numeric($stats[1]['value'] ?? null) ? (int) $stats[1]['value'] : 0;
    $alertCount = is_numeric($stats[2]['value'] ?? null) ? (int) $stats[2]['value'] : 0;
    $reportCount = is_numeric($stats[3]['value'] ?? null) ? (int) $stats[3]['value'] : 0;
    $raceCount = is_numeric($stats[4]['value'] ?? null) ? (int) $stats[4]['value'] : 0;
    $wallet = $walletSummary ?? [];
    $formatIdr = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $grossSalesAmount = $formatIdr($wallet['grossSalesTotal'] ?? 0);
    $adminFeeAmount = $formatIdr($wallet['adminFeeTotal'] ?? 0);
    $netSalesAmount = $formatIdr($wallet['netSalesTotal'] ?? 0);
    $frozenBalanceAmount = $formatIdr($wallet['frozenBalance'] ?? 0);
    $availableBalanceAmount = $formatIdr($wallet['availableBalance'] ?? 0);
    $minimumWithdrawalAmount = max(1, (int) (($wallet['withdrawalFee'] ?? 2500) + 1));
    $maximumWithdrawalAmount = max($minimumWithdrawalAmount, (int) ($wallet['availableBalance'] ?? 0));
    $healthScore = max(18, min(98, 92 - ($alertCount * 6) - ($reportCount * 3) + ($webhookCount * 2)));
    $user = auth()->user();
    $userName = $user?->name ?: 'Workspace User';
    $initials = collect(preg_split('/\s+/', trim($userName)) ?: [])->filter()->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->take(2)->implode('');
    $cards = [
        ['title' => 'Roblox Discord Ops', 'subtitle' => 'Control room utama untuk server aktif.', 'value' => $healthScore.'%', 'progress' => $healthScore, 'bg' => '#dbeafe', 'accent' => '#2563eb', 'meta' => $serverName, 'footer' => 'Guild '.$guildId],
        ['title' => 'Tracked experiences', 'subtitle' => 'Universe dan place yang terhubung.', 'value' => (string) $trackedGamesCount, 'progress' => min(100, 18 + ($trackedGamesCount * 12)), 'bg' => '#fee2e2', 'accent' => '#ef4444', 'meta' => 'Roblox', 'footer' => $stats[0]['hint'] ?? 'Tracked games'],
        ['title' => 'Active webhooks', 'subtitle' => 'Webhook Discord yang siap kirim event.', 'value' => (string) $webhookCount, 'progress' => min(100, 18 + ($webhookCount * 16)), 'bg' => '#ede9fe', 'accent' => '#7c3aed', 'meta' => 'Discord', 'footer' => $stats[1]['hint'] ?? 'Active webhooks'],
        ['title' => 'VIP Title Wallet', 'subtitle' => 'Gross '.$grossSalesAmount.' | siap tarik '.$availableBalanceAmount.'.', 'value' => $availableBalanceAmount, 'progress' => min(100, max(10, (int) round((($wallet['availableBalance'] ?? 0) / max(1, ($wallet['netSalesTotal'] ?? 1))) * 100))), 'bg' => '#dcfce7', 'accent' => '#16a34a', 'meta' => 'Wallet', 'footer' => 'VIP Title Wallet'],
        ['title' => 'Alert pressure', 'subtitle' => 'Insiden operasional yang masih terbuka.', 'value' => (string) $alertCount, 'progress' => min(100, max(10, $alertCount * 20)), 'bg' => '#ffe4e6', 'accent' => '#e11d48', 'meta' => 'Ops', 'footer' => $stats[2]['hint'] ?? 'Open alerts'],
        ['title' => 'Player and bug reports', 'subtitle' => 'Queue laporan user dan bug terbaru.', 'value' => (string) $reportCount, 'progress' => min(100, max(10, $reportCount * 18)), 'bg' => '#cffafe', 'accent' => '#0891b2', 'meta' => 'Support', 'footer' => $stats[3]['hint'] ?? 'Pending reports'],
        ['title' => 'Community race desk', 'subtitle' => 'Event balap yang sedang buka registrasi.', 'value' => (string) $raceCount, 'progress' => min(100, max(10, $raceCount * 22)), 'bg' => '#fae8ff', 'accent' => '#a21caf', 'meta' => 'Community', 'footer' => $stats[4]['hint'] ?? 'Race events'],
    ];
    $activityItems = collect()
        ->merge(collect($alerts)->map(fn ($alert) => ['initials' => 'AL', 'name' => 'Alert: '.$alert->title, 'line' => Str::limit((string) $alert->message, 110), 'time' => optional($alert->occurred_at)->diffForHumans() ?? 'baru saja']))
        ->merge(collect($reports)->map(fn ($report) => ['initials' => 'RP', 'name' => 'Report: '.$report->reported_player_name, 'line' => Str::limit((string) $report->summary, 110), 'time' => optional($report->created_at)->diffForHumans() ?? 'baru saja']))
        ->merge(collect($webhooks)->map(fn ($webhook) => ['initials' => 'WH', 'name' => 'Webhook: '.$webhook->name, 'line' => 'Channel '.$webhook->channel_name.' | '.($webhook->is_active ? 'active' : 'paused'), 'time' => optional($webhook->updated_at)->diffForHumans() ?? 'baru saja']))
        ->merge(collect($wallet['recentWithdrawals'] ?? [])->map(fn ($withdrawal) => ['initials' => 'WD', 'name' => 'Withdrawal: '.$formatIdr($withdrawal['grossAmount'] ?? 0), 'line' => 'Status '.($withdrawal['status'] ?? 'unknown').' | net '.$formatIdr($withdrawal['netAmount'] ?? 0), 'time' => optional($withdrawal['requestedAt'] ?? null)->diffForHumans() ?? 'baru saja']))
        ->take(7)
        ->values();
@endphp

<x-layouts::app :title="$title">
    <style>
        @import url("https://fonts.bunny.net/css?family=dm-sans:400,500,700|space-grotesk:500,700");
        .portfolio-dashboard{--app-bg:#111827;--panel:#1f2937;--panel-2:#172131;--text:#fff;--muted:rgba(255,255,255,.76);--line:rgba(255,255,255,.08);--soft:rgba(255,255,255,.06);--hover:rgba(195,207,244,.16);--active:rgba(195,207,244,.2);width:100%;min-height:calc(100vh - 2rem);background:radial-gradient(circle at top left,rgba(59,130,246,.15),transparent 26%),radial-gradient(circle at 100% 0%,rgba(16,185,129,.12),transparent 22%),var(--app-bg);border-radius:32px;overflow:hidden;color:var(--text);font-family:"DM Sans",sans-serif;box-shadow:0 24px 60px rgba(2,6,23,.35)}
        .portfolio-dashboard.is-light{--app-bg:#edf3fb;--panel:#fff;--panel-2:#f8fbff;--text:#1f1c2e;--muted:#60697b;--line:#e5eaf2;--soft:rgba(31,28,46,.04);--hover:#dbe4ff;--active:#1f1c2e}
        .portfolio-dashboard *{box-sizing:border-box}.dash-app{display:flex;flex-direction:column;min-height:calc(100vh - 2rem)}
        .dash-header{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:20px 24px;border-bottom:1px solid var(--line)}
        .dash-header-left,.dash-header-right{display:flex;align-items:center;gap:.75rem}.dash-header-left{flex:1 1 auto;min-width:0}
        .dash-icon{width:28px;height:3px;border-radius:999px;background:linear-gradient(90deg,#3b82f6,#0ea5e9);position:relative;display:inline-block;flex-shrink:0}.dash-icon:before,.dash-icon:after{content:"";position:absolute;left:50%;transform:translateX(-50%);width:14px;height:3px;border-radius:999px;background:currentColor;color:var(--text)}.dash-icon:before{top:-7px}.dash-icon:after{bottom:-7px}
        .dash-name{margin:0 .8rem 0 .2rem;font:700 1.2rem/1 "Space Grotesk",sans-serif;color:var(--text);flex-shrink:0}
        .search-wrap{display:flex;align-items:center;gap:.7rem;height:46px;max-width:520px;width:100%;padding:0 14px 0 18px;border-radius:999px;background:var(--panel);border:1px solid var(--line);box-shadow:0 10px 28px rgba(15,23,42,.18)}
        .search-wrap input{flex:1;border:0;outline:0;background:transparent;color:var(--text);font:inherit}.search-wrap input::placeholder{color:var(--muted)}
        .icon-btn,.add-btn{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:0;border-radius:50%;background:var(--soft);color:var(--text);text-decoration:none;transition:transform .18s ease}.icon-btn:hover,.add-btn:hover,.profile-btn:hover{transform:translateY(-2px)}
        .add-btn{background:linear-gradient(135deg,#2563eb,#0ea5e9);color:#fff;box-shadow:0 12px 24px rgba(37,99,235,.24)}
        .profile-btn{display:flex;align-items:center;gap:.7rem;padding:6px 12px 6px 6px;border:0;background:transparent;border-left:1px solid var(--line);color:var(--text)}
        .profile-avatar{width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1d4ed8,#14b8a6);font-size:.88rem;font-weight:700;color:#fff}
        .dash-content{display:flex;height:100%;overflow:hidden;padding:18px 24px 24px 0}.dash-sidebar{padding:26px 16px;display:flex;flex-direction:column;align-items:center;gap:.85rem}
        .dash-sidebar a{position:relative;display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:16px;background:var(--soft);color:var(--text);text-decoration:none;transition:.2s}
        .dash-sidebar a:hover,.dash-sidebar a:focus-visible{background:var(--hover);color:#fff}
        .dash-sidebar a.active{background:var(--active);color:#fff;box-shadow:0 10px 24px rgba(31,28,46,.2)}
        .dash-sidebar a::after{content:attr(data-label);position:absolute;left:calc(100% + 12px);top:50%;transform:translateY(-50%) translateX(-6px);padding:8px 12px;border-radius:999px;background:rgba(2,6,23,.92);border:1px solid rgba(255,255,255,.08);color:#fff;font-size:.76rem;font-weight:700;letter-spacing:.02em;white-space:nowrap;opacity:0;pointer-events:none;transition:.2s;box-shadow:0 12px 30px rgba(2,6,23,.28);z-index:6}
        .dash-sidebar a:hover::after,.dash-sidebar a:focus-visible::after{opacity:1;transform:translateY(-50%) translateX(0)}
        .dash-sidebar-sep{width:24px;height:1px;background:var(--line);margin:.2rem 0 .1rem}
        .projects-section,.messages-section{background:var(--panel);border-radius:30px;box-shadow:0 20px 55px rgba(15,23,42,.16)}
        .projects-section{flex:2;padding:30px 30px 0;display:flex;flex-direction:column;overflow:hidden}.messages-section{flex:1;margin-left:24px;position:relative;overflow:auto;padding-bottom:24px}
        .section-header,.section-line{display:flex;justify-content:space-between;align-items:center;gap:1rem}.section-header{margin-bottom:24px}.section-header p{margin:0;font-size:1.45rem;font-weight:700;color:var(--text)}.section-header .time{font-size:1rem;font-weight:500;color:var(--muted)}
        .section-line{padding-bottom:28px}.projects-status{display:flex;flex-wrap:wrap;gap:1rem}.item-status{position:relative;padding-right:18px;display:flex;flex-direction:column}.item-status:not(:last-child):after{content:"";position:absolute;right:0;top:50%;transform:translateY(-50%);width:1px;height:60%;background:var(--line)}.status-number{font-size:1.4rem;font-weight:700}.status-type{margin-top:.2rem;font-size:.88rem;color:var(--muted)}
        .view-actions{display:flex;gap:.5rem}.view-btn{display:flex;align-items:center;justify-content:center;width:38px;height:38px;border:0;border-radius:12px;background:transparent;color:var(--text)}.view-btn.active{background:var(--active);color:#fff}.view-btn:not(.active):hover{background:var(--hover);color:#fff}
        .project-boxes{margin:0 -8px;padding-bottom:28px;overflow-y:auto}.project-boxes.jsGridView{display:flex;flex-wrap:wrap}.project-boxes.jsGridView .project-box-wrapper{width:33.3333%}.project-boxes.jsListView .project-box-wrapper{width:100%}.project-box-wrapper{padding:8px}.project-box{display:flex;flex-direction:column;min-height:260px;padding:18px;border-radius:28px;background:var(--project-bg,#e2e8f0);color:#111827;box-shadow:0 14px 32px rgba(15,23,42,.08)}.project-box-header,.project-box-footer{display:flex;justify-content:space-between;align-items:center;gap:1rem}.project-box-header{margin-bottom:18px}.project-box-header span{font-size:.78rem;opacity:.72}.project-title{margin:0;font-size:1.05rem;line-height:1.35;font-weight:700}.project-subtitle{margin:.35rem 0 0;font-size:.88rem;line-height:1.7;opacity:.82}
        .box-progress-wrapper{margin-top:16px}.box-progress-header{margin:0;font-size:.82rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.8}.box-progress-bar{width:100%;height:7px;margin:10px 0 8px;background:rgba(255,255,255,.7);border-radius:999px;overflow:hidden}.box-progress{display:block;height:100%;background:var(--progress-accent,#2563eb)}.box-progress-percentage{margin:0;text-align:right;font-size:.82rem;font-weight:700}.project-box-footer{margin-top:auto;padding-top:16px;border-top:1px solid rgba(255,255,255,.48)}
        .participants{display:flex;align-items:center}.participant-chip{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;margin-right:-8px;font-size:.7rem;font-weight:700;color:#fff;background:rgba(15,23,42,.46);border:2px solid rgba(255,255,255,.6)}.participant-add{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;margin-left:10px;border-radius:50%;background:rgba(255,255,255,.66);color:var(--progress-accent,#2563eb)}.project-footer-label{padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.66);font-size:.76rem;font-weight:700;color:var(--progress-accent,#2563eb)}
        .project-link{display:inline-flex;align-items:center;gap:.45rem;margin-top:.9rem;font-size:.85rem;font-weight:700;color:inherit;text-decoration:none}.mini-form{display:grid;gap:.7rem;margin-top:.9rem}.mini-form input{padding:.9rem 1rem;border:0;border-radius:14px;background:rgba(255,255,255,.7);font:inherit;color:#111827}.mini-form button{padding:.92rem 1rem;border:0;border-radius:14px;background:rgba(15,23,42,.88);font:inherit;font-weight:700;color:#fff}.mini-help,.flash-note{font-size:.78rem;line-height:1.6;opacity:.82}.flash-note{padding:.85rem 1rem;border-radius:14px;background:rgba(255,255,255,.6)}.flash-note.error{background:rgba(225,29,72,.16);color:#881337}
        .messages-section .section-header{position:sticky;top:0;padding:30px 24px 18px;background:var(--panel);z-index:1}.summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem;padding:0 24px 18px}.summary-card{padding:14px;border-radius:18px;background:var(--panel-2)}.summary-card strong{display:block;margin-bottom:.3rem;font-size:1rem;color:var(--text)}.summary-card span{font-size:.82rem;line-height:1.6;color:var(--muted)}
        .messages{padding-bottom:12px}.message-box{display:flex;gap:14px;padding:16px;border-top:1px solid var(--line)}.message-box:hover{background:var(--panel-2)}.message-avatar{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#1d4ed8,#38bdf8);font-size:.78rem;font-weight:700;color:#fff;flex-shrink:0}.message-content{width:100%;min-width:0}.message-header{display:flex;justify-content:space-between;align-items:center;gap:1rem}.message-name{margin:0;font-size:.96rem;font-weight:700;color:var(--text)}.message-line{margin:8px 0;font-size:.86rem;line-height:1.7;color:var(--muted)}.message-line.time{text-align:right;margin-bottom:0;font-size:.75rem}.messages-close,.messages-btn{display:none}
        @media (max-width:1180px){.project-boxes.jsGridView .project-box-wrapper{width:50%}}
        @media (max-width:880px){.messages-section{position:absolute;top:0;right:0;z-index:4;width:min(100%,420px);height:100%;margin-left:0;opacity:0;transform:translateX(100%);transition:.3s}.messages-section.show{opacity:1;transform:translateX(0)}.messages-close,.messages-btn{display:flex}.messages-close{position:absolute;top:12px;right:12px;z-index:5;width:38px;height:38px;border:0;border-radius:50%;background:var(--soft);color:var(--text)}.messages-btn{position:absolute;right:0;top:72px;align-items:center;justify-content:center;width:40px;height:40px;border:0;border-radius:12px 0 0 12px;background:var(--soft);color:var(--text)}}
        @media (max-width:720px){.dash-header{padding:16px;flex-wrap:wrap}.dash-header-left,.dash-header-right{width:100%}.dash-name,.profile-btn span{display:none}.search-wrap{max-width:none}.dash-header-right{justify-content:flex-end}}
        @media (max-width:520px){.portfolio-dashboard{min-height:calc(100vh - 1rem);border-radius:24px}.dash-sidebar,.dash-icon{display:none}.dash-content{padding:12px}.projects-section{padding:22px 16px 0}.section-header p,.section-header .time{font-size:1rem}.section-line{flex-direction:column;align-items:flex-start}.project-boxes.jsGridView .project-box-wrapper{width:100%}.summary-grid{grid-template-columns:1fr}.messages-btn{top:56px}}
    </style>

    <div class="portfolio-dashboard is-dark" id="portfolioDashboard">
        <div class="dash-app">
            <div class="dash-header">
                <div class="dash-header-left">
                    <span class="dash-icon"></span>
                    <p class="dash-name">Portfolio</p>
                    <div class="search-wrap">
                        <input id="dashboardSearch" type="text" placeholder="Search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
                    </div>
                </div>
                <div class="dash-header-right">
                    <button class="icon-btn" id="themeSwitch" title="Switch Theme"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" width="22" height="22" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path></svg></button>
                    <a href="{{ route('vip-title.setup') }}" wire:navigate class="add-btn" title="VIP Title Setup"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg></a>
                    <button class="icon-btn" title="Workspace pulse"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" /></svg></button>
                    <button class="profile-btn" title="{{ $userName }}"><span class="profile-avatar">{{ $initials !== '' ? $initials : 'LY' }}</span><span>{{ $userName }}</span></button>
                </div>
                <button class="messages-btn" id="messagesBtn"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" /></svg></button>
            </div>

            <div class="dash-content">
                <div class="dash-sidebar">
                    <a href="#overview" class="active" data-label="Overview" title="Overview"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg></a>
                    <a href="{{ route('guilds.select') }}" wire:navigate data-label="Pilih Server" title="Pilih Server"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="6" rx="2" /><rect x="3" y="14" width="18" height="6" rx="2" /><path d="M7 7h.01" /><path d="M7 17h.01" /></svg></a>
                    <a href="{{ route('discord.setup') }}" wire:navigate data-label="Discord Setup" title="Discord Setup"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" /></svg></a>
                    <a href="{{ route('vip-title.setup') }}" wire:navigate data-label="VIP Title + API Key" title="VIP Title Setup / API Key Roblox"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 14a5 5 0 1 1 0-10h7" /><path d="M14 10a5 5 0 1 1 0 10H7" /><path d="M8 12h8" /></svg></a>
                    <a href="{{ route('roblox.scripts.index') }}" wire:navigate data-label="Roblox Scripts" title="Roblox Scripts"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 18 22 12 16 6" /><path d="M8 6 2 12l6 6" /></svg></a>
                    <div class="dash-sidebar-sep" aria-hidden="true"></div>
                    <a href="#wallet-card" data-label="VIP Title Wallet" title="VIP Title Wallet"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"></path><path d="M16 12h5"></path><path d="M19 9v6"></path></svg></a>
                    <a href="#activity-panel" data-label="Activity" title="Activity"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18" /><path d="M18 17V9" /><path d="M13 17V5" /><path d="M8 17v-3" /></svg></a>
                    <a href="{{ route('profile.edit') }}" wire:navigate data-label="Settings Akun" title="Settings Akun"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Z" /><path d="M20 21a8 8 0 0 0-16 0" /></svg></a>
                </div>

                <div class="projects-section" id="overview">
                    <div class="section-header"><p>Roblox Discord Ops</p><p class="time">{{ now()->translatedFormat('F d') }}</p></div>
                    <div class="section-line">
                        <div class="projects-status">
                            <div class="item-status"><span class="status-number">{{ $alertCount + $reportCount }}</span><span class="status-type">In Progress</span></div>
                            <div class="item-status"><span class="status-number">{{ $raceCount + collect($wallet['recentWithdrawals'] ?? [])->where('status', 'ready')->count() }}</span><span class="status-type">Upcoming</span></div>
                            <div class="item-status"><span class="status-number">{{ $trackedGamesCount + $webhookCount + $alertCount + $reportCount + $raceCount }}</span><span class="status-type">Total Projects</span></div>
                        </div>
                        <div class="view-actions">
                            <button class="view-btn list-view" title="List View"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6" /><line x1="8" y1="12" x2="21" y2="12" /><line x1="8" y1="18" x2="21" y2="18" /><line x1="3" y1="6" x2="3.01" y2="6" /><line x1="3" y1="12" x2="3.01" y2="12" /><line x1="3" y1="18" x2="3.01" y2="18" /></svg></button>
                            <button class="view-btn grid-view active" title="Grid View"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" /></svg></button>
                        </div>
                    </div>

                    <div class="project-boxes jsGridView" id="dashboardCards">
                        @foreach ($cards as $card)
                            <div class="project-box-wrapper" data-search="{{ Str::lower($card['title'].' '.$card['subtitle'].' '.$card['footer'].' '.$card['meta']) }}">
                                <div class="project-box" style="--project-bg: {{ $card['bg'] }}; --progress-accent: {{ $card['accent'] }};">
                                    <div class="project-box-header"><span>{{ $card['meta'] }}</span><button class="view-btn" type="button" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1" /><circle cx="12" cy="5" r="1" /><circle cx="12" cy="19" r="1" /></svg></button></div>
                                    <div><p class="project-title">{{ $card['title'] }}</p><p class="project-subtitle">{{ $card['subtitle'] }}</p></div>
                                    <div class="box-progress-wrapper"><p class="box-progress-header">Progress</p><div class="box-progress-bar"><span class="box-progress" style="width: {{ max(8, min(100, (int) $card['progress'])) }}%"></span></div><p class="box-progress-percentage">{{ $card['value'] }}</p></div>
                                    <div class="project-box-footer"><div class="participants"><span class="participant-chip">{{ $initials !== '' ? $initials : 'LY' }}</span><span class="participant-chip">SV</span><span class="participant-add">+</span></div><div class="project-footer-label">{{ $card['footer'] }}</div></div>
                                    @if ($card['title'] === 'VIP Title Wallet')
                                        <a href="#wallet-card" class="project-link">Request Penarikan <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></a>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div class="project-box-wrapper" data-search="request penarikan vip title wallet saldo tarik">
                            <div class="project-box" style="--project-bg:#fef3c7;--progress-accent:#d97706" id="wallet-card">
                                <div class="project-box-header"><span>Wallet Action</span><button class="view-btn" type="button" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1" /><circle cx="12" cy="5" r="1" /><circle cx="12" cy="19" r="1" /></svg></button></div>
                                <div><p class="project-title">Request Penarikan</p><p class="project-subtitle">Total jual {{ $grossSalesAmount }}, saldo siap tarik {{ $availableBalanceAmount }}, biaya tarik {{ $formatIdr($wallet['withdrawalFee'] ?? 0) }}.</p></div>
                                <div class="box-progress-wrapper"><p class="box-progress-header">Ready balance</p><div class="box-progress-bar"><span class="box-progress" style="width: {{ min(100, max(8, (int) round((($wallet['availableBalance'] ?? 0) / max(1, ($wallet['maturedBalance'] ?? 1))) * 100))) }}%"></span></div><p class="box-progress-percentage">{{ $formatIdr($wallet['availableBalance'] ?? 0) }}</p></div>
                                @if (session('wallet_status'))<div class="flash-note">{{ session('wallet_status') }}</div>@endif
                                @if ($errors->has('amount'))<div class="flash-note error">{{ $errors->first('amount') }}</div>@endif
                                <form method="POST" action="{{ route('dashboard.wallet.withdrawals.store') }}" class="mini-form">@csrf
                                    <input type="number" name="amount" min="{{ $minimumWithdrawalAmount }}" max="{{ $maximumWithdrawalAmount }}" step="1" value="{{ old('amount', max(0, (int) ($wallet['availableBalance'] ?? 0))) }}" placeholder="Contoh 50000">
                                    <button type="submit" @disabled(($wallet['availableBalance'] ?? 0) <= ($wallet['withdrawalFee'] ?? 0))>Ajukan penarikan</button>
                                    <p class="mini-help">VIP Title Wallet akan diproses 1 hari, lalu statusnya masuk siap ditarik manual.</p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="messages-section" id="activity-panel">
                    <button class="messages-close" id="messagesClose"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" /></svg></button>
                    <div class="section-header"><p>Player and bug reports</p></div>
                    <div class="summary-grid">
                        <div class="summary-card"><strong>VIP Title Wallet</strong><span>Total penjualan {{ $grossSalesAmount }}, fee admin {{ $adminFeeAmount }}, net {{ $netSalesAmount }}</span></div>
                        <div class="summary-card"><strong>Request Penarikan</strong><span>{{ count($wallet['recentWithdrawals'] ?? []) }} request terakhir | siap {{ collect($wallet['recentWithdrawals'] ?? [])->where('status', 'ready')->count() }} | beku {{ $frozenBalanceAmount }}</span></div>
                    </div>
                    <div class="messages">
                        @forelse ($activityItems as $item)
                            <div class="message-box">
                                <span class="message-avatar">{{ $item['initials'] }}</span>
                                <div class="message-content">
                                    <div class="message-header"><p class="message-name">{{ $item['name'] }}</p><button class="view-btn" type="button" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg></button></div>
                                    <p class="message-line">{{ $item['line'] }}</p>
                                    <p class="message-line time">{{ $item['time'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="message-box">
                                <span class="message-avatar">LY</span>
                                <div class="message-content">
                                    <div class="message-header"><p class="message-name">Belum ada activity</p></div>
                                    <p class="message-line">Saat alert, report, webhook, race, atau withdrawal mulai bergerak, panel ini akan otomatis terisi.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const root = document.getElementById('portfolioDashboard');
            const themeSwitch = document.getElementById('themeSwitch');
            const listView = root?.querySelector('.list-view');
            const gridView = root?.querySelector('.grid-view');
            const cards = document.getElementById('dashboardCards');
            const search = document.getElementById('dashboardSearch');
            const messagesPanel = document.getElementById('activity-panel');
            document.getElementById('messagesBtn')?.addEventListener('click', () => messagesPanel?.classList.add('show'));
            document.getElementById('messagesClose')?.addEventListener('click', () => messagesPanel?.classList.remove('show'));
            themeSwitch?.addEventListener('click', () => { root.classList.toggle('is-dark'); root.classList.toggle('is-light'); });
            listView?.addEventListener('click', () => { listView.classList.add('active'); gridView?.classList.remove('active'); cards?.classList.remove('jsGridView'); cards?.classList.add('jsListView'); });
            gridView?.addEventListener('click', () => { gridView.classList.add('active'); listView?.classList.remove('active'); cards?.classList.remove('jsListView'); cards?.classList.add('jsGridView'); });
            search?.addEventListener('input', (event) => {
                const query = String(event.target.value || '').trim().toLowerCase();
                cards?.querySelectorAll('.project-box-wrapper').forEach((card) => {
                    const haystack = String(card.getAttribute('data-search') || '').toLowerCase();
                    card.style.display = query === '' || haystack.includes(query) ? '' : 'none';
                });
            });
        })();
    </script>
</x-layouts::app>
