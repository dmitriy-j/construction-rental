<template>
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">Быстрые действия</h6>
        </div>
        <div class="card-body">
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary btn-sm" @click="createSimilar">
                    <i class="fas fa-copy me-2"></i>Создать похожую заявку
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="exportToPDF">
                    <i class="fas fa-download me-2"></i>Экспорт в PDF
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="shareRequest">
                    <i class="fas fa-share-alt me-2"></i>Поделиться заявкой
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'QuickActions',
    props: {
        requestId: {
            type: [String, Number],
            required: true
        }
    },
    methods: {
        createSimilar() {
            window.location.href = `/lessee/rental-requests/create?copy_from=${this.requestId}`;
        },
        exportToPDF() {
            this.$toast.info('Функция экспорта в PDF в разработке');
        },
        shareRequest() {
            if (navigator.share) {
                navigator.share({
                    title: 'Заявка на аренду',
                    text: 'Посмотрите эту заявку на аренду техники',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                this.$toast.success('Ссылка скопирована в буфер обмена');
            }
        }
    }
}
</script>
