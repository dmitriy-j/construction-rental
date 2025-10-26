// resources/js/navbar.js - ОБНОВЛЕННАЯ ВЕРСИЯ С ОТКЛЮЧЕНИЕМ СКРЫТИЯ НА МОБИЛЬНЫХ
export function initSmartNavbar() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;

  // Проверяем, мобильное ли устройство (по ширине экрана)
  const isMobile = window.innerWidth < 992;

  // Если мобильное устройство, то не инициализируем логику скрытия навбара
  if (isMobile) {
    // Убедимся, что навбар видим
    navbar.classList.remove('navbar--hidden', 'navbar--scrolled');
    document.documentElement.style.setProperty('--navbar-height', `${navbar.offsetHeight}px`);
    return;
  }

  // Для десктопов оставляем старую логику
  let lastScrollY = window.scrollY;
  let ticking = false;
  const navbarHeight = navbar.offsetHeight;
  let isDropdownOpen = false;

  // Отслеживаем открытие/закрытие dropdown
  document.addEventListener('show.bs.dropdown', () => {
    isDropdownOpen = true;
    navbar.classList.remove('navbar--hidden');
    document.documentElement.style.setProperty('--navbar-height', `${navbarHeight}px`);
  });

  document.addEventListener('hide.bs.dropdown', () => {
    isDropdownOpen = false;
  });

  const updateNavbarState = () => {
    if (isDropdownOpen) return; // Не скрываем навбар при открытом dropdown

    const scrollY = window.scrollY;

    // Всегда показывать навбар в верхней части страницы
    if (scrollY <= 50) {
      navbar.classList.remove('navbar--hidden', 'navbar--scrolled');
      document.documentElement.style.setProperty('--navbar-height', `${navbarHeight}px`);
      lastScrollY = scrollY;
      ticking = false;
      return;
    }

    // Управление видимостью
    if (scrollY > lastScrollY && scrollY > 100) {
      navbar.classList.add('navbar--hidden');
      document.documentElement.style.setProperty('--navbar-height', '0px');
    } else if (scrollY < lastScrollY) {
      navbar.classList.remove('navbar--hidden');
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
    if (!ticking && !isDropdownOpen) {
      window.requestAnimationFrame(updateNavbarState);
      ticking = true;
    }
  };

  window.addEventListener('scroll', onScroll);

  window.addEventListener('scroll', () => {
    if (window.scrollY === 0) {
      navbar.classList.remove('navbar--hidden', 'navbar--scrolled');
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
