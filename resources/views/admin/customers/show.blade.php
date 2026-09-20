@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
            <span>Back to customers</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ $customer->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $customer->email }} · Joined {{ $customer->created_at->format('d M Y') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900">Profile</h3>
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $customer->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                {{ $customer->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Name</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $customer->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Phone</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $customer->phone ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $customer->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Addresses</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $customer->addresses->count() }}</dd>
            </div>
        </dl>
        <div class="mt-6 flex items-center gap-2">
            <a href="{{ route('admin.customers.edit', $customer) }}" class="secondary-button text-xs">Edit Customer</a>
            <form method="POST" action="{{ route('admin.customers.toggle-status', $customer) }}" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="secondary-button text-xs">{{ $customer->is_active ? 'Deactivate' : 'Activate' }}</button>
            </form>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-extrabold text-slate-900">Addresses ({{ $customer->addresses->count() }})</h3>
        @forelse ($customer->addresses as $address)
            <div class="mt-4 rounded-xl border border-slate-100 p-4">
                <div class="flex items-center justify-between">
                    <p class="font-bold text-slate-900">{{ $address->title }}</p>
                    @if($address->is_default)
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700">Default</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-slate-600">{{ $address->street }}, {{ $address->city }}</p>
                @if($address->notes)
                    <p class="mt-1 text-xs text-slate-400">{{ $address->notes }}</p>
                @endif
            </div>
        @empty
            <p class="mt-3 text-sm text-slate-500">No addresses saved yet.</p>
        @endforelse
    </div>
</div>
@endsection
