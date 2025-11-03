import { createApp } from 'vue';
import EditRentalRequestForm from '../components/RentalRequest/EditRentalRequestForm.vue';

console.log('🎯 rental-request-edit.js: Скрипт начал выполнение');

// ФУНКЦИЯ ДЛЯ ФИКСА СТРУКТУРЫ СТРАНИЦЫ
function fixPageStructure() {
    console.log('🔧 Исправляем структуру страницы...');

    const appElement = document.getElementById('rental-request-edit-app');
    if (!appElement) return;

    // Гарантируем что контейнер Vue не нарушает структуру
    appElement.style.minHeight = 'auto';
    appElement.style.height = 'auto';
    appElement.style.overflow = 'visible';

    // Находим основные элементы структуры
    const mainContent = document.querySelector('.main-content');
    const contentContainer = document.querySelector('.content-container');
    const footer = document.querySelector('.site-footer');

    if (mainContent) {
        mainContent.style.minHeight = 'auto';
        mainContent.style.height = 'auto';
        mainContent.style.flex = '1';
    }

    if (contentContainer) {
        contentContainer.style.minHeight = 'auto';
        contentContainer.style.height = 'auto';
        contentContainer.style.flex = '1';
    }

    if (footer) {
        // Гарантируем что футер внизу
        footer.style.marginTop = 'auto';
        footer.style.flexShrink = '0';
        footer.style.position = 'relative';
        footer.style.zIndex = '10';
    }

    console.log('✅ Структура страницы исправлена');
}

// ОСНОВНАЯ ИНИЦИАЛИЗАЦИЯ
function initializeVueApp() {
    console.log('🔄 Инициализация Vue приложения...');

    const appElement = document.getElementById('rental-request-edit-app');
    if (!appElement) {
        console.error('❌ Элемент #rental-request-edit-app не найден');
        return;
    }

    try {
        // Сначала исправляем структуру
        fixPageStructure();

        const app = createApp(EditRentalRequestForm, {
            requestId: appElement.dataset.requestId,
            apiUrl: appElement.dataset.apiUrl,
            updateUrl: appElement.dataset.updateUrl,
            csrfToken: appElement.dataset.csrfToken,
            categories: JSON.parse(appElement.dataset.categories || '[]'),
            locations: JSON.parse(appElement.dataset.locations || '[]')
        });

        app.mount('#rental-request-edit-app');
        console.log('✅ Vue приложение смонтировано успешно');

        // Дополнительная проверка после монтирования Vue
        setTimeout(() => {
            fixPageStructure();
            checkFooterPosition();
        }, 500);

    } catch (error) {
        console.error('❌ Ошибка при монтировании Vue:', error);
    }
}

// ПРОВЕРКА ПОЗИЦИИ ФУТЕРА
function checkFooterPosition() {
    const footer = document.querySelector('.site-footer');
    const app = document.getElementById('app');
    const mainContent = document.querySelector('.main-content-wrapper');

    if (!footer || !app || !mainContent) return;

    const windowHeight = window.innerHeight;
    const appHeight = app.offsetHeight;
    const mainContentHeight = mainContent.offsetHeight;
    const footerRect = footer.getBoundingClientRect();

    console.log('📊 Проверка позиции футера:', {
        windowHeight,
        appHeight,
        mainContentHeight,
        footerTop: footerRect.top,
        footerBottom: footerRect.bottom,
        documentHeight: document.documentElement.scrollHeight
    });

    // Если футер не внизу, принудительно исправляем
    if (footerRect.top < windowHeight - 100) {
        console.log('⚠️ Футер не внизу, применяем экстренный фикс');
        applyEmergencyFix();
    }
}

// ЭКСТРЕННЫЙ ФИКС
function applyEmergencyFix() {
    const app = document.getElementById('app');
    const mainContent = document.querySelector('.main-content-wrapper');
    const footer = document.querySelector('.site-footer');

    if (app && mainContent && footer) {
        // Принудительно устанавливаем правильную структуру
        app.style.display = 'flex';
        app.style.flexDirection = 'column';
        app.style.minHeight = '100vh';

        mainContent.style.flex = '1';
        mainContent.style.minHeight = 'auto';

        footer.style.marginTop = 'auto';
        footer.style.flexShrink = '0';
        footer.style.position = 'relative';

        console.log('🚨 Экстренный фикс применен');
    }
}

// ЗАПУСК ПРИ ЗАГРУЗКЕ ДОКУМЕНТА
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM готов');

    // Небольшая задержка для гарантии что все стили применены
    setTimeout(() => {
        initializeVueApp();
    }, 100);
});

// ЗАПУСК ПРИ ПОЛНОЙ ЗАГРУЗКЕ СТРАНИЦЫ
window.addEventListener('load', function() {
    console.log('🖼️ Страница полностью загружена');

    // Финальная проверка и фикс
    setTimeout(() => {
        fixPageStructure();
        checkFooterPosition();
    }, 1000);
});

// ОБРАБОТЧИК ИЗМЕНЕНИЯ РАЗМЕРА
window.addEventListener('resize', function() {
    setTimeout(checkFooterPosition, 100);
});
