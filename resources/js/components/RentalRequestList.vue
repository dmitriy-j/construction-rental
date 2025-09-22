<template>
    <div class="rental-request-list">
        <div class="row" v-if="loading">
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2 text-muted">Загрузка заявок...</p>
                </div>
            </div>
        </div>

        <div class="row" v-else-if="requests.length === 0">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Заявки не найдены</h5>
                    <p class="text-muted">Попробуйте изменить параметры поиска</p>
                    <button @click="$emit('reset-filters')" class="btn btn-outline-primary">
                        Сбросить фильтры
                    </button>
                </div>
            </div>
        </div>

        <div class="row" v-else>
            <div class="col-12">
                <div class="row g-4">
                    <div class="col-xl-4 col-lg-6" v-for="request in requests" :key="request.id">
                        <div class="card rental-request-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <small class="text-muted">#{{ request.id }}</small>
                                <span class="badge" :class="`bg-${request.status_color}`">
                                    {{ request.status_text }}
                                </span>
                            </div>

                            <div class="card-body">
                                <h6 class="card-title">
                                    <a :href="request.view_url" class="text-decoration-none">
                                        {{ request.title }}
                                    </a>
                                </h6>

                                <p class="card-text text-muted small">
                                    {{ request.description_short }}
                                </p>

                                <div class="request-meta">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small>
                                            <i class="fas fa-tag me-1"></i>
                                            {{ request.category.name }}
                                        </small>
                                        <small>
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            {{ request.location.name }}
                                        </small>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <small>
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ formatDate(request.rental_period_start) }} -
                                            {{ formatDate(request.rental_period_end) }}
                                        </small>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="text-primary">
                                            {{ formatCurrency(request.budget_from) }} -
                                            {{ formatCurrency(request.budget_to) }}
                                        </strong>
                                        <span class="badge bg-primary rounded-pill">
                                            <i class="fas fa-proposal me-1"></i>
                                            {{ request.responses_count }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        {{ formatDateTime(request.created_at) }}
                                    </small>
                                    <div class="btn-group">
                                        <a :href="request.view_url" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button v-if="canRespond && request.status === 'active'"
                                                @click="$emit('respond', request)"
                                                class="btn btn-sm btn-success">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Пагинация -->
                <div class="row mt-4" v-if="pagination.last_page > 1">
                    <div class="col-12">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                    <a class="page-link" href="#" @click.prevent="changePage(1)">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                    <a class="page-link" href="#"
                                       @click.prevent="changePage(pagination.current_page - 1)">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>

                                <li v-for="page in displayedPages" :key="page"
                                    class="page-item" :class="{ active: page === pagination.current_page }">
                                    <a class="page-link" href="#" @click.prevent="changePage(page)">
                                        {{ page }}
                                    </a>
                                </li>

                                <li class="page-item"
                                    :class="{ disabled: pagination.current_page === pagination.last_page }">
                                    <a class="page-link" href="#"
                                       @click.prevent="changePage(pagination.current_page + 1)">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item"
                                    :class="{ disabled: pagination.current_page === pagination.last_page }">
                                    <a class="page-link" href="#"
                                       @click.prevent="changePage(pagination.last_page)">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RentalRequestList',

    props: {
        requests: Array,
        pagination: Object,
        loading: Boolean,
        canRespond: {
            type: Boolean,
            default: false
        }
    },

    computed: {
        displayedPages() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const delta = 2;
            const range = [];

            for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
                range.push(i);
            }

            if (current - delta > 2) {
                range.unshift('...');
            }
            if (current + delta < last - 1) {
                range.push('...');
            }

            range.unshift(1);
            if (last > 1) {
                range.push(last);
            }

            return range;
        }
    },

    methods: {
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU');
        },

        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('ru-RU');
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('ru-RU').format(amount) + ' ₽';
        },

        changePage(page) {
            if (page !== '...' && page >= 1 && page <= this.pagination.last_page) {
                this.$emit('page-changed', page);
            }
        }
    }
}
</script>

<style scoped>
.rental-request-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.rental-request-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.page-link {
    color: #0d6efd;
}
</style>
