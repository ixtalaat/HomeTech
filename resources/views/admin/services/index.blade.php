@extends('layouts.app')

@section('title', __('Services Management'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Services Management') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Configure maintenance services, base pricing, durations, and active statuses.') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.categories.index') }}" class="secondary-button">
                <span>{{ __('Manage Categories') }}</span>
            </a>
            <a href="{{ route('admin.services.create') }}" class="primary-button">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                <span>{{ __('Add New Service') }}</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.services.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4 items-end">
            <div>
                <label for="search" class="form-label text-xs">{{ __('Search Service') }}</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('Search by name...') }}" class="form-input text-xs py-2">
            </div>

            <div>
                <label for="category_id" class="form-label text-xs">{{ __('Category') }}</label>
                <select id="category_id" name="category_id" class="form-input text-xs py-2">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="form-label text-xs">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-input text-xs py-2">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active Only') }}</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive Only') }}</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="primary-button text-xs py-2 px-4 w-full">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search', 'category_id', 'status']))
                    <a href="{{ route('admin.services.index') }}" class="secondary-button text-xs py-2 px-3">{{ __('Reset') }}</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-start text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Service') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Category') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Base Price') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Est. Duration') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($services as $service)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($service->coverPhotoUrl())
                                        <img src="{{ $service->coverPhotoUrl() }}" alt="{{ $service->display_name }}" class="h-10 w-14 shrink-0 rounded-lg border border-slate-200 object-cover">
                                    @endif
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $service->display_name }}</p>
                                        @if($service->translated('description'))
                                            <p class="mt-0.5 max-w-xs truncate text-xs text-slate-400">{{ $service->translated('description') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">
                                    {{ $service->category->display_name ?? __('Uncategorized') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-900">
                                {{ number_format($service->base_price, 2) }} <span class="text-xs font-semibold text-slate-500">{{ __('EGP') }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-slate-600">
                                {{ $service->estimated_duration_minutes }} {{ __('mins') }}
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.services.toggle-status', $service) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        title="{{ __('Click to toggle status') }}"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold transition {{ $service->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $service->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $service->is_active ? __('Active') : __('Inactive') }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-end">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.services.edit', $service) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">
                                        {{ __('Edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.services.destroy', $service) }}" data-confirm="{{ __('Are you sure you want to delete this service?') }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:border-rose-300 hover:bg-rose-50 transition">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                {{ __('No services found matching your criteria.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
