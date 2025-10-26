<template>
    <div class="position-card" :class="{ expanded: isExpanded }">
        <div class="position-header" @click="toggleExpanded">
            <div class="position-summary">
                <div class="category-info">
                    <span class="category-badge">{{ item.category?.name || 'Без категории' }}</span>
                    <span class="quantity-badge">×{{ item.quantity }}</span>
                </div>

                <div class="price-info">
                    <span class="price">{{ formatCurrency(item.calculated_price || 0) }}</span>
                </div>

                <div class="conditions-info">
                    <span class="conditions-badge" :class="conditionsTypeClass">
                        {{ conditionsTypeText }}
                    </span>
                </div>
            </div>

            <div class="expand-icon">
                <i class="fas" :class="isExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
        </div>

        <div v-if="isExpanded" class="position-details">
            <div class="details-grid">
                <!-- Технические параметры -->
                <div class="details-section">
                    <h6 class="section-title">
                        <i class="fas fa-sliders-h me-2"></i>Технические параметры
                    </h6>
                    <SpecificationsDisplay :specifications="item.formatted_specifications || []" />
                </div>

                <!-- Условия аренды -->
                <div class="details-section">
                    <h6 class="section-title">
                        <i class="fas fa-file-contract me-2"></i>Условия аренды
                    </h6>
                    <RentalConditionsDisplay :conditions="item.display_conditions || {}" />
                </div>

                <!-- Дополнительная информация -->
                <div class="details-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>Детали позиции
                    </h6>
                    <div class="item-details">
                        <div class="detail-item">
                            <span class="detail-label">Стоимость часа:</span>
                            <span class="detail-value">{{ formatCurrency(item.hourly_rate) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Количество:</span>
                            <span class="detail-value">{{ item.quantity }} ед.</span>
                        </div>
                        <div v-if="item.use_individual_conditions" class="detail-item">
                            <span class="detail-label">Индивидуальные условия:</span>
                            <span class="detail-value text-success">Да</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import SpecificationsDisplay from './SpecificationsDisplay.vue';
import RentalConditionsDisplay from './RentalConditionsDisplay.vue';

export default {
    name: 'PositionCard',
    components: {
        SpecificationsDisplay,
        RentalConditionsDisplay
    },
    props: {
        item: {
            type: Object,
            required: true
        },
        initiallyExpanded: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            isExpanded: this.initiallyExpanded
        }
    },
    computed: {
        conditionsTypeClass() {
            return this.item.conditions_type === 'individual' ? 'bg-warning' : 'bg-secondary';
        },
        conditionsTypeText() {
            return this.item.conditions_type === 'individual' ? 'Индивидуальные' : 'Общие';
        }
    },
    methods: {
        toggleExpanded() {
            this.isExpanded = !this.isExpanded;
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
.position-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.position-card.expanded {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #0d6efd;
}

.position-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    cursor: pointer;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.position-header:hover {
    background: #e9ecef;
}

.position-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.category-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-badge {
    background: #0d6efd;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.875rem;
}

.quantity-badge {
    background: #6c757d;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 15px;
    font-size: 0.75rem;
}

.price-info .price {
    font-weight: 600;
    font-size: 1.1rem;
    color: #198754;
}

.conditions-badge {
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
}

.expand-icon {
    color: #6c757d;
    transition: transform 0.3s ease;
}

.position-card.expanded .expand-icon {
    transform: rotate(180deg);
}

.position-details {
    padding: 1.5rem;
    border-top: 1px solid #dee2e6;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.details-section {
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    border-bottom: 2px solid #0d6efd;
    padding-bottom: 0.5rem;
}

.item-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.detail-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.875rem;
}

.detail-value {
    font-weight: 600;
    color: #000;
}

@media (max-width: 768px) {
    .position-summary {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .details-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .position-header {
        padding: 0.75rem;
    }

    .position-details {
        padding: 1rem;
    }
}
</style>
