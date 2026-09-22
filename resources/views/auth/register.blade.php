@extends('layouts.guest')

@section('title', __('Create your account'))

@section('content')
    <div class="mb-8 lg:hidden"><a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a></div>
    <div class="mb-8"><p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-400">{{ __('Get started') }}</p><h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight">{{ __('Create your account') }}</h1><p class="mt-3 text-sm leading-6 text-slate-400">{{ __('Join HomeTech and take the stress out of home maintenance.') }}</p></div>
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200" role="alert"><p class="font-bold">{{ __('Please check your details.') }}</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <div><label for="name" class="mb-2 block text-sm font-semibold text-slate-200">{{ __('Full name') }}</label><input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus class="form-input" placeholder="{{ __('Jane Smith') }}"></div>
        <div><label for="phone" class="mb-2 block text-sm font-semibold text-slate-200">{{ __('Phone') }} <span class="font-normal text-slate-500">({{ __('optional') }})</span></label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" class="form-input" placeholder="+20 100 000 0000"></div>
        <div><label for="email" class="mb-2 block text-sm font-semibold text-slate-200">{{ __('Email address') }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required class="form-input" placeholder="you@example.com"></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label for="password" class="mb-2 block text-sm font-semibold text-slate-200">{{ __('Password') }}</label><input id="password" type="password" name="password" autocomplete="new-password" required class="form-input" placeholder="{{ __('8+ characters') }}"></div><div><label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-200">{{ __('Confirm password') }}</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="form-input" placeholder="{{ __('Repeat password') }}"></div></div>
        <button type="submit" class="primary-button w-full">{{ __('Create account') }} <span aria-hidden="true">→</span></button>
    </form>
    <p class="mt-7 text-center text-sm text-slate-400">{{ __('Already have an account?') }} <a href="{{ route('login') }}" class="font-bold text-teal-400 hover:text-teal-300">{{ __('Sign in') }}</a></p>
@endsection
