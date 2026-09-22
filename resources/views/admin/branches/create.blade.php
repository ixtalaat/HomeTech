@extends('layouts.app')

@section('title', __('Add Branch'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('admin.branches.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to branches') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Add Branch') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Create a branch with the cities it serves.') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.branches.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="form-label">{{ __('Branch Name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="form-input @error('name') border-rose-300 @enderror">
                    @error('name')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="priority" class="form-label">{{ __('Priority') }}</label>
                    <input type="number" id="priority" name="priority" value="{{ old('priority', 0) }}" min="0"
                        class="form-input @error('priority') border-rose-300 @enderror">
                    <p class="mt-1 text-xs text-slate-400">{{ __('Higher priority branches back up lower ones on overflow.') }}</p>
                    @error('priority')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="cities" class="form-label">{{ __('Cities (comma-separated)') }}</label>
                <input type="text" id="cities" name="cities" value="{{ old('cities') }}" dir="auto"
                    placeholder="{{ __('e.g. Riyadh, Jeddah') }}"
                    class="form-input @error('cities') border-rose-300 @enderror">
                @error('cities')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_active" class="text-sm font-semibold text-slate-700">{{ __('Branch is active and visible') }}</label>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                <h3 class="text-sm font-extrabold text-slate-900">{{ __('Branch Manager') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('One active manager per branch. Super admins can assign, change, or remove them here.') }}</p>
                @php $managerMode = old('manager_mode', 'none'); @endphp
                <div class="mt-4 space-y-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="radio" name="manager_mode" value="none" {{ $managerMode === 'none' ? 'checked' : '' }}
                            class="h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500">
                        {{ __('No manager') }}
                    </label>
                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="radio" name="manager_mode" value="existing" {{ $managerMode === 'existing' ? 'checked' : '' }}
                                class="h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500">
                            {{ __('Assign an existing manager account') }}
                        </label>
                        <select name="manager_user_id" class="form-input mt-2 @error('manager_user_id') border-rose-300 @enderror">
                            <option value="">{{ __('Select manager') }}</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}" {{ (string) old('manager_user_id') === (string) $manager->id ? 'selected' : '' }}>
                                    {{ $manager->name }} ({{ $manager->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('manager_user_id')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">{{ __('Or create a manager account') }}</p>
                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <input type="text" name="manager_name" value="{{ old('manager_name') }}" placeholder="{{ __('Full name') }}"
                                class="form-input text-xs @error('manager_name') border-rose-300 @enderror">
                            <input type="email" name="manager_email" value="{{ old('manager_email') }}" placeholder="{{ __('Email address') }}" dir="ltr"
                                class="form-input text-xs @error('manager_email') border-rose-300 @enderror">
                            <input type="password" name="manager_password" placeholder="{{ __('Initial Password') }}"
                                class="form-input text-xs @error('manager_password') border-rose-300 @enderror">
                        </div>
                        <label class="mt-2 flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <input type="radio" name="manager_mode" value="new" {{ $managerMode === 'new' ? 'checked' : '' }}
                                class="h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500">
                            {{ __('Create this account and assign it') }}
                        </label>
                        @error('manager_name')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('manager_email')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('manager_password')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.branches.index') }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Create Branch') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
