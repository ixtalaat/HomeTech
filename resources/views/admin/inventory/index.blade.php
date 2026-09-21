@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Inventory Management') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Track materials, stock levels, and every movement.') }}</p>
        </div>
        <a href="{{ route('admin.inventory.create') }}" class="primary-button">
            <span>{{ __('Add Item') }}</span>
        </a>
    </div>

    @if($lowStockItems->isNotEmpty())
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <h3 class="text-sm font-extrabold text-amber-900">⚠ {{ __('Low Stock (:count items at or below threshold)', ['count' => $lowStockItems->count()]) }}</h3>
            <ul class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 text-sm">
                @foreach ($lowStockItems as $lowItem)
                    <li class="flex items-center justify-between rounded-xl bg-white/70 px-3 py-2">
                        <a href="{{ route('admin.inventory.show', $lowItem) }}" class="font-bold text-slate-900 hover:text-teal-700">
                            {{ $lowItem->name }}
                        </a>
                        <span class="text-xs font-bold {{ $lowItem->current_stock === 0 ? 'text-rose-600' : 'text-amber-700' }}">
                            {{ $lowItem->current_stock }} {{ $lowItem->unit }} {{ __('left') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="sm:w-64">
                <label for="search" class="form-label text-xs">{{ __('Search Item') }}</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('Name or SKU...') }}" class="form-input text-xs py-2">
            </div>
            <div class="sm:w-48">
                <label for="filter" class="form-label text-xs">{{ __('Filter') }}</label>
                <select id="filter" name="filter" class="form-input text-xs py-2" onchange="this.form.submit()">
                    <option value="">{{ __('All Items') }}</option>
                    <option value="low-stock" {{ request('filter') === 'low-stock' ? 'selected' : '' }}>{{ __('Low Stock Only') }}</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="primary-button text-xs py-2 px-4">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search', 'filter']))
                    <a href="{{ route('admin.inventory.index') }}" class="secondary-button text-xs py-2 px-3">{{ __('Reset') }}</a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Item') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Stock') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Unit Cost') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ $item->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $item->sku ?? $item->unit }}</p>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $item->current_stock }} <span class="text-xs font-medium text-slate-400">{{ $item->unit }}</span></td>
                            <td class="px-6 py-4 text-xs">{{ number_format($item->unit_cost, 2) }} {{ __('EGP') }}</td>
                            <td class="px-6 py-4">
                                @if($item->isLowOnStock())
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">{{ __('Low Stock') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ __('OK') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.inventory.show', $item) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">{{ __('View') }}</a>
                                    <a href="{{ route('admin.inventory.edit', $item) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">{{ __('Edit') }}</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">{{ __('No inventory items found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
