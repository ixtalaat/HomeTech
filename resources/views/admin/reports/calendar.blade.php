@extends('layouts.app')

@section('title', __('Calendar'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Calendar') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $days->first()->format('d M Y') }} – {{ $days->last()->format('d M Y') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.reports.calendar', ['week' => $weekOffset - 1, 'branch_id' => $branchId]) }}" class="secondary-button text-xs">← {{ __('Previous week') }}</a>
            <a href="{{ route('admin.reports.calendar', ['branch_id' => $branchId]) }}" class="secondary-button text-xs">{{ __('This week') }}</a>
            <a href="{{ route('admin.reports.calendar', ['week' => $weekOffset + 1, 'branch_id' => $branchId]) }}" class="secondary-button text-xs">{{ __('Next week') }} →</a>
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.reports.calendar') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <input type="hidden" name="week" value="{{ $weekOffset }}">
            <div class="sm:w-64">
                <label for="branch_id" class="form-label text-xs">{{ __('Branch') }}</label>
                <select id="branch_id" name="branch_id" class="form-input text-xs py-2" onchange="this.form.submit()">
                    <option value="">{{ __('All branches') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (int) $branchId === $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="sticky start-0 bg-slate-50 px-6 py-4">{{ __('Technician') }}</th>
                        @foreach ($days as $day)
                            <th scope="col" class="px-4 py-4 text-center {{ $day->isToday() ? 'text-teal-700' : '' }}">
                                {{ __($day->format('l')) }}
                                <span class="block text-[11px] font-semibold normal-case">{{ $day->format('d M') }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($technicians as $technician)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="sticky start-0 bg-white px-6 py-4 font-bold text-slate-900 whitespace-nowrap">
                                {{ $technician->user->name ?? '—' }}
                            </td>
                            @foreach ($days as $day)
                                @php $slots = $bookings[$technician->id][$day->format('Y-m-d')] ?? []; @endphp
                                <td class="px-3 py-3 text-center align-top min-w-36">
                                    @forelse ($slots as $slot)
                                        <a href="{{ route('admin.requests.show', $slot->maintenance_request_id) }}"
                                            class="mb-1.5 block rounded-lg bg-teal-50 px-2 py-1.5 text-[11px] font-bold text-teal-800 hover:bg-teal-100 transition"
                                            title="#{{ $slot->maintenance_request_id }} · {{ $slot->request->service->display_name ?? '' }}">
                                            <span dir="ltr">{{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}</span>
                                            <span class="block truncate font-semibold">#{{ $slot->maintenance_request_id }}</span>
                                        </a>
                                    @empty
                                        <span class="text-slate-200">·</span>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                {{ __('No technicians found matching your criteria.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
