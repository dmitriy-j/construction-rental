<template>
    <div class="specifications-display">
        <div v-if="formattedSpecifications.length > 0" class="specs-content">
            <div class="specs-grid">
                <div v-for="spec in formattedSpecifications" :key="spec.key" class="spec-item">
                    <span class="spec-label">{{ spec.label }}:</span>
                    <span class="spec-value">
                        {{ formatSpecValue(spec.value) }}
                        <span v-if="spec.unit" class="spec-unit">{{ spec.unit }}</span>
                    </span>
                </div>
            </div>
        </div>
        <div v-else class="no-specs">
            <i class="fas fa-info-circle me-2"></i>
            <span>Технические параметры не указаны</span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SpecificationsDisplay',
    props: {
        specifications: {
            type: [Array, Object],
            default: () => []
        }
    },
    computed: {
        formattedSpecifications() {
            if (!this.specifications) return [];

            console.log('🔍 SpecificationsDisplay: получены спецификации', {
                type: typeof this.specifications,
                isArray: Array.isArray(this.specifications),
                value: this.specifications
            });

            // 🔥 ДЕТАЛЬНАЯ ДИАГНОСТИКА КАСТОМНЫХ СПЕЦИФИКАЦИЙ
            if (typeof this.specifications === 'object' && this.specifications.custom_specifications) {
                console.log('🎯 ДЕТАЛИ кастомных спецификаций:', {
                    количество: Object.keys(this.specifications.custom_specifications).length,
                    ключи: Object.keys(this.specifications.custom_specifications),
                    данные: this.specifications.custom_specifications
                });
            }

            // Если уже массив отформатированных спецификаций - возвращаем как есть
            if (Array.isArray(this.specifications)) {
                const filtered = this.specifications.filter(spec =>
                    spec && spec.value !== null && spec.value !== '' && spec.value !== undefined
                );
                console.log('✅ SpecificationsDisplay: Используем отформатированный массив:', filtered);
                return filtered;
            }

            // Если это объект, пытаемся преобразовать
            if (typeof this.specifications === 'object') {
                const formatted = [];
                const specs = JSON.parse(JSON.stringify(this.specifications));

                // Обработка стандартных спецификаций
                if (specs.standard_specifications) {
                    Object.entries(specs.standard_specifications).forEach(([key, value]) => {
                        if (value !== null && value !== '' && value !== undefined) {
                            formatted.push({
                                key: key,
                                label: this.formatSpecLabel(key),
                                value: value,
                                unit: this.getSpecUnit(key),
                                display_value: value + (this.getSpecUnit(key) ? ' ' + this.getSpecUnit(key) : '')
                            });
                        }
                    });
                }

                // Обработка кастомных спецификаций
                if (specs.custom_specifications) {
                    Object.entries(specs.custom_specifications).forEach(([key, spec]) => {
                        if (spec && spec.value !== null && spec.value !== '' && spec.value !== undefined) {
                            formatted.push({
                                key: key,
                                label: spec.label || 'Доп. параметр',
                                value: spec.value,
                                unit: spec.unit || '',
                                display_value: spec.value + (spec.unit ? ' ' + spec.unit : '')
                            });
                        }
                    });
                }

                // Обработка прямого объекта (старый формат)
                if (Object.keys(specs).length > 0 && !specs.standard_specifications && !specs.custom_specifications) {
                    Object.entries(specs).forEach(([key, value]) => {
                        if (value !== null && value !== '' && value !== undefined && typeof value !== 'object') {
                            formatted.push({
                                key: key,
                                label: this.formatSpecLabel(key),
                                value: value,
                                unit: this.getSpecUnit(key),
                                display_value: value + (this.getSpecUnit(key) ? ' ' + this.getSpecUnit(key) : '')
                            });
                        }
                    });
                }

                console.log('🔄 SpecificationsDisplay: Преобразованный объект в массив:', formatted);
                return formatted;
            }

            return [];
        }
    },
    methods: {
        formatSpecValue(value) {
            if (typeof value === 'number') {
                return value % 1 === 0 ? value.toString() : value.toFixed(1);
            }
            return value;
        },

        formatSpecLabel(key) {
            const labels = {
                'bucket_volume': 'Объем ковша',
                'weight': 'Вес', // 🔥 ИСПРАВЛЕНО
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
                'axle_configuration': 'Колесная формула'
            };
            return labels[key] || key.split('_').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        },

        getSpecUnit(key) {
            const units = {
                'bucket_volume': 'м³',
                'weight': 'т', // 🔥 ДОБАВЛЕНО единица измерения
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
    }
}
</script>

<style scoped>
.specifications-display {
    width: 100%;
}

.specs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
}

.spec-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #0d6efd;
}

.spec-label {
    font-weight: 500;
    color: #495057;
    font-size: 0.875rem;
}

.spec-value {
    font-weight: 600;
    color: #000;
    text-align: right;
}

.spec-unit {
    font-size: 0.75rem;
    color: #6c757d;
    margin-left: 0.25rem;
}

.no-specs {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    color: #6c757d;
    font-style: italic;
    background: #f8f9fa;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .specs-grid {
        grid-template-columns: 1fr;
    }

    .spec-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .spec-value {
        text-align: left;
    }
}
</style>
