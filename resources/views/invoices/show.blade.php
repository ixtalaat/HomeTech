@extends('layouts.app')

@section('title', $invoice->number)

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to invoices') }}</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ $invoice->number }}</h2>
            <x-status-badge :status="$invoice->status" />
        </div>
        @if($invoice->remaining() > 0 && $invoice->acceptsPayments())
            <p class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900" role="status">
                {{ __('Balance due: :amount SAR — pay in full or partially below.', ['amount' => number_format($invoice->remaining(), 2)]) }}
            </p>
        @elseif($invoice->status === \App\Enums\InvoiceStatus::Paid)
            <p class="mt-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900" role="status">
                {{ __('Fully paid. Thank you!') }}
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
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
                        <dt>{{ __('Discount') }}</dt>
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

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Payments (:count)', ['count' => $invoice->payments->count()]) }}</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($invoice->payments as $payment)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <span class="font-bold text-slate-900">{{ number_format($payment->amount, 2) }} {{ __('SAR') }}</span>
                            <span class="text-xs text-slate-400">
                                {{ $payment->method->label() }} · {{ $payment->paid_at?->format('d M Y') }}
                                · {{ $payment->confirmed_at ? __('Confirmed') : __('Awaiting confirmation') }}
                            </span>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">{{ __('No payments yet.') }}</p>
                    @endforelse
                </ul>
            </div>

            @if($invoice->acceptsPayments())
                <div class="rounded-2xl border border-teal-200 bg-teal-50/50 p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('Make a Payment') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Card payments go through secure Stripe Checkout; cash and bank transfers are recorded directly.') }}</p>
                    <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $invoice->remaining() }}" required value="{{ $invoice->remaining() }}" class="form-input text-xs" aria-label="{{ __('Amount') }}">
                            <select name="method" required class="form-input text-xs" aria-label="{{ __('Method') }}">
                                <option value="card">{{ __('Card (online)') }}</option>
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="primary-button w-full text-xs">{{ __('Pay :amount SAR', ['amount' => number_format($invoice->remaining(), 2)]) }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
