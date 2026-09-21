@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
            <span>{{ __('Back to services') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Edit Service: :name', ['name' => $service->name]) }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Update pricing, category, description, or activation status.') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.services.update', $service) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="service_category_id" class="form-label">{{ __('Service Category') }} <span class="text-rose-500">*</span></label>
                <select id="service_category_id" name="service_category_id" required
                    class="form-input @error('service_category_id') border-rose-300 ring-rose-100 @enderror">
                    <option value="">Select Category</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('service_category_id', $service->service_category_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('service_category_id')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="form-label">{{ __('Service Name') }} <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $service->name) }}" required
                    class="form-input @error('name') border-rose-300 ring-rose-100 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="form-label">{{ __('Slug') }}</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $service->slug) }}"
                    class="form-input @error('slug') border-rose-300 ring-rose-100 @enderror">
                @error('slug')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="base_price" class="form-label">{{ __('Base Price (EGP)') }} <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" id="base_price" name="base_price" value="{{ old('base_price', $service->base_price) }}" required
                        class="form-input @error('base_price') border-rose-300 ring-rose-100 @enderror">
                    @error('base_price')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="estimated_duration_minutes" class="form-label">{{ __('Estimated Duration (Minutes)') }} <span class="text-rose-500">*</span></label>
                    <input type="number" min="5" max="1440" id="estimated_duration_minutes" name="estimated_duration_minutes" value="{{ old('estimated_duration_minutes', $service->estimated_duration_minutes) }}" required
                        class="form-input @error('estimated_duration_minutes') border-rose-300 ring-rose-100 @enderror">
                    @error('estimated_duration_minutes')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea id="description" name="description" rows="4"
                    class="form-input @error('description') border-rose-300 ring-rose-100 @enderror">{{ old('description', $service->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $service->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_active" class="text-sm font-semibold text-slate-700">{{ __('Service is active and available for customer booking') }}</label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.services.index') }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Update Service') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection