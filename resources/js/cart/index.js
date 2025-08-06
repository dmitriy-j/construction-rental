// Уберите импорт Modal, так как он вызывает проблемы
export function initCart() {
  console.log('Cart module initialization');

  // Переместите все функции выше вызова
  const getSelectedItems = () => {
    return [...document.querySelectorAll('.item-checkbox:checked')].map(el => el.value);
  };

  function initSelectAll() {
    const selectAll = document.getElementById('select-all');
    if (!selectAll) return;

    selectAll.addEventListener('change', function() {
      document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
      });
    });

    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('item-checkbox')) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const allChecked = checkboxes.length > 0 &&
                          [...checkboxes].every(cb => cb.checked);
        selectAll.checked = allChecked;
      }
    });
  }

  function initRemoveSelected() {
    const removeSelectedBtn = document.getElementById('remove-selected');
    if (!removeSelectedBtn) return;

    removeSelectedBtn.addEventListener('click', function() {
      const selected = getSelectedItems();

      if (selected.length === 0) {
        alert('Выберите хотя бы один элемент');
        return;
      }

      if (confirm(`Вы уверены, что хотите удалить ${selected.length} выбранных позиций?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = document.getElementById('cart-data').dataset.removeSelectedRoute;
        form.innerHTML = `
          <input type="hidden" name="_token" value="${document.getElementById('cart-data').dataset.csrfToken}">
          <input type="hidden" name="_method" value="DELETE">
          <input type="hidden" name="items" value="${JSON.stringify(selected)}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    });
  }

  function initPopovers() {
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach(popoverTriggerEl => {
      new bootstrap.Popover(popoverTriggerEl, {
        html: true,
        trigger: 'hover focus'
      });
    });
  }

  // Вызов функций
  initSelectAll();
  initRemoveSelected();
  initPopovers();

  console.log('Cart module initialized');
}
