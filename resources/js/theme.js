export function initTheme() {
  const themeToggles = document.querySelectorAll('[data-theme-toggle]');
  const html = document.documentElement;

  function setTheme(theme) {
    html.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    updateIcons(theme);
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

  const savedTheme = localStorage.getItem('theme') || 'light';
  setTheme(savedTheme);

  themeToggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
      const currentTheme = html.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      setTheme(newTheme);
    });
  });
}
