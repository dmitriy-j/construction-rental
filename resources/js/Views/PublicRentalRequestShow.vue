<template>
    <div class="public-rental-request-show" v-if="request">
        <!-- Заголовок и навигация -->
        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-12">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title">Публичная заявка: {{ request.title }}</h1>
                        <div>
                            <a href="/rental-requests" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Назад к списку
                            </a>
                            <button v-if="isAuthenticatedLessor && canMakeProposal"
                                    class="btn btn-primary"
                                    @click="showProposalModal">
                                <i class="fas fa-paper-plane me-2"></i>Предложить технику
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Статус и метрики -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="public-stats-card card">
                        <div class="card-body">
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-value">{{ summary.total_items }}</div>
                                    <div class="stat-label">Позиций</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">{{ summary.total_quantity }}</div>
                                    <div class="stat-label">Единиц техники</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">{{ summary.categories_count }}</div>
                                    <div class="stat-label">Категорий</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">{{ request.active_proposals_count || 0 }}</div>
                                    <div class="stat-label">Предложений</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Основная информация -->
                <div class="col-lg-8">
                    <!-- Карточка основной информации -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Основная информация
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <label class="text-muted small">Описание проекта</label>
                                        <p class="mb-0">{{ request.description }}</p>
                                    </div>

                                    <div class="info-item mb-3">
                                        <label class="text-muted small">Локация объекта</label>
                                        <p class="mb-0">
                                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                            {{ request.location?.name || 'Не указана' }}
                                            <br>
                                            <small class="text-muted">{{ request.location?.address || '' }}</small>
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <label class="text-muted small">Период аренды</label>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                                            {{ request.rental_period_display || 'Период не указан' }}
                                            <br>
                                            <small class="text-muted" v-if="request.rental_days">
                                                {{ request.rental_days }} дней
                                            </small>
                                        </p>
                                    </div>

                                    <!-- Бюджет только для арендодателей -->
                                    <div class="info-item mb-3" v-if="isAuthenticatedLessor">
                                        <label class="text-muted small">Бюджет заявки</label>
                                        <p class="mb-0 fs-5 text-success fw-bold">
                                            {{ formatCurrency(request.total_budget || 0) }}
                                        </p>
                                        <small class="text-muted">
                                            Ставка: до {{ formatCurrency(request.max_hourly_rate || request.hourly_rate || 0) }}/час
                                        </small>
                                    </div>

                                    <div class="info-item mb-3" v-else>
                                        <label class="text-muted small">Бюджет</label>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-lock me-2"></i>
                                            Войдите как арендодатель для просмотра бюджета
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Условия аренды -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clipboard-list me-2"></i>Условия аренды
                            </h5>
                        </div>
                        <div class="card-body">
                            <PublicRentalConditionsDisplay
                                :conditions="request.rental_conditions"
                                :show-full="isAuthenticatedLessor"
                            />
                        </div>
                    </div>

                    <!-- Технические требования -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>Технические требования
                                <span class="badge bg-primary ms-2">{{ groupedByCategory.length }} категорий</span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="categories-list">
                                <!-- Используем упрощенный вариант с CategoryGroup -->
                                <PublicCategoryGroup
                                    v-for="category in groupedByCategory"
                                    :key="category.category_id"
                                    :category="category"
                                    :initially-expanded="true"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Боковая панель -->
                <div class="col-lg-4">
                    <!-- Статус заявки -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Статус заявки</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <span class="badge me-2" :class="getStatusBadgeClass(request.status)">
                                    {{ getStatusDisplayText(request.status) }}
                                </span>
                                <small class="text-muted">
                                    Опубликована {{ formatDate(request.created_at) }}
                                </small>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i>
                                    {{ request.views_count || 0 }} просмотров
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Действия для арендодателя -->
                    <div class="card mb-4" v-if="isAuthenticatedLessor">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Ваши действия</h6>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary w-100 mb-2"
                                    @click="showProposalModal"
                                    :disabled="!canMakeProposal">
                                <i class="fas fa-paper-plane me-2"></i>
                                Предложить технику
                            </button>

                            <button class="btn btn-outline-secondary w-100"
                                    @click="addToFavorites">
                                <i class="fas fa-star me-2"></i>
                                В избранное
                            </button>
                        </div>
                    </div>

                    <!-- Призыв к действию для гостей -->
                    <div class="card mb-4" v-else>
                        <div class="card-header">
                            <h6 class="card-title mb-0">Хотите предложить технику?</h6>
                        </div>
                        <div class="card-body text-center">
                            <p class="small text-muted mb-3">
                                Зарегистрируйтесь как арендодатель для доступа к полной информации и возможности делать предложения
                            </p>
                            <a href="/register?type=lessor" class="btn btn-primary w-100 mb-2">
                                Зарегистрироваться
                            </a>
                            <a href="/login" class="btn btn-outline-primary w-100">
                                Войти
                            </a>
                        </div>
                    </div>

                    <!-- Контактная информация (только для арендодателей) -->
                    <div class="card" v-if="isAuthenticatedLessor && request.company">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Контактная информация</h6>
                        </div>
                        <div class="card-body">
                            <div class="contact-info">
                                <p class="mb-2">
                                    <strong>{{ request.company.legal_name }}</strong>
                                </p>
                                <p class="small text-muted mb-1">
                                    <i class="fas fa-user me-2"></i>
                                    {{ request.user?.name || 'Контактное лицо' }}
                                </p>
                                <p class="small text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    {{ request.company.legal_address }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно предложения -->
        <PublicProposalModal
            v-if="showProposalModal"
            :request="request"
            @close="showProposalModal = false"
            @proposal-created="onProposalCreated"
        />
    </div>

    <!-- Загрузка -->
    <div v-else-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Загрузка...</span>
        </div>
        <p class="mt-2">Загрузка заявки...</p>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="alert alert-danger text-center">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ error }}
        <br>
        <button class="btn btn-outline-danger btn-sm mt-2" @click="loadRequest">
            Попробовать снова
        </button>
    </div>
</template>

<script>
import PublicProposalModal from '../components/Public/PublicProposalModal.vue';
import PublicRentalConditionsDisplay from '../components/Public/PublicRentalConditionsDisplay.vue';
import PublicCategoryGroup from '../components/Public/PublicCategoryGroup.vue';

export default {
    name: 'PublicRentalRequestShow',
    components: {
        PublicProposalModal,
        PublicRentalConditionsDisplay,
        PublicCategoryGroup
    },
    data() {
        return {
            loading: true,
            error: null,
            request: null,
            showProposalModal: false,
            currentUser: null,
            groupedByCategory: [],
            summary: {
                total_items: 0,
                total_quantity: 0,
                categories_count: 0
            }
        }
    },
    computed: {
        isAuthenticatedLessor() {
            return this.currentUser && this.currentUser.is_lessor;
        },
        canMakeProposal() {
            if (!this.isAuthenticatedLessor) return false;
            if (!this.request) return false;

            // Проверяем, что заявка активна и не истекла
            const isActive = this.request.status === 'active';
            const notExpired = !this.request.expires_at || new Date(this.request.expires_at) > new Date();

            return isActive && notExpired;
        }
    },
    methods: {
        async loadUser() {
            try {
                const response = await fetch('/api/user', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                if (response.ok) {
                    this.currentUser = await response.json();
                }
            } catch (error) {
                console.error('Ошибка загрузки пользователя:', error);
                this.currentUser = null;
            }
        },

        debugRequestData() {
            console.log('🔍 Отладочная информация о заявке:', {
                id: this.request?.id,
                rental_period_start: this.request?.rental_period_start,
                rental_period_end: this.request?.rental_period_end,
                rental_period: this.request?.rental_period, // проверяем если есть объект
                raw_request: this.request
            });
        },

         async loadRequest() {
            this.loading = true;
            this.error = null;

            try {
                const requestId = this.getRequestIdFromUrl();
                const apiUrl = `/api/public/rental-requests/${requestId}`;

                console.log('🔄 Загрузка публичной заявки:', apiUrl);

                const response = await fetch(apiUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ошибка! Статус: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.request = data.data;

                    // Детальная отладка
                    this.debugRequestData();

                    // Обрабатываем данные для отображения
                    this.processRequestData();

                    console.log('✅ Публичная заявка загружена:', this.request);
                } else {
                    throw new Error(data.message || 'Ошибка загрузки заявки');
                }
            } catch (error) {
                console.error('❌ Ошибка загрузки публичной заявки:', error);
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        getRequestIdFromUrl() {
            // Получаем ID заявки из URL
            const path = window.location.pathname;
            const matches = path.match(/\/public\/rental-requests\/(\d+)/);
            return matches ? matches[1] : null;
        },

         processRequestData() {
            if (!this.request) return;

            console.log('🔍 Данные заявки для обработки:', this.request);

            // Обрабатываем период аренды
            const rentalPeriodDisplay = this.getRentalPeriodDisplay(
                this.request.rental_period_start || this.request.rental_period?.start,
                this.request.rental_period_end || this.request.rental_period?.end
            );

            const rentalDays = this.calculateRentalDays(
                this.request.rental_period_start || this.request.rental_period?.start,
                this.request.rental_period_end || this.request.rental_period?.end
            );

            this.request.rental_period_display = rentalPeriodDisplay;
            this.request.rental_days = rentalDays;

            // Обрабатываем позиции заявки
            const items = this.request.items || [];
            console.log('📦 Позиции заявки:', items);

            // ИСПРАВЛЕНИЕ: считаем количество уникальных категорий по строке
            const uniqueCategories = new Set(items.map(item => item.category || 'Без категории'));

            this.summary = {
                total_items: items.length,
                total_quantity: items.reduce((sum, item) => sum + (item.quantity || 0), 0),
                categories_count: uniqueCategories.size
            };

            this.groupedByCategory = this.groupItemsByCategory(items);
            console.log('🗂 Сгруппированные категории:', this.groupedByCategory);
        },

         getRentalPeriodDisplay(startDate, endDate) {
            console.log('📅 Получены даты:', { startDate, endDate });

            if (!startDate || !endDate) {
                return 'Период не указан';
            }

            try {
                const start = this.formatDate(startDate);
                const end = this.formatDate(endDate);
                return `${start} - ${end}`;
            } catch (error) {
                console.error('Ошибка форматирования периода аренды:', error, { startDate, endDate });
                return 'Ошибка даты';
            }
        },

        groupItemsByCategory(items) {
            console.log('🔄 Начинаем группировку items по категориям:', items);

            if (!items || !Array.isArray(items) || items.length === 0) {
                console.warn('❌ Нет items для группировки');
                return [];
            }

            const grouped = {};

            items.forEach((item, index) => {
                console.log(`📋 Обрабатываем item ${index + 1}:`, item);

                // ИСПРАВЛЕНИЕ: используем строку category вместо category_id
                const categoryName = item.category || 'Без категории';
                const categoryKey = categoryName; // Используем имя категории как ключ

                if (!grouped[categoryKey]) {
                    grouped[categoryKey] = {
                        category_id: categoryKey, // Используем имя как ID для группировки
                        category_name: categoryName,
                        items: [],
                        total_quantity: 0,
                        items_count: 0
                    };
                    console.log(`✅ Создана новая группа категории: ${categoryName}`);
                }

                grouped[categoryKey].items.push(item);
                grouped[categoryKey].total_quantity += item.quantity || 0;
                grouped[categoryKey].items_count += 1;

                console.log(`📥 Добавлен item в категорию "${categoryName}":`, item);
            });

            const result = Object.values(grouped);
            console.log('🎯 Результат группировки:', result);
            return result;
        },

        getStatusBadgeClass(status) {
            const classes = {
                'active': 'bg-success',
                'paused': 'bg-warning',
                'processing': 'bg-warning',
                'completed': 'bg-primary',
                'cancelled': 'bg-danger'
            };
            return classes[status] || 'bg-light';
        },

        getStatusDisplayText(status) {
            const texts = {
                'active': 'Активна',
                'paused': 'Приостановлена',
                'processing': 'В обработке',
                'completed': 'Завершена',
                'cancelled': 'Отменена'
            };
            return texts[status] || status;
        },

        openProposalModal() {
            if (!this.canMakeProposal) {
                this.redirectToLogin();
                return;
            }
            this.openProposalModal = true;
        },

        onProposalCreated() {
            this.openProposalModal = false;
            this.showToast('success', 'Предложение успешно отправлено!');
            // Перезагружаем данные для обновления счетчика предложений
            this.loadRequest();
        },

        addToFavorites() {
            // TODO: Реализовать добавление в избранное
            this.showToast('info', 'Добавлено в избранное');
        },

        redirectToLogin() {
            window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            try {
                return new Date(dateString).toLocaleDateString('ru-RU');
            } catch (error) {
                console.error('Ошибка форматирования даты:', error, dateString);
                return '—';
            }
        },

        formatCurrency(amount) {
            if (!amount && amount !== 0) return '0 ₽';
            try {
                return new Intl.NumberFormat('ru-RU', {
                    style: 'currency',
                    currency: 'RUB',
                    minimumFractionDigits: 0
                }).format(amount);
            } catch (error) {
                console.error('Ошибка форматирования валюты:', error, amount);
                return '0 ₽';
            }
        },

        calculateRentalDays(startDate, endDate) {
            if (!startDate || !endDate) return 0;

            try {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const timeDiff = end.getTime() - start.getTime();
                const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                return dayDiff > 0 ? dayDiff : 0;
            } catch (error) {
                console.error('Ошибка расчета дней аренды:', error);
                return 0;
            }
        },

        showToast(type, message) {
            // Простая реализация toast уведомления
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    },
    async mounted() {
        await this.loadUser();
        await this.loadRequest();
    }
}
</script>

<style scoped>
.public-rental-request-show {
    min-height: 80vh;
    background-color: #f8f9fa;
}

.public-stats-card .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    text-align: center;
}

.stat-item {
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    transition: transform 0.2s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #0d6efd;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-item {
    border-left: 3px solid #0d6efd;
    padding-left: 1rem;
}

.categories-list {
    background: #f8f9fa;
}

.contact-info {
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .public-stats-card .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .page-header .btn {
        width: 100%;
        justify-content: center;
    }
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}
</style>
