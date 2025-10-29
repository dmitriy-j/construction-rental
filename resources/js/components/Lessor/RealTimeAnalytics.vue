<template>
  <div class="real-time-analytics">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📈 Аналитика в реальном времени</h6>
        <div class="last-update text-muted small">
          Обновлено: {{ lastUpdate }}
        </div>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-md-2 col-6 mb-3" v-for="metric in realTimeMetrics" :key="metric.id">
            <div class="metric-card" :class="{ 'highlight': metric.highlight }">
              <div class="metric-value" :class="metric.trendClass">
                {{ metric.value }}
                <i v-if="metric.trendIcon" :class="metric.trendIcon"></i>
              </div>
              <div class="metric-label">{{ metric.label }}</div>
              <div class="metric-change small" :class="metric.trendClass">
                {{ metric.change }}
              </div>
            </div>
          </div>
        </div>

        <!-- Быстрые действия -->
        <div class="quick-actions mt-3 pt-3 border-top">
          <div class="row g-2">
            <div class="col-auto" v-for="action in quickActions" :key="action.id">
              <button class="btn btn-sm" :class="action.class" @click="action.handler">
                <i :class="action.icon"></i> {{ action.label }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RealTimeAnalytics',
  props: {
    analytics: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      lastUpdate: new Date().toLocaleTimeString('ru-RU'),
      updateInterval: null,
      realTimeData: {
        activeRequests: 0,
        newRequestsToday: 0,
        myActiveProposals: 0,
        conversionRate: 0,
        avgResponseTime: '2.5ч',
        marketShare: '15%'
      }
    }
  },
  computed: {
    realTimeMetrics() {
      return [
        {
          id: 1,
          value: this.realTimeData.activeRequests,
          label: 'Активных заявок',
          change: '+3 за сегодня',
          trendClass: 'text-success',
          trendIcon: 'fas fa-arrow-up',
          highlight: true
        },
        {
          id: 2,
          value: this.realTimeData.newRequestsToday,
          label: 'Новых сегодня',
          change: '↗ на 25%',
          trendClass: 'text-warning',
          trendIcon: 'fas fa-chart-line'
        },
        {
          id: 3,
          value: this.realTimeData.myActiveProposals,
          label: 'Ваших предложений',
          change: '5 ожидают ответа',
          trendClass: 'text-info'
        },
        {
          id: 4,
          value: this.realTimeData.conversionRate + '%',
          label: 'Конверсия',
          change: '▲ 5.2%',
          trendClass: 'text-success',
          trendIcon: 'fas fa-trend-up'
        },
        {
          id: 5,
          value: this.realTimeData.avgResponseTime,
          label: 'Среднее время ответа',
          change: '▼ 0.5ч',
          trendClass: 'text-danger',
          trendIcon: 'fas fa-trend-down'
        },
        {
          id: 6,
          value: this.realTimeData.marketShare,
          label: 'Доля рынка',
          change: '↗ 2.1%',
          trendClass: 'text-success',
          trendIcon: 'fas fa-chart-pie'
        }
      ]
    },
    quickActions() {
      return [
        {
          id: 1,
          label: 'Быстрое предложение',
          icon: 'fas fa-bolt me-1',
          class: 'btn-outline-primary',
          handler: this.quickProposal
        },
        {
          id: 2,
          label: 'Мои шаблоны',
          icon: 'fas fa-file-alt me-1',
          class: 'btn-outline-success',
          handler: this.showTemplates
        },
        {
          id: 3,
          label: 'Избранные',
          icon: 'fas fa-star me-1',
          class: 'btn-outline-warning',
          handler: this.showFavorites
        },
        {
          id: 4,
          label: 'Экспорт данных',
          icon: 'fas fa-download me-1',
          class: 'btn-outline-info',
          handler: this.exportData
        }
      ]
    }
  },
  methods: {
    quickProposal() {
      this.$emit('quick-action', 'proposal');
    },
    showTemplates() {
      this.$emit('quick-action', 'templates');
    },
    showFavorites() {
      this.$emit('quick-action', 'favorites');
    },
    exportData() {
      this.$emit('quick-action', 'export');
    },
    updateRealTimeData() {
      // Имитация обновления данных в реальном времени
      this.realTimeData.activeRequests = Math.floor(Math.random() * 50) + 20;
      this.realTimeData.newRequestsToday = Math.floor(Math.random() * 10) + 5;
      this.realTimeData.myActiveProposals = Math.floor(Math.random() * 15) + 3;
      this.realTimeData.conversionRate = Math.floor(Math.random() * 30) + 60;
      this.lastUpdate = new Date().toLocaleTimeString('ru-RU');
    }
  },
  mounted() {
    this.updateRealTimeData();
    // Обновляем данные каждые 30 секунд
    this.updateInterval = setInterval(this.updateRealTimeData, 30000);
  },
  beforeUnmount() {
    if (this.updateInterval) {
      clearInterval(this.updateInterval);
    }
  }
}
</script>

<style scoped>
.real-time-analytics {
  margin-bottom: 1.5rem;
}

.metric-card {
  padding: 1rem 0.5rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.metric-card:hover {
  background: #f8f9fa;
  transform: translateY(-2px);
}

.metric-card.highlight {
  background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
  border: 1px solid #e1f5fe;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.metric-label {
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 0.25rem;
}

.metric-change {
  font-size: 0.75rem;
  font-weight: 500;
}

.quick-actions .btn {
  border-radius: 20px;
  padding: 0.375rem 0.75rem;
}

@media (max-width: 768px) {
  .metric-value {
    font-size: 1.25rem;
  }

  .quick-actions .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>
