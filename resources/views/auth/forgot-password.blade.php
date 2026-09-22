@extends('layouts.guest')

@section('title', __('Forgot your password?'))

@section('content')
    <div class="mb-8 lg:hidden"><a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a></div>
    <div class="mb-9">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-400">{{ __('Reset Password') }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight">{{ __('Forgot your password?') }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-400">{{ __('Enter your account email and we will send you a reset link.') }}</p>
    </div>
    @if (session('status'))
        <div class="mb-6 rounded-2xl border-teal-500/30 bg-teal-500/10 p-4 text-sm text-teal-200" role="alert">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200" role="alert">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div><label for="email" class="mb-2 block text-sm font-semibold text-slate-200">{{ __('Email address') }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="form-input" placeholder="you@example.com"></div>
        <button type="submit" class="primary-button w-full">{{ __('Send reset link') }} <span aria-hidden="true">→</span></button>
    </form>
    <p class="mt-8 text-center text-sm text-slate-400">{{ __('Remembered it?') }} <a href="{{ route('login') }}" class="font-bold text-teal-400 hover:text-teal-300">{{ __('Sign in') }}</a></p>
@endsection
