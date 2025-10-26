@extends('layouts.app')

@section('title', 'Редактирование заявки')

@section('content')
<div class="container-fluid">
    <!-- Vue приложение -->
    <div id="rental-request-edit-app"
        data-request-id="{{ $rentalRequest->id }}"
        data-api-url="{{ url('/api/lessee/rental-requests/' . $rentalRequest->id) }}"
        data-update-url="{{ route('lessee.rental-requests.update', $rentalRequest->id) }}"
        data-csrf-token="{{ csrf_token() }}"
        data-categories="{{ json_encode($categories) }}"
        data-locations="{{ json_encode($locations) }}">

        <div class="alert alert-warning">
            Загрузка редактора заявки...
        </div>
    </div>
</div>
@endsection

@vite(['resources/js/pages/rental-request-edit.js'])

<style>
/* АВТОНОМНЫЕ СТИЛИ ДЛЯ САЙДБАРА НА СТРАНИЦЕ РЕДАКТИРОВАНИЯ */
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
}

.sidebar-navigation {
    height: auto !important;
    max-height: none !important;
    overflow: visible !important;
}

.nav-menu {
    display: block !important;
    height: auto !important;
}

.nav-item {
    display: block !important;
    height: auto !important;
    min-height: 50px !important;
    max-height: none !important;
}

.nav-link {
    display: flex !important;
    align-items: center !important;
    height: auto !important;
    min-height: 50px !important;
    padding: 0.75rem 1rem !important;
}

/* Убедимся, что контент смещается правильно */
.content-area {
    margin-left: 280px !important;
}

@media (max-width: 992px) {
    #sidebarContainer {
        transform: translateX(-100%) !important;
    }

    .content-area {
        margin-left: 0 !important;
    }
}
</style>

<script>
// АВТОНОМНАЯ ИНИЦИАЛИЗАЦИЯ САЙДБАРА БЕЗ ИМПОРТОВ
function initEditPageSidebar() {
    console.log('🔧 Автономная инициализация сайдбара для страницы редактирования');

    const sidebar = document.getElementById('sidebarContainer');
    if (!sidebar) {
        console.log('❌ Сайдбар не найден');
        return;
    }

    // Принудительно устанавливаем корректные стили
    const navbar = document.querySelector('.navbar');
    const navbarHeight = navbar ? navbar.offsetHeight : 80;

    sidebar.style.height = `calc(100vh - ${navbarHeight}px)`;
    sidebar.style.top = `${navbarHeight}px`;
    sidebar.style.position = 'fixed';
    sidebar.style.left = '0';
    sidebar.style.width = '280px';
    sidebar.style.zIndex = '1000';
    sidebar.style.overflowY = 'auto';
    sidebar.style.display = 'block';

    // Сбрасываем проблемные стили у внутренних элементов
    const navMenu = sidebar.querySelector('.nav-menu');
    if (navMenu) {
        navMenu.style.height = 'auto';
        navMenu.style.maxHeight = 'none';
        navMenu.style.overflow = 'visible';
        navMenu.style.display = 'block';
    }

    const navItems = sidebar.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.style.height = 'auto';
        item.style.minHeight = '50px';
        item.style.maxHeight = 'none';
        item.style.display = 'block';
    });

    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.style.display = 'flex';
        link.style.alignItems = 'center';
        link.style.height = 'auto';
        link.style.minHeight = '50px';
    });

    console.log('✅ Сайдбар принудительно инициализирован');
}

// Запускаем инициализацию несколько раз для надежности
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM готов - инициализируем сайдбар');
    initEditPageSidebar();
});

window.addEventListener('load', function() {
    console.log('📦 Страница загружена - проверяем сайдбар');
    setTimeout(initEditPageSidebar, 100);
});

// Дополнительная проверка после загрузки Vue
setTimeout(function() {
    console.log('⏰ Дополнительная проверка после загрузки Vue');
    initEditPageSidebar();
}, 500);
</script>
