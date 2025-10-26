@props([
    'icon' => '',
    'label' => '',
])

<li class="nav-item dropdown">
    <a
        class="nav-link dropdown-toggle"
        href="#"
        role="button"
        data-bs-toggle="dropdown"
        aria-expanded="false"
    >
        <div class="nav-icon">
            <i class="bi bi-{{ $icon }}"></i>
        </div>
        <span class="nav-text">{{ $label }}</span>
        <i class="bi bi-chevron-down dropdown-arrow"></i>
        <div class="active-indicator"></div>
    </a>
    <ul class="dropdown-menu">
        {{ $slot }}
    </ul>
</li>
