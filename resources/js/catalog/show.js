// Функция для переключения видимости блока
window.toggleBlock = function(blockId, isVisible) {
    const block = document.getElementById(blockId);
    if (block) {
        block.style.display = isVisible ? 'block' : 'none';
        // Добавляем анимацию
        block.style.opacity = isVisible ? '0' : '1';
        block.style.transition = 'opacity 0.3s ease-in-out';

        if (isVisible) {
            setTimeout(() => {
                block.style.opacity = '1';
            }, 10);
        }
    }
}

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // Обработчики для условий аренды
    document.querySelectorAll('[id^="use_default_conditions_"]').forEach(checkbox => {
        const termId = checkbox.id.split('_').pop();
        const customBlockId = `custom-conditions_${termId}`;

        // Инициализация начального состояния
        toggleBlock(customBlockId, !checkbox.checked);

        // Обработчик изменений
        checkbox.addEventListener('change', function() {
            toggleBlock(customBlockId, !this.checked);
        });
    });

    // Обработчики для доставки
    document.querySelectorAll('[id^="delivery_required_"]').forEach(checkbox => {
        const termId = checkbox.id.split('_').pop();
        const deliveryBlockId = `deliveryFields_${termId}`;

        // Инициализация начального состояния
        toggleBlock(deliveryBlockId, checkbox.checked);

        // Обработчик изменений
        checkbox.addEventListener('change', function() {
            toggleBlock(deliveryBlockId, this.checked);
        });
    });
});
