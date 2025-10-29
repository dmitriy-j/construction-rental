<!-- resources/js/components/Lessor/charts/PriceComparisonChart.vue -->
<template>
    <div class="price-comparison-chart">
        <div class="chart-header">
            <h6 class="chart-title">
                <i class="fas fa-tags me-2 text-success"></i>
                Сравнение цен
            </h6>
            <div class="chart-filters">
                <select v-model="selectedPeriod" class="form-select form-select-sm" @change="updateChart">
                    <option value="month">За месяц</option>
                    <option value="quarter">За квартал</option>
                    <option value="year">За год</option>
                </select>
            </div>
        </div>
        <div class="chart-container">
            <canvas ref="chartCanvas"></canvas>
        </div>
        <div class="chart-insights">
            <div class="insight-item" :class="priceDifferenceClass">
                <i class="fas" :class="priceDifferenceIcon"></i>
                Ваши цены {{ priceDifferenceText }} на {{ Math.abs(priceDifference) }}%
            </div>
        </div>
    </div>
</template>

<script>
import { Chart, registerables } from 'chart.js';

export default {
    name: 'PriceComparisonChart',
    props: {
        data: {
            type: Object,
            default: () => ({})
        }
    },
    data() {
        return {
            selectedPeriod: 'month',
            chart: null,
            chartData: {
                labels: ['Экскаваторы', 'Бульдозеры', 'Краны', 'Погрузчики', 'Грузовики', 'Компрессоры'],
                datasets: [
                    {
                        label: 'Ваши цены',
                        data: [3200, 2800, 4500, 1800, 2200, 1500],
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Средние по рынку',
                        data: [2950, 2600, 4200, 1650, 2000, 1350],
                        backgroundColor: 'rgba(108, 117, 125, 0.8)',
                        borderColor: '#6c757d',
                        borderWidth: 1
                    }
                ]
            }
        };
    },
    computed: {
        priceDifference() {
            const myPrices = this.chartData.datasets[0].data;
            const marketPrices = this.chartData.datasets[1].data;
            const myAvg = myPrices.reduce((a, b) => a + b, 0) / myPrices.length;
            const marketAvg = marketPrices.reduce((a, b) => a + b, 0) / marketPrices.length;
            return ((myAvg - marketAvg) / marketAvg * 100).toFixed(1);
        },
        priceDifferenceText() {
            return this.priceDifference > 0 ? 'выше' : 'ниже';
        },
        priceDifferenceIcon() {
            return this.priceDifference > 0 ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger';
        },
        priceDifferenceClass() {
            return this.priceDifference > 10 ? 'text-success' :
                   this.priceDifference < -10 ? 'text-danger' : 'text-warning';
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
                type: 'bar',
                data: this.chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#495057',
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += new Intl.NumberFormat('ru-RU', {
                                        style: 'currency',
                                        currency: 'RUB',
                                        minimumFractionDigits: 0
                                    }).format(context.parsed.y);
                                    label += '/час';
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('ru-RU', {
                                        style: 'currency',
                                        currency: 'RUB',
                                        minimumFractionDigits: 0
                                    }).format(value);
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
                                color: '#6c757d',
                                maxRotation: 45
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                }
            });
        },
        updateChart() {
            // Здесь будет логика обновления данных графика в зависимости от выбранного периода
            console.log('Обновление графика для периода:', this.selectedPeriod);

            // Временная заглушка - в реальном приложении здесь будет API запрос
            if (this.selectedPeriod === 'quarter') {
                this.chartData.datasets[0].data = [3100, 2700, 4400, 1750, 2100, 1450];
                this.chartData.datasets[1].data = [2900, 2550, 4150, 1600, 1950, 1300];
            } else if (this.selectedPeriod === 'year') {
                this.chartData.datasets[0].data = [3050, 2650, 4300, 1700, 2050, 1400];
                this.chartData.datasets[1].data = [2850, 2500, 4100, 1550, 1900, 1250];
            } else {
                this.chartData.datasets[0].data = [3200, 2800, 4500, 1800, 2200, 1500];
                this.chartData.datasets[1].data = [2950, 2600, 4200, 1650, 2000, 1350];
            }

            this.initChart();
        }
    }
}
</script>

<style scoped>
.price-comparison-chart {
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

.chart-filters .form-select {
    width: auto;
    font-size: 0.8rem;
}

.chart-container {
    height: 200px;
    position: relative;
}

.chart-insights {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
    text-align: center;
}

.insight-item {
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: #f8f9fa;
    display: inline-block;
}

@media (max-width: 768px) {
    .chart-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .chart-filters {
        align-self: flex-end;
    }
}
</style>
