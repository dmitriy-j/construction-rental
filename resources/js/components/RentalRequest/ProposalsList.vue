<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-handshake me-2"></i>
                Предложения от арендодателей
                <span v-if="proposalsCount > 0" class="badge bg-primary ms-2">{{ proposalsCount }}</span>
            </h5>
        </div>
        <div class="card-body">
            <!-- Загрузка -->
            <div v-if="loading" class="text-center py-3">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <p class="mt-2 text-muted">Загрузка предложений...</p>
            </div>

            <!-- Нет данных -->
            <div v-else-if="proposalsCount === 0" class="text-center py-4">
                <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                <h5>Пока нет предложений</h5>
                <p class="text-muted">Арендодатели увидят вашу заявку и скоро предложат свои варианты</p>
            </div>

            <!-- Есть предложения -->
            <div v-else class="proposals-list">
                <div v-for="proposal in filteredProposals"
                     :key="proposal.id"
                     class="proposal-card mb-4 p-3 border rounded">

                      <!-- 🔥 ОТЛАДОЧНАЯ ИНФОРМАЦИЯ -->
                        <div class="debug-info bg-dark text-white p-2 rounded mb-2 small">
                        <strong>Отладка:</strong>
                        ID: {{ proposal.id }},
                        Price: {{ proposal.proposed_price }},
                        Has PB: {{ !!proposal.price_breakdown }},
                        PB Type: {{ typeof proposal.price_breakdown }}
                        <div v-if="proposal.price_breakdown">
                            PB Keys: {{ Object.keys(proposal.price_breakdown) }}
                        </div>
                        </div>

                    <div class="row align-items-start">
                        <!-- Левая часть: информация о предложении -->
                        <div class="col-md-8">
                            <div class="d-flex align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <!-- Заголовок -->
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-2">
                                            <i class="fas fa-user me-1 text-muted"></i>Арендодатель
                                        </h6>
                                        <span v-if="proposal.lessor?.company?.average_rating"
                                              class="badge bg-warning">
                                            <i class="fas fa-star me-1"></i>
                                            {{ proposal.lessor.company.average_rating.toFixed(1) }}
                                        </span>
                                    </div>

                                    <!-- Оборудование -->
                                    <div class="equipment-info mb-2">
                                        <p class="mb-1">
                                            <i class="fas fa-cube me-1 text-muted"></i>
                                            <span v-if="proposal.equipment">
                                                <a :href="getEquipmentLink(proposal.equipment.id)"
                                                   class="text-decoration-none fw-bold equipment-link"
                                                   target="_blank">
                                                    {{ proposal.equipment.title }}
                                                </a>
                                                <span v-if="proposal.equipment.brand || proposal.equipment.model"
                                                      class="text-muted ms-1">
                                                    ({{ proposal.equipment.brand }} {{ proposal.equipment.model }})
                                                </span>
                                                <i class="fas fa-external-link-alt ms-1 small text-muted"></i>
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Сообщение -->
                                    <div v-if="proposal.message && proposal.message.trim()"
                                         class="message-box bg-light p-2 rounded mb-2">
                                        <p class="mb-0 text-dark small">
                                            <i class="fas fa-comment me-1 text-muted"></i>
                                            {{ proposal.message }}
                                        </p>
                                    </div>

                                    <!-- Цены для арендатора -->
                                    <div class="price-quantity mb-2">
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <span class="text-success fw-bold fs-6">
                                                {{ formatCurrency(getCustomerPricePerHour(proposal)) }} / час
                                            </span>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-cube me-1"></i>
                                                {{ proposal.proposed_quantity }} ед.
                                            </span>

                                            <!-- 🔥 ДОБАВЛЯЕМ СТОИМОСТЬ ДОСТАВКИ -->
                                            <span v-if="hasDelivery(proposal)"
                                                  class="badge bg-warning text-dark">
                                                <i class="fas fa-truck me-1"></i>
                                                Доставка: {{ formatCurrency(getDeliveryCost(proposal)) }}
                                            </span>
                                        </div>

                                        <!-- 🔥 Общая стоимость для арендатора -->
                                        <div class="mt-2">
                                            <span class="text-muted">
                                                Общая стоимость:
                                                <strong class="text-dark">
                                                    {{ formatCurrency(getTotalCustomerPrice(proposal)) }}
                                                </strong>
                                                <span class="text-muted ms-1">
                                                    (за {{ getWorkingHours(proposal) }} часов
                                                    <span v-if="hasDelivery(proposal)">
                                                        + доставка
                                                    </span>)
                                                </span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Детали предложения -->
                                    <div class="proposal-details small text-muted">
                                        <div class="d-flex flex-wrap gap-3">
                                            <span>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ formatDate(proposal.created_at) }}
                                            </span>
                                            <span v-if="proposal.equipment?.category" class="badge bg-light text-dark">
                                                {{ proposal.equipment.category.name }}
                                            </span>
                                            <!-- 🔥 ДОБАВЛЯЕМ ИНФОРМАЦИЮ О ТИПЕ ТРАНСПОРТА -->
                                            <span v-if="hasDelivery(proposal)" class="badge bg-secondary">
                                                <i class="fas fa-truck me-1"></i>
                                                {{ getVehicleType(proposal) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                              <!-- 🔥 ПРОВЕРКА ДОСТАВКИ -->
                                <div v-if="checkDelivery(proposal)" class="delivery-check bg-warning p-2 rounded mb-2">
                                <strong>Доставка найдена!</strong>
                                {{ getDeliveryDebugInfo(proposal) }}
                                </div>
                        </div>

                        <!-- Правая часть: действия -->
                        <div class="col-md-4">
                            <div class="d-flex flex-column h-100">
                                <!-- Основные действия -->
                                <div class="proposal-actions mb-3">
                                    <template v-if="proposal.status === 'pending'">
                                        <button class="btn btn-success w-100 mb-2"
                                                @click="addToProposalCart(proposal.id)"
                                                :disabled="isAddingToCart">
                                            <i class="fas fa-cart-plus me-1"></i>
                                            {{ isAddingToCart ? 'Добавляется...' : 'Добавить в корзину' }}
                                        </button>

                                        <button class="btn btn-outline-danger w-100"
                                                @click="$emit('proposal-rejected', proposal.id)">
                                            <i class="fas fa-times me-1"></i>
                                            Отклонить
                                        </button>
                                    </template>

                                    <template v-else>
                                        <div class="text-center">
                                            <span class="badge status-badge w-100 py-2"
                                                  :class="getStatusBadgeClass(proposal.status)">
                                                {{ getStatusText(proposal.status) }}
                                            </span>
                                        </div>
                                    </template>
                                </div>

                                <!-- Дополнительные действия -->
                                <div class="additional-actions mt-auto">
                                    <div class="d-grid gap-2">
                                        <a :href="getEquipmentLink(proposal.equipment.id)"
                                           class="btn btn-outline-primary btn-sm"
                                           target="_blank">
                                            <i class="fas fa-eye me-1"></i>Посмотреть технику
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🔥 ДОБАВЛЯЕМ БЛОК ДОСТАВКИ -->
                    <div v-if="hasDelivery(proposal)" class="delivery-info bg-light p-2 rounded mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-truck me-1 text-muted"></i>
                                Доставка: <strong class="text-warning">{{ formatCurrency(getDeliveryCost(proposal)) }}</strong>
                            </span>
                            <span class="badge bg-info text-dark">
                                {{ getDeliveryDistance(proposal) }} км
                            </span>
                        </div>
                        <div v-if="getDeliveryRoute(proposal)" class="small text-muted mt-1">
                            <i class="fas fa-route me-1"></i>
                            {{ getDeliveryRoute(proposal) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProposalsList',
    props: {
        proposals: {
            type: Array,
            default: () => []
        },
        loading: {
            type: Boolean,
            default: false
        }
    },
    emits: ['proposal-rejected'],
    data() {
        return {
            isAddingToCart: false
        }
    },
    computed: {
        filteredProposals() {
            if (!this.proposals) return [];

            return this.proposals.filter(proposal => {
                const isComment = proposal.status === 'comment' ||
                                proposal.equipment_id === null;

                return !isComment && proposal.equipment_id;
            });
        },

        proposalsCount() {
            return this.filteredProposals.length;
        }
    },
    methods: {
        // 🔥 ИЗМЕНЯЕМ ЛОГИКУ ОТОБРАЖЕНИЯ ДОСТАВКИ
        hasDelivery(proposal) {
            if (!proposal.price_breakdown) return false;

            const pb = proposal.price_breakdown;

            // Проверяем все возможные варианты структуры
            return (
                (pb.delivery_breakdown && pb.delivery_breakdown.delivery_required) ||
                (pb.delivery_breakdown && pb.delivery_breakdown.delivery_cost > 0) ||
                (pb.delivery_cost > 0)
            );
        },

        shouldShowDelivery(proposal) {
            // Показываем доставку если:
            // 1. Есть информация о доставке
            // 2. ИЛИ стоимость доставки > 0
            // 3. ИЛИ в заявке требуется доставка
            if (!proposal.price_breakdown) return false;

            const pb = proposal.price_breakdown;
            const deliveryBreakdown = pb.delivery_breakdown;

            if (!deliveryBreakdown) return false;

            return deliveryBreakdown.delivery_required ||
                   deliveryBreakdown.delivery_cost > 0 ||
                   deliveryBreakdown.distance_km > 0;
        },

        // 🔥 ОТЛАДОЧНЫЕ МЕТОДЫ
        checkDelivery(proposal) {
            console.log('🔍 Checking delivery for proposal:', proposal.id);
            console.log('📦 Full proposal:', proposal);
            console.log('💰 Price breakdown:', proposal.price_breakdown);

            if (!proposal.price_breakdown) {
                console.log('❌ No price_breakdown at all');
                return false;
            }

            const pb = proposal.price_breakdown;

            // Проверяем разные возможные структуры
            if (pb.delivery_breakdown) {
                console.log('✅ Found delivery_breakdown in root:', pb.delivery_breakdown);
                return true;
            }

            if (pb.delivery_cost !== undefined) {
                console.log('✅ Found delivery_cost in root:', pb.delivery_cost);
                return true;
            }

            console.log('❌ No delivery data found in any structure');
            return false;
        },

        getDeliveryDebugInfo(proposal) {
            const pb = proposal.price_breakdown;
            let info = '';

            if (pb.delivery_breakdown) {
                info = `Delivery breakdown: ${JSON.stringify(pb.delivery_breakdown)}`;
            } else if (pb.delivery_cost !== undefined) {
                info = `Delivery cost: ${pb.delivery_cost}`;
            }

            return info;
        },

        // 🔥 ОСНОВНЫЕ МЕТОДЫ ДОСТАВКИ
        getDeliveryCost(proposal) {
            if (!this.hasDelivery(proposal)) return 0;

            const pb = proposal.price_breakdown;

            if (pb.delivery_breakdown && pb.delivery_breakdown.delivery_cost) {
                return pb.delivery_breakdown.delivery_cost;
            }

            if (pb.delivery_cost) {
                return pb.delivery_cost;
            }

            return 0;
        },

        // 🔥 КОРРЕКТНЫЕ МЕТОДЫ ДЛЯ ЦЕН АРЕНДАТОРА
        getCustomerPricePerHour(proposal) {
            if (proposal.price_breakdown && proposal.price_breakdown.customer_price_per_unit) {
                return proposal.price_breakdown.customer_price_per_unit;
            }

            const workingHours = this.getWorkingHours(proposal);
            if (workingHours > 0 && proposal.proposed_quantity > 0) {
                return proposal.proposed_price / (workingHours * proposal.proposed_quantity);
            }

            return proposal.proposed_price; // fallback
        },

        // 🔥 ОБЩАЯ СТОИМОСТЬ ДЛЯ АРЕНДАТОРА
        getTotalCustomerPrice(proposal) {
            const basePrice = proposal.price_breakdown?.item_total_customer || proposal.proposed_price;
            const deliveryCost = this.getDeliveryCost(proposal);
            return basePrice + deliveryCost;
        },

        getDeliveryDistance(proposal) {
            return proposal.price_breakdown?.delivery_breakdown?.distance_km || 0;
        },

        getDeliveryRoute(proposal) {
            const delivery = proposal.price_breakdown?.delivery_breakdown;
            if (!delivery?.from_location || !delivery?.to_location) return null;
            return `${delivery.from_location.name} → ${delivery.to_location.name}`;
        },

        getVehicleType(proposal) {
            const vehicleType = proposal.price_breakdown?.delivery_breakdown?.vehicle_type;
            const types = {
                'light_truck': 'Газель',
                'heavy_truck': 'Фура',
                'lowbed_trailer': 'Трал',
                'special_trailer': 'Спецтранспорт'
            };
            return types[vehicleType] || vehicleType;
        },

        getWorkingHours(proposal) {
            if (proposal.price_breakdown && proposal.price_breakdown.working_hours) {
                return proposal.price_breakdown.working_hours;
            }

            if (proposal.rental_request) {
                const start = new Date(proposal.rental_request.rental_period_start);
                const end = new Date(proposal.rental_request.rental_period_end);
                const days = Math.ceil((end - start) / (1000 * 3600 * 24)) + 1;

                const rentalConditions = proposal.rental_request.rental_conditions || {};
                const shiftHours = rentalConditions.hours_per_shift || 8;
                const shiftsPerDay = rentalConditions.shifts_per_day || 1;

                return days * shiftHours * shiftsPerDay;
            }

            return 1; // fallback
        },

        async addToProposalCart(proposalId) {
            this.isAddingToCart = true;

            try {
                const response = await fetch('/api/cart/proposal/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ proposal_id: proposalId })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', 'Предложение принято и добавлено в корзину');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                this.showToast('error', error.message);
            } finally {
                this.isAddingToCart = false;
            }
        },

        getStatusBadgeClass(status) {
            const classes = {
                'accepted': 'bg-success',
                'rejected': 'bg-secondary',
                'counter_offer': 'bg-warning text-dark',
                'pending': 'bg-info'
            };
            return classes[status] || 'bg-light text-dark';
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

        getEquipmentLink(equipmentId) {
            return `/catalog/${equipmentId}`;
        },

        getStatusText(status) {
            const statusMap = {
                'pending': 'На рассмотрении',
                'accepted': 'Принято',
                'rejected': 'Отклонено',
                'counter_offer': 'Контрпредложение'
            };
            return statusMap[status] || status;
        },

        formatDate(dateString) {
            if (!dateString) return '—';
            try {
                return new Date(dateString).toLocaleDateString('ru-RU', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            } catch (error) {
                console.error('Date formatting error:', error);
                return '—';
            }
        },

        formatCurrency(amount) {
            if (!amount) return '0 ₽';
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        }
    }
}
</script>

<style scoped>
.proposal-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 4px solid #198754;
    background: white;
}

.proposal-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.equipment-link {
    color: #0d6efd;
    transition: color 0.2s ease;
}

.equipment-link:hover {
    color: #0a58ca;
    text-decoration: underline !important;
}

.proposal-actions {
    min-height: 60px;
}

.status-badge {
    font-size: 0.9em;
    padding: 0.5em 1em;
}

.message-box {
    border-left: 3px solid #6c757d;
    background-color: #f8f9fa !important;
}

.price-quantity {
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    padding: 0.5rem 0;
}

.additional-actions .btn {
    font-size: 0.85em;
}

@media (max-width: 768px) {
    .proposal-card {
        border-left: none;
        border-top: 4px solid #198754;
    }

    .col-md-4 {
        margin-top: 1rem;
        border-top: 1px solid #dee2e6;
        padding-top: 1rem;
    }

    .proposal-actions {
        min-height: auto;
    }
}

.btn {
    transition: all 0.2s ease;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.proposal-actions .btn {
    margin-bottom: 0.5rem;
}

.proposal-actions .btn:last-child {
    margin-bottom: 0;
}

.delivery-info {
    border-left: 3px solid #17a2b8;
}
</style>
