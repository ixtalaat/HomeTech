@props(['service'])

@if($service->coverPhotoUrl())
    <img src="{{ $service->coverPhotoUrl() }}" alt="{{ $service->display_name }}" loading="lazy" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'relative flex items-center justify-center overflow-hidden bg-gradient-to-br from-teal-600 via-teal-800 to-slate-900']) }} role="img" aria-label="{{ $service->display_name }}">
        <span aria-hidden="true" class="pointer-events-none absolute inset-0 flex items-center justify-center font-display text-7xl font-extrabold text-white/10 select-none">{{ mb_substr($service->category->display_name ?? $service->display_name, 0, 1) }}</span>
        <svg class="relative h-12 w-12 text-white/90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
    </div>
@endif
