@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('admin.requests.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← Back to requests</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">Request #{{ $maintenanceRequest->id }}</h2>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                {{ $maintenanceRequest->status->label() }}
            </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $maintenanceRequest->user->name ?? '—' }} · Submitted {{ $maintenanceRequest->created_at->format('d M Y, h:i A') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-6">
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
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Description</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->description }}</dd>
                    </div>
                    @if(! empty($maintenanceRequest->photos))
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Photos</dt>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                @foreach ($maintenanceRequest->photos as $photo)
                                    <a href="{{ Storage::url($photo) }}" target="_blank">
                                        <img src="{{ Storage::url($photo) }}" alt="Request photo" class="h-20 w-full rounded-lg border border-slate-200 object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Appointment</h3>
                <p class="mt-2 text-sm font-semibold text-slate-900">
                    {{ $maintenanceRequest->preferred_date->format('d M Y') }} at {{ \Carbon\Carbon::parse($maintenanceRequest->preferred_time)->format('h:i A') }}
                </p>
                @if($maintenanceRequest->isReviewable())
                    <form method="POST" action="{{ route('admin.requests.appointment', $maintenanceRequest) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="preferred_date" class="form-label text-xs">Date</label>
                                <input type="date" id="preferred_date" name="preferred_date" value="{{ $maintenanceRequest->preferred_date->format('Y-m-d') }}" required min="{{ now()->addDay()->format('Y-m-d') }}" class="form-input text-xs py-2">
                            </div>
                            <div>
                                <label for="preferred_time" class="form-label text-xs">Time</label>
                                <input type="time" id="preferred_time" name="preferred_time" value="{{ \Carbon\Carbon::parse($maintenanceRequest->preferred_time)->format('H:i') }}" required class="form-input text-xs py-2">
                            </div>
                        </div>
                        <button type="submit" class="secondary-button text-xs">Update Appointment</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            @if($maintenanceRequest->isReviewable())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">Review Actions</h3>

                    <form method="POST" action="{{ route('admin.requests.approve', $maintenanceRequest) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="approve_note" class="form-label text-xs">Approval Note (optional)</label>
                            <textarea id="approve_note" name="admin_note" rows="2" class="form-input text-xs"></textarea>
                        </div>
                        <button type="submit" class="primary-button w-full text-xs">Approve Request</button>
                    </form>

                    <form method="POST" action="{{ route('admin.requests.request-info', $maintenanceRequest) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="info_note" class="form-label text-xs">What do you need from the customer? <span class="text-rose-500">*</span></label>
                            <textarea id="info_note" name="admin_note" rows="2" required class="form-input text-xs"></textarea>
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">Request More Info</button>
                    </form>

                    <form method="POST" action="{{ route('admin.requests.reject', $maintenanceRequest) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4" onsubmit="return confirm('Reject this request?');">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="rejection_reason" class="form-label text-xs">Rejection Reason <span class="text-rose-500">*</span></label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="2" required class="form-input text-xs"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition">Reject Request</button>
                    </form>
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm text-sm text-slate-500">
                    This request has been reviewed ({{ $maintenanceRequest->status->label() }}) and can no longer be changed here.
                    @if($maintenanceRequest->rejection_reason)
                        <p class="mt-2 font-semibold text-slate-700">Reason: {{ $maintenanceRequest->rejection_reason }}</p>
                    @endif
                </div>
            @endif

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
</div>
@endsection
