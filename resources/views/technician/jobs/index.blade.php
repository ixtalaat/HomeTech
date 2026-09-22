@extends('layouts.app')

@section('title', __('My jobs'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('My jobs') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Start visits and document diagnosis, labor, materials, and notes.') }}</p>
    </div>

    @if($awaitingStart->isNotEmpty())
        <h3 class="mb-3 text-sm font-extrabold text-slate-900">{{ __('Awaiting Start (:count)', ['count' => $awaitingStart->count()]) }}</h3>
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($awaitingStart as $maintenanceRequest)
                <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm">
                    <p class="font-bold text-slate-900">#{{ $maintenanceRequest->id }} · {{ $maintenanceRequest->service->display_name ?? '—' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $maintenanceRequest->address->title ?? '' }} — {{ $maintenanceRequest->address->street ?? '' }}, {{ $maintenanceRequest->address->city ?? '' }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ __('Visit:') }} {{ $maintenanceRequest->appointment->date->format('d M Y') }},
                        {{ \Carbon\Carbon::parse($maintenanceRequest->appointment->start_time)->format('h:i A') }}
                    </p>
                    <form method="POST" action="{{ route('technician.jobs.start', $maintenanceRequest) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="primary-button w-full text-xs">{{ __('Start Visit') }}</button>
                    </form>
                    @if($maintenanceRequest->status === \App\Enums\RequestStatus::Scheduled)
                        <form method="POST" action="{{ route('technician.jobs.on-way', $maintenanceRequest) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="secondary-button w-full text-xs">{{ __("I'm On The Way") }}</button>
                        </form>
                    @else
                        <p class="mt-2 text-center text-xs font-bold text-teal-700">{{ __('En route — customer notified') }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <h3 class="mb-3 text-sm font-extrabold text-slate-900">{{ __('Work Orders') }}</h3>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Job') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Service') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($workOrders as $workOrder)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">#{{ $workOrder->id }}</td>
                            <td class="px-6 py-4 text-xs">{{ $workOrder->request->service->display_name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <x-status-badge :status="$workOrder->status" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('technician.jobs.show', $workOrder) }}"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">
                                    {{ __('Open') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                {{ __('No work orders yet. Start a visit from an assigned job above.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($workOrders->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $workOrders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
