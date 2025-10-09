<template>
    <!-- Используем v-if для полного управления отображением -->
     <div v-if="show" class="modal-overlay" @click.self="closeModal">
        <div class="modal-container modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2 text-primary"></i>
                        Предложить технику для заявки
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Остальное содержимое без изменений -->
                    <div class="request-info mb-4 p-3 bg-light rounded">
                        <h6>{{ request.title }}</h6>
                        <p class="mb-2 text-muted">{{ request.description }}</p>
                        <div class="row small text-muted">
                            <div class="col-md-6">
                                <i class="fas fa-calendar-alt me-1"></i>
                                {{ formatDate(request.rental_period_start) }} - {{ formatDate(request.rental_period_end) }}
                            </div>
                            <div class="col-md-6">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                {{ request.location?.name }}
                            </div>
                        </div>
                    </div>

                        <!-- Выбор оборудования -->
                        <div class="equipment-selection mb-4">
                            <h6 class="mb-3">Выберите технику для предложения</h6>

                            <div v-if="loadingEquipment" class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                                <p class="mt-2 small text-muted">Загрузка вашей техники...</p>
                            </div>

                            <div v-else-if="availableEquipment.length === 0" class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                У вас нет подходящей техники для этой заявки
                            </div>

                            <div v-else class="equipment-list">
                                <div v-for="item in availableEquipment"
                                     :key="item.equipment.id"
                                     class="equipment-item card mb-3"
                                     :class="{ 'border-primary': selectedEquipmentId === item.equipment.id }">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-1">
                                                <input type="radio"
                                                       :id="`equipment_${item.equipment.id}`"
                                                       :value="item.equipment.id"
                                                       v-model="selectedEquipmentId"
                                                       class="form-check-input">
                                            </div>
                                            <div class="col-md-3">
                                                <label :for="`equipment_${item.equipment.id}`" class="form-check-label cursor-pointer">
                                                    <strong>{{ item.equipment.title }}</strong>
                                                </label>
                                                <div class="small text-muted">
                                                    {{ item.equipment.brand }} {{ item.equipment.model }}
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div v-if="item.equipment.specifications"
                                                     class="specifications small">
                                                    <div v-for="spec in getFormattedSpecifications(item.equipment)"
                                                         :key="spec.key"
                                                         class="spec-item text-muted">
                                                        {{ spec.formatted || spec }}
                                                    </div>
                                                </div>
                                                <div v-else class="text-muted small">
                                                    Нет спецификаций
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <div class="fw-bold text-success">
                                                    {{ formatCurrency(item.recommended_lessor_price) }}/час
                                                </div>
                                                <small class="text-muted">
                                                    Ваша цена
                                                </small>
                                            </div>
                                            <div class="col-md-2">
                                                <span class="badge bg-success">
                                                    Доступно
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Форма предложения -->
                        <div v-if="selectedEquipmentId" class="proposal-form">
                            <h6 class="mb-3">Детали предложения</h6>

                            <!-- Расчет стоимости с наценкой платформы -->
                            <div class="pricing-breakdown p-3 bg-light rounded mb-3">
                                <h6 class="mb-2">Расчет стоимости</h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <label class="form-label small">Ваша цена (₽/час)</label>
                                            <input type="number"
                                                   v-model="proposalData.proposed_price"
                                                   class="form-control form-control-sm"
                                                   :min="minPrice"
                                                   :max="maxPrice"
                                                   step="50"
                                                   @input="recalculatePricing">
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label small">Количество</label>
                                            <input type="number"
                                                   v-model="proposalData.quantity"
                                                   class="form-control form-control-sm"
                                                   min="1"
                                                   :max="selectedEquipment?.max_available_quantity || 1"
                                                   @input="recalculatePricing">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="pricing-details small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Бюджет арендатора:</span>
                                                <span class="fw-bold">{{ formatCurrency(clientMaxBudget) }}/час</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Ваша цена:</span>
                                                <span>{{ formatCurrency(proposalData.proposed_price) }}/час</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1 text-success">
                                                <span>Экономия клиента:</span>
                                                <span>+{{ formatCurrency(pricingDetails.client_saving) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Наценка платформы:</span>
                                                <span class="text-info">
                                                    {{ formatCurrency(pricingDetails.platform_markup.fixed) }} +
                                                    {{ formatCurrency(pricingDetails.platform_markup.percentage) }} =
                                                    <strong>{{ formatCurrency(pricingDetails.platform_markup.total) }}</strong>
                                                </span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between fw-bold">
                                                <span>Цена для арендатора:</span>
                                                <span class="text-success">{{ formatCurrency(pricingDetails.final_price) }}/час</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Итоговый расчет -->
                            <div class="cost-calculation p-3 bg-warning bg-opacity-10 rounded mb-3">
                                <h6 class="mb-2">Итоговая стоимость за период</h6>
                                <div class="row small">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between">
                                            <span>Цена для арендатора:</span>
                                            <span>{{ formatCurrency(pricingDetails.final_price) }}/час</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Количество:</span>
                                            <span>{{ proposalData.quantity }} шт.</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Часов в день:</span>
                                            <span>{{ request.rental_conditions?.hours_per_shift || 8 }} × {{ request.rental_conditions?.shifts_per_day || 1 }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between">
                                            <span>Дней аренды:</span>
                                            <span>{{ rentalDays }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between fw-bold fs-6">
                                            <span>Общая стоимость:</span>
                                            <span class="text-success">{{ formatCurrency(totalCost) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted">
                                            <small>Ваш доход:</small>
                                            <small>{{ formatCurrency(lessorIncome) }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Сообщение для арендатора</label>
                                <textarea v-model="proposalData.message"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Расскажите о вашей технике и условиях..."
                                          :maxlength="1000"></textarea>
                                <div class="form-text text-end">
                                    {{ proposalData.message.length }}/1000 символов
                                </div>
                            </div>
                        </div>
                    </div>
                     <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal">
                            <i class="fas fa-times me-2"></i>Отмена
                        </button>
                        <button type="button"
                                class="btn btn-primary"
                                :disabled="!canSubmitProposal"
                                @click="submitProposal">
                            <i class="fas fa-paper-plane me-2"></i>
                            {{ submitting ? 'Отправка...' : 'Отправить предложение' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
</template>

<script>
export default {
    name: 'PublicProposalModal',
    props: {
        show: {
            type: Boolean,
            required: true
        },
        request: {
            type: Object,
            required: true
        }
    },
    emits: ['close', 'proposal-created'],
    data() {
        return {
            loadingEquipment: false,
            availableEquipment: [],
            selectedEquipmentId: null,
            proposalData: {
                proposed_price: 0,
                quantity: 1,
                message: ''
            },
            pricingDetails: {
                lessor_price: 0,
                client_saving: 0,
                platform_markup: { fixed: 0, percentage: 0, total: 0 },
                final_price: 0
            },
            submitting: false,
            minPrice: 100,
            maxPrice: 10000,
            markup: { type: 'fixed', value: 100 }, // Дефолтная наценка
        }
    },
    computed: {
        selectedEquipment() {
            return this.availableEquipment.find(item => item.equipment.id === this.selectedEquipmentId);
        },
        clientMaxBudget() {
            return this.request.max_hourly_rate || this.request.hourly_rate || 0;
        },
        rentalDays() {
            if (!this.request.rental_period_start || !this.request.rental_period_end) return 0;
            const start = new Date(this.request.rental_period_start);
            const end = new Date(this.request.rental_period_end);
            return Math.ceil((end - start) / (1000 * 3600 * 24)) + 1;
        },
        totalCost() {
            const hoursPerDay = (this.request.rental_conditions?.hours_per_shift || 8) *
                              (this.request.rental_conditions?.shifts_per_day || 1);
            return this.pricingDetails.final_price * hoursPerDay * this.rentalDays * this.proposalData.quantity;
        },
        lessorIncome() {
            const hoursPerDay = (this.request.rental_conditions?.hours_per_shift || 8) *
                              (this.request.rental_conditions?.shifts_per_day || 1);
            return this.proposalData.proposed_price * hoursPerDay * this.rentalDays * this.proposalData.quantity;
        },
        canSubmitProposal() {
            return this.selectedEquipmentId &&
                   this.proposalData.proposed_price >= this.minPrice &&
                   this.proposalData.proposed_price <= this.maxPrice &&
                   this.proposalData.quantity > 0 &&
                   this.proposalData.message.trim().length >= 10 &&
                   !this.submitting;
        }
    },
    watch: {
        show: {
            immediate: true,
            handler(newVal) {
                console.log('🔔 Modal show state changed:', newVal);
                if (newVal) {
                    this.loadAvailableEquipment();
                    // Добавляем обработчик Escape
                    document.addEventListener('keydown', this.handleEscape);
                } else {
                    this.resetForm();
                    document.removeEventListener('keydown', this.handleEscape);
                }
            }
        },
        selectedEquipmentId(newVal) {
            if (newVal && this.selectedEquipment) {
                this.proposalData.proposed_price = this.selectedEquipment.recommended_lessor_price;
                this.recalculatePricing();
            }
        }
    },
    methods: {
        async loadAvailableEquipment() {
            this.loadingEquipment = true;
            try {
                console.log('🔄 Loading available equipment for request:', this.request.id);

                const response = await fetch(`/api/rental-requests/${this.request.id}/available-equipment`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log('📦 Equipment data received:', data);

                    if (data.success) {
                        this.availableEquipment = data.data?.available_equipment || [];

                        // Сохраняем наценку из ответа
                        if (data.data?.markup) {
                            this.markup = data.data.markup;
                            console.log('💰 Markup loaded:', this.markup);
                        }

                        console.log('✅ Available equipment loaded:', this.availableEquipment.length, 'items');
                    }
                }
            } catch (error) {
                console.error('❌ Equipment loading exception:', error);
                this.availableEquipment = [];
            } finally {
                this.loadingEquipment = false;
            }
        },

        recalculatePricing() {
            if (!this.selectedEquipment) return;

            const lessorPrice = this.proposalData.proposed_price;

            // Расчет цены для арендатора с фиксированной наценкой
            let customerPrice;
            if (this.markup.type === 'fixed') {
                customerPrice = lessorPrice + this.markup.value;
            } else {
                customerPrice = lessorPrice * (1 + this.markup.value / 100);
            }

            // Расчет экономии клиента
            const clientSaving = Math.max(0, this.clientMaxBudget - lessorPrice);

            // Расчет наценки платформы
            let platformMarkup;
            if (this.markup.type === 'fixed') {
                platformMarkup = {
                    fixed: this.markup.value,
                    percentage: 0,
                    total: this.markup.value
                };
            } else {
                const percentageAmount = lessorPrice * (this.markup.value / 100);
                platformMarkup = {
                    fixed: 0,
                    percentage: percentageAmount,
                    total: percentageAmount
                };
            }

            this.pricingDetails = {
                lessor_price: lessorPrice,
                client_saving: clientSaving,
                platform_markup: platformMarkup,
                final_price: customerPrice
            };

            console.log('💰 Pricing recalculated with markup:', {
                lessorPrice,
                customerPrice,
                markup: this.markup,
                platformMarkup
            });
        },

        async submitProposal() {
            this.submitting = true;
            try {
                console.log('📤 Submitting proposal for equipment:', this.selectedEquipmentId);

                const response = await fetch(`/api/rental-requests/${this.request.id}/proposals`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        equipment_ids: [this.selectedEquipmentId],
                        proposed_prices: [this.proposalData.proposed_price],
                        quantities: [this.proposalData.quantity],
                        message: this.proposalData.message
                    })
                });

                const data = await response.json();
                console.log('📨 Proposal submission response:', data);

                if (data.success) {
                    console.log('✅ Proposal created successfully:', data.data);
                    this.showSuccessMessage('Предложение успешно отправлено!');
                    this.$emit('proposal-created', data.data);
                    this.closeModal();
                } else {
                    throw new Error(data.message || 'Ошибка отправки предложения');
                }
            } catch (error) {
                console.error('❌ Proposal submission error:', error);
                this.showErrorMessage('Ошибка отправки предложения: ' + error.message);
            } finally {
                this.submitting = false;
            }
        },

        closeModal() {
            console.log('🔴 Closing modal');
            this.$emit('close');
        },

        handleEscape(event) {
            if (event.key === 'Escape') {
                this.closeModal();
            }
        },

        resetForm() {
            console.log('🔄 Resetting form');
            this.selectedEquipmentId = null;
            this.proposalData = {
                proposed_price: 0,
                quantity: 1,
                message: ''
            };
            this.pricingDetails = {
                lessor_price: 0,
                client_saving: 0,
                platform_markup: { fixed: 0, percentage: 0, total: 0 },
                final_price: 0
            };
        },

        getFormattedSpecifications(equipment) {
            if (!equipment.specifications) return [];
            return equipment.formatted_specifications || [];
        },

        showSuccessMessage(message) {
            // Временное решение - можно заменить на toast
            alert('✅ ' + message);
        },

        showErrorMessage(message) {
            alert('❌ ' + message);
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            try {
                return new Date(dateString).toLocaleDateString('ru-RU');
            } catch (error) {
                console.error('Date formatting error:', error);
                return '—';
            }
        },

        formatCurrency(amount) {
            if (!amount && amount !== 0) return '0 ₽';
            try {
                return new Intl.NumberFormat('ru-RU', {
                    style: 'currency',
                    currency: 'RUB',
                    minimumFractionDigits: 0
                }).format(amount);
            } catch (error) {
                console.error('Currency formatting error:', error);
                return '0 ₽';
            }
        }
    },
    beforeUnmount() {
        // Очищаем обработчик при уничтожении компонента
        document.removeEventListener('keydown', this.handleEscape);
    }
}
</script>
<style scoped>
/* Новые стили для чистого модального окна */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    max-width: 95vw;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalAppear 0.3s ease-out;
}

.modal-content {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.modal-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
    padding: 1rem 1.5rem;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

/* Анимация появления */
@keyframes modalAppear {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Адаптивность */
@media (max-width: 768px) {
    .modal-container {
        max-width: 98vw;
        max-height: 95vh;
        margin: 1rem;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 1rem;
    }
}

/* Стили для содержимого модального окна */
.equipment-item {
    border: 2px solid transparent;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.equipment-item:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.equipment-item.border-primary {
    border-color: #0d6efd !important;
    background-color: #f8f9ff;
}

.pricing-breakdown {
    border-left: 4px solid #0d6efd;
}

.cost-calculation {
    border-left: 4px solid #ffc107;
}

/* Улучшения для читаемости */
.specifications {
    max-height: 100px;
    overflow-y: auto;
}

.spec-item {
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    margin-bottom: 0.25rem;
    font-size: 0.85em;
}

/* Плавная прокрутка */
.modal-body {
    scroll-behavior: smooth;
}

/* Улучшения для форм */
.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Стили для состояний кнопок */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
