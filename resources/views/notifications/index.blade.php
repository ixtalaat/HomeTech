@extends('layouts.app')

@section('title', __('Notifications'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-600">{{ __('Stay updated') }}</p>
            <h2 class="mt-1 font-display text-2xl font-extrabold text-slate-900">{{ __('Notifications') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Updates about your requests, jobs, invoices, and approvals.') }}</p>
        </div>
        <button type="button" id="enable-push" class="secondary-button shrink-0 text-xs">
            {{ __('Enable push notifications') }}
        </button>
        <span id="push-config" class="hidden"
            data-config-url="{{ route('firebase.config') }}"
            data-store-url="{{ route('push-tokens.store') }}"
            data-success="{{ __('Push notifications enabled') }}"
            data-unavailable="{{ __('Push notifications blocked or unavailable') }}"
            data-unsupported="{{ __('Browser notifications are not supported here') }}"></span>
    </div>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <a href="{{ route('notifications.show', $notification) }}"
                class="flex items-start gap-3 rounded-2xl border p-4 shadow-sm transition hover:-translate-y-px hover:border-teal-300 hover:shadow-md {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-teal-200 bg-teal-50/60' }}">
                <span aria-hidden="true" class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-teal-500 animate-pulse' }}"></span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium text-slate-900">{{ __($notification->data['message_key'] ?? 'notifications.fallback', $notification->data['message_params'] ?? []) }}</span>
                    <span class="mt-1 block text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <div class="empty-state">{{ __('No notifications yet.') }}</div>
        @endforelse
    </div>

        @if($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
async function registerPush(interactive) {
    const button = document.getElementById('enable-push');
    const config = document.getElementById('push-config');
    const strings = config ? config.dataset : {};
    const fail = (key) => {
        if (!interactive) {
            return;
        }
        const message = strings[key] || key;
        if (window.toast) {
            window.toast(message, 'error');
        } else {
            alert(message);
        }
    };

    if (!('Notification' in window) || !('serviceWorker' in navigator)) {
        fail('unsupported');
        return;
    }

    // The Firebase SDK loads deferred: on silent refreshes wait for it via page load.
    if (typeof firebase === 'undefined') {
        if (!interactive) {
            window.addEventListener('load', () => registerPush(false), { once: true });
        } else {
            fail('unsupported');
        }
        return;
    }

    if (Notification.permission === 'denied') {
        fail('unavailable');
        return;
    }

    if (Notification.permission !== 'granted') {
        if (!interactive || await Notification.requestPermission() !== 'granted') {
            if (interactive) {
                fail('unavailable');
            }
            return;
        }
    }

    try {
        const firebaseConfig = await (await fetch(config.dataset.configUrl, { headers: { Accept: 'application/json' } })).json();

        if (!firebaseConfig.apiKey || !firebaseConfig.vapidKey) {
            fail('unavailable');
            return;
        }

        const firebaseApp = firebase.apps.length ? firebase.app() : firebase.initializeApp({
            apiKey: firebaseConfig.apiKey,
            authDomain: firebaseConfig.authDomain,
            projectId: firebaseConfig.projectId,
            messagingSenderId: firebaseConfig.senderId,
            appId: firebaseConfig.appId,
        });

        const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
        const token = await firebase.messaging().getToken({ vapidKey: firebaseConfig.vapidKey, serviceWorkerRegistration: registration });

        const response = await fetch(config.dataset.storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ token, platform: 'web', device_name: navigator.platform ?? null }),
        });

        if (!response.ok) {
            throw new Error('store failed');
        }

        button?.remove();

        if (interactive && window.toast) {
            window.toast(strings.success, 'success');
        }
    } catch {
        fail('unavailable');
    }
}

document.getElementById('enable-push')?.addEventListener('click', () => registerPush(true));

// Keep the subscription fresh: re-register silently on every visit when allowed.
if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
    registerPush(false);
}
</script>
@endpush
