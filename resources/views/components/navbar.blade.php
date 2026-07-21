{{-- resources/views/components/navbar.blade.php --}}
<nav class="navbar navbar-expand-lg navbar-dark fixed-top main-navbar">
    <div class="container-fluid">
        {{-- Логотип --}}
        <a class="navbar-brand" href="{{ url('/') }}">
            <div class="navbar-logo-container">
                <img src="{{ asset('images/logo/fap2.svg') }}"
                     alt="ФАП - Федеральная Арендная Платформа"
                     class="navbar-logo-img">
            </div>
        </a>

        @auth
        {{-- ============================================================
             ДЛЯ АВТОРИЗОВАННЫХ ПОЛЬЗОВАТЕЛЕЙ
             ============================================================ --}}

        {{-- Десктоп --}}
        <div class="d-none d-lg-flex align-items-center flex-grow-1">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('catalog.index') ? 'active' : '' }}" href="{{ route('catalog.index') }}">
                        <i class="fas fa-th-list me-1"></i> Каталог
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('rental-requests.index') ? 'active' : '' }}" href="{{ route('rental-requests.index') }}">
                        <i class="fas fa-file-alt me-1"></i> Заявки
                    </a>
                </li>

                {{-- Выпадающее меню "О нас" --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs(['about', 'cooperation', 'contacts', 'jobs']) ? 'active' : '' }}"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="aboutDropdown">
                        <i class="fas fa-info-circle me-1"></i> О нас
                    </a>
                    <ul class="dropdown-menu dropdown-menu-about" aria-labelledby="aboutDropdown">
                        <li><a class="dropdown-item {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">
                            <i class="fas fa-building me-2"></i> О компании
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('cooperation') ? 'active' : '' }}" href="{{ route('cooperation') }}">
                            <i class="fas fa-handshake me-2"></i> Сотрудничество
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('contacts') ? 'active' : '' }}" href="{{ route('contacts') }}">
                            <i class="fas fa-envelope me-2"></i> Контакты
                        </a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('jobs') ? 'active' : '' }}" href="{{ route('jobs') }}">
                            <i class="fas fa-briefcase me-2"></i> Вакансии
                        </a></li>
                    </ul>
                </li>
            </ul>

            {{-- Элементы управления --}}
            <ul class="navbar-nav ms-auto align-items-center nav-controls-desktop">
                <li class="nav-item">
                    <a class="btn btn-warning btn-sm fw-bold me-2 cta-btn" href="{{ route('rental-requests.index') }}">
                        <i class="fas fa-plus-circle me-1"></i> Создать заявку
                    </a>
                </li>
                <li class="nav-item">
                    <button class="btn btn-outline-light btn-sm theme-switcher" data-theme-toggle title="Сменить тему">
                        <i class="fas fa-sun"></i>
                    </button>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="{{ route('notifications') }}" title="Уведомления">
                        <i class="fas fa-bell"></i>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>
                </li>
                <li class="nav-item dropdown profile-dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center profile-toggle" href="#"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false" id="profileDropdown">
                        <div class="user-avatar me-2"><i class="fas fa-user-circle"></i></div>
                        <span class="user-name">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end profile-menu">
                        @if(Auth::user()->isPlatformAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i> Админ-панель</a></li>
                        @elseif(Auth::user()->company && Auth::user()->company->is_lessor)
                            <li><a class="dropdown-item" href="{{ route('lessor.dashboard') }}"><i class="fas fa-building me-2"></i> Кабинет арендодателя</a></li>
                        @elseif(Auth::user()->company && Auth::user()->company->is_lessee)
                            <li><a class="dropdown-item" href="{{ route('lessee.dashboard') }}"><i class="fas fa-truck me-2"></i> Кабинет арендатора</a></li>
                        @endif
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user me-2"></i> Профиль</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Выйти</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>

        {{-- Мобильная версия для авторизованных --}}
        <div class="d-flex d-lg-none align-items-center mobile-auth-controls">
            <a class="nav-link position-relative me-2" href="{{ route('notifications') }}" title="Уведомления">
                <i class="fas fa-bell"></i>
                @if(auth()->user()->unreadNotifications->count() > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.5rem;">{{ auth()->user()->unreadNotifications->count() }}</span>
                @endif
            </a>
            <button class="btn btn-outline-light btn-sm me-2 theme-switcher" data-theme-toggle title="Сменить тему"><i class="fas fa-sun"></i></button>
            <div class="nav-item dropdown profile-dropdown me-2">
                <a class="nav-link dropdown-toggle d-flex align-items-center profile-toggle-mobile" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user-circle"></i></a>
                <ul class="dropdown-menu dropdown-menu-end profile-menu-mobile">
                    @if(Auth::user()->isPlatformAdmin())
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i> Админ-панель</a></li>
                    @elseif(Auth::user()->company && Auth::user()->company->is_lessor)
                        <li><a class="dropdown-item" href="{{ route('lessor.dashboard') }}"><i class="fas fa-building me-2"></i> Кабинет</a></li>
                    @elseif(Auth::user()->company && Auth::user()->company->is_lessee)
                        <li><a class="dropdown-item" href="{{ route('lessee.dashboard') }}"><i class="fas fa-truck me-2"></i> Кабинет</a></li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user me-2"></i> Профиль</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Выйти</button></form></li>
                </ul>
            </div>
            <button class="navbar-toggler border-0" type="button" id="sidebarToggleMobile"><span class="navbar-toggler-icon"></span></button>
        </div>

        @else
        {{-- ============================================================
             ДЛЯ НЕАВТОРИЗОВАННЫХ
             ============================================================ --}}

        {{-- Мобильные кнопки --}}
        <div class="d-flex d-lg-none align-items-center mobile-guest-controls">
            <button class="btn btn-outline-light btn-sm me-2 theme-switcher" data-theme-toggle title="Сменить тему"><i class="fas fa-sun"></i></button>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMainContent"
                    aria-controls="navbarMainContent" aria-expanded="false" aria-label="Переключить навигацию">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarMainContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 nav-sections">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('catalog.index') ? 'active' : '' }}" href="{{ route('catalog.index') }}">
                        <i class="fas fa-th-list me-1"></i> Каталог
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('rental-requests.index') ? 'active' : '' }}" href="{{ route('rental-requests.index') }}">
                        <i class="fas fa-file-alt me-1"></i> Заявки
                    </a>
                </li>
                {{-- Выпадающее меню "О нас" для мобильных --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs(['about', 'cooperation', 'contacts', 'jobs']) ? 'active' : '' }}"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-info-circle me-1"></i> О нас
                    </a>
                    <ul class="dropdown-menu dropdown-menu-about">
                        <li><a class="dropdown-item" href="{{ route('about') }}"><i class="fas fa-building me-2"></i> О компании</a></li>
                        <li><a class="dropdown-item" href="{{ route('cooperation') }}"><i class="fas fa-handshake me-2"></i> Сотрудничество</a></li>
                        <li><a class="dropdown-item" href="{{ route('contacts') }}"><i class="fas fa-envelope me-2"></i> Контакты</a></li>
                        <li><a class="dropdown-item" href="{{ route('jobs') }}"><i class="fas fa-briefcase me-2"></i> Вакансии</a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto nav-controls-guest">
                <li class="nav-item">
                    <button class="btn btn-outline-light btn-sm me-2 theme-switcher d-none d-lg-block" data-theme-toggle title="Сменить тему"><i class="fas fa-sun"></i></button>
                </li>
                <li class="nav-item">
                    <a class="btn btn-warning btn-sm fw-bold me-2 cta-btn" href="{{ route('register') }}">
                        <i class="fas fa-plus-circle me-1"></i> Создать заявку
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm me-2 login-btn" href="{{ route('login') }}">
                        <i class="fas fa-sign-in-alt me-1"></i> Войти
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light btn-sm register-btn" href="{{ route('register') }}">
                        <i class="fas fa-user-plus me-1"></i> Регистрация
                    </a>
                </li>
            </ul>
        </div>
        @endauth
    </div>
</nav>

<style>
/* ============================================================
   СТИЛИ НАВБАРА
   ============================================================ */
.main-navbar {
    background: linear-gradient(135deg, #0b5ed7, #0d6efd) !important;
    box-shadow: 0 2px 15px rgba(0,0,0,0.15);
    padding: 0.4rem 1rem;
    min-height: 65px;
    transition: all 0.3s ease;
    z-index: 9999;
    overflow: visible !important;
}

.main-navbar .container-fluid { overflow: visible !important; }

    /* --- Логотип --- */
.navbar-brand img {
    max-height: 40px !important;
    width: auto !important;
}
.navbar-logo-container {
    display: flex; align-items: center; justify-content: center;
    height: 55px; padding: 0; transition: all 0.3s ease;
}
.navbar-logo-img {
    height: 100%; width: auto;
    max-height: 55px;
    max-width: 190px;
    transition: all 0.3s ease; object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}
.navbar-brand:hover .navbar-logo-container { transform: translateY(-2px); }
.navbar-brand:hover .navbar-logo-img { transform: scale(1.03); }
@media (min-width: 1200px) {
    .navbar-logo-container { height: 60px; }
    .navbar-logo-img { max-width: 220px; }
}
@media (max-width: 991.98px) {
    .navbar-logo-container { height: 42px; }
    .navbar-logo-img { max-height: 42px; max-width: 150px; }
}
@media (max-width: 480px) {
    .navbar-logo-container { height: 36px; }
    .navbar-logo-img { max-height: 36px; max-width: 120px; }
}
@media (max-width: 360px) {
    .navbar-logo-container { height: 32px; }
    .navbar-logo-img { max-height: 32px; max-width: 100px; }
}

/* --- Пункты меню --- */
.navbar-nav .nav-link {
    position: relative;
    padding: 0.45rem 0.7rem !important;
    margin: 0 0.05rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.88rem;
    transition: all 0.3s ease;
    color: white !important;
    white-space: nowrap !important;
    display: flex !important;
    align-items: center !important;

    i { font-size: 0.95rem; transition: transform 0.3s ease; margin-right: 0.3rem; flex-shrink: 0; }

    &::before {
        content: '';
        position: absolute;
        bottom: 0; left: 50%;
        width: 0; height: 2px;
        background: linear-gradient(90deg, transparent, #ffffff, transparent);
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }

    &:hover {
        background: rgba(255,255,255,0.15);
        transform: translateY(-1px);
        i { transform: scale(1.1); }
        &::before { width: 60%; }
        color: white !important;
    }

    &.active {
        background: rgba(255,255,255,0.2);
        font-weight: 600;
        &::before { width: 70%; background: #ffffff; }
    }
}

.navbar-nav .nav-item { display: flex !important; align-items: center !important; }

/* --- Выпадающее меню "О нас" --- */
.dropdown-menu-about {
    border: none; border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    padding: 0.4rem 0;
    margin-top: 0.4rem !important;
    min-width: 220px;

    .dropdown-item {
        padding: 0.55rem 1rem;
        margin: 0.1rem 0.4rem;
        border-radius: 6px;
        transition: all 0.3s ease;
        color: #333 !important;
        font-weight: 500;
        font-size: 0.9rem;

        i { width: 18px; text-align: center; margin-right: 0.5rem; color: #0b5ed7; }

        &:hover { background: linear-gradient(135deg, #0b5ed7, #0d6efd); color: white !important; transform: translateX(3px); i { color: white; } }
        &.active { background: rgba(11,94,215,0.1); color: #0b5ed7 !important; font-weight: 600; }
    }
}

/* --- Акцентная кнопка "Создать заявку" --- */
.cta-btn {
    background: linear-gradient(135deg, #ffc107, #ff9800) !important;
    border: none !important;
    color: #1a1a1a !important;
    border-radius: 6px;
    padding: 0.45rem 1rem !important;
    font-weight: 700 !important;
    box-shadow: 0 2px 8px rgba(255,193,7,0.3);
    transition: all 0.3s ease;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255,193,7,0.4);
        color: #000 !important;
    }

    i { margin-right: 0.3rem; }
}

/* --- Кнопки управления --- */
.nav-controls-desktop .nav-link {
    padding: 0.35rem 0.6rem !important; border-radius: 50%; margin: 0;
    &:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px) scale(1.05); }
}
.nav-controls-desktop .theme-switcher {
    width: 34px; height: 34px; border-radius: 50%; padding: 0 !important;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,0.3); transition: all 0.3s ease;
    &:hover { background: rgba(255,255,255,0.2); transform: rotate(15deg) scale(1.1); }
}

/* --- Профиль --- */
.profile-menu {
    border: none; border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15); padding: 0.5rem 0;
    .dropdown-item {
        padding: 0.6rem 1rem; margin: 0 0.3rem; border-radius: 6px;
        transition: all 0.3s ease; color: #333 !important; font-weight: 500;
        i { width: 18px; text-align: center; margin-right: 0.5rem; }
        &:hover { background: linear-gradient(135deg, #0b5ed7, #0d6efd); color: white !important; transform: translateX(3px); }
    }
}

/* --- Кнопки для гостей --- */
.nav-controls-guest {
    .theme-switcher { width: 34px; height: 34px; border-radius: 50%; padding: 0 !important; display: flex; align-items: center; justify-content: center; &:hover { transform: rotate(15deg) scale(1.1); } }
    .login-btn, .register-btn {
        padding: 0.45rem 0.9rem !important; border-radius: 6px; font-weight: 600; font-size: 0.88rem; transition: all 0.3s ease;
        &:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    }
}

/* ============================================================
   АДАПТИВНОСТЬ
   ============================================================ */
@media (max-width: 1199.98px) and (min-width: 992px) {
    .navbar-logo-container { height: 50px; }
    .navbar-logo-img { min-width: 150px; }
}

@media (max-width: 991.98px) {
    .main-navbar { padding: 0.4rem 0.5rem; overflow: visible !important; min-height: 60px; }
    .nav-controls-desktop { display: none !important; }
    .navbar-logo-container { height: 45px; }
    .navbar-logo-img { min-width: 130px; max-width: 170px; }

    .mobile-auth-controls, .mobile-guest-controls {
        display: flex !important; align-items: center !important; gap: 0.4rem !important;
        margin-left: auto !important; position: relative !important; overflow: visible !important;
    }
    .mobile-auth-controls .nav-link { padding: 0.4rem !important; margin: 0; }
    .mobile-auth-controls .btn { padding: 0.3rem 0.5rem !important; }

    .profile-dropdown { position: static !important; }
    .profile-menu-mobile {
        position: fixed !important; top: 55px !important; right: 10px !important;
        left: auto !important; width: 260px !important; max-width: calc(100vw - 20px) !important;
        border: none; border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        .dropdown-item { padding: 0.6rem 1rem; transition: all 0.3s ease;
            &:hover { background: linear-gradient(135deg, #0b5ed7, #0d6efd); color: white !important; }
        }
    }

    .mobile-guest-controls .btn { padding: 0.3rem 0.5rem !important; margin: 0 !important; }
    .mobile-guest-controls .navbar-toggler { border: 1px solid rgba(255,255,255,0.3) !important; padding: 0.2rem 0.4rem !important; }

    #navbarMainContent {
        position: static !important; flex-basis: 100% !important; order: 2 !important;
        background: linear-gradient(135deg, #0b5ed7, #0d6efd) !important;
        border-top: 1px solid rgba(255,255,255,0.2);
        margin: 0.4rem -0.5rem -0.4rem -0.5rem !important;
        padding: 1.2rem 0.8rem !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        max-height: 80vh !important; overflow-y: auto !important;

        &.collapse:not(.show) { display: none !important; height: 0 !important; overflow: hidden !important; }
        &.collapse.show { display: block !important; height: auto !important; animation: slideDown 0.3s ease; }

        .nav-sections { width: 100%; margin-bottom: 1rem;
            .nav-item { width: 100%; margin: 0.2rem 0; }
            .nav-link {
                padding: 0.8rem 1rem !important; margin: 0.1rem 0;
                justify-content: flex-start; width: 100%; border-radius: 8px;
                font-size: 1rem; color: white !important;
                background: rgba(255,255,255,0.08);
                transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1);
                &:hover { background: rgba(255,255,255,0.15); transform: translateX(5px); }
                i { font-size: 1.1rem; width: 24px; margin-right: 0.5rem; }
            }
            /* Стили для dropdown на мобильных */
            .dropdown-menu {
                position: static !important; float: none !important;
                background: rgba(0,0,0,0.15) !important;
                border: 1px solid rgba(255,255,255,0.1) !important;
                border-radius: 8px;
                margin-top: 0.3rem !important;
                padding: 0.3rem !important;
                .dropdown-item {
                    padding: 0.6rem 1rem !important;
                    color: white !important;
                    border-radius: 6px;
                    font-size: 0.9rem;
                    background: transparent !important;
                    &:hover { background: rgba(255,255,255,0.15) !important; transform: translateX(5px); }
                    i { color: rgba(255,255,255,0.7); }
                }
            }
        }

        .nav-controls-guest {
            width: 100%; display: flex; flex-direction: column; gap: 0.6rem;
            border-top: 1px solid rgba(255,255,255,0.3); padding-top: 1rem;
            .nav-item { width: 100%; }
            .theme-switcher { display: none !important; }
            .cta-btn, .login-btn, .register-btn {
                width: 100% !important; justify-content: center !important;
                padding: 0.8rem 1rem !important; margin: 0.1rem 0 !important;
                font-size: 1rem; border-radius: 8px; font-weight: 600;
            }
        }
    }
}

@media (max-width: 576px) {
    .main-navbar { min-height: 55px; }
    .navbar-logo-container { height: 40px; }
    .navbar-logo-img { min-width: 110px; max-width: 150px; }
    .mobile-auth-controls, .mobile-guest-controls { gap: 0.25rem !important; }
    .mobile-auth-controls .btn { padding: 0.25rem 0.4rem !important; }
    .profile-menu-mobile { width: 240px !important; right: 5px !important; }
    .mobile-guest-controls .btn { padding: 0.25rem 0.4rem !important; }
    .mobile-guest-controls .navbar-toggler { padding: 0.15rem 0.35rem !important; }
    #navbarMainContent { padding: 0.8rem 0.6rem !important; max-height: 75vh !important; }
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-15px); max-height: 0; }
    to { opacity: 1; transform: translateY(0); max-height: 80vh; }
}
.collapse.show { animation: slideDown 0.35s ease-out !important; }

body { padding-top: 65px !important; }
@media (max-width: 991.98px) { body { padding-top: 60px !important; } }
@media (max-width: 576px) { body { padding-top: 55px !important; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Бургер сайдбара - используем Bootstrap Offcanvas API
    const sidebarToggle = document.getElementById('sidebarToggleMobile');
    const sidebarOffcanvas = document.getElementById('sidebarOffcanvas');
    if (sidebarToggle && sidebarOffcanvas) {
        // Создаём экземпляр Offcanvas если его нет
        let offcanvas = bootstrap.Offcanvas.getInstance(sidebarOffcanvas);
        if (!offcanvas) {
            offcanvas = new bootstrap.Offcanvas(sidebarOffcanvas);
        }
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            offcanvas.toggle();
        });
    }

    // Закрытие dropdown при ресайзе
    window.addEventListener('resize', function() {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(dropdown) {
            var toggle = dropdown.previousElementSibling;
            if (toggle && bootstrap.Dropdown.getInstance(toggle)) {
                bootstrap.Dropdown.getInstance(toggle).hide();
            }
        });
    });

    // Закрытие мобильного меню при клике вне
    document.addEventListener('click', function(e) {
        var navbar = document.getElementById('navbarMainContent');
        var toggler = document.querySelector('[data-bs-toggle="collapse"][data-bs-target="#navbarMainContent"]');
        if (navbar && toggler && navbar.classList.contains('show') && !navbar.contains(e.target) && !toggler.contains(e.target)) {
            bootstrap.Collapse.getInstance(navbar).hide();
        }
    });
});
</script>
