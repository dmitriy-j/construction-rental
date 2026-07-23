// resources/js/navbar.js — ПЕРЕПИСАНА: навбар всегда виден, glassmorphism эффект
export function initSmartNavbar() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;

  // Убираем классы скрытия, если они были
  navbar.classList.remove('navbar--hidden', 'navbar--scrolled');

  // Фиксируем высоту навбара — она не должна меняться
  const updateHeight = () => {
    const height = navbar.offsetHeight;
    document.documentElement.style.setProperty('--navbar-height', `${height}px`);
  };

  updateHeight();
  window.addEventListener('resize', updateHeight);

  // Только эффект "прокручено" — добавляем тень при скролле
  const onScroll = () => {
    if (window.scrollY > 10) {
      navbar.classList.add('navbar--scrolled');
    } else {
      navbar.classList.remove('navbar--scrolled');
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll(); // инициализация
}
