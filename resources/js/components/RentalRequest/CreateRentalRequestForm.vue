<template>
    <div class="create-rental-request">
        <div v-if="loading && editMode" class="text-center py-5">
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
                    <!--<form-tips :active-field="activeField"></form-tips>-->
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
        // ИСПРАВЛЕНИЕ: Убрано дублирование formData
        const defaultFormData = {
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

        return {
            // ИСПРАВЛЕНИЕ: Правильная инициализация formData
            formData: this.editMode && this.initialData
                ? { ...defaultFormData, ...this.initialData }
                : { ...defaultFormData },
            activeField: '',
            loading: false,
            totalBudget: 0,
            totalQuantity: 0,
            minDate: new Date().toISOString().split('T')[0],
            submitting: false // ДОБАВЛЕНО: для отслеживания отправки
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

        // ИСПРАВЛЕНИЕ: Добавлен метод для получения данных по умолчанию
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

            // Упрощенный расчет - делегируем детальный расчет компоненту RequestItems
            let total = 0;
            const days = this.rentalDays;
            const hourlyRate = this.formData.hourly_rate;

            this.formData.items.forEach(item => {
                const itemHourlyRate = item.hourly_rate || hourlyRate;
                // Базовая формула, детали должны быть в RequestItems
                total += itemHourlyRate * 8 * 1 * days * item.quantity; // 8 часов × 1 смена
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
            this.formData.location_id = location.id;
            console.log('Location ID updated:', this.formData.location_id);
        },

        // ИСПРАВЛЕНИЕ: Объединены дублирующиеся методы submitForm
        async submitForm() {
            if (this.editMode) {
                await this.updateRequest();
            } else {
                await this.createRequest();
            }
        },

        async createRequest() {
            this.$emit('loading-start');
            this.submitting = true;

            if (!this.isFormValid) {
                alert('Пожалуйста, заполните все обязательные поля и добавьте хотя бы одну позицию');
                this.$emit('loading-end');
                this.submitting = false;
                return;
            }

            try {
                const response = await fetch(this.storeUrl, {
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
                    this.$emit('saved', data.data);
                    window.location.href = data.redirect_url;
                } else {
                    throw new Error(data.message || 'Ошибка при создании заявки');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showError(error.message);
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
                this.showError(error.message);
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
                items: this.formData.items.map(item => ({
                    category_id: item.category_id,
                    quantity: parseInt(item.quantity) || 1,
                    hourly_rate: item.hourly_rate ? parseFloat(item.hourly_rate) : null,
                    use_individual_conditions: Boolean(item.use_individual_conditions),
                    individual_conditions: item.use_individual_conditions ? item.individual_conditions : {},
                    specifications: item.specifications || {}
                })),
                delivery_required: Boolean(this.formData.delivery_required)
            };

            // Для edit mode добавляем метод
            if (this.editMode) {
                formData._method = 'PUT';
            }

            console.log('Prepared form data:', formData);
            return formData;
        },

        showError(message) {
            // Можно использовать SweetAlert или другой способ показа ошибок
            alert('Ошибка: ' + message);
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

        // ДОБАВЛЕНО: Инициализация формы данными для редактирования
        initializeFormWithData() {
            if (this.editMode && this.initialData) {
                // Дополнительная обработка данных для режима редактирования
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
            formData: this.formData
        });

        if (this.editMode) {
            this.initializeFormWithData();
        }
    }
}
</script>
