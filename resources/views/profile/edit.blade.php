@extends('layouts.app')

@section('title', __('Profile settings'))

@php($heading = 'Your profile')

@section('content')
    <div class="mx-auto max-w-4xl"><div class="mb-8"><p class="text-sm font-semibold text-slate-500">{{ __('Keep your contact details current for smoother service.') }}</p><h2 class="mt-2 font-display text-3xl font-extrabold tracking-tight">{{ __('Profile settings') }}</h2></div>
    <div class="mb-6 rounded-3xl border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.14em] text-teal-600">{{ __('Phone verification') }}</p>
                <p class="mt-1 text-sm text-slate-500">
                    @if($user->phone_verified_at)
                        <span class="font-bold text-emerald-600">✓ {{ $user->phone }} {{ __('is verified.') }}</span>
                    @elseif($user->phone)
                        {{ $user->phone }} {{ __('is not verified yet. Confirm it via WhatsApp.') }}
                    @else
                        {{ __('Add a phone number above first, then verify it via WhatsApp.') }}
                    @endif
                </p>
            </div>
            @unless($user->phone_verified_at)
                <form method="POST" action="{{ route('phone.send-code') }}" class="inline">
                    @csrf
                    <button type="submit" class="secondary-button text-xs">{{ __('Send WhatsApp Code') }}</button>
                </form>
            @endunless
        </div>
        @unless($user->phone_verified_at)
            <form method="POST" action="{{ route('phone.verify') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                @csrf
                <input type="text" name="code" inputmode="numeric" maxlength="6" required placeholder="{{ __('6-digit code') }}" aria-label="{{ __('Verification code') }}" class="form-input sm:w-48">
                <button type="submit" class="secondary-button text-xs">{{ __('Verify Code') }}</button>
            </form>
        @endunless
    </div>
    @if (session('status'))<div class="mb-6 rounded-2xl border-teal-200 bg-teal-50 p-4 text-sm font-semibold text-teal-800" role="status">{{ __('Your :thing has been updated successfully.', ['thing' => session('status') === 'profile-updated' ? __('Profile') : __('Password')]) }}</div>@endif
    @if ($errors->any())<div class="mb-6 rounded-2xl border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert"><p class="font-bold">{{ __('Please check the highlighted details.') }}</p><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="grid gap-6 lg:grid-cols-2"><section class="rounded-3xl border-slate-200 bg-white p-6 shadow-sm sm:p-8"><div class="mb-7"><p class="text-sm font-bold uppercase tracking-[0.14em] text-teal-600">{{ __('Personal details') }}</p><h3 class="mt-2 font-display text-xl font-extrabold">{{ __('Your contact information') }}</h3></div><form method="POST" action="{{ route('profile.update') }}" class="space-y-5">@csrf @method('PUT')<div><label for="name" class="form-label">{{ __('Full name') }}</label><input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input"></div><div><label for="phone" class="form-label">{{ __('Phone number') }}</label><input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input"></div><div><label for="email" class="form-label">{{ __('Email address') }}</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input"></div><button type="submit" class="primary-button">{{ __('Save changes') }}</button></form></section><section class="rounded-3xl border-slate-200 bg-white p-6 shadow-sm sm:p-8"><div class="mb-7"><p class="text-sm font-bold uppercase tracking-[0.14em] text-teal-600">{{ __('Security') }}</p><h3 class="mt-2 font-display text-xl font-extrabold">{{ __('Change password') }}</h3></div><form method="POST" action="{{ route('password.update') }}" class="space-y-5">@csrf @method('PUT')<div><label for="current_password" class="form-label">{{ __('Current password') }}</label><input id="current_password" type="password" name="current_password" required class="form-input"></div><div><label for="password" class="form-label">{{ __('New password') }}</label><input id="password" type="password" name="password" required class="form-input"></div><div><label for="password_confirmation" class="form-label">{{ __('Confirm new password') }}</label><input id="password_confirmation" type="password" name="password_confirmation" required class="form-input"></div><button type="submit" class="secondary-button">{{ __('Update password') }}</button></form></section></div></div>
@endsection
