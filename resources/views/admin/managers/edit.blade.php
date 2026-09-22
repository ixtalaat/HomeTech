@extends('layouts.app')

@section('title', __('Edit Manager: :name', ['name' => $manager->name]))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('admin.managers.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to managers') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Edit Manager: :name', ['name' => $manager->name]) }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $manager->email }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.managers.update', $manager) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="form-label">{{ __('Name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $manager->name) }}" required
                        class="form-input @error('name') border-rose-300 @enderror">
                    @error('name')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="form-label">{{ __('Email') }} <span class="text-rose-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $manager->email) }}" required dir="ltr"
                        class="form-input @error('email') border-rose-300 @enderror">
                    @error('email')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="password" class="form-label">{{ __('New Password (leave blank to keep)') }}</label>
                    <input type="password" id="password" name="password"
                        class="form-input @error('password') border-rose-300 @enderror">
                    @error('password')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
                    <select id="branch_id" name="branch_id" class="form-input @error('branch_id') border-rose-300 @enderror">
                        <option value="">{{ __('No branch') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) old('branch_id', $manager->managedBranch?->id ?? '') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $manager->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_active" class="text-sm font-semibold text-slate-700">{{ __('Account is active') }}</label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.managers.index') }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Update Manager') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
