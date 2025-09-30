{{-- resources/views/partials/sidebar.blade.php --}}
@auth
@php
    // Безопасные счетчики
    $unreadNotificationsCount = auth()->user()->unreadNotifications->count();

    try {
        $cartItemsCount = App\Services\CartService::getCartItemsCount(auth()->user());
    } catch (Exception $e) {
        $cartItemsCount = 0;
    }

    try {
        $newProposalsCount = App\Services\ProposalManagementService::getNewProposalsCount(auth()->user());
    } catch (Exception $e) {
        $newProposalsCount = 0;
    }

    try {
        $newRentalRequestsCount = \App\Services\RequestMatchingService::getNewRequestsCount(auth()->user());
    } catch (\Exception $e) {
        $newRentalRequestsCount = 0;
    }
@endphp

<style>
/* ГАРАНТИРОВАННЫЕ СТИЛИ ДЛЯ САЙДБАРА */
#sidebarContainer {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: fixed !important;
    top: 80px !important;
    left: 0 !important;
    width: 280px !important;
    height: calc(100vh - 80px) !important;
    z-index: 1000 !important;
    background: #f8f9fa !important;
    border-right: 1px solid #dee2e6 !important;
    overflow-y: auto !important;
    transition: width 0.3s ease !important;
}

/* Минимизированный режим */
.sidebar-mini #sidebarContainer {
    width: 80px !important;
}

/* Контент должен смещаться */
.content-area {
    margin-left: 280px !important;
    transition: margin-left 0.3s ease !important;
}

.sidebar-mini .content-area {
    margin-left: 80px !important;
}

/* Мобильная адаптация */
@media (max-width: 992px) {
    #sidebarContainer {
        transform: translateX(-100%) !important;
        width: 280px !important;
    }

    #sidebarContainer.mobile-open {
        transform: translateX(0) !important;
    }

    .content-area {
        margin-left: 0 !important;
    }
}

/* Стили для анимации стрелки */
.rotate-180 {
    transform: rotate(180deg);
    transition: transform 0.3s ease;
}
</style>

<script>
// ПРОСТОЙ СКРИПТ ДЛЯ МИНИМИЗАЦИИ САЙДБАРА
document.addEventListener('DOMContentLoaded', function() {
    const sidebarMinify = document.getElementById('sidebarMinify');
    if (sidebarMinify) {
        sidebarMinify.addEventListener('click', () => {
            const isMini = localStorage.getItem('sidebarMini') === 'true';
            const newState = !isMini;
            localStorage.setItem('sidebarMini', newState);

            // Обновляем CSS переменные
            document.documentElement.style.setProperty(
                '--sidebar-width',
                newState ? 'var(--sidebar-mini-width)' : 'var(--sidebar-width)'
            );

            // Обновляем иконку стрелки
            const icon = sidebarMinify.querySelector('i');
            if (newState) {
                icon.classList.add('rotate-180');
            } else {
                icon.classList.remove('rotate-180');
            }
        });

        // Восстанавливаем состояние при загрузке
        const isMini = localStorage.getItem('sidebarMini') === 'true';
        if (isMini) {
            document.documentElement.style.setProperty(
                '--sidebar-width',
                'var(--sidebar-mini-width)'
            );
            const icon = sidebarMinify.querySelector('i');
            icon.classList.add('rotate-180');
        }
    }
});
</script>

<aside class="sidebar-container" id="sidebarContainer">
    <div class="user-profile-card">
        <div class="avatar-container">
            <div class="avatar">
                @if(Auth::user()->profile_photo_path)
                    <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="profile-avatar">
                @else
                    <i class="bi bi-person-circle"></i>
                @endif
            </div>
            <div class="user-info">
                <div class="user-name">{{ Auth::user()->name }}</div>
                <div class="user-role">
                    @if(Auth::user()->isPlatformAdmin())
                        <i class="bi bi-shield-check"></i> Администратор
                    @elseif(Auth::user()->company && Auth::user()->company->is_lessor)
                        <i class="bi bi-building"></i> Арендодатель
                    @elseif(Auth::user()->company && Auth::user()->company->is_lessee)
                        <i class="bi bi-truck"></i> Арендатор
                    @else
                        <i class="bi bi-person"></i> Пользователь
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="sidebar-controls">
        <button class="sidebar-collapse-btn ripple d-lg-none" id="sidebarCollapse" aria-label="Закрыть меню">
            <i class="bi bi-x-lg"></i>
        </button>
        <button class="sidebar-minify-btn ripple d-none d-lg-block" id="sidebarMinify" aria-label="Свернуть меню">
            <i class="bi bi-chevron-left transition-transform"></i>
        </button>
    </div>

    <nav class="sidebar-navigation">
        <div class="section-header">
            <i class="bi bi-menu-button-wide sidebar-section-icon"></i>
            <h4 class="sidebar-section-title">Основное меню</h4>
        </div>
        <ul class="nav-menu">
            @if(auth()->check() && auth()->user()->company)
                @if(auth()->user()->company->is_lessor)
                    <!-- Меню для арендодателя -->
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessor/dashboard') ? 'active' : '' }}"
                        href="{{ route('lessor.dashboard') }}"
                        data-tooltip="Главная">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <span class="nav-text">Главная</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessor/balance*') ? 'active' : '' }}"
                        href="{{ route('lessor.balance.index') }}"
                        data-tooltip="Баланс">
                            <i class="nav-icon bi bi-wallet2"></i>
                            <span class="nav-text">Баланс</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessor/equipment*') ? 'active' : '' }}" href="{{ route('lessor.equipment.index') }}" data-tooltip="Моя техника">
                            <i class="nav-icon bi bi-wrench-adjustable-circle"></i>
                            <span class="nav-text">Моя техника</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessor/operators*') ? 'active' : '' }}" href="{{ route('lessor.operators.index') }}" data-tooltip="Операторы">
                            <i class="nav-icon bi bi-people"></i>
                            <span class="nav-text">Операторы</span>
                        </a>
                    </li>

                    @php
                        $newOrdersCount = $newOrdersCount ?? 0;
                    @endphp

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessor/orders*') ? 'active' : '' }}"
                        href="{{ route('lessor.orders.index') }}"
                        data-tooltip="Заказы">
                            <i class="nav-icon bi bi-list-task"></i>
                            <span class="nav-text">Заказы</span>
                            @if($newOrdersCount > 0)
                                <span class="badge bg-primary rounded-pill pulse">{{ $newOrdersCount }}</span>
                            @endif
                        </a>
                    </li>

                    <!-- НОВЫЙ ПУНКТ: Заявки на аренду для арендодателя -->
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessor/rental-requests*') ? 'active' : '' }}"
                           href="{{ route('lessor.rental-requests.index') }}"
                           data-tooltip="Заявки на аренду">
                            <i class="nav-icon bi bi-search-heart"></i>
                            <span class="nav-text">Заявки на аренду</span>
                            @if($newRentalRequestsCount > 0)
                                <span class="badge bg-success rounded-pill pulse">{{ $newRentalRequestsCount }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"
                        data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="nav-icon">
                                <i class="bi bi-files"></i>
                            </div>
                            <span class="nav-text">Документы</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="documentsDropdown">
                            <li><a class="dropdown-item {{ Request::is('lessor/documents/contracts') ? 'active' : '' }}" href="{{ route('lessor.documents.index', ['type' => 'contracts']) }}">Договоры</a></li>
                            <li><a class="dropdown-item {{ Request::is('lessor/documents/waybills') ? 'active' : '' }}" href="{{ route('lessor.documents.index', ['type' => 'waybills']) }}">Путевые листы</a></li>
                            <li><a class="dropdown-item {{ Request::is('lessor/documents/delivery_notes') ? 'active' : '' }}" href="{{ route('lessor.documents.index', ['type' => 'delivery_notes']) }}">Накладные</a></li>
                            <li><a class="dropdown-item {{ Request::is('lessor/documents/completion_acts') ? 'active' : '' }}" href="{{ route('lessor.documents.index', ['type' => 'completion_acts']) }}">Акты выполненных работ</a></li>
                            <li>
                                <a class="dropdown-item {{ Request::is('lessor/upds*') ? 'active' : '' }}" href="{{ route('lessor.upds.index') }}">
                                    <i class="bi bi-receipt me-2"></i> УПД
                                    @if(isset($pendingUpdsCount) && $pendingUpdsCount > 0)
                                        <span class="badge bg-warning float-end">{{ $pendingUpdsCount }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>
                @else
                    <!-- Меню для арендатора -->
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessee/dashboard') ? 'active' : '' }}" href="{{ route('lessee.dashboard') }}" data-tooltip="Главная">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <span class="nav-text">Главная</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessee/balance*') ? 'active' : '' }}"
                        href="{{ route('lessee.balance.index') }}"
                        data-tooltip="Баланс">
                            <i class="nav-icon bi bi-wallet2"></i>
                            <span class="nav-text">Баланс</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('catalog*') ? 'active' : '' }}" href="{{ route('catalog.index') }}" data-tooltip="Каталог техники">
                            <i class="nav-icon bi bi-search"></i>
                            <span class="nav-text">Каталог техники</span>
                        </a>
                    </li>

                    <!-- НОВЫЙ ПУНКТ: Заявки на аренду для арендатора -->
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessee/rental-requests*') ? 'active' : '' }}"
                           href="{{ route('lessee.rental-requests.index') }}"
                           data-tooltip="Мои заявки">
                            <i class="nav-icon bi bi-clipboard-plus"></i>
                            <span class="nav-text">Мои заявки</span>
                            @if($newProposalsCount > 0)
                                <span class="badge bg-success rounded-pill pulse">{{ $newProposalsCount }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessee/cart*') ? 'active' : '' }}" href="{{ route('cart.index') }}" data-tooltip="Корзина">
                            <i class="nav-icon bi bi-cart"></i>
                            <span class="nav-text">Корзина</span>
                            @if($cartItemsCount > 0)
                                <span class="badge bg-primary rounded-pill pulse">{{ $cartItemsCount }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('lessee/orders*') ? 'active' : '' }}" href="{{ route('lessee.orders.index') }}" data-tooltip="Мои заказы">
                            <i class="nav-icon bi bi-list-task"></i>
                            <span class="nav-text">Мои заказы</span>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"
                        data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="nav-icon">
                                <i class="bi bi-files"></i>
                            </div>
                            <span class="nav-text">Документы</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="documentsDropdown">
                            <li><a class="dropdown-item {{ Request::is('lessee/documents*') && request('type') === 'contracts' ? 'active' : '' }}" href="{{ route('documents.index', ['type' => 'contracts']) }}">Договоры</a></li>
                            <li><a class="dropdown-item {{ Request::is('lessee/documents*') && request('type') === 'waybills' ? 'active' : '' }}" href="{{ route('documents.index', ['type' => 'waybills']) }}">Путевые листы</a></li>
                            <li><a class="dropdown-item {{ Request::is('lessee/documents*') && request('type') === 'delivery_notes' ? 'active' : '' }}" href="{{ route('documents.index', ['type' => 'delivery_notes']) }}">Накладные</a></li>
                            <li><a class="dropdown-item {{ Request::is('lessee/documents*') && request('type') === 'completion_acts' ? 'active' : '' }}" href="{{ route('documents.index', ['type' => 'completion_acts']) }}">Акты выполненных работ</a></li>
                        </ul>
                    </li>
                @endif
            @endif

            <!-- Общие пункты для всех пользователей -->
            <li class="nav-item">
                <a class="nav-link {{ Request::is('profile') ? 'active' : '' }}" href="{{ route('profile.edit') }}" data-tooltip="Профиль">
                    <i class="nav-icon bi bi-person"></i>
                    <span class="nav-text">Профиль</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ Request::is('notifications') ? 'active' : '' }}" href="{{ route('notifications') }}" data-tooltip="Уведомления">
                    <i class="nav-icon bi bi-bell"></i>
                    <span class="nav-text">Уведомления</span>
                    @if($unreadNotificationsCount > 0)
                        <span class="badge bg-danger rounded-pill pulse">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>
            </li>

            <!-- Админ панель (только для администраторов) -->
            @if(auth()->user()->isPlatformAdmin())
                <div class="section-header mt-4">
                    <i class="bi bi-shield-lock"></i>
                    <h4>Администрирование</h4>
                </div>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" data-tooltip="Главная">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <span class="nav-text">Главная</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/equipment*') ? 'active' : '' }}" href="{{ route('admin.equipment.index') }}" data-tooltip="Каталог техники">
                        <i class="nav-icon bi bi-tools"></i>
                        <span class="nav-text">Каталог техники</span>
                    </a>
                </li>

                <!-- Финансовый раздел -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->is('admin/finance*') || request()->is('admin/excel-mappings*') || request()->is('admin/bank-statements*') || request()->is('admin/reports*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                        <i class="nav-icon bi bi-cash-coin"></i>
                        <span class="nav-text">Финансы</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item {{ request()->is('admin/finance') ? 'active' : '' }}" href="{{ route('admin.finance.dashboard') }}">Дашборд</a></li>
                        <li><a class="dropdown-item {{ request()->is('admin/finance/transactions*') ? 'active' : '' }}" href="{{ route('admin.finance.transactions') }}">Транзакции</a></li>
                        <li><a class="dropdown-item {{ request()->is('admin/finance/invoices*') ? 'active' : '' }}" href="{{ route('admin.finance.invoices') }}">Счета</a></li>
                        <li><a class="dropdown-item {{ request()->is('admin/bank-statements*') ? 'active' : '' }}" href="{{ route('admin.bank-statements.index') }}">Банковские выписки</a></li>
                        <li><a class="dropdown-item {{ request()->is('admin/reports*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">Отчеты</a></li>
                        <li><a class="dropdown-item {{ request()->is('admin/excel-mappings*') ? 'active' : '' }}" href="{{ route('admin.excel-mappings.index') }}">Шаблоны УПД</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/documents*') ? 'active' : '' }}" href="{{ route('admin.documents.index') }}">
                        <i class="nav-icon bi bi-files"></i>
                        <span class="nav-text">Документы</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/lessees*') ? 'active' : '' }}" href="{{ route('admin.lessees.index') }}" data-tooltip="Арендаторы">
                        <i class="nav-icon bi bi-people"></i>
                        <span class="nav-text">Арендаторы</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/lessors*') ? 'active' : '' }}" href="{{ route('admin.lessors.index') }}" data-tooltip="Арендодатели">
                        <i class="nav-icon bi bi-people"></i>
                        <span class="nav-text">Арендодатели</span>
                    </a>
                </li>

                <!-- Новый раздел: Настройки -->
                <div class="section-header mt-4">
                    <i class="bi bi-gear"></i>
                    <h4>Настройки</h4>
                </div>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/settings/document-templates*') ? 'active' : '' }}" href="{{ route('admin.settings.document-templates.index') }}">
                        <i class="nav-icon bi bi-file-earmark-spreadsheet"></i>
                        <span class="nav-text">Шаблоны документов</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="app-version">v1.2.5</div>
        <div class="session-time">
            <i class="bi bi-clock-history"></i>
            <span>{{ now()->format('d.m.Y H:i') }}</span>
        </div>
    </div>
</aside>
@endauth

<style>
/* ГАРАНТИРОВАННЫЕ СТИЛИ ДЛЯ САЙДБАРА */
#sidebarContainer {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: fixed !important;
    top: var(--navbar-height, 80px) !important;
    left: 0 !important;
    width: 280px !important;
    height: calc(100vh - var(--navbar-height, 80px)) !important;
    z-index: 1000 !important;
    background: #f8f9fa !important;
    border-right: 1px solid #dee2e6 !important;
    overflow-y: auto !important;
    transition: all 0.3s ease !important;
}

/* Когда навбар скрыт - сайдбар занимает всю высоту */
.navbar--hidden ~ .main-container #sidebarContainer {
    top: 0 !important;
    height: 100vh !important;
}

/* Минимизированный режим */
.sidebar-mini #sidebarContainer {
    width: 80px !important;
}

/* Контент должен смещаться */
.content-area {
    margin-left: 280px !important;
    transition: margin-left 0.3s ease !important;
}

.sidebar-mini .content-area {
    margin-left: 80px !important;
}

/* Мобильная адаптация */
@media (max-width: 992px) {
    #sidebarContainer {
        transform: translateX(-100%) !important;
        width: 280px !important;
        top: 0 !important;
        height: 100vh !important;
    }

    #sidebarContainer.mobile-open {
        transform: translateX(0) !important;
    }

    .content-area {
        margin-left: 0 !important;
    }
}

/* Стили для анимации стрелки */
.rotate-180 {
    transform: rotate(180deg);
    transition: transform 0.3s ease;
}
</style>
{{-- В конец файла sidebar.blade.php --}}
<style>
/* ПРИНУДИТЕЛЬНЫЕ СТИЛИ ДЛЯ СТРАНИЦЫ РЕДАКТИРОВАНИЯ */
body.rental-request-edit-page #sidebarContainer {
    height: calc(100vh - 80px) !important;
    max-height: calc(100vh - 80px) !important;
    overflow-y: auto !important;
    position: fixed !important;
    top: 80px !important;
    left: 0 !important;
    width: 280px !important;
    z-index: 1000 !important;
}

body.rental-request-edit-page .sidebar-navigation {
    max-height: none !important;
    height: auto !important;
}

body.rental-request-edit-page .nav-menu {
    display: block !important;
    height: auto !important;
}

body.rental-request-edit-page .nav-item {
    display: block !important;
    height: auto !important;
    min-height: 50px !important;
}
</style>

<script>
// Скрипт для добавления класса к body на странице редактирования
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('/edit')) {
        document.body.classList.add('rental-request-edit-page');
        console.log('🔧 Применены стили для страницы редактирования');
    }
});
</script>
<style>
/* ЭКСТРЕННОЕ ИСПРАВЛЕНИЕ ДЛЯ СТРАНИЦЫ РЕДАКТИРОВАНИЯ */
body:has(#rental-request-edit-app) #sidebarContainer,
body.rental-request-edit-page #sidebarContainer {
    height: calc(100vh - 80px) !important;
    max-height: calc(100vh - 80px) !important;
    min-height: auto !important;
    overflow-y: auto !important;
    position: fixed !important;
    top: 80px !important;
    left: 0 !important;
    width: 280px !important;
    z-index: 1000 !important;
    display: block !important;
}

body:has(#rental-request-edit-app) .sidebar-navigation,
body.rental-request-edit-page .sidebar-navigation {
    height: auto !important;
    max-height: none !important;
    overflow: visible !important;
}

body:has(#rental-request-edit-app) .nav-menu,
body.rental-request-edit-page .nav-menu {
    display: block !important;
    height: auto !important;
}

body:has(#rental-request-edit-app) .nav-item,
body.rental-request-edit-page .nav-item {
    display: block !important;
    height: auto !important;
    min-height: 50px !important;
    max-height: none !important;
}
</style>

<script>
// Принудительное исправление сайдбара для страницы редактирования
document.addEventListener('DOMContentLoaded', function() {
    const isEditPage = window.location.pathname.includes('/edit');
    const hasVueEditApp = document.getElementById('rental-request-edit-app');

    if (isEditPage || hasVueEditApp) {
        document.body.classList.add('rental-request-edit-page');
        console.log('🔧 Применены экстренные стили для страницы редактирования');

        // Принудительно устанавливаем высоту сайдбара
        const sidebar = document.getElementById('sidebarContainer');
        if (sidebar) {
            const navbar = document.querySelector('.navbar');
            const navbarHeight = navbar ? navbar.offsetHeight : 80;

            sidebar.style.height = `calc(100vh - ${navbarHeight}px)`;
            sidebar.style.top = `${navbarHeight}px`;
            console.log('📏 Высота сайдбара принудительно установлена');
        }
    }
});
</script>
