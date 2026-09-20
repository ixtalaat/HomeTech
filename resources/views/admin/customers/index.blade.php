@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8">
        <h2 class="font-display text-2xl font-extrabold text-slate-900">Customers Management</h2>
        <p class="mt-1 text-sm text-slate-500">View and manage customer accounts, profiles, and activation status.</p>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3 items-end">
            <div>
                <label for="search" class="form-label text-xs">Search Customer</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Name, email, or phone..." class="form-input text-xs py-2">
            </div>
            <div>
                <label for="status" class="form-label text-xs">Status</label>
                <select id="status" name="status" class="form-input text-xs py-2">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="primary-button text-xs py-2 px-4 w-full">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.customers.index') }}" class="secondary-button text-xs py-2 px-3">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-4">Customer</th>
                        <th scope="col" class="px-6 py-4">Phone</th>
                        <th scope="col" class="px-6 py-4">Addresses</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ $customer->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $customer->email }}</p>
                            </td>
                            <td class="px-6 py-4 text-xs">{{ $customer->phone ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs font-semibold">{{ $customer->addresses_count }}</td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.customers.toggle-status', $customer) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        title="Click to toggle status"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold transition {{ $customer->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $customer->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.customers.show', $customer) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">
                                        View
                                    </a>
                                    <a href="{{ route('admin.customers.edit', $customer) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 transition">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                No customers found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
