{{-- resources/views/components/navbar.blade.php --}}
<nav class="navbar navbar-expand-lg navbar-dark fixed-top main-navbar">
    <div class="container-fluid">
       <!-- Логотип -->
        <a class="navbar-brand" href="{{ url('/') }}">
            <div class="navbar-logo-container">
                <img src="{{ asset('images/logo/fap2.svg') }}"
                    alt="ФАП - Федеральная Арендная Платформа"
                    class="navbar-logo-img">
            </div>
        </a>

        @auth
        <!-- ДЛЯ АВТОРИЗОВАННЫХ ПОЛЬЗОВАТЕЛЕЙ -->

        <!-- Десктопная версия - ВСЕ разделы меню + элементы управления -->
        <div class="d-none d-lg-flex align-items-center flex-grow-1">
            <!-- Все разделы меню как у неавторизованных -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/about">
                        <i class="bi bi-buildings me-1"></i>
                        О компании
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/requests">
                        <i class="bi bi-clipboard-check me-1"></i>
                        Заявки
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/catalog">
                        <i class="bi bi-list-ul me-1"></i>
                        Каталог
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/free">
                        <i class="bi bi-check-circle me-1"></i>
                        Свободная
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/repair">
                        <i class="bi bi-tools me-1"></i>
                        Ремонт
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/cooperation">
                        <i class="bi bi-people me-1"></i>
                        Сотрудничество
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/contacts">
                        <i class="bi bi-telephone me-1"></i>
                        Контакты
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/jobs">
                        <i class="bi bi-briefcase me-1"></i>
                        Вакансии
                    </a>
                </li>
            </ul>

            <!-- Элементы управления для авторизованных -->
            <ul class="navbar-nav ms-auto align-items-center nav-controls-desktop">
                <li class="nav-item">
                    <button class="btn btn-outline-light btn-sm theme-switcher" data-theme-toggle
                            title="Сменить тему">
                        <i class="bi bi-sun-fill"></i>
                    </button>
                </li>

                <li class="nav-item">
                    <a class="nav-link position-relative" href="/notifications" title="Уведомления">
                        <i class="bi bi-bell"></i>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>
                </li>

                <!-- Профиль пользователя -->
                <li class="nav-item dropdown profile-dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center profile-toggle" href="#"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false" id="profileDropdown">
                        <div class="user-avatar me-2">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <span class="user-name">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end profile-menu">
                        @if(Auth::user()->isPlatformAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2 me-2"></i> Админ-панель
                            </a></li>
                        @elseif(Auth::user()->company && Auth::user()->company->is_lessor)
                            <li><a class="dropdown-item" href="{{ route('lessor.dashboard') }}">
                                <i class="bi bi-building-gear me-2"></i> Кабинет арендодателя
                            </a></li>
                        @elseif(Auth::user()->company && Auth::user()->company->is_lessee)
                            <li><a class="dropdown-item" href="{{ route('lessee.dashboard') }}">
                                <i class="bi bi-truck me-2"></i> Кабинет арендатора
                            </a></li>
                        @endif
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bi bi-person me-2"></i> Профиль
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2"></i> Выйти
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>

        <!-- Мобильная версия для авторизованных - кнопки управления -->
        <div class="d-flex d-lg-none align-items-center mobile-auth-controls">
            <!-- Уведомления -->
            <a class="nav-link position-relative me-2" href="/notifications" title="Уведомления">
                <i class="bi bi-bell"></i>
                @if(auth()->user()->unreadNotifications->count() > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.5rem;">
                        {{ auth()->user()->unreadNotifications->count() }}
                    </span>
                @endif
            </a>

            <!-- Переключатель темы -->
            <button class="btn btn-outline-light btn-sm me-2 theme-switcher" data-theme-toggle
                    title="Сменить тему">
                <i class="bi bi-sun-fill"></i>
            </button>

            <!-- Профиль пользователя -->
            <div class="nav-item dropdown profile-dropdown me-2">
                <a class="nav-link dropdown-toggle d-flex align-items-center profile-toggle-mobile" href="#"
                   role="button" data-bs-toggle="dropdown" aria-expanded="false" id="profileDropdownMobile">
                    <i class="bi bi-person-circle"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end profile-menu-mobile">
                    @if(Auth::user()->isPlatformAdmin())
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i> Админ-панель
                        </a></li>
                    @elseif(Auth::user()->company && Auth::user()->company->is_lessor)
                        <li><a class="dropdown-item" href="{{ route('lessor.dashboard') }}">
                            <i class="bi bi-building-gear me-2"></i> Кабинет арендодателя
                        </a></li>
                    @elseif(Auth::user()->company && Auth::user()->company->is_lessee)
                        <li><a class="dropdown-item" href="{{ route('lessee.dashboard') }}">
                            <i class="bi bi-truck me-2"></i> Кабинет арендатора
                        </a></li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="bi bi-person me-2"></i> Профиль
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i> Выйти
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            <!-- Бургер для сайдбара -->
            <button class="navbar-toggler border-0" type="button" id="sidebarToggleMobile">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        @else
        <!-- ДЛЯ НЕАВТОРИЗОВАННЫХ ПОЛЬЗОВАТЕЛЕЙ -->

        <!-- Мобильная версия для неавторизованных - кнопки управления -->
        <div class="d-flex d-lg-none align-items-center mobile-guest-controls">
            <!-- Переключатель темы -->
            <button class="btn btn-outline-light btn-sm me-2 theme-switcher" data-theme-toggle
                    title="Сменить тему">
                <i class="bi bi-sun-fill"></i>
            </button>

            <!-- Бургер для основного меню -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMainContent"
                    aria-controls="navbarMainContent" aria-expanded="false" aria-label="Переключить навигацию">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <!-- Основное содержимое меню для неавторизованных -->
        <div class="collapse navbar-collapse" id="navbarMainContent">
            <!-- Все разделы меню -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 nav-sections">
                <li class="nav-item">
                    <a class="nav-link" href="/about">
                        <i class="bi bi-buildings me-1"></i>
                        О компании
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/requests">
                        <i class="bi bi-clipboard-check me-1"></i>
                        Заявки
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/catalog">
                        <i class="bi bi-list-ul me-1"></i>
                        Каталог
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/free">
                        <i class="bi bi-check-circle me-1"></i>
                        Свободная
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/repair">
                        <i class="bi bi-tools me-1"></i>
                        Ремонт
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/cooperation">
                        <i class="bi bi-people me-1"></i>
                        Сотрудничество
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/contacts">
                        <i class="bi bi-telephone me-1"></i>
                        Контакты
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/jobs">
                        <i class="bi bi-briefcase me-1"></i>
                        Вакансии
                    </a>
                </li>
            </ul>

            <!-- Элементы управления для неавторизованных -->
            <ul class="navbar-nav ms-auto nav-controls-guest">
                <li class="nav-item">
                    <button class="btn btn-outline-light btn-sm me-2 theme-switcher d-none d-lg-block" data-theme-toggle
                            title="Сменить тему">
                        <i class="bi bi-sun-fill"></i>
                    </button>
                </li>

                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm me-2 login-btn" href="{{ route('login') }}">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        <span class="btn-text">Войти</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light btn-sm register-btn" href="{{ route('register') }}">
                        <i class="bi bi-person-plus me-1"></i>
                        <span class="btn-text">Регистрация</span>
                    </a>
                </li>
            </ul>
        </div>
        @endauth
    </div>
</nav>

<style>

/* ТОЛЬКО КРИТИЧЕСКИЕ СТИЛИ ДЛЯ НАВБАРА */
.main-navbar {
    background: linear-gradient(135deg, #0b5ed7, #0d6efd) !important;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 0.5rem 1rem;
    min-height: 70px;
    transition: all 0.3s ease;
    z-index: 9999;
    overflow: visible !important;
}

/* ФИКС: Убираем overflow у контейнеров */
.main-navbar .container-fluid {
    overflow: visible !important;
}

/* КРИТИЧЕСКИЙ ФИКС ДЛЯ DROPDOWN */
.profile-dropdown {
    position: relative;
}

.profile-menu {
    position: absolute !important;
    top: 100% !important;
    left: auto !important;
    right: 0 !important;
    z-index: 10050 !important;
    margin-top: 0.5rem !important;
}

.profile-menu-mobile {
    position: absolute !important;
    top: 100% !important;
    left: auto !important;
    right: 0 !important;
    z-index: 10050 !important;
    margin-top: 0.5rem !important;
}

/* НОВЫЙ УВЕЛИЧЕННЫЙ ЛОГОТИП SVG */
.navbar-logo-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 60px; /* Увеличенная высота */
    padding: 0;
    transition: all 0.3s ease;
}

.navbar-logo-img {
    height: 100%;
    width: auto;
    min-width: 180px; /* Минимальная ширина */
    transition: all 0.3s ease;
    object-fit: contain;
    /* Добавляем тень для лучшей видимости */
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.navbar-brand:hover .navbar-logo-container {
    transform: translateY(-2px);
}

.navbar-brand:hover .navbar-logo-img {
    transform: scale(1.03);
}

/* Десктопные устройства (большие экраны) */
@media (min-width: 1200px) {
    .navbar-logo-container {
        height: 65px; /* Еще больше на больших экранах */
    }

    .navbar-logo-img {
        min-width: 200px;
    }
}

/* УЛУЧШЕННЫЕ РАЗДЕЛЫ МЕНЮ - КОМПАКТНЫЕ */
.navbar-nav .nav-link {
    position: relative;
    padding: 0.5rem 0.8rem !important;
    margin: 0 0.1rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    color: white !important;
    white-space: nowrap !important;
    display: flex !important;
    align-items: center !important;

    i {
        font-size: 1rem;
        transition: transform 0.3s ease;
        margin-right: 0.4rem;
        flex-shrink: 0;
    }

    &::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, #ffffff, transparent);
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }

    &:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);

        i {
            transform: scale(1.1);
        }

        &::before {
            width: 70%;
        }

        color: white !important;
        text-shadow: 0 0 8px rgba(255,255,255,0.5);
    }
}

/* Гарантия что все пункты меню одинаковой высоты */
.navbar-nav .nav-item {
    display: flex !important;
    align-items: center !important;
}

/* УЛУЧШЕННЫЕ КНОПКИ УПРАВЛЕНИЯ */
.nav-controls-desktop {
    .nav-link {
        padding: 0.4rem 0.7rem !important;
        border-radius: 50%;
        margin: 0 0.1rem;

        &:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px) scale(1.05);
        }
    }

    .theme-switcher {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;

        &:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(15deg) scale(1.1);
        }
    }
}

/* УЛУЧШЕННЫЙ DROPDOWN ПРОФИЛЯ */
.profile-menu {
    border: none;
    border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    padding: 0.5rem 0;
    margin-top: 0.5rem !important;

    .dropdown-item {
        padding: 0.7rem 1rem;
        margin: 0 0.3rem;
        border-radius: 6px;
        transition: all 0.3s ease;
        color: #333 !important;
        font-weight: 500;

        i {
            width: 18px;
            text-align: center;
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        &:hover {
            background: linear-gradient(135deg, #0b5ed7, #0d6efd);
            color: white !important;
            transform: translateX(3px);
        }
    }
}

/* УЛУЧШЕННЫЕ КНОПКИ ДЛЯ НЕАВТОРИЗОВАННЫХ */
.nav-controls-guest {
    .theme-switcher {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;

        &:hover {
            transform: rotate(15deg) scale(1.1);
        }
    }

    .login-btn, .register-btn {
        padding: 0.5rem 1rem !important;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s ease;

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    }
}

/* МОБИЛЬНЫЕ УСТРОЙСТВА */
@media (max-width: 1199.98px) and (min-width: 992px) {
    .navbar-logo-container {
        height: 55px;
    }

    .navbar-logo-img {
        min-width: 160px;
    }
}

@media (max-width: 991.98px) {
    .main-navbar {
        padding: 0.5rem;
        overflow: visible !important;
    }

    .nav-controls-desktop {
        display: none !important;
    }

    .navbar-logo-container {
        height: 50px; /* Сохраняем хороший размер на мобильных */
    }

    .navbar-logo-img {
        min-width: 140px;
        max-width: 180px; /* Ограничиваем ширину на мобильных */
    }

    /* Скрываем десктопные элементы на мобильных */
    .mobile-auth-controls,
    .mobile-guest-controls {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        margin-left: auto !important;
        position: relative !important;
        overflow: visible !important;
    }

    .mobile-auth-controls .nav-link {
        padding: 0.5rem !important;
        margin: 0;
    }

    .mobile-auth-controls .btn {
        padding: 0.4rem 0.6rem !important;
    }

    /* ФИКС: Dropdown для мобильных */
    .profile-dropdown {
        position: static !important;
    }

    .profile-menu-mobile {
        position: fixed !important;
        top: 60px !important;
        right: 10px !important;
        left: auto !important;
        width: 280px !important;
        max-width: calc(100vw - 20px) !important;
        margin-top: 0 !important;
    }

    /* Мобильные кнопки для неавторизованных */
    .mobile-guest-controls {
        .btn {
            padding: 0.4rem 0.6rem !important;
            margin: 0 !important;
        }

        .navbar-toggler {
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            padding: 0.25rem 0.5rem !important;
            margin: 0 !important;
        }
    }

    /* Мобильное меню для неавторизованных */
    #navbarMainContent {
        position: static !important;
        flex-basis: 100% !important;
        order: 2 !important;
        background: linear-gradient(135deg, #0b5ed7, #0d6efd) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        margin: 0.5rem -0.5rem -0.5rem -0.5rem !important;
        padding: 1.5rem 1rem !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        min-height: auto !important;
        height: auto !important;
        max-height: 80vh !important;
        overflow-y: auto !important;

        &.collapse:not(.show) {
            display: none !important;
            height: 0 !important;
            overflow: hidden !important;
        }

        &.collapse.show {
            display: block !important;
            height: auto !important;
            animation: slideDown 0.3s ease;
        }

        .nav-sections {
            width: 100%;
            margin-bottom: 1.5rem;

            .nav-item {
                width: 100%;
                margin: 0.3rem 0;
            }

            .nav-link {
                padding: 1rem 1.2rem !important;
                margin: 0.2rem 0;
                justify-content: flex-start;
                width: 100%;
                border-radius: 10px;
                font-size: 1.1rem;
                color: white !important;
                background: rgba(255, 255, 255, 0.08);
                transition: all 0.3s ease;
                border: 1px solid rgba(255, 255, 255, 0.1);

                &:hover {
                    background: rgba(255, 255, 255, 0.2);
                    transform: translateX(8px);
                    border-color: rgba(255, 255, 255, 0.3);
                }

                i {
                    font-size: 1.3rem;
                    width: 28px;
                    margin-right: 0.5rem;
                }
            }
        }

        .nav-controls-guest {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 1.5rem;

            .nav-item {
                width: 100%;
            }

            .theme-switcher {
                display: none !important;
            }

            .login-btn,
            .register-btn {
                width: 100% !important;
                justify-content: center !important;
                padding: 1rem 1.2rem !important;
                margin: 0.2rem 0 !important;
                font-size: 1.1rem;
                border-radius: 10px;
                font-weight: 600;
                transition: all 0.3s ease;
            }
        }
    }
}

/* Маленькие мобильные */
@media (max-width: 576px) {
    .main-navbar {
        min-height: 60px;
    }

    .navbar-logo-container {
        height: 45px; /* Чуть меньше на очень маленьких экранах */
    }

    .navbar-logo-img {
        min-width: 120px;
        max-width: 160px;
    }

    .mobile-auth-controls,
    .mobile-guest-controls {
        gap: 0.3rem !important;
    }

    .mobile-auth-controls .btn {
        padding: 0.3rem 0.5rem !important;
    }

    .profile-menu-mobile {
        width: 260px !important;
        right: 5px !important;
    }

    .mobile-guest-controls .btn {
        padding: 0.3rem 0.5rem !important;
    }

    .mobile-guest-controls .navbar-toggler {
        padding: 0.2rem 0.4rem !important;
    }

    #navbarMainContent {
        padding: 1rem 0.8rem !important;
        margin: 0.5rem -0.5rem -0.5rem -0.5rem !important;
        max-height: 75vh !important;

        .nav-sections .nav-link {
            padding: 0.9rem 1rem !important;
            font-size: 1rem;
        }

        .nav-controls-guest .login-btn,
        .nav-controls-guest .register-btn {
            padding: 0.9rem 1rem !important;
            font-size: 1rem;
        }
    }
}

/* Анимация появления меню */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
        max-height: 0;
    }
    to {
        opacity: 1;
        transform: translateY(0);
        max-height: 80vh;
    }
}

.collapse.show {
    animation: slideDown 0.4s ease-out !important;
}

/* Фикс для body padding */
body {
    padding-top: 70px !important;
}

@media (max-width: 991.98px) {
    body {
        padding-top: 60px !important;
    }
}

/* Стили для dropdown меню */
.dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    color: #333 !important;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: #0b5ed7 !important;
    color: white !important;
}

.dropdown-divider {
    margin: 0.5rem 0;
}

/* ФИКС для активного состояния */
.navbar-nav .nav-link.active {
    background: rgba(255, 255, 255, 0.2);
    font-weight: 600;
    color: white !important;

    &::before {
        width: 80%;
        background: #ffffff;
    }
}
</style>
<script>
// СКРИПТ ДЛЯ РАБОТЫ НАВБАРА
document.addEventListener('DOMContentLoaded', function() {
    // Кнопка сайдбара для авторизованных на мобильных
    const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
    const sidebar = document.getElementById('sidebarContainer');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggleMobile && sidebar) {
        sidebarToggleMobile.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('📱 Открываем сайдбар для авторизованного пользователя');
            sidebar.classList.add('mobile-open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    // ФИКС: Принудительно показываем dropdown поверх всего
    document.addEventListener('show.bs.dropdown', function(e) {
        const dropdownMenu = e.target.querySelector('.dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.style.zIndex = '10050';
        }
    });

    // ФИКС: Закрытие dropdown при ресайзе
    function closeDropdownsOnResize() {
        const dropdowns = document.querySelectorAll('.dropdown-menu.show');
        dropdowns.forEach(dropdown => {
            const dropdownToggle = dropdown.previousElementSibling;
            if (dropdownToggle && bootstrap.Dropdown.getInstance(dropdownToggle)) {
                bootstrap.Dropdown.getInstance(dropdownToggle).hide();
            }
        });
    }

    window.addEventListener('resize', closeDropdownsOnResize);
});
</script>
