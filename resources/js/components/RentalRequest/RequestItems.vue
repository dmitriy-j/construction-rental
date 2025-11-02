<template>
    <div class="request-items">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Позиции заявки</h6>
            <button type="button" class="btn btn-sm btn-primary" @click="addItem">
                <i class="fas fa-plus me-1"></i>Добавить позицию
            </button>
        </div>

        <div class="items-list">
            <div v-for="(item, index) in items" :key="index" class="item-card card mb-3">
                 <div class="card-body">
                    <div class="row g-3">
                        <!-- Категория техники -->
                        <div class="col-md-4">
                            <label class="form-label">Категория техники *</label>
                            <select class="form-select" v-model="item.category_id"
                                    @change="onCategoryChange(item, index)" required>
                                <option value="">Выберите категорию</option>
                                <option v-for="category in categories" :value="category.id" :key="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Количество -->
                        <div class="col-md-2">
                            <label class="form-label">Количество *</label>
                            <input type="number" class="form-control" v-model.number="item.quantity"
                                   min="1" max="1000" @input="debouncedUpdateItems" required>
                        </div>

                        <!-- Стоимость часа -->
                        <div class="col-md-3">
                            <label class="form-label">
                                Стоимость часа (₽)
                                <small class="text-muted">*</small>
                            </label>
                            <input type="number" class="form-control" v-model.number="item.hourly_rate"
                                   min="0" step="50" @input="debouncedUpdateItems" required>
                            <small class="text-muted" v-if="!item.hourly_rate">
                                Будет использована общая стоимость: {{ formatCurrency(generalHourlyRate) }}
                            </small>
                        </div>

                        <!-- Стоимость позиции -->
                        <div class="col-md-2">
                            <label class="form-label">Стоимость позиции</label>
                            <div class="form-control bg-light">
                                <strong>{{ formatCurrency(calculateItemPrice(item)) }}</strong>
                            </div>
                        </div>

                        <!-- Удаление -->
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger w-100"
                                    @click="removeItem(index)"
                                    :disabled="items.length <= 1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Блок технических параметров -->
                    <div class="mt-3" v-if="item.category_id">
                        <EquipmentSpecifications
                            :category-id="item.category_id"
                            v-model="item.specifications"
                            @update:modelValue="onSpecificationsUpdate(index, $event)"
                        />
                    </div>

                    <!-- Индивидуальные условия для позиции -->
                    <div class="mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   v-model="item.use_individual_conditions"
                                   @change="toggleIndividualConditions(index, $event)">
                            <label class="form-check-label">
                                Использовать индивидуальные условия для этой позиции
                            </label>
                        </div>

                        <div v-if="item.use_individual_conditions" class="individual-conditions mt-3 p-3 bg-light rounded">
                            <h6 class="mb-3">Индивидуальные условия для позиции</h6>
                            <RentalConditions
                                :initial-conditions="item.individual_conditions"
                                @conditions-updated="(conditions) => updateItemConditions(index, conditions)">
                            </RentalConditions>
                        </div>
                    </div>
                </div>
            </div>
        </div>

         <!-- Обновленный блок итоговой информации -->
        <div class="card bg-light">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <strong>Позиций:</strong>
                        <span class="badge bg-primary ms-2">{{ items.length }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Общее количество:</strong>
                        <span class="badge bg-success ms-2">{{ totalQuantity }} ед.</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Категорий:</strong>
                        <span class="badge bg-info ms-2">{{ uniqueCategories }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Общий бюджет:</strong>
                        <span class="badge bg-warning ms-2">{{ formatCurrency(totalBudget) }}</span>
                    </div>
                </div>

                <!-- Детали расчета -->
                <div class="calculation-details mt-3 p-3 bg-white rounded" v-if="totalBudget > 0">
                    <h6 class="text-center mb-3">Детали расчета</h6>
                    <div class="calculation-items">
                        <div v-for="(item, index) in items" :key="index" class="calculation-item mb-2">
                            <small class="d-block">
                                <strong>{{ getCategoryName(item.category_id) }}:</strong>
                                {{ formatCurrency(calculateItemPrice(item)) }}
                                ({{ item.quantity }} шт. × {{ formatCurrency(getItemHourlyRate(item)) }}/час × {{ rentalDays }} дн.)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import RentalConditions from './RentalConditions.vue';
import EquipmentSpecifications from './EquipmentSpecifications.vue';

export default {
    name: 'RequestItems',
    components: {
        RentalConditions,
        EquipmentSpecifications
    },
    props: {
        categories: {
            type: Array,
            default: () => []
        },
        generalHourlyRate: {
            type: Number,
            required: true,
            default: 0,
            // ⚠️ ИСПРАВЛЕНИЕ: Добавлен валидатор для пропса
            validator: (value) => {
                return typeof value === 'number' && value >= 0;
            }
        },
        generalConditions: {
            type: Object,
            default: () => ({})
        },
        rentalPeriod: {
            type: Object,
            default: () => ({})
        },
        initialItems: {
            type: Array,
            default: () => []
        }
    },
    emits: ['items-updated', 'total-budget-updated'],
    data() {
        return {
            items: [],
            isInitialized: false,
            preventUpdateLoop: false,
            debounceTimeout: null
        }
    },
    computed: {
        totalQuantity() {
            return this.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
        },
        uniqueCategories() {
            const categoryIds = this.items.map(item => item.category_id).filter(id => id);
            return new Set(categoryIds).size;
        },
        rentalDays() {
            if (!this.rentalPeriod.start || !this.rentalPeriod.end) return 0;
            try {
                const start = new Date(this.rentalPeriod.start);
                const end = new Date(this.rentalPeriod.end);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                return days > 0 ? days : 0;
            } catch (e) {
                console.error('Date calculation error:', e);
                return 0;
            }
        },
        totalBudget() {
            const total = this.items.reduce((sum, item) => sum + this.calculateItemPrice(item), 0);
            return total;
        }
    },
    watch: {
        items: {
            handler(newItems) {
                if (this.isInitialized && !this.preventUpdateLoop) {
                    this.debouncedUpdateItems();
                }
            },
            deep: true
        },
        generalHourlyRate: {
            handler(newRate) {
                console.log('🔄 RequestItems: generalHourlyRate изменен:', newRate, typeof newRate);
                if (this.isInitialized) {
                    this.updateItemsWithGeneralRate(newRate);
                    this.debouncedUpdateItems();
                }
            },
            immediate: true
        },
        generalConditions: {
            handler(newConditions) {
                if (this.isInitialized) {
                    this.debouncedUpdateItems();
                }
            },
            deep: true
        },
        rentalPeriod: {
            handler(newPeriod) {
                if (this.isInitialized) {
                    this.debouncedUpdateItems();
                }
            },
            deep: true
        },
        initialItems: {
            handler(newItems) {
                if (this.preventUpdateLoop) return;

                console.log('🔄 RequestItems: initialItems изменены', {
                    newItemsLength: newItems?.length,
                    currentItemsLength: this.items?.length
                });

                if (newItems && newItems.length > 0) {
                    const normalizedNew = this.normalizeItems(newItems);
                    const normalizedCurrent = this.normalizeItems(this.items);

                    if (JSON.stringify(normalizedNew) !== JSON.stringify(normalizedCurrent)) {
                        console.log('✅ Загружаем initialItems в items');
                        this.items = normalizedNew;
                    }
                } else if (this.items.length === 0) {
                    this.items = [this.createEmptyItem()];
                }
            },
            deep: true,
            immediate: true
        }

    },
    methods: {
        // ⚠️ ИСПРАВЛЕНИЕ: Добавлен метод для гарантии числового значения
        ensureNumber(value) {
            if (value === null || value === undefined || value === '') {
                return 0;
            }
            const num = Number(value);
            return isNaN(num) ? 0 : num;
        },

        prepareSpecifications(specs) {
            if (!specs || typeof specs !== 'object') {
                return {};
            }

            const prepared = {};

            if (specs.values && typeof specs.values === 'object') {
                Object.keys(specs.values).forEach(key => {
                    const value = specs.values[key];
                    prepared[key] = value === '' || value === null ? null : this.convertToNumber(value);
                });
            } else {
                Object.keys(specs).forEach(key => {
                    const value = specs[key];
                    prepared[key] = value === '' || value === null ? null : this.convertToNumber(value);
                });
            }

            return prepared;
        },

        // ⚠️ ИСПРАВЛЕНИЕ: Улучшенный метод подготовки спецификаций для отправки
        prepareSpecificationsForSubmission(specs) {
            if (!specs || typeof specs !== 'object') {
                return {};
            }

            const prepared = {};

            // Обрабатываем новый формат с values/metadata
            if (specs.values && typeof specs.values === 'object') {
                Object.keys(specs.values).forEach(key => {
                    const value = specs.values[key];

                    // Для числовых значений преобразуем в число, для текстовых оставляем как есть
                    if (specs.metadata?.[key]?.dataType === 'number') {
                        prepared[key] = value === '' || value === null ? null : Number(value);
                    } else {
                        prepared[key] = value === '' || value === null ? null : value;
                    }
                });
            } else {
                // Обрабатываем старый формат
                Object.keys(specs).forEach(key => {
                    const value = specs[key];
                    prepared[key] = value === '' || value === null ? null : value;
                });
            }

            return prepared;
        },


        // ⚠️ ИСПРАВЛЕНИЕ: Метод преобразования в число или null
        convertToNumberOrNull(value) {
            if (value === '' || value === null || value === undefined) {
                return null;
            }

            const num = Number(value);
            return isNaN(num) ? null : num;
        },

        convertToNumber(value) {
            if (value === '' || value === null || value === undefined) {
                return null;
            }

            const num = Number(value);
            return isNaN(num) ? value : num;
        },

        normalizeItem(item) {
            return {
                category_id: item.category_id || null,
                quantity: parseInt(item.quantity) || 1,
                hourly_rate: item.hourly_rate ? this.ensureNumber(item.hourly_rate) : null,
                use_individual_conditions: Boolean(item.use_individual_conditions),
                individual_conditions: item.individual_conditions || {},
                specifications: this.prepareSpecificationsForSubmission(item.specifications),
                // ⚠️ ДОБАВЛЯЕМ МЕТАДАННЫЕ ДЛЯ КАСТОМНЫХ ПАРАМЕТРОВ
                custom_specs_metadata: item.specifications?.metadata || {}
            };
        },

        normalizeItems(items) {
            return items.map(item => this.normalizeItem(item));
        },

        safeEmitUpdates() {
            if (this.preventUpdateLoop) {
                console.log('🛑 Предотвращена циклическая отправка');
                return;
            }

            this.preventUpdateLoop = true;
            this.emitUpdates();

            this.$nextTick(() => {
                this.preventUpdateLoop = false;
            });
        },

        debouncedUpdateItems() {
            if (this.debounceTimeout) {
                clearTimeout(this.debounceTimeout);
            }
            this.debounceTimeout = setTimeout(() => {
                this.emitUpdates();
            }, 300);
        },

        getCategoryName(categoryId) {
            if (!categoryId) return 'Без категории';
            const category = this.categories.find(cat => cat.id == categoryId);
            return category?.name || 'Категория не найдена';
        },

        emitUpdates() {
            console.log('📤 RequestItems: отправка обновленных данных', this.items);

            const preparedItems = this.items.map(item => ({
                ...item,
                specifications: this.prepareSpecificationsForSubmission(item.specifications)
            }));

            this.$emit('items-updated', preparedItems);
            this.$emit('total-budget-updated', this.totalBudget);
        },

        addItem() {
            const newItem = this.createEmptyItem();
            this.items.push(newItem);
            this.emitUpdates();
            console.log('➕ Добавлена новая позиция');
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.emitUpdates();
                console.log('➖ Удалена позиция', index);
            }
        },

        createEmptyItem() {
            return {
                category_id: null,
                quantity: 1,
                hourly_rate: this.ensureNumber(this.generalHourlyRate),
                use_individual_conditions: false,
                individual_conditions: {},
                specifications: {}
            };
        },

        onCategoryChange(item, index) {
            this.items[index].specifications = {};
            this.emitUpdates();
        },

        // В RequestItems.vue - ИСПРАВЛЯЕМ ЭТОТ МЕТОД:
        onSpecificationsUpdate(index, specifications) {
            console.log('🔄 RequestItems: получены обновленные спецификации для позиции', index, specifications);

            // ⚠️ УБИРАЕМ ПРИНУДИТЕЛЬНОЕ ОБНОВЛЕНИЕ - ЭТО ВЫЗЫВАЕТ ЦИКЛ
            // Просто обновляем данные без вызова forceItemsUpdate()
            this.items[index].specifications = specifications;

            // ⚠️ УБИРАЕМ forceItemsUpdate() - данные уже обновлены
            // this.forceItemsUpdate();

            // Вместо этого просто отмечаем изменения
            this.hasUnsavedChanges = true;
        },

        toggleIndividualConditions(index, event) {
            const isChecked = event.target.checked;
            this.items[index].use_individual_conditions = isChecked;

            if (isChecked) {
                this.items[index].individual_conditions = { ...this.generalConditions };
            } else {
                this.items[index].individual_conditions = {};
            }

            this.emitUpdates();
        },

        updateItemConditions(index, conditions) {
            this.items[index].individual_conditions = conditions;
            this.emitUpdates();
        },

        updateItemsWithGeneralRate(newRate) {
            const safeRate = this.ensureNumber(newRate);
            console.log('🔄 Обновление позиций с новой ставкой:', safeRate);

            this.items.forEach(item => {
                if (!item.hourly_rate && safeRate > 0) {
                    item.hourly_rate = safeRate;
                }
            });
        },

        calculateItemPrice(item) {
            if (!item.quantity || item.quantity <= 0) {
                return 0;
            }

            const hourlyRate = this.getItemHourlyRate(item);
            if (!hourlyRate || hourlyRate <= 0) {
                return 0;
            }

            const days = this.rentalDays;
            if (days <= 0) {
                return 0;
            }

            const conditions = this.getItemConditions(item);
            const hoursPerShift = conditions.hours_per_shift || 8;
            const shiftsPerDay = conditions.shifts_per_day || 1;

            const price = hourlyRate * hoursPerShift * shiftsPerDay * days * item.quantity;
            return price;
        },

        getItemHourlyRate(item) {
            return this.ensureNumber(item.hourly_rate || this.generalHourlyRate);
        },

        getItemConditions(item) {
            return item.use_individual_conditions && item.individual_conditions
                ? item.individual_conditions
                : this.generalConditions;
        },

        formatCurrency(amount) {
            if (!amount) return '0 ₽';
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        }
    },
    mounted() {
        console.log('🔍 RequestItems mounted DEBUG:', {
            initialItems: this.initialItems,
            items: this.items,
            categoriesCount: this.categories?.length,
            generalHourlyRate: this.generalHourlyRate,
            generalHourlyRate_type: typeof this.generalHourlyRate,
            rentalPeriod: this.rentalPeriod
        });

        this.isInitialized = true;

        if (this.items.length === 0) {
            this.items = [this.createEmptyItem()];
        }

        this.emitUpdates();
    },

    beforeUnmount() {
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }
    }
}
</script>

<style lang="scss" scoped>
.request-items {
    margin-bottom: 2rem;

    .item-card {
        transition: all 0.3s ease;
        border-left: 4px solid #0d6efd;

        &:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    }

    .individual-conditions {
        border-left: 3px solid #20c997;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .form-control[readonly] {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }

    // Mobile-first стили
    @media (max-width: 768px) {
        .items-list {
            .item-card {
                margin-bottom: 1rem;

                .card-body {
                    padding: 1rem;
                }

                .row.g-3 {
                    margin: 0;

                    > [class*="col-"] {
                        margin-bottom: 1rem;
                        flex: 0 0 100%;
                        max-width: 100%;
                    }
                }
            }
        }

        .card.bg-light {
            .row {
                > [class*="col-"] {
                    margin-bottom: 1rem;
                    flex: 0 0 100%;
                    max-width: 100%;
                }
            }
        }
    }

    @media (max-width: 576px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;

            .btn-sm {
                align-self: stretch;
            }
        }

        .individual-conditions {
            padding: 1rem !important;

            .p-3 {
                padding: 1rem !important;
            }
        }
    }
}
</style>
