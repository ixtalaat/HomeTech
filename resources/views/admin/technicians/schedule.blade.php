@extends('layouts.app')

@section('title', __('Work Schedule'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <a href="{{ $backUrl ?? route('admin.technicians.show', $technician) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-teal-700 transition">
            <span>← {{ __('Back to technician') }}</span>
        </a>
        <h2 class="mt-2 font-display text-2xl font-extrabold text-slate-900">{{ __('Work Schedule') }} · {{ $technician->user->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Set the working days and daily hours used by automatic assignment.') }}</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ $action ?? route('admin.technicians.schedule.update', $technician) }}" class="space-y-3">
            @csrf
            @method('PUT')

            @foreach ($days as $index => $day)
                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 p-3 sm:flex-row sm:items-center">
                    <input type="hidden" name="days[{{ $index }}][day_of_week]" value="{{ $day['day_of_week'] }}">
                    <div class="flex items-center gap-3 sm:w-44">
                        <input type="hidden" name="days[{{ $index }}][is_working]" value="0">
                        <input type="checkbox" id="working-{{ $index }}" name="days[{{ $index }}][is_working]" value="1"
                            {{ old("days.{$index}.is_working", $day['is_working'] ? '1' : '0') == '1' ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <label for="working-{{ $index }}" class="text-sm font-bold text-slate-900">
                            {{ __(Carbon\Carbon::create()->startOfWeek(Carbon\Carbon::SUNDAY)->addDays($day['day_of_week'])->format('l')) }}
                        </label>
                    </div>
                    <div class="grid flex-1 grid-cols-2 gap-2">
                        <div>
                            <label for="start-{{ $index }}" class="sr-only">{{ __('Start') }}</label>
                            <input type="time" id="start-{{ $index }}" name="days[{{ $index }}][start_time]"
                                value="{{ old("days.{$index}.start_time", $day['start_time']) }}" dir="ltr"
                                class="form-input text-xs @error("days.{$index}.start_time") border-rose-300 @enderror">
                        </div>
                        <div>
                            <label for="end-{{ $index }}" class="sr-only">{{ __('End') }}</label>
                            <input type="time" id="end-{{ $index }}" name="days[{{ $index }}][end_time]"
                                value="{{ old("days.{$index}.end_time", $day['end_time']) }}" dir="ltr"
                                class="form-input text-xs @error("days.{$index}.end_time") border-rose-300 @enderror">
                        </div>
                    </div>
                </div>
                @error("days.{$index}.start_time")
                    <p class="-mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
                @error("days.{$index}.end_time")
                    <p class="-mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            @endforeach

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ $backUrl ?? route('admin.technicians.show', $technician) }}" class="secondary-button">{{ __('Cancel') }}</a>
                <button type="submit" class="primary-button">{{ __('Update Schedule') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
