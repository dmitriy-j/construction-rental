{{-- resources/views/components/navbar.blade.php --}}
<nav class="navbar navbar-expand-lg navbar-dark fixed-top main-navbar">
    <div class="container-fluid px-3 px-xl-4">
        {{-- Логотип --}}
        <a class="navbar-brand" href="{{ url('/') }}">
            <div class="navbar-logo-container">
                <img src="{{ asset('images/logo/fap2.svg') }}"
                     alt="ФАП — Федеральная Арендная Платформа"
                     class="navbar-logo-img">
            </div>
        </a>

        @auth
        {{-- ДЛЯ АВТОРИЗОВАННЫХ --}}
        <div class="d-none d-lg-flex align-items-center flex-grow-1">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('catalog.index') ? 'active' : '' }}" href="{{ route('catalog.index') }}">
                        <i class="fas fa-th-list"></i> Каталог
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('rental-requests.index') ? 'active' : '' }}" href="{{ route('rental-requests.index') }}">
                        <i class="fas fa-file-alt"></i> Заявки
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs(['about', 'cooperation', 'contacts', 'jobs']) ? 'active' : '' }}"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-info-circle"></i> О нас
                    </a>
                    <ul class="dropdown-menu dropdown-menu-about">
                        <li><a class="dropdown-item {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}"><i class="fas fa-building"></i> О компании</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('cooperation') ? 'active' : '' }}" href="{{ route('cooperation') }}"><i class="fas fa-handshake"></i> Сотрудничество</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('contacts') ? 'active' : '' }}" href="{{ route('contacts') }}"><i class="fas fa-envelope"></i> Контакты</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('jobs') ? 'active' : '' }}" href="{{ route('jobs') }}"><i class="fas fa-briefcase"></i> Вакансии</a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-1">
                    <a class="nav-link position-relative notification-bell" href="{{ route('notifications') }}" title="Уведомления">
                        <i class="fas fa-bell"></i>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                {{ auth()->user()->unreadNotifications->count() > 99 ? '99+' : auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>
                </li>
                <li class="nav-item ms-1">
                    <a class="btn btn-warning btn-sm fw-bold cta-btn" href="{{ route('rental-requests.index') }}">
                        <i class="fas fa-plus-circle"></i> Создать заявку
                    </a>
                </li>
                <li class="nav-item dropdown profile-dropdown ms-1">
                    <a class="nav-link dropdown-toggle d-flex align-items-center profile-toggle" href="#"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar-circle me-2"><span>{{ mb_substr(Auth::user()->name, 0, 1) }}</span></div>
                        <span class="user-name d-none d-xl-inline">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end profile-menu">
                        @if(Auth::user()->isPlatformAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt"></i> Админ-панель</a></li>
                        @elseif(Auth::user()->company && Auth::user()->company->is_lessor)
                            <li><a class="dropdown-item" href="{{ route('lessor.dashboard') }}"><i class="fas fa-building"></i> Кабинет арендодателя</a></li>
                        @elseif(Auth::user()->company && Auth::user()->company->is_lessee)
                            <li><a class="dropdown-item" href="{{ route('lessee.dashboard') }}"><i class="fas fa-truck"></i> Кабинет арендатора</a></li>
                        @endif
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user"></i> Профиль</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Выйти</button></form></li>
                    </ul>
                </li>
            </ul>
        </div>

        {{-- Мобильная версия для авторизованных --}}
        <div class="d-flex d-lg-none align-items-center mobile-auth-controls">
            <a class="nav-link position-relative me-1 notification-bell-mobile" href="{{ route('notifications') }}" title="Уведомления">
                <i class="fas fa-bell"></i>
                @if(auth()->user()->unreadNotifications->count() > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">{{ auth()->user()->unreadNotifications->count() > 99 ? '99+' : auth()->user()->unreadNotifications->count() }}</span>
                @endif
            </a>
            <div class="nav-item dropdown profile-dropdown me-1">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-circle-sm"><span>{{ mb_substr(Auth::user()->name, 0, 1) }}</span></div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end profile-menu-mobile">
                    @if(Auth::user()->isPlatformAdmin())
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt"></i> Админ-панель</a></li>
                    @elseif(Auth::user()->company && Auth::user()->company->is_lessor)
                        <li><a class="dropdown-item" href="{{ route('lessor.dashboard') }}"><i class="fas fa-building"></i> Кабинет</a></li>
                    @elseif(Auth::user()->company && Auth::user()->company->is_lessee)
                        <li><a class="dropdown-item" href="{{ route('lessee.dashboard') }}"><i class="fas fa-truck"></i> Кабинет</a></li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user"></i> Профиль</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Выйти</button></form></li>
                </ul>
            </div>
            <button class="navbar-toggler border-0" type="button" id="sidebarToggleMobile" aria-label="Меню"><span class="navbar-toggler-icon"></span></button>
        </div>

        @else
        {{-- ДЛЯ НЕАВТОРИЗОВАННЫХ --}}
        {{-- Десктопная навигация (lg+) --}}
        <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarMainContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 nav-sections">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('catalog.index') ? 'active' : '' }}" href="{{ route('catalog.index') }}"><i class="fas fa-th-list"></i> Каталог</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('rental-requests.index') ? 'active' : '' }}" href="{{ route('rental-requests.index') }}"><i class="fas fa-file-alt"></i> Заявки</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-info-circle"></i> О нас</a>
                    <ul class="dropdown-menu"><li><a class="dropdown-item" href="{{ route('about') }}">О компании</a></li><li><a class="dropdown-item" href="{{ route('cooperation') }}">Сотрудничество</a></li><li><a class="dropdown-item" href="{{ route('contacts') }}">Контакты</a></li><li><a class="dropdown-item" href="{{ route('jobs') }}">Вакансии</a></li></ul>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto nav-controls-guest">
                <li class="nav-item"><a class="btn btn-warning btn-sm fw-bold me-2 cta-btn" href="{{ route('register') }}"><i class="fas fa-plus-circle"></i> Создать заявку</a></li>
                <li class="nav-item"><a class="btn btn-outline-light btn-sm me-2 login-btn" href="{{ route('login') }}"><i class="fas fa-sign-in-alt"></i> Войти</a></li>
                <li class="nav-item"><a class="btn btn-light btn-sm register-btn" href="{{ route('register') }}"><i class="fas fa-user-plus"></i> Регистрация</a></li>
            </ul>
        </div>

        {{-- Мобильная навигация (до lg) --}}
        <div class="d-flex d-lg-none align-items-center mobile-guest-controls">
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNavContent"
                    aria-controls="mobileNavContent" aria-expanded="false" aria-label="Меню"><span class="navbar-toggler-icon"></span></button>
        </div>

        <div class="collapse d-lg-none mobile-nav-overlay" id="mobileNavContent">
            <div class="mobile-nav-menu">
                <div class="mobile-nav-header">
                    <div class="mobile-nav-identity">
                        <div class="mobile-nav-icon"><i class="fas fa-hard-hat"></i></div>
                        <span class="mobile-nav-brand">Федеральная Арендная Платформа</span>
                    </div>
                </div>
                <ul class="mobile-nav-list">
                    <li class="mobile-nav-item">
                        <a class="mobile-nav-link {{ request()->routeIs('catalog.index') ? 'active' : '' }}" href="{{ route('catalog.index') }}">
                            <span class="mobile-nav-link-icon"><i class="fas fa-th-list"></i></span>
                            <span>Каталог техники</span>
                        </a>
                    </li>
                    <li class="mobile-nav-item">
                        <a class="mobile-nav-link {{ request()->routeIs('rental-requests.index') ? 'active' : '' }}" href="{{ route('rental-requests.index') }}">
                            <span class="mobile-nav-link-icon"><i class="fas fa-file-alt"></i></span>
                            <span>Заявки</span>
                        </a>
                    </li>
                    <li class="mobile-nav-item mobile-dropdown" data-dropdown>
                        <button class="mobile-nav-link mobile-dropdown-toggle" type="button" data-dropdown-toggle>
                            <span class="mobile-nav-link-icon"><i class="fas fa-info-circle"></i></span>
                            <span>О нас</span>
                            <i class="fas fa-chevron-down mobile-dropdown-arrow"></i>
                        </button>
                        <div class="mobile-dropdown-menu" data-dropdown-menu>
                            <a class="mobile-dropdown-item" href="{{ route('about') }}">О компании</a>
                            <a class="mobile-dropdown-item" href="{{ route('cooperation') }}">Сотрудничество</a>
                            <a class="mobile-dropdown-item" href="{{ route('contacts') }}">Контакты</a>
                            <a class="mobile-dropdown-item" href="{{ route('jobs') }}">Вакансии</a>
                        </div>
                    </li>
                </ul>
                <div class="mobile-nav-actions">
                    <a class="mobile-nav-btn mobile-nav-btn-primary" href="{{ route('register') }}">
                        <i class="fas fa-plus-circle me-1"></i> Создать заявку
                    </a>
                    <div class="mobile-nav-auth-buttons">
                        <a class="mobile-nav-btn mobile-nav-btn-outline" href="{{ route('login') }}">
                            <i class="fas fa-sign-in-alt me-1"></i> Войти
                        </a>
                        <a class="mobile-nav-btn mobile-nav-btn-ghost" href="{{ route('register') }}">
                            <i class="fas fa-user-plus me-1"></i> Регистрация
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endauth
    </div>
</nav>

<style>
/* ============================================================
   NAVBAR — PREMIUM GLASSMORPHISM
   ============================================================ */
.main-navbar {
    background: linear-gradient(135deg, rgba(11,94,215,0.97) 0%, rgba(0,45,114,0.97) 100%);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 2px 20px rgba(0, 45, 114, 0.25);
    padding: 0 1rem;
    min-height: var(--navbar-height, 72px);
    z-index: 9999;
}

.main-navbar .container-fluid { max-width: 1440px; margin: 0 auto; }

.navbar-brand { padding: 0; margin-right: 1.5rem; }
.navbar-logo-container { height: 48px; display: flex; align-items: center; }
.navbar-logo-img { height: 100%; width: auto; max-height: 48px; max-width: 180px; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.25)); }
@media (min-width: 1200px) { .navbar-logo-container { height: 52px; } .navbar-logo-img { max-width: 200px; } }
@media (max-width: 991.98px) { .navbar-logo-container { height: 40px; } .navbar-logo-img { max-height: 40px; max-width: 150px; } }
@media (max-width: 480px) { .navbar-logo-container { height: 36px; } .navbar-logo-img { max-height: 36px; max-width: 130px; } }

.navbar-nav .nav-link {
    padding: 0.5rem 0.75rem; margin: 0 0.1rem; border-radius: 8px;
    font-weight: 500; font-size: 0.875rem;
    color: rgba(255,255,255,0.9); white-space: nowrap;
    display: flex; align-items: center; gap: 0.35rem;
    transition: all 0.2s ease;
}
.navbar-nav .nav-link i { font-size: 0.9rem; }
.navbar-nav .nav-link:hover { background: rgba(255,255,255,0.12); color: #fff; }
.navbar-nav .nav-link.active { background: rgba(255,255,255,0.18); font-weight: 600; }

.dropdown-menu-about, .profile-menu { border: none; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); padding: 0.375rem; min-width: 220px; border: 1px solid rgba(0,0,0,0.05); }
.dropdown-menu-about .dropdown-item, .profile-menu .dropdown-item {
    padding: 0.5rem 0.875rem; margin: 0.125rem 0; border-radius: 8px;
    color: #1A1D21; font-weight: 500; font-size: 0.875rem; transition: all 0.2s ease;
}
.dropdown-menu-about .dropdown-item:hover, .profile-menu .dropdown-item:hover { background: rgba(11,94,215,0.08); color: #0B5ED7; }
.dropdown-menu-about .dropdown-item i, .profile-menu .dropdown-item i { width: 18px; text-align: center; margin-right: 0.5rem; color: #0B5ED7; }

.btn-ghost-light { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.85); width: 36px; height: 36px; padding: 0; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
.btn-ghost-light:hover { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.35); color: #fff; transform: rotate(15deg); }

.cta-btn { background: linear-gradient(135deg, #FF8C00 0%, #FF6B00 100%); border: none; color: #1a1a1a; border-radius: 8px; padding: 0.5rem 1rem; font-weight: 700; font-size: 0.875rem; box-shadow: 0 2px 10px rgba(255,140,0,0.3); transition: all 0.2s ease; }
.cta-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(255,140,0,0.4); color: #000; }

.notification-bell { padding: 0.375rem !important; border-radius: 50%; width: 36px; height: 36px; display: flex !important; align-items: center; justify-content: center; }
.notification-badge { font-size: 0.6rem; padding: 0.2em 0.4em; min-width: 18px; min-height: 18px; display: flex; align-items: center; justify-content: center; }

.user-avatar-circle { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255,255,255,0.3); }
.user-avatar-circle span { color: #fff; font-weight: 700; font-size: 0.8125rem; }
.user-name { color: rgba(255,255,255,0.9); font-weight: 500; font-size: 0.875rem; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

@media (max-width: 991.98px) {
    .main-navbar { min-height: var(--navbar-height, 65px); padding: 0 0.75rem; }
    .user-avatar-circle-sm { width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255,255,255,0.3); }
    .user-avatar-circle-sm span { color: #fff; font-weight: 700; font-size: 0.75rem; }
    .profile-menu-mobile { position: fixed; top: 60px; right: 10px; width: 250px; border: none; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.15); padding: 0.375rem; }
    .profile-menu-mobile .dropdown-item { padding: 0.5rem 0.875rem; margin: 0.125rem 0; border-radius: 8px; }
    .profile-menu-mobile .dropdown-item:hover { background: rgba(11,94,215,0.08); color: #0B5ED7; }

    /* ============================================================
        MOBILE MENU — PREMIUM DROPDOWN
       ============================================================ */
    .mobile-nav-overlay {
        position: fixed !important;
        top: var(--navbar-height, 72px) !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: rgba(0,0,0,0.3) !important;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 9998 !important;
        border: none !important;
    }

    .mobile-nav-menu {
        background: #fff;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 8px 40px rgba(0,0,0,0.12);
        max-height: calc(100vh - var(--navbar-height, 72px));
        overflow-y: auto;
        padding-bottom: 1rem;
    }

    .mobile-nav-header {
        padding: 1.25rem 1.25rem 0.75rem;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        margin-bottom: 0.5rem;
    }

    .mobile-nav-identity {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .mobile-nav-icon {
        width: 36px; height: 36px;
        background: linear-gradient(135deg, #0B5ED7, #002D72);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .mobile-nav-brand {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #1A1D21;
        line-height: 1.3;
    }

    .mobile-nav-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .mobile-nav-item {
        margin: 0.125rem 0.5rem;
    }

    .mobile-nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        color: #1A1D21;
        font-weight: 500;
        font-size: 0.9375rem;
        text-decoration: none;
        transition: all 0.2s ease;
        width: 100%;
        border: none;
        background: none;
        cursor: pointer;
        text-align: left;
    }

    .mobile-nav-link:active,
    .mobile-nav-link.active {
        background: rgba(11,94,215,0.08);
        color: #0B5ED7;
    }

    .mobile-nav-link-icon {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .mobile-nav-link.active .mobile-nav-link-icon {
        color: #0B5ED7;
    }

    .mobile-dropdown-arrow {
        margin-left: auto;
        font-size: 0.75rem;
        color: #6c757d;
        transition: transform 0.3s ease;
    }

    .mobile-dropdown.open .mobile-dropdown-arrow {
        transform: rotate(180deg);
    }

    .mobile-dropdown-menu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.35s ease, padding 0.35s ease;
        padding-left: 2.75rem;
    }

    .mobile-dropdown.open .mobile-dropdown-menu {
        max-height: 300px;
        padding-bottom: 0.5rem;
    }

    .mobile-dropdown-item {
        display: block;
        padding: 0.5rem 0.75rem;
        color: #495057;
        font-size: 0.875rem;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s ease;
        margin: 0.125rem 0;
    }

    .mobile-dropdown-item:active {
        background: rgba(11,94,215,0.06);
        color: #0B5ED7;
    }

    .mobile-nav-actions {
        padding: 1rem 1.25rem 0.5rem;
        border-top: 1px solid rgba(0,0,0,0.04);
        margin-top: 0.5rem;
    }

    .mobile-nav-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.2s ease;
        margin-bottom: 0.5rem;
    }

    .mobile-nav-btn-primary {
        background: linear-gradient(135deg, #FF8C00, #FF6B00);
        color: #1a1a1a;
        box-shadow: 0 2px 10px rgba(255,140,0,0.25);
    }

    .mobile-nav-btn-primary:active {
        transform: scale(0.98);
    }

    .mobile-nav-auth-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .mobile-nav-btn-outline {
        flex: 1;
        background: rgba(11,94,215,0.06);
        color: #0B5ED7;
        border: 1.5px solid rgba(11,94,215,0.15);
    }

    .mobile-nav-btn-outline:active {
        background: rgba(11,94,215,0.12);
    }

    .mobile-nav-btn-ghost {
        flex: 1;
        background: transparent;
        color: #6c757d;
    }

    .mobile-nav-btn-ghost:active {
        background: rgba(0,0,0,0.04);
    }

    .collapse:not(.show) .mobile-nav-overlay {
        display: none;
    }
}

@media (max-width: 576px) { .main-navbar { min-height: var(--navbar-height, 60px); } }

body { padding-top: var(--navbar-height, 72px); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Sidebar toggle (mobile auth)
    const sidebarToggle = document.getElementById('sidebarToggleMobile');
    const sidebarOffcanvas = document.getElementById('sidebarOffcanvas');
    if (sidebarToggle && sidebarOffcanvas) {
        let offcanvas = bootstrap.Offcanvas.getInstance(sidebarOffcanvas);
        if (!offcanvas) offcanvas = new bootstrap.Offcanvas(sidebarOffcanvas);
        sidebarToggle.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); offcanvas.toggle(); });
    }

    // Mobile dropdown toggle (неавторизованные)
    document.querySelectorAll('[data-dropdown-toggle]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var parent = this.closest('[data-dropdown]');
            if (parent) { parent.classList.toggle('open'); }
        });
    });

    // Close mobile nav on link click
    document.querySelectorAll('.mobile-nav-link[href], .mobile-dropdown-item').forEach(function(link) {
        link.addEventListener('click', function() {
            var navbar = document.getElementById('mobileNavContent');
            if (navbar && navbar.classList.contains('show')) {
                var collapse = bootstrap.Collapse.getInstance(navbar);
                if (collapse) collapse.hide();
            }
        });
    });

    // Close mobile nav on outside click
    document.addEventListener('click', function(e) {
        var navbar = document.getElementById('navbarMainContent');
        var toggler = document.querySelector('[data-bs-toggle="collapse"][data-bs-target="#navbarMainContent"]');
        if (navbar && toggler && navbar.classList.contains('show') && !navbar.contains(e.target) && !toggler.contains(e.target)) {
            var collapse = bootstrap.Collapse.getInstance(navbar);
            if (collapse) collapse.hide();
        }
    });
});
</script>
</write_to_file>
