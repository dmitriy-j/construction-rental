<template>
    <div class="markup-audit-log">
        <!-- Заголовок и фильтры -->
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Журнал аудита изменений наценок
                </h5>
                <div class="statistics">
                    <span class="badge bg-primary me-2">Всего записей: {{ pagination.total }}</span>
                    <span class="badge bg-success me-2">Сегодня: {{ stats.today }}</span>
                    <span class="badge bg-warning">Неделя: {{ stats.week }}</span>
                </div>
            </div>

            <div class="card-body">
                <!-- Фильтры -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Действие</label>
                        <select class="form-select" v-model="filters.action">
                            <option value="">Все действия</option>
                            <option value="created">Создание</option>
                            <option value="updated">Обновление</option>
                            <option value="deleted">Удаление</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Пользователь</label>
                        <select class="form-select" v-model="filters.user_id">
                            <option value="">Все пользователи</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Наценка</label>
                        <select class="form-select" v-model="filters.platform_markup_id">
                            <option value="">Все наценки</option>
                            <option v-for="markup in markups" :key="markup.id" :value="markup.id">
                                #{{ markup.id }} - {{ getMarkupDescription(markup) }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Период</label>
                        <select class="form-select" v-model="filters.period">
                            <option value="all">За все время</option>
                            <option value="today">Сегодня</option>
                            <option value="week">Неделя</option>
                            <option value="month">Месяц</option>
                            <option value="custom">Произвольный</option>
                        </select>
                    </div>
                </div>

                <!-- Произвольный период -->
                <div class="row g-3 mb-4" v-if="filters.period === 'custom'">
                    <div class="col-md-3">
                        <label class="form-label">С</label>
                        <input type="date" class="form-control" v-model="filters.date_from">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">По</label>
                        <input type="date" class="form-control" v-model="filters.date_to">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button class="btn btn-outline-secondary" @click="applyCustomDateRange">
                                Применить период
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Поиск -->
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label">Поиск по причине/изменениям</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="filters.search"
                            placeholder="Введите текст для поиска..."
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary flex-fill" @click="loadAuditLog">
                                <i class="bi bi-search me-1"></i>
                                Применить фильтры
                            </button>

                            <button class="btn btn-outline-secondary" @click="resetFilters">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Сбросить
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Экспорт -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-text">
                        Показано {{ auditLog.length }} из {{ pagination.total }} записей
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" @click="exportToCsv">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                            Экспорт в CSV
                        </button>

                        <button class="btn btn-sm btn-outline-secondary" @click="exportToJson">
                            <i class="bi bi-file-code me-1"></i>
                            Экспорт в JSON
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Таблица аудита -->
        <div class="card mt-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="60">ID</th>
                                <th width="120">Дата/Время</th>
                                <th width="100">Действие</th>
                                <th>Наценка</th>
                                <th>Пользователь</th>
                                <th>Изменения</th>
                                <th width="200">Причина</th>
                                <th width="80">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="audit in auditLog" :key="audit.id" class="audit-row">
                                <td class="fw-bold text-muted">#{{ audit.id }}</td>

                                <td>
                                    <div class="small">
                                        <div>{{ formatDate(audit.created_at) }}</div>
                                        <div class="text-muted">{{ formatTime(audit.created_at) }}</div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge" :class="getActionBadge(audit.action)">
                                        {{ getActionLabel(audit.action) }}
                                    </span>
                                </td>

                                <td>
                                    <div v-if="audit.markup">
                                        <div class="fw-bold">
                                            #{{ audit.markup.id }} - {{ getMarkupDescription(audit.markup) }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ getEntityTypeLabel(audit.markup.entity_type) }} •
                                            {{ getMarkupTypeLabel(audit.markup.type) }}
                                        </div>
                                    </div>
                                    <div v-else class="text-muted">
                                        <i>Наценка удалена</i>
                                    </div>
                                </td>

                                <td>
                                    <div v-if="audit.user">
                                        <div class="fw-bold">{{ audit.user.name }}</div>
                                        <div class="small text-muted">{{ audit.user.email }}</div>
                                    </div>
                                    <div v-else class="text-muted">
                                        <i>Система</i>
                                    </div>
                                </td>

                                <td>
                                    <div v-if="audit.formatted_changes && audit.formatted_changes.length > 0">
                                        <div class="changes-preview">
                                            <span
                                                v-for="change in audit.formatted_changes.slice(0, 2)"
                                                :key="change.field"
                                                class="badge bg-light text-dark me-1 mb-1"
                                            >
                                                {{ change.field }}
                                            </span>
                                            <span
                                                v-if="audit.formatted_changes.length > 2"
                                                class="badge bg-secondary"
                                            >
                                                +{{ audit.formatted_changes.length - 2 }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else class="text-muted">
                                        <i>Нет изменений</i>
                                    </div>
                                </td>

                                <td>
                                    <div class="reason-text">
                                        {{ audit.reason || '—' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            class="btn btn-outline-primary"
                                            @click="showAuditDetails(audit)"
                                            title="Просмотреть детали"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <button
                                            v-if="audit.action === 'updated'"
                                            class="btn btn-outline-info"
                                            @click="showChangesComparison(audit)"
                                            title="Сравнить изменения"
                                        >
                                            <i class="bi bi-arrow-left-right"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация -->
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Страница {{ pagination.current_page }} из {{ pagination.last_page }}
                    </div>

                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                                <button class="page-link" @click="changePage(pagination.current_page - 1)">
                                    ‹
                                </button>
                            </li>

                            <li
                                v-for="page in pagination.links"
                                :key="page.label"
                                class="page-item"
                                :class="{
                                    active: page.active,
                                    disabled: !page.url
                                }"
                            >
                                <button
                                    class="page-link"
                                    @click="changePage(page.label)"
                                    v-html="page.label"
                                ></button>
                            </li>

                            <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                                <button class="page-link" @click="changePage(pagination.current_page + 1)">
                                    ›
                                </button>
                            </li>
                        </ul>
                    </nav>

                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">На странице:</span>
                        <select class="form-select form-select-sm" v-model="pagination.per_page" @change="loadAuditLog" style="width: auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно деталей аудита -->
        <div class="modal fade" id="auditDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Детали записи аудита #{{ selectedAudit?.id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedAudit">
                            <!-- Основная информация -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Основная информация</h6>
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold">Действие:</td>
                                                <td>
                                                    <span class="badge" :class="getActionBadge(selectedAudit.action)">
                                                        {{ getActionLabel(selectedAudit.action) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Дата/Время:</td>
                                                <td>{{ formatDateTime(selectedAudit.created_at) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Пользователь:</td>
                                                <td>
                                                    <span v-if="selectedAudit.user">
                                                        {{ selectedAudit.user.name }} ({{ selectedAudit.user.email }})
                                                    </span>
                                                    <span v-else class="text-muted">Система</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h6>Наценка</h6>
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr v-if="selectedAudit.markup">
                                                <td class="fw-bold">ID:</td>
                                                <td>#{{ selectedAudit.markup.id }}</td>
                                            </tr>
                                            <tr v-if="selectedAudit.markup">
                                                <td class="fw-bold">Тип:</td>
                                                <td>{{ getMarkupTypeLabel(selectedAudit.markup.type) }}</td>
                                            </tr>
                                            <tr v-if="selectedAudit.markup">
                                                <td class="fw-bold">Контекст:</td>
                                                <td>{{ getEntityTypeLabel(selectedAudit.markup.entity_type) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Причина:</td>
                                                <td>{{ selectedAudit.reason || '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Изменения -->
                            <div v-if="selectedAudit.formatted_changes && selectedAudit.formatted_changes.length > 0">
                                <h6>Изменения ({{ selectedAudit.formatted_changes.length }})</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th width="200">Поле</th>
                                                <th>Старое значение</th>
                                                <th>Новое значение</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="change in selectedAudit.formatted_changes" :key="change.field">
                                                <td class="fw-bold">{{ change.field }}</td>
                                                <td>
                                                    <span class="text-danger" v-html="highlightChanges(change.from, change.to)"></span>
                                                </td>
                                                <td>
                                                    <span class="text-success" v-html="highlightChanges(change.to, change.from)"></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-else>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Для этой записи нет детальных изменений
                                </div>
                            </div>

                            <!-- Полные данные JSON -->
                            <div class="mt-4">
                                <h6>Полные данные (JSON)</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <pre class="mb-0 small"><code>{{ formatJson(selectedAudit) }}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно сравнения изменений -->
        <div class="modal fade" id="changesComparisonModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Сравнение изменений</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedAudit" class="row">
                            <div class="col-md-6">
                                <h6 class="text-danger">Старые значения</h6>
                                <div class="card border-danger">
                                    <div class="card-body">
                                        <pre class="mb-0 small"><code>{{ formatJson(selectedAudit.old_values) }}</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success">Новые значения</h6>
                                <div class="card border-success">
                                    <div class="card-body">
                                        <pre class="mb-0 small"><code>{{ formatJson(selectedAudit.new_values) }}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- График активности -->
        <div class="card mt-4" v-if="activityChartData.labels.length > 0">
            <div class="card-header">
                <h6 class="mb-0">Активность изменений</h6>
            </div>
            <div class="card-body">
                <canvas ref="activityChart" height="100"></canvas>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MarkupAuditLog',

    data() {
        return {
            auditLog: [],
            users: [],
            markups: [],

            filters: {
                action: '',
                user_id: '',
                platform_markup_id: '',
                period: 'week',
                date_from: '',
                date_to: '',
                search: ''
            },

            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 25,
                total: 0,
                links: [],
                prev_page_url: null,
                next_page_url: null
            },

            stats: {
                today: 0,
                week: 0,
                month: 0
            },

            selectedAudit: null,
            activityChart: null,
            activityChartData: {
                labels: [],
                datasets: []
            }
        };
    },

    mounted() {
        this.loadAuditLog();
        this.loadFilterData();
        this.loadStats();
        this.initializeActivityChart();
    },

    watch: {
        'filters.period': function(newVal) {
            if (newVal !== 'custom') {
                this.loadAuditLog();
            }
        }
    },

    methods: {
        async loadAuditLog(page = 1) {
            try {
                const params = {
                    page: page,
                    per_page: this.pagination.per_page,
                    ...this.filters
                };

                // Очищаем даты если период не custom
                if (this.filters.period !== 'custom') {
                    delete params.date_from;
                    delete params.date_to;
                }

                const response = await axios.get('/admin/markups/audit-log', { params });

                this.auditLog = response.data.data;
                this.pagination = {
                    ...response.data,
                    links: response.data.links || []
                };

            } catch (error) {
                console.error('Error loading audit log:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка загрузки',
                    text: 'Не удалось загрузить журнал аудита'
                });
            }
        },

        async loadFilterData() {
            try {
                const [usersResponse, markupsResponse] = await Promise.all([
                    axios.get('/admin/markups/audit-users'),
                    axios.get('/admin/markups/audit-markups')
                ]);

                this.users = usersResponse.data.users || [];
                this.markups = markupsResponse.data.markups || [];

            } catch (error) {
                console.error('Error loading filter data:', error);
            }
        },

        async loadStats() {
            try {
                const response = await axios.get('/admin/markups/audit-stats');
                this.stats = response.data.stats || {};

                // Загружаем данные для графика
                if (response.data.activity_data) {
                    this.updateActivityChart(response.data.activity_data);
                }

            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.loadAuditLog(page);
            }
        },

        resetFilters() {
            this.filters = {
                action: '',
                user_id: '',
                platform_markup_id: '',
                period: 'week',
                date_from: '',
                date_to: '',
                search: ''
            };
            this.loadAuditLog(1);
        },

        applyCustomDateRange() {
            if (this.filters.date_from && this.filters.date_to) {
                this.loadAuditLog(1);
            } else {
                this.$swal.fire({
                    icon: 'warning',
                    title: 'Внимание',
                    text: 'Пожалуйста, выберите обе даты'
                });
            }
        },

        showAuditDetails(audit) {
            this.selectedAudit = audit;
            new bootstrap.Modal(document.getElementById('auditDetailsModal')).show();
        },

        showChangesComparison(audit) {
            this.selectedAudit = audit;
            new bootstrap.Modal(document.getElementById('changesComparisonModal')).show();
        },

        async exportToCsv() {
            try {
                const params = { ...this.filters, export: 'csv' };
                const response = await axios.get('/admin/markups/audit-export', {
                    params,
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `markup-audit-${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                link.remove();

            } catch (error) {
                console.error('Export error:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка экспорта',
                    text: 'Не удалось экспортировать данные'
                });
            }
        },

        async exportToJson() {
            try {
                const params = { ...this.filters, export: 'json' };
                const response = await axios.get('/admin/markups/audit-export', {
                    params,
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `markup-audit-${new Date().toISOString().split('T')[0]}.json`);
                document.body.appendChild(link);
                link.click();
                link.remove();

            } catch (error) {
                console.error('Export error:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка экспорта',
                    text: 'Не удалось экспортировать данные'
                });
            }
        },

        initializeActivityChart() {
            if (this.$refs.activityChart) {
                this.activityChart = new Chart(this.$refs.activityChart, {
                    type: 'bar',
                    data: this.activityChartData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Количество изменений'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Дата'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
            }
        },

        updateActivityChart(activityData) {
            if (this.activityChart && activityData) {
                this.activityChart.data.labels = activityData.labels || [];
                this.activityChart.data.datasets = activityData.datasets || [];
                this.activityChart.update();
            }
        },

        // Вспомогательные методы
        getActionLabel(action) {
            const labels = {
                'created': 'Создана',
                'updated': 'Обновлена',
                'deleted': 'Удалена'
            };
            return labels[action] || action;
        },

        getActionBadge(action) {
            const badges = {
                'created': 'bg-success',
                'updated': 'bg-warning text-dark',
                'deleted': 'bg-danger'
            };
            return badges[action] || 'bg-secondary';
        },

        getMarkupDescription(markup) {
            if (!markup) return '—';

            const typeLabel = this.getMarkupTypeLabel(markup.type);
            const entityLabel = this.getEntityTypeLabel(markup.entity_type);

            return `${typeLabel} • ${entityLabel}`;
        },

        getMarkupTypeLabel(type) {
            const labels = {
                'fixed': 'Фиксированная',
                'percent': 'Процентная',
                'tiered': 'Ступенчатая',
                'combined': 'Комбинированная',
                'seasonal': 'Сезонная'
            };
            return labels[type] || type;
        },

        getEntityTypeLabel(entityType) {
            const labels = {
                'order': 'Заказы',
                'rental_request': 'Заявки',
                'proposal': 'Предложения'
            };
            return labels[entityType] || entityType;
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU');
        },

        formatTime(dateString) {
            return new Date(dateString).toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('ru-RU');
        },

        formatJson(data) {
            return JSON.stringify(data, null, 2);
        },

        highlightChanges(text, compareWith) {
            if (!text || !compareWith || text === compareWith) {
                return this.escapeHtml(text);
            }

            // Простая подсветка различий
            return this.escapeHtml(text)
                .replace(new RegExp(this.escapeRegex(compareWith), 'g'), '<mark>$&</mark>');
        },

        escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
    }
};
</script>

<style scoped>
.markup-audit-log {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.audit-row {
    transition: background-color 0.2s ease;
}

.audit-row:hover {
    background-color: #f8f9fa !important;
}

.changes-preview {
    max-width: 200px;
}

.reason-text {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Стили для модальных окон */
.modal pre {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 12px;
    border: 1px solid #e9ecef;
    max-height: 400px;
    overflow: auto;
}

/* Подсветка изменений */
mark {
    background-color: #fff3cd;
    padding: 1px 4px;
    border-radius: 2px;
}

/* Пагинация */
.pagination {
    margin-bottom: 0;
}

/* Статистика */
.statistics .badge {
    font-size: 0.75rem;
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
