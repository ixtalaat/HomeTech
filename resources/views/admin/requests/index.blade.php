@extends('layouts.app')

@section('title', __('Maintenance Requests'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-600">{{ __('Operations') }}</p>
        <h2 class="mt-1 font-display text-2xl font-extrabold text-slate-900">{{ __('Maintenance Requests') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Review incoming requests, approve or reject them, and adjust appointments.') }}</p>
    </div>

    <details class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm" {{ request()->hasAny(['search', 'status']) ? 'open' : '' }}>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3.5 text-sm font-extrabold text-slate-900 sm:px-5 [&::-webkit-details-marker]:hidden">
            <span>{{ __('Filters') }}{{ request()->hasAny(['search', 'status']) ? ' · '.__('Active') : '' }}</span>
            <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600/10 text-lg font-light leading-none text-teal-700">+</span>
        </summary>
        <div class="border-t border-slate-100 px-4 py-4 sm:px-5">
        <form method="GET" action="{{ route('admin.requests.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3 items-end">
            <div>
                <label for="search" class="form-label text-xs">{{ __('Search') }}</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('Description, customer...') }}" class="form-input text-xs py-2">
            </div>
            <div>
                <label for="status" class="form-label text-xs">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-input text-xs py-2">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="primary-button text-xs py-2 px-4 w-full">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.requests.index') }}" class="secondary-button text-xs py-2 px-3">{{ __('Reset') }}</a>
                @endif
            </div>
        </form>
        </div>
    </details>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="sticky top-0 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Request') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Customer') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Appointment') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($requests as $maintenanceRequest)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">#{{ $maintenanceRequest->id }} · {{ $maintenanceRequest->service->display_name ?? '—' }}</p>
                                <p class="mt-0.5 max-w-xs truncate text-xs text-slate-500">{{ $maintenanceRequest->description }}</p>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium">{{ $maintenanceRequest->user->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs font-medium">
                                {{ $maintenanceRequest->preferred_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <x-status-badge :status="$maintenanceRequest->status" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.requests.show', $maintenanceRequest) }}"
                                    class="btn-row">
                                    {{ __('Review') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8">
                                <div class="empty-state">{{ __('No requests found matching your criteria.') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
