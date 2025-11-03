<template>
    <div>
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загрузка данных заявки...</p>
        </div>

        <div v-else-if="error" class="alert alert-danger">
            {{ error }}
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
                                <label class="form-label">Локация *</label>
                                <select class="form-select" v-model="formData.location_id" required>
                                    <option value="">Выберите локацию</option>
                                    <option v-for="location in locations" :value="location.id" :key="location.id">
                                        {{ location.name }} - {{ location.address }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Базовая стоимость часа (₽) *</label>
                                <input type="number" class="form-control" v-model.number="formData.hourly_rate"
                                       min="0" step="50" required>
                                <small class="text-muted">Будет использована для позиций без индивидуальной стоимости</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Позиции заявки -->
                <RequestItems
                    :categories="categories"
                    :general-hourly-rate="formData.hourly_rate"
                    :general-conditions="formData.rental_conditions"
                    :rental-period="rentalPeriod"
                    :initial-items="formData.items"
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
                        <div class="display-4 text-success mb-2">{{ formatCurrency(totalBudget) }}</div>
                        <p class="text-muted">
                            Общая стоимость для {{ totalQuantity }} единиц техники
                            на период {{ rentalDays }} дней
                        </p>
                    </div>
                </div>

                <!-- Кнопки отправки -->
                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                        {{ submitting ? 'Сохранение...' : 'Обновить заявку' }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" @click="cancel">
                        Отмена
                    </button>

                    <button type="button" class="btn btn-outline-info ms-auto" @click="showDebug = !showDebug">
                        {{ showDebug ? 'Скрыть отладку' : 'Показать отладку' }}
                    </button>
                </div>
            </form>

            <!-- Отладочная информация -->
            <div v-if="showDebug" class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Отладочная информация</h6>
                </div>
                <div class="card-body">
                    <pre>{{ debugInfo }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import RequestItems from './RequestItems.vue';
import RentalConditions from './RentalConditions.vue';

export default {
    name: 'EditRentalRequestForm',
    components: {
        RequestItems,
        RentalConditions
    },
    props: {
        requestId: { type: [String, Number], required: true },
        apiUrl: { type: String, required: true },
        updateUrl: { type: String, required: true },
        csrfToken: { type: String, required: true },
        categories: { type: Array, default: () => [] },
        locations: { type: Array, default: () => [] }
    },
    data() {
        return {
            loading: true,
            error: null,
            formData: this.getDefaultFormData(),
            totalBudget: 0,
            totalQuantity: 0,
            minDate: new Date().toISOString().split('T')[0],
            submitting: false,
            showDebug: false,
            hasUnsavedChanges: false,
            preventUpdateLoop: false,
            isProcessingItemsUpdate: false
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
        debugInfo() {
            return {
                requestId: this.requestId,
                apiUrl: this.apiUrl,
                updateUrl: this.updateUrl,
                formData: this.formData,
                loading: this.loading,
                error: this.error,
                hasUnsavedChanges: this.hasUnsavedChanges,
                totalBudget: this.totalBudget,
                totalQuantity: this.totalQuantity,
                isProcessingItemsUpdate: this.isProcessingItemsUpdate,
                preventUpdateLoop: this.preventUpdateLoop
            };
        }
    },
    methods: {
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

        // ✅ ДОБАВЛЕН НОВЫЙ МЕТОД: Гарантированная очистка unit перед отправкой
        ensureUnitIsString(specs) {
            if (!specs || typeof specs !== 'object') return specs;

            const cleanedSpecs = {};

            Object.keys(specs).forEach(key => {
                const spec = specs[key];
                if (spec && typeof spec === 'object') {
                    cleanedSpecs[key] = {
                        ...spec,
                        unit: spec.unit !== null && spec.unit !== undefined ? String(spec.unit) : ''
                    };

                    // ✅ ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА
                    if (cleanedSpecs[key].unit === null) {
                        console.error(`❌ ensureUnitIsString: unit всё равно null для ${key}`);
                        cleanedSpecs[key].unit = '';
                    }
                }
            });

            console.log('🔄 ensureUnitIsString выполнено:', {
                входные: Object.keys(specs).length,
                выходные: Object.keys(cleanedSpecs).length,
                units: Object.values(cleanedSpecs).map(s => ({ unit: s.unit, type: typeof s.unit }))
            });

            return cleanedSpecs;
        },

        // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Подготовка кастомных спецификаций с гарантией типов
        prepareCustomSpecificationsForBackend(customSpecs) {
            const prepared = {};

            Object.keys(customSpecs).forEach(key => {
                const spec = customSpecs[key];

                // ✅ ИЗМЕНЕНИЕ: Принимаем спецификации даже с пустым value, но с заполненным label
                if (spec && spec.label) {
                    // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Гарантируем что unit всегда строка
                    let unitValue = '';
                    if (spec.unit !== null && spec.unit !== undefined) {
                        unitValue = String(spec.unit);
                    }

                    // ✅ ДЕТАЛЬНАЯ ОТЛАДКА
                    console.log('🔍 EditRentalRequestForm: подготовка кастомной спецификации для бэкенда:', {
                        key,
                        label: spec.label,
                        value: spec.value,
                        originalUnit: spec.unit,
                        normalizedUnit: unitValue,
                        unitType: typeof unitValue,
                        isNull: unitValue === null
                    });

                    const preparedSpec = {
                        label: String(spec.label || ''),
                        value: this.normalizeCustomSpecValue(spec.value, spec.dataType),
                        unit: unitValue, // ✅ Всегда строка, никогда null
                        dataType: String(spec.dataType || 'string')
                    };

                    // ✅ ФИНАЛЬНАЯ ПРОВЕРКА
                    if (preparedSpec.unit === null) {
                        console.error('❌ EditRentalRequestForm: КРИТИЧЕСКАЯ ОШИБКА - unit всё равно null после всех преобразований!');
                        preparedSpec.unit = '';
                    }

                    // ✅ ДОПОЛНИТЕЛЬНАЯ ВАЛИДАЦИЯ ПЕРЕД ДОБАВЛЕНИЕМ
                    console.log('✅ EditRentalRequestForm: финальная проверка спецификации:', {
                        key,
                        unit: preparedSpec.unit,
                        unitType: typeof preparedSpec.unit,
                        isNull: preparedSpec.unit === null
                    });

                    prepared[key] = preparedSpec;
                }
            });

            console.log('🔧 Подготовлены кастомные спецификации для бэкенда:', {
                количество: Object.keys(prepared).length,
                данные: prepared,
                units_check: Object.values(prepared).map(s => ({
                    unit: s.unit,
                    type: typeof s.unit,
                    isNull: s.unit === null
                }))
            });

            return prepared;
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
                items: [],
                delivery_required: false
            };
        },

        async loadRequestData() {
            this.loading = true;
            this.error = null;

            try {
                await new Promise(resolve => setTimeout(resolve, 1000));

                console.log('🔄 EditRentalRequestForm: загрузка данных заявки:', this.apiUrl);

                const response = await fetch(this.apiUrl, {
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
                    this.initializeFormData(data.data);
                } else {
                    throw new Error(data.message || 'Ошибка загрузки данных');
                }
            } catch (error) {
                console.error('❌ EditRentalRequestForm: ошибка загрузки:', error);
                this.error = error.message;

                if (error.message.includes('429')) {
                    this.error = 'Слишком много запросов. Подождите несколько секунд и попробуйте снова.';
                }
            } finally {
                this.loading = false;
            }
        },

        initializeFormData(requestData) {
            const formatDateForInput = (dateString) => {
                if (!dateString) return '';
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            };

            this.formData = {
                title: requestData.title || '',
                description: requestData.description || '',
                hourly_rate: parseFloat(requestData.hourly_rate) || 0,
                rental_period_start: formatDateForInput(requestData.rental_period_start),
                rental_period_end: formatDateForInput(requestData.rental_period_end),
                location_id: requestData.location_id || '',
                rental_conditions: requestData.rental_conditions || this.getDefaultConditions(),
                items: requestData.items ? requestData.items.map(item => ({
                    category_id: item.category_id,
                    quantity: item.quantity,
                    hourly_rate: item.hourly_rate,
                    use_individual_conditions: item.use_individual_conditions || false,
                    individual_conditions: item.individual_conditions || {},
                    specifications: {
                        standard_specifications: item.standard_specifications || item.specifications?.standard_specifications || {},
                        custom_specifications: item.custom_specifications || item.specifications?.custom_specifications || {}
                    }
                })) : [],
                delivery_required: Boolean(requestData.delivery_required)
            };

            this.totalQuantity = this.formData.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
            this.calculateTotalBudget();

            console.log('📝 EditRentalRequestForm: форма инициализирована с данными:', {
                items_count: this.formData.items.length,
                items_with_custom_specs: this.formData.items.filter(item =>
                    item.specifications?.custom_specifications &&
                    Object.keys(item.specifications.custom_specifications).length > 0
                ).length
            });
        },

        onItemsUpdated(items) {
            if (this.preventUpdateLoop || this.isProcessingItemsUpdate) {
                console.log('🛑 EditRentalRequestForm: предотвращен циклический вызов onItemsUpdated');
                return;
            }

            const currentItemsStr = JSON.stringify(this.formData.items);
            const newItemsStr = JSON.stringify(items);

            if (currentItemsStr !== newItemsStr) {
                console.log('✅ EditRentalRequestForm: приняты новые items от RequestItems', {
                    количество: items.length,
                    позиции_с_кастомными_спецификациями: items.filter(item =>
                        item.specifications?.custom_specifications &&
                        Object.keys(item.specifications.custom_specifications).length > 0
                    ).length
                });

                this.isProcessingItemsUpdate = true;
                this.formData.items = items;
                this.totalQuantity = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
                this.calculateTotalBudget();
                this.hasUnsavedChanges = true;

                setTimeout(() => {
                    this.isProcessingItemsUpdate = false;
                }, 100);
            } else {
                console.log('🛑 EditRentalRequestForm: данные items не изменились, пропускаем обновление');
            }
        },

        onTotalBudgetUpdated(budget) {
            this.totalBudget = budget;
        },

        onConditionsUpdated(conditions) {
            this.formData.rental_conditions = conditions;
            this.hasUnsavedChanges = true;
            this.calculateTotalBudget();
        },

        calculateTotalBudget() {
            if (this.formData.items.length === 0) {
                this.totalBudget = 0;
                return;
            }

            let total = 0;
            const days = this.rentalDays;
            const hourlyRate = this.formData.hourly_rate;

            this.formData.items.forEach(item => {
                const itemHourlyRate = item.hourly_rate || hourlyRate;
                const conditions = item.use_individual_conditions && item.individual_conditions
                    ? item.individual_conditions
                    : this.formData.rental_conditions;

                const hoursPerShift = conditions.hours_per_shift || 8;
                const shiftsPerDay = conditions.shifts_per_day || 1;

                total += itemHourlyRate * hoursPerShift * shiftsPerDay * days * item.quantity;
            });

            this.totalBudget = total;
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        },

        async submitForm() {
            if (!this.isFormValid) {
                alert('Пожалуйста, заполните все обязательные поля и добавьте хотя бы одну позицию');
                return;
            }

            this.submitting = true;

            try {
                const response = await fetch(this.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.prepareFormData())
                });

                const data = await response.json();

                if (data.success) {
                    this.hasUnsavedChanges = false;
                    alert('Заявка успешно обновлена!');
                    window.location.href = `/lessee/rental-requests/${this.requestId}`;
                } else {
                    throw new Error(data.message || 'Ошибка при обновлении заявки');
                }
            } catch (error) {
                console.error('❌ EditRentalRequestForm: ошибка сохранения:', error);
                alert('Ошибка: ' + error.message);
            } finally {
                this.submitting = false;
            }
        },

        prepareFormData() {
            const formData = {
                title: this.formData.title,
                description: this.formData.description,
                hourly_rate: parseFloat(this.formData.hourly_rate) || 0,
                rental_period_start: this.formData.rental_period_start,
                rental_period_end: this.formData.rental_period_end,
                location_id: this.formData.location_id,
                rental_conditions: this.formData.rental_conditions,
                items: this.formData.items.map(item => {
                    const preparedItem = {
                        category_id: item.category_id,
                        quantity: parseInt(item.quantity) || 1,
                        hourly_rate: item.hourly_rate ? parseFloat(item.hourly_rate) : null,
                        use_individual_conditions: Boolean(item.use_individual_conditions),
                        individual_conditions: item.use_individual_conditions ? item.individual_conditions : {},
                    };

                    if (item.specifications) {
                        preparedItem.standard_specifications = this.prepareStandardSpecifications(
                            item.specifications.standard_specifications || {}
                        );

                        // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Двойная защита - очистка unit
                        const rawCustomSpecs = item.specifications.custom_specifications || {};
                        let processedCustomSpecs = this.prepareCustomSpecificationsForBackend(rawCustomSpecs);

                        // ✅ ДОПОЛНИТЕЛЬНАЯ ЗАЩИТА: Очищаем unit от null значений
                        preparedItem.custom_specifications = this.ensureUnitIsString(processedCustomSpecs);

                        // ✅ ФИНАЛЬНАЯ ПРОВЕРКА ПЕРЕД ОТПРАВКОЙ
                        Object.keys(preparedItem.custom_specifications).forEach(key => {
                            const spec = preparedItem.custom_specifications[key];
                            if (spec.unit === null) {
                                console.error(`❌ КРИТИЧЕСКАЯ ОШИБКА В prepareFormData: unit null для ${key}`);
                                preparedItem.custom_specifications[key].unit = '';
                            }
                        });

                        preparedItem.specifications = {
                            ...preparedItem.standard_specifications,
                            ...this.extractCustomValues(preparedItem.custom_specifications)
                        };

                        console.log('📦 Prepared item specs for backend:', {
                            стандартные: Object.keys(preparedItem.standard_specifications).length,
                            кастомные: Object.keys(preparedItem.custom_specifications).length,
                            кастомные_данные: preparedItem.custom_specifications,
                            units_final_check: Object.values(preparedItem.custom_specifications).map(s => ({
                                unit: s.unit,
                                type: typeof s.unit,
                                isNull: s.unit === null
                            }))
                        });
                    } else {
                        preparedItem.standard_specifications = {};
                        preparedItem.custom_specifications = {};
                        preparedItem.specifications = {};
                    }

                    return preparedItem;
                }),
                delivery_required: Boolean(this.formData.delivery_required)
            };

            formData._method = 'PUT';

            // ✅ ФИНАЛЬНАЯ ПРОВЕРКА ВСЕХ ДАННЫХ ПЕРЕД ОТПРАВКОЙ
            console.log('🔍 ФИНАЛЬНАЯ ПРОВЕРКА ДАННЫХ ПЕРЕД ОТПРАВКОЙ:');
            let totalNullUnits = 0;
            formData.items.forEach((item, index) => {
                console.log(`Item ${index} custom specs:`, item.custom_specifications);
                Object.keys(item.custom_specifications || {}).forEach(key => {
                    const spec = item.custom_specifications[key];
                    console.log(`  ${key}:`, {
                        label: spec.label,
                        value: spec.value,
                        unit: spec.unit,
                        unitType: typeof spec.unit,
                        isNull: spec.unit === null
                    });

                    if (spec.unit === null) {
                        totalNullUnits++;
                        console.error(`❌ ОБНАРУЖЕН NULL UNIT: item ${index}, key ${key}`);
                    }
                });
            });

            if (totalNullUnits > 0) {
                console.error(`🚨 КРИТИЧЕСКАЯ ОШИБКА: Обнаружено ${totalNullUnits} полей unit со значением null!`);
            }

            console.log('📤 EditRentalRequestForm: Final prepared form data for update:', {
                items_count: formData.items.length,
                items_with_custom_specs: formData.items.filter(item =>
                    item.custom_specifications && Object.keys(item.custom_specifications).length > 0
                ).length,
                total_custom_specs: formData.items.reduce((sum, item) =>
                    sum + Object.keys(item.custom_specifications || {}).length, 0),
                total_null_units: totalNullUnits
            });

            return formData;
        },

        prepareStandardSpecifications(standardSpecs) {
            const prepared = {};

            Object.keys(standardSpecs).forEach(key => {
                const value = standardSpecs[key];

                if (value !== null && value !== undefined && value !== '') {
                    if (typeof value === 'string' && !isNaN(value) && value.trim() !== '') {
                        prepared[key] = Number(value);
                    } else {
                        prepared[key] = value;
                    }
                }
            });

            return prepared;
        },

        normalizeCustomSpecValue(value, dataType) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            if (dataType === 'number') {
                const numValue = Number(value);
                return isNaN(numValue) ? null : numValue;
            } else {
                return String(value);
            }
        },

        extractCustomValues(customSpecs) {
            const values = {};
            Object.keys(customSpecs).forEach(key => {
                const spec = customSpecs[key];
                if (spec && spec.value !== null && spec.value !== undefined) {
                    const labelKey = spec.label || key;
                    values[labelKey] = spec.value;
                }
            });
            return values;
        },

        cancel() {
            if (this.hasUnsavedChanges) {
                if (!confirm('У вас есть несохраненные изменения. Вы уверены, что хотите отменить?')) {
                    return;
                }
            }
            window.history.back();
        }
    },
    async mounted() {
        console.log('✅ EditRentalRequestForm: компонент редактирования смонтирован');
        console.log('📊 Параметры:', {
            requestId: this.requestId,
            apiUrl: this.apiUrl,
            updateUrl: this.updateUrl,
            categoriesCount: this.categories.length,
            locationsCount: this.locations.length
        });

        await this.loadRequestData();
    },

    beforeUnmount() {
        if (this.hasUnsavedChanges) {
            const confirmationMessage = 'У вас есть несохраненные изменения. Вы уверены, что хотите уйти?';
            if (!confirm(confirmationMessage)) {
                return false;
            }
        }
    }
}
</script>

<style scoped>
.form-actions {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .form-actions .btn {
        margin-bottom: 0.5rem;
    }
}

/* Гарантия что карточки не создают лишние отступы */
.card {
    margin-bottom: 1.5rem;
}

.card:last-child {
    margin-bottom: 0;
}
</style>
