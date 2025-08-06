import { initRipple } from './ripple';
import { initTheme } from './theme';
import { initSidebar } from './sidebar';

// Функция для инициализации каталога
function initCatalog() {
  document.addEventListener('change', function(e) {
    if (e.target.classList.contains('delivery-toggle')) {
      const termId = e.target.dataset.termId;
      const block = document.getElementById(`deliveryFields_${termId}`);
      if (block) block.style.display = e.target.checked ? 'block' : 'none';
    }

    if (e.target.classList.contains('use-default-conditions')) {
      const termId = e.target.dataset.termId;
      const block = document.getElementById(`custom-conditions_${termId}`);
      if (block) block.style.display = e.target.checked ? 'none' : 'block';
    }
  });
}

// Функция для инициализации корзины
function initCart() {
  console.log('Cart initialization started');

  // Функция для получения выбранных элементов
  const getSelectedItems = () => {
    return Array.from(document.querySelectorAll('.item-checkbox:checked'))
      .map(el => el.value);
  };

  // Инициализация "Выбрать все"
  const selectAll = document.getElementById('select-all');
  if (selectAll) {
    selectAll.addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.item-checkbox');
      checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
      });
    });
  }

  // Инициализация кнопки удаления с SweetAlert
  const removeSelectedBtn = document.getElementById('remove-selected');
  if (removeSelectedBtn) {
    removeSelectedBtn.addEventListener('click', async function() {
      const selected = getSelectedItems();
      if (selected.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Ошибка',
          text: 'Выберите хотя бы один элемент',
        });
        return;
      }

      // Подтверждение через SweetAlert
      const result = await Swal.fire({
        title: 'Вы уверены?',
        text: `Вы собираетесь удалить ${selected.length} выбранных позиций`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Да',
        cancelButtonText: 'Отмена'
      });

      if (result.isConfirmed) {
        try {
          const response = await fetch(document.getElementById('cart-data').dataset.removeSelectedRoute, {
            method: 'DELETE',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ items: selected })
          });

          const data = await response.json();

          if (response.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Успешно!',
              text: data.message || 'Позиции успешно удалены',
            }).then(() => {
              location.reload(); // Перезагружаем страницу
            });
          } else {
            throw new Error(data.message || 'Ошибка при удалении');
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Ошибка',
            text: error.message || 'Не удалось удалить позиции',
          });
        }
      }
    });
  }

  // Инициализация popovers
  const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
  popoverTriggerList.forEach(popoverTriggerEl => {
    if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
      const content = `
        <div><strong>От:</strong> ${popoverTriggerEl.dataset.deliveryFrom || 'N/A'}</div>
        <div><strong>До:</strong> ${popoverTriggerEl.dataset.deliveryTo || 'N/A'}</div>
        <div class="mt-2"><strong>Стоимость:</strong> ${popoverTriggerEl.dataset.deliveryCost || '0'} ₽</div>
      `;

      new bootstrap.Popover(popoverTriggerEl, {
        html: true,
        trigger: 'hover focus',
        title: 'Детали доставки',
        content: content
      });
    }
  });

  console.log('Cart initialization complete');
}

document.addEventListener('DOMContentLoaded', () => {
  console.log('DOMContentLoaded');

  initRipple();
  initTheme();
  initSidebar();

  if (document.querySelector('.catalog-show-page')) {
    console.log('Initializing catalog');
    initCatalog();
  }

  if (document.getElementById('cart-data')) {
    console.log('Initializing cart');
    initCart();
  }
});

