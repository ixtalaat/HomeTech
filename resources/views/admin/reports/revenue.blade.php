@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← Back to dashboard</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">Revenue Reports</h2>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Daily Revenue (last 30 days)</h3>
            <ul class="mt-3 max-h-72 space-y-1 overflow-y-auto text-sm">
                @forelse ($daily as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ $row->day }}</span>
                        <span class="font-bold text-slate-900">{{ number_format($row->total, 2) }} EGP</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">No revenue recorded yet.</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Monthly Revenue (last 12 months)</h3>
            <ul class="mt-3 max-h-72 space-y-1 overflow-y-auto text-sm">
                @forelse ($monthly as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ $row->month }}</span>
                        <span class="font-bold text-slate-900">{{ number_format($row->total, 2) }} EGP</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">No revenue recorded yet.</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Revenue by Service</h3>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($byService as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ $row->name }}</span>
                        <span class="font-bold text-slate-900">{{ number_format($row->total, 2) }} EGP</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">No invoiced work yet.</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Revenue by Technician</h3>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($byTechnician as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ $row->name }}</span>
                        <span class="font-bold text-slate-900">{{ number_format($row->total, 2) }} EGP</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">No paid work yet.</p>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-extrabold text-slate-900">Outstanding Invoices</h3>
        <ul class="mt-3 space-y-1 text-sm">
            @forelse ($outstanding as $invoice)
                <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="font-bold text-teal-700 hover:underline">{{ $invoice->number }} · {{ $invoice->user->name ?? '' }}</a>
                    <span class="font-bold text-rose-600">{{ number_format($invoice->total - $invoice->paid_amount, 2) }} EGP due</span>
                </li>
            @empty
                <p class="text-sm text-slate-400">No outstanding invoices.</p>
            @endforelse
        </ul>
    </div>
</div>
@endsection
