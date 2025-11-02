<template>
    <div class="rental-request-show" v-if="request">
        <!-- Заголовок и навигация -->
        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-12">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title">Заявка на аренду: {{ request.title }}</h1>
                        <div>
                            <a :href="'/lessee/rental-requests'" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Назад к списку
                            </a>
                            <button v-if="request.status === 'active'"
                                    class="btn btn-warning me-2"
                                    @click="showPauseModal = true">
                                <i class="fas fa-pause me-2"></i>Приостановить
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Хлебные крошки статуса -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="status-breadcrumb">
                        <div class="step" :class="getStatusStepClass('active')">
                            <span class="step-number">1</span>
                            <span class="step-label">Активна</span>
                        </div>
                        <div class="step" :class="getStatusStepClass('processing')">
                            <span class="step-number">2</span>
                            <span class="step-label">В процессе</span>
                        </div>
                        <div class="step" :class="getStatusStepClass('completed')">
                            <span class="step-number">3</span>
                            <span class="step-label">Завершена</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Статистика заявки -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="request-stats-card card">
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
                                    <div class="stat-value">{{ formatCurrency(request.total_budget || request.calculated_budget_from) }}</div>
                                    <div class="stat-label">Общий бюджет</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Навигация по категориям -->
            <div v-if="groupedByCategory.length > 1" class="row mb-4">
                <div class="col-12">
                    <div class="category-nav card">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Быстрая навигация по категориям</h6>
                            <div class="nav-buttons">
                                <button
                                    v-for="category in groupedByCategory"
                                    :key="category.category_id"
                                    class="btn btn-outline-primary btn-sm"
                                    @click="scrollToCategory(category.category_id)"
                                >
                                    {{ category.category_name }} ({{ category.items_count }})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ⚠️ КОМПОНЕНТ ПРОГРЕССА ЭКСПОРТА PDF -->
            <div v-if="isExportingPDF" class="row mb-3">
                <div class="col-12">
                    <div class="export-progress-container">
                        <div class="alert alert-info d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-3" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">Идет экспорт PDF...</div>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                                         :style="{ width: pdfExportProgress + '%' }"></div>
                                </div>
                                <small class="text-muted">{{ pdfExportProgress }}%</small>
                            </div>
                            <button type="button" class="btn-close" @click="cancelExportPDF"></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ⚠️ ОШИБКА ЭКСПОРТА PDF -->
            <div v-if="exportError" class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Ошибка экспорта:</strong> {{ exportError }}
                        <button type="button" class="btn-close" @click="exportError = null"></button>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Основная информация -->
                <div class="col-lg-8">
                    <!-- Карточка основной информации -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Основная информация
                            </h5>
                            <span class="badge" :class="getStatusBadgeClass(request.status)">
                                {{ getStatusDisplayText(request.status) }}
                            </span>
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
                                            {{ formatDate(request.rental_period_start) }} - {{ formatDate(request.rental_period_end) }}
                                            <br>
                                            <small class="text-muted">
                                                {{ calculateRentalDays(request.rental_period_start, request.rental_period_end) }} дней
                                            </small>
                                        </p>
                                    </div>

                                    <div class="info-item mb-3">
                                        <label class="text-muted small">Бюджет заявки</label>
                                        <p class="mb-0 fs-5 text-success fw-bold">
                                            {{ formatCurrency(request.total_budget || request.calculated_budget_from) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Позиции заявки (новая структура) -->
                    <div class="card mb-4" v-if="request.items && request.items.length > 0">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cubes me-2"></i>
                                Позиции заявки
                                <span class="badge bg-primary ms-2">{{ summary.total_items }}</span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <!-- Группировка по категориям -->
                            <div v-if="groupedByCategory.length > 0" class="categories-list">
                                <CategoryGroup
                                    v-for="category in groupedByCategory"
                                    :key="category.category_id"
                                    :category="category"
                                    :initially-expanded="groupedByCategory.length <= 3"
                                />
                            </div>

                            <!-- Прямой список если нет группировки -->
                            <div v-else class="positions-list p-3">
                                <PositionCard
                                    v-for="item in request.items"
                                    :key="item.id"
                                    :item="item"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Предложения от арендодателей -->
                    <ProposalsList
                        :request-id="requestId"
                        :proposals="proposals"
                        @proposal-rejected="onProposalRejected"
                    />
                </div>

                <!-- Боковая панель -->
                <div class="col-lg-4">
                    <!-- Статистика заявки -->
                    <RequestStats
                        :request="request"
                        :views-count="request.views_count || 0"
                        :proposals-count="request.responses_count || 0"
                        :items-count="request.items ? request.items.length : 0"
                    />

                    <!-- Действия с заявкой -->
                    <RequestActions
                        :request="request"
                        @pause-request="pauseRequest"
                        @resume-request="resumeRequest"
                        @cancel-request="cancelRequest"
                        @edit-request="editRequest"
                    />

                    <!-- Быстрые действия -->
                    <QuickActions
                        ref="quickActions"
                        :request-id="request.id"
                        @export-pdf="handleExportPDF"
                    />
                </div>
            </div>
        </div>

        <!-- Модальные окна -->
        <PauseRequestModal
            v-if="showPauseModal"
            :request-id="request.id"
            @confirmed="pauseRequest"
            @closed="showPauseModal = false"
        />

        <CancelRequestModal
            v-if="showCancelModal"
            :request-id="request.id"
            @confirmed="cancelRequest"
            @closed="showCancelModal = false"
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
import ProposalsList from './ProposalsList.vue';
import RequestStats from './RequestStats.vue';
import RequestActions from './RequestActions.vue';
import QuickActions from './QuickActions.vue';
import PauseRequestModal from './PauseRequestModal.vue';
import CancelRequestModal from './CancelRequestModal.vue';
import RentalConditionsDisplay from './RentalConditionsDisplay.vue';
import CategoryGroup from './CategoryGroup.vue';
import PositionCard from './PositionCard.vue';

export default {
    name: 'RentalRequestShow',
    components: {
        ProposalsList,
        RequestStats,
        RequestActions,
        QuickActions,
        PauseRequestModal,
        CancelRequestModal,
        RentalConditionsDisplay,
        CategoryGroup,
        PositionCard
    },
    props: {
        requestId: {
            type: [String, Number],
            required: true
        },
        apiUrl: {
            type: String,
            required: true
        },
        pauseUrl: {
            type: String,
            required: true
        },
        cancelUrl: {
            type: String,
            required: true
        },
        csrfToken: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            loading: true,
            error: null,
            request: null,
            proposals: [],
            showPauseModal: false,
            showCancelModal: false,
            autoRefreshInterval: null,
            groupedByCategory: [],
            summary: {
                total_items: 0,
                total_quantity: 0,
                categories_count: 0
            },
            // ⚠️ ДАННЫЕ ДЛЯ УПРАВЛЕНИЯ ЭКСПОРТОМ PDF
            isExportingPDF: false,
            exportError: null,
            pdfExportProgress: 0,
            pdfExportController: null,
            pdfExportTimeout: null
        }
    },
    computed: {
        statusSteps() {
            return {
                'active': 1,
                'processing': 2,
                'completed': 3
            };
        }
    },
    methods: {
        // ⚠️ МЕТОД ДЛЯ ПОЛУЧЕНИЯ ОТОБРАЖАЕМОГО ИМЕНИ ПАРАМЕТРА
        getParameterDisplayName(key, item) {
              if (!key) return 'Неизвестный параметр';

                // Если это кастомный параметр и есть метаданные с названием
                if (key.startsWith('custom_') && item.custom_specs_metadata?.[key]?.name) {
                    const customName = item.custom_specs_metadata[key].name;
                    return customName.trim() || this.formatCustomParameterName(key);
                }

            // Стандартные параметры - полный маппинг
            const standardNames = {
                // Экскаваторы
                'bucket_volume': 'Объем ковша',
                'engine_power': 'Мощность двигателя',
                'operating_weight': 'Рабочий вес',
                'max_digging_depth': 'Максимальная глубина копания',
                'max_reach': 'Максимальный вылет стрелы',
                'bucket_width': 'Ширина ковша',
                'arm_force': 'Усилие на рукояти',
                'boom_force': 'Усилие на стреле',

                // Бульдозеры
                'blade_width': 'Ширина отвала',
                'blade_height': 'Высота отвала',
                'blade_capacity': 'Объем отвала',
                'max_cutting_depth': 'Максимальная глубина резания',
                'max_lifting_height': 'Максимальная высота подъема',

                // Самосвалы
                'load_capacity': 'Грузоподъемность',
                'body_volume': 'Объем кузова',
                'body_length': 'Длина кузова',
                'body_width': 'Ширина кузова',
                'body_height': 'Высота кузова',
                'unloading_angle': 'Угол разгрузки',
                'axle_configuration': 'Колёсная формула',

                // Краны
                'lifting_capacity': 'Грузоподъёмность',
                'boom_length': 'Длина стрелы',
                'outreach': 'Вылет стрелы',
                'rotation_angle': 'Угол поворота',

                // Катки
                'roller_width': 'Ширина вальца',
                'roller_diameter': 'Диаметр вальца',
                'vibration_frequency': 'Частота вибрации',
                'amplitude': 'Амплитуда',
                'compaction_width': 'Ширина уплотнения',

                // Общие
                'max_speed': 'Максимальная скорость',
                'fuel_tank_capacity': 'Объем топливного бака',
                'transmission': 'Трансмиссия',
                'drive_type': 'Тип привода',

                // Бетонная техника
                'concrete_output': 'Производительность по бетону',
                'max_pressure': 'Максимальное давление',
                'pump_height': 'Высота подачи'
            };

            return standardNames[key] || this.formatParameterKey(key);
        },

        // ⚠️ ВСПОМОГАТЕЛЬНЫЙ МЕТОД ДЛЯ ФОРМАТИРОВАНИЯ КЛЮЧЕЙ КАСТОМНЫХ ПАРАМЕТРОВ
        formatCustomParameterName(key) {
            if (!key.startsWith('custom_')) return key;
            let cleanKey = key.replace(/^custom_/, '');
            cleanKey = cleanKey.replace(/_\d+$/, '');
            return cleanKey
                .split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        // ⚠️ ВСПОМОГАТЕЛЬНЫЙ МЕТОД ДЛЯ ФОРМАТИРОВАНИЯ СТАНДАРТНЫХ КЛЮЧЕЙ
        formatParameterKey(key) {
            return key
                .split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        // ⚠️ МЕТОД ДЛЯ ФОРМАТИРОВАННОГО ОТОБРАЖЕНИЯ ЗНАЧЕНИЯ
        getFormattedValue(value, key, item) {
            if (value === null || value === '' || value === undefined) {
                return 'Не указано';
            }

            // Для кастомных параметров добавляем единицу измерения из метаданных
            if (key.startsWith('custom_') && item.custom_specs_metadata?.[key]?.unit) {
                const unit = item.custom_specs_metadata[key].unit;
                return unit ? `${value} ${unit}` : value;
            }

            // Для стандартных параметров используем предопределенные единицы
            const standardUnits = {
                'bucket_volume': 'м³',
                'engine_power': 'л.с.',
                'operating_weight': 'т',
                'max_digging_depth': 'м',
                'blade_width': 'м',
                'blade_height': 'м',
                'load_capacity': 'т',
                'body_volume': 'м³',
                'max_speed': 'км/ч'
            };

            const unit = standardUnits[key] || '';
            return unit ? `${value} ${unit}` : value;
        },

        // ⚠️ ДОПОЛНИТЕЛЬНЫЙ МЕТОД ДЛЯ ПОЛУЧЕНИЯ ТИПА ДАННЫХ ПАРАМЕТРА
        getParameterDataType(key, item) {
            if (key.startsWith('custom_') && item.custom_specs_metadata?.[key]?.dataType) {
                return item.custom_specs_metadata[key].dataType;
            }

            // Для стандартных параметров определяем по ключу
            const numericParameters = [
                'bucket_volume', 'engine_power', 'operating_weight', 'max_digging_depth',
                'blade_width', 'blade_height', 'load_capacity', 'body_volume', 'max_speed',
                'lifting_capacity', 'boom_length', 'max_lifting_height', 'max_reach',
                'rotation_angle', 'drum_width', 'vibration_frequency', 'concrete_output',
                'max_pressure', 'fuel_tank_capacity'
            ];

            return numericParameters.includes(key) ? 'number' : 'string';
        },

        async loadRequest() {
            this.loading = true;
            this.error = null;

            try {
                console.log('🔄 Загрузка заявки по API URL:', this.apiUrl);

                const response = await fetch(this.apiUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                const contentType = response.headers.get('content-type');
                console.log('📄 Content-Type ответа:', contentType);

                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('❌ Сервер вернул не JSON:', textResponse.substring(0, 500));
                    throw new Error(`API вернул HTML вместо JSON. Status: ${response.status}`);
                }

                if (!response.ok) {
                    throw new Error(`HTTP ошибка! Статус: ${response.status}`);
                }

                const data = await response.json();
                console.log('✅ Данные от API:', data);

                if (data.success) {
                    this.request = data.data;
                    this.groupedByCategory = data.grouped_by_category || [];
                    this.summary = data.summary || {
                        total_items: this.request.items?.length || 0,
                        total_quantity: this.request.items?.reduce((sum, item) => sum + (item.quantity || 0), 0) || 0,
                        categories_count: new Set(this.request.items?.map(item => item.category_id)).size || 0
                    };

                    this.proposals = this.request.responses || [];
                } else {
                    throw new Error(data.message || 'Ошибка загрузки заявки');
                }
            } catch (error) {
                console.error('❌ Ошибка загрузки заявки:', error);
                this.error = error.message;
                this.showFallbackContent();
            } finally {
                this.loading = false;
            }
        },

        // ⚠️ ОБНОВЛЕННЫЙ МЕТОД ДЛЯ ОБРАБОТКИ ЭКСПОРТА PDF
        async handleExportPDF() {
            if (this.isExportingPDF) {
                console.log('🟡 Экспорт уже выполняется, пропускаем');
                return;
            }

            this.isExportingPDF = true;
            this.exportError = null;
            this.pdfExportProgress = 0;

            // Создаем AbortController для возможности отмены
            this.pdfExportController = new AbortController();

            try {
                console.log('🟡 Начало экспорта PDF для заявки:', this.requestId);

                // Симулируем прогресс для лучшего UX
                const progressInterval = setInterval(() => {
                    if (this.pdfExportProgress < 90) {
                        this.pdfExportProgress += 10;
                    }
                }, 500);

                // ⚠️ ТАЙМАУТ ДЛЯ ВСЕГО ПРОЦЕССА ЭКСПОРТА
                this.pdfExportTimeout = setTimeout(() => {
                    if (this.isExportingPDF) {
                        console.warn('🕒 Таймаут экспорта PDF');
                        this.pdfExportController.abort();
                        throw new Error('Экспорт занял слишком много времени');
                    }
                }, 45000);

                // Вызываем метод экспорта из QuickActions
                if (this.$refs.quickActions) {
                    await this.$refs.quickActions.exportToPDF();
                } else {
                    throw new Error('Компонент QuickActions не найден');
                }

                // Успешное завершение
                clearInterval(progressInterval);
                this.pdfExportProgress = 100;

                console.log('✅ Экспорт PDF завершен успешно');

            } catch (error) {
                console.error('❌ Ошибка экспорта PDF:', error);
                this.exportError = this.getExportErrorMessage(error);

                // Показываем детали ошибки в консоли для отладки
                if (error.response) {
                    console.error('📡 Ответ сервера:', error.response);
                }
                if (error.request) {
                    console.error('🌐 Запрос:', error.request);
                }
            } finally {
                this.cleanupExport();
            }
        },

        // ⚠️ МЕТОД ДЛЯ ОТМЕНЫ ЭКСПОРТА
        cancelExportPDF() {
            console.log('🛑 Отмена экспорта PDF пользователем');

            if (this.pdfExportController) {
                this.pdfExportController.abort();
            }

            this.cleanupExport();
            this.exportError = 'Экспорт отменен пользователем';
        },

        // ⚠️ ОЧИСТКА РЕСУРСОВ ЭКСПОРТА
        cleanupExport() {
            this.isExportingPDF = false;
            this.pdfExportProgress = 0;

            if (this.pdfExportTimeout) {
                clearTimeout(this.pdfExportTimeout);
                this.pdfExportTimeout = null;
            }

            this.pdfExportController = null;
        },

        // ⚠️ ФОРМИРОВАНИЕ ЧЕЛОВЕКОЧИТАЕМЫХ СООБЩЕНИЙ ОБ ОШИБКАХ
        getExportErrorMessage(error) {
            console.log('🔍 Анализ ошибки экспорта:', error);

            if (error.message.includes('canceled') || error.message.includes('abort')) {
                return 'Экспорт отменен';
            }

            if (error.message.includes('Timeout') || error.message.includes('timeout')) {
                return 'Генерация PDF заняла слишком много времени. Попробуйте позже или обратитесь к администратору.';
            }

            if (error.message.includes('Network Error')) {
                return 'Проблемы с соединением. Проверьте интернет-подключение.';
            }

            if (error.response) {
                const status = error.response.status;
                switch (status) {
                    case 404:
                        return 'Функция экспорта PDF недоступна. Пожалуйста, сообщите администратору.';
                    case 500:
                        return 'Ошибка сервера при генерации PDF. Попробуйте позже.';
                    case 403:
                        return 'У вас нет прав для экспорта этой заявки';
                    case 401:
                        return 'Необходима авторизация для экспорта PDF';
                    default:
                        return `Ошибка сервера (${status}). Попробуйте позже.`;
                }
            }

            if (error.request) {
                return 'Не удалось подключиться к серверу. Проверьте соединение.';
            }

            return error.message || 'Неизвестная ошибка при экспорте PDF';
        },

        // ⚠️ МЕТОД ДЛЯ ПРОВЕРКИ ДОСТУПНОСТИ PDF ЭКСПОРТА
        async checkPDFEndpoint() {
            try {
                console.log('🔍 Проверка доступности PDF endpoint');

                const response = await fetch(`/api/lessee/rental-requests/${this.requestId}/export-pdf`, {
                    method: 'HEAD',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/pdf'
                    },
                    credentials: 'include'
                });

                console.log('📡 Статус проверки PDF endpoint:', response.status);
                return response.ok;
            } catch (error) {
                console.error('❌ Ошибка проверки PDF endpoint:', error);
                return false;
            }
        },

        getStatusBadgeClass(status) {
            const classes = {
                'draft': 'bg-secondary',
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
                'draft': 'Черновик',
                'active': 'Активна',
                'paused': 'Приостановлена',
                'processing': 'Приостановлена',
                'completed': 'Завершена',
                'cancelled': 'Отменена'
            };
            return texts[status] || status;
        },

        async resumeRequest() {
            try {
                const url = `/api/lessee/rental-requests/${this.requestId}/resume`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message);
                    await this.loadRequest();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                this.showToast('error', error.message);
            }
        },

        scrollToCategory(categoryId) {
            const element = document.getElementById(`category-${categoryId}`);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        showFallbackContent() {
            const vueApp = document.getElementById('rental-request-show-app');
            const fallbackContent = document.getElementById('blade-fallback-content');

            if (vueApp && fallbackContent) {
                console.log('🔄 Переключаемся на резервный Blade контент');
                vueApp.style.display = 'none';
                fallbackContent.style.display = 'block';
            }
        },

        getStatusStepClass(targetStatus) {
            if (!this.request) return '';

            const currentStep = this.statusSteps[this.request.status] || 0;
            const targetStep = this.statusSteps[targetStatus] || 0;

            if (currentStep > targetStep) return 'completed';
            if (currentStep === targetStep) return 'active';
            return '';
        },

        getStatusColor(status) {
            const colors = {
                'active': 'success',
                'processing': 'warning',
                'completed': 'primary',
                'cancelled': 'danger',
                'draft': 'secondary'
            };
            return colors[status] || 'light';
        },

        getStatusText(status) {
            const texts = {
                'active': 'Активна',
                'processing': 'В процессе',
                'completed': 'Завершена',
                'cancelled': 'Отменена',
                'draft': 'Черновик'
            };
            return texts[status] || status;
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU');
        },

        formatCurrency(amount) {
            if (!amount) return '0 ₽';
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        },

        calculateRentalDays(startDate, endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        },

        async pauseRequest() {
            try {
                const response = await fetch(this.pauseUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message);
                    await this.loadRequest();
                    this.showPauseModal = false;
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                this.showToast('error', error.message);
            }
        },

        async cancelRequest() {
            try {
                const response = await fetch(this.cancelUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message);
                    await this.loadRequest();
                    this.showCancelModal = false;
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                this.showToast('error', error.message);
            }
        },

        editRequest() {
            window.location.href = `/lessee/rental-requests/${this.requestId}/edit`;
        },

        async onProposalAccepted(proposalId) {
            try {
                const url = `/api/lessee/rental-requests/${this.requestId}/proposals/${proposalId}/accept`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message);
                    await this.loadRequest();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                this.showToast('error', error.message);
            }
        },

        async onProposalRejected(proposalId) {
            try {
                const url = `/api/lessee/rental-requests/${this.requestId}/proposals/${proposalId}/reject`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('info', data.message);
                    await this.loadRequest();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                this.showToast('error', error.message);
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
        },

        setupAutoRefresh() {
            this.autoRefreshInterval = setInterval(() => {
                if (document.visibilityState === 'visible') {
                    this.loadRequest();
                }
            }, 120000);
        }
    },
    async mounted() {
        await this.loadRequest();
        this.setupAutoRefresh();

        // ⚠️ ПРОВЕРЯЕМ ДОСТУПНОСТЬ PDF ЭКСПОРТА ПРИ ЗАГРУЗКЕ
        const isAvailable = await this.checkPDFEndpoint();
        console.log('📊 PDF экспорт доступен:', isAvailable);
    },
    beforeUnmount() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }

        // ⚠️ ОЧИЩАЕМ РЕСУРСЫ ЭКСПОРТА ПРИ УНИЧТОЖЕНИИ КОМПОНЕНТА
        this.cleanupExport();
    }
}
</script>

<style scoped>
.status-breadcrumb {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 2rem;
    position: relative;
}

.step:not(:last-child):after {
    content: '';
    position: absolute;
    top: 20px;
    right: -1rem;
    width: 2rem;
    height: 2px;
    background-color: #dee2e6;
}

.step.completed:not(:last-child):after {
    background-color: #198754;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.step.active .step-number {
    background-color: #0d6efd;
    color: white;
}

.step.completed .step-number {
    background-color: #198754;
    color: white;
}

.step-label {
    font-size: 0.9rem;
    font-weight: 500;
}

.info-item {
    border-left: 3px solid #0d6efd;
    padding-left: 1rem;
}

.rental-request-show {
    min-height: 80vh;
}

.request-stats-card .stats-grid {
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

.category-nav .nav-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.categories-list {
    background: #f8f9fa;
}

.positions-list {
    background: #f8f9fa;
    min-height: 200px;
}

/* ⚠️ СТИЛИ ДЛЯ КОМПОНЕНТА ЭКСПОРТА PDF */
.export-progress-container {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .request-stats-card .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .category-nav .nav-buttons {
        justify-content: center;
    }

    .category-nav .btn {
        flex: 1;
        min-width: 120px;
        margin-bottom: 0.5rem;
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

    .export-progress-container .alert {
        padding: 0.75rem;
    }
}
</style>
