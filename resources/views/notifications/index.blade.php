@extends('layouts.app')

@section('title', __('Notifications'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Notifications') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Updates about your requests, jobs, invoices, and approvals.') }}</p>
    </div>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <a href="{{ route('notifications.show', $notification) }}"
                class="block rounded-2xl border p-4 shadow-sm transition hover:border-teal-300 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-teal-200 bg-teal-50/60' }}">
                <p class="text-sm font-medium text-slate-900">{{ __($notification->data['message_key'] ?? 'notifications.fallback', $notification->data['message_params'] ?? []) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                {{ __('No notifications yet.') }}
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
