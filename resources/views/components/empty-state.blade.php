@props(['icon' => 'bi-inbox', 'title' => 'Nothing here yet', 'text' => null, 'action' => null, 'actionLabel' => null])
<div class="empty-state">
    <i class="bi {{ $icon }}"></i>
    <h6>{{ $title }}</h6>
    @if($text)
        <p>{{ $text }}</p>
    @endif
    @if($action && $actionLabel)
        <a href="{{ $action }}" class="btn btn-sm btn-primary mt-3">{{ $actionLabel }}</a>
    @endif
</div>
