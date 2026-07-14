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
/* СТИЛИ ДЛЯ МОБИЛЬНОГО САЙДБАРА */
#sidebarContainer {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 85vw !important;
    max-width: 320px !important;
    height: 100vh !important;
    background: #f8f9fa !important;
    z-index: 10001 !important; /* Выше навбара */
    transform: translateX(-100%) !important;
    transition: transform 0.3s ease !important;
    overflow-y: auto !important;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
}

#sidebarContainer.mobile-open {
    transform: translateX(0) !important;
}

/* Оверлей */
.sidebar-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0,0,0,0.5) !important;
    z-index: 10000 !important; /* Между навбаром и сайдбаром */
    display: none !important;
}

.sidebar-overlay.active {
    display: block !important;
}

/* ФИКС ДЛЯ КЛИКАБЕЛЬНОСТИ */
.sidebar-container * {
    pointer-events: auto !important;
}

.sidebar-container.mobile-open * {
    pointer-events: auto !important;
}

/* ВЫРАВНИВАНИЕ ЭЛЕМЕНТОВ */
.user-profile-card {
    padding: 1.5rem 1rem !important;
    margin: 0 !important;
    border-bottom: 1px solid #dee2e6 !important;
}

.avatar-container {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
}

.user-info {
    flex: 1 !important;
    min-width: 0 !important;
}

.user-name {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.25rem !important;
    color: #212529 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

.user-role {
    font-size: 0.8rem !important;
    color: #6c757d !important;
    background: #e9ecef !important;
    padding: 0.25rem 0.5rem !important;
    border-radius: 12px !important;
    display: inline-block !important;
}

/* ВЫРАВНИВАНИЕ НАВИГАЦИИ */
.sidebar-navigation {
    padding: 1rem 0.5rem !important;
}

.section-header {
    display: flex !important;
    align-items: center !important;
    padding: 0.75rem 0.5rem !important;
    margin: 0.5rem 0.5rem 1rem !important;
    color: #0b5ed7 !important;
    background: rgba(11, 94, 215, 0.05) !important;
    border-radius: 8px !important;
}

.sidebar-section-icon {
    font-size: 1.2rem !important;
    margin-right: 0.75rem !important;
}

.sidebar-section-title {
    font-size: 0.9rem !important;
    font-weight: 600 !important;
    margin: 0 !important;
    text-transform: uppercase !important;
}

/* ВЫРАВНИВАНИЕ ПУНКТОВ МЕНЮ */
.nav-menu {
    list-style: none !important;
    padding: 0 !important;
    margin: 0 !important;
}

.nav-item {
    margin-bottom: 0.25rem !important;
    width: 100% !important;
}

.nav-link {
    display: flex !important;
    align-items: center !important;
    padding: 0.85rem 1rem !important;
    border-radius: 8px !important;
    color: #495057 !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    margin: 0 0.25rem !important;
    width: calc(100% - 0.5rem) !important;
    box-sizing: border-box !important;
}

.nav-link:hover {
    background: rgba(11, 94, 215, 0.1) !important;
    color: #0b5ed7 !important;
}

.nav-link.active {
    background: rgba(11, 94, 215, 0.15) !important;
    color: #0b5ed7 !important;
    font-weight: 600 !important;
}

.nav-icon {
    width: 24px !important;
    height: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin-right: 0.75rem !important;
    font-size: 1.1rem !important;
    color: #6c757d !important;
    flex-shrink: 0 !important;
}

.nav-text {
    flex: 1 !important;
    text-align: left !important;
}

/* КНОПКИ УПРАВЛЕНИЯ */
.sidebar-controls {
    position: absolute !important;
    top: 15px !important;
    right: 15px !important;
    z-index: 10000 !important;
}

.sidebar-collapse-btn,
.sidebar-minify-btn {
    width: 32px !important;
    height: 32px !important;
    border-radius: 50% !important;
    background: white !important;
    border: 1px solid #dee2e6 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    font-size: 0.9rem !important;
    color: #495057 !important;
}

/* Для десктопа */
@media (min-width: 992px) {
    #sidebarContainer {
        transform: translateX(0) !important;
        position: fixed !important;
        top: var(--navbar-height) !important;
        height: calc(100vh - var(--navbar-height)) !important;
        width: 280px !important;
        max-width: none !important;
    }

    .content-area {
        margin-left: 280px !important;
    }

    .sidebar-overlay {
        display: none !important;
    }

    .sidebar-collapse-btn {
        display: none !important;
    }
}

/* Темная тема */
[data-theme="dark"] #sidebarContainer {
    background: #1a1d21 !important;
    color: #f8f9fa !important;
}

[data-theme="dark"] .user-name {
    color: #f8f9fa !important;
}

[data-theme="dark"] .nav-link {
    color: #e9ecef !important;
}

[data-theme="dark"] .nav-link:hover {
    background: rgba(61, 139, 253, 0.1) !important;
    color: #3d8bfd !important;
}
</style>

<!-- Оверлей для мобильных -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Сам сайдбар -->
<aside class="sidebar-container" id="sidebarContainer">
    <div class="user-profile-card">
        <div class="avatar-container">
            <div class="avatar">
                @if(Auth::user()->profile_photo_path)
                    <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="profile-avatar" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                @else
                    <i class="bi bi-person-circle" style="font-size: 2rem; color: #6c757d;"></i>
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

    <!-- Кнопка закрытия для мобильных -->
    <div class="sidebar-controls">
        <button class="sidebar-collapse-btn d-lg-none" id="sidebarClose" aria-label="Закрыть меню">
            <i class="bi bi-x-lg"></i>
        </button>
        <button class="sidebar-minify-btn d-none d-lg-block" id="sidebarMinify" aria-label="Свернуть меню">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>

    <!-- Навигация сайдбара -->
    <nav class="sidebar-navigation">
        {{-- Заголовок «Основное меню» только для НЕ-администраторов --}}
        @if(!auth()->user()->isPlatformAdmin())
            <div class="section-header">
                <i class="bi bi-menu-button-wide sidebar-section-icon"></i>
                <h4 class="sidebar-section-title">Основное меню</h4>
            </div>
        @endif

        <ul class="nav-menu">
            {{-- Пункты меню для арендодателя / арендатора только для НЕ-администраторов --}}
            @if(!auth()->user()->isPlatformAdmin())
                @if(auth()->check() && auth()->user()->company)
                    @if(auth()->user()->company->is_lessor)
                        <!-- Меню для арендодателя -->
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessor/dashboard') ? 'active' : '' }}" href="{{ route('lessor.dashboard') }}">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <span class="nav-text">Главная</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessor/balance*') ? 'active' : '' }}" href="{{ route('lessor.balance.index') }}">
                                <i class="nav-icon bi bi-wallet2"></i>
                                <span class="nav-text">Баланс</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessor/equipment*') ? 'active' : '' }}" href="{{ route('lessor.equipment.index') }}">
                                <i class="nav-icon bi bi-wrench-adjustable-circle"></i>
                                <span class="nav-text">Моя техника</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessor/operators*') ? 'active' : '' }}" href="{{ route('lessor.operators.index') }}">
                                <i class="nav-icon bi bi-people"></i>
                                <span class="nav-text">Операторы</span>
                            </a>
                        </li>

                        @php
                            $newOrdersCount = $newOrdersCount ?? 0;
                        @endphp

                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessor/orders*') ? 'active' : '' }}" href="{{ route('lessor.orders.index') }}">
                                <i class="nav-icon bi bi-list-task"></i>
                                <span class="nav-text">Заказы</span>
                                @if($newOrdersCount > 0)
                                    <span class="badge bg-primary rounded-pill pulse">{{ $newOrdersCount }}</span>
                                @endif
                            </a>
                        </li>

                        <!-- Заявки на аренду для арендодателя -->
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessor/rental-requests*') ? 'active' : '' }}" href="{{ route('lessor.rental-requests.index') }}">
                                <i class="nav-icon bi bi-search-heart"></i>
                                <span class="nav-text">Заявки на аренду</span>
                                @if($newRentalRequestsCount > 0)
                                    <span class="badge bg-success rounded-pill pulse">{{ $newRentalRequestsCount }}</span>
                                @endif
                            </a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <a class="nav-link {{ Request::is('lessee/dashboard') ? 'active' : '' }}" href="{{ route('lessee.dashboard') }}">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <span class="nav-text">Главная</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessee/balance*') ? 'active' : '' }}" href="{{ route('lessee.balance.index') }}">
                                <i class="nav-icon bi bi-wallet2"></i>
                                <span class="nav-text">Баланс</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('catalog*') ? 'active' : '' }}" href="{{ route('catalog.index') }}">
                                <i class="nav-icon bi bi-search"></i>
                                <span class="nav-text">Каталог техники</span>
                            </a>
                        </li>

                        <!-- Заявки на аренду для арендатора -->
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessee/rental-requests*') ? 'active' : '' }}" href="{{ route('lessee.rental-requests.index') }}">
                                <i class="nav-icon bi bi-clipboard-plus"></i>
                                <span class="nav-text">Мои заявки</span>
                                @if($newProposalsCount > 0)
                                    <span class="badge bg-success rounded-pill pulse">{{ $newProposalsCount }}</span>
                                @endif
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessee/cart*') ? 'active' : '' }}" href="{{ route('cart.index') }}">
                                <i class="nav-icon bi bi-cart"></i>
                                <span class="nav-text">Корзина</span>
                                @if($cartItemsCount > 0)
                                    <span class="badge bg-primary rounded-pill pulse">{{ $cartItemsCount }}</span>
                                @endif
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('lessee/orders*') ? 'active' : '' }}" href="{{ route('lessee.orders.index') }}">
                                <i class="nav-icon bi bi-list-task"></i>
                                <span class="nav-text">Мои заказы</span>
                            </a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
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
            @endif

            {{-- Общие пункты для всех пользователей (включая администраторов) --}}
            <li class="nav-item">
                <a class="nav-link {{ Request::is('profile') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                    <i class="nav-icon bi bi-person"></i>
                    <span class="nav-text">Профиль</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ Request::is('notifications') ? 'active' : '' }}" href="{{ route('notifications') }}">
                    <i class="nav-icon bi bi-bell"></i>
                    <span class="nav-text">Уведомления</span>
                    @if($unreadNotificationsCount > 0)
                        <span class="badge bg-danger rounded-pill pulse">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>
            </li>

            {{-- Админ панель (только для администраторов) --}}
            @if(auth()->user()->isPlatformAdmin())
                <div class="section-header mt-4">
                    <i class="bi bi-shield-lock"></i>
                    <h4>Администрирование</h4>
                </div>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('admin/dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <span class="nav-text">Главная</span>
                    </a>
                </li>
                <!-- Управление заказами -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/orders*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                        <i class="nav-icon bi bi-receipt"></i>
                        <span class="nav-text">Управление заказами</span>
                        @if($pendingOrders ?? 0 > 0)
                            <span class="badge bg-warning rounded-pill pulse">{{ $pendingOrders }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/equipment*') ? 'active' : '' }}" href="{{ route('admin.equipment.index') }}">
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
                    <a class="nav-link {{ request()->is('admin/lessees*') ? 'active' : '' }}" href="{{ route('admin.lessees.index') }}">
                        <i class="nav-icon bi bi-people"></i>
                        <span class="nav-text">Арендаторы</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/lessors*') ? 'active' : '' }}" href="{{ route('admin.lessors.index') }}">
                        <i class="nav-icon bi bi-people"></i>
                        <span class="nav-text">Арендодатели</span>
                    </a>
                </li>

                <!-- Настройки -->
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
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/markups*') ? 'active' : '' }}" href="{{ route('markups.index') }}">
                        <i class="nav-icon bi bi-percent"></i>
                        <span class="nav-text">Наценки платформы</span>
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

<script>
// ПРОСТОЙ СКРИПТ ДЛЯ УПРАВЛЕНИЯ САЙДБАРОМ
// Полное разделение логики dropdown и обычных ссылок
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebarContainer');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('sidebarClose');

    function closeSidebar() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // 1. Обработчик для обычных ссылок - закрываем сайдбар
    const regularLinks = sidebar.querySelectorAll('a.nav-link:not(.dropdown-toggle)');
    regularLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });

    // 2. Обработчик для dropdown-item (пункты внутри выпадающего меню) - закрываем сайдбар
    const dropdownItems = sidebar.querySelectorAll('a.dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });

    // 3. Для dropdown-toggle (сама кнопка раскрытия) - НЕ закрываем сайдбар
    const dropdownToggles = sidebar.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            // Ничего не делаем - позволяем Bootstrap открыть dropdown
            console.log('Dropdown toggle clicked - keeping sidebar open');
        });
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
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

@media (max-width: 991.98px) {
    /* Скрываем навбар когда открыт сайдбар */
    .mobile-open ~ .navbar .navbar-collapse {
        display: none !important;
    }

    /* Гарантируем что сайдбар поверх навбара */
    #sidebarContainer {
        z-index: 10000 !important;
    }

    .sidebar-overlay {
        z-index: 9999 !important;
    }

    /* Навбар должен быть под сайдбаром */
    .navbar {
        z-index: 9990 !important;
    }
}

/* Анимация для плавного открытия/закрытия */
#sidebarContainer {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.sidebar-overlay {
    transition: opacity 0.3s ease !important;
}

/* ФИКС ДЛЯ ТЕКСТА ПОЛЬЗОВАТЕЛЯ В МОБИЛЬНОЙ ВЕРСИИ */
@media (max-width: 991.98px) {
    .user-profile-card {
        padding: 1rem 0.75rem !important;
        margin: 0 5px 10px 5px !important;
        width: calc(100% - 10px) !important;
        box-sizing: border-box !important;
    }

    .avatar-container {
        gap: 8px !important;
        min-width: 0 !important;
    }

    .user-info {
        min-width: 0 !important;
        flex: 1 !important;
        overflow: hidden !important;
        max-width: calc(100% - 60px) !important; /* учитываем аватар + отступ */
    }

    .user-name {
        font-size: 0.95rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 100% !important;
        display: block !important;
        line-height: 1.2 !important;
        margin-bottom: 0.2rem !important;
    }

    .user-role {
        font-size: 0.7rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 100% !important;
        display: block !important;
        padding: 0.15rem 0.35rem !important;
        line-height: 1.1 !important;
    }

    .avatar {
        flex-shrink: 0 !important;
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
    }

    .profile-avatar {
        width: 40px !important;
        height: 40px !important;
    }

    .bi-person-circle {
        font-size: 1.5rem !important;
    }
}

/* Гарантия что сайдбар не шире экрана */
#sidebarContainer {
    max-width: 100vw !important;
    box-sizing: border-box !important;
    overflow-x: hidden !important;
}

.user-profile-card {
    box-sizing: border-box !important;
    width: 100% !important;
    overflow: hidden !important;
}
/* ДОПОЛНИТЕЛЬНЫЙ ФИКС ДЛЯ ОЧЕНЬ ДЛИННЫХ ИМЕН */
@media (max-width: 991.98px) {
    .user-info {
        position: relative !important;
    }

    .user-name {
        /* Принудительное ограничение длины */
        max-width: min(200px, 65vw) !important;
    }

    .user-role {
        /* Принудительное ограничение длины */
        max-width: min(180px, 60vw) !important;
    }

    /* Для очень маленьких экранов */
    @media (max-width: 360px) {
        .user-name {
            font-size: 0.9rem !important;
            max-width: min(150px, 55vw) !important;
        }

        .user-role {
            font-size: 0.65rem !important;
            max-width: min(140px, 50vw) !important;
        }

        .avatar {
            width: 35px !important;
            height: 35px !important;
            min-width: 35px !important;
        }

        .profile-avatar {
            width: 35px !important;
            height: 35px !important;
        }
    }
}
/* КРИТИЧЕСКИЙ ФИКС ДЛЯ ПОЗИЦИОНИРОВАНИЯ САЙДБАРА НА МОБИЛЬНЫХ */
@media (max-width: 991.98px) {
    #sidebarContainer {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 85vw !important;
        max-width: 320px !important;
        height: 100vh !important;
        background: #f8f9fa !important;
        z-index: 10001 !important;
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease !important;
        overflow-y: auto !important;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
    }

    #sidebarContainer.mobile-open {
        transform: translateX(0) !important;
    }

    /* Гарантия что контент сайдбара не выходит за пределы */
    .user-profile-card {
        position: relative !important;
        z-index: 1 !important;
        margin: 0 !important;
        padding: 1rem !important;
        width: 100% !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    .sidebar-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background: rgba(0,0,0,0.5) !important;
        z-index: 10000 !important;
        display: none !important;
    }

    .sidebar-overlay.active {
        display: block !important;
    }

    /* ФИКС ДЛЯ ЗАГОЛОВКА - гарантия что он остается внутри */
    .avatar-container {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    .user-info {
        flex: 1 !important;
        min-width: 0 !important;
        overflow: hidden !important;
    }

    .user-name {
        font-size: 1rem !important;
        font-weight: 600 !important;
        color: #212529 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: block !important;
        width: 100% !important;
        line-height: 1.2 !important;
        margin-bottom: 0.25rem !important;
    }

    .user-role {
        font-size: 0.75rem !important;
        color: #6c757d !important;
        background: #e9ecef !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 12px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        display: inline-block !important;
        max-width: 100% !important;
    }
}

/* Дополнительный фикс для очень маленьких экранов */
@media (max-width: 360px) {
    .user-name {
        font-size: 0.9rem !important;
    }

    .user-role {
        font-size: 0.7rem !important;
    }

    .avatar {
        width: 35px !important;
        height: 35px !important;
        min-width: 35px !important;
    }
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
@endauth