export function initCart() {
  console.log('Cart module initialization');

  // Функция для получения выбранных элементов
  const getSelectedItems = () => {
    return [...document.querySelectorAll('.item-checkbox:checked')].map(el => el.value);
  };

  // Функция обновления скрытых полей
  function updateSelectedItems() {
    const selectedItems = getSelectedItems();
    const selectedItemsJSON = JSON.stringify(selectedItems);

    // Обновляем поле в форме оформления заказа
    const checkoutInput = document.getElementById('selected-items');
    if (checkoutInput) {
      checkoutInput.value = selectedItemsJSON;
    }

    // Обновляем поле в форме массовых действий
    const bulkFormInput = document.getElementById('selected-items-input');
    if (bulkFormInput) {
      bulkFormInput.value = selectedItemsJSON;
    }
  }

  // Инициализация "Выбрать все"
  function initSelectAll() {
    const selectAll = document.getElementById('select-all');
    if (!selectAll) return;

    selectAll.addEventListener('change', function() {
      document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
      });
      updateSelectedItems(); // Обновляем скрытые поля
    });

    // Обработчик изменений для отдельных чекбоксов
    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('item-checkbox')) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const allChecked = [...checkboxes].every(cb => cb.checked);
        selectAll.checked = allChecked;
        updateSelectedItems(); // Обновляем скрытые поля
      }
    });
  }

  // Инициализация кнопки удаления
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
          <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
          <input type="hidden" name="_method" value="DELETE">
          <input type="hidden" name="items" value="${JSON.stringify(selected)}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    });
  }

  // Добавьте вызов новых функций
  initSelectAll();
  initRemoveSelected();
  updateSelectedItems(); // Инициализация при загрузке

  console.log('Cart module initialized');
}
