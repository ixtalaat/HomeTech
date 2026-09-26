<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#042f2e">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>@yield('code') — {{ __('HomeTech') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-950 px-5 py-12 text-white antialiased">
    <div aria-hidden="true" class="pointer-events-none fixed -top-24 start-1/4 h-72 w-72 rounded-full bg-teal-400/20 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none fixed -bottom-24 end-1/4 h-72 w-72 rounded-full bg-sky-400/15 blur-3xl"></div>
    <main class="relative w-full max-w-lg rounded-3xl border border-white/10 bg-white/[0.06] p-8 text-center shadow-2xl backdrop-blur-sm sm:p-12">
        <a href="{{ url('/') }}" aria-label="HomeTech home" class="inline-block"><x-brand-logo variant="dark" /></a>
        <p class="mt-8 font-display text-7xl font-extrabold tracking-tight text-teal-300">@yield('code')</p>
        <h1 class="mt-3 font-display text-2xl font-extrabold">@yield('title')</h1>
        <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-slate-300">@yield('message')</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            @yield('actions')
        </div>
    </main>
</body>
</html>
