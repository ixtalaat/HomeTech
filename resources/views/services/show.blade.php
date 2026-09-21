<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $service->name }} — HomeTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900 antialiased min-h-screen flex flex-col">
    <!-- Header / Navbar -->
    <header class="relative z-10 border-b border-slate-200/80 bg-white/90 px-5 py-4 backdrop-blur sm:px-8 lg:px-12">
        <div class="mx-auto flex max-w-7xl items-center justify-between">
            <a href="{{ url('/') }}" aria-label="HomeTech home">
                <x-brand-logo />
            </a>
            <nav class="flex items-center gap-4 text-sm font-bold">
                <a href="{{ route('services.index') }}" class="text-teal-700 transition">{{ __('Services') }}</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-slate-700 hover:border-teal-200 hover:bg-teal-50 transition">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-slate-600 transition hover:text-slate-950">{{ __('Sign in') }}</a>
                    <a href="{{ route('register') }}" class="primary-button py-2 px-4 text-xs">{{ __('Get started') }} <span aria-hidden="true">→</span></a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1 py-10 sm:py-16">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
            <!-- Breadcrumbs -->
            <nav class="mb-8 flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="{{ route('services.index') }}" class="hover:text-teal-700">{{ __('Services') }}</a>
                <span>›</span>
                <a href="{{ route('services.index', ['category' => $service->category->slug]) }}" class="hover:text-teal-700">
                    {{ $service->category->display_name }}
                </a>
                <span>›</span>
                <span class="text-slate-900 truncate">{{ $service->display_name }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-12 lg:grid-cols-[1.2fr_0.8fr]">
                <!-- Main Service Information -->
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-teal-700">
                        {{ $service->category->display_name }}
                    </span>

                    <h1 class="mt-4 font-display text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">
                        {{ $service->display_name }}
                    </h1>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-bold text-slate-900">{{ __('Service Description & Scope') }}</h2>
                        <div class="mt-3 text-sm leading-7 text-slate-600 space-y-3">
                            <p>{{ $service->translated('description') ?? __('Professional maintenance, thorough diagnostics, and reliable execution by experienced technicians.') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="stat-card">
                            <div class="stat-icon bg-teal-50 text-teal-700">
                                ⏱️
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400">{{ __('Estimated Duration') }}</p>
                                <p class="font-display text-lg font-bold text-slate-900">~{{ $service->estimated_duration_minutes }} {{ __('minutes') }}</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon bg-sky-50 text-sky-700">
                                🛡️
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400">{{ __('Quality Guarantee') }}</p>
                                <p class="font-display text-lg font-bold text-slate-900">{{ __('Verified Pros & Warranty') }}</p>
                            </div>
                        </div>
                    </div>

                    @if($relatedServices->isNotEmpty())
                        <div class="mt-12">
                            <h3 class="font-display text-xl font-bold text-slate-900">{{ __('Other :name Services', ['name' => $service->category->display_name]) }}</h3>
                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ($relatedServices as $related)
                                    <a href="{{ route('services.show', $related->slug) }}"
                                        class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-teal-300 hover:shadow-sm">
                                        <p class="font-bold text-slate-900 text-sm">{{ $related->display_name }}</p>
                                        <div class="mt-3 flex items-center justify-between text-xs">
                                            <span class="font-bold text-slate-900">{{ number_format($related->base_price, 2) }} {{ __('EGP') }}</span>
                                            <span class="text-teal-700 font-semibold">{{ __('View →') }}</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Booking Action Card -->
                <div>
                    <div class="sticky top-6 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-xl shadow-slate-900/5">
                        <p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ __('Pricing & Booking') }}</p>
                        
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="font-display text-4xl font-extrabold text-slate-950">{{ number_format($service->base_price, 2) }}</span>
                            <span class="text-sm font-bold text-slate-500">{{ __('EGP') }}</span>
                            <span class="text-xs text-slate-400">({{ __('base diagnostic & labor estimate') }})</span>
                        </div>

                        <div class="mt-6 border-t border-slate-100 pt-6 space-y-3 text-xs text-slate-600">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-teal-600 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                <span>{{ __('No hidden fees — transparent upfront pricing') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-teal-600 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                <span>{{ __('Scheduled appointment at your preferred date/time') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-teal-600 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                <span>{{ __('Itemized diagnosis, labor, and materials breakdown') }}</span>
                            </div>
                        </div>

                        <div class="mt-8">
                            @auth
                                <a href="{{ route('requests.create', ['service_id' => $service->id]) }}" class="primary-button w-full py-3.5 text-center text-sm font-bold">
                                    {{ __('Request This Service Now') }}
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="primary-button w-full py-3.5 text-center text-sm font-bold">
                                    {{ __('Sign Up to Request Service') }}
                                </a>
                                <p class="mt-2 text-center text-xs text-slate-500">
                                    {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="font-bold text-teal-700 hover:underline">{{ __('Log in') }}</a>
                                </p>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
            <span>© {{ date('Y') }} HomeTech</span>
            <span>{{ __('Built for better everyday living.') }}</span>
        </div>
    </footer>
</body>
</html>
