<header class="studio-topbar" data-studio-hover>
    <div class="studio-brand">
        <div class="studio-brand-mark">{{ $mark ?? 'LY' }}</div>
        <div>
            <h1>{{ $brandTitle ?? 'LYVA Studio' }}</h1>
            <p>{{ $brandCopy ?? 'Workspace control center yang lebih rapi dan modern.' }}</p>
        </div>
    </div>

    <nav class="studio-nav">
        @foreach (($navLinks ?? []) as $link)
            <a href="{{ $link['href'] }}" @class(['studio-nav-active' => ($activeHref ?? null) === $link['href']])>{{ $link['label'] }}</a>
        @endforeach
        @if (! empty($ctaHref ?? null) && ! empty($ctaLabel ?? null))
            <a href="{{ $ctaHref }}" class="studio-nav-active">{{ $ctaLabel }}</a>
        @endif
    </nav>
</header>
