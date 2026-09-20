@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('admin.requests.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← Back to requests</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">Work Order #{{ $workOrder->id }}</h2>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                {{ $workOrder->status->label() }}
            </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">
            {{ $workOrder->technician->user->name ?? '—' }} ·
            Started {{ $workOrder->started_at?->format('d M Y, h:i A') ?? '—' }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Diagnosis & Notes</h3>
                <dl class="mt-3 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Diagnosis</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $workOrder->diagnosis ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Work Notes</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $workOrder->work_notes ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Labor ({{ number_format($workOrder->laborTotal(), 2) }} EGP)</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($workOrder->laborItems as $item)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <span class="font-medium text-slate-700">{{ $item->description }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($item->cost, 2) }} EGP</span>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">No labor recorded.</p>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Authorized Correction</h3>
                <p class="mt-1 text-xs text-slate-500">Corrections are applied immediately and written to the audit log with your name and reason.</p>
                <form method="POST" action="{{ route('admin.work-orders.correct', $workOrder) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="diagnosis" class="form-label text-xs">Diagnosis</label>
                        <textarea id="diagnosis" name="diagnosis" rows="2" class="form-input text-xs">{{ old('diagnosis', $workOrder->diagnosis) }}</textarea>
                    </div>
                    <div>
                        <label for="work_notes" class="form-label text-xs">Work Notes</label>
                        <textarea id="work_notes" name="work_notes" rows="2" class="form-input text-xs">{{ old('work_notes', $workOrder->work_notes) }}</textarea>
                    </div>
                    <div>
                        <label for="reason" class="form-label text-xs">Reason <span class="text-rose-500">*</span></label>
                        <textarea id="reason" name="reason" rows="2" required class="form-input text-xs"></textarea>
                    </div>
                    <button type="submit" class="primary-button w-full text-xs">Apply Correction</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Audit Trail</h3>
                <ol class="mt-3 space-y-3">
                    @forelse ($corrections as $log)
                        <li class="text-sm">
                            <p class="font-bold text-slate-900">{{ $log->action }} <span class="font-normal text-slate-400">by {{ $log->actor->name ?? 'system' }}</span></p>
                            <p class="text-xs text-slate-400">{{ $log->created_at->format('d M Y, h:i A') }}</p>
                            @if($log->reason)
                                <p class="mt-0.5 text-xs text-slate-600">Reason: {{ $log->reason }}</p>
                            @endif
                        </li>
                    @empty
                        <p class="text-sm text-slate-500">No corrections recorded.</p>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
