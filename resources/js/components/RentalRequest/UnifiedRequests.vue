<template>
    <div class="unified-requests">
        <!-- Заголовок -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">{{ pageTitle }}</h1>
            <div v-if="isLessee">
                <a :href="createRoute" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Создать заявку
                </a>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Категория</label>
                        <select v-model="filters.category_id" class="form-select" @change="loadRequests">
                            <option value="">Все категории</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Локация</label>
                        <select v-model="filters.location_id" class="form-select" @change="loadRequests">
                            <option value="">Все локации</option>
                            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Сортировка</label>
                        <select v-model="filters.sort" class="form-select" @change="loadRequests">
                            <option value="newest">Сначала новые</option>
                            <option value="budget">По бюджету</option>
                            <option value="proposals">По предложениям</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button v-if="isLessee" class="btn btn-outline-secondary w-100" @click="loadRequests">
                            <i class="fas fa-sync me-1"></i>Обновить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2 text-muted">Загрузка заявок...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
        </div>

        <!-- Пусто -->
        <div v-else-if="!requests.data || requests.data.length === 0" class="text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
            <h4>Заявки не найдены</h4>
            <p class="text-muted" v-if="isGuest">
                Публичные заявки отсутствуют. Попробуйте изменить параметры фильтрации.
            </p>
            <p class="text-muted" v-else-if="isLessee">
                У вас ещё нет заявок. Создайте первую!
            </p>
            <p class="text-muted" v-else-if="isLessor">
                Заявки, соответствующие вашему оборудованию, не найдены.
            </p>
            <a v-if="isLessee" :href="createRoute" class="btn btn-primary mt-2">
                <i class="fas fa-plus me-2"></i>Создать заявку
            </a>
        </div>

        <!-- Список заявок -->
        <div v-else class="row">
            <div class="col-lg-6 mb-4" v-for="request in processedRequests" :key="request.id">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ request.title || 'Без названия' }}</h6>
                        <span class="badge" :class="'bg-' + getStatusColor(request.status)">
                            {{ getStatusText(request.status) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="card-text small">{{ request.description_short || (request.description || '').substring(0, 200) || 'Описание отсутствует' }}</p>

                        <div class="d-flex justify-content-between text-muted small mb-2">
                            <span><i class="fas fa-calendar me-1"></i>{{ formatPeriod(request.rental_period_start, request.rental_period_end) }}</span>
                            <span>{{ request.rental_days || calcDays(request.rental_period_start, request.rental_period_end) }} дн.</span>
                        </div>

                        <div class="d-flex justify-content-between text-muted small">
                            <span><i class="fas fa-map-marker-alt me-1"></i>{{ request.location?.name || 'Не указана' }}</span>
                            <span><i class="fas fa-tag me-1"></i>{{ request.category || (request.items && request.items[0]?.category?.name) || '—' }}</span>
                        </div>

                        <!-- Позиции -->
                        <div v-if="request.items && request.items.length > 0" class="mt-2">
                            <div v-for="item in request.items.slice(0, 3)" :key="item.id" class="small">
                                <span class="badge bg-light text-dark me-1">{{ item.category?.name || '—' }}</span>
                                × {{ item.quantity || 1 }}
                            </div>
                            <div v-if="request.items.length > 3" class="small text-muted mt-1">
                                + ещё {{ request.items.length - 3 }} позиций
                            </div>
                        </div>

                        <!-- Бюджет для арендодателя -->
                        <div v-if="isLessor && request.lessor_pricing" class="mt-2 p-2 bg-light rounded small">
                            <strong>Ваш бюджет:</strong>
                            {{ formatCurrency(request.lessor_pricing.total_lessor_budget || 0) }}
                        </div>
                    </div>

                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>{{ formatDate(request.created_at) }}
                            </small>
                            <div>
                                <!-- Гость -->
                                <button v-if="isGuest" class="btn btn-sm btn-outline-secondary" @click="redirectToLogin">
                                    <i class="fas fa-sign-in-alt me-1"></i>Авторизуйтесь
                                </button>
                                <!-- Арендатор -->
                                <a v-if="isLessee" :href="'/lessee/rental-requests/' + request.id"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Подробнее
                                </a>
                                <!-- Арендодатель -->
                                <a v-if="isLessor" :href="'/lessor/rental-requests/' + request.id"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Подробнее
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пагинация -->
        <nav v-if="requests.meta && requests.meta.last_page > 1" class="mt-3">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item" :class="{ disabled: requests.meta.current_page <= 1 }">
                    <button class="page-link" @click="changePage(requests.meta.current_page - 1)">Назад</button>
                </li>
                <li class="page-item" v-for="page in pages" :key="page"
                    :class="{ active: page === requests.meta.current_page }">
                    <button class="page-link" @click="changePage(page)">{{ page }}</button>
                </li>
                <li class="page-item" :class="{ disabled: requests.meta.current_page >= requests.meta.last_page }">
                    <button class="page-link" @click="changePage(requests.meta.current_page + 1)">Вперед</button>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script>
export default {
    name: 'UnifiedRequests',
    props: {
        userRole: { type: String, default: 'guest' },
        authUser: { type: Object, default: null },
        categories: { type: Array, default: () => [] },
        locations: { type: Array, default: () => [] }
    },
    data() {
        return {
            loading: true,
            error: null,
            requests: { data: [], meta: { current_page: 1, last_page: 1, total: 0 }, links: {} },
            filters: {
                category_id: '',
                location_id: '',
                sort: 'newest'
            }
        }
    },
    computed: {
        isGuest() { return this.userRole === 'guest'; },
        isLessee() { return this.userRole === 'lessee'; },
        isLessor() { return this.userRole === 'lessor'; },
        pageTitle() {
            if (this.isLessee) return 'Мои заявки на аренду';
            if (this.isLessor) return 'Заявки на аренду';
            return 'Публичные заявки на аренду';
        },
        createRoute() { return '/lessee/rental-requests/create'; },
        pages() {
            if (!this.requests.meta) return [];
            const current = this.requests.meta.current_page || 1;
            const last = this.requests.meta.last_page || 1;
            const pages = [];
            for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                pages.push(i);
            }
            return pages;
        },
        processedRequests() {
            const data = this.requests.data;
            if (!data || !Array.isArray(data)) return [];
            return data.map(r => ({
                ...r,
                rental_days: this.calcDays(r.rental_period_start, r.rental_period_end)
            }));
        }
    },
    methods: {
        getApiUrl() {
            if (this.isLessee) return '/api/lessee/rental-requests';
            if (this.isLessor) return '/api/lessor/rental-requests';
            return '/api/public/rental-requests';
        },
        async loadRequests(page = 1) {
            this.loading = true;
            this.error = null;
            try {
                const params = new URLSearchParams({ page, ...this.filters });
                const url = this.getApiUrl() + '?' + params.toString();
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const json = await response.json();

                // API может возвращать {success, data: пагинатор} или пагинатор напрямую
                if (json.success && json.data) {
                    this.requests = json.data;
                } else if (json.data) {
                    this.requests = json;
                } else {
                    this.requests = json;
                }
                // Убедимся, что data - массив
                if (!Array.isArray(this.requests.data)) {
                    this.requests.data = [];
                }
            } catch (e) {
                console.error('Ошибка загрузки:', e);
                this.error = 'Не удалось загрузить заявки';
                this.requests = { data: [], meta: { current_page: 1, last_page: 1, total: 0 }, links: {} };
            } finally {
                this.loading = false;
            }
        },
        changePage(page) {
            if (page >= 1 && page <= (this.requests.meta?.last_page || 1)) {
                this.loadRequests(page);
            }
        },
        redirectToLogin() { window.location.href = '/login'; },
        formatDate(d) {
            if (!d) return '—';
            try { return new Date(d).toLocaleDateString('ru-RU'); }
            catch(e) { return '—'; }
        },
        formatPeriod(start, end) {
            if (!start || !end) return 'Период не указан';
            return this.formatDate(start) + ' — ' + this.formatDate(end);
        },
        calcDays(start, end) {
            if (!start || !end) return 0;
            try {
                return Math.ceil((new Date(end) - new Date(start)) / (1000*3600*24)) + 1;
            } catch(e) { return 0; }
        },
        formatCurrency(v) {
            if (!v && v !== 0) return '—';
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(v);
        },
        getStatusColor(s) {
            const colors = { draft: 'secondary', active: 'success', paused: 'warning', processing: 'warning', completed: 'primary', cancelled: 'danger' };
            return colors[s] || 'light';
        },
        getStatusText(s) {
            const texts = { draft: 'Черновик', active: 'Активна', paused: 'Приостановлена', processing: 'В процессе', completed: 'Завершена', cancelled: 'Отменена' };
            return texts[s] || s;
        }
    },
    mounted() {
        this.loadRequests();
    }
}
</script>
