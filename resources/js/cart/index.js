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

    const checkoutInput = document.getElementById('selected-items');
    if (checkoutInput) {
      checkoutInput.value = selectedItemsJSON;
    }

    const bulkFormInput = document.getElementById('selected-items-input');
    if (bulkFormInput) {
      bulkFormInput.value = selectedItemsJSON;
    }
  }

  function initSelectAll() {
    const selectAll = document.getElementById('select-all');
    if (!selectAll) return;

    selectAll.addEventListener('change', function() {
      document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
      });
      updateSelectedItems();
    });

    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('item-checkbox')) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const allChecked = [...checkboxes].every(cb => cb.checked);
        selectAll.checked = allChecked;
        updateSelectedItems();
      }
    });
  }

  function initRemoveSelected() {
    const removeSelectedBtn = document.getElementById('remove-selected');
    if (!removeSelectedBtn) return;

    removeSelectedBtn.setAttribute('type', 'button');

    removeSelectedBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      const selected = getSelectedItems();

      if (selected.length === 0) {
        alert('Выберите хотя бы один элемент');
        return;
      }

      if (!confirm(`Удалить ${selected.length} выбранных позиций?`)) return;

      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      let success = true;

      for (const id of selected) {
        try {
          const res = await fetch('/api/cart/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
          });
          const data = await res.json();
          if (!data.success) {
            console.error('Failed to delete item', id, data);
            success = false;
          }
        } catch(e) {
          console.error('Error deleting item', id, e);
          success = false;
        }
      }

      if (success) {
        alert('Позиции удалены');
      } else {
        alert('Некоторые позиции не удалились (см. консоль)');
      }
      location.reload();
    });
  }

  initSelectAll();
  initRemoveSelected();
  updateSelectedItems();

  console.log('Cart module initialized');
}
