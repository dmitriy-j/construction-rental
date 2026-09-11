<template>
    <div class="create-rental-request">
        <div v-if="loading && editMode" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загрузка данных заявки...</p>
        </div>

        <form @submit.prevent="submitForm">

            <!-- Error Modal -->
            <div class="modal fade" id="errorModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-danger text-white border-0">
                            <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Ошибка</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body py-4">
                            <p class="mb-0 fs-6">{{ error }}</p>
                        </div>
                        <div class="modal-footer border-0 justify-content-center">
                            <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Понятно</button>
                        </div>
                    </div>
                </div>
            </div>

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
                            <label class="form-label">Базовая стоимость часа (₽)</label>
                            <input type="number"
                                   class="form-control"
                                   v-model.number="formData.hourly_rate"
                                   min="0" step="50"
                                   placeholder="Необязательно, если у каждой позиции своя цена"
                                   @change="onHourlyRateChange($event.target.value)">
                            <small class="text-muted">Укажите, если хотите задать общую цену для всех позиций</small>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       v-model="formData.delivery_required"
                                       id="delivery_required" true-value="1" false-value="0">
                                <label class="form-check-label" for="delivery_required">
                                    <i class="fas fa-truck me-2"></i>Требуется доставка техники к объекту
                                </label>
                                <small class="form-text text-muted d-block">
                                    Отметьте, если вам необходима доставка оборудования к месту проведения работ.
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
                :rental-period="rentalPeriod"
                @items-updated="onItemsUpdated"
                @total-budget-updated="onTotalBudgetUpdated"
            />

            <!-- Итоговый бюджет -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-calculator me-2"></i>Итоговый бюджет заявки</h5>
                </div>
                <div class="card-body text-center">
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
                <button type="submit" class="btn btn-primary btn-lg px-5" :disabled="submitting">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="fas fa-paper-plane me-1"></i>
                    {{ editMode ? 'Обновить заявку' : 'Создать заявку' }}
                </button>
                <button type="button" class="btn btn-outline-secondary ms-2" @click="$emit('cancelled')">Отмена</button>
            </div>
        </form>
    </div>
</template>

<script>
import RequestItems from './RequestItems.vue';
import RentalConditions from './RentalConditions.vue';
import BudgetCalculator from './BudgetCalculator.vue';
import LocationSelector from './LocationSelector.vue';

export default {
    name: 'CreateRentalRequestForm',
    components: { RequestItems, RentalConditions, BudgetCalculator, LocationSelector },
    props: {
        categories: { type: Array, required: true, default: () => [] },
        locations: { type: Array, required: true, default: () => [] },
        storeUrl: { type: String, required: true, default: '' },
        editMode: { type: Boolean, default: false },
        initialData: { type: Object, default: null },
        requestId: { type: [String, Number], default: null },
        csrfToken: { type: String, required: true, default: '' }
    },
    data() {
        return {
            formData: this.editMode && this.initialData
                ? { ...this.getDefaultFormData(), ...this.initialData }
                : { ...this.getDefaultFormData() },
            activeField: '', loading: false, totalBudget: 0, totalQuantity: 0,
            minDate: new Date().toISOString().split('T')[0], submitting: false,
            error: null, generalHourlyRate: 0
        }
    },
    computed: {
        rentalPeriod() { return { start: this.formData.rental_period_start, end: this.formData.rental_period_end }; },
        rentalDays() {
            if (!this.formData.rental_period_start || !this.formData.rental_period_end) return 0;
            const start = new Date(this.formData.rental_period_start);
            const end = new Date(this.formData.rental_period_end);
            return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        },
        isFormValid() {
            return this.formData.title && this.formData.description &&
                   this.formData.rental_period_start && this.formData.rental_period_end &&
                   this.formData.location_id && this.formData.items.length > 0 &&
                   this.formData.items.every(item => item.category_id && item.quantity > 0);
        },
        formattedBudget() {
            if (typeof this.totalBudget !== 'number' || isNaN(this.totalBudget)) return '0 ₽';
            return this.formatCurrency(this.totalBudget);
        }
    },
    watch: {
        'formData.hourly_rate': { handler(newRate) { this.generalHourlyRate = this.ensureNumber(newRate); }, immediate: true }
    },
    methods: {
        onHourlyRateChange(value) {
            const numValue = value === '' ? 0 : Number(value);
            this.formData.hourly_rate = isNaN(numValue) ? 0 : numValue;
            this.generalHourlyRate = this.formData.hourly_rate;
        },
        ensureNumber(value) {
            if (value === null || value === undefined || value === '') return 0;
            const num = Number(value); return isNaN(num) ? 0 : num;
        },
        getDefaultConditions() {
            return { payment_type: 'hourly', hours_per_shift: 8, shifts_per_day: 1,
                transportation_organized_by: 'lessor', gsm_payment: 'included',
                operator_included: false, accommodation_payment: false, extension_possibility: true };
        },
        getDefaultFormData() {
            return { title: '', description: '', hourly_rate: 0,
                rental_period_start: '', rental_period_end: '', location_id: '',
                rental_conditions: this.getDefaultConditions(), items: [], delivery_required: false };
        },
        onItemsUpdated(items) {
            this.formData.items = items;
            this.totalQuantity = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
            this.calculateTotalBudget();
        },
        onTotalBudgetUpdated(budget) { this.totalBudget = budget; },
        onConditionsUpdated(conditions) { this.formData.rental_conditions = conditions; this.calculateTotalBudget(); },
        calculateTotalBudget() {
            if (this.formData.items.length === 0) { this.totalBudget = 0; return; }
            let total = 0; const days = this.rentalDays;
            const hourlyRate = this.ensureNumber(this.formData.hourly_rate);
            this.formData.items.forEach(item => {
                const itemHourlyRate = item.hourly_rate ? this.ensureNumber(item.hourly_rate) : hourlyRate;
                total += itemHourlyRate * 8 * 1 * days * item.quantity;
            });
            this.totalBudget = total;
        },
        onLocationCreated(newLocation) { this.locations.push(newLocation); },
        onLocationSelected(location) {
            this.formData.location_id = location && location.id ? location.id : null;
        },
        showErrorModal() {
            this.$nextTick(() => {
                const modalEl = document.getElementById('errorModal');
                if (modalEl) { const modal = new bootstrap.Modal(modalEl); modal.show(); }
            });
        },
        async submitForm() {
            try {
                this.error = null;
                if (this.editMode) await this.updateRequest();
                else await this.createRequest();
            } catch (error) {
                console.error('Ошибка при отправке формы:', error);
                this.error = error.message || 'Произошла ошибка при отправке формы';
                this.showErrorModal();
            }
        },
        async createRequest() {
            this.$emit('loading-start');
            this.submitting = true;
            if (!this.isFormValid) {
                this.error = 'Пожалуйста, заполните все обязательные поля и добавьте хотя бы одну позицию';
                this.showErrorModal();
                this.$emit('loading-end');
                this.submitting = false;
                return;
            }
            try {
                const preparedData = this.prepareFormData();
                const response = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(preparedData)
                });
                const data = await response.json();
                if (data.success) {
                    this.$emit('saved', data.data);
                    window.location.href = data.redirect_url;
                } else throw new Error(data.message || 'Ошибка при создании заявки');
            } catch (error) {
                console.error('Error:', error);
                this.error = error.message || 'Произошла ошибка при создании заявки';
                throw error;
            } finally { this.submitting = false; this.$emit('loading-end'); }
        },
        async updateRequest() {
            this.submitting = true;
            try {
                const response = await fetch(`/api/lessee/rental-requests/${this.requestId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(this.prepareFormData())
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                if (data.success) this.$emit('saved', data.data);
                else throw new Error(data.message || 'Ошибка при обновлении заявки');
            } catch (error) {
                console.error('Update error:', error);
                this.error = error.message || 'Произошла ошибка при обновлении заявки';
                throw error;
            } finally { this.submitting = false; }
        },
        prepareFormData() {
            let formData = {
                title: this.formData.title, description: this.formData.description,
                hourly_rate: this.ensureNumber(this.formData.hourly_rate),
                rental_period_start: this.formData.rental_period_start,
                rental_period_end: this.formData.rental_period_end,
                location_id: this.formData.location_id,
                rental_conditions: this.formData.rental_conditions,
                delivery_required: Boolean(this.formData.delivery_required),
                items: this.formData.items.map(item => ({
                    category_id: item.category_id, quantity: parseInt(item.quantity) || 1,
                    hourly_rate: item.hourly_rate ? this.ensureNumber(item.hourly_rate) : null,
                    use_individual_conditions: Boolean(item.use_individual_conditions),
                    individual_conditions: item.use_individual_conditions ? item.individual_conditions : {},
                    standard_specifications: item.specifications ? this.prepareSpecifications(item.specifications).standard : {},
                    custom_specifications: item.specifications ? this.prepareSpecifications(item.specifications).custom : {}
                }))
            };
            if (this.editMode) formData._method = 'PUT';
            return formData;
        },
        prepareSpecifications(specs) {
            if (!specs || typeof specs !== 'object') return { standard: {}, custom: {} };
            const standard = {}, custom = {};
            Object.keys(specs).forEach(key => {
                const value = specs[key];
                if (['bucket_volume','max_digging_depth','power','weight','engine_power','lifting_capacity','boom_length'].includes(key) || !key.startsWith('custom_'))
                    standard[key] = value;
                else
                    custom[key] = typeof value === 'object' ? value : { label: key.replace(/_/g,' ').replace(/(?:^|\s)\S/g,c=>c.toUpperCase()), value: value, unit: '', dataType: typeof value === 'number' ? 'number' : 'string' };
            });
            return { standard, custom };
        },
        formatCurrency(amount) { return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(amount); }
    },
    mounted() {
        this.generalHourlyRate = this.ensureNumber(this.formData.hourly_rate);
    }
}
</script>
