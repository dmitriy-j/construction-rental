<!-- resources/js/components/Public/PublicRentalConditionsDisplay.vue -->
<template>
    <div class="public-rental-conditions">
        <div v-if="hasConditions" class="conditions-container">
            <!-- Основные условия - видны всем -->
            <div class="basic-conditions">
                <h6 class="section-title">
                    <i class="fas fa-clipboard-check me-2 text-primary"></i>
                    Основные условия аренды
                </h6>
                <div class="conditions-grid">
                    <ConditionItem
                        v-for="condition in basicConditions"
                        :key="condition.key"
                        :condition="condition"
                    />
                </div>
            </div>

            <!-- Расширенные условия - только для авторизованных арендодателей -->
            <div v-if="showFull && hasExtendedConditions" class="extended-conditions mt-4">
                <h6 class="section-title">
                    <i class="fas fa-list-alt me-2 text-success"></i>
                    Дополнительные условия
                </h6>
                <div class="conditions-grid">
                    <ConditionItem
                        v-for="condition in extendedConditions"
                        :key="condition.key"
                        :condition="condition"
                    />
                </div>
            </div>

            <!-- Информация для неавторизованных -->
            <div v-else-if="!showFull && hasExtendedConditions" class="extended-conditions-info mt-3 p-3 bg-light rounded">
                <div class="text-center">
                    <i class="fas fa-lock me-2 text-muted"></i>
                    <small class="text-muted">
                        Полный список условий доступен после авторизации как арендодатель
                    </small>
                    <div class="mt-2">
                        <a href="/login" class="btn btn-sm btn-outline-primary me-2">Войти</a>
                        <a href="/register?type=lessor" class="btn btn-sm btn-primary">Зарегистрироваться</a>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="no-conditions text-center py-4">
            <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
            <p class="text-muted mb-0">Стандартные условия аренды применяются по умолчанию</p>
        </div>
    </div>
</template>

<script>
import ConditionItem from './ConditionItem.vue';

export default {
    name: 'PublicRentalConditionsDisplay',
    components: {
        ConditionItem
    },
    props: {
        conditions: {
            type: Object,
            default: () => ({})
        },
        showFull: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        hasConditions() {
            return this.conditions && Object.keys(this.conditions).length > 0;
        },

        hasExtendedConditions() {
            const extendedKeys = ['transportation_organized_by', 'gsm_payment', 'accommodation_payment', 'extension_possibility', 'minimum_rental_period'];
            return extendedKeys.some(key => this.conditions[key] !== undefined);
        },

        basicConditions() {
            if (!this.hasConditions) return [];

            const basicKeys = ['payment_type', 'hours_per_shift', 'shifts_per_day', 'operator_included'];
            return this.filterAndFormatConditions(basicKeys);
        },

        extendedConditions() {
            if (!this.hasConditions || !this.showFull) return [];

            const extendedKeys = ['transportation_organized_by', 'gsm_payment', 'accommodation_payment', 'extension_possibility', 'minimum_rental_period'];
            return this.filterAndFormatConditions(extendedKeys);
        }
    },
    methods: {
        filterAndFormatConditions(keys) {
            return keys
                .filter(key => this.conditions[key] !== undefined)
                .map(key => ({
                    key,
                    label: this.getConditionLabel(key),
                    value: this.formatConditionValue(key, this.conditions[key]),
                    icon: this.getConditionIcon(key)
                }));
        },

        getConditionLabel(key) {
            const labels = {
                'payment_type': 'Тип оплаты',
                'hours_per_shift': 'Часов в смену',
                'shifts_per_day': 'Смен в день',
                'operator_included': 'Оператор включен',
                'transportation_organized_by': 'Организация транспортировки',
                'gsm_payment': 'Оплата ГСМ',
                'accommodation_payment': 'Оплата проживания',
                'extension_possibility': 'Возможность продления',
                'minimum_rental_period': 'Минимальный период аренды'
            };

            return labels[key] || key;
        },

        getConditionIcon(key) {
            const icons = {
                'payment_type': 'fa-money-bill-wave',
                'hours_per_shift': 'fa-clock',
                'shifts_per_day': 'fa-calendar-day',
                'operator_included': 'fa-user-hard-hat',
                'transportation_organized_by': 'fa-truck-moving',
                'gsm_payment': 'fa-gas-pump',
                'accommodation_payment': 'fa-hotel',
                'extension_possibility': 'fa-calendar-plus',
                'minimum_rental_period': 'fa-calendar-alt'
            };

            return icons[key] || 'fa-cog';
        },

        formatConditionValue(key, value) {
            switch (key) {
                case 'payment_type':
                    return value === 'hourly' ? 'Почасовая' :
                           value === 'daily' ? 'Посуточная' :
                           value === 'monthly' ? 'Помесячная' : value;

                case 'operator_included':
                case 'accommodation_payment':
                case 'extension_possibility':
                    return value ? 'Да' : 'Нет';

                case 'transportation_organized_by':
                    return value === 'lessor' ? 'Арендодателем' :
                           value === 'lessee' ? 'Арендатором' : value;

                case 'gsm_payment':
                    return value === 'included' ? 'Включена' :
                           value === 'separate' ? 'Отдельно' : value;

                case 'minimum_rental_period':
                    return `${value} ${this.getPeriodUnit(value)}`;

                default:
                    return value;
            }
        },

        getPeriodUnit(days) {
            if (days === 1) return 'день';
            if (days > 1 && days < 5) return 'дня';
            return 'дней';
        }
    }
}
</script>

<style scoped>
.public-rental-conditions {
    font-size: 0.95rem;
}

.section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.conditions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

.extended-conditions-info {
    border-left: 4px solid #ffc107;
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
}

.no-conditions {
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 2px dashed #dee2e6;
}

@media (max-width: 768px) {
    .conditions-grid {
        grid-template-columns: 1fr;
    }

    .section-title {
        font-size: 1rem;
    }
}
</style>
