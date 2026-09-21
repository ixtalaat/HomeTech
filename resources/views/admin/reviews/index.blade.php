@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Customer Reviews') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Feedback submitted after completed jobs.') }}</p>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="sm:w-48">
                <label for="rating" class="form-label text-xs">{{ __('Filter by Rating') }}</label>
                <select id="rating" name="rating" class="form-input text-xs py-2" onchange="this.form.submit()">
                    <option value="">{{ __('All Ratings') }}</option>
                    @for($stars = 5; $stars >= 1; $stars--)
                        <option value="{{ $stars }}" {{ (int) request('rating') === $stars ? 'selected' : '' }}>{{ $stars }} {{ __('stars') }}</option>
                    @endfor
                </select>
            </div>
            @if(request('rating'))
                <a href="{{ route('admin.reviews.index') }}" class="secondary-button text-xs py-2 px-3">{{ __('Reset') }}</a>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Rating') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Comment') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Customer') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Request') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($reviews as $review)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-extrabold text-amber-500 whitespace-nowrap">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</td>
                            <td class="px-6 py-4 text-xs">{{ $review->comment ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs">{{ $review->user->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs">
                                <a href="{{ route('admin.requests.show', $review->maintenance_request_id) }}" class="font-bold text-teal-700 hover:underline">
                                    #{{ $review->maintenance_request_id }} · {{ $review->request->service->name ?? '' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">{{ __('No reviews yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
