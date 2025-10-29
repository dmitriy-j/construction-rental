// resources/js/pages/lessor-rental-requests-debug.js
console.log('🚀 DEBUG: lessor-rental-requests.js загружен!');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 DEBUG: DOM загружен');

    const appElement = document.getElementById('lessor-rental-requests-app');
    const fallbackElement = document.getElementById('lessor-html-fallback');

    console.log('🔍 DEBUG: appElement:', appElement);
    console.log('🔍 DEBUG: fallbackElement:', fallbackElement);

    if (appElement) {
        console.log('✅ DEBUG: Элемент найден, Vue должен загрузиться');
        // Принудительно покажем Vue app для теста
        appElement.innerHTML = '<div class="alert alert-success">Vue компонент загружен!</div>';
        appElement.style.display = 'block';
        if (fallbackElement) fallbackElement.style.display = 'none';
    } else {
        console.error('❌ DEBUG: Элемент lessor-rental-requests-app не найден');
    }
});
