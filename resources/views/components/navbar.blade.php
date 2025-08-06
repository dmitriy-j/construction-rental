<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid px-3 px-lg-4 navbar-container">
        <!-- Логотип -->
        <a class="navbar-brand p-0" href="{{ url('/') }}">
            <div class="logo-container ripple" data-tooltip="Главная страница">
                <div class="logo-main">ФАП</div>
                <div class="logo-subtitle">Федеральная Арендная Платформа</div>
            </div>
        </a>

        <!-- Кнопка мобильного меню -->
        @auth
        <div class="d-flex d-lg-none ms-auto">
            <button class="nav-link ripple mx-2" id="mobileSidebarToggler">
                <i class="bi bi-list"></i>
                <span class="ms-1 d-none d-sm-inline">Меню</span>
            </button>
            <button class="navbar-toggler border-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        @endauth

        <!-- Основное меню -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav main-menu">
                <li class="nav-item">
                    <a class="nav-link ripple" href="/about">
                        <i class="bi bi-buildings"></i>
                        <span>О компании</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link ripple" href="/requests">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Заявки</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link ripple" href="/catalog">
                        <i class="bi bi-list-ul"></i>
                        <span>Каталог</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link ripple" href="/free">
                        <i class="bi bi-check-circle"></i>
                        <span>Свободная</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link ripple" href="/repair">
                        <i class="bi bi-tools"></i>
                        <span>Ремонт</span>
                    </a>
                </li>
            </ul>

            <!-- Правая часть меню -->
            <ul class="navbar-nav right-menu ms-auto">
                <li class="nav-item">
                    <a class="nav-link ripple" href="/cooperation">
                        <i class="bi bi-people"></i>
                        <span>Сотрудничество</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link ripple" href="/contacts">
                        <i class="bi bi-telephone"></i>
                        <span>Контакты</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link ripple" href="/jobs">
                        <i class="bi bi-briefcase"></i>
                        <span>Вакансии</span>
                    </a>
                </li>

                <!-- Переключатель темы -->
                <li class="nav-item d-none d-lg-flex align-items-center ms-2">
                    <button class="theme-switcher ripple" data-theme-toggle>
                        <i class="bi bi-sun-fill"></i>
                    </button>
                </li>

                <!-- Аутентификация -->
                @auth
                <li class="nav-item dropdown ms-lg-1 user-dropdown"> <!-- Добавлен класс user-dropdown -->
                    <a class="nav-link dropdown-toggle d-flex align-items-center ripple" href="#"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-2"></i>
                        <span class="d-none d-md-inline position-relative">
                            {{ Auth::user()->name }}
                            <span class="notification-badge">3</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end"> <!-- Оставлен класс dropdown-menu-end -->
                        <!-- Обернули содержимое в div для управления шириной -->
                        <div class="dropdown-content" style="min-width: 220px;">
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

                            <li><a class="dropdown-item position-relative" href="#">
                                <i class="bi bi-bell me-2"></i> Уведомления
                                <span class="position-absolute end-0 me-2 badge bg-danger rounded-pill">3</span>
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
                        </div>
                    </ul>
                </li>
                @else
                <li class="nav-item">
                    <a class="nav-link btn-auth ripple" href="{{ route('register') }}">
                        <i class="bi bi-person-plus"></i>
                        <span>Регистрация</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-auth ripple" href="{{ route('login') }}">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Войти</span>
                    </a>
                </li>
                @endauth
            </ul>

            <!-- Мобильный переключатель темы -->
            <div class="d-lg-none w-100 text-center mt-2">
                <button class="theme-switcher ripple" data-theme-toggle>
                    <i class="bi bi-sun-fill"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Ripple эффект -->
<div class="ripple-animation-container"></div>
