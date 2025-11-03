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
                        <small class="text-muted ms-2">
                            ({{ getFormattedSpecifications().length }} параметров)
                        </small>
                    </h6>

                    <!-- Диагностическая информация (можно убрать после отладки) -->
                    <div v-if="getFormattedSpecifications().length > 0" class="alert alert-info py-1 mb-2">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Используются {{ item.formatted_specifications ? 'готовые' : 'самостоятельно форматированные' }} спецификации
                        </small>
                    </div>

                    <SpecificationsDisplay
                        :specifications="getFormattedSpecifications()"
                    />
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
        },

        // 🔥 ИСПРАВЛЕННЫЙ МЕТОД: Приоритет готовым отформатированным данным
        getFormattedSpecifications() {
            // ПРИОРИТЕТ 1: используем готовые отформатированные спецификации от бэкенда
            if (this.item.formatted_specifications && this.item.formatted_specifications.length > 0) {
                console.log('✅ PositionCard: Используем formatted_specifications от бэкенда:', this.item.formatted_specifications);
                return this.item.formatted_specifications;
            }

            // ПРИОРИТЕТ 2: форматируем самостоятельно из сырых данных
            if (!this.item.specifications) {
                console.log('❌ Нет спецификаций в item:', this.item);
                return [];
            }

            console.log('🔍 PositionCard: Анализ спецификаций для самостоятельного форматирования:', {
                specifications: this.item.specifications,
                type: typeof this.item.specifications,
                isArray: Array.isArray(this.item.specifications)
            });

            const formatted = [];

            // Обрабатываем массив спецификаций
            if (Array.isArray(this.item.specifications)) {
                console.log('📋 Обработка массива спецификаций:', this.item.specifications.length);
                this.item.specifications.forEach(spec => {
                    if (spec && spec.value !== null && spec.value !== '') {
                        formatted.push({
                            key: spec.key || spec.name,
                            label: spec.label || spec.name || 'Параметр',
                            value: spec.value,
                            unit: spec.unit || '',
                            display_value: spec.value + (spec.unit ? ' ' + spec.unit : ''),
                            formatted: (spec.label || spec.name || 'Параметр') + ': ' + spec.value + (spec.unit ? ' ' + spec.unit : '')
                        });
                    }
                });
            }
            // Обрабатываем объект с новой структурой
            else if (typeof this.item.specifications === 'object') {
                const specs = JSON.parse(JSON.stringify(this.item.specifications));

                // Обрабатываем стандартные спецификации
                if (specs.standard_specifications && typeof specs.standard_specifications === 'object') {
                    console.log('🏗️ Обработка стандартных спецификаций:', Object.keys(specs.standard_specifications));
                    Object.entries(specs.standard_specifications).forEach(([key, value]) => {
                        if (value !== null && value !== '' && value !== undefined) {
                            formatted.push({
                                key: key,
                                label: this.getSpecificationLabel(key),
                                value: value,
                                unit: this.getSpecificationUnit(key),
                                display_value: value + (this.getSpecificationUnit(key) ? ' ' + this.getSpecificationUnit(key) : ''),
                                formatted: this.getSpecificationLabel(key) + ': ' + value + (this.getSpecificationUnit(key) ? ' ' + this.getSpecificationUnit(key) : '')
                            });
                        }
                    });
                }

                // Обрабатываем кастомные спецификации
                if (specs.custom_specifications && typeof specs.custom_specifications === 'object') {
                    console.log('🎨 Обработка кастомных спецификаций:', Object.keys(specs.custom_specifications));
                    Object.entries(specs.custom_specifications).forEach(([key, spec]) => {
                        if (spec && spec.value !== null && spec.value !== '' && spec.value !== undefined) {
                            formatted.push({
                                key: key,
                                label: spec.label || 'Дополнительный параметр',
                                value: spec.value,
                                unit: spec.unit || '',
                                display_value: spec.value + (spec.unit ? ' ' + spec.unit : ''),
                                formatted: (spec.label || 'Дополнительный параметр') + ': ' + spec.value + (spec.unit ? ' ' + spec.unit : '')
                            });
                        }
                    });
                }

                // Обрабатываем прямой объект спецификаций (старый формат)
                if (Object.keys(specs).length > 0 && !specs.standard_specifications && !specs.custom_specifications) {
                    console.log('🔧 Обработка прямого объекта спецификаций:', Object.keys(specs));
                    Object.entries(specs).forEach(([key, value]) => {
                        if (value !== null && value !== '' && value !== undefined && typeof value !== 'object') {
                            formatted.push({
                                key: key,
                                label: this.getSpecificationLabel(key),
                                value: value,
                                unit: this.getSpecificationUnit(key),
                                display_value: value + (this.getSpecificationUnit(key) ? ' ' + this.getSpecificationUnit(key) : ''),
                                formatted: this.getSpecificationLabel(key) + ': ' + value + (this.getSpecificationUnit(key) ? ' ' + this.getSpecificationUnit(key) : '')
                            });
                        }
                    });
                }
            }

            console.log('📊 PositionCard: Итоговые форматированные спецификации:', formatted);
            return formatted;
        },

        // Метод для получения читаемых названий спецификаций
        getSpecificationLabel(key) {
            const labels = {
                'bucket_volume': 'Объем ковша',
                'weight': 'Вес', // 🔥 ДОБАВЛЕНО
                'power': 'Мощность',
                'max_digging_depth': 'Макс. глубина копания',
                'engine_power': 'Мощность двигателя',
                'operating_weight': 'Эксплуатационный вес',
                'transport_length': 'Длина транспортировки',
                'transport_width': 'Ширина транспортировки',
                'transport_height': 'Высота транспортировки',
                'engine_type': 'Тип двигателя',
                'fuel_tank_capacity': 'Емкость топливного бака',
                'max_speed': 'Макс. скорость',
                'bucket_capacity': 'Емкость ковша',
                'body_volume': 'Объем кузова',
                'load_capacity': 'Грузоподъемность',
                'axle_configuration': 'Колесная формула',
                'weight': 'Вес' // 🔥 ДОБАВЛЕНО
            };
            return labels[key] || this.formatKeyToLabel(key);
        },

        // Форматируем ключ в читаемый label
        formatKeyToLabel(key) {
            return key
                .split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        // Метод для получения единиц измерения
        getSpecificationUnit(key) {
            const units = {
                'bucket_volume': 'м³',
                'weight': 'т',
                'power': 'л.с.',
                'max_digging_depth': 'м',
                'engine_power': 'кВт',
                'operating_weight': 'т',
                'transport_length': 'м',
                'transport_width': 'м',
                'transport_height': 'м',
                'fuel_tank_capacity': 'л',
                'max_speed': 'км/ч',
                'bucket_capacity': 'м³',
                'body_volume': 'м³',
                'load_capacity': 'т'
            };
            return units[key] || '';
        }
    },

    mounted() {
        console.log('🔍 PositionCard mounted: данные для отображения', {
            id: this.item.id,
            has_formatted_specs: !!this.item.formatted_specifications,
            formatted_specs_count: this.item.formatted_specifications ? this.item.formatted_specifications.length : 0,
            has_raw_specs: !!this.item.specifications,
            raw_specs_keys: this.item.specifications ? Object.keys(this.item.specifications) : []
        });

        // Дополнительная диагностика formatted_specifications
        if (this.item.formatted_specifications) {
            console.log('📋 PositionCard formatted_specifications:', this.item.formatted_specifications);
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
