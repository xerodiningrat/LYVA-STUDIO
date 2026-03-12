@props([
    'title' => 'Dashboard',
    'activeKey' => 'dashboard',
    'searchPlaceholder' => 'Search',
])

@php
    $user = auth()->user();
    $userName = $user?->name ?: 'Workspace User';
    $userInitials = method_exists($user, 'initials') ? $user->initials() : collect(preg_split('/\s+/', trim($userName)) ?: [])->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
    $sidebarLinks = [
        [
            'key' => 'dashboard',
            'href' => route('dashboard'),
            'label' => 'Dashboard',
            'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" />',
        ],
        [
            'key' => 'guilds',
            'href' => route('guilds.select'),
            'label' => 'Pilih Server',
            'icon' => '<rect x="3" y="4" width="18" height="6" rx="2" /><rect x="3" y="14" width="18" height="6" rx="2" /><path d="M7 7h.01" /><path d="M7 17h.01" />',
        ],
        [
            'key' => 'discord',
            'href' => route('discord.setup'),
            'label' => 'Discord Setup',
            'icon' => '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />',
        ],
        [
            'key' => 'vip-title',
            'href' => route('vip-title.setup'),
            'label' => 'VIP Title + API Key',
            'icon' => '<path d="M7 14a5 5 0 1 1 0-10h7" /><path d="M14 10a5 5 0 1 1 0 10H7" /><path d="M8 12h8" />',
        ],
        [
            'key' => 'scripts',
            'href' => route('roblox.scripts.index'),
            'label' => 'Roblox Scripts',
            'icon' => '<path d="M16 18 22 12 16 6" /><path d="M8 6 2 12l6 6" />',
        ],
        [
            'key' => 'settings',
            'href' => route('profile.edit'),
            'label' => 'Settings Akun',
            'icon' => '<path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Z" /><path d="M20 21a8 8 0 0 0-16 0" />',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        {{ $head ?? '' }}
        <style>
            .portfolio-shell{--app-bg:#111827;--panel:#1f2937;--panel-2:#172131;--text:#fff;--muted:rgba(255,255,255,.76);--line:rgba(255,255,255,.08);--soft:rgba(255,255,255,.06);--hover:rgba(195,207,244,.16);--active:rgba(195,207,244,.2);min-height:100vh;background:radial-gradient(circle at top left,rgba(59,130,246,.15),transparent 26%),radial-gradient(circle at 100% 0%,rgba(16,185,129,.12),transparent 22%),var(--app-bg);color:var(--text);font-family:"DM Sans",sans-serif}
            .portfolio-shell.is-light{--app-bg:#edf3fb;--panel:#fff;--panel-2:#f8fbff;--text:#1f1c2e;--muted:#60697b;--line:#e5eaf2;--soft:rgba(31,28,46,.04);--hover:#dbe4ff;--active:#1f1c2e}
            .portfolio-shell *{box-sizing:border-box}
            .portfolio-shell-body{max-width:1800px;min-height:100vh;margin:0 auto;padding:0 0 18px}
            .portfolio-shell-header{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:20px 24px;border-bottom:1px solid var(--line)}
            .portfolio-shell-left,.portfolio-shell-right{display:flex;align-items:center;gap:.75rem}.portfolio-shell-left{flex:1 1 auto;min-width:0}
            .portfolio-shell-mark{width:28px;height:3px;border-radius:999px;background:linear-gradient(90deg,#3b82f6,#0ea5e9);position:relative;display:inline-block;flex-shrink:0}.portfolio-shell-mark:before,.portfolio-shell-mark:after{content:"";position:absolute;left:50%;transform:translateX(-50%);width:14px;height:3px;border-radius:999px;background:currentColor;color:var(--text)}.portfolio-shell-mark:before{top:-7px}.portfolio-shell-mark:after{bottom:-7px}
            .portfolio-shell-name{margin:0 .8rem 0 .2rem;font:700 1.2rem/1 "Space Grotesk",sans-serif;color:var(--text);flex-shrink:0}
            .portfolio-shell-search{display:flex;align-items:center;gap:.7rem;height:46px;max-width:520px;width:100%;padding:0 14px 0 18px;border-radius:999px;background:var(--panel);border:1px solid var(--line);box-shadow:0 10px 28px rgba(15,23,42,.18)}
            .portfolio-shell-search input{flex:1;border:0;outline:0;background:transparent;color:var(--text);font:inherit}.portfolio-shell-search input::placeholder{color:var(--muted)}
            .portfolio-shell-icon,.portfolio-shell-action{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:0;border-radius:50%;background:var(--soft);color:var(--text);text-decoration:none;transition:transform .18s ease}
            .portfolio-shell-action{padding:0 14px;border-radius:999px;width:auto;min-width:40px;font-size:.82rem;font-weight:700;background:linear-gradient(135deg,#2563eb,#0ea5e9);color:#fff;box-shadow:0 12px 24px rgba(37,99,235,.24)}
            .portfolio-shell-icon:hover,.portfolio-shell-action:hover,.portfolio-shell-profile:hover{transform:translateY(-2px)}
            .portfolio-shell-profile{display:flex;align-items:center;gap:.7rem;padding:6px 12px 6px 6px;border:0;background:transparent;border-left:1px solid var(--line);color:var(--text)}
            .portfolio-shell-avatar{width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1d4ed8,#14b8a6);font-size:.88rem;font-weight:700;color:#fff}
            .portfolio-shell-content{display:flex;gap:0;height:calc(100vh - 91px);padding:18px 24px 24px 0}
            .portfolio-shell-sidebar{padding:26px 16px;display:flex;flex-direction:column;align-items:center;gap:.85rem}
            .portfolio-shell-sidebar a{position:relative;display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:16px;background:var(--soft);color:var(--text);text-decoration:none;transition:.2s}
            .portfolio-shell-sidebar a:hover,.portfolio-shell-sidebar a:focus-visible{background:var(--hover);color:#fff}
            .portfolio-shell-sidebar a.is-active{background:var(--active);color:#fff;box-shadow:0 10px 24px rgba(31,28,46,.2)}
            .portfolio-shell-sidebar a::after{content:attr(data-label);position:absolute;left:calc(100% + 12px);top:50%;transform:translateY(-50%) translateX(-6px);padding:8px 12px;border-radius:999px;background:rgba(2,6,23,.92);border:1px solid rgba(255,255,255,.08);color:#fff;font-size:.76rem;font-weight:700;white-space:nowrap;opacity:0;pointer-events:none;transition:.2s;box-shadow:0 12px 30px rgba(2,6,23,.28);z-index:6}
            .portfolio-shell-sidebar a:hover::after,.portfolio-shell-sidebar a:focus-visible::after{opacity:1;transform:translateY(-50%) translateX(0)}
            .portfolio-shell-main{flex:1;min-width:0;border-radius:30px;background:var(--panel);box-shadow:0 20px 55px rgba(15,23,42,.16);overflow:auto;padding:24px}
            .portfolio-page-content{display:grid;gap:1.2rem}
            .portfolio-page-content .studio-shell{max-width:none;margin:0;padding:0}
            .portfolio-page-content .studio-glow-a,.portfolio-page-content .studio-glow-b,.portfolio-page-content .studio-glow-c,.portfolio-page-content .studio-topbar{display:none}
            .portfolio-page-content .studio-hero{margin-top:0}
            .portfolio-shell-mobile-trigger{display:none}
            @media (max-width:720px){.portfolio-shell-header{padding:16px;flex-wrap:wrap}.portfolio-shell-left,.portfolio-shell-right{width:100%}.portfolio-shell-name,.portfolio-shell-profile span{display:none}.portfolio-shell-search{max-width:none}.portfolio-shell-right{justify-content:flex-end}}
            @media (max-width:520px){.portfolio-shell-content{padding:12px;height:auto;min-height:calc(100vh - 91px)}.portfolio-shell-sidebar,.portfolio-shell-mark{display:none}.portfolio-shell-main{padding:16px;border-radius:24px}.portfolio-shell-body{padding-bottom:12px}}
        </style>
    </head>
    <body>
        <div class="portfolio-shell is-dark" id="portfolioShell">
            <div class="portfolio-shell-body">
                <div class="portfolio-shell-header">
                    <div class="portfolio-shell-left">
                        <span class="portfolio-shell-mark"></span>
                        <p class="portfolio-shell-name">{{ $title }}</p>
                        <div class="portfolio-shell-search">
                            <input type="text" placeholder="{{ $searchPlaceholder }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
                        </div>
                    </div>
                    <div class="portfolio-shell-right">
                        <button class="portfolio-shell-icon" id="portfolioThemeSwitch" title="Switch Theme"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" width="22" height="22" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path></svg></button>
                        {{ $headerActions ?? '' }}
                        <button class="portfolio-shell-profile" title="{{ $userName }}"><span class="portfolio-shell-avatar">{{ $userInitials !== '' ? $userInitials : 'LY' }}</span><span>{{ $userName }}</span></button>
                    </div>
                </div>
                <div class="portfolio-shell-content">
                    <div class="portfolio-shell-sidebar">
                        @foreach ($sidebarLinks as $link)
                            <a href="{{ $link['href'] }}" class="{{ $activeKey === $link['key'] ? 'is-active' : '' }}" data-label="{{ $link['label'] }}" title="{{ $link['label'] }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $link['icon'] !!}</svg>
                            </a>
                        @endforeach
                    </div>
                    <main class="portfolio-shell-main">
                        <div class="portfolio-page-content">
                            {{ $slot }}
                        </div>
                    </main>
                </div>
            </div>
        </div>
        <script>
            (() => {
                const root = document.getElementById('portfolioShell');
                const themeSwitch = document.getElementById('portfolioThemeSwitch');
                themeSwitch?.addEventListener('click', () => {
                    root.classList.toggle('is-dark');
                    root.classList.toggle('is-light');
                });
            })();
        </script>
        {{ $scripts ?? '' }}
    </body>
</html>
