@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('technician.jobs.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← Back to jobs</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">Job #{{ $workOrder->id }}</h2>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                {{ $workOrder->status->label() }}
            </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">
            {{ $workOrder->request->service->name ?? '—' }} ·
            {{ $workOrder->request->address->title ?? '' }} — {{ $workOrder->request->address->street ?? '' }}, {{ $workOrder->request->address->city ?? '' }}
        </p>
        @if($workOrder->isCompleted())
            <p class="mt-2 rounded-xl bg-slate-100 p-3 text-xs font-semibold text-slate-600">
                This job is completed and locked. Contact staff if a correction is needed.
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Diagnosis</h3>
                @if($workOrder->diagnosis)
                    <p class="mt-2 text-sm text-slate-700">{{ $workOrder->diagnosis }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-400">No diagnosis recorded yet.</p>
                @endif
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.diagnosis', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="diagnosis" rows="3" required placeholder="e.g. The AC compressor capacitor is damaged."
                            class="form-input text-sm">{{ old('diagnosis', $workOrder->diagnosis) }}</textarea>
                        <button type="submit" class="secondary-button w-full text-xs">Save Diagnosis</button>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Work Notes</h3>
                @if($workOrder->work_notes)
                    <p class="mt-2 text-sm text-slate-700">{{ $workOrder->work_notes }}</p>
                @endif
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.notes', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="work_notes" rows="3" required placeholder="Work performed, problems found, recommendations…"
                            class="form-input text-sm">{{ old('work_notes', $workOrder->work_notes) }}</textarea>
                        <button type="submit" class="secondary-button w-full text-xs">Save Notes</button>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Photos</h3>
                @foreach (['before' => $workOrder->before_photos, 'after' => $workOrder->after_photos] as $slot => $photos)
                    <p class="mt-3 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $slot }} ({{ count($photos ?? []) }})</p>
                    @if(! empty($photos))
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @foreach ($photos as $photo)
                                <a href="{{ Storage::url($photo) }}" target="_blank">
                                    <img src="{{ Storage::url($photo) }}" alt="{{ $slot }} photo" class="h-20 w-full rounded-lg border border-slate-200 object-cover">
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.photos', $workOrder) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <select name="slot" required class="form-input text-xs" aria-label="Photo slot">
                                <option value="before">Before</option>
                                <option value="after">After</option>
                            </select>
                            <input type="file" name="photos[]" multiple required accept="image/jpeg,image/png,image/webp" class="form-input text-xs">
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">Upload Photos</button>
                    </form>
                @endunless
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">Labor</h3>
                    <span class="text-sm font-extrabold text-slate-900">Total: {{ number_format($workOrder->laborTotal(), 2) }} EGP</span>
                </div>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($workOrder->laborItems as $item)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <span class="font-medium text-slate-700">{{ $item->description }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($item->cost, 2) }} EGP</span>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">No labor recorded yet.</p>
                    @endforelse
                </ul>
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.labor', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="description" required placeholder="Description" class="form-input text-xs">
                            <input type="number" name="cost" step="0.01" min="0" required placeholder="Cost (EGP)" class="form-input text-xs">
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">Add Labor</button>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">Materials</h3>
                    <span class="text-sm font-extrabold text-slate-900">Total: {{ number_format($workOrder->materialsTotal(), 2) }} EGP</span>
                </div>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($workOrder->materialUsages as $usage)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <span class="font-medium text-slate-700">{{ $usage->item->name ?? '—' }} × {{ $usage->quantity }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($usage->extendedCost(), 2) }} EGP</span>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">No materials used yet.</p>
                    @endforelse
                </ul>
                @unless($workOrder->isCompleted())
                    <form method="POST" action="{{ route('technician.jobs.materials', $workOrder) }}" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <select name="inventory_item_id" required class="form-input text-xs" aria-label="Material">
                                <option value="">Select material</option>
                                @foreach ($stockedItems as $stockedItem)
                                    <option value="{{ $stockedItem->id }}">{{ $stockedItem->name }} ({{ $stockedItem->current_stock }} left)</option>
                                @endforeach
                            </select>
                            <input type="number" name="quantity" min="1" required placeholder="Qty" class="form-input text-xs">
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">Use Material</button>
                    </form>
                @endunless
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Completion</h3>
                @if($workOrder->isCompleted())
                    <p class="mt-2 text-sm text-slate-500">Completed {{ $workOrder->completed_at?->format('d M Y, h:i A') }}.</p>
                @else
                    <p class="mt-2 text-xs text-slate-500">Requires a recorded diagnosis and work notes.</p>
                    <form method="POST" action="{{ route('technician.jobs.complete', $workOrder) }}" class="mt-3" onsubmit="return confirm('Mark this job as completed?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="primary-button w-full text-xs">Complete Job</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
