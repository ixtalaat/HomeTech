@extends('layouts.app')

@section('title', $technician->user->name ?? __('Technician'))

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('admin.technicians.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to technicians') }}</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ $technician->user->name ?? '—' }}</h2>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $technician->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                {{ $technician->is_active ? __('Active') : __('Inactive') }}
            </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $technician->user->email ?? '' }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-extrabold text-slate-900">{{ __('Profile') }}</h3>
        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Work Phone') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $technician->phone ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Emergency Contact') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $technician->emergency_contact ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Hired At') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $technician->hired_at?->format('d M Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Branch') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $technician->branch->name ?? __('No branch') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Assigned Requests') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $technician->assignedRequests->count() }}</dd>
            </div>
        </dl>
        @if($technician->notes)
            <p class="mt-4 text-sm text-slate-600">{{ $technician->notes }}</p>
        @endif
        <div class="mt-6 flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.technicians.edit', $technician) }}" class="secondary-button text-xs">{{ __('Edit Technician') }}</a>
            <form method="POST" action="{{ route('admin.technicians.toggle-status', $technician) }}" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="secondary-button text-xs">{{ $technician->is_active ? __('Deactivate') : __('Activate') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.technicians.destroy', $technician) }}" data-confirm="{{ __('Remove this technician?') }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition">{{ __('Remove') }}</button>
            </form>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Work Schedule') }}</h3>
            <a href="{{ route('admin.technicians.schedule.edit', $technician) }}" class="secondary-button text-xs">{{ __('Edit Schedule') }}</a>
        </div>
        <div class="mt-3 space-y-1 text-sm">
            @forelse ($technician->schedules->sortBy('day_of_week') as $row)
                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                    <span class="font-semibold text-slate-700">{{ __(Carbon\Carbon::create()->startOfWeek(Carbon\Carbon::SUNDAY)->addDays($row->day_of_week)->format('l')) }}</span>
                    @if($row->is_working)
                        <span class="font-bold text-slate-900" dir="ltr">{{ substr((string) $row->start_time, 0, 5) }} – {{ substr((string) $row->end_time, 0, 5) }}</span>
                    @else
                        <span class="font-bold text-slate-400">{{ __('Off') }}</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('No schedule yet.') }}</p>
            @endforelse
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-extrabold text-slate-900">{{ __('Skills') }}</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse ($technician->categories as $category)
                <span class="inline-flex items-center rounded-lg bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">
                    {{ $category->display_name }}
                </span>
            @empty
                <p class="text-sm text-slate-500">{{ __('No skills assigned yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
