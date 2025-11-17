<template>
    <div class="markup-tester">
        <!-- Заголовок и статистика -->
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-calculator me-2"></i>
                    Интерактивный тестер наценок
                </h5>
                <div class="statistics">
                    <span class="badge bg-primary me-2">Тестов: {{ stats.totalTests }}</span>
                    <span class="badge bg-success me-2">Успешно: {{ stats.successfulTests }}</span>
                    <span class="badge bg-warning">Среднее время: {{ stats.averageTime }}мс</span>
                </div>
            </div>

            <div class="card-body">
                <!-- Параметры тестирования -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Базовая цена (₽/час) *</label>
                        <input
                            type="number"
                            class="form-control"
                            v-model="testParams.basePrice"
                            min="0"
                            step="0.01"
                            placeholder="1000"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Рабочие часы *</label>
                        <input
                            type="number"
                            class="form-control"
                            v-model="testParams.workingHours"
                            min="1"
                            placeholder="8"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Контекст *</label>
                        <select class="form-select" v-model="testParams.entityType">
                            <option value="order">Заказы</option>
                            <option value="rental_request">Заявки на аренду</option>
                            <option value="proposal">Предложения</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Кол-во тестов</label>
                        <input
                            type="number"
                            class="form-control"
                            v-model="testParams.testCount"
                            min="1"
                            max="100"
                            placeholder="5"
                        >
                    </div>
                </div>

                <!-- Контекст применения -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">ID оборудования</label>
                        <input
                            type="number"
                            class="form-control"
                            v-model="testParams.equipmentId"
                            placeholder="123"
                        >
                        <div class="form-text">Для тестирования наценок на оборудование</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ID категории</label>
                        <input
                            type="number"
                            class="form-control"
                            v-model="testParams.categoryId"
                            placeholder="45"
                        >
                        <div class="form-text">Для тестирования наценок на категории</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ID компании</label>
                        <input
                            type="number"
                            class="form-control"
                            v-model="testParams.companyId"
                            placeholder="67"
                        >
                        <div class="form-text">Для тестирования наценок на компании</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">ID компании арендатора</label>
                        <input
                            type="number"
                            class="form-control"
                            v-model="testParams.lesseeCompanyId"
                            placeholder="89"
                        >
                        <div class="form-text">Для компаний-арендаторов</div>
                    </div>
                </div>

                <!-- Кнопки управления -->
                <div class="d-flex gap-2 mb-4">
                    <button
                        class="btn btn-primary"
                        @click="runSingleTest"
                        :disabled="isTesting"
                    >
                        <i class="bi bi-play-circle me-1" v-if="!isTesting"></i>
                        <span class="spinner-border spinner-border-sm me-1" v-else></span>
                        {{ isTesting ? 'Тестирование...' : 'Запустить тест' }}
                    </button>

                    <button
                        class="btn btn-outline-secondary"
                        @click="runBatchTests"
                        :disabled="isTesting"
                    >
                        <i class="bi bi-collection-play me-1"></i>
                        Пакетное тестирование
                    </button>

                    <button
                        class="btn btn-outline-info"
                        @click="loadTestScenarios"
                        :disabled="isTesting"
                    >
                        <i class="bi bi-cloud-download me-1"></i>
                        Загрузить сценарии
                    </button>

                    <button
                        class="btn btn-outline-danger"
                        @click="clearResults"
                    >
                        <i class="bi bi-trash me-1"></i>
                        Очистить результаты
                    </button>
                </div>

                <!-- Быстрые сценарии -->
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Быстрые сценарии:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button
                            v-for="scenario in quickScenarios"
                            :key="scenario.name"
                            class="btn btn-sm btn-outline-primary"
                            @click="applyScenario(scenario)"
                        >
                            {{ scenario.name }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Результаты тестирования -->
        <div class="row mt-4">
            <!-- Детали расчета -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Детали расчета</h6>
                    </div>
                    <div class="card-body">
                        <div v-if="currentResult" class="calculation-details">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold">Базовая цена:</td>
                                            <td class="text-end">{{ formatCurrency(currentResult.base_price) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Тип наценки:</td>
                                            <td class="text-end">
                                                <span class="badge" :class="getMarkupTypeBadge(currentResult.markup_type)">
                                                    {{ getMarkupTypeLabel(currentResult.markup_type) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Значение наценки:</td>
                                            <td class="text-end">{{ currentResult.markup_value }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Сумма наценки:</td>
                                            <td class="text-end fw-bold text-primary">
                                                {{ formatCurrency(currentResult.markup_amount) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Итоговая цена:</td>
                                            <td class="text-end fw-bold text-success">
                                                {{ formatCurrency(currentResult.final_price) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Рабочие часы:</td>
                                            <td class="text-end">{{ currentResult.working_hours }}</td>
                                        </tr>
                                        <tr>
                                            <td>Источник:</td>
                                            <td class="text-end">
                                                <small class="text-muted">{{ currentResult.calculation_details.source }}</small>
                                            </td>
                                        </tr>
                                        <tr v-if="currentResult.calculation_details.priority">
                                            <td>Приоритет:</td>
                                            <td class="text-end">{{ currentResult.calculation_details.priority }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Визуализация расчета -->
                            <div class="mt-3">
                                <h6 class="text-muted mb-2">Визуализация:</h6>
                                <div class="calculation-visualization">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Базовая цена:</span>
                                        <span class="fw-bold">{{ formatCurrency(currentResult.base_price) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Наценка ({{ getMarkupTypeLabel(currentResult.markup_type) }}):</span>
                                        <span class="fw-bold text-primary">+ {{ formatCurrency(currentResult.markup_amount) }}</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Итоговая цена:</span>
                                        <span class="fw-bold text-success">{{ formatCurrency(currentResult.final_price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center text-muted py-4">
                            <i class="bi bi-calculator display-4"></i>
                            <p class="mt-2">Запустите тест для просмотра результатов</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- История тестов -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">История тестов</h6>
                        <span class="badge bg-secondary">{{ testHistory.length }}</span>
                    </div>
                    <div class="card-body">
                        <div v-if="testHistory.length > 0" class="test-history">
                            <div
                                v-for="(test, index) in testHistory.slice().reverse()"
                                :key="test.id"
                                class="test-item border-bottom pb-2 mb-2 cursor-pointer"
                                :class="{ 'bg-light': currentResult && currentResult.id === test.id }"
                                @click="showTestDetails(test)"
                            >
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold">
                                            {{ formatCurrency(test.base_price) }} × {{ test.working_hours }}ч
                                        </div>
                                        <div class="small text-muted">
                                            {{ getMarkupTypeLabel(test.markup_type) }}: {{ test.markup_value }}
                                            <span class="badge bg-info ms-1">{{ test.calculation_details.source }}</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success">
                                            {{ formatCurrency(test.final_price) }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ formatTime(test.timestamp) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center text-muted py-4">
                            <i class="bi bi-clock-history display-4"></i>
                            <p class="mt-2">История тестов пуста</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Графики производительности -->
        <div class="row mt-4" v-if="performanceData.length > 0">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Производительность расчетов</h6>
                    </div>
                    <div class="card-body">
                        <canvas ref="performanceChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Сравнение сценариев -->
        <div class="row mt-4" v-if="batchResults.length > 0">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Сравнение сценариев</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Сценарий</th>
                                        <th>Базовая цена</th>
                                        <th>Наценка</th>
                                        <th>Тип</th>
                                        <th>Источник</th>
                                        <th>Итоговая цена</th>
                                        <th>Время (мс)</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="result in batchResults" :key="result.id">
                                        <td class="fw-bold">{{ result.scenarioName }}</td>
                                        <td>{{ formatCurrency(result.base_price) }}</td>
                                        <td>
                                            <span class="badge" :class="getMarkupTypeBadge(result.markup_type)">
                                                {{ result.markup_value }}
                                            </span>
                                        </td>
                                        <td>{{ getMarkupTypeLabel(result.markup_type) }}</td>
                                        <td>
                                            <small class="text-muted">{{ result.calculation_details.source }}</small>
                                        </td>
                                        <td class="fw-bold text-success">
                                            {{ formatCurrency(result.final_price) }}
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ result.executionTime }}</span>
                                        </td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-outline-primary"
                                                @click="loadResultToForm(result)"
                                            >
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MarkupTester',

    data() {
        return {
            testParams: {
                basePrice: 1000,
                workingHours: 8,
                entityType: 'order',
                equipmentId: null,
                categoryId: null,
                companyId: null,
                lesseeCompanyId: null,
                testCount: 5
            },

            currentResult: null,
            testHistory: [],
            batchResults: [],
            performanceData: [],
            isTesting: false,

            stats: {
                totalTests: 0,
                successfulTests: 0,
                averageTime: 0
            },

            quickScenarios: [
                {
                    name: 'Стандартный заказ',
                    basePrice: 1500,
                    workingHours: 8,
                    entityType: 'order'
                },
                {
                    name: 'Заявка на аренду',
                    basePrice: 1200,
                    workingHours: 24,
                    entityType: 'rental_request'
                },
                {
                    name: 'Долгосрочная аренда',
                    basePrice: 800,
                    workingHours: 160,
                    entityType: 'order'
                },
                {
                    name: 'Премиум оборудование',
                    basePrice: 3000,
                    workingHours: 4,
                    entityType: 'proposal'
                }
            ]
        };
    },

    mounted() {
        this.loadTestHistory();
        this.initializeCharts();
    },

    methods: {
        async runSingleTest() {
            this.isTesting = true;

            try {
                const startTime = performance.now();

                const response = await axios.post('/admin/markups/test-calculation', {
                    base_price: this.testParams.basePrice,
                    working_hours: this.testParams.workingHours,
                    entity_type: this.testParams.entityType,
                    equipment_id: this.testParams.equipmentId || null,
                    category_id: this.testParams.categoryId || null,
                    company_id: this.testParams.companyId || null,
                    lessee_company_id: this.testParams.lesseeCompanyId || null
                });

                const endTime = performance.now();
                const executionTime = Math.round(endTime - startTime);

                if (response.data.success) {
                    const result = {
                        ...response.data.result,
                        id: Date.now() + Math.random(),
                        timestamp: new Date(),
                        executionTime: executionTime,
                        params: { ...this.testParams }
                    };

                    this.currentResult = result;
                    this.addToTestHistory(result);
                    this.updateStats(result);
                    this.addPerformanceData(executionTime);

                    this.$swal.fire({
                        icon: 'success',
                        title: 'Тест выполнен успешно!',
                        text: `Расчет завершен за ${executionTime}мс`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(response.data.message);
                }
            } catch (error) {
                console.error('Test error:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка расчета',
                    text: error.response?.data?.message || error.message,
                    confirmButtonText: 'OK'
                });
            } finally {
                this.isTesting = false;
            }
        },

        async runBatchTests() {
            this.isTesting = true;
            this.batchResults = [];

            try {
                const tests = [];

                for (let i = 0; i < this.testParams.testCount; i++) {
                    // Генерируем различные параметры для тестирования
                    const testParams = {
                        basePrice: this.testParams.basePrice * (0.8 + Math.random() * 0.4), // ±20%
                        workingHours: Math.max(1, Math.round(this.testParams.workingHours * (0.5 + Math.random()))),
                        entityType: this.testParams.entityType,
                        equipmentId: this.testParams.equipmentId,
                        categoryId: this.testParams.categoryId,
                        companyId: this.testParams.companyId,
                        lesseeCompanyId: this.testParams.lesseeCompanyId
                    };

                    tests.push(testParams);
                }

                const results = [];

                for (const [index, test] of tests.entries()) {
                    const startTime = performance.now();

                    const response = await axios.post('/admin/markups/test-calculation', {
                        base_price: test.basePrice,
                        working_hours: test.workingHours,
                        entity_type: test.entityType,
                        equipment_id: test.equipmentId || null,
                        category_id: test.categoryId || null,
                        company_id: test.companyId || null,
                        lessee_company_id: test.lesseeCompanyId || null
                    });

                    const endTime = performance.now();
                    const executionTime = Math.round(endTime - startTime);

                    if (response.data.success) {
                        results.push({
                            ...response.data.result,
                            id: Date.now() + index,
                            timestamp: new Date(),
                            executionTime: executionTime,
                            scenarioName: `Тест ${index + 1}`,
                            params: test
                        });
                    }

                    // Небольшая задержка между запросами
                    await new Promise(resolve => setTimeout(resolve, 100));
                }

                this.batchResults = results;

                this.$swal.fire({
                    icon: 'success',
                    title: 'Пакетное тестирование завершено!',
                    text: `Выполнено ${results.length} тестов`,
                    confirmButtonText: 'OK'
                });

            } catch (error) {
                console.error('Batch test error:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка пакетного тестирования',
                    text: error.message,
                    confirmButtonText: 'OK'
                });
            } finally {
                this.isTesting = false;
            }
        },

        addToTestHistory(result) {
            this.testHistory.push(result);

            // Сохраняем в localStorage
            const history = this.testHistory.slice(-20); // Последние 20 тестов
            localStorage.setItem('markupTestHistory', JSON.stringify(history));
        },

        loadTestHistory() {
            try {
                const saved = localStorage.getItem('markupTestHistory');
                if (saved) {
                    this.testHistory = JSON.parse(saved);
                    this.stats.totalTests = this.testHistory.length;
                    this.stats.successfulTests = this.testHistory.length;

                    if (this.testHistory.length > 0) {
                        const times = this.testHistory.map(t => t.executionTime || 0);
                        this.stats.averageTime = Math.round(times.reduce((a, b) => a + b, 0) / times.length);
                    }
                }
            } catch (error) {
                console.error('Error loading test history:', error);
            }
        },

        updateStats(result) {
            this.stats.totalTests++;
            this.stats.successfulTests++;

            // Обновляем среднее время
            const times = this.testHistory.map(t => t.executionTime || 0);
            this.stats.averageTime = Math.round(times.reduce((a, b) => a + b, 0) / times.length);
        },

        addPerformanceData(executionTime) {
            this.performanceData.push({
                time: new Date(),
                executionTime: executionTime
            });

            // Ограничиваем количество точек данных
            if (this.performanceData.length > 50) {
                this.performanceData = this.performanceData.slice(-50);
            }

            this.updatePerformanceChart();
        },

        initializeCharts() {
            if (this.$refs.performanceChart) {
                this.performanceChart = new Chart(this.$refs.performanceChart, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Время выполнения (мс)',
                            data: [],
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Время (мс)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Тесты'
                                }
                            }
                        }
                    }
                });
            }
        },

        updatePerformanceChart() {
            if (this.performanceChart && this.performanceData.length > 0) {
                const labels = this.performanceData.map((_, index) => `Тест ${index + 1}`);
                const data = this.performanceData.map(d => d.executionTime);

                this.performanceChart.data.labels = labels;
                this.performanceChart.data.datasets[0].data = data;
                this.performanceChart.update();
            }
        },

        applyScenario(scenario) {
            this.testParams.basePrice = scenario.basePrice;
            this.testParams.workingHours = scenario.workingHours;
            this.testParams.entityType = scenario.entityType;

            this.$swal.fire({
                icon: 'success',
                title: 'Сценарий применен',
                text: `"${scenario.name}" загружен`,
                timer: 1500,
                showConfirmButton: false
            });
        },

        showTestDetails(test) {
            this.currentResult = test;
        },

        loadResultToForm(result) {
            this.testParams.basePrice = result.params.basePrice;
            this.testParams.workingHours = result.params.workingHours;
            this.testParams.entityType = result.params.entityType;
            this.testParams.equipmentId = result.params.equipmentId;
            this.testParams.categoryId = result.params.categoryId;
            this.testParams.companyId = result.params.companyId;
            this.testParams.lesseeCompanyId = result.params.lesseeCompanyId;
        },

        clearResults() {
            this.currentResult = null;
            this.testHistory = [];
            this.batchResults = [];
            this.performanceData = [];
            this.stats = { totalTests: 0, successfulTests: 0, averageTime: 0 };

            localStorage.removeItem('markupTestHistory');

            this.$swal.fire({
                icon: 'success',
                title: 'Результаты очищены',
                timer: 1500,
                showConfirmButton: false
            });
        },

        async loadTestScenarios() {
            // Загрузка предопределенных сценариев с сервера
            try {
                const response = await axios.get('/admin/markups/test-scenarios');
                if (response.data.success) {
                    this.quickScenarios = response.data.scenarios;
                    this.$swal.fire({
                        icon: 'success',
                        title: 'Сценарии загружены',
                        text: `Загружено ${response.data.scenarios.length} сценариев`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            } catch (error) {
                console.error('Error loading scenarios:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка загрузки',
                    text: 'Не удалось загрузить сценарии тестирования'
                });
            }
        },

        // Вспомогательные методы
        formatCurrency(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 2
            }).format(amount);
        },

        formatTime(date) {
            return new Date(date).toLocaleTimeString('ru-RU');
        },

        getMarkupTypeLabel(type) {
            const labels = {
                'fixed': 'Фиксированная',
                'percent': 'Процентная',
                'tiered': 'Ступенчатая',
                'combined': 'Комбинированная',
                'seasonal': 'Сезонная'
            };
            return labels[type] || type;
        },

        getMarkupTypeBadge(type) {
            const badges = {
                'fixed': 'bg-primary',
                'percent': 'bg-success',
                'tiered': 'bg-warning text-dark',
                'combined': 'bg-info',
                'seasonal': 'bg-secondary'
            };
            return badges[type] || 'bg-light text-dark';
        }
    }
};
</script>

<style scoped>
.markup-tester {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.calculation-visualization {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid #0d6efd;
}

.test-item {
    transition: all 0.2s ease;
    padding: 8px 12px;
    border-radius: 6px;
}

.test-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.cursor-pointer {
    cursor: pointer;
}

.statistics .badge {
    font-size: 0.75rem;
}

.calculation-details table td {
    padding: 8px 12px;
}

/* Анимации */
.test-item-enter-active,
.test-item-leave-active {
    transition: all 0.3s ease;
}

.test-item-enter-from {
    opacity: 0;
    transform: translateX(-10px);
}

.test-item-leave-to {
    opacity: 0;
    transform: translateX(10px);
}
</style>
