export function initTheme() {
  const themeToggles = document.querySelectorAll('[data-theme-toggle]');
  const html = document.documentElement;

  // CSS-переменные для светлой темы
  const lightTheme = {
    '--primary-500': '#0b5ed7',
    '--primary-600': '#0a50b9',
    '--accent-400': '#00d2ff',
    '--text-primary': '#212529',
    '--text-secondary': '#495057',
    '--bg-surface': '#ffffff',
    '--bg-secondary': '#f8f9fa',
    '--divider': '#e9ecef',
    '--bg-gradient': 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)'
  };

  // CSS-переменные для темной темы
  const darkTheme = {
  '--primary-500': '#4D9DFF',
  '--primary-600': '#2B8CFF',
  '--accent-400': '#00F0FF',
  '--text-primary': '#F5F9FF',
  '--text-secondary': '#E2E8F0',
  '--bg-surface': '#1E293B',
  '--bg-secondary': '#0F172A',
  '--divider': '#475569',
  '--bg-gradient': 'linear-gradient(135deg, #1a1c23 0%, #232630 50%, #1a1c23 100%)'
};

 function applyTheme(theme) {
    const html = document.documentElement;

    // Добавляем плавное переключение
    html.style.transition = 'background-color 0.3s ease, color 0.3s ease';

    // Применяем CSS-переменные
    const themeVars = theme === 'dark' ? darkTheme : lightTheme;
    Object.entries(themeVars).forEach(([key, value]) => {
        document.documentElement.style.setProperty(key, value);
    });

    // Обновляем атрибут темы
    html.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    updateIcons(theme);

    // Убираем transition после применения
    setTimeout(() => {
        html.style.transition = '';
    }, 300);
}

  function updateIcons(theme) {
    themeToggles.forEach(toggle => {
      const icon = toggle.querySelector('i');
      if (!icon) return;

      if (theme === 'dark') {
        icon.classList.remove('bi-sun-fill');
        icon.classList.add('bi-moon-fill');
      } else {
        icon.classList.remove('bi-moon-fill');
        icon.classList.add('bi-sun-fill');
      }
    });
  }

  // Определяем начальную тему
  function getInitialTheme() {
    // 1. Проверяем сохраненные предпочтения
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) return savedTheme;

    // 2. Проверяем системные предпочтения
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) return 'dark';

    // 3. По умолчанию - светлая тема
    return 'light';
  }

  // Инициализируем тему
  const initialTheme = getInitialTheme();
  applyTheme(initialTheme);

  // Обработчики для переключателей темы
  themeToggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
      const currentTheme = html.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      applyTheme(newTheme);
    });
  });

  // Следим за изменениями системных предпочтений
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    // Меняем тему только если пользователь не сделал явный выбор
    if (!localStorage.getItem('theme')) {
      applyTheme(e.matches ? 'dark' : 'light');
    }
  });
}
