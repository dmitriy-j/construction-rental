@auth
<div class="sidebar sidebar-styles">
    <ul class="nav flex-column">
        @if(auth()->check() && auth()->user()->company)
            @if(auth()->user()->company->is_lessor)
            <!-- Меню для арендодателя -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('lessor.dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> Главная
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('lessor.equipment') }}">
                    <i class="fas fa-cogs"></i> Моя техника
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('lessor.orders') }}">
                    <i class="fas fa-list"></i> Заказы
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="documentsDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-file-contract"></i> Документы
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
                <a class="nav-link" href="{{ route('lessee.dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> Главная
                </a>
            </li>

            <li class="nav-item">
    <a class="nav-link" href="{{ route('news.index') }}">
        <i class="bi bi-newspaper me-2"></i> Новости
    </a>
</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('catalog') }}">
                    <i class="fas fa-search"></i> Каталог техники
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('lessee.cart.index') }}">
                    <i class="fas fa-shopping-cart"></i> Корзина
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('lessee.orders') }}">
                    <i class="fas fa-list"></i> Мои заказы
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="documentsDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-file-contract"></i> Документы
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
        
        <!-- Общие пункты -->
        <li class="nav-item">
            <a class="nav-link" href="{{ route('profile.edit') }}">
                <i class="fas fa-user"></i> Профиль
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('notifications') }}">
                <i class="fas fa-bell"></i> Уведомления
            </a>
        </li>
        <!-- Добавим позже -->
        <!-- <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-wallet"></i> Баланс
            </a>
        </li> -->
    </ul>
</div>
@endauth