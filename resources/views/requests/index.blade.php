@extends('layouts.app')

@section('title', __('My Maintenance Requests'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-600">{{ __('Service tracking') }}</p>
            <h2 class="mt-1 font-display text-2xl font-extrabold text-slate-900">{{ __('My Maintenance Requests') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Track your requests from submission through completion.') }}</p>
        </div>
        <a href="{{ route('requests.create') }}" class="primary-button">
            <span>{{ __('New Request') }}</span>
        </a>
    </div>

    <details class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm" {{ request('status') ? 'open' : '' }}>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3.5 text-sm font-extrabold text-slate-900 [&::-webkit-details-marker]:hidden">
            <span>{{ __('Filter by Status') }}{{ request('status') ? ' · '.__('Active') : '' }}</span>
            <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-600/10 text-lg font-light leading-none text-teal-700">+</span>
        </summary>
        <form method="GET" action="{{ route('requests.index') }}" class="flex flex-col gap-4 border-t border-slate-100 px-4 py-4 sm:flex-row sm:items-end">
            <div class="sm:w-64">
                <label for="status" class="form-label text-xs">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-input text-xs py-2" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(request('status'))
                <a href="{{ route('requests.index') }}" class="secondary-button text-xs py-2 px-3">{{ __('Reset') }}</a>
            @endif
        </form>
    </details>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="sticky top-0 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Service') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Preferred Appointment') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($requests as $maintenanceRequest)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ $maintenanceRequest->service->display_name ?? '—' }}</p>
                                <p class="mt-0.5 max-w-xs truncate text-xs text-slate-500">{{ $maintenanceRequest->description }}</p>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium">
                                {{ $maintenanceRequest->preferred_date->format('d M Y') }} at {{ \Carbon\Carbon::parse($maintenanceRequest->preferred_time)->format('h:i A') }}
                            </td>
                            <td class="px-6 py-4">
                                <x-status-badge :status="$maintenanceRequest->status" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('requests.show', $maintenanceRequest) }}"
                                    class="btn-row">
                                    {{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8">
                                <div class="empty-state">
                                    <p class="font-bold text-slate-900">{{ __('No requests yet.') }}</p>
                                    <p class="mt-1">{{ __('Submit your first request and track it here.') }}</p>
                                    <a href="{{ route('requests.create') }}" class="primary-button mt-4 text-xs">{{ __('Submit your first request') }}</a>
                                </div>
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
