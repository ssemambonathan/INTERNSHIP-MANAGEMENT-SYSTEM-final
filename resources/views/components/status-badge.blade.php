@props(['status', 'label' => null])
@php
    $class = is_object($status) && method_exists($status, 'badgeClass') ? $status->badgeClass() : 'secondary';
    $text = $label ?? (is_object($status) && method_exists($status, 'label') ? $status->label() : (string) $status);
@endphp
<span {{ $attributes->merge(['class' => 'badge badge-soft-'.$class]) }}>{{ $text }}</span>
