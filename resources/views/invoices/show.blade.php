@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← Back to invoices</span>
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ $invoice->number }}</h2>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                {{ $invoice->status->label() }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Line Items</h3>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($invoice->items as $item)
                    <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <div>
                            <p class="font-medium text-slate-700">{{ $item->description }}</p>
                            <p class="text-xs text-slate-400">{{ $item->item_type->label() }} · {{ $item->quantity }} × {{ number_format($item->unit_price, 2) }}</p>
                        </div>
                        <span class="font-bold text-slate-900">{{ number_format($item->total, 2) }} EGP</span>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-4 space-y-1 border-t border-slate-100 pt-4 text-sm">
                <div class="flex justify-between text-slate-600">
                    <dt>Subtotal</dt>
                    <dd class="font-semibold">{{ number_format($invoice->subtotal, 2) }} EGP</dd>
                </div>
                @if($invoice->discount_amount > 0)
                    <div class="flex justify-between text-emerald-700">
                        <dt>Discount</dt>
                        <dd class="font-semibold">−{{ number_format($invoice->discount_amount, 2) }} EGP</dd>
                    </div>
                @endif
                <div class="flex justify-between text-base font-extrabold text-slate-900">
                    <dt>Total</dt>
                    <dd>{{ number_format($invoice->total, 2) }} EGP</dd>
                </div>
                <div class="flex justify-between text-slate-600">
                    <dt>Paid</dt>
                    <dd class="font-semibold">{{ number_format($invoice->paid_amount, 2) }} EGP</dd>
                </div>
                <div class="flex justify-between font-bold text-teal-700">
                    <dt>Remaining</dt>
                    <dd>{{ number_format($invoice->remaining(), 2) }} EGP</dd>
                </div>
            </dl>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900">Payments ({{ $invoice->payments->count() }})</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($invoice->payments as $payment)
                        <li class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                            <span class="font-bold text-slate-900">{{ number_format($payment->amount, 2) }} EGP</span>
                            <span class="text-xs text-slate-400">{{ $payment->method->label() }} · {{ $payment->paid_at?->format('d M Y') }}</span>
                        </li>
                    @empty
                        <p class="text-sm text-slate-400">No payments yet.</p>
                    @endforelse
                </ul>
            </div>

            @if($invoice->acceptsPayments())
                <div class="rounded-2xl border border-teal-200 bg-teal-50/50 p-6 shadow-sm">
                    <h3 class="text-sm font-extrabold text-slate-900">Make a Payment</h3>
                    <p class="mt-1 text-xs text-slate-500">Pay in full ({{ number_format($invoice->remaining(), 2) }} EGP) or partially.</p>
                    <form method="POST" action="{{ route('invoices.stripe.checkout', $invoice) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="primary-button w-full text-xs">Pay {{ number_format($invoice->remaining(), 2) }} EGP Online by Card</button>
                    </form>
                    <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="mt-3 space-y-3 border-t border-teal-100 pt-3">
                        @csrf
                        <p class="text-xs font-bold text-slate-500">Or record a manual payment:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $invoice->remaining() }}" required value="{{ $invoice->remaining() }}" class="form-input text-xs">
                            <select name="method" required class="form-input text-xs" aria-label="Method">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <button type="submit" class="secondary-button w-full text-xs">Record Manual Payment</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
