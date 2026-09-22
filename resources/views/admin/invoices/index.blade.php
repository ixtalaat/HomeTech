@extends('layouts.app')

@section('title', __('Invoices Management'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Invoices Management') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Generate invoices from completed jobs, apply discounts, and record payments.') }}</p>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.invoices.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3 items-end">
            <div>
                <label for="search" class="form-label text-xs">{{ __('Search') }}</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('Invoice number, customer...') }}" class="form-input text-xs py-2">
            </div>
            <div>
                <label for="status" class="form-label text-xs">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-input text-xs py-2">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="primary-button text-xs py-2 px-4 w-full">{{ __('Filter') }}</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.invoices.index') }}" class="secondary-button text-xs py-2 px-3">{{ __('Reset') }}</a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Invoice') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Customer') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Total') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Paid') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $invoice->number }}</td>
                            <td class="px-6 py-4 text-xs">{{ $invoice->user->name ?? '—' }}</td>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ number_format($invoice->total, 2) }} {{ __('EGP') }}</td>
                            <td class="px-6 py-4 text-xs">{{ number_format($invoice->paid_amount, 2) }} {{ __('EGP') }}</td>
                            <td class="px-6 py-4">
                                <x-status-badge :status="$invoice->status" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.invoices.show', $invoice) }}"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">{{ __('No invoices found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
