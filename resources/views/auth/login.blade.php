@extends('layouts.guest')

@section('title', __('Sign in to HomeTech'))

@section('content')
    <div class="mb-8 lg:hidden"><a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a></div>
    <div class="mb-9">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-400">{{ __('Welcome back') }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight">{{ __('Sign in to HomeTech') }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-400">{{ __('Manage your home services from one simple place.') }}</p>
    </div>
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200" role="alert">
            <p class="font-bold">{{ __("We couldn't sign you in.") }}</p>
            <ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf
        <div><label for="email" class="mb-2 block text-sm font-semibold text-slate-200">{{ __('Email address') }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="form-input" placeholder="you@example.com"></div>
        <div><label for="password" class="mb-2 block text-sm font-semibold text-slate-200">{{ __('Password') }}</label><input id="password" type="password" name="password" autocomplete="current-password" required class="form-input" placeholder="{{ __('Enter your password') }}"></div>
        <label class="flex items-center gap-3 text-sm text-slate-400"><input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-600 bg-slate-800 text-teal-500 focus:ring-teal-500"> {{ __('Keep me signed in') }}</label>
        <div class="text-right text-sm"><a href="{{ route('password.request') }}" class="font-semibold text-slate-400 hover:text-teal-300">{{ __('Forgot password?') }}</a></div>
        <button type="submit" class="primary-button w-full">{{ __('Sign in') }} <span aria-hidden="true">→</span></button>
    </form>
    <p class="mt-8 text-center text-sm text-slate-400">{{ __('New to HomeTech?') }} <a href="{{ route('register') }}" class="font-bold text-teal-400 hover:text-teal-300">{{ __('Create an account') }}</a></p>
@endsection
