<template>
    <div class="rental-request-list">
        <!-- Заголовок и кнопка создания -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">Мои заявки на аренду</h1>
            <a :href="createRoute" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Создать заявку
            </a>
        </div>

        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4" v-for="stat in statistics" :key="stat.key">
                <div class="card text-white mb-4" :class="`bg-${stat.color}`">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-xs font-weight-bold text-uppercase mb-1">{{ stat.title }}</div>
                                <div class="h5 mb-0">{{ stat.value }}</div>
                            </div>
                            <div class="col-auto">
                                <i :class="stat.icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Фильтры и поиск -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Статус заявки</label>
                        <select class="form-select" v-model="filters.status" @change="loadRequests">
                            <option value="all">Все статусы</option>
                            <option value="draft">Черновик</option>
                            <option value="active">Активные</option>
                            <option value="paused">Приостановленные</option>
                            <option value="processing">В процессе</option>
                            <option value="completed">Завершенные</option>
                            <option value="cancelled">Отмененные</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Поиск</label>
                        <input type="text" class="form-control" v-model="filters.search"
                               placeholder="По названию или описанию" @input="debouncedSearch">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Сортировка</label>
                        <select class="form-select" v-model="filters.sort" @change="loadRequests">
                            <option value="newest">Сначала новые</option>
                            <option value="oldest">Сначала старые</option>
                            <option value="proposals">По количеству предложений</option>
                            <option value="budget">По размеру бюджета</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Элементов на странице</label>
                        <select class="form-select" v-model="filters.per_page" @change="loadRequests">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Переключение вида -->
        <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Найдено заявок: {{ requests.meta?.total || 0 }}</h5>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary" :class="{ active: viewMode === 'table' }"
                        @click="viewMode = 'table'">
                    <i class="fas fa-table"></i>
                </button>
                <button type="button" class="btn btn-outline-primary" :class="{ active: viewMode === 'cards' }"
                        @click="viewMode = 'cards'">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
        </div>

        <!-- Табличный вид -->
        <div v-if="viewMode === 'table'" class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Название заявки</th>
                                <th>Категории</th>
                                <th>Позиций</th>
                                <th>Период аренды</th>
                                <th>Бюджет</th>
                                <th>Статус</th>
                                <th>Предложения</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="request in requests.data" :key="request.id">
                                <td>#{{ request.id }}</td>
                                <td>
                                    <a :href="`/lessee/rental-requests/${request.id}`" class="text-decoration-none fw-bold">
                                        {{ request.title }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ request.description }}</small>
                                </td>
                                <td>
                                    <div v-if="request.items && request.items.length > 0">
                                        <span v-for="item in request.items" :key="item.id"
                                              class="badge bg-light text-dark mb-1 me-1">
                                            {{ item.category?.name || 'Без категории' }}
                                        </span>
                                    </div>
                                    <span v-else class="badge bg-warning">Нет позиций</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ request.items_count }}</span>
                                </td>
                                <td>
                                    <small>
                                        {{ formatDate(request.rental_period_start) }}<br>
                                        {{ formatDate(request.rental_period_end) }}
                                    </small>
                                </td>
                                <td>
                                    <strong>{{ formatCurrency(request.calculated_budget_from || request.budget_from) }} ₽</strong>
                                </td>
                                <td>
                                    <span class="badge" :class="`bg-${getStatusColor(request.status)}`">
                                        {{ getStatusText(request.status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary rounded-pill">{{ request.responses_count }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a :href="`/lessee/rental-requests/${request.id}`"
                                           class="btn btn-outline-primary" title="Просмотр">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Пагинация -->
            <div class="card-footer" v-if="requests.meta">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Показано с {{ requests.meta.from }} по {{ requests.meta.to }} из {{ requests.meta.total }} записей
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item" :class="{ disabled: !requests.links.prev }">
                                <a class="page-link" href="#" @click.prevent="loadPage(requests.meta.current_page - 1)">
                                    Назад
                                </a>
                            </li>
                            <li class="page-item" v-for="page in requests.meta.links" :key="page.label"
                                :class="{ active: page.active, disabled: !page.url }">
                                <a class="page-link" href="#" @click.prevent="loadPageFromUrl(page.url)"
                                   v-html="page.label"></a>
                            </li>
                            <li class="page-item" :class="{ disabled: !requests.links.next }">
                                <a class="page-link" href="#" @click.prevent="loadPage(requests.meta.current_page + 1)">
                                    Вперед
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Карточный вид -->
        <div v-if="viewMode === 'cards'" class="row">
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4" v-for="request in requests.data" :key="request.id">
                <div class="card h-100 rental-request-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge" :class="`bg-${getStatusColor(request.status)}`">
                            {{ getStatusText(request.status) }}
                        </span>
                        <small class="text-muted">#{{ request.id }}</small>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title">{{ request.title }}</h6>
                        <p class="card-text small text-muted">{{ request.description }}</p>

                        <div class="request-meta mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Категории:</span>
                                <div class="text-end">
                                    <span v-for="item in request.items" :key="item.id"
                                          class="badge bg-light text-dark d-block mb-1">
                                        {{ item.category?.name || 'Без категории' }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Период:</span>
                                <strong>{{ formatDate(request.rental_period_start) }} - {{ formatDate(request.rental_period_end) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Бюджет:</span>
                                <strong>{{ formatCurrency(request.calculated_budget_from || request.budget_from) }} ₽</strong>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Позиций:</span>
                                <strong>{{ request.items_count }}</strong>
                            </div>
                        </div>

                        <div class="progress mb-2" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                 :style="`width: ${getProposalProgress(request)}%`"
                                 :title="`${request.responses_count} предложений из ${request.items_count} позиций`">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary rounded-pill">{{ request.responses_count }}</span>
                                <small class="text-muted ms-1">предложений</small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a :href="`/lessee/rental-requests/${request.id}`"
                                   class="btn btn-outline-primary" title="Просмотр">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Создана: {{ formatDateTime(request.created_at) }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
            <div v-if="!loading && requests.data && requests.data.length === 0" class="text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
            <h4>Заявки не найдены</h4>
            <p class="text-muted">Попробуйте изменить параметры фильтрации</p>
            <a :href="createRoute" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Создать первую заявку
            </a>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загрузка заявок...</p>
        </div>
    </div>
</template>

<script>

export default {
    name: 'RentalRequestList',
    data() {
        return {
            requests: { data: [], meta: {}, links: {} },
            statistics: [],
            filters: {
                status: 'all',
                search: '',
                sort: 'newest',
                per_page: 15
            },
            viewMode: 'table',
            loading: false,
            debounceTimeout: null,
            createRoute: '/lessee/rental-requests/create',
            error: null
        }
    },
    methods: {
        async loadRequests(page = 1) {
            this.loading = true;
            this.error = null;

            try {
                console.log('🔍 Загружаем заявки...');

                const params = new URLSearchParams({
                    page: page,
                    ...this.filters
                });

                const apiUrl = `${window.location.origin}/api/lessee/rental-requests?${params}`;
                console.log('📡 API URL:', apiUrl);

                const response = await fetch(apiUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'include'
                });

                console.log('📊 Ответ сервера:', response.status, response.statusText);

                if (!response.ok) {
                    throw new Error(`HTTP ошибка! Статус: ${response.status}`);
                }

                const data = await response.json();
                console.log('📦 Данные заявок:', data);

                if (data.success) {
                    this.requests = data.data;
                    console.log('✅ Успешно загружено заявок:', data.data.data?.length || 0);

                    // После успешной загрузки заявок, рассчитываем статистику из данных
                    this.calculateStatsFromRequests();
                } else {
                    throw new Error(data.message || 'Ошибка сервера');
                }

            } catch (error) {
                console.error('❌ Ошибка загрузки заявок:', error);
                this.error = `Не удалось загрузить заявки: ${error.message}`;

                // Fallback данные для отладки
                this.requests = {
                    data: [],
                    meta: { total: 0, current_page: 1, last_page: 1 }
                };

                // Создаем пустую статистику
                this.createEmptyStats();
            } finally {
                this.loading = false;
            }
        },

        // УДАЛЕН метод loadStats() - используем только расчет из данных

        // Альтернативный метод расчета статистики из загруженных данных
        calculateStatsFromRequests() {
            if (!this.requests.data || this.requests.data.length === 0) {
                this.createEmptyStats();
                return;
            }

            const stats = {
                total: this.requests.meta?.total || this.requests.data.length,
                active: this.requests.data.filter(r => r.status === 'active').length,
                processing: this.requests.data.filter(r => r.status === 'processing').length,
                completed: this.requests.data.filter(r => r.status === 'completed').length,
                cancelled: this.requests.data.filter(r => r.status === 'cancelled').length,
                draft: this.requests.data.filter(r => r.status === 'draft').length,
                total_items: this.requests.data.reduce((sum, r) => sum + (r.items_count || 0), 0),
                total_proposals: this.requests.data.reduce((sum, r) => sum + (r.responses_count || 0), 0),
                total_budget: this.requests.data.reduce((sum, r) => sum + (r.calculated_budget_from || r.budget_from || 0), 0)
            };

            this.statistics = [
                {
                    key: 'total',
                    title: 'Всего заявок',
                    value: stats.total,
                    color: 'primary',
                    icon: 'fas fa-clipboard-list fa-2x'
                },
                {
                    key: 'active',
                    title: 'Активные',
                    value: stats.active,
                    color: 'success',
                    icon: 'fas fa-play-circle fa-2x'
                },
                {
                    key: 'processing',
                    title: 'В процессе',
                    value: stats.processing,
                    color: 'warning',
                    icon: 'fas fa-cogs fa-2x'
                },
                {
                    key: 'completed',
                    title: 'Завершенные',
                    value: stats.completed,
                    color: 'info',
                    icon: 'fas fa-check-circle fa-2x'
                },
                {
                    key: 'total_items',
                    title: 'Всего позиций',
                    value: stats.total_items,
                    color: 'secondary',
                    icon: 'fas fa-cubes fa-2x'
                },
                {
                    key: 'total_proposals',
                    title: 'Предложений',
                    value: stats.total_proposals,
                    color: 'dark',
                    icon: 'fas fa-handshake fa-2x'
                }
            ];

            console.log('📊 Статистика рассчитана из данных:', stats);
        },

        // Создание пустой статистики
        createEmptyStats() {
            this.statistics = [
                {
                    key: 'total',
                    title: 'Всего заявок',
                    value: 0,
                    color: 'primary',
                    icon: 'fas fa-clipboard-list fa-2x'
                },
                {
                    key: 'active',
                    title: 'Активные',
                    value: 0,
                    color: 'success',
                    icon: 'fas fa-play-circle fa-2x'
                },
                {
                    key: 'processing',
                    title: 'В процессе',
                    value: 0,
                    color: 'warning',
                    icon: 'fas fa-cogs fa-2x'
                },
                {
                    key: 'completed',
                    title: 'Завершенные',
                    value: 0,
                    color: 'info',
                    icon: 'fas fa-check-circle fa-2x'
                },
                {
                    key: 'total_items',
                    title: 'Всего позиций',
                    value: 0,
                    color: 'secondary',
                    icon: 'fas fa-cubes fa-2x'
                },
                {
                    key: 'total_proposals',
                    title: 'Предложений',
                    value: 0,
                    color: 'dark',
                    icon: 'fas fa-handshake fa-2x'
                }
            ];
        },

        debouncedSearch() {
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(() => {
                this.loadRequests(1);
            }, 500);
        },

        loadPage(page) {
            if (page >= 1 && page <= (this.requests.meta?.last_page || 1)) {
                this.loadRequests(page);
            }
        },

        loadPageFromUrl(url) {
            if (!url) return;
            try {
                const page = new URL(url).searchParams.get('page');
                this.loadRequests(parseInt(page) || 1);
            } catch (error) {
                console.error('Ошибка парсинга URL:', error);
            }
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            try {
                return new Date(dateString).toLocaleDateString('ru-RU');
            } catch (error) {
                console.error('Ошибка форматирования даты:', error);
                return '—';
            }
        },

        formatDateTime(dateString) {
            if (!dateString) return '—';
            try {
                return new Date(dateString).toLocaleDateString('ru-RU') + ' ' +
                       new Date(dateString).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
            } catch (error) {
                console.error('Ошибка форматирования даты/времени:', error);
                return '—';
            }
        },

        formatCurrency(amount) {
            if (!amount && amount !== 0) return '—';
            try {
                return new Intl.NumberFormat('ru-RU').format(amount);
            } catch (error) {
                console.error('Ошибка форматирования валюты:', error);
                return '—';
            }
        },

        getStatusColor(status) {
            const colors = {
                'draft': 'secondary',
                'active': 'success',
                'paused': 'warning',
                'processing': 'warning',
                'completed': 'primary',
                'cancelled': 'danger'
            };
            return colors[status] || 'light';
        },

        getStatusText(status) {
            const texts = {
                'draft': 'Черновик',
                'active': 'Активна',
                'paused': 'Приостановлена',
                'processing': 'В процессе',
                'completed': 'Завершена',
                'cancelled': 'Отменена'
            };
            return texts[status] || status;
        },

        getProposalProgress(request) {
            if (!request.responses_count || !request.items_count) return 0;
            return Math.min(100, (request.responses_count / Math.max(1, request.items_count)) * 100);
        }
    },
    mounted() {
        console.log('🔄 Компонент RentalRequestList монтирован');

        // Загружаем заявки (статистика рассчитается из данных)
        this.loadRequests();

        // Восстановление настроек из localStorage
        const savedViewMode = localStorage.getItem('rentalRequestsViewMode');
        if (savedViewMode) {
            this.viewMode = savedViewMode;
        }
    },
    watch: {
        viewMode(newVal) {
            localStorage.setItem('rentalRequestsViewMode', newVal);
        }
    }
}
</script>

<style scoped>
.rental-request-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 4px solid #0d6efd;
}

.rental-request-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.request-meta {
    border-top: 1px solid #eee;
    padding-top: 10px;
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.page-link {
    color: #0d6efd;
}

.page-link:hover {
    color: #0a58ca;
}
</style>
