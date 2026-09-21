@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
            <span>{{ __('Back to categories') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Add New Category') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Create a service category for grouping home maintenance jobs.') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="form-label">{{ __('Category Name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="form-input @error('name') border-rose-300 ring-rose-100 @enderror"
                        placeholder="{{ __('e.g. Air Conditioning') }}">
                    @error('name')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="name_ar" class="form-label">{{ __('Category Name (Arabic)') }}</label>
                    <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar') }}" dir="rtl"
                        class="form-input @error('name_ar') border-rose-300 ring-rose-100 @enderror"
                        placeholder="{{ __('e.g. تكييف') }}">
                    @error('name_ar')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="slug" class="form-label">{{ __('Slug (Optional)') }}</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                    class="form-input @error('slug') border-rose-300 ring-rose-100 @enderror"
                    placeholder="{{ __('e.g. air-conditioning (leave blank to auto-generate)') }}">
                @error('slug')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" rows="3"
                    class="form-input @error('description') border-rose-300 ring-rose-100 @enderror"
                    placeholder="{{ __('Brief description of the services offered in this category...') }}">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description_ar" class="form-label">{{ __('Description (Arabic)') }}</label>
                <textarea id="description_ar" name="description_ar" rows="3" dir="rtl"
                    class="form-input @error('description_ar') border-rose-300 ring-rose-100 @enderror">{{ old('description_ar') }}</textarea>
                @error('description_ar')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="icon" class="form-label">{{ __('Icon identifier (Optional)') }}</label>
                <input type="text" id="icon" name="icon" value="{{ old('icon') }}"
                    class="form-input @error('icon') border-rose-300 ring-rose-100 @enderror"
                    placeholder="{{ __('e.g. snowflake, wrench, zap') }}">
                @error('icon')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_active" class="text-sm font-semibold text-slate-700">{{ __('Category is active and visible') }}</label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.categories.index') }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Create Category') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
