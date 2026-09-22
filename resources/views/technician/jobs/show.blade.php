@extends('layouts.app')

@section('title', __('Job #:id', ['id' => $workOrder->id]))

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('technician.jobs.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to jobs') }}</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Job #:id', ['id' => $workOrder->id]) }}</h2>
            <x-status-badge :status="$workOrder->status" />
        </div>
        @php
            $steps = [
                ['label' => __('Diagnosis'), 'done' => ! empty($workOrder->diagnosis)],
                ['label' => __('Notes'), 'done' => ! empty($workOrder->work_notes)],
                ['label' => __('Charges'), 'done' => $workOrder->laborItems->isNotEmpty() || $workOrder->materialUsages->isNotEmpty()],
                ['label' => __('Extras resolved'), 'done' => $workOrder->additionalWorkItems->where('status', \App\Enums\AdditionalWorkStatus::PendingApproval)->isEmpty()],
            ];
            $doneSteps = collect($steps)->where('done')->count();
        @endphp
        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" role="img" aria-label="{{ __('Job progress: :done of :total steps complete', ['done' => $doneSteps, 'total' => count($steps)]) }}">
            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-teal-500 transition-all" style="width: {{ (int) ($doneSteps / count($steps) * 100) }}%"></div>
            </div>
            <ol class="mt-3 flex flex-wrap gap-2">
                @foreach ($steps as $step)
                    <li class="badge {{ $step['done'] ? 'badge-success' : 'badge-neutral' }}">{{ $step['label'] }}</li>
                @endforeach
            </ol>
        </div>
        <p class="mt-1 text-sm text-slate-500">
            {{ $workOrder->request->service->display_name ?? '—' }} ·
            {{ $workOrder->request->address->title ?? '' }} — {{ $workOrder->request->address->street ?? '' }}, {{ $workOrder->request->address->city ?? '' }}
        </p>
        @if($workOrder->isCompleted())
            <p class="mt-2 rounded-xl bg-slate-100 p-3 text-xs font-semibold text-slate-600">
                {{ __('This job is completed and locked. Contact staff if a correction is needed.') }}
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Diagnosis') }}</h3>
                @if($workOrder->diagnosis)
                    <p class="mt-2 text-sm text-slate-700">{{ $workOrder->diagnosis }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-400">{{ __('No diagnosis recorded yet.') }}</p>
                @endif
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.diagnosis', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="diagnosis" rows="3" required placeholder="{{ __('e.g. The AC compressor capacitor is damaged.') }}"
                            class="form-input text-sm">{{ old('diagnosis', $workOrder->diagnosis) }}</textarea>
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Save Diagnosis') }}</button>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Work Notes') }}</h3>
                @if($workOrder->work_notes)
                    <p class="mt-2 text-sm text-slate-700">{{ $workOrder->work_notes }}</p>
                @endif
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.notes', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="work_notes" rows="3" required placeholder="{{ __('Work performed, problems found, recommendations…') }}"
                            class="form-input text-sm">{{ old('work_notes', $workOrder->work_notes) }}</textarea>
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Save Notes') }}</button>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Photos') }}</h3>
                @foreach (['before' => $workOrder->before_photos, 'after' => $workOrder->after_photos] as $slot => $photos)
                    <p class="mt-3 text-xs font-bold uppercase tracking-wider text-slate-400">{{ __($slot === 'before' ? 'Before' : 'After') }} ({{ count($photos ?? []) }})</p>
                    @if(! empty($photos))
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @foreach ($photos as $photo)
                                <a href="{{ route('files.show', $photo) }}" target="_blank">
                                    <img src="{{ route('files.show', $photo) }}" alt="{{ __(':slot photo', ['slot' => $slot]) }}" class="h-20 w-full rounded-lg border border-slate-200 object-cover">
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.photos', $workOrder) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <select name="slot" required class="form-input text-xs" aria-label="{{ __('Photo slot') }}">
                                <option value="before">{{ __('Before') }}</option>
                                <option value="after">{{ __('After') }}</option>
                            </select>
                            <input type="file" name="photos[]" multiple required accept="image/jpeg,image/png,image/webp" class="form-input text-xs" aria-label="{{ __('Photos') }}">
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Upload Photos') }}</button>
                    </form>
                @endunless
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Labor') }}</h3>
                    <span class="text-sm font-extrabold text-slate-900">{{ __('Total: :amount EGP', ['amount' => number_format($workOrder->laborTotal(), 2)]) }}</span>
                </div>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($workOrder->laborItems as $item)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <span class="font-medium text-slate-700">{{ $item->description }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($item->cost, 2) }} {{ __('EGP') }}</span>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">{{ __('No labor recorded yet.') }}</p>
                    @endforelse
                </ul>
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.labor', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="description" required placeholder="{{ __('Description') }}" aria-label="{{ __('Description') }}" class="form-input text-xs">
                            <input type="number" name="cost" step="0.01" min="0" required placeholder="{{ __('Cost (EGP)') }}" aria-label="{{ __('Cost (EGP)') }}" class="form-input text-xs">
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Add Labor') }}</button>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Materials') }}</h3>
                    <span class="text-sm font-extrabold text-slate-900">{{ __('Total: :amount EGP', ['amount' => number_format($workOrder->materialsTotal(), 2)]) }}</span>
                </div>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($workOrder->materialUsages as $usage)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <span class="font-medium text-slate-700">{{ $usage->item->display_name ?? '—' }} × {{ $usage->quantity }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($usage->extendedCost(), 2) }} {{ __('EGP') }}</span>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">{{ __('No materials used yet.') }}</p>
                    @endforelse
                </ul>
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.materials', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <select name="inventory_item_id" required class="form-input text-xs" aria-label="{{ __('Material') }}">
                                <option value="">{{ __('Select material') }}</option>
                                @foreach ($stockedItems as $stockedItem)
                                    <option value="{{ $stockedItem->id }}">{{ $stockedItem->display_name }} ({{ $stockedItem->current_stock }} {{ __('left') }})</option>
                                @endforeach
                            </select>
                            <input type="number" name="quantity" min="1" required placeholder="{{ __('Qty') }}" aria-label="{{ __('Quantity') }}" class="form-input text-xs">
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Use Material') }}</button>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Additional Work') }}</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($workOrder->additionalWorkItems as $extra)
                        <li class="rounded-xl bg-slate-50 px-3 py-2">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-700">{{ $extra->description }}</span>
                                <span class="font-bold text-slate-900">{{ number_format($extra->cost, 2) }} {{ __('EGP') }}</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-500">{{ $extra->status->label() }}</span>
                                @if($extra->status === \App\Enums\AdditionalWorkStatus::Approved && !$workOrder->isCompleted())
                                    <form method="POST" action="{{ route('technician.jobs.additional-work.complete', $extra) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs font-bold text-teal-700 hover:underline">{{ __('Mark performed') }}</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">{{ __('No additional work requested yet.') }}</p>
                    @endforelse
                </ul>
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.additional-work', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        <input type="text" name="description" required placeholder="{{ __('Describe the extra problem…') }}" aria-label="{{ __('Extra work description') }}" class="form-input text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="cost" step="0.01" min="0" required placeholder="{{ __('Extra cost (EGP)') }}" aria-label="{{ __('Extra cost (EGP)') }}" class="form-input text-xs">
                            <button type="submit" class="secondary-button text-xs">{{ __('Request Approval') }}</button>
                        </div>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Completion') }}</h3>
                @if($workOrder->isCompleted())
                    <p class="mt-2 text-sm text-slate-500">{{ __('Completed :date.', ['date' => $workOrder->completed_at?->format('d M Y, h:i A')]) }}</p>
                @else
                    <p class="mt-2 text-xs text-slate-500">{{ __('Requires a recorded diagnosis, work notes, and resolved additional work.') }}</p>
                    <form method="POST" action="{{ route('technician.jobs.complete', $workOrder) }}" class="mt-3" data-confirm="{{ __('Mark this job as completed?') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="primary-button w-full text-xs">{{ __('Complete Job') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
