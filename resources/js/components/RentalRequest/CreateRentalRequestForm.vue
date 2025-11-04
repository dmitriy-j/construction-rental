<template>
    <div class="create-rental-request">
        <div v-if="loading && editMode" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загрузка данных заявки...</p>
        </div>

        <!-- ⚠️ ДОБАВЛЕН БЛОК ДЛЯ ОТОБРАЖЕНИЯ ОШИБОК -->
        <div v-if="error" class="alert alert-danger">
            <strong>Ошибка:</strong> {{ error }}
        </div>

        <div v-else>

        <form @submit.prevent="submitForm">
            <!-- Основная информация -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Название заявки *</label>
                            <input type="text" class="form-control" v-model="formData.title" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Описание *</label>
                            <textarea class="form-control" v-model="formData.description" rows="4" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Дата начала *</label>
                            <input type="date" class="form-control" v-model="formData.rental_period_start"
                                   :min="minDate" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Дата окончания *</label>
                            <input type="date" class="form-control" v-model="formData.rental_period_end"
                                   :min="formData.rental_period_start" required>
                        </div>

                        <div class="col-md-6">
                            <location-selector
                                :existing-locations="locations"
                                v-model="formData.location_id"
                                @location-created="onLocationCreated"
                                @location-selected="onLocationSelected">
                            </location-selector>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Базовая стоимость часа (₽) *</label>
                            <!-- ⚠️ ИСПРАВЛЕНИЕ: Добавлен модификатор .number и обработчик -->
                            <input type="number"
                                   class="form-control"
                                   v-model.number="formData.hourly_rate"
                                   min="0"
                                   step="50"
                                   @change="onHourlyRateChange($event.target.value)"
                                   required>
                            <small class="text-muted">Будет использована для позиций без индивидуальной стоимости</small>
                        </div>

                        <!-- 🔥 ДОБАВЛЕН ЧЕКБОКС ДОСТАВКИ -->
                         <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       v-model="formData.delivery_required"
                                       id="delivery_required"
                                       true-value="1"
                                       false-value="0">
                                <label class="form-check-label" for="delivery_required">
                                    <i class="fas fa-truck me-2"></i>Требуется доставка техники к объекту
                                </label>
                                <small class="form-text text-muted d-block">
                                    Отметьте, если вам необходима доставка оборудования к месту проведения работ.
                                    Это повлияет на расчет стоимости аренды.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Позиции заявки -->
            <RequestItems
                :categories="categories"
                :general-hourly-rate="generalHourlyRate"
                :general-conditions="formData.rental_conditions"
                :rental-period="rentalPeriod"
                @items-updated="onItemsUpdated"
                @total-budget-updated="onTotalBudgetUpdated"
            />

            <!-- Общие условия аренды -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Общие условия аренды</h5>
                    <small class="text-muted">Применяются ко всем позициям, если не указаны индивидуальные условия</small>
                </div>
                <div class="card-body">
                    <RentalConditions
                        :initial-conditions="formData.rental_conditions"
                        @conditions-updated="onConditionsUpdated"
                    />
                </div>
            </div>

            <!-- Итоговый бюджет -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calculator me-2"></i>Итоговый бюджет заявки
                    </h5>
                </div>
                <div class="card-body text-center">
                    <!-- ⚠️ ИСПРАВЛЕНИЕ: Используем вычисляемое свойство для форматирования -->
                    <div class="display-4 text-success mb-2">{{ formattedBudget }}</div>
                    <p class="text-muted">
                        Общая стоимость для {{ totalQuantity }} единиц техники
                        на период {{ rentalDays }} дней
                        <span v-if="formData.delivery_required" class="badge bg-info ms-2">
                            <i class="fas fa-truck me-1"></i>С доставкой
                        </span>
                    </p>
                </div>
            </div>

            <!-- Кнопки отправки -->
            <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                        {{ editMode ? 'Обновить заявку' : 'Создать заявку' }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" @click="$emit('cancelled')">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import RequestItems from './RequestItems.vue';
import RentalConditions from './RentalConditions.vue';
import BudgetCalculator from './BudgetCalculator.vue';
import LocationSelector from './LocationSelector.vue';

export default {
    name: 'CreateRentalRequestForm',
    components: {
        RequestItems,
        RentalConditions,
        BudgetCalculator,
        LocationSelector
    },
    props: {
        categories: {
            type: Array,
            required: true,
            default: () => []
        },
        locations: {
            type: Array,
            required: true,
            default: () => []
        },
        storeUrl: {
            type: String,
            required: true,
            default: ''
        },
        editMode: {
            type: Boolean,
            default: false
        },
        initialData: {
            type: Object,
            default: null
        },
        requestId: {
            type: [String, Number],
            default: null
        },
        csrfToken: {
            type: String,
            required: true,
            default: ''
        }
    },
    data() {
        const defaultFormData = {
            title: '',
            description: '',
            hourly_rate: 0,
            rental_period_start: '',
            rental_period_end: '',
            location_id: '',
            rental_conditions: this.getDefaultConditions(),
            items: [],
            delivery_required: false // 🔥 ЯВНО УКАЗЫВАЕМ false по умолчанию
        };

        return {
            formData: this.editMode && this.initialData
                ? { ...defaultFormData, ...this.initialData }
                : { ...defaultFormData },
            activeField: '',
            loading: false,
            totalBudget: 0,
            totalQuantity: 0,
            minDate: new Date().toISOString().split('T')[0],
            submitting: false,
            error: null,
            generalHourlyRate: 0
        }
    },
    computed: {
        rentalPeriod() {
            return {
                start: this.formData.rental_period_start,
                end: this.formData.rental_period_end
            };
        },
        rentalDays() {
            if (!this.formData.rental_period_start || !this.formData.rental_period_end) return 0;
            const start = new Date(this.formData.rental_period_start);
            const end = new Date(this.formData.rental_period_end);
            return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        },
        isFormValid() {
            return this.formData.title &&
                   this.formData.description &&
                   this.formData.hourly_rate > 0 &&
                   this.formData.rental_period_start &&
                   this.formData.rental_period_end &&
                   this.formData.location_id &&
                   this.formData.items.length > 0 &&
                   this.formData.items.every(item => item.category_id && item.quantity > 0);
        },
        formattedBudget() {
            if (typeof this.totalBudget !== 'number' || isNaN(this.totalBudget)) {
                return '0 ₽';
            }
            return this.formatCurrency(this.totalBudget);
        }
    },
    watch: {
        'formData.hourly_rate': {
            handler(newRate) {
                console.log('🔄 hourly_rate изменен:', newRate, typeof newRate);
                this.generalHourlyRate = this.ensureNumber(newRate);
            },
            immediate: true
        }
    },
    methods: {
        onHourlyRateChange(value) {
            console.log('🔧 Обработка изменения hourly rate:', value);
            const numValue = value === '' ? 0 : Number(value);
            this.formData.hourly_rate = isNaN(numValue) ? 0 : numValue;
            this.generalHourlyRate = this.formData.hourly_rate;
        },

        ensureNumber(value) {
            if (value === null || value === undefined || value === '') {
                return 0;
            }
            const num = Number(value);
            return isNaN(num) ? 0 : num;
        },

        getDefaultConditions() {
            return {
                payment_type: 'hourly',
                hours_per_shift: 8,
                shifts_per_day: 1,
                transportation_organized_by: 'lessor',
                gsm_payment: 'included',
                operator_included: false,
                accommodation_payment: false,
                extension_possibility: true
            };
        },

        getDefaultFormData() {
            return {
                title: '',
                description: '',
                hourly_rate: 0,
                rental_period_start: '',
                rental_period_end: '',
                location_id: '',
                rental_conditions: this.getDefaultConditions(),
                items: [{
                    category_id: null,
                    quantity: 1,
                    hourly_rate: null,
                    use_individual_conditions: false,
                    individual_conditions: {},
                    specifications: {}
                }],
                delivery_required: false
            };
        },

        deepProcessFormData(data) {
            const processValue = (value) => {
                if (value === '' || value === null || value === undefined) {
                    return null;
                }

                if (typeof value === 'number') {
                    return value;
                }

                if (typeof value === 'string') {
                    const num = Number(value);
                    return isNaN(num) ? value : num;
                }

                if (Array.isArray(value)) {
                    return value.map(item => this.deepProcessFormData(item));
                }

                if (typeof value === 'object') {
                    const result = {};
                    Object.keys(value).forEach(key => {
                        if (key === 'specifications' || key.startsWith('custom_')) {
                            result[key] = this.processSpecifications(value[key]);
                        } else {
                            result[key] = this.deepProcessFormData(value[key]);
                        }
                    });
                    return result;
                }

                return value;
            };

            return processValue(data);
        },

        processSpecifications(specs) {
            if (!specs || typeof specs !== 'object') {
                return {};
            }

            const processed = {};

            if (specs.values && typeof specs.values === 'object') {
                Object.keys(specs.values).forEach(key => {
                    const value = specs.values[key];
                    processed[key] = this.convertToNumberOrNull(value);
                });
            } else {
                Object.keys(specs).forEach(key => {
                    const value = specs[key];
                    processed[key] = this.convertToNumberOrNull(value);
                });
            }

            return processed;
        },

        convertToNumberOrNull(value) {
            if (value === '' || value === null || value === undefined) {
                return null;
            }

            const num = Number(value);
            return isNaN(num) ? null : num;
        },

        onItemsUpdated(items) {
            this.formData.items = items;
            this.totalQuantity = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
            this.calculateTotalBudget();
        },

        onTotalBudgetUpdated(budget) {
            this.totalBudget = budget;
        },

        onConditionsUpdated(conditions) {
            this.formData.rental_conditions = conditions;
            this.calculateTotalBudget();
        },

        calculateTotalBudget() {
            if (this.formData.items.length === 0) {
                this.totalBudget = 0;
                return;
            }

            let total = 0;
            const days = this.rentalDays;
            const hourlyRate = this.ensureNumber(this.formData.hourly_rate);

            this.formData.items.forEach(item => {
                const itemHourlyRate = item.hourly_rate ? this.ensureNumber(item.hourly_rate) : hourlyRate;
                total += itemHourlyRate * 8 * 1 * days * item.quantity;
            });

            this.totalBudget = total;
        },

        setActiveField(fieldName) {
            this.activeField = fieldName;
        },

        clearActiveField() {
            this.activeField = '';
        },

        onLocationCreated(newLocation) {
            this.locations.push(newLocation);
        },

        onLocationSelected(location) {
            console.log('Selected location:', location);

            if (location && location.id) {
                console.log('Selected location id:', location.id);
                this.formData.location_id = location.id;
            } else {
                console.log('Location is null, resetting location_id');
                this.formData.location_id = null;
            }
        },

        async submitForm() {
            try {
                this.error = null;

                if (this.editMode) {
                    await this.updateRequest();
                } else {
                    await this.createRequest();
                }
            } catch (error) {
                console.error('Ошибка при отправке формы:', error);
                this.error = error.message || 'Произошла ошибка при отправке формы';

                if (error.response?.data?.errors) {
                    console.error('Детали ошибки:', error.response.data.errors);
                }
            }
        },

        async createRequest() {
            this.$emit('loading-start');
            this.submitting = true;

            if (!this.isFormValid) {
                this.error = 'Пожалуйста, заполните все обязательные поля и добавьте хотя бы одну позицию';
                this.$emit('loading-end');
                this.submitting = false;
                return;
            }

            try {
                const preparedData = this.prepareFormData();

                // 🔥 ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА ПЕРЕД ОТПРАВКОЙ
                console.log('🚚 Данные доставки при отправке:', {
                    delivery_required: preparedData.delivery_required,
                    type: typeof preparedData.delivery_required,
                    value: preparedData.delivery_required
                });

                console.log('📤 Final data for create request:', {
                    delivery_required: preparedData.delivery_required,
                    full_data: preparedData
                });

                const response = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(preparedData)
                });

                const data = await response.json();

                if (data.success) {
                    // 🔥 ПРОВЕРЯЕМ ОТВЕТ СЕРВЕРА
                    console.log('✅ Заявка создана успешно:', {
                        request_id: data.request_id,
                        delivery_required_in_response: data.data?.delivery_required
                    });

                    this.$emit('saved', data.data);
                    window.location.href = data.redirect_url;
                } else {
                    throw new Error(data.message || 'Ошибка при создании заявки');
                }
            } catch (error) {
                console.error('Error:', error);
                this.error = error.message || 'Произошла ошибка при создании заявки';
                throw error;
            } finally {
                this.submitting = false;
                this.$emit('loading-end');
            }
        },

        async updateRequest() {
            this.submitting = true;
            try {
                const response = await fetch(`/api/lessee/rental-requests/${this.requestId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.prepareFormData())
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.$emit('saved', data.data);
                } else {
                    throw new Error(data.message || 'Ошибка при обновлении заявки');
                }
            } catch (error) {
                console.error('Update error:', error);
                this.error = error.message || 'Произошла ошибка при обновлении заявки';
                throw error;
            } finally {
                this.submitting = false;
            }
        },

        prepareFormData() {
            let formData = {
                title: this.formData.title,
                description: this.formData.description,
                hourly_rate: this.ensureNumber(this.formData.hourly_rate),
                rental_period_start: this.formData.rental_period_start,
                rental_period_end: this.formData.rental_period_end,
                location_id: this.formData.location_id,
                rental_conditions: this.formData.rental_conditions,
                // 🔥 ГАРАНТИРУЕМ ПРАВИЛЬНЫЙ ФОРМАТ ДЛЯ delivery_required
                delivery_required: Boolean(this.formData.delivery_required),
                items: this.formData.items.map(item => {
                    const preparedItem = {
                        category_id: item.category_id,
                        quantity: parseInt(item.quantity) || 1,
                        hourly_rate: item.hourly_rate ? this.ensureNumber(item.hourly_rate) : null,
                        use_individual_conditions: Boolean(item.use_individual_conditions),
                        individual_conditions: item.use_individual_conditions ? item.individual_conditions : {},
                    };

                    if (item.specifications) {
                        const { standard = {}, custom = {} } = this.prepareSpecifications(item.specifications);

                        preparedItem.standard_specifications = standard;
                        preparedItem.custom_specifications = custom;

                        preparedItem.specifications = { ...standard, ...this.extractCustomValues(custom) };

                        const customMetadata = {};
                        Object.keys(custom).forEach(key => {
                            const spec = custom[key];
                            customMetadata[key] = {
                                name: spec.label || key,
                                dataType: spec.dataType || 'string',
                                unit: spec.unit || ''
                            };
                        });
                        preparedItem.custom_specs_metadata = customMetadata;
                    } else {
                        preparedItem.standard_specifications = {};
                        preparedItem.custom_specifications = {};
                        preparedItem.specifications = {};
                        preparedItem.custom_specs_metadata = {};
                    }

                    console.log('📦 Prepared item specs:', {
                        standard: Object.keys(preparedItem.standard_specifications),
                        custom: Object.keys(preparedItem.custom_specifications),
                        legacy: Object.keys(preparedItem.specifications)
                    });

                    return preparedItem;
                })
            };

            if (this.editMode) {
                formData._method = 'PUT';
            }

            console.log('📤 Final prepared form data:', formData);
            return formData;
        },

        prepareSpecifications(specs) {
            if (!specs || typeof specs !== 'object') {
                return { standard: {}, custom: {} };
            }

            const standard = {};
            const custom = {};

            Object.keys(specs).forEach(key => {
                const value = specs[key];

                if (this.isStandardSpecification(key)) {
                    standard[key] = this.normalizeSpecValue(value);
                } else {
                    if (typeof value === 'object' && value !== null) {
                        custom[key] = {
                            label: value.label || key,
                            value: this.normalizeSpecValue(value.value),
                            unit: value.unit || '',
                            dataType: value.dataType || 'string'
                        };
                    } else {
                        custom[key] = {
                            label: this.formatLabel(key),
                            value: this.normalizeSpecValue(value),
                            unit: '',
                            dataType: typeof value === 'number' ? 'number' : 'string'
                        };
                    }
                }
            });

            return { standard, custom };
        },

        isStandardSpecification(key) {
            const standardKeys = [
                'bucket_volume', 'max_digging_depth', 'power', 'weight',
                'engine_power', 'lifting_capacity', 'boom_length'
            ];
            return standardKeys.includes(key) || !key.startsWith('custom_');
        },

        normalizeSpecValue(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            if (typeof value === 'string' && value.includes(',')) {
                const numValue = parseFloat(value.replace(',', '.'));
                return isNaN(numValue) ? value : numValue;
            }

            if (typeof value === 'string' && !isNaN(value) && value.trim() !== '') {
                return parseFloat(value);
            }

            return value;
        },

        extractCustomValues(customSpecs) {
            const values = {};
            Object.keys(customSpecs).forEach(key => {
                values[key] = customSpecs[key].value;
            });
            return values;
        },

        formatLabel(key) {
            return key.replace(/_/g, ' ')
                    .replace(/(?:^|\s)\S/g, char => char.toUpperCase());
        },

        cancel() {
            if (confirm('Отменить создание заявки?')) {
                if (this.editMode) {
                    this.$emit('cancelled');
                } else {
                    window.history.back();
                }
            }
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        },

        initializeFormWithData() {
            if (this.editMode && this.initialData) {
                console.log('Initializing form with data:', this.initialData);
            }
        }
    },

    mounted() {
        console.log('CreateRentalRequestForm mounted', {
            editMode: this.editMode,
            requestId: this.requestId,
            categories: this.categories?.length,
            locations: this.locations?.length,
            formData: this.formData,
            generalHourlyRate: this.generalHourlyRate,
            hourly_rate_type: typeof this.formData.hourly_rate,
            delivery_required: this.formData.delivery_required,
            delivery_required_type: typeof this.formData.delivery_required
        });

        this.generalHourlyRate = this.ensureNumber(this.formData.hourly_rate);

        if (this.editMode) {
            this.initializeFormWithData();
        }
    }
}
</script>
