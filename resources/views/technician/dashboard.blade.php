@extends('layouts.app')

@php($heading = 'Good morning, '.auth()->user()->name)

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-sm font-semibold text-slate-500">Here’s your workday at a glance.</p><h2 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-slate-950">Ready for today’s jobs.</h2></div><a href="{{ route('technician.jobs.index') }}" class="primary-button">Open My Jobs <span aria-hidden="true">→</span></a></div>
        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="stat-card"><span class="stat-icon bg-teal-100 text-teal-700">✓</span><div><p class="text-sm font-semibold text-slate-500">Active jobs</p><p class="mt-1 font-display text-3xl font-extrabold">{{ $active_jobs }}</p></div></div>
            <div class="stat-card"><span class="stat-icon bg-amber-100 text-amber-700">◷</span><div><p class="text-sm font-semibold text-slate-500">Upcoming visits</p><p class="mt-1 font-display text-3xl font-extrabold">{{ $upcoming_visits }}</p></div></div>
            <div class="stat-card"><span class="stat-icon bg-sky-100 text-sky-700">▣</span><div><p class="text-sm font-semibold text-slate-500">Completed jobs</p><p class="mt-1 font-display text-3xl font-extrabold">{{ $completed_jobs }}</p></div></div>
        </div>
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-label="Upcoming visits">
                <p class="text-sm font-bold uppercase tracking-[0.14em] text-teal-600">Up next</p>
                <ul class="mt-4 space-y-2">
                    @forelse ($upcoming as $appointment)
                        <li class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                            <span>
                                <span class="block text-sm font-bold text-slate-900">#{{ $appointment->maintenance_request_id }} · {{ $appointment->request->service->name ?? '' }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $appointment->request->address->city ?? '' }} · {{ $appointment->date->format('d M Y') }}, {{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}</span>
                            </span>
                        </li>
                    @empty
                        <p class="text-sm text-slate-500">No upcoming visits scheduled.</p>
                    @endforelse
                </ul>
            </section>
            <section class="rounded-3xl border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-label="Jobs awaiting start">
                <p class="text-sm font-bold uppercase tracking-[0.14em] text-teal-600">Awaiting start</p>
                <ul class="mt-4 space-y-2">
                    @forelse ($assigned as $job)
                        <li>
                            <form method="POST" action="{{ route('technician.jobs.start', $job) }}" class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                                @csrf
                                <span class="text-sm font-bold text-slate-900">#{{ $job->id }} · {{ $job->service->name ?? '' }}</span>
                                <button type="submit" class="secondary-button px-3 py-1.5 text-xs">Start visit</button>
                            </form>
                        </li>
                    @empty
                        <p class="text-sm text-slate-500">Nothing waiting. Nice work!</p>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
@endsection
