@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← Back to dashboard</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">Technician Reports</h2>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Technician</th>
                        <th scope="col" class="px-6 py-4">Assigned</th>
                        <th scope="col" class="px-6 py-4">Completed</th>
                        <th scope="col" class="px-6 py-4">Cancelled</th>
                        <th scope="col" class="px-6 py-4">Revenue</th>
                        <th scope="col" class="px-6 py-4">Rating</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($technicians as $technician)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $technician->name }}</td>
                            <td class="px-6 py-4">{{ $technician->assigned }}</td>
                            <td class="px-6 py-4">{{ $technician->completed }}</td>
                            <td class="px-6 py-4">{{ $technician->cancelled }}</td>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ number_format($technician->revenue, 2) }} EGP</td>
                            <td class="px-6 py-4 text-xs">
                                @if($technician->reviews_count > 0)
                                    <span class="font-extrabold text-amber-500">★ {{ number_format($technician->avg_rating, 1) }}</span>
                                    <span class="text-slate-400">({{ $technician->reviews_count }})</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">No technicians yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
