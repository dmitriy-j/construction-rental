<template>
    <div class="public-rental-request-show">
        <div v-if="request && !loading && !error">
            <div class="container-fluid px-4">
                <div class="row">
                    <div class="col-12">
                        <div class="page-header d-flex justify-content-between align-items-center mb-4">
                            <h1 class="page-title">Публичная заявка: {{ request.title }}</h1>
                            <div>
                                <a href="/requests" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left me-2"></i>Назад к списку
                                </a>
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
                    <div class="col-lg-8">
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

                                        <div class="info-item mb-3" v-if="isAuthenticatedLessor && request.lessor_pricing">
                                            <label class="text-muted small">Бюджет для вас</label>
                                            <p class="mb-0 fs-5 text-success fw-bold">
                                                {{ formatCurrency(request.lessor_pricing.total_lessor_budget || 0) }}
                                            </p>
                                            <div class="pricing-details mt-2">
                                                <div v-for="item in request.lessor_pricing.items" :key="item.item_id"
                                                     class="price-item small text-muted mb-1">
                                                    <strong>{{ item.category_name }}</strong>:
                                                    {{ item.quantity }} шт. × {{ formatCurrency(item.lessor_price) }}/час
                                                </div>
                                            </div>
                                            <div class="rental-info small text-muted mt-2">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ request.lessor_pricing.working_hours }} часов
                                                ({{ request.lessor_pricing.rental_days }} дней)
                                            </div>
                                        </div>

                                        <div class="info-item mb-3" v-else-if="isAuthenticatedLessor">
                                            <label class="text-muted small">Бюджет</label>
                                            <p class="mb-0 text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Бюджет загружается...
                                            </p>
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
                                    <span class="badge bg-primary ms-2">{{ request.grouped_items?.length || 0 }} категорий</span>
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="categories-list">
                                    <!-- 🔥 ИСПОЛЬЗУЕМ ГРУППИРОВКУ ИЗ КОНТРОЛЛЕРА -->
                                    <PublicCategoryGroup
                                        v-for="category in request.grouped_items"
                                        :key="category.category_name"
                                        :category="category"
                                        :initially-expanded="true"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Боковая панель -->
                    <div class="col-lg-4">
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
                                        @click="openProposalModal"
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

                <!-- Отладочная информация -->
                <div v-if="isAuthenticatedLessor" style="background: lightgreen; padding: 10px;">
                    <p>Отладка: Роль арендодателя определена.</p>
                    <p>Бюджет: {{ request.total_budget }}</p>
                    <p>Условия: {{ request.rental_conditions }}</p>
                </div>

                <!-- Модальное окно предложения -->
                <PublicProposalModal
                    :show="showProposalModal"
                    :request="request"
                    @close="showProposalModal=false"
                    @proposal-created="onProposalCreated"
                />
            </div>
        </div>

        <!-- Индикатор загрузки -->
        <div v-else-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загрузка заявки...</p>
        </div>

        <!-- Сообщение об ошибке -->
        <div v-else-if="error" class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ error }}
            <br>
            <button class="btn btn-outline-danger btn-sm mt-2" @click="loadRequest">
                Попробовать снова
            </button>
        </div>
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
            authChecked: false,
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
            // Добавлена проверка на существование company
            const isLessor = this.currentUser &&
                            this.currentUser.company &&
                            this.currentUser.company.is_lessor === 1; // Явная проверка на 1

            console.log('🔐 Проверка роли пользователя:', {
                currentUser: this.currentUser,
                company: this.currentUser?.company,
                is_lessor: this.currentUser?.company?.is_lessor,
                result: isLessor
            });

            return isLessor;
        },

         totalEquipmentQuantity() {
            if (!this.request.items) return 0;
            return this.request.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
        },

        canMakeProposal() {
            if (!this.isAuthenticatedLessor) {
                console.log('❌ Не может делать предложение: не арендодатель');
                return false;
            }
            if (!this.request) {
                console.log('❌ Не может делать предложение: нет данных заявки');
                return false;
            }

            // 🎯 ИСПРАВЛЕННАЯ ПРОВЕРКА: используем status из API
            const isActive = this.request.status === 'active';
            const notExpired = !this.request.expires_at || new Date(this.request.expires_at) > new Date();

            console.log('📋 Проверка возможности предложения:', {
                isActive,
                notExpired,
                status: this.request.status,
                expires_at: this.request.expires_at
            });

            return isActive && notExpired;
        }
    },
    methods: {
        async loadUser() {
            try {
                console.log('🔄 Загрузка данных пользователя...');
                const response = await fetch('/api/user', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                if (response.ok) {
                    const userData = await response.json();

                    // ДЛЯ ОТЛАДКИ: выведите полную структуру ответа
                    console.log('📊 Полные данные пользователя из API:', JSON.stringify(userData, null, 2));

                    // Проверяем различные возможные структуры ответа
                    if (userData.company) {
                        // Стандартная структура
                        this.currentUser = userData;
                    } else if (userData.data && userData.data.company) {
                        // Структура с обёрткой {data: {}}
                        this.currentUser = userData.data;
                    } else if (userData.original && userData.original.company) {
                        // Структура Laravel с обёрткой {original: {}}
                        this.currentUser = userData.original;
                    } else {
                        // Если компания не найдена, устанавливаем структуру вручную
                        this.currentUser = {
                            ...userData,
                            company: userData.company || null
                        };
                        console.warn('⚠️ Компания не найдена в ответе API');
                    }

                    console.log('✅ Обработанные данные пользователя:', {
                        id: this.currentUser.id,
                        name: this.currentUser.name,
                        hasCompany: !!this.currentUser.company,
                        company: this.currentUser.company,
                        is_lessor: this.currentUser.company?.is_lessor
                    });
                } else {
                    console.log('⚠️ Пользователь не авторизован, статус:', response.status);
                    this.currentUser = null;
                }
            } catch (error) {
                console.error('❌ Ошибка загрузки пользователя:', error);
                this.currentUser = null;
            } finally {
                this.authChecked = true;
            }
        },

        debugRequestData() {
            console.log('🔍 Отладочная информация о заявке:', {
                id: this.request?.id,
                rental_period_start: this.request?.rental_period_start,
                rental_period_end: this.request?.rental_period_end,
                rental_period: this.request?.rental_period,
                total_budget: this.request?.total_budget,
                hourly_rate: this.request?.hourly_rate,
                max_hourly_rate: this.request?.max_hourly_rate,
                rental_conditions: this.request?.rental_conditions,
                raw_request: this.request
            });
        },

        async loadRequest() {
            this.loading = true;
            this.error = null;

            try {
                const requestId = this.getRequestIdFromUrl();
                const apiUrl = `/api/public/rental-requests/${requestId}`;

                console.log('🔄 Загрузка публичной заявки...', { requestId, apiUrl });
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
                console.log('📦 Ответ от API заявки:', data);

                if (data.success) {
                    this.request = data.data;

                    console.log('💰 Данные бюджета в заявке:', {
                        has_lessor_pricing: !!this.request.lessor_pricing,
                        lessor_budget: this.request.lessor_pricing?.total_lessor_budget,
                        items_count: this.request.lessor_pricing?.items?.length
                    });

                    this.processRequestData();

                } else {
                    throw new Error(data.message || 'Ошибка загрузки заявки');
                }
            } catch (error) {
                console.error('❌ Ошибка загрузки заявки:', error);
                this.error = `Не удалось загрузить заявку: ${error.message}`;
            } finally {
                this.loading = false;
            }
        },

        getRequestIdFromUrl() {
            const path = window.location.pathname;
            const matches = path.match(/\/public\/rental-requests\/(\d+)/);
            return matches ? matches[1] : null;
        },

        processRequestData() {
            if (!this.request) return;

            console.log('🔍 Данные заявки для обработки:', this.request);

            // Обрабатываем период аренды (если не пришло из API)
            if (!this.request.rental_period_display) {
                this.request.rental_period_display = this.getRentalPeriodDisplay(
                    this.request.rental_period_start,
                    this.request.rental_period_end
                );
            }

            if (!this.request.rental_days) {
                this.request.rental_days = this.calculateRentalDays(
                    this.request.rental_period_start,
                    this.request.rental_period_end
                );
            }

            // Обрабатываем позиции заявки
            const items = this.request.items || [];

            // Считаем суммарную информацию
            const uniqueCategories = new Set(items.map(item => item.category?.name || 'Без категории'));

            this.summary = {
                total_items: items.length,
                total_quantity: items.reduce((sum, item) => sum + (item.quantity || 0), 0),
                categories_count: uniqueCategories.size
            };

            this.groupedByCategory = this.groupItemsByCategory(items);

            // Детальная отладка данных заявки
            this.debugRequestData();
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

                const categoryName = item.category || 'Без категории';
                const categoryKey = categoryName;

                if (!grouped[categoryKey]) {
                    grouped[categoryKey] = {
                        category_id: categoryKey,
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
            console.log('🔄 Открытие модального окна предложения');

            if (!this.canMakeProposal) {
                console.log('❌ Нельзя сделать предложение:', {
                    isAuthenticatedLessor: this.isAuthenticatedLessor,
                    requestStatus: this.request?.status,
                    canMakeProposal: this.canMakeProposal
                });
                this.redirectToLogin();
                return;
            }

            this.showProposalModal = true;
            console.log('✅ Модальное окно открыто');
        },

        onProposalCreated(proposalData) {
            console.log('✅ Предложение создано:', proposalData);
            this.showProposalModal = false; // Заменить closeProposalModal()
            this.showToast('success', 'Предложение успешно отправлено!');
            this.loadRequest();
        },

        addToFavorites() {
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
        console.log('🚀 Компонент PublicRentalRequestShow mounted');
        await this.loadUser();
        await this.loadRequest();
        console.log('✅ Инициализация компонента завершена');
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
