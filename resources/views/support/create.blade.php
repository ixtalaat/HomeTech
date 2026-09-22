@extends('layouts.app')

@section('title', __('Contact support'))

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Contact support') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Tell us what you need help with and our team will get back to you.') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="relative overflow-hidden rounded-2xl bg-slate-900 p-6 text-white sm:p-8">
            <div class="pointer-events-none absolute -top-12 left-1/2 h-28 w-44 -translate-x-1/2 rounded-full bg-teal-500/25 blur-2xl" aria-hidden="true"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10 text-teal-300">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="2.8" y="13.5" width="4" height="6.5" rx="2"/><rect x="17.2" y="13.5" width="4" height="6.5" rx="2"/><path d="M19.5 20a4.5 4.5 0 0 1-4.5 3H13"/></svg>
                </span>
                <div>
                    <p class="text-sm font-bold">{{ __('Need a hand?') }}</p>
                    <p class="mt-0.5 flex items-center gap-1.5 text-[11px] font-semibold text-slate-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>{{ __('Support is online') }}
                    </p>
                </div>
            </div>
            <p class="relative mt-4 text-xs leading-6 text-slate-300 text-balance">{{ __('Our support team is ready to help with your next service.') }}</p>
            <p class="relative mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-slate-400">
                <svg class="h-3.5 w-3.5 text-teal-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/></svg>
                {{ __('We usually reply within one business day.') }}
            </p>

            <div class="relative mt-6 flex items-center gap-3 rounded-xl bg-white/5 p-3 ring-1 ring-white/10">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-500/20 text-sm font-extrabold text-teal-300">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('Sending as') }}</p>
                    <p class="truncate text-xs font-bold text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[11px] text-slate-400">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('support.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="subject" class="form-label">{{ __('Subject') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="120"
                        placeholder="{{ __('Summarize your issue in a few words…') }}"
                        class="form-input @error('subject') border-rose-300 ring-rose-100 @enderror">
                    @error('subject')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="message" class="form-label">{{ __('Message') }} <span class="text-rose-500">*</span></label>
                    <textarea id="message" name="message" rows="6" required maxlength="2000"
                        placeholder="{{ __('Write the details so our team can help faster…') }}"
                        class="form-input @error('message') border-rose-300 ring-rose-100 @enderror">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                    <a href="{{ route('dashboard') }}" class="secondary-button">{{ __('Back to dashboard') }}</a>
                    <button type="submit" class="primary-button">{{ __('Send message') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
