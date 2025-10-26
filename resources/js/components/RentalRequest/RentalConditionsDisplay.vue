<template>
    <div class="rental-conditions-display">
        <div class="conditions-grid">
            <div class="condition-item" v-if="conditions.payment_type">
                <span class="condition-label">Тип оплаты:</span>
                <span class="condition-value">{{ getPaymentTypeText(conditions.payment_type) }}</span>
            </div>

            <div class="condition-item" v-if="conditions.hours_per_shift">
                <span class="condition-label">Часов в смене:</span>
                <span class="condition-value">{{ conditions.hours_per_shift }} ч</span>
            </div>

            <div class="condition-item" v-if="conditions.shifts_per_day">
                <span class="condition-label">Смен в сутки:</span>
                <span class="condition-value">{{ conditions.shifts_per_day }}</span>
            </div>

            <div class="condition-item" v-if="conditions.transportation_organized_by">
                <span class="condition-label">Транспортировка:</span>
                <span class="condition-value">{{ getTransportationText(conditions.transportation_organized_by) }}</span>
            </div>

            <div class="condition-item" v-if="conditions.gsm_payment">
                <span class="condition-label">Оплата ГСМ:</span>
                <span class="condition-value">{{ getGsmPaymentText(conditions.gsm_payment) }}</span>
            </div>

            <div class="condition-item" v-if="conditions.operator_included !== undefined">
                <span class="condition-label">Оператор включен:</span>
                <span class="condition-value">{{ conditions.operator_included ? 'Да' : 'Нет' }}</span>
            </div>

            <div class="condition-item" v-if="conditions.accommodation_payment !== undefined">
                <span class="condition-label">Оплата проживания:</span>
                <span class="condition-value">{{ conditions.accommodation_payment ? 'Да' : 'Нет' }}</span>
            </div>

            <div class="condition-item" v-if="conditions.extension_possibility !== undefined">
                <span class="condition-label">Возможно продление:</span>
                <span class="condition-value">{{ conditions.extension_possibility ? 'Да' : 'Нет' }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RentalConditionsDisplay',
    props: {
        conditions: {
            type: Object,
            required: true,
            default: () => ({})
        }
    },
    methods: {
        getPaymentTypeText(type) {
            const types = {
                'hourly': 'Почасовая',
                'shift': 'Посменная',
                'daily': 'Посуточная'
            };
            return types[type] || type;
        },

        getTransportationText(type) {
            const types = {
                'lessor': 'Арендодателем',
                'lessee': 'Арендатором',
                'shared': 'Совместно'
            };
            return types[type] || type;
        },

        getGsmPaymentText(type) {
            const types = {
                'included': 'Включена в стоимость',
                'separate': 'Отдельная оплата'
            };
            return types[type] || type;
        }
    }
}
</script>

<style scoped>
.conditions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.condition-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.condition-label {
    font-weight: 500;
    color: #6c757d;
}

.condition-value {
    color: #000;
    font-weight: 500;
}

@media (max-width: 768px) {
    .conditions-grid {
        grid-template-columns: 1fr;
    }
}
</style>
