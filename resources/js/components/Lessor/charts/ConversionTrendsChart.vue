<!-- resources/js/components/Lessor/charts/ConversionTrendsChart.vue -->
<template>
    <div class="conversion-trends-chart">
        <div class="chart-header">
            <h6 class="chart-title">
                <i class="fas fa-chart-line me-2 text-primary"></i>
                Динамика конверсии
            </h6>
            <div class="chart-legend">
                <span class="legend-item">
                    <span class="legend-color my-conversion"></span>
                    Ваша конверсия
                </span>
                <span class="legend-item">
                    <span class="legend-color market-conversion"></span>
                    Рынок
                </span>
            </div>
        </div>
        <div class="chart-container">
            <canvas ref="chartCanvas"></canvas>
        </div>
        <div class="chart-summary">
            <div class="summary-item">
                <span class="summary-label">Текущая конверсия:</span>
                <span class="summary-value text-success">{{ currentConversion }}%</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Изменение за период:</span>
                <span class="summary-value" :class="trendClass">
                    {{ trendIcon }} {{ Math.abs(trendValue) }}%
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import { Chart, registerables } from 'chart.js';

export default {
    name: 'ConversionTrendsChart',
    props: {
        data: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            chart: null,
            chartData: {
                labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
                datasets: [
                    {
                        label: 'Ваша конверсия',
                        data: [45, 52, 48, 55, 58, 62, 65, 63, 68, 72, 70, 75],
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Средняя по рынку',
                        data: [40, 45, 42, 48, 50, 52, 55, 53, 56, 58, 57, 60],
                        borderColor: '#6c757d',
                        backgroundColor: 'rgba(108, 117, 125, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            }
        };
    },
    computed: {
        currentConversion() {
            const lastData = this.chartData.datasets[0].data;
            return lastData[lastData.length - 1] || 0;
        },
        trendValue() {
            const data = this.chartData.datasets[0].data;
            if (data.length < 2) return 0;
            return ((data[data.length - 1] - data[data.length - 2]) / data[data.length - 2] * 100).toFixed(1);
        },
        trendIcon() {
            return this.trendValue > 0 ? '↗' : this.trendValue < 0 ? '↘' : '→';
        },
        trendClass() {
            return this.trendValue > 0 ? 'text-success' : this.trendValue < 0 ? 'text-danger' : 'text-secondary';
        }
    },
    mounted() {
        this.initChart();
    },
    beforeUnmount() {
        if (this.chart) {
            this.chart.destroy();
        }
    },
    methods: {
        initChart() {
            if (this.chart) {
                this.chart.destroy();
            }

            const ctx = this.$refs.chartCanvas;
            if (!ctx) return;

            Chart.register(...registerables);

            this.chart = new Chart(ctx, {
                type: 'line',
                data: this.chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                },
                                color: '#6c757d'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6c757d'
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }
    }
}
</script>

<style scoped>
.conversion-trends-chart {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #e9ecef;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.chart-title {
    margin: 0;
    font-size: 0.9rem;
    color: #495057;
    font-weight: 600;
}

.chart-legend {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.legend-color.my-conversion {
    background: #0d6efd;
}

.legend-color.market-conversion {
    background: #6c757d;
}

.chart-container {
    height: 200px;
    position: relative;
}

.chart-summary {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.summary-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.summary-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.summary-value {
    font-weight: 600;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .chart-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .chart-legend {
        align-self: flex-end;
    }

    .chart-summary {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
