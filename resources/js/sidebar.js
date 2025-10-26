// resources/js/sidebar.js
export function initSidebar() {
    const sidebar = document.getElementById('sidebarContainer');
    if (!sidebar) {
        console.log('❌ Blade сайдбар не найден');
        return;
    }

    console.log('✅ Инициализируем Blade сайдбар');

    const calculateHeight = () => {
        const navbarHeight = document.querySelector('.navbar')?.offsetHeight || 0;
        const isMobile = window.innerWidth < 992;

        sidebar.style.height = isMobile
            ? '100vh'
            : `calc(100vh - ${navbarHeight}px)`;

        sidebar.style.top = isMobile ? '0' : `${navbarHeight}px`;
    };

    calculateHeight();
    window.addEventListener('resize', calculateHeight);

    // Минимизация сайдбара
    const minifyBtn = document.getElementById('sidebarMinify');
    if (minifyBtn) {
        function updateMinifyIcon(isMini) {
            const icon = minifyBtn.querySelector('i');
            if (icon) {
                icon.style.transition = 'transform 0.3s ease';
                icon.style.transform = isMini ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }

        const isMini = localStorage.getItem('sidebarMini') === 'true';
        if (isMini) {
            sidebar.classList.add('sidebar-mini');
            updateMinifyIcon(true);
        }

        minifyBtn.addEventListener('click', () => {
            const isNowMini = !sidebar.classList.contains('sidebar-mini');
            sidebar.classList.toggle('sidebar-mini', isNowMini);
            localStorage.setItem('sidebarMini', isNowMini);
            updateMinifyIcon(isNowMini);
        });
    }

    // Закрытие на мобильных
    const collapseBtn = document.getElementById('sidebarCollapse');
    if (collapseBtn) {
        collapseBtn.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
        });
    }
}
