<template>
    <div class="public-rental-requests">
         <!-- Теперь роль определяется правильно -->
        <h2 v-if="userRole === 'lessor'">Панель арендодателя: {{ authUser?.company?.legal_name }}</h2>
        <h2 v-else>Публичные заявки на аренду</h2>
        <!-- Фильтры (остается без изменений) -->
        <div class="filters-section bg-light p-4 mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Категория</label>
                    <select v-model="filters.category_id" class="form-select" @change="loadRequests">
                        <option value="">Все категории</option>
                        <option v-for="category in filterCategories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Локация</label>
                    <select v-model="filters.location_id" class="form-select" @change="loadRequests">
                        <option value="">Все локации</option>
                        <option v-for="location in locations" :key="location.id" :value="location.id">
                            {{ location.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Сортировка</label>
                    <select v-model="filters.sort" class="form-select" @change="loadRequests">
                        <option value="newest">Сначала новые</option>
                        <option value="budget">По бюджету</option>
                        <option value="proposals">По количеству предложений</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Список заявок -->
        <div class="requests-list">
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>

            <div v-else-if="error" class="alert alert-danger text-center">
                {{ error }}
            </div>

            <div v-else-if="requests.data.length === 0" class="alert alert-info text-center">
                Публичные заявки не найдены
            </div>

            <div v-else class="row">
                <div class="col-lg-6 mb-4" v-for="request in processedRequests" :key="request.id">
                    <div class="card h-100 rental-request-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ request.title || 'Без названия' }}</h5>
                            <span class="badge bg-primary">{{ request.active_proposals_count || 0 }} предложений</span>
                        </div>

                        <div class="card-body">
                            <p class="card-text">{{ request.description || 'Описание отсутствует' }}</p>

                            <div class="request-meta mb-3">
                                <div class="d-flex justify-content-between text-muted small mb-2">
                                    <span>
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        {{ request.rental_period_display }}
                                    </span>
                                    <span>{{ request.rental_days }} дней</span>
                                </div>

                                <div class="d-flex justify-content-between text-muted small">
                                    <span>
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ request.location?.name || 'Локация не указана' }}
                                    </span>
                                    <span>{{ request.created_at_display }}</span>
                                </div>
                            </div>

                            <!-- Позиции заявки -->
                            <div class="request-items" v-if="request.items && request.items.length > 0">
                                <h6 class="mb-2">Требуемая техника:</h6>
                                <div v-for="(item, index) in request.items" :key="index" class="request-item mb-2">
                                    <strong>{{ item.category?.name || 'Без категории' }}</strong> × {{ item.quantity || 1 }}
                                    <div v-if="item.specifications && item.specifications.length > 0"
                                         class="specifications small text-muted mt-1">
                                        <div v-for="spec in item.formatted_specifications || item.specifications"
                                             :key="spec.key || spec">
                                            {{ spec.formatted || spec.label || spec }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Бюджет -->
                            <div v-if="isAuthenticatedLessor && request.lessor_pricing" class="budget-info mt-3 p-3 bg-light rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Бюджет для вас:</span>
                                    <span class="text-success fw-bold">
                                        {{ formatCurrency(request.lessor_pricing.total_lessor_budget || 0) }}
                                    </span>
                                </div>
                                <div class="pricing-details mt-2">
                                    <div v-for="item in request.lessor_pricing.items" :key="item.item_id"
                                         class="price-item small text-muted mb-1">
                                        {{ item.category_name }}: {{ item.quantity }} шт. ×
                                        {{ formatCurrency(item.lessor_price) }}/час
                                    </div>
                                </div>
                                <div class="rental-info small text-muted mt-2">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ request.lessor_pricing.working_hours }} часов
                                    ({{ request.lessor_pricing.rental_days }} дней)
                                </div>
                            </div>

                            <div v-else-if="isAuthenticatedLessor" class="budget-info mt-3 p-3 bg-light rounded">
                                <div class="text-center text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Бюджет заявки доступен при просмотре деталей
                                </div>
                            </div>

                            <div v-else class="budget-info mt-3 p-3 bg-light rounded">
                                <div class="text-center text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Войдите как арендодатель для просмотра бюджета
                                </div>
                            </div>
                        </div>
                         <!-- 🔥 ДОБАВИТЬ ЭТОТ CARD-FOOTER -->
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <button class="btn btn-outline-primary btn-sm" @click="viewRequest(request.id)">
                                        <i class="fas fa-eye me-1"></i>Подробнее
                                    </button>

                                    <button v-if="isAuthenticatedLessor"
                                            class="btn btn-primary btn-sm"
                                            @click="showProposalModal(request)"
                                            :disabled="!canMakeProposal(request)">
                                        <i class="fas fa-paper-plane me-1"></i>Предложить
                                    </button>

                                    <button v-else class="btn btn-outline-secondary btn-sm"
                                            @click="redirectToLogin">
                                        Войдите для предложения
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


        <!-- Пагинация -->
        <nav v-if="requests.meta && requests.meta.last_page > 1" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: !requests.links || !requests.links.prev }">
                    <button class="page-link" @click="changePage(requests.meta.current_page - 1)">Назад</button>
                </li>

                <li v-for="page in pages" :key="page"
                    class="page-item"
                    :class="{ active: page === (requests.meta?.current_page || 1) }">
                    <button class="page-link" @click="changePage(page)">{{ page }}</button>
                </li>

                <li class="page-item" :class="{ disabled: !requests.links || !requests.links.next }">
                    <button class="page-link" @click="changePage(requests.meta.current_page + 1)">Вперед</button>
                </li>
            </ul>
        </nav>

        <!-- Модальное окно предложения -->
        <ProposalModal
            v-if="showModal"
            :request="selectedRequest"
            @close="showModal = false"
            @proposal-created="onProposalCreated" />
    </div>
</div>
</template>

<script>
import ProposalModal from '../components/ProposalModal.vue';

export default {
    name: 'PublicRentalRequests',
    components: { ProposalModal },

     props: {
        userRole: String,
        authUser: Object
    },

    data() {
        return {
            loading: true,
            error: null,
            requests: { data: [], meta: {}, links: {} },
            filterCategories: [],
            locations: [],
            filters: {
                category_id: '',
                location_id: '',
                sort: 'newest'
            },
            showModal: false,
            selectedRequest: null,
            currentUser: null,
            authChecked: false
        }
    },

    computed: {
        pages() {
            if (!this.requests.meta) return [];
            const current = this.requests.meta.current_page;
            const last = this.requests.meta.last_page;
            const range = 2;

            let start = Math.max(1, current - range);
            let end = Math.min(last, current + range);

            if (end - start < range * 2) {
                if (current < last / 2) {
                    end = Math.min(last, start + range * 2);
                } else {
                    start = Math.max(1, end - range * 2);
                }
            }

            const pages = [];
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },

          isAuthenticatedLessor() {
            // Проверяем, что authUser существует и является арендодателем
            return this.authUser &&
                this.authUser.company &&
                this.authUser.company.is_lessor;
        },

        // 🎯 Ключевое исправление: обрабатываем данные заявок
         processedRequests() {
            if (!this.requests.data || !Array.isArray(this.requests.data)) {
                return [];
            }

            return this.requests.data.map(request => {
                const processed = {
                    ...request,
                    rental_period_display: this.getRentalPeriodDisplay(
                        request.rental_period_start,
                        request.rental_period_end
                    ),
                    rental_days: this.calculateRentalDays(
                        request.rental_period_start,
                        request.rental_period_end
                    ),
                    created_at_display: this.formatDate(request.created_at),
                    items: (request.items || []).map(item => ({
                        ...item,
                        formatted_specifications: item.formatted_specifications || this.formatSpecifications(item.specifications)
                    }))
                };

                // Добавляем преобразованные цены для арендодателей
                if (this.isAuthenticatedLessor && request.lessor_pricing) {
                    processed.lessor_pricing = request.lessor_pricing;
                }

                return processed;
            });
        }
    },

    methods: {
        // 🔧 Исправленный метод для отображения периода аренды
        getRentalPeriodDisplay(startDate, endDate) {
            if (!startDate || !endDate) {
                return 'Период не указан';
            }

            try {
                const start = this.formatDate(startDate);
                const end = this.formatDate(endDate);
                return `${start} - ${end}`;
            } catch (error) {
                console.error('Ошибка форматирования периода аренды:', error);
                return 'Ошибка даты';
            }
        },

        // 🔧 Исправленный расчет дней аренды
        calculateRentalDays(startDate, endDate) {
            if (!startDate || !endDate) {
                return 0;
            }

            try {
                const start = new Date(startDate);
                const end = new Date(endDate);

                // Проверяем валидность дат
                if (isNaN(start.getTime()) || isNaN(end.getTime())) {
                    return 0;
                }

                // Вычисляем разницу в днях (включительно)
                const timeDiff = end.getTime() - start.getTime();
                const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

                return dayDiff > 0 ? dayDiff : 0;
            } catch (error) {
                console.error('Ошибка расчета дней аренды:', error);
                return 0;
            }
        },

        // 🔧 Улучшенное форматирование спецификаций
        formatSpecifications(specs) {
            if (!specs || !Array.isArray(specs)) {
                return [];
            }

            return specs.map(spec => {
                if (typeof spec === 'string') {
                    return { formatted: spec };
                }

                if (spec.formatted) {
                    return spec;
                }

                // Пытаемся создать читаемое представление
                if (spec.label && spec.value) {
                    const unit = spec.unit ? ` ${spec.unit}` : '';
                    return {
                        ...spec,
                        formatted: `${spec.label}: ${spec.value}${unit}`
                    };
                }

                return { formatted: JSON.stringify(spec) };
            });
        },

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
                    console.log('✅ Пользователь загружен:', this.currentUser);
                } else {
                    console.log('⚠️ Пользователь не авторизован');
                    this.currentUser = null;
                }
            } catch (error) {
                console.error('❌ Ошибка загрузки пользователя:', error);
                this.currentUser = null;
            } finally {
                this.authChecked = true;
            }
        },

        async loadRequests(page = 1) {
            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams({
                    page,
                    ...this.filters
                });

                const apiUrl = `/api/public/rental-requests?${params}`;
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
                    this.requests = data.data;
                    this.filterCategories = data.filters?.categories || [];
                    this.locations = data.filters?.locations || [];

                    console.log('✅ Заявки загружены с преобразованными ценами:',
                        this.requests.data.map(r => ({
                            id: r.id,
                            has_lessor_pricing: !!r.lessor_pricing,
                            lessor_budget: r.lessor_pricing?.total_lessor_budget
                        }))
                    );
                } else {
                    throw new Error(data.message || 'Ошибка сервера');
                }

            } catch (error) {
                console.error('❌ Ошибка загрузки заявок:', error);
                this.error = `Не удалось загрузить заявки: ${error.message}`;
                this.requests = { data: [], meta: { total: 0, current_page: 1, last_page: 1 } };
            } finally {
                this.loading = false;
            }
        },

        canMakeProposal(request) {
            return this.isAuthenticatedLessor;
        },

        changePage(page) {
            if (page >= 1 && page <= this.requests.meta.last_page) {
                this.loadRequests(page);
            }
        },

        viewRequest(id) {
            if (!id) {
                console.error('ID заявки не указан');
                return;
            }
            // Открываем в той же вкладке
            window.location.href = `/public/rental-requests/${id}`;
        },

        showProposalModal(request) {
            if (!this.canMakeProposal(request)) {
                this.redirectToLogin();
                return;
            }
            this.selectedRequest = request;
            this.showModal = true;
        },

        onProposalCreated() {
            this.showModal = false;
            this.loadRequests(this.requests.meta.current_page);
            alert('Предложение успешно отправлено!');
        },

        redirectToLogin() {
            window.location.href = '/login';
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
        }
    },

    async mounted() {
        await this.loadUser();
        await this.loadRequests();
        console.log('Vue Component mounted. User role prop:', this.userRole);
        console.log('Auth user prop:', this.authUser);
        console.log('Is authenticated lessor (computed):', this.isAuthenticatedLessor);

        // Детальный лог обработанных данных
        console.log('📋 Обработанные заявки:', this.processedRequests);
    }
}
</script>

<style scoped>
.rental-request-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.rental-request-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.request-meta {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 1rem;
}

.specifications {
    max-height: 100px;
    overflow-y: auto;
    font-size: 0.85em;
}

.budget-info {
    border-left: 4px solid #28a745;
}

.request-item {
    padding: 0.5rem;
    border-bottom: 1px solid #f8f9fa;
}

.request-item:last-child {
    border-bottom: none;
}
</style>
