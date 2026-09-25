@extends('layouts.app')

@section('title', $invoice->number)

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to invoices') }}</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ $invoice->number }}</h2>
            <x-status-badge :status="$invoice->status" />
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $invoice->user->name ?? '—' }} · {{ __('Request #:id', ['id' => $invoice->maintenance_request_id]) }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Line Items') }}</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($invoice->items as $item)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <div>
                                <p class="font-medium text-slate-700">{{ $item->description }}</p>
                                <p class="text-xs text-slate-400">{{ $item->item_type->label() }} · {{ $item->quantity }} × {{ number_format($item->unit_price, 2) }}</p>
                            </div>
                            <span class="font-bold text-slate-900">{{ number_format($item->total, 2) }} {{ __('SAR') }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-4 space-y-1 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <dt>{{ __('Subtotal') }}</dt>
                        <dd class="font-semibold">{{ number_format($invoice->subtotal, 2) }} {{ __('SAR') }}</dd>
                    </div>
                    @if($invoice->discount_amount > 0)
                        <div class="flex justify-between text-emerald-700">
                            <dt>{{ __('Discount (:type :value)', ['type' => $invoice->discount_type?->label(), 'value' => $invoice->discount_value]) }}</dt>
                            <dd class="font-semibold">−{{ number_format($invoice->discount_amount, 2) }} {{ __('SAR') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-extrabold text-slate-900">
                        <dt>{{ __('Total') }}</dt>
                        <dd>{{ number_format($invoice->total, 2) }} {{ __('SAR') }}</dd>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <dt>{{ __('Paid') }}</dt>
                        <dd class="font-semibold">{{ number_format($invoice->paid_amount, 2) }} {{ __('SAR') }}</dd>
                    </div>
                    <div class="flex justify-between font-bold text-teal-700">
                        <dt>{{ __('Remaining') }}</dt>
                        <dd>{{ number_format($invoice->remaining(), 2) }} {{ __('SAR') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Payments (:count)', ['count' => $invoice->payments->count()]) }}</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($invoice->payments as $payment)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <div>
                                <p class="font-bold text-slate-900">{{ number_format($payment->amount, 2) }} {{ __('SAR') }}</p>
                                <p class="text-xs text-slate-400">{{ $payment->method->label() }} · {{ $payment->paid_at?->format('d M Y, h:i A') }} · {{ __('by :name', ['name' => $payment->receiver->name ?? '—']) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($payment->confirmed_at)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ __('Confirmed') }}</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">{{ __('Pending') }}</span>
                                    <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg bg-teal-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-teal-700 transition">{{ __('Confirm') }}</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">{{ __('No payments recorded yet.') }}</p>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            @if($invoice->status === \App\Enums\InvoiceStatus::Draft)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Issue Invoice') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Issuing makes the invoice payable and moves the request to invoiced.') }}</p>
                    <form method="POST" action="{{ route('admin.invoices.issue', $invoice) }}" class="mt-3">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="primary-button w-full text-xs">{{ __('Issue Invoice') }}</button>
                    </form>
                </div>
            @endif

            @if(! in_array($invoice->status, [\App\Enums\InvoiceStatus::Paid, \App\Enums\InvoiceStatus::Cancelled], true))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Apply Discount') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Discounts above 20% or 500 SAR require a manager — yours will be queued for approval.') }}</p>
                    <form method="POST" action="{{ route('admin.invoices.discount', $invoice) }}" class="mt-3 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-2 gap-2">
                            <select name="discount_type" required class="form-input text-xs" aria-label="{{ __('Discount type') }}">
                                <option value="fixed">{{ __('Fixed (SAR)') }}</option>
                                <option value="percent">{{ __('Percent (%)') }}</option>
                            </select>
                            <input type="number" name="discount_value" step="0.01" min="0" required placeholder="{{ __('Value') }}" aria-label="{{ __('Discount value') }}" class="form-input text-xs">
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">{{ __('Apply Discount') }}</button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Record Payment') }}</h3>
                    <form method="POST" action="{{ route('admin.invoices.payments', $invoice) }}" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="amount" step="0.01" min="0.01" required placeholder="{{ __('Amount (SAR)') }}" aria-label="{{ __('Amount') }}" class="form-input text-xs">
                            <select name="method" required class="form-input text-xs" aria-label="{{ __('Method') }}">
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="card">{{ __('Card') }}</option>
                                <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                            </select>
                        </div>
                        <input type="text" name="reference" placeholder="{{ __('Reference (optional)') }}" aria-label="{{ __('Reference') }}" class="form-input text-xs">
                        <button type="submit" class="primary-button w-full text-xs">{{ __('Record Payment') }}</button>
                    </form>
                </div>

                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.invoices.cancel', $invoice) }}" data-confirm="{{ __('Cancel this invoice?') }}" class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition">{{ __('Cancel Invoice') }}</button>
                    </form>
                </div>
            @endif

            @if($invoice->status === \App\Enums\InvoiceStatus::Paid)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Close Invoice') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Closing moves the request to closed.') }}</p>
                    <form method="POST" action="{{ route('admin.invoices.close', $invoice) }}" class="mt-3">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="primary-button w-full text-xs">{{ __('Close Invoice') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
