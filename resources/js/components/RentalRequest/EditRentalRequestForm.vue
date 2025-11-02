<template>
    <!-- ⚠️ ДОБАВЛЕН КЛАСС ДЛЯ ПРАВИЛЬНОЙ СТРУКТУРЫ СТРАНИЦЫ -->
    <div class="edit-rental-request-page">
        <div class="main-content">
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
            hasUnsavedChanges: false
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
                totalQuantity: this.totalQuantity
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

        // Загрузка данных заявки
        async loadRequestData() {
            this.loading = true;
            this.error = null;

            try {
                // Добавьте задержку для избежания 429 ошибки
                await new Promise(resolve => setTimeout(resolve, 1000));

                console.log('🔄 Загрузка данных заявки:', this.apiUrl);

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
                console.log('✅ Данные загружены:', data);

                if (data.success) {
                    this.initializeFormData(data.data);
                } else {
                    throw new Error(data.message || 'Ошибка загрузки данных');
                }
            } catch (error) {
                console.error('❌ Ошибка загрузки:', error);
                this.error = error.message;

                // Если это 429 ошибка, предложить обновить позже
                if (error.message.includes('429')) {
                    this.error = 'Слишком много запросов. Подождите несколько секунд и попробуйте снова.';
                }
            } finally {
                this.loading = false;
            }
        },

        // Инициализация данных формы
        initializeFormData(requestData) {
            // Функция для преобразования даты из ISO в формат YYYY-MM-DD
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
                    specifications: item.specifications || {}
                })) : [],
                delivery_required: Boolean(requestData.delivery_required)
            };

            // Пересчитываем бюджет и количество
            this.totalQuantity = this.formData.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
            this.calculateTotalBudget();

            console.log('📝 Форма инициализирована с данными:', this.formData);
        },

       onItemsUpdated(items) {
            // ⚠️ ДОБАВЛЯЕМ ПРОВЕРКУ НА ЦИКЛ
            if (this.preventUpdateLoop) {
                console.log('🛑 Предотвращен циклический вызов');
                return;
            }

            const currentItemsStr = JSON.stringify(this.formData.items);
            const newItemsStr = JSON.stringify(items);

            if (currentItemsStr !== newItemsStr) {
                console.log('✅ Приняты новые items от RequestItems');

                // ⚠️ ВКЛЮЧАЕМ ЗАЩИТУ ОТ ЦИКЛА
                this.preventUpdateLoop = true;
                this.formData.items = items;
                this.totalQuantity = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
                this.calculateTotalBudget();
                this.hasUnsavedChanges = true;

                // ⚠️ ВЫКЛЮЧАЕМ ЗАЩИТУ ЧЕРЕЗ НЕСКОЛЬКО МИЛЛИСЕКУНД
                setTimeout(() => {
                    this.preventUpdateLoop = false;
                }, 100);
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
                // Использовать условия из позиции или общие
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
                console.error('❌ Ошибка сохранения:', error);
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

            // Для Laravel PUT через POST
            formData._method = 'PUT';

            console.log('📤 Подготовленные данные для отправки:', formData);
            return formData;
        },

        cancel() {
            if (this.hasUnsavedChanges) {
                if (confirm('У вас есть несохраненные изменения. Вы уверены, что хотите отменить редактирование?')) {
                    window.location.href = `/lessee/rental-requests/${this.requestId}`;
                }
            } else {
                window.location.href = `/lessee/rental-requests/${this.requestId}`;
            }
        }
    },
    async mounted() {
        console.log('✅ Компонент редактирования смонтирован');
        console.log('📊 Параметры:', {
            requestId: this.requestId,
            apiUrl: this.apiUrl,
            updateUrl: this.updateUrl,
            categoriesCount: this.categories.length,
            locationsCount: this.locations.length
        });
        const sidebar = document.getElementById('sidebarContainer');
            if (sidebar) {
                console.log('📊 Состояние сайдбара:', {
                    height: sidebar.style.height,
                    classes: sidebar.className,
                    computedStyle: window.getComputedStyle(sidebar)
                });
            }

        await this.loadRequestData();
    },

    // Предупреждение при попытке уйти со страницы с несохраненными изменениями
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
/* ⚠️ ДОБАВЛЕНЫ СТИЛИ ДЛЯ ПРАВИЛЬНОЙ СТРУКТУРЫ СТРАНИЦЫ */
.edit-rental-request-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.main-content {
    flex: 1;
    padding-bottom: 2rem;
}

.edit-rental-request {
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}

.form-actions {
    padding: 1rem 0;
    border-top: 1px solid #dee2e6;
}

pre {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    font-size: 0.8rem;
    max-height: 400px;
    overflow-y: auto;
}

/* Гарантия что контент не выходит за пределы */
@media (max-width: 768px) {
    .edit-rental-request {
        padding: 0 0.75rem;
    }

    .main-content {
        padding-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .edit-rental-request {
        padding: 0 0.5rem;
    }
}
</style>
