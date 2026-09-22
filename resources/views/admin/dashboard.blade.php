@extends('layouts.app')

@section('title', __('Operations Dashboard'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Operations Dashboard') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Live overview of jobs, invoices, and inventory.') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.revenue') }}" class="secondary-button text-xs">{{ __('Revenue') }}</a>
            <a href="{{ route('admin.reports.jobs') }}" class="secondary-button text-xs">{{ __('Jobs') }}</a>
            <a href="{{ route('admin.reports.technicians') }}" class="secondary-button text-xs">{{ __('Technicians') }}</a>
            <a href="{{ route('admin.reports.inventory') }}" class="secondary-button text-xs">{{ __('Inventory') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <a href="{{ route('admin.requests.index') }}" class="stat-card transition hover:border-teal-200 hover:shadow-md" aria-label="{{ __('View today\'s jobs') }}">
            <span class="stat-icon bg-teal-50 text-teal-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __("Today's Jobs") }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-slate-900">{{ $todays_jobs }}</span>
            </span>
        </a>
        <a href="{{ route('admin.requests.index', ['status' => 'pending_review']) }}" class="stat-card transition hover:border-amber-200 hover:shadow-md" aria-label="{{ __('View pending requests') }}">
            <span class="stat-icon bg-amber-50 text-amber-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Pending Requests') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-amber-600">{{ $pending_requests }}</span>
            </span>
        </a>
        <a href="{{ route('admin.requests.index') }}" class="stat-card transition hover:border-teal-200 hover:shadow-md" aria-label="{{ __('View active jobs') }}">
            <span class="stat-icon bg-teal-50 text-teal-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Active Jobs') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-teal-600">{{ $active_jobs }}</span>
            </span>
        </a>
        <a href="{{ route('admin.reports.jobs') }}" class="stat-card transition hover:border-emerald-200 hover:shadow-md" aria-label="{{ __('View job reports') }}">
            <span class="stat-icon bg-emerald-50 text-emerald-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Completed Today') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-emerald-600">{{ $completed_today }}</span>
            </span>
        </a>
        <a href="{{ route('admin.invoices.index') }}" class="stat-card transition hover:border-rose-200 hover:shadow-md" aria-label="{{ __('View unpaid invoices') }}">
            <span class="stat-icon bg-rose-50 text-rose-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h9l4 4v14H6V3Zm8 0v5h5"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Unpaid Invoices') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-rose-600">{{ $unpaid_invoices }}</span>
                <span class="mt-0.5 block text-xs font-semibold text-slate-500">{{ number_format($outstanding_total, 2) }} {{ __('EGP outstanding') }}</span>
            </span>
        </a>
        <a href="{{ route('admin.inventory.index', ['filter' => 'low-stock']) }}" class="stat-card transition hover:border-amber-200 hover:shadow-md" aria-label="{{ __('View low-stock items') }}">
            <span class="stat-icon bg-amber-50 text-amber-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7H4a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM7 10h2v2H7v-2Zm0 4h2v2H7v-2Zm4-4h6v2h-6v-2Zm0 4h6v2h-6v-2Z"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Low-Stock Items') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold {{ $low_stock_count > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $low_stock_count }}</span>
                @if($low_stock_items->isNotEmpty())
                    <span class="mt-0.5 block text-xs text-slate-500">{{ $low_stock_items->map(fn ($lowItem) => $lowItem->display_name)->join(', ') }}</span>
                @endif
            </span>
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Upcoming Appointments') }}</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($upcoming_appointments as $appointment)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <span class="font-medium text-slate-700">#{{ $appointment->maintenance_request_id }} · {{ $appointment->request->service->display_name ?? '' }}</span>
                        <span class="text-xs text-slate-500">{{ $appointment->date->format('d M') }}, {{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No upcoming appointments.') }}</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Unassigned Jobs') }}</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($unassigned_jobs as $job)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <a href="{{ route('admin.requests.show', $job) }}" class="font-bold text-teal-700 hover:underline">#{{ $job->id }} · {{ $job->service->display_name ?? '' }}</a>
                        <span class="text-xs text-slate-500">{{ $job->user->name ?? '' }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No unassigned jobs. All clear!') }}</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Waiting Customer Approval') }}</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($waiting_approval as $job)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <a href="{{ route('admin.requests.show', $job) }}" class="font-bold text-teal-700 hover:underline">#{{ $job->id }} · {{ $job->service->display_name ?? '' }}</a>
                        <span class="text-xs text-slate-500">{{ $job->user->name ?? '' }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('Nothing awaiting approval.') }}</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Recently Created Requests') }}</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($recent_requests as $job)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <a href="{{ route('admin.requests.show', $job) }}" class="font-bold text-teal-700 hover:underline">#{{ $job->id }} · {{ $job->service->display_name ?? '' }}</a>
                        <span class="text-xs text-slate-500">{{ $job->status->label() }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No requests yet.') }}</p>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
