@props(['variant' => 'default', 'compact' => false])

@php
    $isLight = $variant === 'light';
    $textClass = $isLight ? 'text-white' : 'text-slate-900';
    $accentClass = $isLight ? 'text-teal-200' : 'text-teal-600';
@endphp
<span
    {{ $attributes->merge(['class' => "inline-flex items-center gap-3 text-xl font-extrabold tracking-tight {$textClass}"]) }}>
    <span
        class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-teal-400 to-teal-700 text-white shadow-lg shadow-teal-700/20">
        <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <path d="M6 15.5 16 7l10 8.5v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-10Z" fill="currentColor" fill-opacity=".18"
                stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
            <path d="m11 19 3.2 3.2L22 14.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
        <span class="absolute right-1 top-1 h-1.5 w-1.5 rounded-full bg-white/90"></span>
    </span>
    @unless ($compact)
        <span>Home<span class="{{ $accentClass }}">Tech</span></span>
    @endunless
</span>
