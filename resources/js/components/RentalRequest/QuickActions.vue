<template>
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">Быстрые действия</h6>
        </div>
        <div class="card-body">
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary btn-sm" @click="createSimilar">
                    <i class="fas fa-copy me-2"></i>Создать похожую заявку
                </button>

                <button
                    class="btn btn-outline-secondary btn-sm"
                    @click="exportToPDF"
                    :disabled="isExporting"
                >
                    <i class="fas fa-download me-2"></i>
                    {{ isExporting ? 'Экспорт...' : 'Экспорт в PDF' }}
                </button>

                <button class="btn btn-outline-secondary btn-sm" @click="shareRequest">
                    <i class="fas fa-share-alt me-2"></i>Поделиться заявкой
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'QuickActions',
    props: {
        requestId: {
            type: [String, Number],
            required: true
        }
    },
    data() {
        return {
            isExporting: false
        }
    },
    methods: {
        createSimilar() {
            window.location.href = `/lessee/rental-requests/create?copy_from=${this.requestId}`;
        },

        // ⚠️ МЕТОД БЕЗ SWEETALERT2 - используем нативные уведомления
        async exportToPDF() {
            if (this.isExporting) return;

            this.isExporting = true;
            console.log('🚀 Starting PDF export for request:', this.requestId);

            try {
                // 1. Показываем простое уведомление
                this.showNotification('Экспорт в PDF', 'Подготовка документа...', 'info');

                // 2. Выполняем запрос
                const response = await axios.get(
                    `/api/lessee/rental-requests/${this.requestId}/export-pdf`,
                    {
                        responseType: 'blob',
                        timeout: 30000
                    }
                );

                console.log('📄 PDF response received:', {
                    status: response.status,
                    size: response.data.size,
                    type: response.data.type
                });

                // 3. Скачиваем файл
                const blob = new Blob([response.data], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `rental-request-${this.requestId}.pdf`;
                document.body.appendChild(link);
                link.click();

                // Очистка
                setTimeout(() => {
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                }, 1000);

                // 4. Показываем успех
                this.showNotification('Успех!', 'PDF документ успешно скачан', 'success', 3000);

                console.log('✅ PDF export completed successfully');

            } catch (error) {
                console.error('❌ PDF export error:', error);

                let errorMessage = 'Не удалось скачать PDF. Попробуйте еще раз.';

                if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
                    errorMessage = 'Время ожидания истекло. PDF слишком большой или сервер перегружен.';
                } else if (error.response?.status === 500) {
                    errorMessage = 'Ошибка сервера при генерации PDF.';
                } else if (error.response?.status === 404) {
                    errorMessage = 'Функция экспорта PDF недоступна.';
                }

                this.showNotification('Ошибка', errorMessage, 'error');

            } finally {
                this.isExporting = false;
            }
        },

        // ⚠️ УНИВЕРСАЛЬНЫЙ МЕТОД ДЛЯ УВЕДОМЛЕНИЙ
        showNotification(title, message, type = 'info', duration = 0) {
            // Создаем кастомное уведомление
            const notification = document.createElement('div');
            notification.className = `custom-notification custom-notification-${type}`;
            notification.innerHTML = `
                <div class="custom-notification-content">
                    <div class="custom-notification-icon">${this.getIcon(type)}</div>
                    <div class="custom-notification-text">
                        <div class="custom-notification-title">${title}</div>
                        <div class="custom-notification-message">${message}</div>
                    </div>
                    <button class="custom-notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;

            // Стили для уведомления
            const style = document.createElement('style');
            style.textContent = `
                .custom-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border-left: 4px solid #007bff;
                    z-index: 10000;
                    min-width: 300px;
                    max-width: 400px;
                    animation: slideIn 0.3s ease-out;
                }
                .custom-notification-success {
                    border-left-color: #28a745;
                }
                .custom-notification-error {
                    border-left-color: #dc3545;
                }
                .custom-notification-warning {
                    border-left-color: #ffc107;
                }
                .custom-notification-info {
                    border-left-color: #17a2b8;
                }
                .custom-notification-content {
                    display: flex;
                    align-items: center;
                    padding: 16px;
                    position: relative;
                }
                .custom-notification-icon {
                    font-size: 20px;
                    margin-right: 12px;
                }
                .custom-notification-text {
                    flex: 1;
                }
                .custom-notification-title {
                    font-weight: bold;
                    margin-bottom: 4px;
                }
                .custom-notification-message {
                    color: #666;
                    font-size: 14px;
                }
                .custom-notification-close {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: #999;
                    margin-left: 10px;
                }
                .custom-notification-close:hover {
                    color: #666;
                }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;

            // Добавляем стили, если их еще нет
            if (!document.querySelector('#custom-notification-styles')) {
                style.id = 'custom-notification-styles';
                document.head.appendChild(style);
            }

            // Добавляем уведомление на страницу
            document.body.appendChild(notification);

            // Авто-закрытие если указана длительность
            if (duration > 0) {
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, duration);
            }
        },

        getIcon(type) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            return icons[type] || 'ℹ️';
        },

        // ⚠️ АЛЬТЕРНАТИВНЫЙ МЕТОД С NATIVE ALERT (для тестирования)
        async exportToPDFWithAlert() {
            if (this.isExporting) return;

            this.isExporting = true;

            try {
                // Показываем alert вместо Swal
                alert('Начинаем экспорт PDF...');

                const response = await axios.get(
                    `/api/lessee/rental-requests/${this.requestId}/export-pdf`,
                    { responseType: 'blob' }
                );

                // Скачивание
                const blobUrl = URL.createObjectURL(response.data);
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = `rental-request-${this.requestId}.pdf`;
                link.click();

                // Очистка
                setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);

                // Успех через alert
                alert('PDF успешно скачан!');

            } catch (error) {
                console.error('PDF export error:', error);
                alert('Ошибка: ' + error.message);
            } finally {
                this.isExporting = false;
            }
        },

        async shareRequest() {
            try {
                if (navigator.share) {
                    await navigator.share({
                        title: 'Заявка на аренду техники',
                        text: 'Посмотрите эту заявку на аренду строительной техники',
                        url: window.location.href
                    });
                    this.showNotification('Успешно!', 'Заявка успешно отправлена', 'success', 3000);
                } else {
                    await navigator.clipboard.writeText(window.location.href);
                    this.showNotification('Скопировано!', 'Ссылка скопирована в буфер обмена', 'success', 3000);
                }
            } catch (error) {
                console.error('Ошибка при попытке поделиться:', error);
                if (error.name !== 'AbortError') {
                    this.showNotification('Ошибка', 'Не удалось поделиться заявкой', 'error');
                }
            }
        }
    }
}
</script>

<style scoped>
.card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    padding: 0.75rem 1.25rem;
}

.card-title {
    color: #5a5c69;
    font-size: 0.9rem;
    font-weight: 600;
}

.card-body {
    padding: 1.25rem;
}

.btn {
    font-size: 0.85rem;
    padding: 0.375rem 0.75rem;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
