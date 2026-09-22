@extends('layouts.app')

@section('title', $inventoryItem->display_name)

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to inventory') }}</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ $inventoryItem->display_name }}</h2>
            @if($inventoryItem->isLowOnStock())
                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">{{ __('Low Stock') }}</span>
            @endif
        </div>
        <p class="mt-1 text-sm text-slate-500">
            {{ __(':stock :unit in stock', ['stock' => $inventoryItem->current_stock, 'unit' => $inventoryItem->unit]) }} ·
            {{ number_format($inventoryItem->unit_cost, 2) }} {{ __('EGP') }}/{{ $inventoryItem->unit }} ·
            {{ __('threshold :value', ['value' => $inventoryItem->low_stock_threshold]) }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Manual Adjustment') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ __('Positive restocks, negative corrects. Never below zero; every change is logged.') }}</p>
            <form method="POST" action="{{ route('admin.inventory.adjust', $inventoryItem) }}" class="mt-3 space-y-3">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="delta" class="form-label text-xs">{{ __('Quantity Change') }}</label>
                        <input type="number" id="delta" name="delta" required placeholder="+10 / -2" class="form-input text-xs">
                    </div>
                    <div>
                        <label for="reason" class="form-label text-xs">{{ __('Reason') }} <span class="text-rose-500">*</span></label>
                        <input type="text" id="reason" name="reason" required class="form-input text-xs">
                    </div>
                </div>
                <button type="submit" class="secondary-button w-full text-xs">{{ __('Apply Adjustment') }}</button>
            </form>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('admin.inventory.edit', $inventoryItem) }}" class="secondary-button text-xs">{{ __('Edit Item') }}</a>
                <form method="POST" action="{{ route('admin.inventory.destroy', $inventoryItem) }}" data-confirm="{{ __('Delete this item?') }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition">{{ __('Delete') }}</button>
                </form>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Movement Ledger') }}</h3>
            <ol class="mt-3 space-y-3">
                @forelse ($inventoryItem->movements as $movement)
                    <li class="flex items-center justify-between text-sm">
                        <div>
                            <p class="font-bold text-slate-900">{{ $movement->type->label() }}</p>
                            <p class="text-xs text-slate-400">{{ $movement->created_at->format('d M Y, h:i A') }}{{ $movement->notes ? " · {$movement->notes}" : '' }}</p>
                        </div>
                        <span class="font-extrabold {{ $movement->quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                        </span>
                    </li>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No movements recorded.') }}</p>
                @endforelse
            </ol>
        </div>
    </div>
</div>
@endsection
