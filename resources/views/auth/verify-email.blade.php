@extends('layouts.guest')

@section('title', __('Verify email'))

@section('content')
    <div class="mb-8 lg:hidden"><a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a></div>
    <div class="mb-9">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-400">{{ __('Verify email') }}</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight">{{ __('Check your inbox') }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-400">{{ __('We sent a verification link to your email address. Click it to unlock your account.') }}</p>
    </div>
    @if (session('status') === 'verification-link-sent')
        <div class="mb-6 rounded-2xl border-teal-500/30 bg-teal-500/10 p-4 text-sm text-teal-200" role="alert">
            {{ __('A fresh verification link has been sent.') }}
        </div>
    @endif
    <form method="POST" action="{{ route('verification.send') }}" class="space-y-5">
        @csrf
        <button type="submit" class="primary-button w-full">{{ __('Resend verification email') }}</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full text-center text-sm font-semibold text-slate-400 hover:text-teal-300">{{ __('Log out') }}</button>
    </form>
@endsection
