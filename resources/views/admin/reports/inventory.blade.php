@extends('layouts.app')

@section('title', __('Inventory Reports'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to dashboard') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Inventory Reports') }}</h2>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Current Stock') }}</h3>
            <ul class="mt-3 max-h-72 space-y-1 overflow-y-auto text-sm">
                @forelse ($items as $item)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ $item->display_name }}</span>
                        <span class="font-bold {{ $item->isLowOnStock() ? 'text-amber-600' : 'text-slate-900' }}">{{ $item->current_stock }} {{ $item->unit }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No items yet.') }}</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Low-Stock Items') }}</h3>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($low_stock as $item)
                    <li class="flex items-center justify-between rounded-lg bg-amber-50 px-3 py-1.5">
                        <span class="font-medium text-slate-700">{{ $item->display_name }}</span>
                        <span class="font-bold text-amber-700">{{ $item->current_stock }} / {{ $item->low_stock_threshold }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No low-stock items. All clear!') }}</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Most-Used Materials') }}</h3>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($most_used as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ $row->name }}</span>
                            <span class="font-bold text-slate-900">{{ $row->moved }} {{ __('consumed') }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No consumption recorded yet.') }}</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Recent Movements') }}</h3>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($recent_movements as $movement)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ $movement->item->display_name ?? '—' }} · {{ $movement->type->label() }}</span>
                        <span class="font-extrabold {{ $movement->quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No movements recorded yet.') }}</p>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
