document.addEventListener('DOMContentLoaded', function() {
    const cartData = document.getElementById('cart-data');
    if (!cartData) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const removeSelectedRoute = cartData.dataset.removeSelectedRoute;

    // 1. Обработка "Выбрать все"
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                selectAll.checked = [...checkboxes].every(cb => cb.checked);
            });
        });
    }

    // 2. Удаление выбранных с модальным окном
    const removeSelectedBtn = document.getElementById('remove-selected');
    if (removeSelectedBtn) {
        removeSelectedBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                .map(checkbox => checkbox.value);

            if (selected.length === 0) {
                alert('Выберите хотя бы один элемент');
                return;
            }

            // Создаем модальное окно
            const modalHtml = `
                <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="confirmationModalLabel">Подтверждение удаления</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Вы уверены, что хотите удалить ${selected.length} выбранных позиций?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="button" class="btn btn-danger" id="confirm-delete">Удалить</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Добавляем модальное окно в DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();

            // Обработчик подтверждения удаления
            document.getElementById('confirm-delete').addEventListener('click', async function() {
                modal.hide();

                try {
                    const response = await fetch(removeSelectedRoute, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ items: selected })
                    });

                    const data = await response.json();

                    if (data.success) {
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Server error');
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                    alert(`Ошибка: ${error.message}`);
                }

                // Удаляем модальное окно из DOM после использования
                document.getElementById('confirmationModal').remove();
            });

            // Удаляем модальное окно при закрытии
            document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        });
    }

    // 3. Обновление дат
    const dateForm = document.getElementById('bulk-form');
    if (dateForm) {
        dateForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const selectedItems = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                .map(checkbox => checkbox.value);

            const formData = new FormData(this);
            formData.append('selected_items', JSON.stringify(selectedItems));

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (response.ok) {
                    location.reload();
                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Ошибка обновления дат');
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert(`Ошибка: ${error.message}`);
            }
        });
    }
});
