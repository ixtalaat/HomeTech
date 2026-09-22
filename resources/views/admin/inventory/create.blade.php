@extends('layouts.app')

@section('title', __('Add Inventory Item'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to inventory') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Add Inventory Item') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Opening stock is recorded as a purchase movement.') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.inventory.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="form-label">{{ __('Name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="form-input @error('name') border-rose-300 @enderror">
                    @error('name')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="name_ar" class="form-label">{{ __('Name (Arabic)') }}</label>
                    <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar') }}" dir="rtl"
                        class="form-input @error('name_ar') border-rose-300 @enderror">
                    @error('name_ar')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="sku" class="form-label">{{ __('SKU') }}</label>
                    <input type="text" id="sku" name="sku" value="{{ old('sku') }}"
                        class="form-input @error('sku') border-rose-300 @enderror">
                    @error('sku')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div>
                    <label for="unit" class="form-label">{{ __('Unit') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="unit" name="unit" value="{{ old('unit', 'pcs') }}" required
                        class="form-input @error('unit') border-rose-300 @enderror">
                    @error('unit')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="current_stock" class="form-label">{{ __('Opening Stock') }} <span class="text-rose-500">*</span></label>
                    <input type="number" id="current_stock" name="current_stock" value="{{ old('current_stock', 0) }}" min="0" required
                        class="form-input @error('current_stock') border-rose-300 @enderror">
                    @error('current_stock')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="low_stock_threshold" class="form-label">{{ __('Low-Stock Threshold') }} <span class="text-rose-500">*</span></label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" value="{{ old('low_stock_threshold', 5) }}" min="0" required
                        class="form-input @error('low_stock_threshold') border-rose-300 @enderror">
                    @error('low_stock_threshold')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="unit_cost" class="form-label">{{ __('Unit Cost (EGP)') }} <span class="text-rose-500">*</span></label>
                    <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" value="{{ old('unit_cost', 0) }}" required
                        class="form-input @error('unit_cost') border-rose-300 @enderror">
                    @error('unit_cost')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="notes" class="form-label">{{ __('Notes') }}</label>
                    <input type="text" id="notes" name="notes" value="{{ old('notes') }}"
                        class="form-input @error('notes') border-rose-300 @enderror">
                    @error('notes')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="notes_ar" class="form-label">{{ __('Notes (Arabic)') }}</label>
                    <input type="text" id="notes_ar" name="notes_ar" value="{{ old('notes_ar') }}" dir="rtl"
                        class="form-input @error('notes_ar') border-rose-300 @enderror">
                    @error('notes_ar')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.inventory.index') }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Create Item') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
