@extends('layouts.app')

@section('title', __('Edit Customer: :name', ['name' => $customer->name]))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('admin.customers.show', $customer) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
            <span>{{ __('Back to customer') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Edit Customer: :name', ['name' => $customer->name]) }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Update profile details or activation status.') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="form-label">{{ __('Name') }} <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required
                    class="form-input @error('name') border-rose-300 ring-rose-100 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="form-label">{{ __('Phone') }}</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}"
                    class="form-input @error('phone') border-rose-300 ring-rose-100 @enderror">
                @error('phone')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="form-label">{{ __('Email (read-only)') }}</label>
                <input type="text" value="{{ $customer->email }}" disabled class="form-input bg-slate-50 text-slate-500">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $customer->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_active" class="text-sm font-semibold text-slate-700">{{ __('Account is active') }}</label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.customers.show', $customer) }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Update Customer') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
