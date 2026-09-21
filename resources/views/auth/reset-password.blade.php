@extends('layouts.guest')

@section('content')
    <div class="mb-8 lg:hidden"><a href="{{ url('/') }}" aria-label="HomeTech home"><x-brand-logo /></a></div>
    <div class="mb-9">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-400">Reset password</p>
        <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight">Choose a new password</h1>
    </div>
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200" role="alert">
            <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div><label for="email" class="mb-2 block text-sm font-semibold text-slate-200">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="form-input" placeholder="you@example.com"></div>
        <div><label for="password" class="mb-2 block text-sm font-semibold text-slate-200">New password</label><input id="password" type="password" name="password" autocomplete="new-password" required class="form-input" placeholder="At least 8 characters"></div>
        <div><label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-200">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="form-input" placeholder="Repeat your password"></div>
        <button type="submit" class="primary-button w-full">Reset password <span aria-hidden="true">→</span></button>
    </form>
@endsection
