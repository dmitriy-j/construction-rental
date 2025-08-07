export function initSmartNavbar() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;

  let lastScrollY = window.scrollY;
  let ticking = false;
  const navbarHeight = navbar.offsetHeight;

  const updateNavbarState = () => {
    const scrollY = window.scrollY;

    // Всегда показывать навбар в верхней части страницы
    if (scrollY <= 50) {
      navbar.classList.remove('navbar--hidden', 'navbar--scrolled');
      navbar.style.transform = 'translateY(0)';
      navbar.style.opacity = '1';
      document.documentElement.style.setProperty('--navbar-height', `${navbarHeight}px`);
      lastScrollY = scrollY;
      ticking = false;
      return;
    }

    // Управление видимостью
    if (scrollY > lastScrollY && scrollY > 100) {
      navbar.classList.add('navbar--hidden');
      navbar.style.transform = 'translateY(-100%)';
      document.documentElement.style.setProperty('--navbar-height', '0px');
    } else if (scrollY < lastScrollY) {
      navbar.classList.remove('navbar--hidden');
      navbar.style.transform = 'translateY(0)';
      navbar.style.opacity = '1';
      document.documentElement.style.setProperty('--navbar-height', `${navbarHeight}px`);
    }

    // Эффект при скролле
    if (scrollY > 50) {
      navbar.classList.add('navbar--scrolled');
    } else {
      navbar.classList.remove('navbar--scrolled');
    }

    lastScrollY = scrollY;
    ticking = false;
  };

  const onScroll = () => {
    if (!ticking) {
      window.requestAnimationFrame(updateNavbarState);
      ticking = true;
    }
  };

  window.addEventListener('scroll', onScroll);

  window.addEventListener('scroll', () => {
    if (window.scrollY === 0) {
      navbar.classList.remove('navbar--hidden', 'navbar--scrolled');
      navbar.style.transform = 'translateY(0)';
      navbar.style.opacity = '1';
      document.documentElement.style.setProperty('--navbar-height', `${navbarHeight}px`);
    }
  });

  window.addEventListener('resize', () => {
    if (!navbar.classList.contains('navbar--hidden')) {
      document.documentElement.style.setProperty('--navbar-height', `${navbar.offsetHeight}px`);
    }
  });

  // Инициализация состояния
  updateNavbarState();
}
