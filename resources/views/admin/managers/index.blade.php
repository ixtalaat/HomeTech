@extends('layouts.app')

@section('title', __('Branch Managers'))

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <h2 class="font-display text-2xl font-extrabold text-slate-900">{{ __('Branch Managers') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('One active manager per branch. Assign them from the branch page.') }}</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-start text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">{{ __('Manager') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Branch') }}</th>
                        <th scope="col" class="px-6 py-4">{{ __('Status') }}</th>
                        <th scope="col" class="px-6 py-4 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($managers as $manager)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ $manager->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $manager->email }}</p>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold">
                                @if($manager->managedBranch)
                                    <a href="{{ route('admin.branches.edit', $manager->managedBranch) }}" class="text-teal-700 hover:underline">
                                        {{ $manager->managedBranch->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400">{{ __('Unassigned') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($manager->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ __('Active') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-end">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.managers.edit', $manager) }}"
                                        class="btn-row">
                                        {{ __('Edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.managers.toggle-status', $manager) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="btn-row">
                                            {{ $manager->is_active ? __('Deactivate') : __('Activate') }}
                                        </button>
                                    </form>
                                    @if($manager->managedBranch)
                                        <form method="POST" action="{{ route('admin.managers.unassign', $manager) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn-row-danger">
                                                {{ __('Unassign manager') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                {{ __('No managers yet. Create one from a branch page.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($managers->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $managers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
