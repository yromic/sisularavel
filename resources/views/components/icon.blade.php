@props(['name', 'class' => 'h-5 w-5'])

@php
    $paths = [
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'more-horizontal' => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'upload' => '<path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'signature' => '<path d="M3 17c3 0 3-10 6-10 2 0 2 4 0 6-1 1-2 2-1 3 1 2 4-1 5-2 2-2 3 2 5 2h3"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class, 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    {!! $paths[$name] ?? $paths['search'] !!}
</svg>
