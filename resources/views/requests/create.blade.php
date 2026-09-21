@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ route('requests.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to requests') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('New Maintenance Request') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Describe the problem, pick a service address, and choose a preferred appointment.') }}</p>
    </div>

    @if($addresses->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-medium text-amber-900">
            {{ __('You need a service address first.') }}
            <a href="{{ route('addresses.create') }}" class="font-bold underline">{{ __('Add an address') }}</a> {{ __('to continue.') }}
        </div>
    @else
        <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
            <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label for="service_id" class="form-label">{{ __('Service') }} <span class="text-rose-500">*</span></label>
                    <select id="service_id" name="service_id" required
                        class="form-input @error('service_id') border-rose-300 @enderror">
                        <option value="">{{ __('Select a service') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" {{ (string) old('service_id', $selectedService) === (string) $service->id ? 'selected' : '' }}>
                                {{ $service->display_name }} — {{ number_format($service->base_price, 2) }} {{ __('EGP') }}
                            </option>
                        @endforeach
                    </select>
                    @error('service_id')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="address_id" class="form-label">{{ __('Service Address') }} <span class="text-rose-500">*</span></label>
                    <select id="address_id" name="address_id" required
                        class="form-input @error('address_id') border-rose-300 @enderror">
                        <option value="">{{ __('Select an address') }}</option>
                        @foreach ($addresses as $address)
                            <option value="{{ $address->id }}" {{ (string) old('address_id') === (string) $address->id ? 'selected' : '' }}>
                                {{ $address->title }} — {{ $address->street }}, {{ $address->city }}{{ $address->is_default ? ' ('.__('Default').')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('address_id')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="form-label">{{ __('Problem Description') }} <span class="text-rose-500">*</span></label>
                    <textarea id="description" name="description" rows="4" required placeholder="{{ __('e.g. My AC is running but is not cooling the room.') }}"
                        class="form-input @error('description') border-rose-300 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="preferred_date" class="form-label">{{ __('Preferred Date') }} <span class="text-rose-500">*</span></label>
                        <input type="date" id="preferred_date" name="preferred_date" value="{{ old('preferred_date') }}" required min="{{ now()->addDay()->format('Y-m-d') }}"
                            class="form-input @error('preferred_date') border-rose-300 @enderror">
                        @error('preferred_date')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="preferred_time" class="form-label">{{ __('Preferred Time') }} <span class="text-rose-500">*</span></label>
                        <input type="time" id="preferred_time" name="preferred_time" value="{{ old('preferred_time') }}" required
                            class="form-input @error('preferred_time') border-rose-300 @enderror">
                        @error('preferred_time')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="photos" class="form-label">{{ __('Photos (optional, up to 5)') }}</label>
                    <input type="file" id="photos" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"
                        class="form-input @error('photos') border-rose-300 @enderror @error('photos.*') border-rose-300 @enderror">
                    @error('photos')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('photos.*')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                    <a href="{{ route('requests.index') }}" class="secondary-button">{{ __('Cancel') }}</a>
                    <button type="submit" class="primary-button">{{ __('Submit Request') }}</button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
