@extends('layouts.guest')

@section('content')
    <div class="mb-8 lg:hidden"><a href="{{ url('/') }}" class="flex items-center gap-3 text-xl font-extrabold tracking-tight"><span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-teal-500 text-lg text-white">H</span> Home<span class="text-teal-400">Tech</span></a></div>
    <div class="mb-8"><p class="text-sm font-bold uppercase tracking-[0.18em] text-teal-400">Get started</p><h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight">Create your account</h1><p class="mt-3 text-sm leading-6 text-slate-400">Join HomeTech and take the stress out of home maintenance.</p></div>
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border-red-500/30 bg-red-500/10 p-4 text-sm text-red-200" role="alert"><p class="font-bold">Please check your details.</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <div><label for="name" class="mb-2 block text-sm font-semibold text-slate-200">Full name</label><input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus class="form-input" placeholder="Jane Smith"></div>
        <div><label for="phone" class="mb-2 block text-sm font-semibold text-slate-200">Phone <span class="font-normal text-slate-500">(optional)</span></label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" class="form-input" placeholder="+1 555 000 0000"></div>
        <div><label for="email" class="mb-2 block text-sm font-semibold text-slate-200">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required class="form-input" placeholder="you@example.com"></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label for="password" class="mb-2 block text-sm font-semibold text-slate-200">Password</label><input id="password" type="password" name="password" autocomplete="new-password" required class="form-input" placeholder="8+ characters"></div><div><label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-200">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="form-input" placeholder="Repeat password"></div></div>
        <button type="submit" class="primary-button w-full">Create account <span aria-hidden="true">→</span></button>
    </form>
    <p class="mt-7 text-center text-sm text-slate-400">Already have an account? <a href="{{ route('login') }}" class="font-bold text-teal-400 hover:text-teal-300">Sign in</a></p>
@endsection
