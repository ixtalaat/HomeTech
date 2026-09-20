<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'HomeTech' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 text-white antialiased">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
        <section class="relative hidden overflow-hidden bg-teal-700 p-12 lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-teal-500/40 blur-3xl"></div>
            <div class="relative">
                <a href="{{ url('/') }}" aria-label="HomeTech home">
                    <x-brand-logo variant="light" />
                </a>
                <div class="mt-28 max-w-lg">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-teal-200">Care for your home</p>
                    <h1 class="mt-5 font-display text-5xl font-extrabold leading-tight tracking-tight">Reliable help for
                        every corner of home.</h1>
                    <p class="mt-6 max-w-md text-lg leading-8 text-teal-50/80">Book trusted maintenance, follow your
                        service, and keep your home running smoothly.</p>
                </div>
            </div>
            <p class="relative text-sm text-teal-100/70">Trusted service coordination for modern households.</p>
        </section>
        <main class="flex items-center justify-center bg-slate-950 px-5 py-12 sm:px-10">
            <div class="w-full max-w-md">@yield('content')</div>
        </main>
    </div>
</body>

</html>
