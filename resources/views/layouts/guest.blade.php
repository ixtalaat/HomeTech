<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>@hasSection('title')@yield('title') — {{ __('HomeTech') }}@else{{ $title ?? 'HomeTech' }}@endif</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
        <section class="relative hidden overflow-hidden bg-teal-50 p-12 text-slate-900 lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-teal-200/60 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between">
                    <a href="{{ url('/') }}" aria-label="HomeTech home">
                        <x-brand-logo />
                    </a>
                    <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                        class="rounded-xl border border-teal-200 bg-white/70 px-3 py-2 text-xs font-bold text-teal-700 transition hover:bg-white"
                        aria-label="{{ __('Switch language') }}">{{ app()->getLocale() === 'ar' ? 'EN' : 'عربي' }}</a>
                </div>
                <div class="mt-28 max-w-lg">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">{{ __('Care for your home') }}</p>
                    <h1 class="mt-5 font-display text-5xl font-extrabold leading-tight tracking-tight">{{ __('Reliable help for every corner of home.') }}</h1>
                    <p class="mt-6 max-w-md text-lg leading-8 text-slate-600">{{ __('Book trusted maintenance, follow your service, and keep your home running smoothly.') }}</p>
                </div>
            </div>
            <p class="relative text-sm text-slate-500">{{ __('Trusted service coordination for modern households.') }}</p>
        </section>
        <main class="flex items-center justify-center bg-slate-50 px-5 py-12 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-6 flex justify-end lg:hidden">
                    <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700"
                        aria-label="{{ __('Switch language') }}">{{ app()->getLocale() === 'ar' ? 'EN' : 'عربي' }}</a>
                </div>
                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>
