<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ __('About Us') }} — {{ __('HomeTech') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Cairo:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900 antialiased">
    <header class="relative z-10 border-b border-slate-200/80 bg-white/90 px-5 py-6 backdrop-blur sm:px-8 lg:px-12"><div class="mx-auto flex max-w-7xl items-center justify-between">
        <a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a>
        <nav class="flex items-center gap-3 text-sm font-bold" aria-label="{{ __('Home navigation') }}">
            <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="rounded-xl border border-slate-200 px-3 py-2.5 text-slate-600 transition hover:border-teal-300 hover:text-teal-700" aria-label="{{ __('Switch language') }}">{{ app()->getLocale() === 'ar' ? 'EN' : 'عربي' }}</a>
            <a href="{{ route('about') }}" class="rounded-xl px-4 py-2.5 text-teal-700 transition">{{ __('About Us') }}</a>
            <a href="{{ route('contact.create') }}" class="rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-teal-700">{{ __('Contact Us') }}</a>
            <a href="{{ route('services.index') }}" class="rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-teal-700">{{ __('Browse Services') }}</a>
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-slate-950">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-slate-950">{{ __('Sign in') }}</a>
                <a href="{{ route('register') }}" class="primary-button">{{ __('Get started') }} <span aria-hidden="true">→</span></a>
            @endauth
        </nav></div>
    </header>

    <main class="relative overflow-hidden">
        <div class="pointer-events-none absolute -left-24 top-10 h-96 w-96 rounded-full bg-teal-200/50 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-0 h-[32rem] w-[32rem] rounded-full bg-sky-100/70 blur-3xl"></div>

        <section class="relative mx-auto max-w-7xl px-5 pb-16 pt-20 sm:px-8 lg:px-12 lg:pt-28">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 rounded-full border-teal-200 bg-teal-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-teal-700">
                    <span class="h-2 w-2 rounded-full bg-teal-300"></span> {{ __('About Us') }}</p>
                <h1 class="mt-7 font-display text-5xl font-extrabold leading-[1.08] tracking-tight text-slate-950 sm:text-6xl">
                    {{ __('A maintenance company built around your home.') }}</h1>
                <p class="mt-7 max-w-xl text-lg leading-8 text-slate-600">{{ __('Certified technicians across plumbing, electrical, AC, painting and appliances — with transparent pricing and guaranteed work.') }}</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('services.index') }}" class="primary-button px-6 py-3.5">{{ __('Explore services') }}</a>
                    <a href="{{ route('contact.create') }}" class="inline-flex items-center justify-center rounded-xl border-slate-300 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50">{{ __('Contact Us') }}</a>
                </div>
            </div>

            <div class="mt-14 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="stat-card">
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Active services') }}</span>
                        <span class="mt-0.5 block font-display text-3xl font-extrabold text-slate-900">{{ $servicesCount }}</span>
                    </span>
                </div>
                <div class="stat-card">
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Technicians') }}</span>
                        <span class="mt-0.5 block font-display text-3xl font-extrabold text-slate-900">{{ $techniciansCount }}</span>
                    </span>
                </div>
                <div class="stat-card">
                    <span>
                        <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Completed jobs') }}</span>
                        <span class="mt-0.5 block font-display text-3xl font-extrabold text-slate-900">{{ $completedJobs }}</span>
                    </span>
                </div>
            </div>
        </section>

        <section class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12">
                <div class="max-w-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-300">{{ __('How it works') }}</p>
                    <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Home maintenance, without the runaround.') }}</h2>
                </div>
                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    <div class="rounded-3xl border-slate-200 bg-slate-50 p-6 shadow-sm"><span
                            class="text-3xl font-extrabold text-teal-400">01</span>
                        <h3 class="mt-7 font-display text-xl font-extrabold">{{ __('Tell us what you need') }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Share the details and we’ll help match the right service to your home.') }}</p>
                    </div>
                    <div class="rounded-3xl border-slate-200 bg-slate-50 p-6 shadow-sm"><span
                            class="text-3xl font-extrabold text-teal-400">02</span>
                        <h3 class="mt-7 font-display text-xl font-extrabold">{{ __('Meet your technician') }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Get clear scheduling updates and know who is coming before they arrive.') }}</p>
                    </div>
                    <div class="rounded-3xl border-slate-200 bg-slate-50 p-6 shadow-sm"><span
                            class="text-3xl font-extrabold text-teal-400">03</span>
                        <h3 class="mt-7 font-display text-xl font-extrabold">{{ __('Enjoy peace of mind') }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Follow your service from request to completion in one calm, clear dashboard.') }}</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
            <span>© {{ date('Y') }} HomeTech</span>
            <span class="flex items-center gap-4">
                <a href="{{ route('about') }}" class="transition hover:text-teal-700">{{ __('About Us') }}</a>
                <a href="{{ route('contact.create') }}" class="transition hover:text-teal-700">{{ __('Contact Us') }}</a>
            </span>
            <span>{{ __('Built for better everyday living.') }}</span>
        </div>
    </footer>
</body>

</html>
