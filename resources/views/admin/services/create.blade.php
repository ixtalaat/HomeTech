@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
            <span>Back to services</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">Add New Service</h2>
        <p class="mt-1 text-sm text-slate-500">Add a new home maintenance service with base price and estimated duration.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.services.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="service_category_id" class="form-label">Service Category <span class="text-rose-500">*</span></label>
                <select id="service_category_id" name="service_category_id" required
                    class="form-input @error('service_category_id') border-rose-300 ring-rose-100 @enderror">
                    <option value="">Select Category</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('service_category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('service_category_id')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="form-label">Service Name <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="form-input @error('name') border-rose-300 ring-rose-100 @enderror"
                    placeholder="e.g. AC Filter Wash & Deep Cleaning">
                @error('name')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="form-label">Slug (Optional)</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                    class="form-input @error('slug') border-rose-300 ring-rose-100 @enderror"
                    placeholder="e.g. ac-filter-wash (leave blank to auto-generate)">
                @error('slug')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="base_price" class="form-label">Base Price (EGP) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" id="base_price" name="base_price" value="{{ old('base_price', '0.00') }}" required
                        class="form-input @error('base_price') border-rose-300 ring-rose-100 @enderror"
                        placeholder="350.00">
                    @error('base_price')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="estimated_duration_minutes" class="form-label">Estimated Duration (Minutes) <span class="text-rose-500">*</span></label>
                    <input type="number" min="5" max="1440" id="estimated_duration_minutes" name="estimated_duration_minutes" value="{{ old('estimated_duration_minutes', '60') }}" required
                        class="form-input @error('estimated_duration_minutes') border-rose-300 ring-rose-100 @enderror"
                        placeholder="60">
                    @error('estimated_duration_minutes')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="4"
                    class="form-input @error('description') border-rose-300 ring-rose-100 @enderror"
                    placeholder="Describe what is included in this service, typical diagnosis, and prerequisites...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_active" class="text-sm font-semibold text-slate-700">Service is active and available for customer booking</label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.services.index') }}" class="secondary-button">Cancel</a>
                <button type="submit" class="primary-button">Create Service</button>
            </div>
        </form>
    </div>
</div>
@endsection
