@extends('layouts.app')

@php($heading = 'Good morning, '.auth()->user()->name)

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-sm font-semibold text-slate-500">Here’s what’s happening with your home.</p><h2 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-slate-950">Stay ahead of every repair.</h2></div><a href="{{ route('requests.create') }}" class="primary-button">Request a service <span aria-hidden="true">＋</span></a></div>
        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <a href="{{ route('requests.index') }}" class="stat-card transition hover:border-teal-200 hover:shadow-md" aria-label="View active services">
                <span class="stat-icon bg-teal-100 text-teal-700">✓</span>
                <div><p class="text-sm font-semibold text-slate-500">Active services</p><p class="mt-1 font-display text-3xl font-extrabold">{{ $active_services }}</p></div>
            </a>
            <a href="{{ route('requests.index') }}" class="stat-card transition hover:border-amber-200 hover:shadow-md" aria-label="View upcoming visits">
                <span class="stat-icon bg-amber-100 text-amber-700">◷</span>
                <div><p class="text-sm font-semibold text-slate-500">Upcoming visits</p><p class="mt-1 font-display text-3xl font-extrabold">{{ $upcoming_visits }}</p></div>
            </a>
            <a href="{{ route('requests.index') }}" class="stat-card transition hover:border-sky-200 hover:shadow-md" aria-label="View completed jobs">
                <span class="stat-icon bg-sky-100 text-sky-700">▣</span>
                <div><p class="text-sm font-semibold text-slate-500">Completed jobs</p><p class="mt-1 font-display text-3xl font-extrabold">{{ $completed_jobs }}</p></div>
            </a>
        </div>
        @if($outstanding_balance > 0)
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900" role="status">
                Outstanding balance: {{ number_format($outstanding_balance, 2) }} EGP —
                <a href="{{ route('invoices.index') }}" class="font-bold underline">view invoices →</a>
            </div>
        @endif
        <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
            <section class="rounded-3xl border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-label="Your activity">
                <div class="flex items-start justify-between"><div><p class="text-sm font-bold uppercase tracking-[0.14em] text-teal-600">Your activity</p><h3 class="mt-2 font-display text-xl font-extrabold">{{ $recent_requests->isEmpty() ? 'No services yet' : 'Recent requests' }}</h3></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Overview</span></div>
                @if($recent_requests->isEmpty())
                    <div class="mt-8 rounded-2xl border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center"><div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-2xl text-teal-700">⌂</div><p class="mt-4 font-bold text-slate-900">Your home deserves a great start</p><p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Book your first service and our team will take care of the rest.</p><a href="{{ route('services.index') }}" class="mt-5 inline-flex font-bold text-teal-700 hover:text-teal-800">Explore services <span class="ml-2">→</span></a></div>
                @else
                    <ul class="mt-6 space-y-2">
                        @foreach ($recent_requests as $recent)
                            <li>
                                <a href="{{ route('requests.show', $recent) }}" class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3 transition hover:bg-teal-50">
                                    <span>
                                        <span class="block text-sm font-bold text-slate-900">#{{ $recent->id }} · {{ $recent->service->name ?? '' }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">
                                            @if($recent->appointment && !$recent->appointment->isCancelled())
                                                Visit {{ $recent->appointment->date->format('d M Y') }}, {{ \Carbon\Carbon::parse($recent->appointment->start_time)->format('h:i A') }}
                                            @else
                                                Preferred {{ $recent->preferred_date->format('d M Y') }}
                                            @endif
                                        </span>
                                    </span>
                                    <x-status-badge :status="$recent->status" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
            <section class="rounded-3xl bg-slate-900 p-6 text-white shadow-xl shadow-slate-900/10 sm:p-8"><p class="text-sm font-bold uppercase tracking-[0.14em] text-teal-300">Quick tip</p><h3 class="mt-4 font-display text-2xl font-extrabold leading-tight">Small fixes today prevent big repairs tomorrow.</h3><p class="mt-4 text-sm leading-6 text-slate-300">Keep a regular maintenance routine and your home will thank you.</p><div class="mt-8 h-1 w-16 rounded-full bg-teal-400"></div></section>
        </div>
    </div>
@endsection
