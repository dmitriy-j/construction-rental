<template>
    <div class="rental-conditions">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Тип оплаты</label>
                <select class="form-select" v-model="conditions.payment_type" @change="updateConditions">
                    <option value="hourly">Почасовая</option>
                    <option value="shift">Посменная</option>
                    <option value="daily">Посуточная</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Часов в смене</label>
                <input type="number" class="form-control" v-model.number="conditions.hours_per_shift"
                       min="1" max="24" @input="updateConditions">
            </div>

            <div class="col-md-3">
                <label class="form-label">Смен в сутки</label>
                <input type="number" class="form-control" v-model.number="conditions.shifts_per_day"
                       min="1" max="3" @input="updateConditions">
            </div>

            <div class="col-md-6">
                <label class="form-label">Организация транспортировки</label>
                <select class="form-select" v-model="conditions.transportation_organized_by" @change="updateConditions">
                    <option value="lessor">Арендодателем</option>
                    <option value="lessee">Арендатором</option>
                    <option value="shared">Совместно</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Оплата ГСМ</label>
                <select class="form-select" v-model="conditions.gsm_payment" @change="updateConditions">
                    <option value="included">Включена в стоимость</option>
                    <option value="separate">Отдельная оплата</option>
                </select>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="conditions.operator_included"
                           @change="updateConditions">
                    <label class="form-check-label">Оператор включен</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="conditions.accommodation_payment"
                           @change="updateConditions">
                    <label class="form-check-label">Оплата проживания</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="conditions.extension_possibility"
                           @change="updateConditions">
                    <label class="form-check-label">Возможно продление</label>
                </div>
            </div>
        </div>

        <!-- Информация о расчете -->
        <div v-if="showCalculation" class="calculation-info mt-4 p-3 bg-light rounded">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Расчет основан: {{ conditions.hours_per_shift }}ч × {{ conditions.shifts_per_day }} смен =
                <strong>{{ totalHoursPerDay }} часов/сутки</strong>
            </small>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RentalConditions',
    props: {
        initialConditions: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['conditions-updated'],
    data() {
        return {
            conditions: { ...this.getDefaultConditions(), ...this.initialConditions }
        }
    },
    computed: {
        totalHoursPerDay() {
            return this.conditions.hours_per_shift * this.conditions.shifts_per_day;
        },
        showCalculation() {
            return this.conditions.hours_per_shift > 0 && this.conditions.shifts_per_day > 0;
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
        updateConditions() {
            this.$emit('conditions-updated', this.conditions);
        },
        resetToDefaults() {
            this.conditions = this.getDefaultConditions();
            this.updateConditions();
        }
    },
    watch: {
        initialConditions: {
            handler(newConditions) {
                this.conditions = { ...this.getDefaultConditions(), ...newConditions };
            },
            deep: true
        }
    }
}
</script>
