<template>
    <div class="markup-bulk-actions">
        <!-- Заголовок и статистика -->
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-collection me-2"></i>
                    Массовые операции с наценками
                </h5>
                <div class="statistics">
                    <span class="badge bg-primary me-2">Выбрано: {{ selectedCount }}</span>
                    <span class="badge bg-success">Всего: {{ totalCount }}</span>
                </div>
            </div>

            <div class="card-body">
                <!-- Быстрый выбор -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="fw-bold">Быстрый выбор:</span>
                            <button
                                class="btn btn-sm btn-outline-primary"
                                @click="selectAll"
                                :disabled="isProcessing"
                            >
                                Все
                            </button>
                            <button
                                class="btn btn-sm btn-outline-primary"
                                @click="selectByType('active')"
                                :disabled="isProcessing"
                            >
                                Активные
                            </button>
                            <button
                                class="btn btn-sm btn-outline-primary"
                                @click="selectByType('inactive')"
                                :disabled="isProcessing"
                            >
                                Неактивные
                            </button>
                            <button
                                class="btn btn-sm btn-outline-primary"
                                @click="selectByType('expired')"
                                :disabled="isProcessing"
                            >
                                Истекшие
                            </button>
                            <button
                                class="btn btn-sm btn-outline-primary"
                                @click="selectByType('general')"
                                :disabled="isProcessing"
                            >
                                Общие
                            </button>
                            <button
                                class="btn btn-sm btn-outline-secondary"
                                @click="clearSelection"
                                :disabled="isProcessing"
                            >
                                Сбросить
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Массовые операции -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-warning bg-opacity-25">
                                <h6 class="mb-0">Изменение статуса</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Новый статус</label>
                                    <select class="form-select" v-model="bulkActions.status">
                                        <option value="activate">Активировать</option>
                                        <option value="deactivate">Деактивировать</option>
                                        <option value="toggle">Переключить</option>
                                    </select>
                                </div>
                                <button
                                    class="btn btn-warning w-100"
                                    @click="executeBulkStatus"
                                    :disabled="!canExecuteAction"
                                >
                                    <i class="bi bi-power me-1"></i>
                                    Применить статус
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-info bg-opacity-25">
                                <h6 class="mb-0">Изменение приоритета</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Новый приоритет</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model="bulkActions.priority"
                                        min="0"
                                        max="999"
                                        placeholder="0-999"
                                    >
                                    <div class="form-text">
                                        Общие: 0-99, Компании: 100-199, Категории: 200-299, Оборудование: 300-399
                                    </div>
                                </div>
                                <button
                                    class="btn btn-info w-100"
                                    @click="executeBulkPriority"
                                    :disabled="!canExecuteAction"
                                >
                                    <i class="bi bi-sort-numeric-up me-1"></i>
                                    Обновить приоритет
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-danger bg-opacity-25">
                                <h6 class="mb-0">Опасные операции</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Действие</label>
                                    <select class="form-select" v-model="bulkActions.dangerous">
                                        <option value="duplicate">Дублировать</option>
                                        <option value="delete">Удалить</option>
                                        <option value="archive">Архивировать</option>
                                    </select>
                                </div>
                                <button
                                    class="btn btn-danger w-100"
                                    @click="executeBulkDangerous"
                                    :disabled="!canExecuteAction"
                                >
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Выполнить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Расширенные массовые операции -->
                <div class="card mb-4">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h6 class="mb-0">Расширенные массовые операции</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Обновление значений</label>
                                <select class="form-select" v-model="advancedActions.valueUpdate.type">
                                    <option value="fixed">Фиксированное значение</option>
                                    <option value="increase">Увеличить на %</option>
                                    <option value="decrease">Уменьшить на %</option>
                                    <option value="multiply">Умножить на</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Значение</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="advancedActions.valueUpdate.value"
                                    step="0.01"
                                    placeholder="0.00"
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Типы наценок</label>
                                <select class="form-select" v-model="advancedActions.valueUpdate.applyToTypes" multiple>
                                    <option value="fixed">Фиксированные</option>
                                    <option value="percent">Процентные</option>
                                    <option value="tiered">Ступенчатые</option>
                                    <option value="combined">Комбинированные</option>
                                    <option value="seasonal">Сезонные</option>
                                </select>
                                <div class="form-text">Удерживайте Ctrl для выбора нескольких</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button
                                    class="btn btn-primary w-100"
                                    @click="executeValueUpdate"
                                    :disabled="!canExecuteAction"
                                >
                                    <i class="bi bi-calculator me-1"></i>
                                    Обновить значения
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Обновление периодов действия -->
                <div class="card mb-4">
                    <div class="card-header bg-success bg-opacity-10">
                        <h6 class="mb-0">Обновление периодов действия</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Действует с</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="advancedActions.periodUpdate.valid_from"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Действует до</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="advancedActions.periodUpdate.valid_to"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Опция</label>
                                <select class="form-select" v-model="advancedActions.periodUpdate.option">
                                    <option value="set">Установить даты</option>
                                    <option value="extend">Продлить на (дни)</option>
                                    <option value="clear">Очистить даты</option>
                                </select>
                            </div>

                            <div class="col-md-12" v-if="advancedActions.periodUpdate.option === 'extend'">
                                <label class="form-label">Количество дней</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="advancedActions.periodUpdate.extendDays"
                                    min="1"
                                    placeholder="30"
                                >
                            </div>

                            <div class="col-md-12">
                                <button
                                    class="btn btn-success"
                                    @click="executePeriodUpdate"
                                    :disabled="!canExecuteAction"
                                >
                                    <i class="bi bi-calendar-range me-1"></i>
                                    Обновить периоды
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Предпросмотр и выполнение -->
                <div class="card">
                    <div class="card-header bg-secondary bg-opacity-10">
                        <h6 class="mb-0">Предпросмотр и выполнение</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        v-model="previewMode"
                                        id="previewMode"
                                    >
                                    <label class="form-check-label" for="previewMode">
                                        Режим предпросмотра (показывает изменения без применения)
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        v-model="sendNotifications"
                                        id="sendNotifications"
                                    >
                                    <label class="form-check-label" for="sendNotifications">
                                        Отправить уведомления о изменениях
                                    </label>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label">Причина изменений (для аудита)</label>
                                    <textarea
                                        class="form-control"
                                        v-model="changeReason"
                                        rows="2"
                                        placeholder="Опишите причину массовых изменений..."
                                    ></textarea>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="d-grid gap-2">
                                    <button
                                        class="btn btn-primary btn-lg"
                                        @click="executeAllActions"
                                        :disabled="!canExecuteAction || isProcessing"
                                    >
                                        <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="bi bi-play-circle me-2"></i>
                                        {{ isProcessing ? 'Выполнение...' : 'Выполнить все операции' }}
                                    </button>

                                    <button
                                        class="btn btn-outline-secondary"
                                        @click="generatePreview"
                                        :disabled="!canExecuteAction || isProcessing"
                                    >
                                        <i class="bi bi-eye me-1"></i>
                                        Предпросмотр изменений
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Список выбранных наценок -->
        <div class="card mt-4" v-if="selectedMarkups.length > 0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Выбранные наценки ({{ selectedCount }})</h6>
                <button class="btn btn-sm btn-outline-danger" @click="clearSelection">
                    <i class="bi bi-x-circle me-1"></i>
                    Очистить выбор
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleAllSelection"
                                    >
                                </th>
                                <th width="80">ID</th>
                                <th>Описание</th>
                                <th width="120">Тип</th>
                                <th width="100">Значение</th>
                                <th width="100">Приоритет</th>
                                <th width="100">Статус</th>
                                <th width="120">Период действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="markup in paginatedSelected"
                                :key="markup.id"
                                :class="{ 'table-primary': selectedMarkups.includes(markup.id) }"
                            >
                                <td>
                                    <input
                                        type="checkbox"
                                        :value="markup.id"
                                        v-model="selectedMarkups"
                                    >
                                </td>
                                <td class="fw-bold">#{{ markup.id }}</td>
                                <td>
                                    <div class="markup-description">
                                        <div class="fw-bold">{{ getMarkupDescription(markup) }}</div>
                                        <div class="small text-muted">
                                            {{ getEntityTypeLabel(markup.entity_type) }}
                                            <span v-if="markup.markupable" class="ms-1">
                                                • {{ getMarkupableName(markup) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="getMarkupTypeBadge(markup.type)">
                                        {{ getMarkupTypeLabel(markup.type) }}
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    {{ markup.value }}
                                    <span v-if="markup.type === 'percent'">%</span>
                                    <span v-else>₽</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ markup.priority }}</span>
                                </td>
                                <td>
                                    <span class="badge" :class="markup.is_active ? 'bg-success' : 'bg-danger'">
                                        {{ markup.is_active ? 'Активна' : 'Неактивна' }}
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <div>{{ markup.valid_from ? formatDate(markup.valid_from) : '∞' }}</div>
                                        <div>{{ markup.valid_to ? formatDate(markup.valid_to) : '∞' }}</div>
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Пагинация выбранных -->
                <div class="card-footer" v-if="selectedPages > 1">
                    <nav>
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <li class="page-item" :class="{ disabled: selectedPage === 1 }">
                                <button class="page-link" @click="selectedPage--">‹</button>
                            </li>
                            <li
                                v-for="page in selectedPages"
                                :key="page"
                                class="page-item"
                                :class="{ active: page === selectedPage }"
                            >
                                <button class="page-link" @click="selectedPage = page">{{ page }}</button>
                            </li>
                            <li class="page-item" :class="{ disabled: selectedPage === selectedPages }">
                                <button class="page-link" @click="selectedPage++">›</button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Результаты выполнения -->
        <div class="card mt-4" v-if="executionResults.length > 0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Результаты выполнения</h6>
                <button class="btn btn-sm btn-outline-secondary" @click="clearResults">
                    <i class="bi bi-trash me-1"></i>
                    Очистить
                </button>
            </div>
            <div class="card-body">
                <div class="execution-results">
                    <div
                        v-for="result in executionResults"
                        :key="result.id"
                        class="alert"
                        :class="getResultAlertClass(result.status)"
                    >
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="alert-heading">
                                    <i :class="getResultIcon(result.status)" class="me-2"></i>
                                    {{ result.operation }}
                                </h6>
                                <div class="mb-2">
                                    <strong>Обработано:</strong> {{ result.processed }}/{{ result.total }}
                                    <span class="ms-2" v-if="result.success > 0">
                                        <i class="bi bi-check-circle text-success me-1"></i>
                                        Успешно: {{ result.success }}
                                    </span>
                                    <span class="ms-2" v-if="result.errors > 0">
                                        <i class="bi bi-x-circle text-danger me-1"></i>
                                        Ошибки: {{ result.errors }}
                                    </span>
                                </div>
                                <div v-if="result.message" class="small">
                                    {{ result.message }}
                                </div>
                                <div v-if="result.details" class="mt-2">
                                    <button
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="toggleResultDetails(result)"
                                    >
                                        <i class="bi" :class="result.showDetails ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                        Детали
                                    </button>

                                    <div v-if="result.showDetails" class="mt-2">
                                        <div
                                            v-for="detail in result.details"
                                            :key="detail.id"
                                            class="small border-start border-3 ps-2 mb-1"
                                            :class="getDetailBorderClass(detail.status)"
                                        >
                                            <strong>#{{ detail.markup_id }}</strong>:
                                            {{ detail.message }}
                                            <span class="text-muted">({{ detail.execution_time }}мс)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small">
                                    {{ formatTime(result.timestamp) }}
                                </div>
                                <button
                                    class="btn btn-sm btn-outline-primary mt-2"
                                    @click="exportResult(result)"
                                    v-if="result.status === 'completed'"
                                >
                                    <i class="bi bi-download me-1"></i>
                                    Экспорт
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно предпросмотра -->
        <div class="modal fade" id="previewModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Предпросмотр массовых изменений</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="previewData" class="preview-content">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Это предпросмотр изменений. Данные не будут сохранены до подтверждения.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Текущее значение</th>
                                            <th>Новое значение</th>
                                            <th>Изменение</th>
                                            <th>Статус</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in previewData.changes" :key="item.markup_id">
                                            <td class="fw-bold">#{{ item.markup_id }}</td>
                                            <td>
                                                <pre class="mb-0 small">{{ formatPreviewValue(item.current) }}</pre>
                                            </td>
                                            <td>
                                                <pre class="mb-0 small">{{ formatPreviewValue(item.new) }}</pre>
                                            </td>
                                            <td>
                                                <span class="badge" :class="getChangeTypeBadge(item.change_type)">
                                                    {{ item.change_type }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Готово</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <h6>Сводка изменений:</h6>
                                <ul>
                                    <li v-for="summary in previewData.summary" :key="summary.type">
                                        {{ summary.type }}: {{ summary.count }} наценок
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="button" class="btn btn-primary" @click="confirmExecution">
                            <i class="bi bi-check-circle me-1"></i>
                            Подтвердить выполнение
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MarkupBulkActions',

    data() {
        return {
            // Данные
            allMarkups: [],
            selectedMarkups: [],

            // Пагинация
            selectedPage: 1,
            pageSize: 10,

            // Действия
            bulkActions: {
                status: 'activate',
                priority: 0,
                dangerous: 'duplicate'
            },

            advancedActions: {
                valueUpdate: {
                    type: 'fixed',
                    value: 0,
                    applyToTypes: []
                },
                periodUpdate: {
                    valid_from: '',
                    valid_to: '',
                    option: 'set',
                    extendDays: 30
                }
            },

            // Настройки
            previewMode: true,
            sendNotifications: false,
            changeReason: '',

            // Состояние
            isProcessing: false,
            executionResults: [],
            previewData: null
        };
    },

    computed: {
        selectedCount() {
            return this.selectedMarkups.length;
        },

        totalCount() {
            return this.allMarkups.length;
        },

        isAllSelected() {
            return this.selectedCount > 0 && this.selectedCount === this.totalCount;
        },

        canExecuteAction() {
            return this.selectedCount > 0 && !this.isProcessing;
        },

        selectedPages() {
            return Math.ceil(this.selectedCount / this.pageSize);
        },

        paginatedSelected() {
            const start = (this.selectedPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            return this.allMarkups.filter(m => this.selectedMarkups.includes(m.id)).slice(start, end);
        }
    },

    mounted() {
        this.loadMarkups();
    },

    methods: {
        async loadMarkups() {
            try {
                const response = await axios.get('/admin/markups/bulk-data');
                this.allMarkups = response.data.markups || [];
            } catch (error) {
                console.error('Error loading markups:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка загрузки',
                    text: 'Не удалось загрузить список наценок'
                });
            }
        },

        // Методы выбора
        selectAll() {
            this.selectedMarkups = this.allMarkups.map(m => m.id);
        },

        selectByType(type) {
            switch (type) {
                case 'active':
                    this.selectedMarkups = this.allMarkups
                        .filter(m => m.is_active)
                        .map(m => m.id);
                    break;
                case 'inactive':
                    this.selectedMarkups = this.allMarkups
                        .filter(m => !m.is_active)
                        .map(m => m.id);
                    break;
                case 'expired':
                    this.selectedMarkups = this.allMarkups
                        .filter(m => m.valid_to && new Date(m.valid_to) < new Date())
                        .map(m => m.id);
                    break;
                case 'general':
                    this.selectedMarkups = this.allMarkups
                        .filter(m => !m.markupable_type)
                        .map(m => m.id);
                    break;
            }
        },

        clearSelection() {
            this.selectedMarkups = [];
            this.selectedPage = 1;
        },

        toggleAllSelection() {
            if (this.isAllSelected) {
                this.clearSelection();
            } else {
                this.selectAll();
            }
        },

        // Массовые операции
        async executeBulkStatus() {
            await this.executeBulkOperation('status', {
                action: this.bulkActions.status
            });
        },

        async executeBulkPriority() {
            if (this.bulkActions.priority === '') {
                this.$swal.fire({
                    icon: 'warning',
                    title: 'Внимание',
                    text: 'Пожалуйста, укажите значение приоритета'
                });
                return;
            }

            await this.executeBulkOperation('priority', {
                priority: parseInt(this.bulkActions.priority)
            });
        },

        async executeBulkDangerous() {
            const result = await this.$swal.fire({
                icon: 'warning',
                title: 'Подтверждение опасной операции',
                text: `Вы уверены, что хотите выполнить "${this.getDangerousActionLabel()}" для ${this.selectedCount} наценок?`,
                showCancelButton: true,
                confirmButtonText: 'Да, выполнить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#dc3545'
            });

            if (result.isConfirmed) {
                await this.executeBulkOperation('dangerous', {
                    action: this.bulkActions.dangerous
                });
            }
        },

        async executeValueUpdate() {
            if (this.advancedActions.valueUpdate.value === '') {
                this.$swal.fire({
                    icon: 'warning',
                    title: 'Внимание',
                    text: 'Пожалуйста, укажите значение для обновления'
                });
                return;
            }

            await this.executeBulkOperation('value-update', this.advancedActions.valueUpdate);
        },

        async executePeriodUpdate() {
            await this.executeBulkOperation('period-update', this.advancedActions.periodUpdate);
        },

        async executeAllActions() {
            const operations = [];

            // Собираем все операции
            if (this.bulkActions.status) {
                operations.push({
                    type: 'status',
                    data: { action: this.bulkActions.status }
                });
            }

            if (this.bulkActions.priority) {
                operations.push({
                    type: 'priority',
                    data: { priority: parseInt(this.bulkActions.priority) }
                });
            }

            if (this.advancedActions.valueUpdate.value) {
                operations.push({
                    type: 'value-update',
                    data: this.advancedActions.valueUpdate
                });
            }

            if (this.advancedActions.periodUpdate.valid_from || this.advancedActions.periodUpdate.valid_to) {
                operations.push({
                    type: 'period-update',
                    data: this.advancedActions.periodUpdate
                });
            }

            if (operations.length === 0) {
                this.$swal.fire({
                    icon: 'warning',
                    title: 'Внимание',
                    text: 'Не выбрано ни одной операции для выполнения'
                });
                return;
            }

            await this.executeBulkOperation('batch', { operations });
        },

        async executeBulkOperation(operationType, data) {
            this.isProcessing = true;

            try {
                const payload = {
                    markups: this.selectedMarkups,
                    operation: operationType,
                    data: data,
                    preview: this.previewMode,
                    send_notifications: this.sendNotifications,
                    reason: this.changeReason
                };

                const response = await axios.post('/admin/markups/bulk-operations', payload);

                this.executionResults.unshift({
                    id: Date.now(),
                    operation: this.getOperationLabel(operationType),
                    status: response.data.status,
                    processed: response.data.processed,
                    total: this.selectedCount,
                    success: response.data.success || 0,
                    errors: response.data.errors || 0,
                    message: response.data.message,
                    details: response.data.details,
                    timestamp: new Date(),
                    showDetails: false
                });

                if (response.data.status === 'completed' && !this.previewMode) {
                    // Обновляем данные
                    this.loadMarkups();
                    this.clearSelection();

                    this.$swal.fire({
                        icon: 'success',
                        title: 'Операция выполнена',
                        text: response.data.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                }

            } catch (error) {
                console.error('Bulk operation error:', error);

                this.executionResults.unshift({
                    id: Date.now(),
                    operation: this.getOperationLabel(operationType),
                    status: 'error',
                    processed: 0,
                    total: this.selectedCount,
                    success: 0,
                    errors: this.selectedCount,
                    message: error.response?.data?.message || 'Ошибка выполнения операции',
                    timestamp: new Date(),
                    showDetails: false
                });

                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка выполнения',
                    text: error.response?.data?.message || 'Произошла ошибка при выполнении операции'
                });
            } finally {
                this.isProcessing = false;
            }
        },

        async generatePreview() {
            await this.executeBulkOperation('preview', {
                operations: [
                    { type: 'status', data: { action: this.bulkActions.status } },
                    { type: 'priority', data: { priority: parseInt(this.bulkActions.priority) } },
                    { type: 'value-update', data: this.advancedActions.valueUpdate },
                    { type: 'period-update', data: this.advancedActions.periodUpdate }
                ]
            });
        },

        confirmExecution() {
            this.previewMode = false;
            this.executeAllActions();
            bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();
        },

        // Вспомогательные методы
        getOperationLabel(operationType) {
            const labels = {
                'status': 'Изменение статуса',
                'priority': 'Обновление приоритета',
                'dangerous': 'Опасная операция',
                'value-update': 'Обновление значений',
                'period-update': 'Обновление периодов',
                'batch': 'Пакет операций',
                'preview': 'Предпросмотр'
            };
            return labels[operationType] || operationType;
        },

        getDangerousActionLabel() {
            const labels = {
                'duplicate': 'Дублирование',
                'delete': 'Удаление',
                'archive': 'Архивирование'
            };
            return labels[this.bulkActions.dangerous] || this.bulkActions.dangerous;
        },

        getResultAlertClass(status) {
            return {
                'completed': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[status] || 'alert-secondary';
        },

        getResultIcon(status) {
            return {
                'completed': 'bi-check-circle',
                'error': 'bi-x-circle',
                'warning': 'bi-exclamation-triangle',
                'info': 'bi-info-circle'
            }[status] || 'bi-question-circle';
        },

        getDetailBorderClass(status) {
            return {
                'success': 'border-success',
                'error': 'border-danger',
                'warning': 'border-warning'
            }[status] || 'border-secondary';
        },

        getChangeTypeBadge(changeType) {
            const badges = {
                'modified': 'bg-warning text-dark',
                'added': 'bg-success',
                'removed': 'bg-danger',
                'unchanged': 'bg-secondary'
            };
            return badges[changeType] || 'bg-light text-dark';
        },

        toggleResultDetails(result) {
            result.showDetails = !result.showDetails;
        },

        clearResults() {
            this.executionResults = [];
        },

        exportResult(result) {
            // Реализация экспорта результатов
            console.log('Export result:', result);
        },

        // Методы форматирования (аналогичные предыдущим компонентам)
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

        getMarkupTypeBadge(type) {
            const badges = {
                'fixed': 'bg-primary',
                'percent': 'bg-success',
                'tiered': 'bg-warning text-dark',
                'combined': 'bg-info',
                'seasonal': 'bg-secondary'
            };
            return badges[type] || 'bg-light text-dark';
        },

        getEntityTypeLabel(entityType) {
            const labels = {
                'order': 'Заказы',
                'rental_request': 'Заявки',
                'proposal': 'Предложения'
            };
            return labels[entityType] || entityType;
        },

        getMarkupableName(markup) {
            if (!markup.markupable) return 'N/A';
            return markup.markupable.name || markup.markupable.title || 'N/A';
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU');
        },

        formatTime(dateString) {
            return new Date(dateString).toLocaleTimeString('ru-RU');
        },

        formatPreviewValue(value) {
            return JSON.stringify(value, null, 2);
        }
    }
};
</script>

<style scoped>
.markup-bulk-actions {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.markup-description {
    max-width: 300px;
}

.execution-results .alert {
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #198754 !important;
}

.alert-danger {
    border-left-color: #dc3545 !important;
}

.alert-warning {
    border-left-color: #ffc107 !important;
}

.alert-info {
    border-left-color: #0dcaf0 !important;
}

/* Стили для таблицы выбранных */
.table-primary {
    background-color: #e7f1ff !important;
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

/* Предпросмотр */
.preview-content pre {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 8px;
    border: 1px solid #e9ecef;
    font-size: 0.875em;
    margin-bottom: 0;
}

/* Мультиселект */
select[multiple] {
    height: 100px;
}
</style>
