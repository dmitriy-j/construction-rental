<template>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="fas fa-chart-bar me-2"></i>Статистика заявки
            </h6>
            <span class="badge" :class="`bg-${getStatusColor(request.status)}`">
                {{ getStatusText(request.status) }}
            </span>
        </div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">{{ request.views_count || 0 }}</div>
                    <div class="stat-label">
                        <i class="fas fa-eye me-1"></i>Просмотров
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ request.responses_count || 0 }}</div>
                    <div class="stat-label">
                        <i class="fas fa-handshake me-1"></i>Предложений
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ request.items_count || 0 }}</div>
                    <div class="stat-label">
                        <i class="fas fa-cube me-1"></i>Позиций
                    </div>
                </div>
                <div class="stat-item" v-if="request.calculated_budget_from || request.budget_from">
                    <div class="stat-value">{{ formatCurrency(request.calculated_budget_from || request.budget_from) }}</div>
                    <div class="stat-label">
                        <i class="fas fa-ruble-sign me-1"></i>Бюджет
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ formatDate(request.rental_period_start) }}</div>
                    <div class="stat-label">
                        <i class="fas fa-calendar-start me-1"></i>Начало
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ formatDate(request.rental_period_end) }}</div>
                    <div class="stat-label">
                        <i class="fas fa-calendar-end me-1"></i>Окончание
                    </div>
                </div>
            </div>

            <!-- Прогресс предложений -->
            <div class="progress-section mt-3" v-if="request.items_count > 0">
                <div class="d-flex justify-content-between mb-2">
                    <small class="text-muted">Заполнение предложений</small>
                    <small class="text-muted">{{ request.responses_count || 0 }}/{{ request.items_count }}</small>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar"
                         :style="`width: ${getProposalProgress(request)}%`"
                         :title="`${request.responses_count} предложений из ${request.items_count} позиций`">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RequestStats',
    props: {
        request: {
            type: Object,
            required: true
        }
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return '—';
            try {
                return new Date(dateString).toLocaleDateString('ru-RU');
            } catch (error) {
                return '—';
            }
        },

        formatCurrency(amount) {
            if (!amount && amount !== 0) return '—';
            try {
                return new Intl.NumberFormat('ru-RU').format(amount);
            } catch (error) {
                return '—';
            }
        },

        getStatusColor(status) {
            const colors = {
                'active': 'success',
                'processing': 'warning',
                'completed': 'primary',
                'cancelled': 'danger',
                'draft': 'secondary'
            };
            return colors[status] || 'light';
        },

        getStatusText(status) {
            const texts = {
                'active': 'Активна',
                'processing': 'В процессе',
                'completed': 'Завершена',
                'cancelled': 'Отменена',
                'draft': 'Черновик'
            };
            return texts[status] || status;
        },

        getProposalProgress(request) {
            if (!request.responses_count || !request.items_count) return 0;
            return Math.min(100, (request.responses_count / Math.max(1, request.items_count)) * 100);
        }
    }
}
</script>

<style scoped>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    text-align: center;
}

.stat-value {
    font-size: 1.3rem;
    font-weight: bold;
    color: #0d6efd;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-section {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }

    .stat-value {
        font-size: 1.1rem;
    }

    .stat-label {
        font-size: 0.7rem;
    }
}
</style>
