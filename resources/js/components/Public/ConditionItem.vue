<!-- resources/js/components/Public/ConditionItem.vue -->
<template>
    <div class="condition-item">
        <div class="condition-icon">
            <i class="fas" :class="condition.icon"></i>
        </div>
        <div class="condition-content">
            <div class="condition-label">{{ condition.label }}</div>
            <div class="condition-value">{{ condition.value }}</div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ConditionItem',
    props: {
        condition: {
            type: Object,
            required: true
        }
    }
}
</script>

<style scoped>
.condition-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
    border-left: 4px solid #0d6efd;
}

.condition-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-left-color: #495057;
}

.condition-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: white;
    border-radius: 8px;
    flex-shrink: 0;
}

.condition-content {
    flex: 1;
}

.condition-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.condition-value {
    color: #28a745;
    font-weight: 600;
    font-size: 1rem;
}

@media (max-width: 576px) {
    .condition-item {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }

    .condition-icon {
        align-self: center;
    }
}
</style>
