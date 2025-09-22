<template>
    <div class="rental-request-search">
        <div class="card">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Категория</label>
                        <select v-model="filters.category_id" class="form-select" @change="applyFilters">
                            <option value="">Все категории</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Локация</label>
                        <select v-model="filters.location_id" class="form-select" @change="applyFilters">
                            <option value="">Все локации</option>
                            <option v-for="location in locations" :key="location.id" :value="location.id">
                                {{ location.name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Бюджет до</label>
                        <div class="input-group">
                            <input type="number" v-model="filters.budget_max" class="form-control"
                                   @input="debounceApplyFilters" placeholder="Макс. бюджет">
                            <span class="input-group-text">₽</span>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Сортировка</label>
                        <select v-model="filters.sort_by" class="form-select" @change="applyFilters">
                            <option value="newest">Сначала новые</option>
                            <option value="budget_high">Высокий бюджет</option>
                            <option value="budget_low">Низкий бюджет</option>
                            <option value="responses">Много предложений</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button @click="resetFilters" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-redo me-1"></i>Сбросить
                        </button>
                    </div>
                </div>

                <div class="row mt-3" v-if="showAdvancedFilters">
                    <div class="col-md-12">
                        <div class="advanced-filters">
                            <h6 class="mb-3">Дополнительные фильтры</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Период аренды</label>
                                    <div class="input-group">
                                        <input type="date" v-model="filters.period_start" class="form-control">
                                        <span class="input-group-text">-</span>
                                        <input type="date" v-model="filters.period_end" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Доставка</label>
                                    <select v-model="filters.delivery_required" class="form-select" @change="applyFilters">
                                        <option value="">Не важно</option>
                                        <option value="1">Требуется</option>
                                        <option value="0">Не требуется</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <a href="#" @click.prevent="toggleAdvancedFilters" class="text-decoration-none">
                            <i class="fas" :class="showAdvancedFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            {{ showAdvancedFilters ? 'Скрыть' : 'Показать' }} дополнительные фильтры
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RentalRequestSearch',

    props: {
        initialCategories: Array,
        initialLocations: Array,
        initialFilters: Object
    },

    data() {
        return {
            categories: this.initialCategories,
            locations: this.initialLocations,
            filters: { ...this.initialFilters },
            showAdvancedFilters: false,
            debounceTimer: null
        }
    },

    methods: {
        applyFilters() {
            this.$emit('filters-changed', this.filters);
        },

        debounceApplyFilters() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.applyFilters();
            }, 500);
        },

        resetFilters() {
            this.filters = {
                category_id: '',
                location_id: '',
                budget_max: '',
                sort_by: 'newest',
                period_start: '',
                period_end: '',
                delivery_required: ''
            };
            this.applyFilters();
        },

        toggleAdvancedFilters() {
            this.showAdvancedFilters = !this.showAdvancedFilters;
        }
    },

    mounted() {
        console.log('RentalRequestSearch component mounted');
    }
}
</script>

<style scoped>
.advanced-filters {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}
</style>
