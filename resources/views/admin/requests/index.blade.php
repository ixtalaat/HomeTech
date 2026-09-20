@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <h2 class="font-display text-2xl font-extrabold text-slate-900">Maintenance Requests</h2>
        <p class="mt-1 text-sm text-slate-500">Review incoming requests, approve or reject them, and adjust appointments.</p>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.requests.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3 items-end">
            <div>
                <label for="search" class="form-label text-xs">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Description, customer..." class="form-input text-xs py-2">
            </div>
            <div>
                <label for="status" class="form-label text-xs">Status</label>
                <select id="status" name="status" class="form-input text-xs py-2">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="primary-button text-xs py-2 px-4 w-full">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.requests.index') }}" class="secondary-button text-xs py-2 px-3">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Request</th>
                        <th scope="col" class="px-6 py-4">Customer</th>
                        <th scope="col" class="px-6 py-4">Appointment</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($requests as $maintenanceRequest)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">#{{ $maintenanceRequest->id }} · {{ $maintenanceRequest->service->name ?? '—' }}</p>
                                <p class="mt-0.5 max-w-xs truncate text-xs text-slate-400">{{ $maintenanceRequest->description }}</p>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium">{{ $maintenanceRequest->user->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs font-medium">
                                {{ $maintenanceRequest->preferred_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    {{ $maintenanceRequest->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.requests.show', $maintenanceRequest) }}"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                No requests found matching your criteria.
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
