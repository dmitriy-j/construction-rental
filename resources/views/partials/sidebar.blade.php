@auth
<div class="sidebar sidebar-styles">
    <ul class="nav flex-column">
        @if(auth()->check() && auth()->user()->company)
            @if(auth()->user()->company->is_lessor)
            <!-- Меню для арендодателя -->
            <li class="nav-item">
                <a class="nav-link {{ Request::is('lessor/dashboard') ? 'active' : '' }}"
                   href="{{ route('lessor.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i> Главная
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ Request::is('lessor/equipment*') ? 'active' : '' }}"
                   href="{{ route('lessor.equipment.index') }}">
                    <i class="fas fa-cogs me-2"></i> Моя техника
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ Request::is('lessor/orders*') ? 'active' : '' }}"
                   href="{{ route('lessor.orders') }}">
                    <i class="fas fa-list me-2"></i> Заказы
                </a>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="documentsDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-file-contract me-2"></i> Документы
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('lessor.documents', ['type' => 'contracts']) }}">Договоры</a></li>
                    <li><a class="dropdown-item" href="{{ route('lessor.documents', ['type' => 'waybills']) }}">Путевые листы</a></li>
                    <li><a class="dropdown-item" href="{{ route('lessor.documents', ['type' => 'delivery_notes']) }}">Накладные</a></li>
                    <li><a class="dropdown-item" href="{{ route('lessor.documents', ['type' => 'completion_acts']) }}">Акты выполненных работ</a></li>
                </ul>
            </li>
        @else
            <!-- Меню для арендатора -->
            <li class="nav-item">
                <a class="nav-link {{ Request::is('lessee/dashboard') ? 'active' : '' }}"
                   href="{{ route('lessee.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i> Главная
                </a>
            </li>

            

            <li class="nav-item">
                <a class="nav-link {{ Request::is('catalog*') ? 'active' : '' }}"
                href="{{ route('catalog.index') }}">
                    <i class="fas fa-search me-2"></i> Каталог техники
                </a>
            </li>

            <!-- Корзина -->
            <li class="nav-item">
                <a class="nav-link {{ Request::is('lessee/cart*') ? 'active' : '' }}"
                href="{{ route('cart.index') }}">
                    <i class="fas fa-shopping-cart me-2"></i> Корзина
                </a>
            </li>

            <!-- Мои заказы -->
            <li class="nav-item">
                <a class="nav-link {{ Request::is('lessee/orders*') ? 'active' : '' }}"
                href="{{ route('lessee.orders.index') }}">
                    <i class="fas fa-list me-2"></i> Мои заказы
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="documentsDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-file-contract me-2"></i> Документы
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('lessee.documents', ['type' => 'contracts']) }}">Договоры</a></li>
                    <li><a class="dropdown-item" href="{{ route('lessee.documents', ['type' => 'waybills']) }}">Путевые листы</a></li>
                    <li><a class="dropdown-item" href="{{ route('lessee.documents', ['type' => 'delivery_notes']) }}">Накладные</a></li>
                    <li><a class="dropdown-item" href="{{ route('lessee.documents', ['type' => 'completion_acts']) }}">Акты выполненных работ</a></li>
                </ul>
            </li>
        @endif
    @endif

        <!-- АДМИН ПАНЕЛЬ -->
        <li class="nav-item">
            <a class="nav-link {{ Request::is('profile') ? 'active' : '' }}"
               href="{{ route('profile.edit') }}">
                <i class="fas fa-user me-2"></i> Профиль
            </a>
        </li>
        <li class="nav-item">
    <a class="nav-link {{ request()->is('admin/equipment*') ? 'active' : '' }}" href="{{ route('admin.equipment.index') }}">
        <i class="bi bi-tools me-2"></i> Каталог техники
       
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.lessees.index') }}">
        <i class="bi bi-people"></i> Арендаторы
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.lessors.index') }}">
        <i class="bi bi-people"></i> Арендодатели
    </a>
</li>

        <li class="nav-item">
            <a class="nav-link {{ Request::is('notifications') ? 'active' : '' }}"
               href="{{ route('notifications') }}">
                <i class="fas fa-bell me-2"></i> Уведомления
            </a>
        </li>
    </ul>
</div>
@endauth
