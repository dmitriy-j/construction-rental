<template>
    <div class="markup-statistics">
        <!-- Заголовок и период -->
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>
                    Статистика применения наценок
                </h5>
                <div class="period-selector">
                    <div class="btn-group btn-group-sm">
                        <button
                            v-for="period in quickPeriods"
                            :key="period.value"
                            class="btn"
                            :class="selectedPeriod === period.value ? 'btn-primary' : 'btn-outline-primary'"
                            @click="setPeriod(period.value)"
                        >
                            {{ period.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Произвольный период -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Начальная дата</label>
                        <input
                            type="date"
                            class="form-control"
                            v-model="customDateFrom"
                            :max="customDateTo || today"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Конечная дата</label>
                        <input
                            type="date"
                            class="form-control"
                            v-model="customDateTo"
                            :min="customDateFrom"
                            :max="today"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button
                            class="btn btn-primary w-100"
                            @click="applyCustomPeriod"
                            :disabled="!customDateFrom || !customDateTo"
                        >
                            <i class="bi bi-calendar-range me-1"></i>
                            Применить период
                        </button>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Группировка</label>
                        <select class="form-select" v-model="groupBy">
                            <option value="day">По дням</option>
                            <option value="week">По неделям</option>
                            <option value="month">По месяцам</option>
                            <option value="quarter">По кварталам</option>
                        </select>
                    </div>
                </div>

                <!-- Основные метрики -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="card-body text-center">
                                <div class="metric-value text-primary">
                                    {{ formatNumber(metrics.totalApplications) }}
                                </div>
                                <div class="metric-label">Всего применений</div>
                                <div
                                    class="metric-change"
                                    :class="getChangeClass(metrics.applicationChange)"
                                >
                                    <i :class="getChangeIcon(metrics.applicationChange)"></i>
                                    {{ Math.abs(metrics.applicationChange) }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="card-body text-center">
                                <div class="metric-value text-success">
                                    {{ formatCurrency(metrics.totalRevenue) }}
                                </div>
                                <div class="metric-label">Общий доход</div>
                                <div
                                    class="metric-change"
                                    :class="getChangeClass(metrics.revenueChange)"
                                >
                                    <i :class="getChangeIcon(metrics.revenueChange)"></i>
                                    {{ Math.abs(metrics.revenueChange) }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="card-body text-center">
                                <div class="metric-value text-info">
                                    {{ formatNumber(metrics.activeMarkups) }}
                                </div>
                                <div class="metric-label">Активных наценок</div>
                                <div class="metric-description">
                                    {{ metrics.markupTypesCount }} типов
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="card-body text-center">
                                <div class="metric-value text-warning">
                                    {{ formatNumber(metrics.avgMarkupValue) }}
                                </div>
                                <div class="metric-label">Средняя наценка</div>
                                <div class="metric-description">
                                    на применение
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Графики и визуализации -->
        <div class="row mt-4">
            <!-- График применения по времени -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Динамика применения наценок</h6>
                        <div class="chart-legend">
                            <span class="legend-item me-3">
                                <i class="bi bi-circle-fill text-primary me-1"></i>
                                Количество
                            </span>
                            <span class="legend-item">
                                <i class="bi bi-circle-fill text-success me-1"></i>
                                Доход
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas ref="applicationsChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Распределение по типам -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Распределение по типам наценок</h6>
                    </div>
                    <div class="card-body">
                        <canvas ref="typesChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Детальная статистика -->
        <div class="row mt-4">
            <!-- Топ наценок по доходу -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Топ наценок по доходу</h6>
                        <button
                            class="btn btn-sm btn-outline-primary"
                            @click="exportTopMarkups"
                        >
                            <i class="bi bi-download me-1"></i>
                            Экспорт
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Наценка</th>
                                        <th width="100">Применений</th>
                                        <th width="120">Доход</th>
                                        <th width="80">Эффектив.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="markup in topMarkups"
                                        :key="markup.id"
                                        class="cursor-pointer"
                                        @click="showMarkupDetails(markup)"
                                    >
                                        <td>
                                            <div class="markup-info">
                                                <div class="fw-bold">#{{ markup.id }}</div>
                                                <div class="small text-muted">
                                                    {{ getMarkupTypeLabel(markup.type) }} •
                                                    {{ getEntityTypeLabel(markup.entity_type) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ markup.applications }}</span>
                                        </td>
                                        <td class="fw-bold text-success">
                                            {{ formatCurrency(markup.revenue) }}
                                        </td>
                                        <td>
                                            <div class="efficiency-bar">
                                                <div
                                                    class="efficiency-fill"
                                                    :style="{ width: markup.efficiency + '%' }"
                                                    :class="getEfficiencyClass(markup.efficiency)"
                                                ></div>
                                                <span class="efficiency-text">
                                                    {{ Math.round(markup.efficiency) }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Статистика по контекстам -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Эффективность по контекстам</h6>
                    </div>
                    <div class="card-body">
                        <canvas ref="contextsChart" height="200"></canvas>
                    </div>
                </div>

                <!-- Быстрая статистика -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">Ключевые показатели</h6>
                    </div>
                    <div class="card-body">
                        <div class="quick-stats">
                            <div
                                v-for="stat in quickStats"
                                :key="stat.label"
                                class="quick-stat-item"
                            >
                                <div class="stat-value">{{ stat.value }}</div>
                                <div class="stat-label">{{ stat.label }}</div>
                                <div
                                    class="stat-trend"
                                    :class="getTrendClass(stat.trend)"
                                >
                                    <i :class="getTrendIcon(stat.trend)"></i>
                                    {{ stat.trend }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Детальный анализ -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Детальный анализ эффективности</h6>
                        <div class="btn-group btn-group-sm">
                            <button
                                class="btn btn-outline-primary"
                                @click="exportDetailedAnalysis"
                            >
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                                Экспорт анализа
                            </button>
                            <button
                                class="btn btn-outline-secondary"
                                @click="generateReport"
                            >
                                <i class="bi bi-file-text me-1"></i>
                                Создать отчет
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="analysis-tabs">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeTab === 'performance' }"
                                        @click="activeTab = 'performance'"
                                    >
                                        Производительность
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeTab === 'seasonality' }"
                                        @click="activeTab = 'seasonality'"
                                    >
                                        Сезонность
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link"
                                        :class="{ active: activeTab === 'comparison' }"
                                        @click="activeTab = 'comparison'"
                                    >
                                        Сравнение периодов
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content mt-3">
                                <!-- Вкладка производительности -->
                                <div
                                    v-if="activeTab === 'performance'"
                                    class="tab-pane fade show active"
                                >
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Скорость расчета наценок</h6>
                                            <canvas ref="performanceChart" height="200"></canvas>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Распределение времени ответа</h6>
                                            <div class="response-time-stats">
                                                <div
                                                    v-for="timeStat in responseTimeStats"
                                                    :key="timeStat.range"
                                                    class="response-time-item"
                                                >
                                                    <div class="time-range">{{ timeStat.range }}</div>
                                                    <div class="time-bar">
                                                        <div
                                                            class="time-fill"
                                                            :style="{ width: timeStat.percentage + '%' }"
                                                        ></div>
                                                    </div>
                                                    <div class="time-count">{{ timeStat.count }}</div>
                                                    <div class="time-percentage">{{ timeStat.percentage }}%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Вкладка сезонности -->
                                <div
                                    v-if="activeTab === 'seasonality'"
                                    class="tab-pane fade show active"
                                >
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6>Сезонность применения наценок</h6>
                                            <canvas ref="seasonalityChart" height="200"></canvas>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>Сезонные коэффициенты</h6>
                                            <div class="seasonal-coefficients">
                                                <div
                                                    v-for="coef in seasonalCoefficients"
                                                    :key="coef.season"
                                                    class="coefficient-item"
                                                >
                                                    <div class="coefficient-season">{{ coef.season }}</div>
                                                    <div class="coefficient-value">
                                                        <span class="badge" :class="getCoefficientBadge(coef.value)">
                                                            ×{{ coef.value }}
                                                        </span>
                                                    </div>
                                                    <div class="coefficient-revenue">
                                                        {{ formatCurrency(coef.revenue) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Вкладка сравнения -->
                                <div
                                    v-if="activeTab === 'comparison'"
                                    class="tab-pane fade show active"
                                >
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Сравнение с предыдущим периодом</h6>
                                            <canvas ref="comparisonChart" height="200"></canvas>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Ключевые изменения</h6>
                                            <div class="changes-list">
                                                <div
                                                    v-for="change in keyChanges"
                                                    :key="change.metric"
                                                    class="change-item"
                                                >
                                                    <div class="change-metric">{{ change.metric }}</div>
                                                    <div class="change-values">
                                                        <span class="old-value">{{ change.oldValue }}</span>
                                                        <i class="bi bi-arrow-right mx-2 text-muted"></i>
                                                        <span class="new-value">{{ change.newValue }}</span>
                                                    </div>
                                                    <div
                                                        class="change-difference"
                                                        :class="getChangeClass(change.difference)"
                                                    >
                                                        {{ change.difference > 0 ? '+' : '' }}{{ change.difference }}%
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно деталей наценки -->
        <div class="modal fade" id="markupDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Детальная статистика наценки #{{ selectedMarkup?.id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedMarkup" class="markup-details">
                            <!-- Основная информация -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold">Тип:</td>
                                                <td>
                                                    <span class="badge" :class="getMarkupTypeBadge(selectedMarkup.type)">
                                                        {{ getMarkupTypeLabel(selectedMarkup.type) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Контекст:</td>
                                                <td>{{ getEntityTypeLabel(selectedMarkup.entity_type) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Значение:</td>
                                                <td class="fw-bold">
                                                    {{ selectedMarkup.value }}
                                                    <span v-if="selectedMarkup.type === 'percent'">%</span>
                                                    <span v-else>₽</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold">Применений:</td>
                                                <td class="fw-bold text-primary">{{ selectedMarkup.applications }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Доход:</td>
                                                <td class="fw-bold text-success">{{ formatCurrency(selectedMarkup.revenue) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Эффективность:</td>
                                                <td>
                                                    <span class="badge" :class="getEfficiencyBadge(selectedMarkup.efficiency)">
                                                        {{ Math.round(selectedMarkup.efficiency) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- График применения -->
                            <h6>Динамика применения</h6>
                            <canvas ref="markupTrendChart" height="150"></canvas>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MarkupStatistics',

    data() {
        const today = new Date().toISOString().split('T')[0];
        const oneMonthAgo = new Date();
        oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);

        return {
            // Период анализа
            selectedPeriod: 'month',
            customDateFrom: oneMonthAgo.toISOString().split('T')[0],
            customDateTo: today,
            today: today,
            groupBy: 'day',

            // Данные
            metrics: {
                totalApplications: 0,
                totalRevenue: 0,
                activeMarkups: 0,
                markupTypesCount: 0,
                avgMarkupValue: 0,
                applicationChange: 0,
                revenueChange: 0
            },

            topMarkups: [],
            quickStats: [],
            responseTimeStats: [],
            seasonalCoefficients: [],
            keyChanges: [],

            // Графики
            applicationsChart: null,
            typesChart: null,
            contextsChart: null,
            performanceChart: null,
            seasonalityChart: null,
            comparisonChart: null,
            markupTrendChart: null,

            // Состояние
            activeTab: 'performance',
            selectedMarkup: null,

            // Быстрые периоды
            quickPeriods: [
                { label: 'Неделя', value: 'week' },
                { label: 'Месяц', value: 'month' },
                { label: 'Квартал', value: 'quarter' },
                { label: 'Год', value: 'year' }
            ]
        };
    },

    mounted() {
        this.loadStatistics();
        this.initializeCharts();
    },

    watch: {
        selectedPeriod: 'loadStatistics',
        groupBy: 'loadStatistics'
    },

    methods: {
        async loadStatistics() {
            try {
                const params = {
                    period: this.selectedPeriod,
                    group_by: this.groupBy
                };

                if (this.selectedPeriod === 'custom') {
                    params.date_from = this.customDateFrom;
                    params.date_to = this.customDateTo;
                }

                const response = await axios.get('/admin/markups/statistics', { params });
                const data = response.data;

                // Основные метрики
                this.metrics = data.metrics || {};

                // Топ наценок
                this.topMarkups = data.top_markups || [];

                // Быстрая статистика
                this.quickStats = data.quick_stats || [];

                // Время ответа
                this.responseTimeStats = data.response_time_stats || [];

                // Сезонные коэффициенты
                this.seasonalCoefficients = data.seasonal_coefficients || [];

                // Ключевые изменения
                this.keyChanges = data.key_changes || [];

                // Обновляем графики
                this.updateCharts(data.charts);

            } catch (error) {
                console.error('Error loading statistics:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка загрузки',
                    text: 'Не удалось загрузить статистику'
                });
            }
        },

        setPeriod(period) {
            this.selectedPeriod = period;
        },

        applyCustomPeriod() {
            this.selectedPeriod = 'custom';
            this.loadStatistics();
        },

        initializeCharts() {
            // График применения
            if (this.$refs.applicationsChart) {
                this.applicationsChart = new Chart(this.$refs.applicationsChart, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [
                            {
                                label: 'Количество применений',
                                data: [],
                                borderColor: 'rgb(54, 162, 235)',
                                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                yAxisID: 'y',
                                tension: 0.4
                            },
                            {
                                label: 'Доход',
                                data: [],
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                yAxisID: 'y1',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Количество'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Доход (₽)'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });
            }

            // Кругивая диаграмма типов
            if (this.$refs.typesChart) {
                this.typesChart = new Chart(this.$refs.typesChart, {
                    type: 'doughnut',
                    data: {
                        labels: [],
                        datasets: [{
                            data: [],
                            backgroundColor: [
                                '#4dc9f6', '#f67019', '#f53794', '#537bc4', '#acc236',
                                '#166a8f', '#00a950', '#58595b', '#8549ba'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Инициализация остальных графиков...
        },

        updateCharts(chartData) {
            if (!chartData) return;

            // Обновляем график применения
            if (this.applicationsChart && chartData.applications) {
                this.applicationsChart.data.labels = chartData.applications.labels;
                this.applicationsChart.data.datasets[0].data = chartData.applications.applications;
                this.applicationsChart.data.datasets[1].data = chartData.applications.revenue;
                this.applicationsChart.update();
            }

            // Обновляем круговую диаграмму
            if (this.typesChart && chartData.types) {
                this.typesChart.data.labels = chartData.types.labels;
                this.typesChart.data.datasets[0].data = chartData.types.data;
                this.typesChart.update();
            }

            // Обновляем остальные графики...
        },

        showMarkupDetails(markup) {
            this.selectedMarkup = markup;
            new bootstrap.Modal(document.getElementById('markupDetailsModal')).show();

            // Загружаем детальную статистику для этой наценки
            this.loadMarkupDetails(markup.id);
        },

        async loadMarkupDetails(markupId) {
            try {
                const response = await axios.get(`/admin/markups/${markupId}/statistics`);
                this.updateMarkupTrendChart(response.data.trend);
            } catch (error) {
                console.error('Error loading markup details:', error);
            }
        },

        updateMarkupTrendChart(trendData) {
            if (this.markupTrendChart && trendData) {
                // Инициализация или обновление графика тренда
                if (!this.markupTrendChart) {
                    this.markupTrendChart = new Chart(this.$refs.markupTrendChart, {
                        type: 'line',
                        data: {
                            labels: trendData.labels,
                            datasets: [{
                                label: 'Применения',
                                data: trendData.data,
                                borderColor: 'rgb(54, 162, 235)',
                                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                } else {
                    this.markupTrendChart.data.labels = trendData.labels;
                    this.markupTrendChart.data.datasets[0].data = trendData.data;
                    this.markupTrendChart.update();
                }
            }
        },

        exportTopMarkups() {
            // Реализация экспорта топ наценок
            console.log('Export top markups');
        },

        exportDetailedAnalysis() {
            // Реализация экспорта детального анализа
            console.log('Export detailed analysis');
        },

        async generateReport() {
            try {
                const response = await axios.post('/admin/markups/generate-report', {
                    period: this.selectedPeriod,
                    date_from: this.customDateFrom,
                    date_to: this.customDateTo
                });

                this.$swal.fire({
                    icon: 'success',
                    title: 'Отчет создан',
                    text: 'Статистический отчет успешно сгенерирован',
                    timer: 3000,
                    showConfirmButton: false
                });
            } catch (error) {
                console.error('Error generating report:', error);
                this.$swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: 'Не удалось создать отчет'
                });
            }
        },

        // Вспомогательные методы
        formatNumber(number) {
            return new Intl.NumberFormat('ru-RU').format(number);
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(amount);
        },

        getChangeClass(change) {
            return change > 0 ? 'text-success' : change < 0 ? 'text-danger' : 'text-muted';
        },

        getChangeIcon(change) {
            return change > 0 ? 'bi-arrow-up' : change < 0 ? 'bi-arrow-down' : 'bi-dash';
        },

        getTrendClass(trend) {
            return trend > 0 ? 'trend-up' : trend < 0 ? 'trend-down' : 'trend-neutral';
        },

        getTrendIcon(trend) {
            return trend > 0 ? 'bi-arrow-up' : trend < 0 ? 'bi-arrow-down' : 'bi-dash';
        },

        getEfficiencyClass(efficiency) {
            if (efficiency >= 80) return 'efficiency-high';
            if (efficiency >= 60) return 'efficiency-medium';
            return 'efficiency-low';
        },

        getEfficiencyBadge(efficiency) {
            if (efficiency >= 80) return 'bg-success';
            if (efficiency >= 60) return 'bg-warning text-dark';
            return 'bg-danger';
        },

        getCoefficientBadge(value) {
            if (value > 1.2) return 'bg-success';
            if (value > 0.8) return 'bg-warning text-dark';
            return 'bg-danger';
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
        },

        getEntityTypeLabel(entityType) {
            const labels = {
                'order': 'Заказы',
                'rental_request': 'Заявки',
                'proposal': 'Предложения'
            };
            return labels[entityType] || entityType;
        }
    }
};
</script>

<style scoped>
.markup-statistics {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.metric-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
}

.metric-value {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.metric-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

.metric-change {
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.metric-description {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Эффективность */
.efficiency-bar {
    position: relative;
    background: #e9ecef;
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
}

.efficiency-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
}

.efficiency-high {
    background: linear-gradient(90deg, #28a745, #20c997);
}

.efficiency-medium {
    background: linear-gradient(90deg, #ffc107, #fd7e14);
}

.efficiency-low {
    background: linear-gradient(90deg, #dc3545, #e83e8c);
}

.efficiency-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: bold;
    color: #fff;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

/* Быстрая статистика */
.quick-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.quick-stat-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #495057;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.stat-trend {
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.trend-up {
    color: #28a745;
}

.trend-down {
    color: #dc3545;
}

.trend-neutral {
    color: #6c757d;
}

/* Время ответа */
.response-time-stats {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.response-time-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.time-range {
    width: 80px;
    font-size: 0.875rem;
    font-weight: 500;
}

.time-bar {
    flex: 1;
    background: #e9ecef;
    border-radius: 10px;
    height: 8px;
    overflow: hidden;
}

.time-fill {
    height: 100%;
    background: linear-gradient(90deg, #4dc9f6, #f67019);
    transition: width 0.3s ease;
}

.time-count, .time-percentage {
    width: 40px;
    font-size: 0.75rem;
    text-align: right;
    color: #6c757d;
}

/* Сезонные коэффициенты */
.seasonal-coefficients {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.coefficient-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.coefficient-season {
    font-weight: 500;
}

.coefficient-value .badge {
    font-size: 0.75rem;
}

.coefficient-revenue {
    font-weight: bold;
    color: #28a745;
}

/* Список изменений */
.changes-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.change-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.change-metric {
    font-weight: 500;
    flex: 1;
}

.change-values {
    display: flex;
    align-items: center;
    margin: 0 1rem;
}

.old-value {
    color: #6c757d;
    text-decoration: line-through;
}

.new-value {
    font-weight: bold;
    color: #495057;
}

.change-difference {
    font-weight: bold;
    width: 60px;
    text-align: right;
}

/* Легенда графика */
.chart-legend {
    font-size: 0.875rem;
}

.legend-item {
    display: inline-flex;
    align-items: center;
}

/* Информация о наценке */
.markup-info {
    max-width: 200px;
}

.cursor-pointer {
    cursor: pointer;
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
    .quick-stats {
        grid-template-columns: 1fr;
    }

    .metric-value {
        font-size: 1.5rem;
    }

    .change-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .change-values {
        margin: 0;
    }
}
</style>
