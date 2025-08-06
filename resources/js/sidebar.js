export function initSidebar() {
  const sidebar = document.getElementById('sidebarContainer');
  const minifyBtn = document.getElementById('sidebarMinify');
  const collapseBtn = document.getElementById('sidebarCollapse');
  const mobileToggler = document.getElementById('mobileSidebarToggler');

  if (!sidebar || !minifyBtn) return;

  function updateMinifyIcon(isMini) {
    const icon = minifyBtn.querySelector('i');
    if (icon) {
      icon.classList.toggle('bi-chevron-left', !isMini);
      icon.classList.toggle('bi-chevron-right', isMini);
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

    // Форсируем перерисовку
    setTimeout(() => {
      sidebar.style.display = 'none';
      sidebar.offsetHeight;
      sidebar.style.display = 'flex';
    }, 10);
  });

  if (collapseBtn) {
    collapseBtn.addEventListener('click', () => {
      sidebar.classList.remove('mobile-open');
    });
  }

  if (mobileToggler) {
    mobileToggler.addEventListener('click', () => {
      sidebar.classList.toggle('mobile-open');
    });
  }

  // Закрытие сайдбара при клике вне (мобильные)
  document.addEventListener('click', (e) => {
    if (window.innerWidth < 992 && sidebar.classList.contains('mobile-open')) {
      const isClickInside = sidebar.contains(e.target) ||
                           (mobileToggler && mobileToggler.contains(e.target));

      if (!isClickInside) {
        sidebar.classList.remove('mobile-open');
      }
    }
  });
}
