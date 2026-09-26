@extends('layouts.app')

@section('title', __('Technicians'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Technicians') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Technicians working for your branch.') }}</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ $branch->name }}</span>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('branch.technicians.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3 items-end">
            <div>
                <label for="search" class="form-label text-xs">{{ __('Search Technician') }}</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('Name or email...') }}" class="form-input text-xs py-2">
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
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('branch.technicians.index') }}" class="secondary-button text-xs py-2 px-3">{{ __('Reset') }}</a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-start text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Technician') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Skills') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($technicians as $technician)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ $technician->user->name ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $technician->user->email ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($technician->categories as $category)
                                        <span class="inline-flex items-center rounded-lg bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700">
                                            {{ $category->display_name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400">{{ __('No skills') }}</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($technician->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ __('Active') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-end">
                                <a href="{{ route('branch.technicians.show', $technician) }}"
                                    class="btn-row">
                                    {{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                {{ __('No technicians found matching your criteria.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($technicians->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $technicians->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
