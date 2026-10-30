@props(['label', 'value', 'icon' => 'bi-graph-up', 'tone' => 'primary', 'hint' => null])
<div class="stat-card">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value">{{ $value }}</div>
            @if($hint)
                <div class="text-muted-2" style="font-size:.75rem;">{{ $hint }}</div>
            @endif
        </div>
        <span class="stat-icon bg-soft-{{ $tone }} text-{{ $tone === 'primary' ? 'navy' : $tone }}">
            <i class="bi {{ $icon }}"></i>
        </span>
    </div>
</div>
