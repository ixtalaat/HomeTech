<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f766e">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script defer src="https://www.gstatic.com/firebasejs/10.14.0/firebase-app-compat.js"></script>
    <script defer src="https://www.gstatic.com/firebasejs/10.14.0/firebase-messaging-compat.js"></script>
    <title>@hasSection('title')@yield('title') — {{ __('HomeTech') }}@else{{ $title ?? config('app.name', 'HomeTech') }}@endif</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Cairo:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-teal-600 focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white">{{ __('Skip to content') }}</a>
    <div class="min-h-screen lg:flex">
        <aside class="hidden w-72 shrink-0 flex-col border-r border-slate-200 bg-gradient-to-b from-white via-white to-teal-50/60 px-6 py-7 lg:flex">
            <a href="{{ route('dashboard') }}" aria-label="HomeTech dashboard">
                <x-brand-logo />
            </a>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Service hub') }}</p>
            <nav class="mt-8 flex flex-col gap-1.5" aria-label="Main navigation">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-bold transition {{ request()->routeIs('dashboard') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-9Z"/><path d="M9 21v-6h6v6"/></svg>
                    <span>{{ __('Overview') }}</span>
                </a>

                <a href="{{ route('services.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('services.*') && !request()->routeIs('admin.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <span>{{ __('Browse Services') }}</span>
                </a>

                @if(auth()->user() && in_array(auth()->user()->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Manager], true) && ! auth()->user()->isBranchScoped())
                    <div class="pt-4 pb-1">
                        <p class="px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('Admin Management') }}</p>
                    </div>

                    <a href="{{ route('admin.services.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.services.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <span>{{ __('Manage Services') }}</span>
                    </a>

                    <a href="{{ route('admin.categories.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.categories.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        <span>{{ __('Categories') }}</span>
                    </a>

                    <a href="{{ route('admin.branches.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.branches.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        <span>{{ __('Branches') }}</span>
                    </a>

                    <a href="{{ route('admin.customers.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.customers.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                        <span>{{ __('Customers') }}</span>
                    </a>

                    <a href="{{ route('admin.requests.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.requests.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                        <span>{{ __('Requests') }}</span>
                    </a>

                    <a href="{{ route('admin.technicians.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.technicians.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <span>{{ __('Technicians') }}</span>
                    </a>

                    <a href="{{ route('admin.managers.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.managers.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 8.5a3 3 0 1 0-2.2-5"/><path d="M17.5 20a5.5 5.5 0 0 0-4-5.2"/></svg>
                        <span>{{ __('Managers') }}</span>
                    </a>

                    <a href="{{ route('admin.inventory.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.inventory.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 7H4a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM7 10h2v2H7v-2Zm0 4h2v2H7v-2Zm4-4h6v2h-6v-2Zm0 4h6v2h-6v-2Z"/></svg>
                        <span>{{ __('Inventory') }}</span>
                    </a>

                    <a href="{{ route('admin.invoices.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.invoices.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm8 0v5h5"/></svg>
                        <span>{{ __('Invoices') }}</span>
                    </a>

                    <a href="{{ route('admin.discount-approvals.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.discount-approvals.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 12.5 11 15l4-5"/><circle cx="12" cy="12" r="9"/></svg>
                        <span>{{ __('Approvals') }}</span>
                    </a>

                    <a href="{{ route('admin.reviews.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reviews.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 2 2.9 6.26 6.6.57-5 4.4 1.5 6.47L12 16.9 5.99 19.7l1.5-6.47-5-4.4 6.6-.57L12 2Z"/></svg>
                        <span>{{ __('Reviews') }}</span>
                    </a>

                    <div class="pt-4 pb-1">
                        <p class="px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('Reports') }}</p>
                    </div>

                    <a href="{{ route('admin.reports.revenue') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.revenue') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>{{ __('Revenue') }}</span>
                    </a>

                    <a href="{{ route('admin.reports.calendar') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.calendar') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>{{ __('Calendar') }}</span>
                    </a>

                    <a href="{{ route('admin.reports.jobs') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.jobs') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>{{ __('Jobs') }}</span>
                    </a>

                    <a href="{{ route('admin.reports.technicians') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.technicians') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>{{ __('Technicians') }}</span>
                    </a>

                    <a href="{{ route('admin.reports.inventory') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.inventory') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>{{ __('Inventory') }}</span>
                    </a>
                @endif

                @if(auth()->user() && auth()->user()->role === \App\Enums\UserRole::Manager && auth()->user()->isBranchScoped())
                    <div class="pt-4 pb-1">
                        <p class="px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('Branch Management') }}</p>
                    </div>

                    <a href="{{ route('branch.dashboard') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('branch.dashboard') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-9Z"/><path d="M9 21v-6h6v6"/></svg>
                        <span>{{ __('Overview') }}</span>
                    </a>

                    <a href="{{ route('branch.technicians.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('branch.technicians.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                        <span>{{ __('Technicians') }}</span>
                    </a>

                    <a href="{{ route('branch.requests.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('branch.requests.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                        <span>{{ __('Requests') }}</span>
                    </a>
                @endif

                <div class="pt-4 pb-1">
                    <p class="px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('Account') }}</p>
                </div>

                <a href="{{ route('addresses.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('addresses.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <span>{{ __('My addresses') }}</span>
                </a>

                <a href="{{ route('requests.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('requests.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <span>{{ __('My requests') }}</span>
                </a>

                <a href="{{ route('invoices.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('invoices.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm8 0v5h5"/></svg>
                    <span>{{ __('My invoices') }}</span>
                </a>

                @if(auth()->user() && auth()->user()->role === \App\Enums\UserRole::Technician)
                    <a href="{{ route('technician.jobs.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('technician.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <span>{{ __('My jobs') }}</span>
                    </a>
                @endif

                <a href="{{ route('profile.edit') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('profile.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                    <span>{{ __('My profile') }}</span>
                </a>
            </nav>
            <div class="dark-panel mt-auto p-5 text-white">
                <div class="pointer-events-none absolute -top-12 left-1/2 h-28 w-44 -translate-x-1/2 rounded-full bg-teal-400/30 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-10 -right-10 h-24 w-24 rounded-full bg-sky-400/20 blur-2xl" aria-hidden="true"></div>
                <div class="relative flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-teal-300">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="2.8" y="13.5" width="4" height="6.5" rx="2"/><rect x="17.2" y="13.5" width="4" height="6.5" rx="2"/><path d="M19.5 20a4.5 4.5 0 0 1-4.5 3H13"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold">{{ __('Need a hand?') }}</p>
                        <p class="mt-0.5 flex items-center gap-1.5 text-[11px] font-semibold text-slate-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>{{ __('Support is online') }}
                        </p>
                    </div>
                </div>
                <p class="relative mt-3 text-xs leading-5 text-slate-300 text-balance">{{ __('Our support team is ready to help with your next service.') }}</p>
                <a href="{{ route('support.create') }}" class="relative mt-4 inline-flex w-full items-center justify-center rounded-xl bg-teal-500 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-teal-400">{{ __('Contact support →') }}</a>
            </div>
        </aside>
        <main id="main-content" class="min-w-0 flex-1" tabindex="-1">
            <header
                class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200 bg-white/90 px-5 py-4 shadow-[inset_0_-3px_0_0_rgb(20_184_166/0.15)] backdrop-blur sm:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-600">{{ __('Home maintenance, simplified') }}</p>
                    <h1 class="mt-1 font-display text-lg font-extrabold text-slate-900">
                        {{ $heading ?? __('Your service overview') }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700"
                        aria-label="{{ __('Switch language') }}">{{ app()->getLocale() === 'ar' ? 'EN' : 'عربي' }}</a>
                    <a href="{{ route('notifications.index') }}" aria-label="{{ __('Notifications') }}"
                        class="relative rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
                        @if(auth()->user()->unreadNotifications()->count() > 0)
                            <span class="absolute -top-1.5 -right-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-extrabold text-white">
                                {{ auth()->user()->unreadNotifications()->count() }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('profile.edit') }}"
                        class="hidden text-sm font-semibold text-slate-500 transition hover:text-slate-900 sm:block">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700"
                            type="submit">{{ __('Log out') }}</button>
                    </form>
                </div>
            </header>

            <div class="px-5 py-8 sm:px-8 lg:px-12 lg:py-10">
                <div id="flash-data" class="hidden" data-flash='@json(['success' => session('success'), 'error' => session('error'), 'status' => session('status')])'></div>

                @yield('content')
            </div>

            <nav class="sticky bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-2 backdrop-blur lg:hidden" aria-label="Mobile navigation">
                <div class="flex items-center gap-1 overflow-x-auto">
                    <a href="{{ route('dashboard') }}" @class(['flex shrink-0 flex-col items-center gap-1 rounded-xl px-4 py-2 text-[11px] font-bold', 'text-teal-700' => request()->routeIs('dashboard'), 'text-slate-500' => ! request()->routeIs('dashboard')])>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1-1v-9Z"/><path d="M9 21v-6h6v6"/></svg>
                        <span>{{ __('Home') }}</span>
                    </a>
                    <a href="{{ route('services.index') }}" @class(['flex shrink-0 flex-col items-center gap-1 rounded-xl px-4 py-2 text-[11px] font-bold', 'text-teal-700' => request()->routeIs('services.*'), 'text-slate-500' => ! request()->routeIs('services.*')])>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                        <span>{{ __('Services') }}</span>
                    </a>
                    <a href="{{ route('requests.index') }}" @class(['flex shrink-0 flex-col items-center gap-1 rounded-xl px-4 py-2 text-[11px] font-bold', 'text-teal-700' => request()->routeIs('requests.*'), 'text-slate-500' => ! request()->routeIs('requests.*')])>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 12h6M9 16h6M13 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V8l-5-5Z"/></svg>
                        <span>{{ __('Requests') }}</span>
                    </a>
                    @if(auth()->user() && auth()->user()->role === \App\Enums\UserRole::Technician)
                        <a href="{{ route('technician.jobs.index') }}" @class(['flex shrink-0 flex-col items-center gap-1 rounded-xl px-4 py-2 text-[11px] font-bold', 'text-teal-700' => request()->routeIs('technician.*'), 'text-slate-500' => ! request()->routeIs('technician.*')])>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            <span>{{ __('Jobs') }}</span>
                        </a>
                    @else
                        <a href="{{ route('invoices.index') }}" @class(['flex shrink-0 flex-col items-center gap-1 rounded-xl px-4 py-2 text-[11px] font-bold', 'text-teal-700' => request()->routeIs('invoices.*'), 'text-slate-500' => ! request()->routeIs('invoices.*')])>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm8 0v5h5"/></svg>
                            <span>{{ __('Bills') }}</span>
                        </a>
                    @endif
                    <a href="{{ route('profile.edit') }}" @class(['flex shrink-0 flex-col items-center gap-1 rounded-xl px-4 py-2 text-[11px] font-bold', 'text-teal-700' => request()->routeIs('profile.*'), 'text-slate-500' => ! request()->routeIs('profile.*')])>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                        <span>{{ __('Profile') }}</span>
                    </a>
                </div>
            </nav>
        </main>
    </div>
    <script>
        // Foreground push messages: show them as a toast while the app is open.
        window.addEventListener('load', () => {
            if (typeof firebase === 'undefined' || !('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }

            fetch('{{ route('firebase.config') }}', { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((config) => {
                    if (!config.apiKey) {
                        return;
                    }

                    const app = firebase.apps.length ? firebase.app() : firebase.initializeApp({
                        apiKey: config.apiKey,
                        authDomain: config.authDomain,
                        projectId: config.projectId,
                        messagingSenderId: config.senderId,
                        appId: config.appId,
                    });

                    firebase.messaging(app).onMessage((payload) => {
                        const text = (payload.notification && (payload.notification.body || payload.notification.title)) || '';

                        if (text && window.toast) {
                            window.toast(text, 'info');
                        }
                    });
                })
                .catch(() => {});
        });
    </script>
    @stack('scripts')
</body>

</html>
