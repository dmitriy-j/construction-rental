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
  });

  function updateMinifyIcon(isMini) {
  const icon = minifyBtn.querySelector('i');
  if (icon) {
    icon.classList.toggle('bi-chevron-left', !isMini);
    icon.classList.toggle('bi-chevron-right', isMini);
    // Добавляем анимацию
    icon.style.transition = 'transform 0.3s ease';
    icon.style.transform = isMini ? 'rotate(180deg)' : 'rotate(0deg)';
  }
}
}
