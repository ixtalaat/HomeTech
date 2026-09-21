@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('My Maintenance Requests') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Track your requests from submission through completion.') }}</p>
        </div>
        <a href="{{ route('requests.create') }}" class="primary-button">
            <span>{{ __('New Request') }}</span>
        </a>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('requests.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="sm:w-64">
                <label for="status" class="form-label text-xs">{{ __('Filter by Status') }}</label>
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
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
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
                                <p class="font-bold text-slate-900">{{ $maintenanceRequest->service->name ?? '—' }}</p>
                                <p class="mt-0.5 max-w-xs truncate text-xs text-slate-400">{{ $maintenanceRequest->description }}</p>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium">
                                {{ $maintenanceRequest->preferred_date->format('d M Y') }} at {{ \Carbon\Carbon::parse($maintenanceRequest->preferred_time)->format('h:i A') }}
                            </td>
                            <td class="px-6 py-4">
                                <x-status-badge :status="$maintenanceRequest->status" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('requests.show', $maintenanceRequest) }}"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">
                                    {{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                {{ __('No requests yet.') }} <a href="{{ route('requests.create') }}" class="font-bold text-teal-700 hover:underline">{{ __('Submit your first request') }}</a>.
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
