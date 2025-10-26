export function initCatalog() {
  console.log('Initializing catalog show page');

  // Функция для переключения блоков
  const toggleBlock = (block, show) => {
    if (!block) return;
    block.style.display = show ? 'block' : 'none';
  };

  // Обработчик изменений с защитой от AlpineJS
  const handleCheckboxChange = (e) => {
    e.stopImmediatePropagation();

    // Для условий аренды
    if (e.target.matches('.use-default-conditions')) {
      const termId = e.target.dataset.termId;
      const block = document.getElementById(`custom-conditions-${termId}`);
      toggleBlock(block, !e.target.checked);
    }

    // Для доставки
    if (e.target.matches('.delivery-toggle')) {
      const termId = e.target.dataset.termId;
      const block = document.getElementById(`deliveryFields-${termId}`);
      toggleBlock(block, e.target.checked);
    }
  };

  // Вешаем обработчик с высоким приоритетом
  document.addEventListener('change', handleCheckboxChange, true);

  // Инициализация начального состояния
  document.querySelectorAll('.use-default-conditions').forEach(checkbox => {
    const termId = checkbox.dataset.termId;
    const block = document.getElementById(`custom-conditions-${termId}`);
    toggleBlock(block, !checkbox.checked);
  });

  document.querySelectorAll('.delivery-toggle').forEach(checkbox => {
    const termId = checkbox.dataset.termId;
    const block = document.getElementById(`deliveryFields-${termId}`);
    toggleBlock(block, checkbox.checked);
  });

  console.log('Catalog initialized');
}
