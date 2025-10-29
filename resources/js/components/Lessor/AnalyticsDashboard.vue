<template>
  <div class="analytics-dashboard">
    <!-- Заголовок и переключение режимов -->
    <div class="dashboard-header">
      <h5 class="dashboard-title">
        <i class="fas fa-chart-line me-2"></i>
        Аналитика эффективности
      </h5>
      <div class="dashboard-tabs">
        <button
          @click="activeMode = 'realtime'"
          :class="['tab-button', { active: activeMode === 'realtime' }]"
        >
          <i class="fas fa-bolt me-1"></i>
          Оперативная
        </button>
        <button
          @click="activeMode = 'strategic'"
          :class="['tab-button', { active: activeMode === 'strategic' }]"
        >
          <i class="fas fa-chart-bar me-1"></i>
          Стратегическая
        </button>

        <!-- Добавленная кнопка для шаблонов -->
        <button
          @click="activeMode = 'templates'"
          :class="['tab-button', { active: activeMode === 'templates' }]"
        >
          <i class="fas fa-file-alt me-1"></i>
          Шаблоны
        </button>

        <!-- Кнопка обновления -->
        <button
          @click="refreshCounters"
          class="tab-button refresh-btn"
          :disabled="refreshing"
        >
          <i class="fas fa-sync" :class="{ 'fa-spin': refreshing }"></i>
          Обновить
        </button>
      </div>
    </div>

    <!-- Режим оперативной аналитики -->
    <div v-if="activeMode === 'realtime'" class="realtime-mode">
      <RealTimeAnalytics
        :analytics="realTimeData"
        @quick-action="handleQuickAction"
      />

      <!-- Быстрые действия -->
      <div class="quick-actions-grid mt-3">
        <QuickActionCard
          title="Срочные заявки"
          :count="dashboardCounters.urgent_requests"
          icon="fas fa-exclamation-circle"
          color="danger"
          @click="showUrgentRequests"
          :loading="loadingCounters.urgent"
          description="Новые за последние 2 часа"
        />
        <QuickActionCard
          title="Активные шаблоны"
          :count="dashboardCounters.templates"
          icon="fas fa-file-alt"
          color="primary"
          @click="showTemplates"
          :loading="loadingCounters.templates"
          description="Готовые предложения"
        />
        <QuickActionCard
          title="Мои предложения"
          :count="dashboardCounters.my_proposals"
          icon="fas fa-paper-plane"
          color="warning"
          @click="showMyProposals"
          :loading="loadingCounters.proposals"
          description="Ожидают ответа"
        />
        <QuickActionCard
          title="Всего заявок"
          :count="dashboardCounters.active_requests"
          icon="fas fa-list"
          color="info"
          @click="showAllRequests"
          :loading="loadingCounters.active"
          description="Активные на платформе"
        />
      </div>
    </div>

    <!-- Режим стратегической аналитики -->
    <div v-else-if="activeMode === 'strategic'" class="strategic-mode">
      <StrategicAnalytics
        :conversion-data="conversionData"
        :price-analytics="priceAnalytics"
        :recommendations="strategicRecommendations"
      />

      <!-- Дополнительные отчеты -->
      <div class="reports-section mt-4">
        <div class="row">
          <div class="col-md-6">
            <ConversionTrendsChart :data="conversionTrends" />
          </div>
          <div class="col-md-6">
            <PriceComparisonChart :data="priceComparison" />
          </div>
        </div>
      </div>
    </div>

    <!-- Режим шаблонов -->
    <div v-if="activeMode === 'templates'" class="templates-mode">
      <ProposalTemplates
        :categories="categories"
        @template-applied="handleTemplateApplied"
        @template-saved="handleTemplateSaved"
      />

      <!-- Дополнительная аналитика шаблонов -->
      <div class="row mt-4" v-if="templateAnalytics">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">Эффективность по категориям</h6>
            </div>
            <div class="card-body">
              <!-- График эффективности шаблонов -->
              <div>
                <p>График эффективности будет здесь.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h6 class="mb-0">Рекомендации</h6>
            </div>
            <div class="card-body">
              <div v-for="rec in templateRecommendations" :key="rec.id" class="alert alert-warning py-2">
                <small>{{ rec.message }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Общие уведомления -->
    <div v-if="criticalAlerts.length > 0" class="critical-alerts mt-3">
      <div v-for="alert in criticalAlerts" :key="alert.id" class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ alert.message }}
        <button v-if="alert.action" @click="alert.action" class="btn btn-sm btn-outline-warning ms-2">
          {{ alert.actionText }}
        </button>
      </div>
    </div>

    <!-- Статус обновления -->
    <div v-if="dashboardCounters.last_updated" class="update-status mt-2">
      <small class="text-muted">
        <i class="fas fa-clock me-1"></i>
        Обновлено: {{ formatLastUpdated(dashboardCounters.last_updated) }}
      </small>
    </div>
  </div>
</template>

<script>
import RealTimeAnalytics from './RealTimeAnalytics.vue';
import StrategicAnalytics from './StrategicAnalytics.vue';
import ProposalTemplates from './ProposalTemplates.vue';
import QuickActionCard from './QuickActionCard.vue';
import ConversionTrendsChart from './charts/ConversionTrendsChart.vue';
import PriceComparisonChart from './charts/PriceComparisonChart.vue';

export default {
  name: 'AnalyticsDashboard',
  components: {
    RealTimeAnalytics,
    StrategicAnalytics,
    ProposalTemplates,
    QuickActionCard,
    ConversionTrendsChart,
    PriceComparisonChart
  },
  props: {
    initialData: {
      type: Object,
      default: () => ({})
    },
    realTimeMetrics: {
      type: Object,
      default: () => ({})
    },
    strategicMetrics: {
      type: Object,
      default: () => ({})
    },
    categories: {
      type: Array,
      default: () => []
    },
    urgentRequests: {
      type: Array,
      default: () => []
    },
    templates: {
      type: Array,
      default: () => []
    },
    myProposalsCount: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      activeMode: 'realtime',
      refreshing: false,
      dashboardCounters: {
        urgent_requests: 0,
        templates: 0,
        my_proposals: 0,
        active_requests: 0,
        last_updated: null
      },
      loadingCounters: {
        urgent: false,
        templates: false,
        proposals: false,
        active: false
      },
      realTimeData: {
        activeRequests: 0,
        newRequestsToday: 0,
        myActiveProposals: 0,
        conversionRate: 0,
        avgResponseTime: '0ч',
        marketShare: '0%'
      },
      conversionData: {
        myConversionRate: 0,
        marketConversionRate: 0,
        trend: 'stable'
      },
      priceAnalytics: {
        myAvgPrice: 0,
        marketAvgPrice: 0,
        priceDifferencePercent: 0
      },
      strategicRecommendations: [],
      criticalAlerts: [],
      conversionTrends: [],
      priceComparison: [],
      templateAnalytics: true,
      templateRecommendations: []
    };
  },
  computed: {
    urgentRequestsCount() {
      return this.urgentRequests.length || this.dashboardCounters.urgent_requests || 0;
    },
    templatesCount() {
      return this.templates.length || this.dashboardCounters.templates || 0;
    },
    myProposalsComputedCount() {
      return this.myProposalsCount || this.dashboardCounters.my_proposals || 0;
    }
  },
  methods: {
    async loadRealCounters() {
      try {
        console.log('📊 Загрузка реальных данных счетчиков...');
        this.setLoadingState(true);

        const response = await axios.get('/api/lessor/dashboard-counters');

        if (response.data.success) {
          this.dashboardCounters = {
            ...this.dashboardCounters,
            ...response.data.data
          };

          // Обновляем реальные данные
          this.realTimeData.activeRequests = this.dashboardCounters.active_requests;
          this.realTimeData.myActiveProposals = this.dashboardCounters.my_proposals;

          console.log('✅ Счетчики загружены:', this.dashboardCounters);
        } else {
          throw new Error(response.data.message || 'Ошибка сервера');
        }

      } catch (error) {
        console.error('❌ Ошибка загрузки счетчиков:', error);
        this.showErrorNotification(error);
        this.showFallbackCounters();
      } finally {
        this.setLoadingState(false);
        this.refreshing = false;
      }
    },

    async refreshCounters() {
      this.refreshing = true;
      await this.loadRealCounters();

      this.$notify({
        title: '✅ Данные обновлены',
        text: `Обновлено: ${new Date().toLocaleTimeString()}`,
        type: 'success',
        duration: 2000
      });
    },

    setLoadingState(loading) {
      this.loadingCounters = {
        urgent: loading,
        templates: loading,
        proposals: loading,
        active: loading
      };
    },

    showFallbackCounters() {
      console.log('🔄 Используем запасные данные счетчиков');
      this.dashboardCounters = {
        urgent_requests: this.urgentRequests.length || 0,
        templates: this.templates.length || 0,
        my_proposals: this.myProposalsCount || 0,
        active_requests: 0,
        last_updated: new Date().toISOString()
      };
    },

    showErrorNotification(error) {
      this.$notify({
        title: '❌ Ошибка загрузки',
        text: 'Не удалось загрузить данные счетчиков',
        type: 'error',
        duration: 5000
      });
    },

    formatLastUpdated(timestamp) {
      if (!timestamp) return '';
      const date = new Date(timestamp);
      return date.toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
    },

    handleQuickAction(action) {
      console.log('Быстрое действие:', action);
      switch (action) {
        case 'proposal':
          this.showQuickProposalModal();
          break;
        case 'templates':
          this.showTemplates();
          break;
        case 'favorites':
          this.showFavorites();
          break;
        case 'export':
          this.exportAnalyticsData();
          break;
        case 'refresh':
          this.refreshCounters();
          break;
      }
    },

    showTemplates() {
      this.activeMode = 'templates';
    },

    showUrgentRequests() {
      this.$emit('show-urgent-requests');
    },

    showMyProposals() {
      this.$emit('show-my-proposals');
    },

    showAllRequests() {
      this.$emit('show-all-requests');
    },

    showQuickProposalModal() {
      this.$emit('quick-proposal');
    },

    showFavorites() {
      this.$emit('show-favorites');
    },

    handleTemplateApplied(templateData) {
      console.log('Шаблон применен:', templateData);
      // Обновляем счетчик предложений при применении шаблона
      this.dashboardCounters.my_proposals += 1;

      this.$notify({
        title: '✅ Шаблон применен',
        text: `Шаблон "${templateData.template.name}" успешно применен`,
        type: 'success',
        duration: 3000
      });
    },

    handleTemplateSaved() {
      console.log('Шаблон сохранен');
      // Обновляем счетчик шаблонов при сохранении
      this.dashboardCounters.templates += 1;

      this.$notify({
        title: '✅ Шаблон сохранен',
        text: 'Новый шаблон успешно создан',
        type: 'success',
        duration: 3000
      });
    },

    exportAnalyticsData() {
      const data = {
        realTime: this.realTimeData,
        strategic: {
          conversion: this.conversionData,
          pricing: this.priceAnalytics
        },
        counters: this.dashboardCounters,
        exportDate: new Date().toISOString()
      };

      const blob = new Blob([JSON.stringify(data, null, 2)], {
        type: 'application/json'
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `analytics-dashboard-${new Date().toISOString().split('T')[0]}.json`;
      a.click();
      URL.revokeObjectURL(url);

      this.$notify({
        title: '📊 Экспорт завершен',
        text: 'Данные аналитики успешно экспортированы',
        type: 'success',
        duration: 3000
      });
    },

    async loadData() {
      try {
        // Загружаем реальные счетчики первыми
        await this.loadRealCounters();

        // Затем загружаем остальные данные
        const [realtimeResponse, strategicResponse] = await Promise.all([
          this.fetchRealTimeData(),
          this.fetchStrategicData()
        ]);

        this.realTimeData = { ...this.realTimeData, ...realtimeResponse.data };
        this.conversionData = strategicResponse.data.conversion;
        this.priceAnalytics = strategicResponse.data.pricing;
        this.criticalAlerts = strategicResponse.data.alerts || [];

      } catch (error) {
        console.error('Ошибка загрузки данных аналитики:', error);
        this.showFallbackData();
      }
    },

    showFallbackData() {
      this.realTimeData = {
        activeRequests: this.dashboardCounters.active_requests || 24,
        newRequestsToday: 8,
        myActiveProposals: this.dashboardCounters.my_proposals || 5,
        conversionRate: 65,
        avgResponseTime: '2.1ч',
        marketShare: '18%'
      };

      this.conversionData = {
        myConversionRate: 65,
        marketConversionRate: 58,
        trend: 'up'
      };

      this.priceAnalytics = {
        myAvgPrice: 2450,
        marketAvgPrice: 2200,
        priceDifferencePercent: 11.4
      };

      this.strategicRecommendations = [
        {
          id: 1,
          icon: 'fas fa-arrow-up text-success',
          message: `У вас ${this.dashboardCounters.templates || 0} активных шаблонов - отличный результат!`,
          priority: 'medium'
        },
        {
          id: 2,
          icon: 'fas fa-bolt text-warning',
          message: `${this.dashboardCounters.urgent_requests || 0} срочных заявок ждут вашего предложения`,
          priority: this.dashboardCounters.urgent_requests > 0 ? 'critical' : 'low'
        }
      ];
    },

    async fetchRealTimeData() {
      return Promise.resolve({
        data: {
          activeRequests: this.dashboardCounters.active_requests || 0,
          newRequestsToday: 8,
          myActiveProposals: this.dashboardCounters.my_proposals || 0,
          conversionRate: 65,
          avgResponseTime: '2.1ч',
          marketShare: '18%'
        }
      });
    },

    async fetchStrategicData() {
      return Promise.resolve({
        data: {
          conversion: {
            myConversionRate: 65,
            marketConversionRate: 58,
            trend: 'up'
          },
          pricing: {
            myAvgPrice: 2450,
            marketAvgPrice: 2200,
            priceDifferencePercent: 11.4
          },
          alerts: [
            {
              id: 1,
              message: `У вас ${this.dashboardCounters.my_proposals || 0} активных предложений`,
              action: () => this.activeMode = 'strategic',
              actionText: 'Анализировать'
            }
          ]
        }
      });
    }
  },
  watch: {
    urgentRequests: {
      handler(newRequests) {
        console.log('🔄 Обновление срочных заявок:', newRequests.length);
        this.dashboardCounters.urgent_requests = newRequests.length;
      },
      immediate: true,
      deep: true
    },

    templates: {
      handler(newTemplates) {
        console.log('🔄 Обновление шаблонов:', newTemplates.length);
        this.dashboardCounters.templates = newTemplates.length;
      },
      immediate: true,
      deep: true
    },

    myProposalsCount: {
      handler(newCount) {
        console.log('🔄 Обновление моих предложений:', newCount);
        this.dashboardCounters.my_proposals = newCount;
      },
      immediate: true
    }
  },
  mounted() {
    this.loadData();
    console.log('✅ AnalyticsDashboard mounted');
    console.log('📊 Начальные счетчики:', this.dashboardCounters);

    // Периодическое обновление счетчиков
    this.countersInterval = setInterval(() => {
      this.loadRealCounters();
    }, 120000); // Обновление каждые 2 минуты
  },

  beforeUnmount() {
    if (this.countersInterval) {
      clearInterval(this.countersInterval);
    }
  }
}
</script>

<style scoped>
.analytics-dashboard {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e9ecef;
}

.dashboard-title {
  margin: 0;
  color: #2c3e50;
  font-weight: 600;
}

.dashboard-tabs {
  display: flex;
  gap: 0.5rem;
  background: #f8f9fa;
  padding: 0.25rem;
  border-radius: 8px;
  align-items: center;
}

.tab-button {
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s ease;
  color: #6c757d;
  white-space: nowrap;
}

.tab-button:hover {
  background: rgba(0,0,0,0.05);
}

.tab-button.active {
  background: white;
  color: #0d6efd;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.refresh-btn {
  margin-left: auto;
  background: #e3f2fd;
  color: #1976d2;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.critical-alerts {
  animation: slideIn 0.5s ease-out;
}

.update-status {
  text-align: right;
  padding-top: 0.5rem;
  border-top: 1px solid #e9ecef;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.loading-counter {
  opacity: 0.7;
  pointer-events: none;
}

.counter-skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .dashboard-tabs {
    justify-content: center;
    flex-wrap: wrap;
  }

  .quick-actions-grid {
    grid-template-columns: 1fr;
  }

  .tab-button {
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
  }
}
</style>
