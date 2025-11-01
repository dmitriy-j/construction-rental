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

        <button
          @click="activeMode = 'templates'"
          :class="['tab-button', { active: activeMode === 'templates' }]"
        >
          <i class="fas fa-file-alt me-1"></i>
          Шаблоны
        </button>

        <button
          @click="refreshAllData"
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
        :loading="loadingRealtime"
        @quick-action="handleQuickAction"
        />

      <!-- Быстрые действия -->
      <div class="quick-actions-grid mt-3">
        <QuickActionCard
          title="Срочные заявки"
          :count="dashboardCounters.urgent_requests || 0"
          icon="fas fa-exclamation-circle"
          color="danger"
          @click="showUrgentRequests"
          :loading="loadingCounters"
          description="Новые за последние 2 часа"
        />
        <QuickActionCard
          title="Активные шаблоны"
          :count="dashboardCounters.templates || 0"
          icon="fas fa-file-alt"
          color="primary"
          @click="showTemplates"
          :loading="loadingCounters"
          description="Готовые предложения"
        />
        <QuickActionCard
          title="Мои предложения"
          :count="dashboardCounters.my_proposals || 0"
          icon="fas fa-paper-plane"
          color="warning"
          @click="showMyProposals"
          :loading="loadingCounters"
          description="Ожидают ответа"
        />
        <QuickActionCard
          title="Всего заявок"
          :count="dashboardCounters.active_requests || 0"
          icon="fas fa-list"
          color="info"
          @click="showAllRequests"
          :loading="loadingCounters"
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
        :loading="loadingStrategic"
      />

      <!-- Дополнительные отчеты -->
      <div class="reports-section mt-4" v-if="!loadingStrategic && conversionTrends.length > 0">
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

    <!-- Индикатор загрузки -->
    <div v-if="loadingRealtime || loadingStrategic" class="loading-overlay">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Загрузка...</span>
      </div>
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
import Swal from 'sweetalert2';

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
      loadingRealtime: false,
      loadingStrategic: false,
      loadingCounters: false,

      // ВСЕ ДАННЫЕ ИНИЦИАЛИЗИРУЕМ НУЛЯМИ - НИКАКИХ ФИКТИВНЫХ ДАННЫХ!
      dashboardCounters: {
        urgent_requests: 0,
        templates: 0,
        my_proposals: 0,
        active_requests: 0,
        last_updated: null
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
    async refreshAllData() {
      this.refreshing = true;
      try {
        await Promise.all([
          this.loadRealCounters(),
          this.loadRealTimeData(),
          this.loadStrategicData()
        ]);

        Swal.fire({
          title: '✅ Данные обновлены',
          text: `Обновлено: ${new Date().toLocaleTimeString()}`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        });
      } catch (error) {
        console.error('Ошибка обновления данных:', error);
        this.showErrorNotification('Не удалось обновить данные');
      } finally {
        this.refreshing = false;
      }
    },

    async loadRealCounters() {
      try {
        this.loadingCounters = true;
        console.log('📊 Загрузка реальных данных счетчиков...');

        const response = await axios.get('/api/lessor/analytics/dashboard-counters');

        if (response.data.success) {
          this.dashboardCounters = {
            ...response.data.data,
            last_updated: new Date().toISOString()
          };
          console.log('✅ Счетчики загружены:', this.dashboardCounters);
        } else {
          throw new Error(response.data.message || 'Ошибка сервера');
        }

      } catch (error) {
        console.error('❌ Ошибка загрузки счетчиков:', error);
        this.showErrorNotification('Не удалось загрузить данные счетчиков');
        // Используем только реальные данные из props
        this.useOnlyRealData();
      } finally {
        this.loadingCounters = false;
      }
    },

    async loadRealTimeData() {
      try {
        this.loadingRealtime = true;
        console.log('🔄 Загрузка данных реального времени...');

        const response = await axios.get('/api/lessor/analytics/realtime');

        if (response.data.success) {
          this.realTimeData = response.data.data;
          console.log('✅ Данные реального времени загружены:', this.realTimeData);
        } else {
          throw new Error(response.data.message || 'Ошибка сервера');
        }

      } catch (error) {
        console.error('❌ Ошибка загрузки данных реального времени:', error);
        // НЕ ИСПОЛЬЗУЕМ ФИКТИВНЫЕ ДАННЫЕ - только нули
        this.realTimeData = {
          activeRequests: 0,
          newRequestsToday: 0,
          myActiveProposals: 0,
          conversionRate: 0,
          avgResponseTime: '0ч',
          marketShare: '0%'
        };
      } finally {
        this.loadingRealtime = false;
      }
    },

    async loadStrategicData() {
      try {
        this.loadingStrategic = true;
        console.log('📈 Загрузка стратегической аналитики...');

        const response = await axios.get('/api/lessor/analytics/strategic');

        if (response.data.success) {
          this.conversionData = response.data.data.conversion || {};
          this.priceAnalytics = response.data.data.pricing || {};
          this.strategicRecommendations = response.data.data.recommendations || [];
          this.criticalAlerts = response.data.data.alerts || [];
          console.log('✅ Стратегическая аналитика загружена');
        } else {
          throw new Error(response.data.message || 'Ошибка сервера');
        }

      } catch (error) {
        console.error('❌ Ошибка загрузки стратегической аналитики:', error);
        // НЕ ИСПОЛЬЗУЕМ ФИКТИВНЫЕ ДАННЫЕ - только нули
        this.conversionData = {
          myConversionRate: 0,
          marketConversionRate: 0,
          trend: 'stable'
        };
        this.priceAnalytics = {
          myAvgPrice: 0,
          marketAvgPrice: 0,
          priceDifferencePercent: 0
        };
        this.strategicRecommendations = [];
        this.criticalAlerts = [];
      } finally {
        this.loadingStrategic = false;
      }
    },

    useOnlyRealData() {
      // Используем ТОЛЬКО реальные данные из props
      this.dashboardCounters = {
        urgent_requests: this.urgentRequests.length || 0,
        templates: this.templates.length || 0,
        my_proposals: this.myProposalsCount || 0,
        active_requests: 0,
        last_updated: new Date().toISOString()
      };
    },

    showErrorNotification(message) {
      Swal.fire({
        title: '❌ Ошибка загрузки',
        text: message,
        icon: 'error',
        timer: 5000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
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
          this.refreshAllData();
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
      this.dashboardCounters.my_proposals += 1;

      Swal.fire({
        title: '✅ Шаблон применен',
        text: `Шаблон "${templateData.template.name}" успешно применен`,
        icon: 'success',
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
      });
    },

    handleTemplateSaved() {
      console.log('Шаблон сохранен');
      this.dashboardCounters.templates += 1;

      Swal.fire({
        title: '✅ Шаблон сохранен',
        text: 'Новый шаблон успешно создан',
        icon: 'success',
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
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

      Swal.fire({
        title: '📊 Экспорт завершен',
        text: 'Данные аналитики успешно экспортированы',
        icon: 'success',
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
      });
    },

    async loadData() {
      try {
        await Promise.all([
          this.loadRealCounters(),
          this.loadRealTimeData(),
          this.loadStrategicData()
        ]);
      } catch (error) {
        console.error('Ошибка загрузки данных аналитики:', error);
        this.showErrorNotification('Не удалось загрузить данные аналитики');
      }
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
  position: relative;
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

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
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
