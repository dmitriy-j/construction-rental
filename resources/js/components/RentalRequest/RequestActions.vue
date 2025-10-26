<template>
    <div class="request-actions">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Действия с заявкой</h6>
            </div>
            <div class="card-body">
                <!-- Для активной заявки -->
                <div v-if="request.status === 'active'" class="d-grid gap-2">
                    <button class="btn btn-warning btn-sm" @click="$emit('pause-request')">
                        <i class="fas fa-pause me-2"></i>Приостановить заявку
                    </button>
                    <button class="btn btn-outline-danger btn-sm" @click="$emit('cancel-request')">
                        <i class="fas fa-times me-2"></i>Отменить заявку
                    </button>
                    <a :href="`/lessee/rental-requests/${request.id}/edit`" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>Редактировать
                    </a>
                </div>

                <!-- ДОБАВЛЯЕМ: Для приостановленной заявки (paused) -->
                <div v-else-if="request.status === 'paused'" class="d-grid gap-2">
                    <button class="btn btn-success btn-sm" @click="$emit('resume-request')">
                        <i class="fas fa-play me-2"></i>Возобновить заявку
                    </button>
                    <button class="btn btn-outline-danger btn-sm" @click="$emit('cancel-request')">
                        <i class="fas fa-times me-2"></i>Отменить заявку
                    </button>
                    <a :href="`/lessee/rental-requests/${request.id}/edit`" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>Редактировать
                    </a>
                </div>

                <!-- Для приостановленной заявки (processing) - на всякий случай оставим -->
                <div v-else-if="request.status === 'processing'" class="d-grid gap-2">
                    <button class="btn btn-success btn-sm" @click="$emit('resume-request')">
                        <i class="fas fa-play me-2"></i>Возобновить заявку
                    </button>
                    <button class="btn btn-outline-danger btn-sm" @click="$emit('cancel-request')">
                        <i class="fas fa-times me-2"></i>Отменить заявку
                    </button>
                    <a :href="`/lessee/rental-requests/${request.id}/edit`" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>Редактировать
                    </a>
                </div>

                <!-- Для завершенной заявки -->
                <div v-else-if="request.status === 'completed'" class="alert alert-info">
                    <i class="fas fa-flag-checkered me-2"></i>
                    Заявка успешно завершена.
                </div>

                <!-- Для отмененной заявки -->
                <div v-else-if="request.status === 'cancelled'" class="alert alert-warning">
                    <i class="fas fa-ban me-2"></i>
                    Заявка отменена.
                </div>

                <!-- Для черновика -->
                <div v-else-if="request.status === 'draft'" class="d-grid gap-2">
                    <a :href="`/lessee/rental-requests/${request.id}/edit`" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit me-2"></i>Продолжить редактирование
                    </a>
                    <button class="btn btn-outline-danger btn-sm" @click="$emit('cancel-request')">
                        <i class="fas fa-times me-2"></i>Удалить черновик
                    </button>
                </div>

                <!-- Запасной вариант для неизвестных статусов -->
                <div v-else class="alert alert-secondary">
                    <i class="fas fa-question-circle me-2"></i>
                    Статус заявки: {{ request.status }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RequestActions',
    props: {
        request: {
            type: Object,
            required: true
        }
    },
    emits: ['pause-request', 'resume-request', 'cancel-request', 'edit-request']
}
</script>

<style scoped>
.request-actions .card {
    border-left: 4px solid #0d6efd;
}

.request-actions .btn {
    font-size: 0.875rem;
}
</style>
