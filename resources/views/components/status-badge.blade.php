@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = $status instanceof \App\Enums\RequestStatus
        || $status instanceof \App\Enums\AppointmentStatus
        || $status instanceof \App\Enums\WorkOrderStatus
        || $status instanceof \App\Enums\InvoiceStatus
        || $status instanceof \App\Enums\AdditionalWorkStatus
        ? $status->label()
        : ucfirst(str_replace('_', ' ', $value));

    $tone = match ($value) {
        'pending_review', 'info_requested', 'pending', 'draft' => 'badge-pending',
        'approved', 'scheduled', 'confirmed', 'open' => 'badge-info',
        'technician_assigned', 'technician_on_way', 'in_progress', 'waiting_customer_approval', 'issued', 'partially_paid' => 'badge-active',
        'completed', 'paid', 'closed' => 'badge-success',
        'rejected', 'cancelled' => 'badge-danger',
        default => 'badge-neutral',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$tone}"]) }}>{{ $label }}</span>
