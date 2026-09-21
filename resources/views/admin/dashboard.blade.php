@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">Operations Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Live overview of jobs, invoices, and inventory.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.revenue') }}" class="secondary-button text-xs">Revenue</a>
            <a href="{{ route('admin.reports.jobs') }}" class="secondary-button text-xs">Jobs</a>
            <a href="{{ route('admin.reports.technicians') }}" class="secondary-button text-xs">Technicians</a>
            <a href="{{ route('admin.reports.inventory') }}" class="secondary-button text-xs">Inventory</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Today's Jobs</p>
            <p class="mt-1 font-display text-3xl font-extrabold text-slate-900">{{ $todays_jobs }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pending Requests</p>
            <p class="mt-1 font-display text-3xl font-extrabold text-amber-600">{{ $pending_requests }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Jobs</p>
            <p class="mt-1 font-display text-3xl font-extrabold text-teal-600">{{ $active_jobs }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Completed Today</p>
            <p class="mt-1 font-display text-3xl font-extrabold text-emerald-600">{{ $completed_today }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Unpaid Invoices</p>
            <p class="mt-1 font-display text-3xl font-extrabold text-rose-600">{{ $unpaid_invoices }}</p>
            <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ number_format($outstanding_total, 2) }} EGP outstanding</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Low-Stock Items</p>
            <p class="mt-1 font-display text-3xl font-extrabold {{ $low_stock_count > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $low_stock_count }}</p>
            @if($low_stock_items->isNotEmpty())
                <p class="mt-0.5 text-xs text-slate-500">{{ $low_stock_items->pluck('name')->join(', ') }}</p>
            @endif
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Upcoming Appointments</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($upcoming_appointments as $appointment)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <span class="font-medium text-slate-700">#{{ $appointment->maintenance_request_id }} · {{ $appointment->request->service->name ?? '' }}</span>
                        <span class="text-xs text-slate-500">{{ $appointment->date->format('d M') }}, {{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">No upcoming appointments.</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Unassigned Jobs</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($unassigned_jobs as $job)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <a href="{{ route('admin.requests.show', $job) }}" class="font-bold text-teal-700 hover:underline">#{{ $job->id }} · {{ $job->service->name ?? '' }}</a>
                        <span class="text-xs text-slate-500">{{ $job->user->name ?? '' }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">No unassigned jobs. All clear!</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Waiting Customer Approval</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($waiting_approval as $job)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <a href="{{ route('admin.requests.show', $job) }}" class="font-bold text-teal-700 hover:underline">#{{ $job->id }} · {{ $job->service->name ?? '' }}</a>
                        <span class="text-xs text-slate-500">{{ $job->user->name ?? '' }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">Nothing awaiting approval.</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Recently Created Requests</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($recent_requests as $job)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <a href="{{ route('admin.requests.show', $job) }}" class="font-bold text-teal-700 hover:underline">#{{ $job->id }} · {{ $job->service->name ?? '' }}</a>
                        <span class="text-xs text-slate-500">{{ $job->status->label() }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">No requests yet.</p>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
