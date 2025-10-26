/**
 * Оптимизатор мобильной версии
 */
class MobileOptimizer {
    constructor() {
        this.isMobile = window.innerWidth < 992;
        this.init();
    }

    init() {
        this.optimizeNavbar();
        this.optimizeImages();
        this.optimizeTables();
        this.addTouchSupport();
    }

    optimizeNavbar() {
        const navbar = document.querySelector('.navbar');
        if (this.isMobile && navbar) {
            navbar.classList.add('mobile-optimized');
            // Отключаем скрытие навбара на мобильных
            navbar.classList.remove('navbar--hidden');
        }
    }

    optimizeImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
        });
    }

    optimizeTables() {
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }

    addTouchSupport() {
        // Добавляем поддержку касаний для dropdown
        const dropdowns = document.querySelectorAll('.dropdown-toggle');
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('touchstart', function(e) {
                e.preventDefault();
                const dropdownMenu = this.nextElementSibling;
                dropdownMenu.classList.toggle('show');
            });
        });
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    new MobileOptimizer();
});
