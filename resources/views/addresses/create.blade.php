@extends('layouts.app')

@section('title', __('Add Address'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('addresses.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to addresses') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Add Address') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Save a service location for future maintenance requests.') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('addresses.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="title" class="form-label">{{ __('Address Title') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="{{ __('Home, Office...') }}"
                        class="form-input @error('title') border-rose-300 @enderror">
                    @error('title')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="city" class="form-label">{{ __('City') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="city" name="city" value="{{ old('city') }}" required
                        class="form-input @error('city') border-rose-300 @enderror">
                    @error('city')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="street" class="form-label">{{ __('Building / Street') }} <span class="text-rose-500">*</span></label>
                <input type="text" id="street" name="street" value="{{ old('street') }}" required
                    class="form-input @error('street') border-rose-300 @enderror">
                @error('street')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="form-label">{{ __('Additional Notes') }}</label>
                <textarea id="notes" name="notes" rows="3" class="form-input @error('notes') border-rose-300 @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_default" class="text-sm font-semibold text-slate-700">{{ __('Set as default address') }}</label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('addresses.index') }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Save Address') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
