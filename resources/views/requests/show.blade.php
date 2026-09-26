@extends('layouts.app')

@section('title', __('Request #:id', ['id' => $maintenanceRequest->id]))

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('requests.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to requests') }}</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Request #:id', ['id' => $maintenanceRequest->id]) }}</h2>
            <x-status-badge :status="$maintenanceRequest->status" />
        </div>
        @if($maintenanceRequest->invoice)
            <a href="{{ route('invoices.show', $maintenanceRequest->invoice) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-teal-700 hover:underline">
                {{ __('View invoice :number (:status) →', ['number' => $maintenanceRequest->invoice->number, 'status' => $maintenanceRequest->invoice->status->label()]) }}
            </a>
        @endif
        @if($maintenanceRequest->cancellation)
            <p class="mt-2 rounded-xl bg-slate-100 p-3 text-xs font-semibold text-slate-600">
                {{ __('Cancelled: :reason', ['reason' => $maintenanceRequest->cancellation->reason]) }}
                @if($maintenanceRequest->cancellation->fee > 0)
                    · {{ __('Fee: :amount SAR', ['amount' => number_format($maintenanceRequest->cancellation->fee, 2)]) }}
                @endif
            </p>
        @endif
    </div>

    @if($maintenanceRequest->status === \App\Enums\RequestStatus::TechnicianOnWay && $maintenanceRequest->technician)
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-teal-200 bg-teal-50 p-4 shadow-sm">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
            </span>
            <div>
                <p class="text-sm font-extrabold text-slate-900">{{ $maintenanceRequest->technician->user->name }} {{ __('is on the way to you') }}</p>
                @if($maintenanceRequest->appointment && !$maintenanceRequest->appointment->isCancelled())
                    <p class="text-xs text-slate-600">{{ __('Visit today at :time', ['time' => \Carbon\Carbon::parse($maintenanceRequest->appointment->start_time)->format('h:i A')]) }}</p>
                @endif
            </div>
        </div>
    @elseif($maintenanceRequest->status === \App\Enums\RequestStatus::Scheduled && $maintenanceRequest->appointment && !$maintenanceRequest->appointment->isCancelled() && $maintenanceRequest->appointment->date->isToday())
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
            </span>
            <p class="text-sm font-extrabold text-slate-900">{{ __('Visit today at :time', ['time' => \Carbon\Carbon::parse($maintenanceRequest->appointment->start_time)->format('h:i A')]) }}</p>
        </div>
    @elseif($maintenanceRequest->status === \App\Enums\RequestStatus::InProgress)
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-teal-200 bg-teal-50 p-4 shadow-sm">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>
            <p class="text-sm font-extrabold text-slate-900">{{ __('Work is in progress on your request.') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Request Details') }}</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Service') }}</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $maintenanceRequest->service->display_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Address') }}</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $maintenanceRequest->address->title ?? '—' }} — {{ $maintenanceRequest->address->street ?? '' }}, {{ $maintenanceRequest->address->city ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Preferred Appointment') }}</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ $maintenanceRequest->preferred_date->format('d M Y') }} at {{ \Carbon\Carbon::parse($maintenanceRequest->preferred_time)->format('h:i A') }}</dd>
                </div>
                @if($maintenanceRequest->appointment && !$maintenanceRequest->appointment->isCancelled())
                    <div class="rounded-xl bg-teal-50 p-3">
                        <dt class="text-xs font-bold uppercase tracking-wider text-teal-600">{{ __('Scheduled Visit (:status)', ['status' => $maintenanceRequest->appointment->status->label()]) }}</dt>
                        <dd class="mt-0.5 font-semibold text-slate-900">
                            {{ $maintenanceRequest->appointment->date->format('d M Y') }},
                            {{ \Carbon\Carbon::parse($maintenanceRequest->appointment->start_time)->format('h:i A') }} –
                            {{ \Carbon\Carbon::parse($maintenanceRequest->appointment->end_time)->format('h:i A') }}
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Description') }}</dt>
                    <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->description }}</dd>
                </div>
                @if($maintenanceRequest->rejection_reason)
                    <div class="rounded-xl bg-rose-50 p-3">
                        <dt class="text-xs font-bold uppercase tracking-wider text-rose-500">{{ __('Rejection Reason') }}</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->rejection_reason }}</dd>
                    </div>
                @endif
                @if($maintenanceRequest->admin_note)
                    <div class="rounded-xl bg-amber-50 p-3">
                        <dt class="text-xs font-bold uppercase tracking-wider text-amber-600">{{ __('Note From Our Team') }}</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $maintenanceRequest->admin_note }}</dd>
                    </div>
                @endif
            </dl>

            @php $pendingExtras = $maintenanceRequest->workOrder?->additionalWorkItems->where('status', \App\Enums\AdditionalWorkStatus::PendingApproval) ?? collect(); @endphp
            @if($pendingExtras->isNotEmpty())
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <h3 class="text-sm font-extrabold text-amber-900">{{ __('Approval Needed: Additional Work') }}</h3>
                    @foreach ($pendingExtras as $extra)
                        <div class="mt-3 rounded-xl bg-white/80 p-3 text-sm">
                            <p class="font-bold text-slate-900">{{ $extra->description }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ __('Additional cost: :amount SAR', ['amount' => number_format($extra->cost, 2)]) }} · {{ __('requested :date', ['date' => $extra->created_at->format('d M Y, h:i A')]) }}</p>
                            <div class="mt-2 flex gap-2">
                                <form method="POST" action="{{ route('additional-work.approve', $extra) }}" class="flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="primary-button w-full text-xs">{{ __('Approve (:amount SAR)', ['amount' => number_format($extra->cost, 2)]) }}</button>
                                </form>
                                <form method="POST" action="{{ route('additional-work.reject', $extra) }}" class="flex-1" data-confirm="{{ __('Reject this additional work? It will not be billed.') }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="secondary-button w-full text-xs">{{ __('Reject') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(! empty($maintenanceRequest->photos))
                <h3 class="mt-6 text-sm font-extrabold text-slate-900">{{ __('Photos') }}</h3>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    @foreach ($maintenanceRequest->photos as $photo)
                        <a href="{{ route('files.show', $photo) }}" target="_blank">
                            <img src="{{ route('files.show', $photo) }}" alt="{{ __('Request photo') }}" class="h-20 w-full rounded-lg border border-slate-200 object-cover">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if($maintenanceRequest->status === \App\Enums\RequestStatus::Approved && $maintenanceRequest->technician_id === null)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-amber-900">{{ __('No technician was available for your slot. Pick another date or time.') }}</h3>
                <form method="POST" action="{{ route('requests.reschedule', $maintenanceRequest) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PATCH')
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="preferred_date" class="form-label text-xs">{{ __('Preferred Date') }}</label>
                            <input type="date" id="preferred_date" name="preferred_date" value="{{ old('preferred_date', $maintenanceRequest->preferred_date->format('Y-m-d')) }}" required
                                class="form-input text-xs @error('preferred_date') border-rose-300 @enderror">
                            @error('preferred_date')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="preferred_time" class="form-label text-xs">{{ __('Preferred Time') }}</label>
                            <input type="time" id="preferred_time" name="preferred_time" value="{{ old('preferred_time', $maintenanceRequest->preferred_time) }}" required dir="ltr"
                                class="form-input text-xs @error('preferred_time') border-rose-300 @enderror">
                            @error('preferred_time')
                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <button type="submit" class="primary-button w-full text-xs">{{ __('Update Appointment') }}</button>
                </form>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Your Review') }}</h3>
            @if($maintenanceRequest->review)
                <p class="mt-2 text-lg font-extrabold text-amber-500">{{ str_repeat('★', $maintenanceRequest->review->rating) }}{{ str_repeat('☆', 5 - $maintenanceRequest->review->rating) }}</p>
                @if($maintenanceRequest->review->comment)
                    <p class="mt-1 text-sm text-slate-700">{{ $maintenanceRequest->review->comment }}</p>
                @endif
            @elseif(in_array($maintenanceRequest->status, [\App\Enums\RequestStatus::Completed, \App\Enums\RequestStatus::Invoiced, \App\Enums\RequestStatus::Paid, \App\Enums\RequestStatus::Closed], true))
                <form method="POST" action="{{ route('requests.reviews.store', $maintenanceRequest) }}" class="mt-3 space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <select name="rating" required class="form-input text-xs" aria-label="{{ __('Rating') }}">
                            <option value="">{{ __('Rating…') }}</option>
                            @for($stars = 5; $stars >= 1; $stars--)
                                <option value="{{ $stars }}">{{ $stars }} {{ $stars > 1 ? __('stars') : __('star') }}</option>
                            @endfor
                        </select>
                        <input type="text" name="comment" maxlength="2000" placeholder="{{ __('Comment (optional)') }}" class="form-input text-xs">
                    </div>
                    <button type="submit" class="primary-button w-full text-xs">{{ __('Submit Review') }}</button>
                </form>
            @else
                <p class="mt-2 text-xs text-slate-500">{{ __('You can review this job once it is completed.') }}</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Status History') }}</h3>
            <ol class="timeline mt-4">
                @forelse ($maintenanceRequest->statusHistories as $history)
                    <li class="timeline-item">
                        <span class="timeline-dot{{ $loop->last ? '' : ' timeline-dot-muted' }}" aria-hidden="true"></span>
                        <div class="text-sm">
                            <p class="font-bold text-slate-900">
                                @if($history->from_status)
                                    {{ \App\Enums\RequestStatus::from($history->from_status)->label() }} →
                                @endif
                                {{ \App\Enums\RequestStatus::from($history->status)->label() }}
                                @if($loop->last)
                                    <span class="ms-1 rounded-full bg-teal-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-teal-700">{{ __('Current') }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-slate-500">{{ $history->created_at->format('d M Y, h:i A') }}</p>
                            @if($history->reason)
                                <p class="mt-0.5 text-xs text-slate-600">{{ $history->reason }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No history yet.') }}</p>
                @endforelse
            </ol>

            @if(!$maintenanceRequest->cancellation && in_array($maintenanceRequest->status, [\App\Enums\RequestStatus::PendingReview, \App\Enums\RequestStatus::InfoRequested, \App\Enums\RequestStatus::Approved, \App\Enums\RequestStatus::TechnicianAssigned, \App\Enums\RequestStatus::Scheduled], true))
                <form method="POST" action="{{ route('requests.cancel', $maintenanceRequest) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4" data-confirm="{{ __('Cancel this request? A fee may apply per the cancellation policy.') }}">
                    @csrf
                    <div>
                        <label for="reason" class="form-label text-xs">{{ __('Cancellation Reason') }} <span class="text-rose-500">*</span></label>
                        <textarea id="reason" name="reason" rows="2" required class="form-input text-xs"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition">{{ __('Cancel Request') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
