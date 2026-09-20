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
        <a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo variant="light" /></a>
        <nav class="flex items-center gap-3 text-sm font-bold"><a href="{{ route('login') }}"
                class="rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-slate-950">Sign in</a><a
                href="{{ route('register') }}" class="primary-button">Get started <span aria-hidden="true">→</span></a>
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
                    <div class="flex -space-x-2"><span
                            class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-amber-300 text-xs font-bold text-amber-900">A</span><span
                            class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-sky-300 text-xs font-bold text-sky-900">M</span><span
                            class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-slate-50 bg-rose-300 text-xs font-bold text-rose-900">J</span>
                    </div><span>Simple service coordination for modern households.</span>
                </div>
            </div>
            <div class="relative">
                <div
                    class="rounded-[2rem] border-slate-200 bg-white p-3 shadow-2xl shadow-slate-900/10">
                    <div class="rounded-[1.5rem] bg-slate-900 p-6 text-white sm:p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-300">Your home
                                    overview</p>
                                <p class="mt-2 font-display text-2xl font-extrabold">Everything in one place</p>
                            </div><span
                                class="rounded-xl bg-teal-400/10 px-3 py-2 text-xs font-bold text-teal-300">Live</span>
                        </div>
                        <div class="mt-8 grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-white/[0.06] p-4">
                                <p class="text-xs text-slate-400">Active services</p>
                                <p class="mt-2 text-2xl font-extrabold">03</p>
                                <div class="mt-3 h-1.5 rounded-full bg-teal-500"></div>
                            </div>
                            <div class="rounded-2xl bg-white/[0.06] p-4">
                                <p class="text-xs text-slate-400">Next visit</p>
                                <p class="mt-2 text-2xl font-extrabold">Fri</p>
                                <p class="mt-3 text-xs text-slate-400">10:30 AM</p>
                            </div>
                        </div>
                        <div class="mt-4 rounded-2xl border-white/10 bg-white/[0.04] p-4">
                            <div class="flex items-center gap-3"><span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400/15 text-amber-300">✓</span>
                                <div>
                                    <p class="text-sm font-bold">AC maintenance</p>
                                    <p class="mt-1 text-xs text-slate-400">Technician confirmed · Tomorrow</p>
                                </div><span class="ml-auto text-slate-500">›</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="absolute -bottom-5 -left-5 rounded-2xl border-white/10 bg-white p-4 text-slate-900 shadow-xl sm:-left-8">
                    <p class="text-xs font-bold text-teal-700">Trusted by homeowners</p>
                    <p class="mt-1 font-display text-lg font-extrabold">Less stress. More comfort.</p>
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
