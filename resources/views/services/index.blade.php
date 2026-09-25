<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ __('Available Services — HomeTech') }}</title>
    <x-meta :title="__('Available Services — HomeTech')" :description="__('Transparent pricing, certified technicians, and hassle-free scheduling across all home maintenance categories.')" :canonical="route('services.index')" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Cairo:wght@400;500;600;700;800&display=swap"
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

    <main class="flex-1">
        <!-- Hero Header -->
        <section class="border-b border-slate-200 bg-white py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                <div class="max-w-2xl">
                    <p class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-teal-700">
                        <span class="h-2 w-2 rounded-full bg-teal-400"></span>
                        {{ __('Our Services Catalog') }}
                    </p>
                    <h1 class="mt-4 font-display text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">
                        {{ __('Expert care for every part of your home.') }}
                    </h1>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        {{ __('Transparent pricing, certified technicians, and hassle-free scheduling across all home maintenance categories.') }}
                    </p>
                </div>

                <!-- Search Box -->
                <div class="mt-8 max-w-xl">
                    <form method="GET" action="{{ route('services.index') }}" class="flex gap-2">
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        <div class="relative flex-1">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="{{ __('Search services (e.g. AC cleaning, faucet repair)...') }}"
                                aria-label="{{ __('Search services') }}"
                                class="form-input pl-10 text-sm">
                            <svg class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <button type="submit" class="primary-button text-xs px-5">{{ __('Search') }}</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Category Filter Chips -->
        <section class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50/95 py-4 backdrop-blur">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 text-sm no-scrollbar">
                    <a href="{{ route('services.index', request()->only('search')) }}"
                        class="shrink-0 rounded-full px-4 py-2 text-xs font-bold transition {{ !request('category') ? 'bg-slate-900 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-teal-300 hover:text-teal-700' }}">
                        {{ __('All Services') }}
                    </a>
                    @foreach ($categories as $cat)
                        <a href="{{ route('services.index', array_merge(request()->only('search'), ['category' => $cat->slug])) }}"
                            class="shrink-0 rounded-full px-4 py-2 text-xs font-bold transition {{ request('category') === $cat->slug ? 'bg-teal-700 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-teal-300 hover:text-teal-700' }}">
                            {{ $cat->display_name }}
                            <span class="ml-1 opacity-70">({{ $cat->services_count }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Services Grid -->
        <section class="py-12">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-12">
                @if($selectedCategory)
                    <div class="mb-8">
                        <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ $selectedCategory->display_name }}</h2>
                        @if($selectedCategory->translated('description'))
                            <p class="mt-1 text-sm text-slate-500 max-w-2xl">{{ $selectedCategory->translated('description') }}</p>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($services as $service)
                        <div class="flex flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-teal-300 hover:shadow-md">
                            <div class="-mx-6 -mt-6 mb-5 aspect-[16/10] w-[calc(100%+3rem)] overflow-hidden rounded-t-3xl border-b border-slate-100 bg-slate-100">
                                <x-service-photo :service="$service" class="h-full w-full" />
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700">
                                    {{ $service->category->display_name }}
                                </span>
                                <span class="flex items-center gap-1 text-xs font-medium text-slate-500">
                                    <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                                    </svg>
                                    ~{{ $service->estimated_duration_minutes }} {{ __('min') }}
                                </span>
                            </div>

                            <h3 class="mt-4 font-display text-lg font-bold text-slate-900">{{ $service->display_name }}</h3>
                            
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-500 line-clamp-3">
                                {{ $service->translated('description') ?? __('Professional maintenance and diagnostics by qualified technicians.') }}
                            </p>

                            <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('Starting from') }}</p>
                                    <p class="font-display text-xl font-extrabold text-slate-950">
                                        {{ number_format($service->base_price, 2) }} <span class="text-xs font-semibold text-slate-500">{{ __('EGP') }}</span>
                                    </p>
                                </div>
                                <a href="{{ route('services.show', $service->slug) }}"
                                    class="secondary-button py-2 px-3 text-xs">
                                    {{ __('Details →') }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="mt-3 text-base font-bold text-slate-900">{{ __('No services found') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('Try adjusting your category filter or search keywords.') }}</p>
                            <div class="mt-5">
                                <a href="{{ route('services.index') }}" class="primary-button text-xs py-2 px-4">{{ __('Clear all filters') }}</a>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if($services->hasPages())
                    <div class="mt-10">
                        {{ $services->links() }}
                    </div>
                @endif
            </div>
        </section>
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
