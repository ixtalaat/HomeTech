<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'HomeTech') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="hidden w-72 shrink-0 flex-col border-r border-slate-200 bg-white px-6 py-7 lg:flex">
            <a href="{{ route('dashboard') }}" aria-label="HomeTech dashboard">
                <x-brand-logo />
            </a>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Service hub</p>
            <nav class="mt-8 flex flex-col gap-1.5" aria-label="Main navigation">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-bold transition {{ request()->routeIs('dashboard') ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-9Z"/><path d="M9 21v-6h6v6"/></svg>
                    <span>Overview</span>
                </a>

                <a href="{{ route('services.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('services.*') && !request()->routeIs('admin.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <span>Browse Services</span>
                </a>

                @if(auth()->user() && in_array(auth()->user()->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Manager], true))
                    <div class="pt-4 pb-1">
                        <p class="px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Admin Management</p>
                    </div>

                    <a href="{{ route('admin.services.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.services.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <span>Manage Services</span>
                    </a>

                    <a href="{{ route('admin.categories.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.categories.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        <span>Categories</span>
                    </a>

                    <a href="{{ route('admin.customers.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.customers.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                        <span>Customers</span>
                    </a>

                    <a href="{{ route('admin.requests.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.requests.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                        <span>Requests</span>
                    </a>

                    <a href="{{ route('admin.technicians.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.technicians.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <span>Technicians</span>
                    </a>

                    <a href="{{ route('admin.inventory.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.inventory.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 7H4a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM7 10h2v2H7v-2Zm0 4h2v2H7v-2Zm4-4h6v2h-6v-2Zm0 4h6v2h-6v-2Z"/></svg>
                        <span>Inventory</span>
                    </a>

                    <a href="{{ route('admin.invoices.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.invoices.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm8 0v5h5"/></svg>
                        <span>Invoices</span>
                    </a>

                    <a href="{{ route('admin.discount-approvals.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.discount-approvals.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 12.5 11 15l4-5"/><circle cx="12" cy="12" r="9"/></svg>
                        <span>Approvals</span>
                    </a>

                    <a href="{{ route('admin.reviews.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reviews.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 2 2.9 6.26 6.6.57-5 4.4 1.5 6.47L12 16.9 5.99 19.7l1.5-6.47-5-4.4 6.6-.57L12 2Z"/></svg>
                        <span>Reviews</span>
                    </a>

                    <div class="pt-4 pb-1">
                        <p class="px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Reports</p>
                    </div>

                    <a href="{{ route('admin.reports.revenue') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.revenue') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>Revenue</span>
                    </a>

                    <a href="{{ route('admin.reports.jobs') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.jobs') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>Jobs</span>
                    </a>

                    <a href="{{ route('admin.reports.technicians') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.technicians') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>Technicians</span>
                    </a>

                    <a href="{{ route('admin.reports.inventory') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.inventory') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span>Inventory</span>
                    </a>
                @endif

                <div class="pt-4 pb-1">
                    <p class="px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Account</p>
                </div>

                <a href="{{ route('addresses.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('addresses.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <span>My addresses</span>
                </a>

                <a href="{{ route('requests.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('requests.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <span>My requests</span>
                </a>

                <a href="{{ route('invoices.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('invoices.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l4 4v14H6V3Zm8 0v5h5"/></svg>
                    <span>My invoices</span>
                </a>

                @if(auth()->user() && auth()->user()->role === \App\Enums\UserRole::Technician)
                    <a href="{{ route('technician.jobs.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('technician.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <span>My jobs</span>
                    </a>
                @endif

                <a href="{{ route('profile.edit') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('profile.*') ? 'bg-teal-50 text-teal-700 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                    <span>My profile</span>
                </a>
            </nav>
            <div class="mt-auto rounded-2xl bg-slate-900 p-5 text-white">
                <p class="text-sm font-bold">Need a hand?</p>
                <p class="mt-2 text-xs leading-5 text-slate-300">Our support team is ready to help with your next
                    service.</p>
                <a href="mailto:support@hometech.test" class="mt-4 inline-flex text-xs font-bold text-teal-300">Contact
                    support →</a>
            </div>
        </aside>
        <main class="min-w-0 flex-1">
            <header
                class="flex items-center justify-between border-b border-slate-200 bg-white/90 px-5 py-4 backdrop-blur sm:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-600">Home maintenance,
                        simplified</p>
                    <h1 class="mt-1 font-display text-lg font-extrabold text-slate-900">
                        {{ $heading ?? 'Your service overview' }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('notifications.index') }}" aria-label="Notifications"
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
                            type="submit">Log out</button>
                    </form>
                </div>
            </header>

            <div class="px-5 py-8 sm:px-8 lg:px-12 lg:py-10">
                @if (session('success'))
                    <div class="mb-6 flex items-center gap-3 rounded-2xl border border-teal-200 bg-teal-50 p-4 text-sm font-medium text-teal-900">
                        <svg class="h-5 w-5 text-teal-600 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-900">
                        <svg class="h-5 w-5 text-rose-600 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>
