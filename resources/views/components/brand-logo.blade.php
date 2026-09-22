@props(['variant' => 'default', 'compact' => false])

@php
    $isDark = in_array($variant, ['dark', 'inverse', 'on-dark'], true);
    $textClass = $isDark ? 'text-white' : 'text-slate-900';
    $accentClass = $isDark ? 'text-teal-300' : 'text-teal-600';
@endphp
<span
    {{ $attributes->merge(['class' => "inline-flex items-center gap-3 font-display text-xl font-extrabold tracking-tight {$textClass}"]) }}>
    <span
        class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 text-white shadow-md shadow-teal-700/20">
        <svg class="h-6 w-6" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <path d="M8 10 16 4l8 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                stroke-linejoin="round" />
            <path d="M19 13A4 4 0 1 0 23 17" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" />
            <path d="M16.2 19.8 9 27" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
    </span>
    @unless ($compact)
        <span>Home<span class="{{ $accentClass }}">Tech</span></span>
    @endunless
</span>
