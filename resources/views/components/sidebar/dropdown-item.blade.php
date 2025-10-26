@props([
    'route' => null,
    'icon' => '',
    'label' => '',
    'params' => [],
])

<li>
    <a
        class="dropdown-item"
        @if($route) href="{{ route($route, $params) }}" @endif
    >
        <i class="bi bi-{{ $icon }} me-2"></i>
        {{ $label }}
    </a>
</li>
