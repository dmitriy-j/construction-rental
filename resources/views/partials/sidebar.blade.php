{{-- resources/views/partials/sidebar.blade.php --}}
@auth
@php
    $unreadNotificationsCount = auth()->user()->unreadNotifications->count();
    try { $cartItemsCount = App\Services\CartService::getCartItemsCount(auth()->user()); } catch (Exception $e) { $cartItemsCount = 0; }
    try { $newProposalsCount = App\Services\ProposalManagementService::getNewProposalsCount(auth()->user()); } catch (Exception $e) { $newProposalsCount = 0; }
    try { $newRentalRequestsCount = \App\Services\RequestMatchingService::getNewRequestsCount(auth()->user()); } catch (\Exception $e) { $newRentalRequestsCount = 0; }
@endphp
<div class="offcanvas offcanvas-start sidebar-offcanvas" id="sidebarOffcanvas" data-bs-scroll="true" data-bs-backdrop="false">
    <div class="offcanvas-body p-0 d-flex flex-column" style="height: 100%;">
        <div class="user-profile-card p-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar flex-shrink-0">
                    @if(Auth::user()->profile_photo_path)
                        <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                    @else
                        <i class="bi bi-person-circle fs-1 text-secondary"></i>
                    @endif
                </div>
                <div class="flex-grow-1 min-width-0">
                    <div class="fw-bold text-primary text-truncate">{{ Auth::user()->name }}</div>
                    <div class="small text-muted">
                        @if(Auth::user()->isPlatformAdmin()) <i class="bi bi-shield-check"></i> Администратор
                        @elseif(Auth::user()->company && Auth::user()->company->is_lessor) <i class="bi bi-building"></i> Арендодатель
                        @elseif(Auth::user()->company && Auth::user()->company->is_lessee) <i class="bi bi-truck"></i> Арендатор
                        @else <i class="bi bi-person"></i> Пользователь
                        @endif
                    </div>
                </div>
                <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas"></button>
            </div>
        </div>

        <nav class="flex-grow-1 overflow-auto p-2">
            @if(!auth()->user()->isPlatformAdmin())
                <div class="section-header px-2 py-2 mb-2 rounded"><i class="bi bi-menu-button-wide me-2 text-primary"></i><span class="fw-bold text-primary text-uppercase small">Основное меню</span></div>
            @endif
            <ul class="nav flex-column" id="sidebarNav">
                @if(!auth()->user()->isPlatformAdmin())
                    @if(auth()->check() && auth()->user()->company)
                        @if(auth()->user()->company->is_lessor)
                            @foreach([
                                ['route'=>'lessor.dashboard','icon'=>'bi-speedometer2','label'=>'Главная','check'=>'lessor/dashboard'],
                                ['route'=>'lessor.balance.index','icon'=>'bi-wallet2','label'=>'Баланс','check'=>'lessor/balance*'],
                                ['route'=>'lessor.equipment.index','icon'=>'bi-wrench-adjustable-circle','label'=>'Моя техника','check'=>'lessor/equipment*'],
                                ['route'=>'lessor.operators.index','icon'=>'bi-people','label'=>'Операторы','check'=>'lessor/operators*'],
                                ['route'=>'lessor.orders.index','icon'=>'bi-list-task','label'=>'Заказы','check'=>'lessor/orders*','badge'=>($newOrdersCount??0)],
                                ['route'=>'lessor.rental-requests.index','icon'=>'bi-search-heart','label'=>'Заявки на аренду','check'=>'lessor/rental-requests*','badge'=>$newRentalRequestsCount],
                            ] as $item)
                            <li class="nav-item"><a class="nav-link py-2 px-3 rounded {{ Request::is($item['check']) ? 'active' : '' }}" href="{{ route($item['route']) }}" title="{{ $item['label'] }}"><i class="bi {{ $item['icon'] }} nav-icon"></i><span>{{ $item['label'] }}</span>@if(!empty($item['badge']) && $item['badge']>0)<span class="badge bg-primary rounded-pill ms-auto">{{ $item['badge'] }}</span>@endif</a></li>
                            @endforeach
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 rounded" data-bs-toggle="collapse" href="#docsMenu" role="button" title="Документы"><i class="bi bi-files nav-icon"></i><span>Документы</span><i class="bi bi-chevron-down ms-auto small"></i></a>
                                <div class="collapse" id="docsMenu">
                                    <ul class="nav flex-column ps-3">
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('lessor.documents.index', ['type'=>'contracts']) }}">Договоры</a></li>
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('lessor.documents.index', ['type'=>'waybills']) }}">Путевые листы</a></li>
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('lessor.documents.index', ['type'=>'delivery_notes']) }}">Накладные</a></li>
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('lessor.documents.index', ['type'=>'completion_acts']) }}">Акты</a></li>
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('lessor.upds.index') }}">УПД</a></li>
                                    </ul>
                                </div>
                            </li>
                        @else
                            @foreach([
                                ['route'=>'lessee.dashboard','icon'=>'bi-speedometer2','label'=>'Главная','check'=>'lessee/dashboard'],
                                ['route'=>'lessee.balance.index','icon'=>'bi-wallet2','label'=>'Баланс','check'=>'lessee/balance*'],
                                ['route'=>'catalog.index','icon'=>'bi-search','label'=>'Каталог техники','check'=>'catalog*'],
                                ['route'=>'lessee.rental-requests.index','icon'=>'bi-clipboard-plus','label'=>'Мои заявки','check'=>'lessee/rental-requests*','badge'=>$newProposalsCount],
                                ['route'=>'cart.index','icon'=>'bi-cart','label'=>'Корзина','check'=>'lessee/cart*','badge'=>$cartItemsCount],
                                ['route'=>'lessee.orders.index','icon'=>'bi-list-task','label'=>'Мои заказы','check'=>'lessee/orders*'],
                            ] as $item)
                            <li class="nav-item"><a class="nav-link py-2 px-3 rounded {{ Request::is($item['check']) ? 'active' : '' }}" href="{{ route($item['route']) }}" title="{{ $item['label'] }}"><i class="bi {{ $item['icon'] }} nav-icon"></i><span>{{ $item['label'] }}</span>@if(!empty($item['badge']) && $item['badge']>0)<span class="badge bg-success rounded-pill ms-auto">{{ $item['badge'] }}</span>@endif</a></li>
                            @endforeach
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 rounded" data-bs-toggle="collapse" href="#docsMenu" role="button" title="Документы"><i class="bi bi-files nav-icon"></i><span>Документы</span><i class="bi bi-chevron-down ms-auto small"></i></a>
                                <div class="collapse" id="docsMenu">
                                    <ul class="nav flex-column ps-3">
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('documents.index', ['type'=>'contracts']) }}">Договоры</a></li>
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('documents.index', ['type'=>'waybills']) }}">Путевые листы</a></li>
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('documents.index', ['type'=>'delivery_notes']) }}">Накладные</a></li>
                                        <li><a class="nav-link py-1 px-3 small" href="{{ route('documents.index', ['type'=>'completion_acts']) }}">Акты</a></li>
                                    </ul>
                                </div>
                            </li>
                        @endif
                    @endif
                @endif

                <li class="nav-item"><a class="nav-link py-2 px-3 rounded {{ Request::is('profile') ? 'active' : '' }}" href="{{ route('profile.edit') }}" title="Профиль"><i class="bi bi-person nav-icon"></i><span>Профиль</span></a></li>
                <li class="nav-item"><a class="nav-link py-2 px-3 rounded {{ Request::is('notifications') ? 'active' : '' }}" href="{{ route('notifications') }}" title="Уведомления"><i class="bi bi-bell nav-icon"></i><span>Уведомления</span>@if($unreadNotificationsCount>0)<span class="badge bg-danger rounded-pill ms-auto">{{ $unreadNotificationsCount }}</span>@endif</a></li>

                @if(auth()->user()->isPlatformAdmin())
                    <div class="section-header px-2 py-2 mt-3 mb-2 rounded"><i class="bi bi-shield-lock me-2 text-primary"></i><span class="fw-bold text-primary text-uppercase small">Администрирование</span></div>
                    @foreach([
                        ['route'=>'admin.dashboard','icon'=>'bi-speedometer2','label'=>'Главная','check'=>'admin/dashboard'],
                        ['route'=>'admin.orders.index','icon'=>'bi-receipt','label'=>'Управление заказами','check'=>'admin/orders*'],
                        ['route'=>'admin.rental-requests.index','icon'=>'bi-clipboard-plus','label'=>'Заявки','check'=>'admin/rental-requests*'],
                    ] as $item)
                    <li class="nav-item"><a class="nav-link py-2 px-3 rounded {{ Request::is($item['check']) ? 'active' : '' }}" href="{{ route($item['route']) }}" title="{{ $item['label'] }}"><i class="bi {{ $item['icon'] }} nav-icon"></i><span>{{ $item['label'] }}</span></a></li>
                    @endforeach
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 rounded {{ request()->is('admin/equipment*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#techMenu" role="button" title="Техника"><i class="bi bi-tools nav-icon"></i><span>Техника</span><i class="bi bi-chevron-down ms-auto small"></i></a>
                        <div class="collapse" id="techMenu">
                            <ul class="nav flex-column ps-3">
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.equipment.index') }}">Вся техника</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.equipment.index',['owner_type'=>'platform']) }}">Техника платформы</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.equipment.index',['owner_type'=>'lessor']) }}">Техника арендодателей</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-2 px-3 rounded {{ request()->is('admin/finance*')||request()->is('admin/bank-statements*')||request()->is('admin/reports*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#financeMenu" role="button" title="Финансы"><i class="bi bi-cash-coin nav-icon"></i><span>Финансы</span><i class="bi bi-chevron-down ms-auto small"></i></a>
                        <div class="collapse" id="financeMenu">
                            <ul class="nav flex-column ps-3">
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.finance.dashboard') }}">Дашборд</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.finance.lessee-debts') }}">Долги арендаторов</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.finance.lessor-debts') }}">Долги арендодателям</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.finance.transactions') }}">Транзакции</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.finance.invoices') }}">Счета</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.finance.balance-adjustments') }}">Корректировки</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.bank-statements.index') }}">Банковские выписки</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.reports.index') }}">Отчеты</a></li>
                                <li><a class="nav-link py-1 px-3 small" href="{{ route('admin.excel-mappings.index') }}">Шаблоны УПД</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item"><a class="nav-link py-2 px-3 rounded {{ Request::is('admin/documents*') ? 'active' : '' }}" href="{{ route('admin.documents.index') }}" title="Документы"><i class="bi bi-files nav-icon"></i><span>Документы</span></a></li>
                    <li class="nav-item"><a class="nav-link py-2 px-3 rounded {{ request()->is('admin/lessees*') ? 'active' : '' }}" href="{{ route('admin.lessees.index') }}" title="Арендаторы"><i class="bi bi-people nav-icon"></i><span>Арендаторы</span></a></li>
                    <li class="nav-item"><a class="nav-link py-2 px-3 rounded {{ request()->is('admin/lessors*') ? 'active' : '' }}" href="{{ route('admin.lessors.index') }}" title="Арендодатели"><i class="bi bi-people nav-icon"></i><span>Арендодатели</span></a></li>
                    <div class="section-header px-2 py-2 mt-3 mb-2 rounded"><i class="bi bi-gear me-2 text-primary"></i><span class="fw-bold text-primary text-uppercase small">Настройки</span></div>
                    <li class="nav-item"><a class="nav-link py-2 px-3 rounded" href="{{ route('admin.settings.document-templates.index') }}" title="Шаблоны"><i class="bi bi-file-earmark-spreadsheet nav-icon"></i><span>Шаблоны документов</span></a></li>
                    <li class="nav-item"><a class="nav-link py-2 px-3 rounded" href="{{ route('markups.index') }}" title="Наценки"><i class="bi bi-percent nav-icon"></i><span>Наценки платформы</span></a></li>
                @endif
            </ul>
        </nav>

        <div class="border-top p-2 small text-muted d-flex justify-content-between align-items-center">
            <span class="app-version px-2 py-1 rounded bg-primary bg-opacity-10 text-primary fw-bold">v1.2.5</span>
            <span class="session-time d-flex align-items-center gap-1"><i class="bi bi-clock-history text-primary"></i> {{ now()->format('d.m.Y H:i') }}</span>
        </div>
    </div>
</div>
@endauth
