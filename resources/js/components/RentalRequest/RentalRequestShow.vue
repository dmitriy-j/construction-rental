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
                    <QuickActions :request-id="request.id" />
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
            }
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

                    // 🔥 РАСШИРЕННАЯ ДИАГНОСТИКА ДАННЫХ ОТ БЭКЕНДА
                    console.log('🔍 ДЕТАЛЬНАЯ ДИАГНОСТИКА ДАННЫХ ОТ БЭКЕНДА:');
                    if (this.request.items && this.request.items.length > 0) {
                        this.request.items.forEach((item, index) => {
                            console.log(`📦 Позиция ${index + 1} (ID: ${item.id}):`, {
                                // Основная информация
                                title: item.title,
                                category: item.category?.name,

                                // Спецификации - что приходит с бэкенда
                                raw_specifications: item.specifications,
                                formatted_specifications: item.formatted_specifications,

                                // Детальный анализ спецификаций
                                specs_type: typeof item.specifications,
                                specs_is_array: Array.isArray(item.specifications),
                                specs_keys: item.specifications ? Object.keys(item.specifications) : [],

                                // Форматированные спецификации
                                has_formatted_specs: !!item.formatted_specifications,
                                formatted_specs_count: item.formatted_specifications ? item.formatted_specifications.length : 0,

                                // Пример первого параметра для проверки
                                first_spec_if_any: item.specifications && Object.keys(item.specifications).length > 0 ?
                                    Object.entries(item.specifications)[0] : 'нет'
                            });

                            // 🔥 ПРОВЕРКА КОНКРЕТНО ПАРАМЕТРА "weight"
                            if (item.specifications) {
                                // Проверка в разных местах где может быть weight
                                const weightInStandard = item.specifications.standard_specifications?.weight;
                                const weightInCustom = item.specifications.custom_specifications?.weight;
                                const weightDirect = item.specifications.weight;

                                if (weightInStandard || weightInCustom || weightDirect) {
                                    console.log('⚖️ НАЙДЕН ПАРАМЕТР WEIGHT:', {
                                        key: 'weight',
                                        value: weightDirect || weightInStandard || weightInCustom,
                                        in_standard_specs: weightInStandard,
                                        in_custom_specs: weightInCustom,
                                        direct_access: weightDirect,
                                        location: weightInStandard ? 'standard_specifications' :
                                                weightInCustom ? 'custom_specifications' :
                                                weightDirect ? 'direct' : 'not_found'
                                    });
                                }
                            }

                            // Проверка formatted_specifications
                            if (item.formatted_specifications) {
                                const weightSpec = item.formatted_specifications.find(spec =>
                                    spec.key === 'weight' || spec.label?.toLowerCase().includes('weight')
                                );
                                if (weightSpec) {
                                    console.log('⚖️ WEIGHT В FORMATTED_SPECIFICATIONS:', weightSpec);
                                }

                                // Проверка всех labels в formatted_specifications
                                console.log('🏷️ Все labels в formatted_specifications:',
                                    item.formatted_specifications.map(spec => ({
                                        key: spec.key,
                                        label: spec.label,
                                        value: spec.value
                                    }))
                                );
                            }
                        });
                    }

                    // 🔥 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Обеспечиваем наличие formatted_specifications с русскими названиями
                    if (this.request.items && this.request.items.length > 0) {
                        this.request.items.forEach(item => {
                            // Если бэкенд не предоставил formatted_specifications, создаем их на фронтенде
                            if (!item.formatted_specifications && item.specifications) {
                                item.formatted_specifications = this.formatSpecificationsFrontend(item.specifications);
                            }

                            // 🔥 ПРИНУДИТЕЛЬНОЕ ИСПРАВЛЕНИЕ: Если formatted_specifications есть но содержат английские названия
                            if (item.formatted_specifications) {
                                item.formatted_specifications = this.fixRussianLabels(item.formatted_specifications);
                            }

                            // Логируем для отладки
                            console.log(`📦 Позиция ${item.id} после обработки:`, {
                                has_formatted_specs: !!item.formatted_specifications,
                                formatted_specs_count: item.formatted_specifications ? item.formatted_specifications.length : 0,
                                formatted_specs_sample: item.formatted_specifications ?
                                    item.formatted_specifications.map(spec => `${spec.label}: ${spec.value}`) : []
                            });
                        });
                    }

                    // Также обрабатываем groupedByCategory
                    if (this.groupedByCategory && this.groupedByCategory.length > 0) {
                        this.groupedByCategory.forEach(category => {
                            if (category.items && category.items.length > 0) {
                                category.items.forEach(item => {
                                    if (!item.formatted_specifications && item.specifications) {
                                        item.formatted_specifications = this.formatSpecificationsFrontend(item.specifications);
                                    }
                                    if (item.formatted_specifications) {
                                        item.formatted_specifications = this.fixRussianLabels(item.formatted_specifications);
                                    }
                                });
                            }
                        });
                    }

                    console.log('✅ Заявка успешно загружена и обработана');
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

        // 🔥 НОВЫЙ МЕТОД: Принудительное исправление русских названий
        fixRussianLabels(specifications) {
            if (!Array.isArray(specifications)) return specifications;

            console.log('🔧 Исправление русских названий в спецификациях:', specifications);

            const labelMappings = {
                'weight': 'Вес',
                'Weight': 'Вес',
                'power': 'Мощность',
                'Power': 'Мощность',
                'bucket_volume': 'Объем ковша',
                'load_capacity': 'Грузоподъемность',
                'axle_configuration': 'Колесная формула',
                'body_volume': 'Объем кузова',
                'max_digging_depth': 'Макс. глубина копания',
                'engine_power': 'Мощность двигателя',
                'operating_weight': 'Эксплуатационный вес',
                'transport_length': 'Длина транспортировки',
                'transport_width': 'Ширина транспортировки',
                'transport_height': 'Высота транспортировки',
                'engine_type': 'Тип двигателя',
                'fuel_tank_capacity': 'Емкость топливного бака',
                'max_speed': 'Макс. скорость',
                'bucket_capacity': 'Емкость ковша'
            };

            const fixedSpecs = specifications.map(spec => {
                let fixedLabel = spec.label;

                // Исправляем по label
                if (spec.label && labelMappings[spec.label]) {
                    fixedLabel = labelMappings[spec.label];
                    console.log(`🔄 Исправлен label: "${spec.label}" -> "${fixedLabel}"`);
                }

                // Также проверяем ключ если label не исправлен
                if (fixedLabel === spec.label && spec.key && labelMappings[spec.key]) {
                    fixedLabel = labelMappings[spec.key];
                    console.log(`🔄 Исправлен по key: "${spec.key}" -> "${fixedLabel}"`);
                }

                // Если все еще английский, пытаемся исправить через ключ
                if (fixedLabel === spec.label && /^[a-zA-Z_]+$/.test(fixedLabel)) {
                    const possibleRussian = labelMappings[fixedLabel.toLowerCase()];
                    if (possibleRussian) {
                        fixedLabel = possibleRussian;
                        console.log(`🔄 Исправлен через lowercase: "${spec.label}" -> "${fixedLabel}"`);
                    }
                }

                return {
                    ...spec,
                    label: fixedLabel
                };
            });

            console.log('✅ Исправленные спецификации:', fixedSpecs);
            return fixedSpecs;
        },

        // 🔥 НОВЫЙ МЕТОД: Принудительное форматирование спецификаций
        forceFormatSpecifications(specifications) {
            if (!specifications) return [];

            console.log('🔧 Принудительное форматирование спецификаций:', specifications);

            const formatted = [];
            const labelMappings = {
                'weight': 'Вес',
                'power': 'Мощность',
                'bucket_volume': 'Объем ковша',
                'load_capacity': 'Грузоподъемность',
                'axle_configuration': 'Колесная формула',
                'body_volume': 'Объем кузова',
                'max_digging_depth': 'Макс. глубина копания',
                'engine_power': 'Мощность двигателя',
                'operating_weight': 'Эксплуатационный вес',
                'transport_length': 'Длина транспортировки',
                'transport_width': 'Ширина транспортировки',
                'transport_height': 'Высота транспортировки',
                'engine_type': 'Тип двигателя',
                'fuel_tank_capacity': 'Емкость топливного бака',
                'max_speed': 'Макс. скорость',
                'bucket_capacity': 'Емкость ковша'
            };

            const unitMappings = {
                'weight': 'т',
                'power': 'л.с.',
                'bucket_volume': 'м³',
                'load_capacity': 'т',
                'body_volume': 'м³',
                'max_digging_depth': 'м',
                'engine_power': 'кВт',
                'operating_weight': 'т',
                'transport_length': 'м',
                'transport_width': 'м',
                'transport_height': 'м',
                'fuel_tank_capacity': 'л',
                'max_speed': 'км/ч',
                'bucket_capacity': 'м³'
            };

            // Обработка разных форматов
            if (Array.isArray(specifications)) {
                return specifications.map(spec => ({
                    ...spec,
                    label: labelMappings[spec.key] || spec.label || this.formatKeyToLabel(spec.key),
                    unit: unitMappings[spec.key] || spec.unit || '',
                    display_value: spec.value + (unitMappings[spec.key] ? ' ' + unitMappings[spec.key] : (spec.unit ? ' ' + spec.unit : ''))
                }));
            }

            if (typeof specifications === 'object') {
                // Обработка стандартных спецификаций
                if (specifications.standard_specifications && typeof specifications.standard_specifications === 'object') {
                    Object.entries(specifications.standard_specifications).forEach(([key, value]) => {
                        if (value !== null && value !== '' && value !== undefined) {
                            formatted.push({
                                key: key,
                                label: labelMappings[key] || this.formatKeyToLabel(key),
                                value: value,
                                unit: unitMappings[key] || '',
                                display_value: value + (unitMappings[key] ? ' ' + unitMappings[key] : '')
                            });
                        }
                    });
                }

                // Обработка кастомных спецификаций
                if (specifications.custom_specifications && typeof specifications.custom_specifications === 'object') {
                    Object.entries(specifications.custom_specifications).forEach(([key, spec]) => {
                        if (spec && spec.value !== null && spec.value !== '' && spec.value !== undefined) {
                            formatted.push({
                                key: key,
                                label: spec.label || 'Дополнительный параметр',
                                value: spec.value,
                                unit: spec.unit || '',
                                display_value: spec.value + (spec.unit ? ' ' + spec.unit : '')
                            });
                        }
                    });
                }

                // Обработка прямого объекта (старый формат)
                if (Object.keys(specifications).length > 0 && !specifications.standard_specifications && !specifications.custom_specifications) {
                    Object.entries(specifications).forEach(([key, value]) => {
                        if (key !== 'metadata' && value !== null && value !== '' && value !== undefined && typeof value !== 'object') {
                            formatted.push({
                                key: key,
                                label: labelMappings[key] || this.formatKeyToLabel(key),
                                value: value,
                                unit: unitMappings[key] || '',
                                display_value: value + (unitMappings[key] ? ' ' + unitMappings[key] : '')
                            });
                        }
                    });
                }
            }

            console.log('✅ Принудительно отформатированные спецификации:', formatted);
            return formatted;
        },

        // 🔥 НОВЫЙ МЕТОД: Форматирование спецификаций на фронтенде
        formatSpecificationsFrontend(specifications) {
            if (!specifications) return [];

            console.log('🔧 Форматирование спецификаций на фронтенде:', specifications);

            // Сначала используем принудительное форматирование
            const formatted = this.forceFormatSpecifications(specifications);

            // Затем применяем исправление русских названий
            return this.fixRussianLabels(formatted);
        },

       getSpecificationLabel(key) {
            const labels = {
                'bucket_volume': 'Объем ковша',
                'weight': 'Вес', // 🔥 ДОБАВЛЕНО
                'power': 'Мощность',
                'max_digging_depth': 'Макс. глубина копания',
                'engine_power': 'Мощность двигателя',
                'operating_weight': 'Эксплуатационный вес',
                'transport_length': 'Длина транспортировки',
                'transport_width': 'Ширина транспортировки',
                'transport_height': 'Высота транспортировки',
                'engine_type': 'Тип двигателя',
                'fuel_tank_capacity': 'Емкость топливного бака',
                'max_speed': 'Макс. скорость',
                'bucket_capacity': 'Емкость ковша',
                'body_volume': 'Объем кузова',
                'load_capacity': 'Грузоподъемность',
                'axle_configuration': 'Колесная формула',
                'weight': 'Вес' // 🔥 ДОБАВЛЕНО
            };
            return labels[key] || this.formatKeyToLabel(key);
        },

        getSpecificationUnit(key) {
            const units = {
                'bucket_volume': 'м³',
                'weight': 'т',
                'power': 'л.с.',
                'max_digging_depth': 'м',
                'engine_power': 'кВт',
                'operating_weight': 'т',
                'transport_length': 'м',
                'transport_width': 'м',
                'transport_height': 'м',
                'fuel_tank_capacity': 'л',
                'max_speed': 'км/ч',
                'bucket_capacity': 'м³',
                'body_volume': 'м³',
                'load_capacity': 'т'
            };
            return units[key] || '';
        },

        formatKeyToLabel(key) {
            return key
                .split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
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
    },
    beforeUnmount() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
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
}
</style>
