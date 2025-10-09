<template>
    <div class="public-category-group">
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
            <div v-for="item in category.items" :key="item.id" class="public-position-item">
                <div class="position-header">
                    <strong>Количество: {{ item.quantity || 1 }}</strong>
                </div>

                <!-- 🔥 ИСПРАВЛЕННОЕ ОТОБРАЖЕНИЕ СПЕЦИФИКАЦИЙ -->
                <div v-if="hasSpecifications(item)"
                     class="specifications mt-2">
                    <div v-for="spec in getDisplaySpecifications(item)"
                         :key="getSpecKey(spec)"
                         class="spec-item small text-muted">
                         <!-- 🔥 ВЫВОДИМ ТОЛЬКО ФОРМАТИРОВАННЫЙ ТЕКСТ -->
                         {{ getSpecDisplayText(spec) }}
                    </div>
                </div>

                <div v-else class="text-muted small">
                    Нет спецификаций
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PublicCategoryGroup',
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
        },

        // 🔥 ПРОВЕРКА НАЛИЧИЯ СПЕЦИФИКАЦИЙ
        hasSpecifications(item) {
            return item.formatted_specs &&
                   Array.isArray(item.formatted_specs) &&
                   item.formatted_specs.length > 0;
        },

        // 🔥 ПОЛУЧЕНИЕ СПЕЦИФИКАЦИЙ ДЛЯ ОТОБРАЖЕНИЯ
        getDisplaySpecifications(item) {
            if (!this.hasSpecifications(item)) return [];

            // Если это массив объектов - используем его
            if (typeof item.formatted_specs[0] === 'object') {
                return item.formatted_specs;
            }

            // Если это массив строк - преобразуем в объекты
            return item.formatted_specs.map(spec => {
                if (typeof spec === 'string') {
                    return { formatted: spec };
                }
                return spec;
            });
        },

        // 🔥 ПОЛУЧЕНИЕ КЛЮЧА ДЛЯ v-for
        getSpecKey(spec) {
            if (typeof spec === 'string') return spec;
            if (spec.key) return spec.key;
            if (spec.formatted) return spec.formatted;
            return JSON.stringify(spec);
        },

        // 🔥 ПОЛУЧЕНИЕ ТЕКСТА ДЛЯ ОТОБРАЖЕНИЯ
        getSpecDisplayText(spec) {
            // Если это строка - возвращаем как есть
            if (typeof spec === 'string') return spec;

            // Если есть formatted - используем его
            if (spec.formatted) return spec.formatted;

            // Если есть label и value - форматируем
            if (spec.label && spec.value !== undefined) {
                const unit = spec.unit ? ` ${spec.unit}` : '';
                return `${spec.label}: ${spec.value}${unit}`;
            }

            // Резервный вариант
            return JSON.stringify(spec);
        }
    }
}
</script>

<style scoped>
.public-category-group {
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

.category-items {
    padding: 1rem;
    background: white;
}

.public-position-item {
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    background: #fdfdfd;
}

.public-position-item:last-child {
    margin-bottom: 0;
}

.position-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.specifications {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.spec-item {
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border-left: 3px solid #0d6efd;
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

    .specifications {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>
