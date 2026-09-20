<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HomeTech — Home maintenance made simple</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900 antialiased">
    <header class="relative z-10 border-b border-slate-200/80 bg-white/90 px-5 py-6 backdrop-blur sm:px-8 lg:px-12"><div class="mx-auto flex max-w-7xl items-center justify-between">
        <a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a>
        <nav class="flex items-center gap-3 text-sm font-bold">
            <a href="{{ route('services.index') }}" class="rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-teal-700">Browse Services</a>
            <a href="{{ route('login') }}" class="rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-slate-950">Sign in</a>
            <a href="{{ route('register') }}" class="primary-button">Get started <span aria-hidden="true">→</span></a>
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
                    <span class="h-2 w-2 rounded-full bg-teal-300"></span> The smarter way to care for home</p>
                <h1
                    class="mt-7 max-w-2xl font-display text-5xl font-extrabold leading-[1.08] tracking-tight text-slate-950 sm:text-6xl">
                    A better home starts with <span class="text-teal-600">better care.</span></h1>
                <p class="mt-7 max-w-xl text-lg leading-8 text-slate-600">Book trusted maintenance, keep track of every
                    visit, and get back to enjoying your home — without the hassle.</p>
                <div class="mt-9 flex-col gap-3 sm:flex-row"><a href="{{ route('register') }}"
                        class="primary-button px-6 py-3.5">Create a free account <span aria-hidden="true">→</span></a><a
                        href="#how-it-works"
                        class="inline-flex items-center justify-center rounded-xl border-slate-300 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-teal-300 hover:bg-teal-50">How
                        it works</a></div>
                <div class="mt-10 flex items-center gap-4 text-sm text-slate-500">
                    <div class="flex -space-x-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-amber-300 text-xs font-bold text-amber-900">A</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-sky-300 text-xs font-bold text-sky-900">M</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-rose-300 text-xs font-bold text-rose-900">J</span>
                    </div>
                    <span>Simple service coordination for modern households.</span>
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
                                <p class="text-[11px] font-bold uppercase tracking-wider text-teal-700">Home Maintenance Hub</p>
                                <h2 class="font-display text-lg font-extrabold text-slate-950">Live Service Overview</h2>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live Updates
                        </span>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                            <p class="text-xs font-semibold text-slate-500">Active Requests</p>
                            <p class="mt-2 font-display text-2xl font-extrabold text-slate-900">02 <span class="text-xs font-normal text-slate-400">Jobs</span></p>
                            <div class="mt-3 flex gap-1">
                                <div class="h-1.5 flex-1 rounded-full bg-teal-500"></div>
                                <div class="h-1.5 flex-1 rounded-full bg-teal-300"></div>
                                <div class="h-1.5 flex-1 rounded-full bg-slate-200"></div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                            <p class="text-xs font-semibold text-slate-500">Next Scheduled Visit</p>
                            <p class="mt-2 font-display text-2xl font-extrabold text-slate-900">Tomorrow</p>
                            <p class="mt-1 text-xs font-medium text-teal-700">10:30 AM · Confirmed</p>
                        </div>
                    </div>

                    <!-- Live Active Job Card -->
                    <div class="mt-4 rounded-2xl border border-teal-100 bg-gradient-to-br from-teal-50/70 to-sky-50/40 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white font-bold shadow-md shadow-teal-600/20">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-bold text-slate-900">AC Full Seasonal Service</p>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">Technician Ahmed M. · On the way</p>
                                </div>
                            </div>
                            <span class="font-display text-sm font-extrabold text-slate-900">350 EGP</span>
                        </div>

                        <!-- Progress Steps -->
                        <div class="mt-4 flex items-center justify-between text-[11px] font-semibold text-slate-500 pt-2 border-t border-teal-200/40">
                            <span class="flex items-center gap-1 text-teal-700 font-bold">
                                <svg class="h-3.5 w-3.5 text-teal-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                Booked
                            </span>
                            <span class="h-0.5 flex-1 mx-2 bg-teal-400"></span>
                            <span class="flex items-center gap-1 text-teal-700 font-bold">
                                <svg class="h-3.5 w-3.5 text-teal-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                                Assigned
                            </span>
                            <span class="h-0.5 flex-1 mx-2 bg-teal-400"></span>
                            <span class="flex items-center gap-1 text-teal-700 font-bold animate-pulse">
                                In Route
                            </span>
                            <span class="h-0.5 flex-1 mx-2 bg-slate-200"></span>
                            <span class="text-slate-400">Done</span>
                        </div>
                    </div>
                </div>

                <!-- Floating Bottom Badge -->
                <div class="absolute -bottom-5 -left-4 sm:-left-6 rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-xl shadow-slate-900/10 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-500 font-bold">
                        ★
                    </div>
                    <div>
                        <p class="text-xs font-extrabold text-slate-900">4.9 / 5 Rating</p>
                        <p class="text-[11px] text-slate-500">Over 1,200+ completed repairs</p>
                    </div>
                </div>
            </div>
        </section>
        <section id="how-it-works" class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12">
                <div class="max-w-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-300">How it works</p>
                    <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">Home maintenance,
                        without the runaround.</h2>
                </div>
                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    <div class="rounded-3xl border-slate-200 bg-slate-50 p-6 shadow-sm"><span
                            class="text-3xl font-extrabold text-teal-400">01</span>
                        <h3 class="mt-7 font-display text-xl font-extrabold">Tell us what you need</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Share the details and we’ll help match the
                            right service to your home.</p>
                    </div>
                    <div class="rounded-3xl border-slate-200 bg-slate-50 p-6 shadow-sm"><span
                            class="text-3xl font-extrabold text-teal-400">02</span>
                        <h3 class="mt-7 font-display text-xl font-extrabold">Meet your technician</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Get clear scheduling updates and know who is
                            coming before they arrive.</p>
                    </div>
                    <div class="rounded-3xl border-slate-200 bg-slate-50 p-6 shadow-sm"><span
                            class="text-3xl font-extrabold text-teal-400">03</span>
                        <h3 class="mt-7 font-display text-xl font-extrabold">Enjoy peace of mind</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Follow your service from request to completion
                            in one calm, clear dashboard.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer
        class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
        <span>© {{ date('Y') }} HomeTech</span><span>Built for better everyday living.</span></footer>
</body>

</html>
