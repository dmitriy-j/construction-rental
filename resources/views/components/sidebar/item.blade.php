@props([
    'route' => null,
    'icon' => '',
    'label' => '',
    'badge' => null,
    'params' => [],
])

<li class="nav-item">
    <a
        {{ $attributes->class(['nav-link']) }}
        @if($route) href="{{ route($route, $params) }}" @endif
    >
        <div class="nav-icon">
            <i class="bi bi-{{ $icon }}"></i>
        </div>
        <span class="nav-text">{{ $label }}</span>

        @if(isset($badge) && $badge > 0)
            <span class="nav-badge">{{ $badge }}</span>
        @endif

        <div class="active-indicator"></div>
    </a>
</li>
