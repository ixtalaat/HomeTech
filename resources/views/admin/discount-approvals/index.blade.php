@extends('layouts.app')

@section('title', __('Discount Approvals'))

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to invoices') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Discount Approvals') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('High-value discounts requested by staff. Only managers can decide.') }}</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Invoice') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Discount') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Requested By') }}</th>
                        <th scope="col" class="px-6 py-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($approvals as $approval)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.invoices.show', $approval->invoice) }}" class="font-bold text-teal-700 hover:underline">
                                    {{ $approval->invoice->number }}
                                </a>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $approval->invoice->user->name ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-900">
                                {{ $approval->discount_type->label() }} {{ $approval->discount_value }}
                            </td>
                            <td class="px-6 py-4 text-xs">{{ $approval->requester->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                @if(auth()->user()->role === \App\Enums\UserRole::Manager)
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.discount-approvals.approve', $approval) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-teal-700 transition">{{ __('Approve') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.discount-approvals.reject', $approval) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-row-danger">{{ __('Reject') }}</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">{{ __('Awaiting manager') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">{{ __('No pending discount approvals.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($approvals->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $approvals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
