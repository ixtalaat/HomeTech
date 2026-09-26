@extends('errors.layout')

@section('code', '403')
@section('title', __('Not allowed'))
@section('message', __('You don’t have permission to view this page. If you think this is a mistake, contact support.'))

@section('actions')
    <a href="{{ route('dashboard') }}" class="primary-button">{{ __('Go to dashboard') }}</a>
    <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">{{ __('Back to home') }}</a>
@endsection
