<!-- resources/js/components/Lessor/StrategicAnalytics.vue -->
<template>
  <div class="strategic-analytics">
    <div class="row">
      <!-- Конверсия -->
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="fas fa-chart-line me-2 text-primary"></i>
              Конверсия предложений
            </h6>
          </div>
          <div class="card-body">
            <div class="conversion-metrics">
              <div class="metric-row">
                <div class="metric-label">Ваша конверсия</div>
                <div class="metric-value text-primary">
                  {{ conversionData.myConversionRate }}%
                  <i v-if="conversionData.trend === 'up'" class="fas fa-arrow-up text-success ms-1"></i>
                  <i v-else-if="conversionData.trend === 'down'" class="fas fa-arrow-down text-danger ms-1"></i>
                </div>
              </div>
              <div class="metric-row">
                <div class="metric-label">Средняя по рынку</div>
                <div class="metric-value text-secondary">
                  {{ conversionData.marketConversionRate }}%
                </div>
              </div>
              <div class="progress mt-3" style="height: 8px;">
                <div
                  class="progress-bar bg-primary"
                  :style="{ width: conversionData.myConversionRate + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ценовая аналитика -->
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="fas fa-tag me-2 text-success"></i>
              Ценовая аналитика
            </h6>
          </div>
          <div class="card-body">
            <div class="price-metrics">
              <div class="metric-row">
                <div class="metric-label">Ваша средняя цена</div>
                <div class="metric-value text-success">
                  {{ formatCurrency(priceAnalytics.myAvgPrice) }}
                </div>
              </div>
              <div class="metric-row">
                <div class="metric-label">Средняя по рынку</div>
                <div class="metric-value text-secondary">
                  {{ formatCurrency(priceAnalytics.marketAvgPrice) }}
                </div>
              </div>
              <div class="price-comparison mt-3">
                <div class="comparison-item" :class="getComparisonClass(priceAnalytics.priceDifferencePercent)">
                  <i :class="getComparisonIcon(priceAnalytics.priceDifferencePercent)"></i>
                  {{ getComparisonText(priceAnalytics.priceDifferencePercent) }}
                  на {{ Math.abs(priceAnalytics.priceDifferencePercent) }}%
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Рекомендации -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="fas fa-lightbulb me-2 text-warning"></i>
              Рекомендации для роста
            </h6>
          </div>
          <div class="card-body">
            <div class="recommendations-list">
              <div
                v-for="rec in recommendations"
                :key="rec.id"
                class="recommendation-item"
                :class="'priority-' + rec.priority"
              >
                <div class="recommendation-content">
                  <i :class="rec.icon + ' me-2'"></i>
                  {{ rec.message }}
                </div>
                <div v-if="rec.action" class="recommendation-actions">
                  <button @click="rec.action" class="btn btn-sm btn-outline-primary">
                    {{ rec.actionText || 'Применить' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StrategicAnalytics',
  props: {
    conversionData: {
      type: Object,
      default: () => ({
        myConversionRate: 0,
        marketConversionRate: 0,
        trend: 'stable'
      })
    },
    priceAnalytics: {
      type: Object,
      default: () => ({
        myAvgPrice: 0,
        marketAvgPrice: 0,
        priceDifferencePercent: 0
      })
    },
    recommendations: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    formatCurrency(amount) {
      if (!amount && amount !== 0) return '0 ₽';
      try {
        return new Intl.NumberFormat('ru-RU', {
          style: 'currency',
          currency: 'RUB',
          minimumFractionDigits: 0
        }).format(amount);
      } catch (error) {
        console.error('Ошибка форматирования валюты:', error);
        return '0 ₽';
      }
    },

    getComparisonClass(difference) {
      if (difference > 10) return 'text-success';
      if (difference > -10) return 'text-warning';
      return 'text-danger';
    },

    getComparisonIcon(difference) {
      if (difference > 0) return 'fas fa-arrow-up text-success me-1';
      if (difference < 0) return 'fas fa-arrow-down text-danger me-1';
      return 'fas fa-equals text-secondary me-1';
    },

    getComparisonText(difference) {
      if (difference > 0) return 'выше';
      if (difference < 0) return 'ниже';
      return 'на уровне';
    }
  },
  mounted() {
    console.log('✅ StrategicAnalytics mounted');
  }
}
</script>

<style scoped>
.strategic-analytics {
  animation: fadeIn 0.5s ease-in;
}

.metric-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.metric-label {
  color: #6c757d;
  font-weight: 500;
}

.metric-value {
  font-weight: 600;
  font-size: 1.1rem;
}

.comparison-item {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 500;
  text-align: center;
  background: #f8f9fa;
}

.recommendations-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.recommendation-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid #0d6efd;
  background: #f8f9fa;
  transition: all 0.3s ease;
}

.recommendation-item:hover {
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.recommendation-item.priority-high {
  border-left-color: #dc3545;
  background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%);
}

.recommendation-item.priority-critical {
  border-left-color: #fd7e14;
  background: linear-gradient(135deg, #fff4e6 0%, #ffe8cc 100%);
  animation: pulse 2s infinite;
}

.recommendation-content {
  flex: 1;
  font-weight: 500;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(253, 126, 20, 0); }
  100% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0); }
}

@media (max-width: 768px) {
  .recommendation-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }

  .recommendation-actions {
    align-self: flex-end;
  }
}
</style>
