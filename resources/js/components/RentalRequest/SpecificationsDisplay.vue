<template>
    <div class="specifications-display">
        <div v-if="specifications && specifications.length > 0" class="specs-grid">
            <div v-for="spec in specifications" :key="spec.key" class="spec-item">
                <span class="spec-label">{{ spec.label }}:</span>
                <span class="spec-value">
                    {{ spec.value }}
                    <span v-if="spec.unit" class="spec-unit">{{ spec.unit }}</span>
                </span>
            </div>
        </div>
        <div v-else class="no-specs">
            <i class="fas fa-info-circle me-2"></i>
            <span>Технические параметры не указаны</span>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SpecificationsDisplay',
    props: {
        specifications: {
            type: Array,
            default: () => []
        }
    }
}
</script>

<style scoped>
.specs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
}

.spec-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #0d6efd;
}

.spec-label {
    font-weight: 500;
    color: #495057;
    font-size: 0.875rem;
}

.spec-value {
    font-weight: 600;
    color: #000;
}

.spec-unit {
    font-size: 0.75rem;
    color: #6c757d;
    margin-left: 0.25rem;
}

.no-specs {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    color: #6c757d;
    font-style: italic;
    background: #f8f9fa;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .specs-grid {
        grid-template-columns: 1fr;
    }

    .spec-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>
