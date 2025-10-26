{{-- resources/views/components/navbar.blade.php --}}
<nav class="navbar navbar-expand-lg navbar-dark fixed-top main-navbar">
    <div class="container-fluid">
        <!-- Логотип -->
        <a class="navbar-brand" href="{{ url('/') }}">
            <div class="d-flex align-items-center">
                <div class="navbar-logo-icon me-2">
                    <i class="bi bi-building-fill"></i>
                </div>
                <div class="navbar-logo-text">
                    <div class="logo-main">ФАП</div>
                    <div class="logo-subtitle">Федеральная Арендная Платформа</div>
                </div>
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

        <!-- Мобильная версия для авторизованных - только элементы управления -->
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

            <!-- Профиль пользователя - ИСПРАВЛЕННЫЙ DROPDOWN -->
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

        <!-- Кнопка бургер меню для мобильных -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMainContent"
                aria-controls="navbarMainContent" aria-expanded="false" aria-label="Переключить навигацию">
            <span class="navbar-toggler-icon"></span>
        </button>

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
                    <button class="btn btn-outline-light btn-sm me-2 theme-switcher" data-theme-toggle
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
/* ОСНОВНЫЕ СТИЛИ НАВБАРА */
.main-navbar {
    background: linear-gradient(135deg, #0b5ed7, #0d6efd) !important;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 0.5rem 1rem;
    min-height: 70px;
    transition: all 0.3s ease;
    z-index: 9999;
    /* Гарантируем, что навбар не ограничивает дочерние элементы */
    overflow: visible !important;
}

/* КРИТИЧЕСКИЙ ФИКС ДЛЯ DROPDOWN ПРОФИЛЯ - ДЕСКТОП */
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
    min-width: 220px;
    background: white !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
    animation: dropdownAppear 0.2s ease-out !important;
}

/* КРИТИЧЕСКИЙ ФИКС ДЛЯ DROPDOWN ПРОФИЛЯ - МОБИЛЬНЫЕ */
.profile-menu-mobile {
    position: absolute !important;
    top: 100% !important;
    left: auto !important;
    right: 0 !important;
    z-index: 10050 !important;
    margin-top: 0.5rem !important;
    min-width: 220px;
    background: white !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
    animation: dropdownAppear 0.2s ease-out !important;
}

@keyframes dropdownAppear {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Логотип */
.navbar-brand {
    padding: 0;
    margin-right: 2rem;
}

.navbar-logo-icon {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.navbar-logo-icon i {
    font-size: 1.5rem;
    color: white;
}

.navbar-brand:hover .navbar-logo-icon {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px) rotate(-5deg);
}

.navbar-logo-text {
    display: flex;
    flex-direction: column;
}

.logo-main {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
    line-height: 1;
    letter-spacing: 0.5px;
}

.logo-subtitle {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.1;
    margin-top: 0.1rem;
}

/* ОБЩИЕ СТИЛИ ДЛЯ ВСЕХ ЭЛЕМЕНТОВ */
.navbar-nav .nav-link {
    color: white !important;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    white-space: nowrap;
}

.navbar-nav .nav-link:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

/* Переключатель темы */
.theme-switcher {
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    padding: 0.4rem 0.6rem !important;
    color: white !important;
    transition: all 0.3s ease;
}

.theme-switcher:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    transform: scale(1.05);
}

/* Кнопки входа/регистрации */
.login-btn, .register-btn {
    padding: 0.5rem 1rem !important;
    display: flex !important;
    align-items: center !important;
    transition: all 0.3s ease !important;
    white-space: nowrap !important;
}

.login-btn {
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: white !important;
}

.login-btn:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    transform: translateY(-1px);
}

.register-btn {
    background: rgba(255, 255, 255, 0.9) !important;
    color: #0b5ed7 !important;
    font-weight: 600 !important;
}

.register-btn:hover {
    background: white !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Аватар пользователя */
.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-avatar i {
    font-size: 1.2rem;
    color: white;
}

.user-name {
    color: white;
    font-weight: 500;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Бейдж уведомлений */
.navbar .badge {
    font-size: 0.6rem;
    padding: 0.2rem 0.4rem;
}

/* АДАПТИВНОСТЬ */

/* Десктоп (большие экраны) */
@media (min-width: 992px) {
    .mobile-auth-controls {
        display: none !important;
    }

    .nav-controls-desktop {
        display: flex !important;
        gap: 0.8rem;
        align-items: center;
    }

    /* Компактное меню для десктопа */
    .navbar-nav .nav-link {
        padding: 0.5rem 0.8rem !important;
        font-size: 0.9rem;
    }
}

/* МОБИЛЬНЫЕ УСТРОЙСТВА - КЛЮЧЕВЫЕ ИСПРАВЛЕНИЯ */
@media (max-width: 991.98px) {
    .main-navbar {
        padding: 0.5rem;
        /* Гарантируем, что навбар не создает скроллбар */
        overflow: visible !important;
    }

    .nav-controls-desktop {
        display: none !important;
    }

    .mobile-auth-controls {
        display: flex !important;
        align-items: center;
        gap: 0.5rem;
        /* Важно: гарантируем, что контейнер не ограничивает dropdown */
        position: relative;
        overflow: visible !important;
    }

    .mobile-auth-controls .nav-link {
        padding: 0.5rem !important;
        margin: 0;
    }

    .mobile-auth-controls .btn {
        padding: 0.4rem 0.6rem !important;
    }

    /* ОСНОВНОЕ ИСПРАВЛЕНИЕ: dropdown профиля в мобильной версии */
    .profile-dropdown {
        position: static !important; /* Изменяем на static чтобы dropdown позиционировался относительно viewport */
    }

    .profile-menu-mobile {
        position: fixed !important; /* Используем fixed вместо absolute */
        top: 60px !important; /* Отступ от верха экрана */
        right: 10px !important; /* Отступ от правого края */
        left: auto !important;
        transform: none !important;
        width: 280px !important;
        max-width: calc(100vw - 20px) !important;
        z-index: 10050 !important;
        margin-top: 0 !important;
    }

    /* Стили для collapse меню неавторизованных */
    .navbar-collapse {
        background: linear-gradient(135deg, #0b5ed7, #0d6efd);
        border-radius: 0 0 10px 10px;
        padding: 1rem;
        margin-top: 0.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        z-index: 9998;
        /* Гарантируем, что collapse не ограничивает контент */
        overflow: visible !important;
    }

    .nav-sections {
        width: 100%;
        margin-bottom: 1rem;
    }

    .nav-sections .nav-item {
        width: 100%;
        margin: 0.2rem 0;
    }

    .nav-sections .nav-link {
        padding: 0.8rem 1rem !important;
        margin: 0.1rem 0;
        justify-content: flex-start;
        width: 100%;
        border-radius: 8px;
        font-size: 1rem;
    }

    .nav-controls-guest {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        border-top: 1px solid rgba(255,255,255,0.2);
        padding-top: 1rem;
    }

    .nav-controls-guest .nav-item {
        width: 100%;
    }

    .nav-controls-guest .theme-switcher,
    .nav-controls-guest .login-btn,
    .nav-controls-guest .register-btn {
        width: 100% !important;
        justify-content: center !important;
        padding: 0.8rem 1rem !important;
        margin: 0.1rem 0 !important;
        font-size: 1rem;
    }

    .btn-text {
        display: inline !important;
    }

    .logo-main {
        font-size: 1.3rem;
    }

    .logo-subtitle {
        display: none;
    }

    .user-name {
        display: none;
    }

    .navbar-toggler {
        border: 1px solid rgba(255, 255, 255, 0.3);
        padding: 0.25rem 0.5rem;
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }
}

/* Маленькие мобильные */
@media (max-width: 576px) {
    .main-navbar {
        min-height: 60px;
    }

    .logo-main {
        font-size: 1.2rem;
    }

    .navbar-logo-icon {
        padding: 0.3rem;
    }

    .navbar-logo-icon i {
        font-size: 1.2rem;
    }

    .navbar-brand {
        margin-right: 1rem;
    }

    .mobile-auth-controls {
        gap: 0.3rem;
    }

    .mobile-auth-controls .btn {
        padding: 0.3rem 0.5rem !important;
    }

    /* Адаптируем dropdown для очень маленьких экранов */
    .profile-menu-mobile {
        width: 260px !important;
        right: 5px !important;
        max-width: calc(100vw - 10px) !important;
    }
}

/* Гарантия что навбар всегда виден на мобильных */
@media (max-width: 991.98px) {
    .main-navbar {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        transform: translateY(0) !important;
        opacity: 1 !important;
        visibility: visible !important;
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

/* Фикс для body padding */
body {
    padding-top: 70px !important;
}

@media (max-width: 991.98px) {
    body {
        padding-top: 60px !important;
    }
}

/* Анимация появления */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.navbar-collapse.show {
    animation: slideDown 0.3s ease;
}
</style>

<script>
// Скрипт для работы навбара
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

    // Гарантия что навбар всегда виден на мобильных
    if (window.innerWidth < 992) {
        const navbar = document.querySelector('.main-navbar');
        if (navbar) {
            navbar.classList.remove('navbar--hidden');
        }
    }

    // ФИКС: Принудительно показываем dropdown поверх всего
    document.addEventListener('show.bs.dropdown', function(e) {
        const dropdownMenu = e.target.querySelector('.dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.style.zIndex = '10050';
        }
    });
});

// Обработчик ресайза
window.addEventListener('resize', function() {
    if (window.innerWidth < 992) {
        const navbar = document.querySelector('.main-navbar');
        if (navbar) {
            navbar.classList.remove('navbar--hidden');
        }
    }
});
</script>
