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
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 text-xl font-extrabold tracking-tight text-slate-900">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-2xl bg-teal-600 text-lg text-white shadow-lg shadow-teal-600/20">H</span>
                Home<span class="text-teal-600">Tech</span>
            </a>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Service hub</p>
            <nav class="mt-10 flex-col gap-2" aria-label="Main navigation">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-xl bg-teal-50 px-4 py-3 text-sm font-bold text-teal-700">⌂
                    <span>Overview</span></a>
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-900">◎
                    <span>My profile</span></a>
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
                    <a href="{{ route('profile.edit') }}"
                        class="hidden text-sm font-semibold text-slate-500 transition hover:text-slate-900 sm:block">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            class="rounded-xl border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700"
                            type="submit">Log out</button>
                    </form>
                </div>
            </header>
            <div class="px-5 py-8 sm:px-8 lg:px-12 lg:py-10">@yield('content')</div>
        </main>
    </div>
</body>

</html>
