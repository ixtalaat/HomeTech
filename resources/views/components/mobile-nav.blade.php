@props(['active' => null])

@php
    $link = fn (string $key): string => 'block rounded-xl px-4 py-2.5 text-sm font-bold transition '.($active === $key ? 'text-teal-700' : 'text-slate-600 hover:bg-teal-50 hover:text-teal-700');
@endphp

<details class="relative sm:hidden">
    <summary class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-200 text-slate-700 transition hover:border-teal-300 hover:text-teal-700 [&::-webkit-details-marker]:hidden" aria-label="{{ __('Menu') }}">
        <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
    </summary>
    <div class="absolute end-0 top-full z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
        <a href="{{ route('about') }}" class="{{ $link('about') }}">{{ __('About Us') }}</a>
        <a href="{{ route('contact.create') }}" class="{{ $link('contact') }}">{{ __('Contact Us') }}</a>
        <a href="{{ route('services.index') }}" class="{{ $link('services') }}">{{ __('Browse Services') }}</a>
        @auth
            <a href="{{ route('dashboard') }}" class="{{ $link('dashboard') }}">{{ __('Dashboard') }}</a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-2">
                @csrf
                <button type="submit" class="primary-button w-full">{{ __('Log out') }} <span aria-hidden="true">→</span></button>
            </form>
        @else
            <a href="{{ route('login') }}" class="{{ $link('login') }}">{{ __('Sign in') }}</a>
            <a href="{{ route('register') }}" class="primary-button mt-1 w-full">{{ __('Get started') }} <span aria-hidden="true">→</span></a>
        @endauth
    </div>
</details>
