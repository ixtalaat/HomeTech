<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ __('Contact Us') }} — {{ __('HomeTech') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-900 antialiased">
    <header class="relative z-10 border-b border-slate-200/80 bg-white/90 px-5 py-6 backdrop-blur sm:px-8 lg:px-12"><div class="mx-auto flex max-w-7xl items-center justify-between">
        <a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a>
        <nav class="flex items-center gap-3 text-sm font-bold" aria-label="{{ __('Home navigation') }}">
            <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}" class="rounded-xl border border-slate-200 px-3 py-2.5 text-slate-600 transition hover:border-teal-300 hover:text-teal-700" aria-label="{{ __('Switch language') }}">{{ app()->getLocale() === 'ar' ? 'EN' : 'عربي' }}</a>
            <a href="{{ route('about') }}" class="rounded-xl px-4 py-2.5 text-slate-600 transition hover:text-teal-700">{{ __('About Us') }}</a>
            <a href="{{ route('contact.create') }}" class="rounded-xl px-4 py-2.5 text-teal-700 transition">{{ __('Contact Us') }}</a>
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

        <section class="relative mx-auto grid max-w-7xl items-start gap-10 px-5 py-16 sm:px-8 lg:grid-cols-[1fr_1.1fr] lg:px-12 lg:py-24">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border-teal-200 bg-teal-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] text-teal-700">
                    <span class="h-2 w-2 rounded-full bg-teal-300"></span> {{ __('Contact Us') }}</p>
                <h1 class="mt-6 font-display text-4xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-5xl">
                    {{ __('Questions? Write to us.') }}
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">{{ __('Feedback, questions, or partnership inquiries — our team reads every message.') }}</p>
                <div class="mt-8 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-xs font-extrabold text-teal-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="2.8" y="13.5" width="4" height="6.5" rx="2"/><rect x="17.2" y="13.5" width="4" height="6.5" rx="2"/><path d="M19.5 20a4.5 4.5 0 0 1-4.5 3H13"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ __('Need a hand?') }}</p>
                        <p class="text-xs leading-5 text-slate-500">{{ __('We usually reply within one business day.') }}</p>
                    </div>
                </div>
                @auth
                    <p class="mt-4 text-sm text-slate-500">{{ __('Signed in? You can also reach us from the :link page.', ['link' => __('Contact support')]) }}</p>
                @endauth
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur-sm sm:p-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border-red-500/30 bg-red-500/10 p-4 text-sm text-red-600" role="alert">
                        <p class="font-bold">{{ __('Please check your details.') }}</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @if (session('success'))
                    <div class="mb-6 rounded-2xl border-teal-500/30 bg-teal-500/10 p-4 text-sm font-semibold text-teal-700" role="status">
                        {{ session('success') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">{{ __('Full name') }}</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required class="form-input" placeholder="{{ __('Jane Smith') }}">
                        </div>
                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">{{ __('Email address') }}</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required class="form-input" placeholder="you@example.com" dir="ltr">
                        </div>
                    </div>
                    <div>
                        <label for="subject" class="mb-2 block text-sm font-semibold text-slate-700">{{ __('Subject') }}</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" required maxlength="120" class="form-input">
                    </div>
                    <div>
                        <label for="message" class="mb-2 block text-sm font-semibold text-slate-700">{{ __('Message') }}</label>
                        <textarea id="message" name="message" rows="5" required maxlength="2000" class="form-input">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="primary-button w-full py-3.5">{{ __('Send message') }} <span aria-hidden="true">→</span></button>
                </form>
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
