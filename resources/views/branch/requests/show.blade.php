@extends('layouts.app')

@section('title', __('Request #:id', ['id' => $maintenanceRequest->id]))

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('branch.requests.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to requests') }}</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Request #:id', ['id' => $maintenanceRequest->id]) }}</h2>
            <x-status-badge :status="$maintenanceRequest->status" />
            <span class="inline-flex items-center rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ $branch->name }}</span>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $maintenanceRequest->user->name ?? '—' }} · {{ __('Submitted :date', ['date' => $maintenanceRequest->created_at->format('d M Y, h:i A')]) }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Request Details') }}</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Service') }}</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $maintenanceRequest->service->display_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Service Address') }}</dt>
                    <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->address->title ?? '' }} — {{ $maintenanceRequest->address->street ?? '' }}, {{ $maintenanceRequest->address->city ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Preferred Appointment') }}</dt>
                    <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->preferred_date->format('d M Y') }} · {{ $maintenanceRequest->preferred_time }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Problem Description') }}</dt>
                    <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->description }}</dd>
                </div>
                @if($maintenanceRequest->technician)
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Technician') }}</dt>
                        <dd class="mt-0.5 font-semibold text-teal-700">{{ $maintenanceRequest->technician->user->name ?? '—' }}</dd>
                    </div>
                @endif
                @if($maintenanceRequest->appointment && ! $maintenanceRequest->appointment->isCancelled())
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Appointment') }}</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->appointment->date->format('d M Y') }} · {{ $maintenanceRequest->appointment->start_time }} – {{ $maintenanceRequest->appointment->end_time }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="space-y-6">
            @if($maintenanceRequest->isReviewable())
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Review Actions') }}</h3>
                    <form method="POST" action="{{ route('branch.requests.approve', $maintenanceRequest) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="admin_note" value="{{ old('admin_note') }}" placeholder="{{ __('Approval Note (optional)') }}" aria-label="{{ __('Approval Note (optional)') }}" class="form-input text-xs">
                        <button type="submit" class="primary-button w-full text-xs">{{ __('Approve Request') }}</button>
                    </form>
                    <form method="POST" action="{{ route('branch.requests.reject', $maintenanceRequest) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="rejection_reason" value="{{ old('rejection_reason') }}" required placeholder="{{ __('Rejection Reason') }}" aria-label="{{ __('Rejection Reason') }}" class="form-input text-xs">
                        <button type="submit" class="rounded-xl border border-rose-200 px-3 py-2 w-full text-xs font-bold text-rose-600 hover:bg-rose-50 transition">{{ __('Reject Request') }}</button>
                    </form>
                    <form method="POST" action="{{ route('branch.requests.request-info', $maintenanceRequest) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="admin_note" required placeholder="{{ __('What do you need from the customer?') }}" aria-label="{{ __('What do you need from the customer?') }}" class="form-input text-xs">
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Request More Info') }}</button>
                    </form>
                </div>
            @endif

            @if($maintenanceRequest->status === \App\Enums\RequestStatus::Approved)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Technician Assignment') }}</h3>
                    <form method="POST" action="{{ route('branch.requests.assign', $maintenanceRequest) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <select name="technician_id" required class="form-input text-xs" aria-label="{{ __('Select eligible technician') }}">
                            <option value="">{{ __('Select eligible technician') }}</option>
                            @foreach ($eligibleTechnicians as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->user->name }} ({{ $technician->assigned_requests_count ?? 0 }} {{ __('Assigned') }} · {{ $technician->day_load ?? 0 }}/{{ \App\Services\TechnicianAssignmentService::MAX_DAILY_REQUESTS }}){{ ! empty($technician->slot_note) ? ' — '.$technician->slot_note : '' }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Assign & Book') }}</button>
                    </form>
                </div>
            @elseif($maintenanceRequest->technician)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Technician Assignment') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Assigned to :name — awaiting a slot.', ['name' => $maintenanceRequest->technician->user->name ?? '—']) }}</p>
                    <form method="POST" action="{{ route('branch.requests.unassign', $maintenanceRequest) }}" class="mt-3">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Unassign') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
