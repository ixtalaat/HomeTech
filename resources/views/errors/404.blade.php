@extends('errors.layout')

@section('code', '404')
@section('title', __('Page not found'))
@section('message', __('The page you are looking for moved, expired, or never existed. Let’s get you back on track.'))

@section('actions')
    <a href="{{ url('/') }}" class="primary-button">{{ __('Back to home') }}</a>
    <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">{{ __('Browse services') }}</a>
@endsection
