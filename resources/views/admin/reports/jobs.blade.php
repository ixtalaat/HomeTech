@extends('layouts.app')

@section('title', __('Job Reports'))

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to dashboard') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Job Reports') }}</h2>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Jobs by Status') }}</h3>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($byStatus as $status => $total)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ \App\Enums\RequestStatus::from($status)->label() }}</span>
                        <span class="font-bold text-slate-900">{{ $total }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No jobs yet.') }}</p>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Jobs by Service') }}</h3>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($byService as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="text-slate-600">{{ $row->name }}</span>
                        <span class="font-bold text-slate-900">{{ $row->total }}</span>
                    </li>
                @empty
                    <p class="text-sm text-slate-400">{{ __('No jobs yet.') }}</p>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">{{ __('Average Completion Time') }}</h3>
            <p class="mt-2 font-display text-3xl font-extrabold text-slate-900">
                {{ $averageCompletionHours !== null ? __(':hours hours', ['hours' => $averageCompletionHours]) : '—' }}
            </p>
            <p class="mt-1 text-xs text-slate-500">{{ __('From visit start to work completion, across all completed jobs.') }}</p>
    </div>
</div>
@endsection
