<!-- resources/js/components/Lessor/QuickActionCard.vue -->
<template>
    <div
        class="quick-action-card"
        :class="[colorClass, { 'clickable': !disabled && !loading, 'loading': loading }]"
        @click="handleClick"
    >
        <div class="action-icon">
            <i :class="icon" v-if="!loading"></i>
            <div class="loading-spinner" v-else>
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
        <div class="action-content">
            <div class="action-title">{{ title }}</div>
            <div class="action-count" v-if="!loading && count !== null && count !== undefined">
                {{ formattedCount }}
            </div>
            <div class="action-count loading-skeleton" v-else-if="loading">
                &nbsp;
            </div>
        </div>
        <div class="action-badge" v-if="badge && !loading">
            <span class="badge" :class="badgeClass">{{ badge }}</span>
        </div>

        <!-- Индикатор обновления данных -->
        <div class="update-indicator" v-if="showUpdateIndicator">
            <i class="fas fa-sync-alt fa-spin"></i>
        </div>
    </div>
</template>

<script>
export default {
    name: 'QuickActionCard',
    props: {
        title: {
            type: String,
            required: true
        },
        count: {
            type: [Number, String],
            default: null
        },
        icon: {
            type: String,
            default: 'fas fa-cog'
        },
        color: {
            type: String,
            default: 'primary',
            validator: (value) => ['primary', 'success', 'warning', 'danger', 'info', 'secondary'].includes(value)
        },
        badge: {
            type: String,
            default: null
        },
        badgeType: {
            type: String,
            default: 'primary'
        },
        disabled: {
            type: Boolean,
            default: false
        },
        loading: {
            type: Boolean,
            default: false
        },
        showUpdateIndicator: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        colorClass() {
            return `color-${this.color}`;
        },
        badgeClass() {
            return `bg-${this.badgeType}`;
        },
        formattedCount() {
            if (this.count === null || this.count === undefined) return '';
            if (typeof this.count === 'number') {
                if (this.count > 999) return '999+';
                if (this.count > 99) return '99+';
            }
            return this.count.toString();
        }
    },
    methods: {
        handleClick() {
            if (!this.disabled && !this.loading) {
                this.$emit('click');
            }
        }
    }
}
</script>

<style scoped>
.quick-action-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: 8px;
    background: white;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
    min-height: 80px;
    overflow: hidden;
}

.quick-action-card.clickable {
    cursor: pointer;
}

.quick-action-card.clickable:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.quick-action-card.loading {
    opacity: 0.7;
    cursor: wait;
}

.quick-action-card.loading:hover {
    transform: none;
    box-shadow: none;
}

/* Цветовые схемы */
.quick-action-card.color-primary {
    border-left: 4px solid #0d6efd;
}

.quick-action-card.color-primary:hover:not(.loading) {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
}

.quick-action-card.color-success {
    border-left: 4px solid #198754;
}

.quick-action-card.color-success:hover:not(.loading) {
    border-color: #198754;
    background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e8 100%);
}

.quick-action-card.color-warning {
    border-left: 4px solid #ffc107;
}

.quick-action-card.color-warning:hover:not(.loading) {
    border-color: #ffc107;
    background: linear-gradient(135deg, #f8f9fa 0%, #fff3cd 100%);
}

.quick-action-card.color-danger {
    border-left: 4px solid #dc3545;
}

.quick-action-card.color-danger:hover:not(.loading) {
    border-color: #dc3545;
    background: linear-gradient(135deg, #f8f9fa 0%, #f8d7da 100%);
}

.quick-action-card.color-info {
    border-left: 4px solid #0dcaf0;
}

.quick-action-card.color-info:hover:not(.loading) {
    border-color: #0dcaf0;
    background: linear-gradient(135deg, #f8f9fa 0%, #d1ecf1 100%);
}

.quick-action-card.color-secondary {
    border-left: 4px solid #6c757d;
}

.quick-action-card.color-secondary:hover:not(.loading) {
    border-color: #6c757d;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.quick-action-card:disabled,
.quick-action-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.quick-action-card:disabled:hover,
.quick-action-card.disabled:hover {
    transform: none;
    box-shadow: none;
}

/* Иконка действия */
.action-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    margin-right: 1rem;
    flex-shrink: 0;
    position: relative;
}

.color-primary .action-icon {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: white;
}

.color-success .action-icon {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    color: white;
}

.color-warning .action-icon {
    background: linear-gradient(135deg, #ffc107 0%, #ffcd39 100%);
    color: #212529;
}

.color-danger .action-icon {
    background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
    color: white;
}

.color-info .action-icon {
    background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%);
    color: white;
}

.color-secondary .action-icon {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

/* Спиннер загрузки */
.loading-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.loading-spinner i {
    font-size: 1.2rem;
    opacity: 0.8;
}

/* Контент */
.action-content {
    flex: 1;
}

.action-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.action-count {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    transition: all 0.3s ease;
}

.color-primary .action-count {
    color: #0d6efd;
}

.color-success .action-count {
    color: #198754;
}

.color-warning .action-count {
    color: #ffc107;
}

.color-danger .action-count {
    color: #dc3545;
}

.color-info .action-count {
    color: #0dcaf0;
}

.color-secondary .action-count {
    color: #6c757d;
}

/* Скелетон для загрузки */
.loading-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
    min-width: 40px;
    min-height: 24px;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

/* Бейдж */
.action-badge {
    position: absolute;
    top: -8px;
    right: -8px;
}

.action-badge .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Индикатор обновления */
.update-indicator {
    position: absolute;
    top: 5px;
    right: 5px;
    color: #6c757d;
    font-size: 0.8rem;
    opacity: 0.7;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        opacity: 0.7;
    }
    50% {
        opacity: 1;
    }
    100% {
        opacity: 0.7;
    }
}

/* Анимация появления счетчика */
.action-count {
    animation: countAppear 0.5s ease-out;
}

@keyframes countAppear {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Адаптивность */
@media (max-width: 768px) {
    .quick-action-card {
        padding: 0.75rem;
        min-height: 70px;
    }

    .action-icon {
        width: 40px;
        height: 40px;
        margin-right: 0.75rem;
    }

    .action-title {
        font-size: 0.8rem;
    }

    .action-count {
        font-size: 1.25rem;
    }

    .loading-skeleton {
        min-width: 30px;
        min-height: 20px;
    }
}

@media (max-width: 576px) {
    .quick-action-card {
        padding: 0.5rem;
        min-height: 60px;
    }

    .action-icon {
        width: 35px;
        height: 35px;
        margin-right: 0.5rem;
    }

    .action-title {
        font-size: 0.75rem;
    }

    .action-count {
        font-size: 1.1rem;
    }
}
</style>
