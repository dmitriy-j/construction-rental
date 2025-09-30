<!-- resources/js/components/Public/PublicProposalModal.vue -->
<template>
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2 text-primary"></i>
                        Предложить технику
                    </h5>
                    <button type="button" class="btn-close" @click="$emit('close')"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Функционал предложений находится в разработке
                    </div>

                    <div class="request-info mb-4 p-3 bg-light rounded">
                        <h6>Заявка: {{ request?.title }}</h6>
                        <p class="mb-1 text-muted">{{ request?.description }}</p>
                        <small class="text-muted">
                            Период: {{ request?.rental_period_display }}
                            ({{ request?.rental_days }} дней)
                        </small>
                    </div>

                    <div class="proposal-form">
                        <p>Здесь будет форма выбора оборудования и расчета цены с учетом комбинированной наценки:</p>
                        <ul class="text-muted">
                            <li>Выбор техники из вашего каталога</li>
                            <li>Расчет комбинированной наценки платформы</li>
                            <li>Резервирование техники на 24 часа</li>
                            <li>Уведомление арендатора</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$emit('close')">
                        <i class="fas fa-times me-2"></i>Закрыть
                    </button>
                    <button type="button" class="btn btn-primary" disabled>
                        <i class="fas fa-paper-plane me-2"></i>Отправить предложение
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PublicProposalModal',
    props: {
        show: Boolean,
        request: Object
    },
    emits: ['close', 'proposal-created']
}
</script>

<style scoped>
.modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 12px 12px 0 0;
}

.request-info {
    border-left: 4px solid #0d6efd;
}

.proposal-form ul {
    padding-left: 1.5rem;
}

.proposal-form li {
    margin-bottom: 0.5rem;
}
</style>
