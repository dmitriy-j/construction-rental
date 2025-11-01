<template>
  <div class="real-time-analytics">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📈 Аналитика в реальном времени</h6>
        <div class="last-update text-muted small" v-if="!loading">
          Обновлено: {{ lastUpdate }}
        </div>
        <div class="last-update text-muted small" v-else>
          <i class="fas fa-spinner fa-spin"></i> Загрузка...
        </div>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center py-3">
          <div class="spinner-border spinner-border-sm" role="status"></div>
          <span class="ms-2">Загрузка данных...</span>
        </div>

        <div v-else class="row text-center">
          <div class="col-md-2 col-6 mb-3" v-for="metric in realTimeMetrics" :key="metric.id">
            <div class="metric-card" :class="{ 'highlight': metric.highlight }">
              <div class="metric-value" :class="metric.trendClass">
                {{ metric.value }}
              </div>
              <div class="metric-label">{{ metric.label }}</div>
              <div class="metric-description small text-muted">
                {{ metric.description }}
              </div>
            </div>
          </div>
        </div>

        <!-- Быстрые действия -->
        <div class="quick-actions mt-3 pt-3 border-top" v-if="!loading">
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
      required: true,
      default: () => ({
        activeRequests: 0,
        newRequestsToday: 0,
        myActiveProposals: 0,
        conversionRate: 0,
        avgResponseTime: '0ч',
        marketShare: '0%'
      })
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      lastUpdate: new Date().toLocaleTimeString('ru-RU')
    }
  },
  computed: {
    realTimeMetrics() {
      return [
        {
          id: 1,
          value: this.analytics.activeRequests || 0,
          label: 'Активных заявок',
          description: 'Доступно для ответа',
          trendClass: 'text-primary',
          highlight: true
        },
        {
          id: 2,
          value: this.analytics.newRequestsToday || 0,
          label: 'Новых сегодня',
          description: 'За последние 24 часа',
          trendClass: 'text-info'
        },
        {
          id: 3,
          value: this.analytics.myActiveProposals || 0,
          label: 'Ваших предложений',
          description: 'Ожидают ответа',
          trendClass: 'text-warning'
        },
        {
          id: 4,
          value: (this.analytics.conversionRate || 0) + '%',
          label: 'Конверсия',
          description: 'Принятых предложений',
          trendClass: this.analytics.conversionRate > 0 ? 'text-success' : 'text-secondary'
        },
        {
          id: 5,
          value: this.analytics.avgResponseTime || '0ч',
          label: 'Время ответа',
          description: 'Среднее',
          trendClass: 'text-secondary'
        },
        {
          id: 6,
          value: this.analytics.marketShare || '0%',
          label: 'Доля рынка',
          description: 'Ваша активность',
          trendClass: 'text-success'
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
    }
  },
  watch: {
    analytics: {
      handler() {
        this.lastUpdate = new Date().toLocaleTimeString('ru-RU');
      },
      deep: true
    }
  }
}
</script>

<style scoped>
/* Стили остаются без изменений */
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

.metric-description {
  font-size: 0.75rem;
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
