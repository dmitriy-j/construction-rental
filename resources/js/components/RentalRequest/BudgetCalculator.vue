<template>
    <div class="budget-calculator card">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-calculator me-2"></i>Калькулятор бюджета
            </h6>
        </div>
        <div class="card-body">
            <!-- Детали расчета -->
            <div class="calculation-details mb-3">
                <div class="row g-2 text-center">
                    <div class="col-md-2">
                        <small class="text-muted">Час</small>
                        <div class="fw-bold text-primary">{{ formatCurrency(hourlyRate) }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">× Часов/смену</small>
                        <div class="fw-bold">{{ hoursPerShift }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">× Смен/день</small>
                        <div class="fw-bold">{{ shiftsPerDay }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">× Дней</small>
                        <div class="fw-bold">{{ rentalDays }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">× Количество</small>
                        <div class="fw-bold">{{ equipmentQuantity }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">= ИТОГО</small>
                        <div class="fw-bold text-success">{{ formatCurrency(totalBudget) }}</div>
                    </div>
                </div>
            </div>

            <div class="budget-result" v-if="totalBudget > 0">
                <div class="alert alert-success">
                    <div class="text-center">
                        <strong>Общий бюджет заявки:</strong>
                        <div class="h3 mb-0 mt-1">{{ formatCurrency(totalBudget) }}</div>
                        <small class="text-muted">Точный расчет на основе введенных параметров</small>
                    </div>
                </div>

                <!-- Детали расчета по формуле -->
                <div class="calculation-breakdown">
                    <h6 class="text-muted mb-2">Детали расчета:</h6>
                    <div class="calculation-steps">
                        <div class="step">Стоимость смены: {{ formatCurrency(hourlyRate) }} × {{ hoursPerShift }} = {{ formatCurrency(costPerShift) }}</div>
                        <div class="step">Стоимость дня: {{ formatCurrency(costPerShift) }} × {{ shiftsPerDay }} = {{ formatCurrency(costPerDay) }}</div>
                        <div class="step">Стоимость периода: {{ formatCurrency(costPerDay) }} × {{ rentalDays }} = {{ formatCurrency(costPerPeriod) }}</div>
                        <div class="step">Общая стоимость: {{ formatCurrency(costPerPeriod) }} × {{ equipmentQuantity }} = {{ formatCurrency(totalBudget) }}</div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center text-muted py-3">
                <i class="fas fa-calculator fa-2x mb-2"></i>
                <p>Заполните данные для расчета бюджета</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BudgetCalculator',
    props: {
        hourlyRate: Number,
        rentalPeriodStart: String,
        rentalPeriodEnd: String,
        equipmentQuantity: Number,
        rentalConditions: Object
    },
    data() {
        return {
            totalBudget: 0,
            costPerShift: 0,
            costPerDay: 0,
            costPerPeriod: 0
        }
    },
    computed: {
        rentalDays() {
            if (!this.rentalPeriodStart || !this.rentalPeriodEnd) return 0;
            const start = new Date(this.rentalPeriodStart);
            const end = new Date(this.rentalPeriodEnd);
            return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        },
        hoursPerShift() {
            return this.rentalConditions?.hours_per_shift || 8;
        },
        shiftsPerDay() {
            return this.rentalConditions?.shifts_per_day || 1;
        }
    },
    watch: {
        hourlyRate: 'calculateBudget',
        rentalPeriodStart: 'calculateBudget',
        rentalPeriodEnd: 'calculateBudget',
        equipmentQuantity: 'calculateBudget',
        rentalConditions: {
            handler: 'calculateBudget',
            deep: true
        }
    },
    methods: {
        calculateBudget() {
            if (!this.hourlyRate || this.rentalDays <= 0 || this.equipmentQuantity <= 0) {
                this.resetCalculation();
                return;
            }

            // Точный расчет по вашей формуле
            this.costPerShift = this.hourlyRate * this.hoursPerShift;
            this.costPerDay = this.costPerShift * this.shiftsPerDay;
            this.costPerPeriod = this.costPerDay * this.rentalDays;
            this.totalBudget = this.costPerPeriod * this.equipmentQuantity;

            this.$emit('budget-calculated', { from: this.totalBudget, to: this.totalBudget });
        },
        resetCalculation() {
            this.totalBudget = 0;
            this.costPerShift = 0;
            this.costPerDay = 0;
            this.costPerPeriod = 0;
        },
        formatCurrency(amount) {
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
.calculation-steps .step {
    padding: 0.25rem 0;
    border-bottom: 1px dashed #eee;
}
.calculation-steps .step:last-child {
    border-bottom: none;
    font-weight: bold;
    color: #198754;
}
</style>
