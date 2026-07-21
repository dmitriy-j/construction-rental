<template>
    <div class="admin-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h1 class="h3 mb-0">Панель управления</h1>
            <DashboardDateFilter :value="period" @change="onFilterChange" />
        </div>

        <!-- KPI Cards -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </div>

        <template v-if="!loading && data">
            <div class="row g-3 mb-4">
                <div v-for="(kpi, index) in data.kpi" :key="index" class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="card kpi-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="kpi-title mb-1">{{ kpi.title }}</p>
                                    <h4 class="kpi-value mb-0">{{ kpi.value }}</h4>
                                </div>
                                <div :class="'kpi-icon bg-' + kpi.color + '-subtle rounded-3 p-2'">
                                    <i :class="'bi ' + kpi.icon + ' text-' + kpi.color + ' fs-4'"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                            <h5 class="mb-0">Оборот по дням</h5>
                        </div>
                        <div class="card-body">
                            <Line v-if="data.charts.turnover.labels.length" :data="turnoverChartData" :options="chartOptions" :height="250" />
                            <div v-else class="text-center py-4 text-muted">Нет данных за выбранный период</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                            <h5 class="mb-0">Комиссия платформы</h5>
                        </div>
                        <div class="card-body">
                            <Line v-if="data.charts.commission.labels.length" :data="commissionChartData" :options="chartOptions" :height="250" />
                            <div v-else class="text-center py-4 text-muted">Нет данных за выбранный период</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                            <h5 class="mb-0">Заказы по статусам</h5>
                        </div>
                        <div class="card-body">
                            <Doughnut v-if="data.charts.ordersByStatus.labels.length" :data="ordersByStatusChartData" :options="doughnutOptions" :height="250" />
                            <div v-else class="text-center py-4 text-muted">Нет данных</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                            <h5 class="mb-0">Топ-5 арендодателей</h5>
                        </div>
                        <div class="card-body">
                            <div v-if="data.topLessors.length">
                                <div v-for="(lessor, i) in data.topLessors" :key="i" class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-truncate me-2">{{ i + 1 }}. {{ lessor.name }}</span>
                                    <span class="fw-bold text-success">{{ numberFormat(lessor.total) }} ₽</span>
                                </div>
                            </div>
                            <div v-else class="text-center py-4 text-muted">Нет данных</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
                            <h5 class="mb-0">Топ-5 арендаторов</h5>
                        </div>
                        <div class="card-body">
                            <div v-if="data.topLessees.length">
                                <div v-for="(lessee, i) in data.topLessees" :key="i" class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-truncate me-2">{{ i + 1 }}. {{ lessee.name }}</span>
                                    <span class="fw-bold text-primary">{{ lessee.total }} заказ(ов)</span>
                                </div>
                            </div>
                            <div v-else class="text-center py-4 text-muted">Нет данных</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Последние заказы</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Арендатор</th>
                                    <th>Арендодатель</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in data.recentOrders" :key="order.id">
                                    <td>#{{ order.id }}</td>
                                    <td>{{ order.lessee }}</td>
                                    <td>{{ order.lessor }}</td>
                                    <td class="fw-bold">{{ numberFormat(order.amount) }} ₽</td>
                                    <td><span :class="'badge bg-' + statusColor(order.status)">{{ order.status_text }}</span></td>
                                    <td>{{ order.date }}</td>
                                </tr>
                                <tr v-if="!data.recentOrders.length">
                                    <td colspan="6" class="text-center text-muted py-4">Нет заказов</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import axios from 'axios';
import { Line, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import DashboardDateFilter from './DashboardDateFilter.vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Title, Tooltip, Legend, Filler);

export default {
    name: 'AdminDashboard',
    components: { Line, Doughnut, DashboardDateFilter },
    data() {
        return {
            loading: true,
            period: 'month',
            data: null,
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('ru-RU') + ' ₽' } },
                },
            },
            doughnutOptions: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
            },
        };
    },
    computed: {
        turnoverChartData() {
            return this.data?.charts?.turnover || { labels: [], datasets: [] };
        },
        commissionChartData() {
            return this.data?.charts?.commission || { labels: [], datasets: [] };
        },
        ordersByStatusChartData() {
            return this.data?.charts?.ordersByStatus || { labels: [], datasets: [] };
        },
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        async fetchData() {
            this.loading = true;
            try {
                const params = { period: this.period };
                const response = await axios.get('/api/admin/dashboard', { params });
                this.data = response.data;
            } catch (error) {
                console.error('Error fetching admin dashboard data:', error);
            } finally {
                this.loading = false;
            }
        },
        onFilterChange(filter) {
            this.period = filter.period;
            this.fetchData();
        },
        numberFormat(value) {
            return Number(value || 0).toLocaleString('ru-RU');
        },
        statusColor(status) {
            const colors = {
                pending: 'warning',
                pending_approval: 'warning',
                confirmed: 'info',
                active: 'success',
                completed: 'secondary',
                cancelled: 'danger',
                rejected: 'danger',
                aggregated: 'secondary',
                in_delivery: 'info',
                extension_requested: 'primary',
            };
            return colors[status] || 'secondary';
        },
    },
};
</script>

<style scoped>
.kpi-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border-radius: 12px;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}
.kpi-title {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-weight: 600;
}
.kpi-value {
    font-weight: 700;
    font-size: 1.5rem;
}
.kpi-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card {
    border-radius: 12px;
    border: none;
    transition: box-shadow 0.2s;
}
</style>
