<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ __('HomeTech — Home maintenance made simple') }}</title>
    <x-meta :title="__('HomeTech — Home maintenance made simple')" :description="__('Book trusted maintenance, keep track of every visit, and get back to enjoying your home — without the hassle.')" :canonical="route('home')" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Cairo:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900 antialiased">
    <header class="relative z-10 border-b border-slate-200/80 bg-white/90 px-5 py-4 backdrop-blur sm:px-8 sm:py-6 lg:px-12"><div class="mx-auto flex max-w-7xl items-center justify-between">
        <a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a>
        <nav class="flex items-center gap-2 text-sm font-bold sm:gap-3" aria-label="{{ __('Home navigation') }}">
            <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="rounded-xl border border-slate-200 px-3 py-2.5 text-slate-600 transition hover:border-teal-300 hover:text-teal-700" aria-label="{{ __('Switch language') }}">{{ app()->getLocale() === 'ar' ? 'EN' : 'عربي' }}</a>
            <a href="{{ route('about') }}" class="hidden rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-teal-700 sm:inline">{{ __('About Us') }}</a>
            <a href="{{ route('contact.create') }}" class="hidden rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-teal-700 sm:inline">{{ __('Contact Us') }}</a>
            <a href="{{ route('services.index') }}" class="hidden rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-teal-700 sm:inline">{{ __('Browse Services') }}</a>
            @auth
                <a href="{{ route('dashboard') }}" class="hidden rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-slate-950 sm:inline">{{ __('Dashboard') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="primary-button">{{ __('Log out') }} <span aria-hidden="true">→</span></button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hidden rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-slate-950 sm:inline">{{ __('Sign in') }}</a>
                <a href="{{ route('register') }}" class="primary-button whitespace-nowrap">{{ __('Get started') }} <span aria-hidden="true">→</span></a>
            @endauth
        </nav></div>
    </header>
    <main class="relative overflow-hidden bg-slate-50">
        <div class="pointer-events-none absolute -left-24 top-10 h-96 w-96 rounded-full bg-teal-200/50 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-0 h-[32rem] w-[32rem] rounded-full bg-sky-100/70 blur-3xl">
        </div>
        <section
            class="relative mx-auto grid max-w-7xl items-center gap-16 px-5 pb-24 pt-20 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:px-12 lg:pb-32 lg:pt-28">
            <div>
                <p
                    class="inline-flex items-center gap-2 rounded-full border-teal-200 bg-teal-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-teal-700">
                    <span class="h-2 w-2 rounded-full bg-teal-300"></span> {{ __('The smarter way to care for home') }}</p>
                <h1
                    class="mt-7 max-w-2xl font-display text-5xl font-extrabold leading-[1.08] tracking-tight text-slate-950 sm:text-6xl">
                    {{ __('A better home starts with') }} <span class="text-teal-600">{{ __('better care.') }}</span></h1>
                <p class="mt-7 max-w-xl text-lg leading-8 text-slate-600">{{ __('Book trusted maintenance, keep track of every visit, and get back to enjoying your home — without the hassle.') }}</p>
                <div class="mt-9 flex-col gap-3 sm:flex-row">@auth<a href="{{ route('dashboard') }}"
                        class="primary-button px-6 py-3.5">{{ __('Go to dashboard') }} <span aria-hidden="true">→</span></a>@else<a href="{{ route('register') }}"
                        class="primary-button px-6 py-3.5">{{ __('Create a free account') }} <span aria-hidden="true">→</span></a>@endauth<a
                        href="#how-it-works"
                        class="inline-flex items-center justify-center rounded-xl border-slate-300 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50">{{ __('How it works') }}</a></div>
                <form method="GET" action="{{ route('services.index') }}" class="mt-6 flex max-w-xl gap-2" role="search">
                    <div class="relative flex-1">
                        <svg class="pointer-events-none absolute start-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="{{ __('What do you need fixed?') }}" aria-label="{{ __('Search services') }}"
                            class="form-input ps-11">
                    </div>
                    <button type="submit" class="primary-button shrink-0 px-6">{{ __('Search') }}</button>
                </form>
                @if($quickCategories->isNotEmpty())
                <div class="mt-4 flex max-w-xl flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-500">{{ __('Popular right now') }}</span>
                    @foreach ($quickCategories as $quickCategory)
                        <a href="{{ route('services.index', ['category' => $quickCategory->slug]) }}"
                            class="rounded-full bg-white px-4 py-1.5 text-xs font-bold text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-px hover:text-teal-700 hover:ring-teal-300">{{ $quickCategory->display_name }}</a>
                    @endforeach
                </div>
                @endif
                <div class="mt-10 flex items-center gap-4 text-sm text-slate-500">
                    <div class="flex -space-x-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-amber-300 text-xs font-bold text-amber-900">A</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-sky-300 text-xs font-bold text-sky-900">M</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-rose-300 text-xs font-bold text-rose-900">J</span>
                    </div>
                    <span>{{ __('Simple service coordination for modern households.') }}</span>
                </div>
            </div>

            <div class="relative">
                <!-- Ambient Glow Behind Card -->
                <div class="absolute -inset-1 rounded-[2.5rem] bg-gradient-to-r from-teal-400/20 to-sky-400/20 blur-xl"></div>

                <!-- Main Glass Card -->
                <div class="relative rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur-sm sm:p-8">
                    <!-- Top Bar -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700 font-extrabold text-sm">
                                HT
                            </span>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-teal-700">{{ __('Home Maintenance Hub') }}</p>
                                <h2 class="font-display text-lg font-extrabold text-slate-950">{{ __('Live Service Overview') }}</h2>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ __('Live Updates') }}
                        </span>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                            <p class="text-xs font-semibold text-slate-500">{{ __('Active services') }}</p>
                            <p class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ $stats['services'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                            <p class="text-xs font-semibold text-slate-500">{{ __('Average rating') }}</p>
                            <p class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ $stats['averageRating'] ?? '—' }}<span class="text-xs font-bold text-slate-400"> / 5</span></p>
                            <p class="mt-1 text-xs font-medium text-teal-700">{{ $stats['reviewsCount'] }} {{ __('Reviews') }}</p>
                        </div>
                    </div>

                    @if($popularServices->isNotEmpty())
                    @php($topService = $popularServices->first())
                    <!-- Most Booked Spotlight -->
                    <div class="mt-4 rounded-2xl border border-teal-100 bg-gradient-to-br from-teal-50/70 to-sky-50/40 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white font-bold shadow-md shadow-teal-600/20">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-teal-700">{{ __('Popular services') }}</p>
                                    <p class="text-sm font-bold text-slate-900">{{ $topService->display_name }}</p>
                                </div>
                            </div>
                            <span class="font-display text-sm font-extrabold text-slate-900 shrink-0">{{ number_format($topService->base_price, 2) }} {{ __('SAR') }}</span>
                        </div>

                        <div class="mt-3 flex items-center justify-between border-t border-teal-200/40 pt-3 text-xs">
                            <span class="font-semibold text-slate-500">{{ $topService->category->display_name }}</span>
                            <a href="{{ route('services.show', $topService->slug) }}" class="font-bold text-teal-700 transition hover:text-teal-800">{{ __('Explore services') }} <span aria-hidden="true">→</span></a>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Floating Bottom Badge -->
                <div class="absolute -bottom-5 -left-4 sm:-left-6 rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xl shadow-slate-900/10 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-500 font-bold">
                        ★
                    </div>
                    <div>
                        <p class="text-xs font-extrabold text-slate-900">{{ $stats['averageRating'] ?? '—' }} / 5 {{ __('Rating') }}</p>
                        <p class="text-[11px] text-slate-500">{{ $stats['reviewsCount'] }} {{ __('Reviews') }}</p>
                    </div>
                </div>
            </div>
        </section>
        @if($popularServices->isNotEmpty())
        <section class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:px-12">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-600">{{ __('Popular services') }}</p>
                        <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Most booked by our customers.') }}</h2>
                    </div>
                    <a href="{{ route('services.index') }}" class="secondary-button shrink-0 text-xs hidden sm:inline-flex">{{ __('View all services') }}</a>
                </div>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    @foreach ($popularServices as $service)
                        <a href="{{ route('services.show', $service->slug) }}"
                            class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 transition duration-200 hover:-translate-y-1 hover:border-teal-300 hover:shadow-md">
                            <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                                <x-service-photo :service="$service" class="h-full w-full transition duration-200 group-hover:scale-105" />
                            </div>
                            <div class="flex flex-1 flex-col p-4">
                                <p class="text-xs font-bold text-slate-900 line-clamp-2">{{ $service->display_name }}</p>
                                <p class="mt-2 text-xs font-semibold text-slate-500">{{ __('Starting from') }} <span class="font-extrabold text-slate-900">{{ number_format($service->base_price, 2) }} {{ __('SAR') }}</span></p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
        @if($testimonials->isNotEmpty())
        <section class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12">
                <div class="flex items-end justify-between gap-6">
                    <div class="max-w-xl">
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-600">{{ __('What our customers say') }}</p>
                        <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Loved by households like yours.') }}</h2>
                    </div>
                    <div class="hidden shrink-0 rounded-3xl bg-slate-50 px-6 py-4 text-center ring-1 ring-slate-200/80 sm:block">
                        <p class="font-display text-4xl font-extrabold text-slate-900">{{ $stats['averageRating'] ?? '—' }}<span class="text-base font-bold text-slate-400"> / 5</span></p>
                        <p class="mt-1 text-xs font-bold text-slate-500">{{ $stats['reviewsCount'] }} {{ __('Reviews') }}</p>
                    </div>
                </div>
                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ($testimonials as $testimonial)
                        <figure class="flex flex-col rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/80 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-base font-extrabold tracking-wider text-amber-500" aria-label="{{ $testimonial->rating }} / 5">{{ str_repeat('★', $testimonial->rating) }}{{ str_repeat('☆', 5 - $testimonial->rating) }}</div>
                                <span aria-hidden="true" class="font-display text-4xl leading-none text-teal-200 select-none">“</span>
                            </div>
                            <blockquote class="mt-3 flex-1 text-sm leading-6 text-slate-700">{{ $testimonial->comment }}</blockquote>
                            <figcaption class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                                <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-600 text-sm font-extrabold text-white">{{ mb_substr($testimonial->user->name ?? __('Verified customer'), 0, 1) }}</span>
                                <span class="text-xs font-bold text-slate-900">{{ $testimonial->user->name ?? __('Verified customer') }}</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
        <section id="how-it-works" class="border-t border-slate-200 bg-white">            <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12">
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

        @if(! empty($servedCities))
        <section class="relative overflow-hidden bg-slate-900 text-white">
            <div aria-hidden="true" class="pointer-events-none absolute -top-32 start-1/4 h-72 w-72 rounded-full bg-teal-500/20 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 end-1/4 h-72 w-72 rounded-full bg-sky-500/10 blur-3xl"></div>
            <div class="relative mx-auto max-w-7xl px-5 py-14 sm:px-8 lg:px-12">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-300"><span aria-hidden="true" class="me-2 inline-block h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>{{ __('Now serving') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($servedCities as $city)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-1.5 text-sm font-bold text-white ring-1 ring-white/15">
                            <svg class="h-4 w-4 text-teal-300" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                            {{ $city }}
                        </span>
                    @endforeach
                </div>
                <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-sm">
                        <p class="font-display text-3xl font-extrabold">{{ $stats['services'] }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-400">{{ __('Active services') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-sm">
                        <p class="font-display text-3xl font-extrabold">{{ $stats['technicians'] }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-400">{{ __('Technicians') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-sm">
                        <p class="font-display text-3xl font-extrabold">{{ $stats['completedJobs'] }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-400">{{ __('Completed jobs') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-sm">
                        <p class="font-display text-3xl font-extrabold">{{ $stats['averageRating'] ?? '—' }}<span class="text-base text-slate-400"> / 5</span></p>
                        <p class="mt-1 text-xs font-semibold text-slate-400">{{ __('Average rating') }}</p>
                    </div>
                </div>
            </div>
        </section>
        @endif

        <section class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-3xl px-5 py-20 sm:px-8">
                <h2 class="font-display text-3xl font-extrabold tracking-tight text-center sm:text-4xl">{{ __('Frequently asked questions') }}</h2>
                <div class="mt-10 space-y-3">
                    <details class="group rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition open:bg-white open:shadow-md">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-extrabold text-slate-900 [&::-webkit-details-marker]:hidden">
                            <span>{{ __('How do I book a service?') }}</span>
                            <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600/10 text-lg font-light leading-none text-teal-700 transition-transform duration-300 group-open:rotate-45">+</span>
                        </summary>
                        <div class="grid transition-[grid-template-rows] duration-300 [grid-template-rows:0fr] group-open:[grid-template-rows:1fr]"><div class="overflow-hidden"><p class="mt-3 border-t border-slate-100 pt-3 text-sm leading-6 text-slate-600">{{ __('Create an account, pick a service, choose your address and preferred time. We confirm and assign a certified technician.') }}</p></div></div>
                    </details>
                    <details class="group rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition open:bg-white open:shadow-md">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-extrabold text-slate-900 [&::-webkit-details-marker]:hidden">
                            <span>{{ __('How much does it cost?') }}</span>
                            <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600/10 text-lg font-light leading-none text-teal-700 transition-transform duration-300 group-open:rotate-45">+</span>
                        </summary>
                        <div class="grid transition-[grid-template-rows] duration-300 [grid-template-rows:0fr] group-open:[grid-template-rows:1fr]"><div class="overflow-hidden"><p class="mt-3 border-t border-slate-100 pt-3 text-sm leading-6 text-slate-600">{{ __('Every service shows its starting price upfront. You approve any extra work before we proceed, with no hidden fees.') }}</p></div></div>
                    </details>
                    <details class="group rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition open:bg-white open:shadow-md">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-extrabold text-slate-900 [&::-webkit-details-marker]:hidden">
                            <span>{{ __('Which areas do you serve?') }}</span>
                            <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600/10 text-lg font-light leading-none text-teal-700 transition-transform duration-300 group-open:rotate-45">+</span>
                        </summary>
                        <div class="grid transition-[grid-template-rows] duration-300 [grid-template-rows:0fr] group-open:[grid-template-rows:1fr]"><div class="overflow-hidden"><p class="mt-3 border-t border-slate-100 pt-3 text-sm leading-6 text-slate-600">{{ __('We currently serve :cities.', ['cities' => implode(', ', $servedCities)]) }}</p></div></div>
                    </details>
                    <details class="group rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition open:bg-white open:shadow-md">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-extrabold text-slate-900 [&::-webkit-details-marker]:hidden">
                            <span>{{ __('What if I need to reschedule or cancel?') }}</span>
                            <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600/10 text-lg font-light leading-none text-teal-700 transition-transform duration-300 group-open:rotate-45">+</span>
                        </summary>
                        <div class="grid transition-[grid-template-rows] duration-300 [grid-template-rows:0fr] group-open:[grid-template-rows:1fr]"><div class="overflow-hidden"><p class="mt-3 border-t border-slate-100 pt-3 text-sm leading-6 text-slate-600">{{ __('Approved requests can pick a new slot from your dashboard, and cancellations follow our cancellation policy.') }}</p></div></div>
                    </details>
                </div>
                <div class="mt-6 text-center">
                    <a href="{{ route('contact.create') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-teal-700 transition hover:gap-2.5 hover:text-teal-800">{{ __('Contact Us') }} <span aria-hidden="true">→</span></a>
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-gradient-to-br from-teal-700 to-teal-900 text-white">
            <div aria-hidden="true" class="pointer-events-none absolute -top-24 -start-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-28 -end-16 h-80 w-80 rounded-full bg-teal-300/20 blur-3xl"></div>
            <div class="relative mx-auto flex max-w-7xl flex-col items-start gap-6 px-5 py-14 sm:px-8 lg:flex-row lg:items-center lg:justify-between lg:px-12">
                <h2 class="max-w-xl font-display text-3xl font-extrabold tracking-tight text-white drop-shadow-sm sm:text-4xl">{{ __('Your home deserves a great start') }}</h2>
                <div class="flex flex-col gap-3 sm:flex-row">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-teal-700 shadow-lg shadow-teal-900/20 transition hover:bg-teal-50">{{ __('Go to dashboard') }}</a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-teal-700 shadow-lg shadow-teal-900/20 transition hover:bg-teal-50">{{ __('Create a free account') }}</a>
                    @endauth
                    <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/40 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-white/10">{{ __('Explore services') }}</a>
                </div>
            </div>
        </section>
    </main>
    <footer
        class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
        <span>© {{ date('Y') }} HomeTech</span>
        <span class="flex items-center gap-4">
            <a href="{{ route('about') }}" class="transition hover:text-teal-700">{{ __('About Us') }}</a>
            <a href="{{ route('contact.create') }}" class="transition hover:text-teal-700">{{ __('Contact Us') }}</a>
        </span>
        <span>{{ __('Built for better everyday living.') }}</span></footer>
</body>

</html>
