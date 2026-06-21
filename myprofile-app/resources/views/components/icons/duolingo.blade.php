@props(['variant' => 'face'])

<img
    {{ $attributes->class(['brand-icon'])->merge([
        'src' => asset($variant === 'mascot' ? 'images/duolingo-mascot.png' : 'images/duolingo-face.png').'?v=20260621',
        'alt' => '',
        'aria-hidden' => 'true',
        'width' => '512',
        'height' => '512',
        'loading' => 'eager',
        'decoding' => 'async',
    ]) }}
>
