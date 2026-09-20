@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">My Addresses</h2>
            <p class="mt-1 text-sm text-slate-500">Manage your service addresses. Maintenance requests must use one of your own addresses.</p>
        </div>
        <a href="{{ route('addresses.create') }}" class="primary-button">
            <span>Add New Address</span>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @forelse ($addresses as $address)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="font-bold text-slate-900">{{ $address->title }}</p>
                    @if($address->is_default)
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700">Default</span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $address->street }}</p>
                <p class="text-sm text-slate-600">{{ $address->city }}</p>
                @if($address->notes)
                    <p class="mt-2 text-xs text-slate-400">{{ $address->notes }}</p>
                @endif
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <a href="{{ route('addresses.edit', $address) }}"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">Edit</a>
                    @unless($address->is_default)
                        <form method="POST" action="{{ route('addresses.set-default', $address) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-teal-700 hover:border-teal-300 hover:bg-teal-50 transition">Set Default</button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('addresses.destroy', $address) }}" onsubmit="return confirm('Delete this address?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:border-rose-300 hover:bg-rose-50 transition">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">
                No addresses yet. Add your first service address to get started.
            </div>
        @endforelse
    </div>
</div>
@endsection
