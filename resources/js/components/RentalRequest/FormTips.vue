<template>
    <div class="form-tips" v-if="showTips">
        <div class="tip-container">
            <!-- Контекстные подсказки в зависимости от активного поля -->
            <div v-if="activeField === 'title'" class="tip active">
                <i class="fas fa-lightbulb"></i>
                <strong>Совет:</strong> Используйте конкретные названия: "Экскаватор-погрузчик для земляных работ"
            </div>

            <div v-if="activeField === 'description'" class="tip active">
                <i class="fas fa-lightbulb"></i>
                <strong>Совет:</strong> Опишите объем работ: "Котлован 10×15м, глубина 2м, грунт - суглинок"
            </div>

            <div v-if="activeField === 'hourly_rate'" class="tip active">
                <i class="fas fa-lightbulb"></i>
                <strong>Совет:</strong> Средние цены: экскаватор 1,500-2,500₽/час, кран 3,000-5,000₽/час
            </div>

            <!-- Общие подсказки -->
            <div class="tip general" v-if="!activeField">
                <i class="fas fa-info-circle"></i>
                Заполняйте поля последовательно для точного расчета бюджета
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FormTips',
    props: {
        activeField: String
    },
    data() {
        return {
            showTips: true
        }
    },
    methods: {
        toggleTips() {
            this.showTips = !this.showTips;
        }
    }
}
</script>

<style scoped>
.form-tips {
    margin-bottom: 1rem;
}

.tip-container {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #2196f3;
    padding: 1rem;
    border-radius: 8px;
}

.tip {
    font-size: 0.9rem;
    color: #1565c0;
}

.tip i {
    margin-right: 0.5rem;
}

.tip.active {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.general {
    font-style: italic;
}
</style>
