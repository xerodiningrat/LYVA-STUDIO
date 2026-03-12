<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $brandName = 'LYVA STUDIO';
    $pageTitle = filled($title ?? null) ? $brandName.' | '.$title : $brandName;
@endphp

<title>
    {{ $pageTitle }}
</title>

<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('lyva-studio-logo-square.png') }}">
<link rel="shortcut icon" type="image/png" href="{{ asset('lyva-studio-logo-square.png') }}">
<link rel="apple-touch-icon" href="{{ asset('lyva-studio-logo-1x1.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
