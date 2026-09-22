@extends('layouts.app')

@section('title', __('Edit Technician: :name', ['name' => $technician->user->name]))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('admin.technicians.show', $technician) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to technician') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Edit Technician: :name', ['name' => $technician->user->name]) }}</h2>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.technicians.update', $technician) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="form-label">{{ __('Name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $technician->user->name) }}" required
                        class="form-input @error('name') border-rose-300 @enderror">
                    @error('name')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone" class="form-label">{{ __('Work Phone') }}</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $technician->phone) }}"
                        class="form-input @error('phone') border-rose-300 @enderror">
                    @error('phone')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="emergency_contact" class="form-label">{{ __('Emergency Contact') }}</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact', $technician->emergency_contact) }}"
                        class="form-input @error('emergency_contact') border-rose-300 @enderror">
                    @error('emergency_contact')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="hired_at" class="form-label">{{ __('Hired At') }}</label>
                    <input type="date" id="hired_at" name="hired_at" value="{{ old('hired_at', $technician->hired_at?->format('Y-m-d')) }}"
                        class="form-input @error('hired_at') border-rose-300 @enderror">
                    @error('hired_at')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
                <select id="branch_id" name="branch_id" class="form-input @error('branch_id') border-rose-300 @enderror">
                    <option value="">{{ __('No branch') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('branch_id', $technician->branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="form-label">{{ __('Notes') }}</label>
                <textarea id="notes" name="notes" rows="3" class="form-input @error('notes') border-rose-300 @enderror">{{ old('notes', $technician->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <span class="form-label">{{ __('Skills (supported service categories)') }}</span>
                @php $selectedSkills = old('skills', $technician->categories->pluck('id')->all()); @endphp
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($categories as $category)
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="skills[]" value="{{ $category->id }}"
                                {{ in_array($category->id, $selectedSkills) ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                            {{ $category->display_name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $technician->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <label for="is_active" class="text-sm font-semibold text-slate-700">{{ __('Technician is active') }}</label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.technicians.show', $technician) }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Update Technician') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
