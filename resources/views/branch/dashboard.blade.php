@extends('layouts.app')

@section('title', __('Branch Dashboard'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ $branch->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Live overview of jobs, technicians, and appointments.') }}</p>
        </div>
        <a href="{{ route('branch.requests.index') }}" class="secondary-button text-xs">{{ __('View Requests') }}</a>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
        <div class="stat-card">
            <span class="stat-icon bg-teal-50 text-teal-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Technicians') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-slate-900">{{ $technicians_count }}</span>
            </span>
        </div>
        <div class="stat-card">
            <span class="stat-icon bg-amber-50 text-amber-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Pending Requests') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-amber-600">{{ $pending_requests }}</span>
            </span>
        </div>
        <div class="stat-card">
            <span class="stat-icon bg-teal-50 text-teal-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Active Jobs') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-teal-600">{{ $active_jobs }}</span>
            </span>
        </div>
        <div class="stat-card">
            <span class="stat-icon bg-sky-50 text-sky-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Unassigned Jobs') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-slate-900">{{ $unassigned_approved }}</span>
            </span>
        </div>
        <div class="stat-card">
            <span class="stat-icon bg-emerald-50 text-emerald-600" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.75 9.25a.75.75 0 011.5 0v2.5h2.5a.75.75 0 010 1.5h-3.25a.75.75 0 01-.75-.75v-3.25z" clip-rule="evenodd" /></svg>
            </span>
            <span>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('30-Day Revenue') }}</span>
                <span class="mt-0.5 block font-display text-3xl font-extrabold text-emerald-600">{{ number_format($revenue_30d, 2) }}</span>
                <span class="mt-0.5 block text-xs font-semibold text-slate-500">{{ __('SAR collected') }}</span>
            </span>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Upcoming Appointments') }}</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($todays_appointments as $appointment)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <span class="font-medium text-slate-700">#{{ $appointment->maintenance_request_id }} · {{ $appointment->technician->user->name ?? '' }}</span>
                        <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No upcoming appointments.') }}</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Recently Created Requests') }}</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($recent_requests as $job)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <a href="{{ route('branch.requests.show', $job) }}" class="font-bold text-teal-700 hover:underline">#{{ $job->id }} · {{ $job->service->display_name ?? '' }}</a>
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
