<template>
    <div v-if="show" class="modal-overlay" @click.self="closeModal">
        <div class="modal-container modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2 text-primary"></i>
                        {{ isBulkProposal ? 'Предложить несколько видов техники' : 'Предложить технику для заявки' }}
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- 🔥 ОТЛАДОЧНАЯ ИНФОРМАЦИЯ -->
                    <div v-if="debugMode" class="debug-info bg-dark text-white p-3 rounded mb-3">
                        <strong>🐛 Отладка модального окна:</strong>
                        <div class="row small mt-2">
                            <div class="col-md-4">
                                <strong>Request ID:</strong> {{ request?.id }}<br>
                                <strong>Delivery Required:</strong> {{ request?.delivery_required }}
                            </div>
                            <div class="col-md-4">
                                <strong>Selected Equipment:</strong> {{ selectedEquipmentIds?.length || 0 }}<br>
                                <strong>Available Equipment:</strong> {{ availableEquipment?.length || 0 }}
                            </div>
                            <div class="col-md-4">
                                <strong>Delivery Status:</strong> {{ deliveryCalculation.loading ? 'Loading...' : 'Ready' }}<br>
                                <strong>Delivery Cost:</strong> {{ deliveryCalculation.delivery_cost }}
                            </div>
                        </div>
                    </div>

                    <!-- Проверка наличия данных -->
                    <div v-if="!request" class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Ошибка: данные заявки не загружены
                    </div>

                    <template v-else>
                        <!-- Информация о заявке -->
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
                                    <span v-if="request.delivery_required" class="badge bg-warning ms-2">
                                        <i class="fas fa-truck me-1"></i>Требуется доставка
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Блок информации о доставке -->
                        <div v-if="request.delivery_required" class="delivery-section mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-truck me-2"></i>
                                Информация о доставке
                            </h6>
                            <!-- 🔥 КНОПКА ПЕРЕСЧЕТА -->
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary mb-3"
                                    @click="forceRecalculateDelivery"
                                    :disabled="deliveryCalculation.loading">
                                <i class="fas fa-redo me-1"></i>
                                {{ deliveryCalculation.loading ? 'Расчет...' : 'Пересчитать' }}
                            </button>

                            <div v-if="deliveryCalculation.loading" class="alert alert-info">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Расчет стоимости доставки...
                            </div>

                            <div v-else-if="deliveryCalculation.error" class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ deliveryCalculation.error }}
                            </div>

                            <div v-else-if="deliveryCalculation.delivery_required" class="alert alert-success">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Расстояние:</strong> {{ deliveryCalculation.distance_km }} км
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Тип транспорта:</strong> {{ getVehicleTypeName(deliveryCalculation.vehicle_type) }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Стоимость доставки:</strong>
                                        <span class="fw-bold text-success">{{ formatCurrency(deliveryCalculation.delivery_cost) }}</span>
                                    </div>
                                </div>
                                <div v-if="deliveryCalculation.from_location && deliveryCalculation.to_location" class="mt-2 small">
                                    <i class="fas fa-route me-1"></i>
                                    Маршрут:
                                    <strong>{{ formatLocationName(deliveryCalculation.from_location) }}</strong> →
                                    <strong>{{ formatLocationName(deliveryCalculation.to_location) }}</strong>
                                </div>
                            </div>

                            <div v-else class="alert alert-secondary">
                                <i class="fas fa-info-circle me-2"></i>
                                Доставка не требуется или не может быть рассчитана
                            </div>
                        </div>

                        <!-- Блок информации о комплексном предложении -->
                        <div v-if="isBulkProposal" class="bulk-proposal-info alert alert-info mb-4">
                            <h6><i class="fas fa-layer-group me-2"></i>Комплексное предложение</h6>
                            <p class="mb-0">
                                Вы предлагаете <strong>{{ selectedEquipmentIds.length }} видов техники</strong>.
                                Арендатор увидит конкретные модели из вашего каталога.
                            </p>
                        </div>

                        <!-- Множественный выбор оборудования -->
                        <div class="equipment-selection mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Выберите технику из вашего каталога</h6>
                                <div v-if="selectedEquipmentIds.length > 0" class="badge bg-primary">
                                    Выбрано: {{ selectedEquipmentIds.length }}
                                </div>
                            </div>

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
                                     :class="{ 'border-primary': isEquipmentSelected(item.equipment.id) }">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-1">
                                                <input type="checkbox"
                                                       :id="`equipment_${item.equipment.id}`"
                                                       :value="item.equipment.id"
                                                       v-model="selectedEquipmentIds"
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
                                                    Рекомендуемая цена
                                                </small>
                                            </div>
                                            <div class="col-md-1">
                                                <span class="badge bg-success">
                                                    Доступно
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Детали выбранного оборудования -->
                                        <div v-if="isEquipmentSelected(item.equipment.id)"
                                             class="selected-equipment-details mt-3 p-3 bg-light rounded">
                                            <div class="row align-items-end">
                                                <div class="col-md-8">
                                                    <label class="form-label small">Ваша цена за эту технику (₽/час)</label>
                                                    <input type="number"
                                                           v-model="getSelectedEquipment(item.equipment.id).proposed_price"
                                                           class="form-control"
                                                           :min="minPrice"
                                                           :max="maxPrice"
                                                           step="50"
                                                           @input="recalculatePricing">
                                                    <div class="form-text">
                                                        Рекомендуемая: {{ formatCurrency(item.recommended_lessor_price) }}
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="small text-muted">
                                                        <div>Стоимость:</div>
                                                        <div class="fw-bold text-success fs-6">
                                                            {{ formatCurrency(getSelectedEquipment(item.equipment.id).item_total) }}
                                                        </div>
                                                        <div class="text-muted">
                                                            за {{ calculateWorkingHours() }} часов
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <button type="button"
                                                            class="btn btn-outline-danger btn-sm"
                                                            @click="removeEquipment(item.equipment.id)">
                                                        <i class="fas fa-times"></i> Убрать
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Сводка предложения -->
                        <div v-if="selectedEquipmentIds.length > 0" class="proposal-summary">
                            <h6 class="mb-3">Сводка предложения</h6>

                            <!-- Таблица выбранного оборудования -->
                            <div class="selected-equipment-table mb-4">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Техника</th>
                                                <th class="text-end">Цена (₽/час)</th>
                                                <th class="text-end">Стоимость</th>
                                                <th v-if="deliveryCalculation.delivery_required" class="text-end">Доставка</th>
                                                <th class="text-end">Итого</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in selectedEquipmentDetails" :key="item.equipment.id">
                                                <td>
                                                    <strong>{{ item.equipment.title }}</strong>
                                                    <div class="small text-muted">{{ item.equipment.brand }} {{ item.equipment.model }}</div>
                                                </td>
                                                <td class="text-end">{{ formatCurrency(item.proposed_price) }}</td>
                                                <td class="text-end fw-bold text-success">
                                                    {{ formatCurrency(item.item_total) }}
                                                </td>
                                                <td v-if="deliveryCalculation.delivery_required" class="text-end">
                                                    {{ formatCurrency(deliveryCostPerItem) }}
                                                </td>
                                                <td class="text-end fw-bold text-success">
                                                    {{ formatCurrency(deliveryCalculation.delivery_required ?
                                                      item.item_total + deliveryCostPerItem : item.item_total) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td class="text-end fw-bold" :colspan="deliveryCalculation.delivery_required ? 4 : 3">
                                                    Общая стоимость:
                                                </td>
                                                <td class="text-end fw-bold fs-6 text-primary">
                                                    {{ formatCurrency(totalPriceWithDelivery) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Информация о предложении -->
                            <div class="pricing-info alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>
                                    {{ isBulkProposal ? 'Комплексное предложение' : 'Предложение' }}
                                </h6>
                                <p class="mb-2 small">
                                    <strong>Ваш общий доход:</strong>
                                    {{ formatCurrency(totalLessorPrice) }}
                                </p>
                                <p v-if="deliveryCalculation.delivery_required" class="mb-2 small">
                                    <strong>Стоимость доставки:</strong>
                                    {{ formatCurrency(deliveryCalculation.delivery_cost) }}
                                    <span class="text-muted">({{ deliveryCalculation.distance_km }} км)</span>
                                </p>
                                <p class="mb-0 small">
                                    <strong>Общая стоимость для арендатора:</strong>
                                    {{ formatCurrency(totalPriceWithDelivery) }}
                                </p>
                                <p class="mb-0 small text-muted mt-1">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Арендатор увидит полную стоимость с доставкой
                                </p>
                            </div>

                            <!-- Сообщение -->
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
                    </template>
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
                        {{ submitting ? 'Отправка...' : submitButtonText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

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
            selectedEquipmentIds: [],
            selectedEquipmentItems: {},
            csrfToken: null,
            proposalData: {
                message: ''
            },
            debugMode: true,
            deliveryCalculation: {
                loading: false,
                delivery_required: false,
                delivery_cost: 0,
                distance_km: 0,
                vehicle_type: null,
                rate_per_km: 0,
                from_location: null,
                to_location: null,
                error: null
            },
            submitting: false,
            minPrice: 100,
            maxPrice: 10000
        };
    },

    computed: {
        // 🔥 ДОБАВЛЕННЫЕ COMPUTED СВОЙСТВА
        isBulkProposal() {
            return this.selectedEquipmentIds.length > 1;
        },

        canSubmitProposal() {
            return this.selectedEquipmentIds.length > 0 &&
                   this.proposalData.message.trim().length >= 10 &&
                   !this.submitting;
        },

        submitButtonText() {
            if (this.selectedEquipmentIds.length === 0) return 'Выберите технику';
            if (this.proposalData.message.trim().length < 10) return 'Добавьте сообщение';
            return this.isBulkProposal ? 'Отправить комплексное предложение' : 'Отправить предложение';
        },

        selectedEquipmentDetails() {
            return this.selectedEquipmentIds.map(id => {
                const item = this.selectedEquipmentItems[id];
                const equipment = this.availableEquipment.find(e => e.equipment.id === id)?.equipment;
                return {
                    equipment,
                    proposed_price: item?.proposed_price || 0,
                    item_total: item?.item_total || 0
                };
            });
        },

        totalLessorPrice() {
            return this.selectedEquipmentDetails.reduce((total, item) => total + item.item_total, 0);
        },

        deliveryCostPerItem() {
            if (!this.deliveryCalculation.delivery_required || this.selectedEquipmentIds.length === 0) return 0;
            return this.deliveryCalculation.delivery_cost / this.selectedEquipmentIds.length;
        },

        totalPriceWithDelivery() {
            const basePrice = this.totalLessorPrice;
            const deliveryCost = this.deliveryCalculation.delivery_required ?
                this.deliveryCalculation.delivery_cost : 0;
            return basePrice + deliveryCost;
        }
    },

    mounted() {
        this.csrfToken = this.getCsrfToken();
    },

    watch: {
        show: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    console.log('🔄 Modal opened for request:', this.request);
                    console.log('🚚 Delivery required:', this.request.delivery_required);

                    this.loadAvailableEquipment();

                    if (this.request.delivery_required) {
                        console.log('📦 Calculating delivery because request requires it');
                        // Расчет доставки будет вызван при выборе оборудования
                    } else {
                        console.log('ℹ️ Delivery not required for this request');
                    }

                    document.addEventListener('keydown', this.handleEscape);
                } else {
                    this.resetForm();
                    document.removeEventListener('keydown', this.handleEscape);
                }
            }
        },
        selectedEquipmentIds: {
            handler(newVal) {
                console.log('🔄 Selected equipment changed:', newVal);
                this.handleEquipmentSelectionChange(newVal);
            },
            deep: true
        }
    },

    methods: {
        // 🔥 ДОБАВЛЕННЫЕ МЕТОДЫ
        isEquipmentSelected(equipmentId) {
            return this.selectedEquipmentIds.includes(equipmentId);
        },

        getSelectedEquipment(equipmentId) {
            if (!this.selectedEquipmentItems[equipmentId]) {
                const equipment = this.availableEquipment.find(e => e.equipment.id === equipmentId);
                this.selectedEquipmentItems[equipmentId] = {
                    equipment_id: equipmentId,
                    proposed_price: equipment?.recommended_lessor_price || 0,
                    quantity: 1,
                    item_total: 0
                };
            }
            return this.selectedEquipmentItems[equipmentId];
        },

        handleEquipmentSelectionChange(newIds) {
            // Добавляем новое оборудование
            newIds.forEach(id => {
                if (!this.selectedEquipmentItems[id]) {
                    const equipment = this.availableEquipment.find(item =>
                        item && item.equipment && item.equipment.id === id
                    );
                    this.selectedEquipmentItems[id] = {
                        equipment_id: id,
                        proposed_price: equipment?.recommended_lessor_price || 0,
                        quantity: 1,
                        item_total: 0
                    };
                }
            });

            // Удаляем невыбранное оборудование
            Object.keys(this.selectedEquipmentItems).forEach(id => {
                if (!newIds.includes(parseInt(id))) {
                    delete this.selectedEquipmentItems[id];
                }
            });

            this.recalculatePricing();

            // 🔥 ВЫЗЫВАЕМ РАСЧЕТ ДОСТАВКИ ПРИ ИЗМЕНЕНИИ ВЫБРАННОГО ОБОРУДОВАНИЯ
            if (newIds.length > 0 && this.request && this.request.delivery_required) {
                console.log('🚚 Equipment selection changed, recalculating delivery...');
                this.calculateDelivery();
            } else {
                console.log('ℹ️ No equipment selected or delivery not required');
                this.deliveryCalculation = {
                    loading: false,
                    delivery_required: false,
                    delivery_cost: 0,
                    distance_km: 0,
                    vehicle_type: null,
                    error: newIds.length === 0 ? 'Выберите технику для расчета доставки' : null
                };
            }
        },

        removeEquipment(equipmentId) {
            this.selectedEquipmentIds = this.selectedEquipmentIds.filter(id => id !== equipmentId);
            delete this.selectedEquipmentItems[equipmentId];
        },

        getVehicleTypeName(vehicleType) {
            const types = {
                'truck_25t': 'Грузовик 25т',
                'truck_45t': 'Грузовик 45т',
                'truck_110t': 'Трал 110т'
            };
            return types[vehicleType] || vehicleType;
        },

        formatLocationName(location) {
            return location?.name || location?.address || 'Неизвестно';
        },

        forceRecalculateDelivery() {
            if (this.selectedEquipmentIds.length > 0) {
                this.calculateDelivery();
            }
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        },

        async loadAvailableEquipment() {
            this.loadingEquipment = true;
            try {
                const response = await axios.get(`/api/rental-requests/${this.request.id}/available-equipment`, {
                    withCredentials: true
                });

                if (response.data.success) {
                    this.availableEquipment = response.data.data?.available_equipment || [];
                    console.log('✅ Available equipment loaded:', this.availableEquipment.length);
                } else {
                    console.error('❌ Failed to load equipment:', response.data.message);
                    this.availableEquipment = [];
                }
            } catch (error) {
                console.error('❌ Error loading available equipment:', error);
                this.availableEquipment = [];
            } finally {
                this.loadingEquipment = false;
            }
        },

        async submitProposal() {
            this.submitting = true;
            try {
                const equipmentItems = this.selectedEquipmentIds.map(id => {
                    const item = this.selectedEquipmentItems[id];
                    return {
                        equipment_id: id,
                        proposed_price: item.proposed_price,
                        quantity: item.quantity || 1
                    };
                });

                const response = await axios.post(
                    `/api/rental-requests/${this.request.id}/proposals`,
                    {
                        equipment_items: equipmentItems,
                        message: this.proposalData.message
                    },
                    {
                        withCredentials: true
                    }
                );

                if (response.data.success) {
                    this.$emit('proposal-created', response.data.data);
                    this.closeModal();
                } else {
                    throw new Error(response.data.message || 'Ошибка отправки предложения');
                }
            } catch (error) {
                console.error('Ошибка отправки предложения:', error);
                alert('Ошибка при отправке предложения: ' + error.message);
            } finally {
                this.submitting = false;
            }
        },

        calculateWorkingHours() {
            if (!this.request || !this.request.rental_period_start || !this.request.rental_period_end) {
                return 8;
            }

            try {
                const start = new Date(this.request.rental_period_start);
                const end = new Date(this.request.rental_period_end);
                const days = Math.ceil((end - start) / (1000 * 3600 * 24)) + 1;

                const rentalConditions = this.request.rental_conditions || {};
                const shiftHours = rentalConditions['hours_per_shift'] || 8;
                const shiftsPerDay = rentalConditions['shifts_per_day'] || 1;

                return days * shiftHours * shiftsPerDay;
            } catch (error) {
                console.error('❌ Error calculating working hours:', error);
                return 8;
            }
        },

        async calculateDelivery() {
            const ids = this.selectedEquipmentIds;

            console.log('🚚 Starting delivery calculation with equipment:', ids);

            if (ids.length === 0) {
                console.log('❌ No equipment selected, skipping delivery calculation');
                this.deliveryCalculation = {
                    loading: false,
                    delivery_required: false,
                    delivery_cost: 0,
                    distance_km: 0,
                    vehicle_type: null,
                    error: 'Выберите технику для расчета доставки'
                };
                return;
            }

            if (this.deliveryCalculation.loading) {
                console.log('⚠️ Delivery calculation already in progress, skipping');
                return;
            }

            this.deliveryCalculation.loading = true;
            this.deliveryCalculation.error = null;

            try {
                const equipmentItems = ids.map(id => {
                    const item = this.selectedEquipmentItems[id];
                    return {
                        equipment_id: id,
                        quantity: item?.quantity || 1
                    };
                });

                console.log('📤 Sending delivery calculation request:', {
                    rental_request_id: this.request.id,
                    equipment_items: equipmentItems
                });

                const response = await axios.post(
                    `/api/rental-requests/${this.request.id}/calculate-delivery`,
                    {
                        equipment_items: equipmentItems
                    },
                    {
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        timeout: 30000
                    }
                );

                console.log('📦 Delivery calculation response:', response.data);

                if (response.data.success) {
                    console.log('✅ Delivery calculation successful:', response.data.data);
                    this.deliveryCalculation = {
                        ...response.data.data,
                        loading: false,
                        error: null
                    };

                    this.recalculatePricing();
                } else {
                    throw new Error(response.data.message || 'Ошибка расчета доставки');
                }
            } catch (error) {
                console.error('❌ Delivery calculation failed:', error);
                this.deliveryCalculation = {
                    loading: false,
                    delivery_required: false,
                    delivery_cost: 0,
                    distance_km: 0,
                    vehicle_type: null,
                    error: error.response?.data?.message || error.message || 'Не удалось рассчитать доставку'
                };
            }
        },

        recalculatePricing() {
            const ids = this.selectedEquipmentIds;
            let totalLessorPrice = 0;
            const workingHours = this.calculateWorkingHours();

            ids.forEach(id => {
                const selectedItem = this.selectedEquipmentItems[id];
                if (selectedItem) {
                    const itemTotal = selectedItem.proposed_price * workingHours * (selectedItem.quantity || 1);
                    selectedItem.item_total = itemTotal;
                    totalLessorPrice += itemTotal;
                }
            });

            console.log('💰 Recalculated pricing with delivery:', {
                totalLessorPrice,
                deliveryCost: this.deliveryCalculation.delivery_cost,
                totalCustomerPrice: totalLessorPrice + (this.deliveryCalculation.delivery_cost || 0)
            });
        },

        closeModal() {
            this.$emit('close');
        },

        handleEscape(event) {
            if (event.key === 'Escape') {
                this.closeModal();
            }
        },

        resetForm() {
            this.selectedEquipmentIds = [];
            this.selectedEquipmentItems = {};
            this.proposalData.message = '';
            this.deliveryCalculation = {
                loading: false,
                delivery_required: false,
                delivery_cost: 0,
                distance_km: 0,
                vehicle_type: null,
                rate_per_km: 0,
                from_location: null,
                to_location: null,
                error: null
            };
        },

        getFormattedSpecifications(equipment) {
            if (!equipment.specifications) return [];
            return equipment.formatted_specifications || [];
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            try {
                return new Date(dateString).toLocaleDateString('ru-RU');
            } catch (error) {
                console.error('Ошибка форматирования даты:', error);
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
                console.error('Ошибка форматирования валюты:', error);
                return '0 ₽';
            }
        }
    },

    beforeUnmount() {
        document.removeEventListener('keydown', this.handleEscape);
    }
}
</script>

<style scoped>
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
}

.selected-equipment-details {
    border-left: 4px solid #0d6efd;
    background: #f8f9fa;
}

.equipment-item {
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.equipment-item:hover {
    border-color: #dee2e6;
}

.equipment-item.border-primary {
    border-color: #0d6efd !important;
    background-color: #f8f9ff;
}

.cursor-pointer {
    cursor: pointer;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }

    .selected-equipment-details .row {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
    }

    .selected-equipment-details .col-md-4 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}

.equipment-item {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
