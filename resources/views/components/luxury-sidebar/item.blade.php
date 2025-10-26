@props([
    'route' => null,
    'icon' => '',
    'label' => '',
    'badge' => null,
    'params' => [],
    'hologramColor' => '#00d2ff'
])

<li class="luxury-nav-item" data-hologram-color="{{ $hologramColor }}">
    <a
        {{ $attributes->class(['luxury-nav-link']) }}
        @if($route) href="{{ route($route, $params) }}" @endif
    >
        <div class="luxury-nav-icon">
            <i class="bi bi-{{ $icon }}"></i>
        </div>
        <span class="luxury-nav-text">{{ $label }}</span>

        @if(isset($badge) && $badge > 0)
            <span class="luxury-nav-badge">{{ $badge }}</span>
        @endif

        <div class="luxury-active-indicator"></div>
        <div class="luxury-hover-effect"></div>
    </a>
</li>

<style>
    .luxury-nav-item {
        position: relative;
        perspective: 1000px;
        transform-style: preserve-3d;
    }

    .luxury-nav-link {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        color: rgba(255,255,255,0.85);
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: all 0.4s ease;
        background: var(--luxury-glass);
        backdrop-filter: blur(4px);
        border: 1px solid transparent;
        transform: translateZ(0);
    }

    .luxury-nav-link:hover,
    .luxury-nav-link.active {
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.15);
        transform: translateZ(10px);
    }

    .luxury-nav-link:hover .luxury-nav-icon {
        transform: scale(1.2) translateZ(20px);
    }

    .luxury-nav-icon {
        width: 24px;
        text-align: center;
        margin-right: 1.2rem;
        font-size: 1.3rem;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform: translateZ(10px);
    }

    .luxury-nav-text {
        flex: 1;
        font-weight: 500;
        letter-spacing: 0.3px;
        transform: translateZ(15px);
    }

    .luxury-nav-badge {
        background: #ff2e63;
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.6rem;
        border-radius: 12px;
        font-weight: 700;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transform: translateZ(10px);
    }

    .luxury-active-indicator {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 4px;
        background: var(--luxury-accent);
        transform: translateX(-100%);
        transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        border-radius: 0 4px 4px 0;
    }

    .luxury-nav-link.active .luxury-active-indicator {
        transform: translateX(0);
    }

    .luxury-hover-effect {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at var(--mouse-x) var(--mouse-y),
                      rgba(255,255,255,0.15), transparent 70%);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .luxury-nav-link:hover .luxury-hover-effect {
        opacity: 1;
    }
</style>

<script>
    // Динамический эффект при наведении
    document.querySelectorAll('.luxury-nav-item').forEach(item => {
        const link = item.querySelector('.luxury-nav-link');

        link.addEventListener('mousemove', e => {
            const rect = link.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            link.style.setProperty('--mouse-x', `${x}px`);
            link.style.setProperty('--mouse-y', `${y}px`);
        });

        // Эффект голограммы
        item.addEventListener('mouseenter', () => {
            const color = item.dataset.hologramColor;
            const particles = document.querySelectorAll('.luxury-particle');

            particles.forEach(particle => {
                particle.animate([
                    { boxShadow: `0 0 10px ${color}` },
                    { boxShadow: `0 0 20px ${color}` }
                ], {
                    duration: 1000,
                    fill: 'forwards'
                });
            });
        });
    });
</script>
