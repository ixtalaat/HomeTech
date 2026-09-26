@extends('layouts.app')

@section('title', __('Branches'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Branches') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Manage company branches, served cities, and assignment priority.') }}</p>
        </div>
        <a href="{{ route('admin.branches.create') }}" class="primary-button">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
            <span>{{ __('Add Branch') }}</span>
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-start text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Branch') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Branch Manager') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Cities') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Technicians') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Priority') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($branches as $branch)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $branch->name }}</td>
                            <td class="px-6 py-4 text-xs text-slate-600">{{ $branch->manager->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $branch->cities->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                    {{ $branch->technicians_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $branch->priority }}</td>
                            <td class="px-6 py-4">
                                @if($branch->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-end">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.branches.edit', $branch) }}"
                                        class="btn-row">
                                        {{ __('Edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" data-confirm="{{ __('Are you sure you want to delete this branch?') }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn-row-danger">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                {{ __('No branches yet. Click "Add Branch" to create one.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($branches->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $branches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
