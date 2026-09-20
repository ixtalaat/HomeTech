@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('requests.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← Back to requests</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">Request #{{ $maintenanceRequest->id }}</h2>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                {{ $maintenanceRequest->status->label() }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Request Details</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Service</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $maintenanceRequest->service->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Address</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $maintenanceRequest->address->title ?? '—' }} — {{ $maintenanceRequest->address->street ?? '' }}, {{ $maintenanceRequest->address->city ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Preferred Appointment</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $maintenanceRequest->preferred_date->format('d M Y') }} at {{ \Carbon\Carbon::parse($maintenanceRequest->preferred_time)->format('h:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Description</dt>
                    <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->description }}</dd>
                </div>
                @if($maintenanceRequest->rejection_reason)
                    <div class="rounded-xl bg-rose-50 p-3">
                        <dt class="text-xs font-bold uppercase tracking-wider text-rose-500">Rejection Reason</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->rejection_reason }}</dd>
                    </div>
                @endif
                @if($maintenanceRequest->admin_note)
                    <div class="rounded-xl bg-amber-50 p-3">
                        <dt class="text-xs font-bold uppercase tracking-wider text-amber-600">Note From Our Team</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->admin_note }}</dd>
                    </div>
                @endif
            </dl>

            @if(! empty($maintenanceRequest->photos))
                <h3 class="mt-6 text-sm font-extrabold text-slate-900">Photos</h3>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    @foreach ($maintenanceRequest->photos as $photo)
                        <a href="{{ Storage::url($photo) }}" target="_blank">
                            <img src="{{ Storage::url($photo) }}" alt="Request photo" class="h-20 w-full rounded-lg border border-slate-200 object-cover">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Status History</h3>
            <ol class="mt-4 space-y-4">
                @forelse ($maintenanceRequest->statusHistories as $history)
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-teal-500"></span>
                        <div class="text-sm">
                            <p class="font-bold text-slate-900">
                                @if($history->from_status)
                                    {{ \App\Enums\RequestStatus::from($history->from_status)->label() }} →
                                @endif
                                {{ \App\Enums\RequestStatus::from($history->status)->label() }}
                            </p>
                            <p class="text-xs text-slate-400">{{ $history->created_at->format('d M Y, h:i A') }}</p>
                            @if($history->reason)
                                <p class="mt-0.5 text-xs text-slate-600">{{ $history->reason }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <p class="text-sm text-slate-500">No history yet.</p>
                @endforelse
            </ol>
        </div>
    </div>
</div>
@endsection
