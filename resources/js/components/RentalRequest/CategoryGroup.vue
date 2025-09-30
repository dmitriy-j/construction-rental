<template>
    <div class="category-group" :id="`category-${category.category_id}`">
        <div class="category-header" @click="toggleExpanded">
            <div class="header-content">
                <h5 class="category-name">
                    <i class="fas" :class="isExpanded ? 'fa-folder-open' : 'fa-folder'"></i>
                    {{ category.category_name }}
                </h5>
                <div class="category-stats">
                    <span class="stat">{{ category.items_count }} позиций</span>
                    <span class="stat">× {{ category.total_quantity }} ед.</span>
                </div>
            </div>
            <div class="expand-icon">
                <i class="fas" :class="isExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
        </div>

        <div v-if="isExpanded" class="category-items">
            <PositionCard
                v-for="item in category.items"
                :key="item.id"
                :item="item"
                :initially-expanded="category.items.length <= 3"
            />
        </div>
    </div>
</template>

<script>
import PositionCard from './PositionCard.vue';

export default {
    name: 'CategoryGroup',
    components: {
        PositionCard
    },
    props: {
        category: {
            type: Object,
            required: true
        },
        initiallyExpanded: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            isExpanded: this.initiallyExpanded
        }
    },
    methods: {
        toggleExpanded() {
            this.isExpanded = !this.isExpanded;
        }
    }
}
</script>

<style scoped>
.category-group {
    margin-bottom: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    cursor: pointer;
    transition: background 0.3s ease;
}

.category-header:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.category-name {
    margin: 0;
    font-size: 1.1rem;
    color: #495057;
}

.category-stats {
    display: flex;
    gap: 1rem;
}

.stat {
    background: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
}

.expand-icon {
    color: #6c757d;
    transition: transform 0.3s ease;
}

.category-group:not(.collapsed) .expand-icon {
    transform: rotate(180deg);
}

.category-items {
    padding: 1rem;
    background: white;
}

@media (max-width: 768px) {
    .category-header {
        padding: 0.75rem 1rem;
    }

    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .category-stats {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
