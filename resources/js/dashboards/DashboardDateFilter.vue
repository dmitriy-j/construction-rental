<template>
    <div class="dashboard-filter mb-4">
        <div class="btn-group" role="group">
            <button
                v-for="opt in options"
                :key="opt.value"
                :class="['btn', 'btn-sm', period === opt.value ? 'btn-primary' : 'btn-outline-primary']"
                @click="selectPeriod(opt.value)"
            >
                {{ opt.label }}
            </button>
            <button
                :class="['btn', 'btn-sm', showCustom ? 'btn-primary' : 'btn-outline-primary']"
                @click="toggleCustom"
            >
                <i class="bi bi-calendar3"></i>
            </button>
        </div>
        <div v-if="showCustom" class="custom-date-range mt-2 d-flex align-items-center gap-2">
            <input type="date" v-model="customFrom" class="form-control form-control-sm" style="max-width: 180px" />
            <span>—</span>
            <input type="date" v-model="customTo" class="form-control form-control-sm" style="max-width: 180px" />
            <button class="btn btn-sm btn-primary" @click="applyCustom">Применить</button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DashboardDateFilter',
    props: {
        value: { type: String, default: 'month' },
    },
    emits: ['change'],
    data() {
        return {
            period: this.value,
            showCustom: false,
            customFrom: '',
            customTo: '',
            options: [
                { value: 'today', label: 'Сегодня' },
                { value: 'week', label: 'Неделя' },
                { value: 'month', label: 'Месяц' },
                { value: 'year', label: 'Год' },
            ],
        };
    },
    watch: {
        value(val) { this.period = val; },
    },
    methods: {
        selectPeriod(val) {
            this.period = val;
            this.showCustom = false;
            this.$emit('change', { period: val, from: null, to: null });
        },
        toggleCustom() {
            this.showCustom = !this.showCustom;
            const now = new Date();
            const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            this.customTo = now.toISOString().split('T')[0];
            this.customFrom = monthAgo.toISOString().split('T')[0];
        },
        applyCustom() {
            if (this.customFrom && this.customTo) {
                this.$emit('change', { period: 'custom', from: this.customFrom, to: this.customTo });
            }
        },
    },
};
</script>

<style scoped>
.dashboard-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}
.custom-date-range input[type="date"] {
    min-width: 140px;
}
</style>
