export default function initLuxurySidebar() {
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebarContainer');
        const mobileToggler = document.getElementById('mobileSidebarToggler');
        const body = document.body;
        const html = document.documentElement;

        if (!sidebar || !mobileToggler) return;

        // ФИКС: упрощенная инициализация выпадающих меню
        const initDropdowns = () => {
            const dropdownToggles = sidebar.querySelectorAll('.dropdown-toggle');
            dropdownToggles.forEach(toggle => {
                if (!toggle.classList.contains('initialized')) {
                    new bootstrap.Dropdown(toggle);
                    toggle.classList.add('initialized');
                }
            });
        };

        // Обработчик кнопки мобильного меню
        mobileToggler.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });

        // Функция переключения сайдбара
        function toggleSidebar() {
            sidebar.classList.toggle('mobile-open');
            body.classList.toggle('sidebar-open');
            html.classList.toggle('sidebar-open');
        }

        // Закрытие сайдбара при клике вне его
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggler = mobileToggler.contains(event.target);

            if (!isClickInsideSidebar && !isClickOnToggler && sidebar.classList.contains('mobile-open')) {
                closeSidebar();
            }
        });

        // Функция закрытия сайдбара
        function closeSidebar() {
            sidebar.classList.remove('mobile-open');
            body.classList.remove('sidebar-open');
            html.classList.remove('sidebar-open');
        }

        // Инициализация компонентов
        initDropdowns();
    });
}
