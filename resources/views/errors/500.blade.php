@extends('errors.layout')

@section('code', '500')
@section('title', __('Something went wrong'))
@section('message', __('Our team has been notified. Please try again in a moment, or reach out if the problem persists.'))

@section('actions')
    <a href="{{ url('/') }}" class="primary-button">{{ __('Back to home') }}</a>
    <a href="{{ route('contact.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">{{ __('Contact support') }}</a>
@endsection
