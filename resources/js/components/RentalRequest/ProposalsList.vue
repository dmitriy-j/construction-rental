<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-handshake me-2"></i>
                Предложения от арендодателей
                <span v-if="proposals" class="badge bg-primary ms-2">{{ proposals.length }}</span>
            </h5>
            <div class="dropdown" v-if="proposals && proposals.length > 0">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                        type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-sort me-1"></i>Сортировка
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" @click.prevent="sortProposals('price')">По цене</a></li>
                    <li><a class="dropdown-item" href="#" @click.prevent="sortProposals('rating')">По рейтингу</a></li>
                    <li><a class="dropdown-item" href="#" @click.prevent="sortProposals('date')">По дате</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div v-if="!proposals" class="text-center py-3">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <p class="mt-2 text-muted">Загрузка предложений...</p>
            </div>

            <div v-else-if="proposals.length === 0" class="text-center py-4">
                <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                <h5>Пока нет предложений</h5>
                <p class="text-muted">Арендодатели увидят вашу заявку и скоро предложат свои варианты</p>
            </div>

            <div v-else class="proposals-list">
                <div v-for="proposal in proposals" :key="proposal.id" class="proposal-card mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        {{ proposal.lessor?.company?.legal_name || 'Компания' }}
                                        <span v-if="proposal.lessor?.company?.average_rating"
                                              class="badge bg-warning ms-2">
                                            <i class="fas fa-star me-1"></i>
                                            {{ proposal.lessor.company.average_rating.toFixed(1) }}
                                        </span>
                                    </h6>
                                    <p class="text-muted small mb-1">
                                        Оборудование: {{ proposal.equipment?.title || 'Не указано' }}
                                    </p>
                                    <p v-if="proposal.message" class="mb-2">{{ proposal.message }}</p>
                                    <div class="proposal-details small text-muted">
                                        <span class="me-3">
                                            <i class="fas fa-cube me-1"></i>
                                            Предложено: {{ proposal.proposed_quantity }} ед.
                                        </span>
                                        <span>
                                            <i class="fas fa-clock me-1"></i>
                                            {{ formatDate(proposal.created_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-end">
                                <div class="proposal-price mb-2">
                                    <span class="h5 text-primary">
                                        {{ formatCurrency(proposal.proposed_price) }}
                                    </span>
                                    <div class="small text-muted">за весь период</div>
                                </div>
                                <div class="proposal-actions">
                                    <button v-if="proposal.status === 'pending'"
                                            class="btn btn-sm btn-success me-1"
                                            @click="$emit('proposal-accepted', proposal.id)">
                                        <i class="fas fa-check me-1"></i>Принять
                                    </button>
                                    <button v-if="proposal.status === 'pending'"
                                            class="btn btn-sm btn-outline-danger"
                                            @click="$emit('proposal-rejected', proposal.id)">
                                        Отклонить
                                    </button>
                                    <span v-else class="badge"
                                          :class="proposal.status === 'accepted' ? 'bg-success' : 'bg-secondary'">
                                        {{ proposal.status === 'accepted' ? 'Принято' : 'Отклонено' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProposalsList',
    props: {
        proposals: {
            type: Array,
            default: () => []
        },
        requestId: {
            type: [String, Number],
            required: true
        }
    },
    methods: {
        sortProposals(criteria) {
            if (!this.proposals) return;

            const sorted = [...this.proposals].sort((a, b) => {
                switch (criteria) {
                    case 'price':
                        return a.proposed_price - b.proposed_price;
                    case 'rating':
                        const ratingA = a.lessor?.company?.average_rating || 0;
                        const ratingB = b.lessor?.company?.average_rating || 0;
                        return ratingB - ratingA;
                    case 'date':
                        return new Date(b.created_at) - new Date(a.created_at);
                    default:
                        return 0;
                }
            });

            // Emit sorted array to parent if needed
            this.$emit('proposals-sorted', sorted);
        },

        formatCurrency(amount) {
            if (!amount) return '0 ₽';
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    },
    emits: ['proposal-accepted', 'proposal-rejected', 'proposals-sorted']
}
</script>

<style scoped>
.proposal-card {
    transition: transform 0.2s ease;
    border-left: 4px solid #198754;
}

.proposal-card:hover {
    transform: translateX(5px);
}
</style>
