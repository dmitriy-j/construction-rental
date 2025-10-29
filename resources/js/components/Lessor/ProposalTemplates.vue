<template>
  <div class="proposal-templates">
    <!-- Header with Stats -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <h2 class="mb-0">
            <i class="fas fa-file-alt me-2"></i>Шаблоны предложений
          </h2>
          <div>
            <button class="btn btn-outline-secondary me-2" @click="loadTemplates" :disabled="loading">
              <i class="fas fa-refresh" :class="{ 'fa-spin': loading }"></i>
            </button>
            <button class="btn btn-primary" @click="showCreateModal = true">
              <i class="fas fa-plus-circle me-1"></i> Создать шаблон
            </button>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mt-3">
          <div v-for="stat in statsCards" :key="stat.title" class="col-md-3">
            <div class="card stat-card h-100">
              <div class="card-body text-center">
                <div class="stat-icon mb-2" :class="stat.color">
                  <i :class="stat.icon"></i>
                </div>
                <h5 class="card-title mb-1">{{ stat.value }}</h5>
                <p class="card-text small text-muted">{{ stat.title }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- A/B Tests Active Section -->
    <div class="row mb-4" v-if="activeAbTests.length > 0">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-warning text-dark">
            <h6 class="mb-0">
              <i class="fas fa-flask me-2"></i>Активные A/B тесты
            </h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div v-for="test in activeAbTests" :key="test.id" class="col-md-6 mb-3">
                <div class="ab-test-card p-3 border rounded">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">{{ test.name }}</h6>
                    <span class="badge bg-warning">A/B тест</span>
                  </div>
                  <div class="ab-test-progress mb-2">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                      <span>Длительность: {{ getTestDuration(test) }}</span>
                      <span>{{ test.ab_test_variants?.length || 0 }} вариантов</span>
                    </div>
                  </div>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" @click="viewAbTestStats(test)">
                      <i class="fas fa-chart-bar me-1"></i>Статистика
                    </button>
                    <button class="btn btn-outline-success" @click="stopAbTest(test)">
                      <i class="fas fa-stop me-1"></i>Остановить
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Категория</label>
            <select v-model="filters.category_id" class="form-select" @change="loadTemplates">
              <option value="">Все категории</option>
              <option v-for="category in availableCategories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Статус</label>
            <select v-model="filters.status" class="form-select" @change="loadTemplates">
              <option value="">Все</option>
              <option value="active">Активные</option>
              <option value="inactive">Неактивные</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Тип</label>
            <select v-model="filters.ab_test" class="form-select" @change="loadTemplates">
              <option value="">Все шаблоны</option>
              <option value="active">A/B тесты</option>
              <option value="without">Без A/B тестов</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Поиск</label>
            <div class="input-group">
              <input type="text" class="form-control" placeholder="Название шаблона..."
                     v-model="filters.search" @keyup.enter="loadTemplates">
              <button class="btn btn-outline-secondary" type="button" @click="loadTemplates">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Templates List -->
    <div class="row" v-if="templates.length > 0">
      <div v-for="template in templates" :key="template.id" class="col-lg-6 mb-4">
        <div class="card template-card h-100" :class="{
          'border-warning': !template.is_active,
          'border-success': template.is_ab_test
        }">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">{{ template.name }}</h6>
            <div class="d-flex align-items-center">
              <span v-if="template.is_ab_test" class="badge bg-success me-2">
                <i class="fas fa-flask me-1"></i>A/B тест
              </span>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox"
                       v-model="template.is_active"
                       @change="updateTemplateStatus(template)">
              </div>
            </div>
          </div>

          <div class="card-body">
            <div class="mb-2">
              <span class="badge bg-secondary">{{ getCategoryName(template.category_id) }}</span>
              <span v-if="!template.is_active" class="badge bg-warning ms-1">Неактивен</span>
              <span v-if="template.is_ab_test" class="badge bg-info ms-1">
                {{ template.ab_test_variants?.length || 0 }} вариантов
              </span>
            </div>

            <p class="card-text text-muted small" v-if="template.description">
              {{ template.description }}
            </p>

            <div class="template-info mb-3">
              <div class="price-info">
                <strong class="text-primary">{{ formatCurrency(template.proposed_price) }}/час</strong>
                <small class="text-muted d-block">Время ответа: {{ template.response_time }}ч</small>
              </div>
            </div>

            <div class="template-stats">
              <div class="stat-item">
                <strong>{{ template.usage_count || 0 }}</strong>
                <small class="text-muted">Использований</small>
              </div>
              <div class="stat-item">
                <strong :class="getSuccessRateClass(template.success_rate)">
                  {{ template.success_rate || 0 }}%
                </strong>
                <small class="text-muted">Успех</small>
              </div>
            </div>

            <!-- A/B Test Variants Preview -->
            <div v-if="template.is_ab_test && template.ab_test_variants" class="ab-variants-preview mt-3">
              <h6 class="small text-muted mb-2">Варианты теста:</h6>
              <div class="variant-previews">
                <div v-for="(variant, index) in template.ab_test_variants.slice(0, 2)"
                     :key="index" class="variant-preview small text-muted mb-1">
                  <i class="fas fa-cube me-1"></i>{{ variant.name }}
                  <span class="ms-2">{{ formatCurrency(variant.proposed_price) }}/час</span>
                </div>
                <div v-if="template.ab_test_variants.length > 2" class="text-muted small">
                  + еще {{ template.ab_test_variants.length - 2 }} вариантов
                </div>
              </div>
            </div>

            <div class="message-preview mt-3 p-2 bg-light rounded small">
              {{ truncateMessage(template.message) }}
            </div>
          </div>

          <div class="card-footer bg-transparent">
            <div class="btn-group w-100">
              <button class="btn btn-outline-primary btn-sm" @click="editTemplate(template)" title="Редактировать">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-outline-success btn-sm" @click="quickApply(template)" title="Быстрое применение">
                <i class="fas fa-bolt"></i>
              </button>
              <button v-if="!template.is_ab_test" class="btn btn-outline-warning btn-sm"
                      @click="startAbTest(template)" title="Запустить A/B тест">
                <i class="fas fa-flask"></i>
              </button>
              <button v-else class="btn btn-outline-info btn-sm"
                      @click="viewAbTestStats(template)" title="Статистика A/B теста">
                <i class="fas fa-chart-bar"></i>
              </button>
              <button class="btn btn-outline-secondary btn-sm" @click="duplicateTemplate(template)" title="Дублировать">
                <i class="fas fa-copy"></i>
              </button>
              <button class="btn btn-outline-danger btn-sm" @click="deleteTemplate(template)" title="Удалить">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5">
      <div class="empty-state">
        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
        <h5>Шаблоны не найдены</h5>
        <p class="text-muted">Создайте свой первый шаблон предложения</p>
        <button class="btn btn-primary" @click="showCreateModal = true">
          <i class="fas fa-plus me-1"></i>Создать шаблон
        </button>
      </div>
    </div>

    <!-- Обертка для модальных окон -->
    <div class="content-modal-wrapper">
      <!-- Create/Edit Modal - Bootstrap -->
      <div class="modal fade" :class="{ 'show d-block': showCreateModal }" v-if="showCreateModal" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                {{ editingTemplate ? 'Редактирование шаблона' : 'Создание шаблона' }}
              </h5>
              <button type="button" class="btn-close" @click="closeModal"></button>
            </div>

            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
              <!-- Предупреждение о необходимости вариантов для A/B теста -->
              <div v-if="form.is_ab_test && (!form.ab_test_variants || form.ab_test_variants.length < 2)"
                   class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Для A/B теста необходимо как минимум 2 варианта
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Название шаблона *</label>
                    <input type="text" class="form-control" v-model="form.name" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Категория *</label>
                    <select class="form-select" v-model="form.category_id" required>
                      <option value="">Выберите категорию</option>
                      <option v-for="category in availableCategories" :key="category.id" :value="category.id">
                        {{ category.name }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Описание</label>
                <textarea class="form-control" rows="2" v-model="form.description"
                          placeholder="Краткое описание шаблона..."></textarea>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Предлагаемая цена (₽/час) *</label>
                    <input type="number" step="0.01" class="form-control" v-model="form.proposed_price" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Время ответа (часы) *</label>
                    <input type="number" class="form-control" v-model="form.response_time" min="1" max="168" required>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Текст сообщения *</label>
                <textarea class="form-control" rows="4" v-model="form.message" required
                          placeholder="Текст предложения для арендатора..."></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label">Дополнительные условия</label>
                <textarea class="form-control" rows="3" v-model="form.additional_terms"
                          placeholder="Дополнительные условия аренды..."></textarea>
              </div>

              <!-- A/B Testing Section -->
              <div class="ab-test-section border-top pt-3 mt-3">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" v-model="form.is_ab_test"
                         id="abTestToggle">
                  <label class="form-check-label fw-bold" for="abTestToggle">
                    Включить A/B тестирование
                  </label>
                </div>

                <div v-if="form.is_ab_test" class="ab-test-config bg-light p-3 rounded">
                  <h6 class="mb-3">
                    <i class="fas fa-flask me-2 text-warning"></i>Настройки A/B теста
                  </h6>

                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Распределение трафика</label>
                      <select class="form-select" v-model="form.test_distribution">
                        <option value="50-50">50/50 (два варианта)</option>
                        <option value="33-33-33">33/33/33 (три варианта)</option>
                        <option value="25-25-25-25">25/25/25/25 (четыре варианта)</option>
                        <option value="custom">Произвольное</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Метрика успеха</label>
                      <select class="form-select" v-model="form.test_metric">
                        <option value="conversion">Конверсия в сделку</option>
                        <option value="price">Максимальная цена</option>
                        <option value="speed">Скорость ответа</option>
                      </select>
                    </div>
                  </div>

                  <div class="variants-section">
                    <h6 class="mb-3">Варианты тестирования</h6>

                    <div class="variant-list">
                      <div v-for="(variant, index) in form.ab_test_variants"
                           :key="index"
                           class="variant-card card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                          <h6 class="mb-0">Вариант {{ String.fromCharCode(65 + index) }}</h6>
                          <button type="button" class="btn btn-danger btn-sm"
                                  @click="removeVariant(index)"
                                  :disabled="form.ab_test_variants.length <= 2">
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                        <div class="card-body">
                          <div class="row g-2">
                            <div class="col-md-6">
                              <label class="form-label small">Название варианта *</label>
                              <input type="text" class="form-control form-control-sm"
                                     v-model="variant.name"
                                     placeholder="e.g., Вариант A"
                                     required>
                            </div>
                            <div class="col-md-6">
                              <label class="form-label small">Цена (₽/час) *</label>
                              <input type="number" step="0.01" class="form-control form-control-sm"
                                     v-model="variant.proposed_price"
                                     required>
                            </div>
                            <div class="col-12">
                              <label class="form-label small">Текст сообщения *</label>
                              <textarea class="form-control form-control-sm" rows="3"
                                        v-model="variant.message"
                                        placeholder="Текст предложения для этого варианта..."
                                        required></textarea>
                            </div>
                            <div class="col-12">
                              <label class="form-label small">Дополнительные условия</label>
                              <textarea class="form-control form-control-sm" rows="2"
                                        v-model="variant.additional_terms"
                                        placeholder="Дополнительные условия для этого варианта..."></textarea>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm"
                            @click="addVariant"
                            :disabled="form.ab_test_variants.length >= 4">
                      <i class="fas fa-plus me-1"></i>Добавить вариант
                    </button>
                  </div>
                </div>
              </div>

              <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" v-model="form.is_active">
                <label class="form-check-label">Активный шаблон</label>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeModal">Отмена</button>
              <button type="button" class="btn btn-primary" @click="saveTemplate" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                {{ editingTemplate ? 'Обновить' : 'Создать' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Apply Modal - Bootstrap -->
      <div class="modal fade" :class="{ 'show d-block': showQuickApplyModal }" v-if="showQuickApplyModal" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Быстрое применение шаблона</h5>
              <button type="button" class="btn-close" @click="showQuickApplyModal = false"></button>
            </div>
            <div class="modal-body">
              <p>Применить шаблон <strong>"{{ selectedTemplate?.name }}"</strong>?</p>
              <p class="text-muted small">Цена: {{ formatCurrency(selectedTemplate?.proposed_price) }}/час</p>
              <div v-if="selectedTemplate?.is_ab_test" class="alert alert-warning small">
                <i class="fas fa-flask me-1"></i>
                Этот шаблон участвует в A/B тесте. Будет выбран случайный вариант.
              </div>
              <div class="alert alert-info small">
                <i class="fas fa-info-circle me-1"></i>
                Шаблон будет применен к текущей заявке с автоматическим заполнением данных
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showQuickApplyModal = false">Отмена</button>
              <button type="button" class="btn btn-primary" @click="confirmQuickApply">
                Применить
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- A/B Test Stats Modal -->
      <div class="modal fade" :class="{ 'show d-block': showAbStatsModal }" v-if="showAbStatsModal" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="fas fa-chart-bar me-2"></i>Статистика A/B теста
              </h5>
              <button type="button" class="btn-close" @click="showAbStatsModal = false"></button>
            </div>
            <div class="modal-body">
              <div v-if="abTestStats" class="ab-test-stats">
                <div class="row mb-4">
                  <div class="col-md-6">
                    <h6>{{ selectedTemplate?.name }}</h6>
                    <p class="text-muted small mb-0">
                      Длительность: {{ abTestStats.total_duration }}
                    </p>
                  </div>
                  <div class="col-md-6 text-end">
                    <span class="badge bg-success me-2">
                      Стат. значимость: {{ abTestStats.statistical_significance }}%
                    </span>
                    <button class="btn btn-outline-danger btn-sm" @click="stopAbTest(selectedTemplate)">
                      Остановить тест
                    </button>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Вариант</th>
                        <th>Показы</th>
                        <th>Применения</th>
                        <th>Конверсии</th>
                        <th>Конверсия</th>
                        <th>Ср. цена</th>
                        <th>Действия</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(variant, index) in abTestStats.variants"
                          :key="index"
                          :class="{ 'table-success': variant.is_winner }">
                        <td>
                          <strong>{{ variant.name }}</strong>
                          <span v-if="variant.is_winner" class="badge bg-success ms-2">Победитель</span>
                        </td>
                        <td>{{ variant.impressions }}</td>
                        <td>{{ variant.applications }}</td>
                        <td>{{ variant.conversions }}</td>
                        <td>
                          <span :class="getConversionRateClass(variant.conversion_rate)">
                            {{ variant.conversion_rate }}%
                          </span>
                        </td>
                        <td>{{ formatCurrency(variant.average_price) }}</td>
                        <td>
                          <button v-if="!variant.is_winner && abTestStats.statistical_significance > 95"
                                  class="btn btn-success btn-sm"
                                  @click="declareWinner(index)">
                            Выбрать победителем
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="row mt-4">
                  <div class="col-md-6">
                    <h6>Метрики эффективности</h6>
                    <div class="metrics-grid">
                      <div class="metric-item">
                        <span class="metric-label">Общие показы:</span>
                        <span class="metric-value">{{ abTestStats.total_impressions }}</span>
                      </div>
                      <div class="metric-item">
                        <span class="metric-label">Общие применения:</span>
                        <span class="metric-value">{{ abTestStats.total_applications }}</span>
                      </div>
                      <div class="metric-item">
                        <span class="metric-label">Общая конверсия:</span>
                        <span class="metric-value">{{ abTestStats.total_conversion_rate }}%</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <h6>Рекомендации</h6>
                    <div class="alert" :class="getRecommendationClass(abTestStats.recommendation)">
                      {{ abTestStats.recommendation }}
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Загрузка статистики...</p>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showAbStatsModal = false">Закрыть</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue'

export default {
  name: 'ProposalTemplates',
  props: {
    categories: {
      type: Array,
      default: () => []
    },
    rentalRequestId: {
      type: Number,
      default: null
    }
  },
  emits: ['template-applied'],
  setup(props, { emit }) {
    console.log('🔄 ProposalTemplates setup started')
    console.log('📦 Получены категории из props:', props.categories)

    const templates = ref([])
    const loading = ref(false)
    const saving = ref(false)

    const showCreateModal = ref(false)
    const showQuickApplyModal = ref(false)
    const showAbStatsModal = ref(false)
    const editingTemplate = ref(null)
    const selectedTemplate = ref(null)
    const abTestStats = ref(null)

    const filters = ref({
      category_id: '',
      status: '',
      search: '',
      ab_test: ''
    })

    const form = ref({
      name: '',
      description: '',
      category_id: '',
      proposed_price: '',
      response_time: 24,
      message: '',
      additional_terms: '',
      is_active: true,
      // 🔥 Новые поля для A/B тестирования
      is_ab_test: false,
      ab_test_variants: [],
      test_distribution: '50-50',
      test_metric: 'conversion'
    })

    // 🔥 ВЫЧИСЛЯЕМ АКТИВНЫЕ A/B ТЕСТЫ
    const activeAbTests = computed(() => {
      return templates.value.filter(template =>
        template.is_ab_test && template.ab_test_status === 'active'
      )
    })

    // 🔥 УПРОЩЕННЫЙ ПОДХОД: используем только категории из props
    const availableCategories = computed(() => {
      console.log('📋 Доступные категории:', props.categories)
      return props.categories || []
    })

    // 🔥 ВЫЧИСЛЯЕМ СТАТИСТИКУ НА ОСНОВЕ ШАБЛОНОВ
    const computedStats = computed(() => {
      const totalTemplates = templates.value.length
      const totalUsage = templates.value.reduce((sum, template) => sum + (template.usage_count || 0), 0)
      const activeAbTestsCount = activeAbTests.value.length

      const templatesWithUsage = templates.value.filter(t => t.usage_count > 0)
      const averageSuccessRate = templatesWithUsage.length > 0
        ? templatesWithUsage.reduce((sum, template) => sum + (template.success_rate || 0), 0) / templatesWithUsage.length
        : 0

      const timeSaved = totalUsage * 0.5

      return {
        total_templates: totalTemplates,
        total_usage: totalUsage,
        average_success_rate: Math.round(averageSuccessRate * 10) / 10,
        time_saved: timeSaved,
        active_ab_tests: activeAbTestsCount
      }
    })

    const statsCards = computed(() => [
      {
        title: 'Всего шаблонов',
        value: computedStats.value.total_templates || 0,
        icon: 'fas fa-file-alt',
        color: 'text-primary'
      },
      {
        title: 'Средняя успешность',
        value: `${computedStats.value.average_success_rate || 0}%`,
        icon: 'fas fa-chart-line',
        color: 'text-success'
      },
      {
        title: 'Всего применений',
        value: computedStats.value.total_usage || 0,
        icon: 'fas fa-bolt',
        color: 'text-warning'
      },
      {
        title: 'A/B тесты',
        value: computedStats.value.active_ab_tests || 0,
        icon: 'fas fa-flask',
        color: 'text-info'
      }
    ])

    // 🔥 МЕТОДЫ ДЛЯ A/B ТЕСТИРОВАНИЯ
    const addVariant = () => {
      if (form.value.ab_test_variants.length < 4) {
        form.value.ab_test_variants.push({
          name: `Вариант ${String.fromCharCode(65 + form.value.ab_test_variants.length)}`,
          message: form.value.message,
          proposed_price: form.value.proposed_price,
          additional_terms: form.value.additional_terms,
          response_time: form.value.response_time
        })
      }
    }

    const removeVariant = (index) => {
      if (form.value.ab_test_variants.length > 2) {
        form.value.ab_test_variants.splice(index, 1)
      }
    }

    const startAbTest = async (template) => {
      if (confirm(`Запустить A/B тест для шаблона "${template.name}"?`)) {
        try {
          console.log('🚀 Запуск A/B теста для шаблона:', template.id)

          const response = await axios.post(`/api/lessor/proposal-templates/${template.id}/start-ab-test`)

          if (response.data.success) {
            alert('✅ A/B тест успешно запущен!')
            await loadTemplates()
          } else {
            alert('❌ Ошибка: ' + response.data.message)
          }
        } catch (error) {
          console.error('❌ Ошибка запуска A/B теста:', error)
          console.error('📊 Ответ сервера:', error.response?.data)

          let errorMessage = 'Неизвестная ошибка'
          if (error.response?.data?.message) {
            errorMessage = error.response.data.message
          } else if (error.message) {
            errorMessage = error.message
          }

          alert('❌ Ошибка запуска A/B теста: ' + errorMessage)
        }
      }
    }

    const stopAbTest = async (template) => {
      if (confirm(`Остановить A/B тест для шаблона "${template.name}"?`)) {
        try {
          const response = await axios.post(`/api/lessor/proposal-templates/${template.id}/stop-ab-test`)
          await loadTemplates()
          showAbStatsModal.value = false
          alert('✅ A/B тест остановлен!')
        } catch (error) {
          console.error('❌ Ошибка остановки A/B теста:', error)
          alert('❌ Ошибка остановки A/B теста: ' + (error.response?.data?.message || error.message))
        }
      }
    }

    const viewAbTestStats = async (template) => {
      selectedTemplate.value = template
      showAbStatsModal.value = true

      try {
        const response = await axios.get(`/api/lessor/proposal-templates/${template.id}/ab-test-stats`)
        abTestStats.value = response.data.data
      } catch (error) {
        console.error('❌ Ошибка загрузки статистики A/B теста:', error)
        alert('❌ Ошибка загрузки статистики: ' + (error.response?.data?.message || error.message))
      }
    }

    const declareWinner = async (variantIndex) => {
      if (confirm(`Выбрать этот вариант победителем A/B теста?`)) {
        try {
          const response = await axios.post(`/api/lessor/proposal-templates/${selectedTemplate.value.id}/declare-winner`, {
            winner_index: variantIndex
          })
          await loadTemplates()
          showAbStatsModal.value = false
          alert('✅ Победитель A/B теста выбран! Шаблон обновлен.')
        } catch (error) {
          console.error('❌ Ошибка выбора победителя:', error)
          alert('❌ Ошибка выбора победителя: ' + (error.response?.data?.message || error.message))
        }
      }
    }

    const getTestDuration = (template) => {
      if (!template.ab_test_started_at) return '0 дней'
      const start = new Date(template.ab_test_started_at)
      const now = new Date()
      const diffTime = Math.abs(now - start)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return `${diffDays} дней`
    }

    const getConversionRateClass = (rate) => {
      if (rate >= 30) return 'text-success fw-bold'
      if (rate >= 15) return 'text-warning'
      return 'text-danger'
    }

    const getRecommendationClass = (recommendation) => {
      if (recommendation?.includes('продолжить')) return 'alert-warning'
      if (recommendation?.includes('остановить')) return 'alert-success'
      return 'alert-info'
    }

    // 🔥 ОБНОВЛЕННЫЙ МЕТОД СОХРАНЕНИЯ ШАБЛОНА
    const saveTemplate = async () => {
      // 🔥 ПРОВЕРКА A/B ТЕСТА
      if (form.value.is_ab_test) {
        if (!form.value.ab_test_variants || form.value.ab_test_variants.length < 2) {
          alert('Для A/B теста необходимо как минимум 2 варианта')
          return
        }

        // Проверяем, что все варианты заполнены
        for (let i = 0; i < form.value.ab_test_variants.length; i++) {
          const variant = form.value.ab_test_variants[i]
          if (!variant.name?.trim() || !variant.message?.trim() || !variant.proposed_price) {
            alert(`Заполните все поля для варианта ${String.fromCharCode(65 + i)}`)
            return
          }
        }
      }

      // Остальная логика валидации...
      if (!form.value.name?.trim()) {
        alert('Пожалуйста, введите название шаблона')
        return
      }
      if (!form.value.category_id) {
        alert('Пожалуйста, выберите категорию')
        return
      }
      if (!form.value.proposed_price || form.value.proposed_price <= 0) {
        alert('Пожалуйста, укажите корректную цену (больше 0)')
        return
      }
      if (!form.value.message?.trim()) {
        alert('Пожалуйста, введите текст сообщения')
        return
      }

      saving.value = true

      try {
        console.log('💾 Начало сохранения шаблона...')

        // 🔥 ВАЖНО: Подготавливаем данные для отправки
        const formData = {
          name: form.value.name,
          description: form.value.description,
          category_id: form.value.category_id,
          proposed_price: form.value.proposed_price,
          response_time: form.value.response_time,
          message: form.value.message,
          additional_terms: form.value.additional_terms,
          is_active: form.value.is_active,
          // 🔥 КРИТИЧЕСКИ ВАЖНО: Всегда отправляем поля A/B теста
          is_ab_test: form.value.is_ab_test,
          ab_test_variants: form.value.ab_test_variants || [],
          test_distribution: form.value.test_distribution,
          test_metric: form.value.test_metric
        }

        console.log('📋 Данные для отправки:', JSON.stringify(formData, null, 2))

        let response
        if (editingTemplate.value) {
          console.log('📝 Обновление шаблона:', editingTemplate.value.id)
          response = await axios.put(`/api/lessor/proposal-templates/${editingTemplate.value.id}`, formData)
          console.log('✅ Шаблон успешно обновлен:', response.data)
        } else {
          console.log('🆕 Создание нового шаблона')
          response = await axios.post('/api/lessor/proposal-templates', formData)
          console.log('✅ Шаблон успешно создан:', response.data)
        }

        closeModal()
        await loadTemplates()

        alert('✅ Шаблон успешно сохранен!')

      } catch (error) {
        console.error('❌ ПОЛНАЯ ОШИБКА СОХРАНЕНИЯ ШАБЛОНА:', error)

        let errorMessage = 'Неизвестная ошибка при сохранении шаблона'

        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.errors) {
          const validationErrors = Object.values(error.response.data.errors).flat()
          errorMessage = 'Ошибки валидации: ' + validationErrors.join(', ')
        } else if (error.code === 'NETWORK_ERROR') {
          errorMessage = 'Ошибка сети. Проверьте подключение к интернету.'
        } else if (error.response?.status === 422) {
          errorMessage = 'Ошибка валидации данных. Проверьте правильность заполнения полей.'
        } else {
          errorMessage = error.message || 'Неизвестная ошибка'
        }

        alert(`❌ Ошибка сохранения шаблона: ${errorMessage}`)

      } finally {
        saving.value = false
      }
    }

    // 🔥 ОБНОВЛЕННЫЙ МЕТОД РЕДАКТИРОВАНИЯ ШАБЛОНА
    const editTemplate = (template) => {
      console.log('✏️ Редактирование шаблона:', template)
      editingTemplate.value = template
      form.value = {
        ...template,
        ab_test_variants: template.ab_test_variants || []
      }

      // 🔥 Если это A/B тест, добавляем базовые варианты если их нет
      if (form.value.is_ab_test && (!form.value.ab_test_variants || form.value.ab_test_variants.length === 0)) {
        form.value.ab_test_variants = [
          {
            name: 'Вариант A',
            message: template.message,
            proposed_price: template.proposed_price,
            additional_terms: template.additional_terms,
            response_time: template.response_time
          },
          {
            name: 'Вариант B',
            message: template.message,
            proposed_price: template.proposed_price * 0.9, // -10%
            additional_terms: template.additional_terms,
            response_time: template.response_time
          }
        ]
      }

      showCreateModal.value = true
    }

    // 🔥 ОБНОВЛЕННЫЙ МЕТОД ЗАКРЫТИЯ МОДАЛЬНОГО ОКНА
    const closeModal = () => {
      console.log('🚪 Закрытие модального окна')
      showCreateModal.value = false
      editingTemplate.value = null
      form.value = {
        name: '',
        description: '',
        category_id: '',
        proposed_price: '',
        response_time: 24,
        message: '',
        additional_terms: '',
        is_active: true,
        is_ab_test: false,
        ab_test_variants: [],
        test_distribution: '50-50',
        test_metric: 'conversion'
      }
    }

    // 🔥 ОСТАЛЬНЫЕ МЕТОДЫ
    const getCategoryName = (categoryId) => {
      if (!categoryId) return 'Без категории'
      const category = availableCategories.value.find(cat => cat.id === categoryId)
      return category?.name || 'Категория не найдена'
    }

    const formatCurrency = (amount) => {
      if (!amount && amount !== 0) return '0 ₽'
      try {
        return new Intl.NumberFormat('ru-RU', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 2
        }).format(amount) + ' ₽'
      } catch (error) {
        console.error('Ошибка форматирования валюты:', error)
        return '0 ₽'
      }
    }

    const loadTemplates = async () => {
      loading.value = true
      try {
        console.log('📥 Загрузка шаблонов с фильтрами:', filters.value)
        const response = await axios.get('/api/lessor/proposal-templates', {
          params: filters.value
        })
        console.log('✅ Шаблоны загружены:', response.data.data.map(t => ({
          id: t.id,
          name: t.name,
          is_ab_test: t.is_ab_test,
          ab_test_variants: t.ab_test_variants,
          variants_count: t.ab_test_variants ? t.ab_test_variants.length : 0
        })))
        templates.value = response.data.data || []
      } catch (error) {
        console.error('❌ Ошибка загрузки шаблонов:', error)
        alert('Ошибка загрузки шаблонов: ' + error.message)
      } finally {
        loading.value = false
      }
    }

    const duplicateTemplate = async (template) => {
      try {
        console.log('📋 Дублирование шаблона:', template.id)
        const response = await axios.post('/api/lessor/proposal-templates', {
          ...template,
          name: `${template.name} (копия)`,
          usage_count: 0,
          success_rate: 0,
          is_ab_test: false, // 🔥 Сбрасываем A/B тест при дублировании
          ab_test_variants: []
        })
        await loadTemplates()
        alert('✅ Шаблон успешно дублирован!')
      } catch (error) {
        console.error('❌ Ошибка дублирования шаблона:', error)
        alert('❌ Ошибка дублирования шаблона: ' + (error.response?.data?.message || error.message))
      }
    }

    const deleteTemplate = async (template) => {
      if (confirm(`Удалить шаблон "${template.name}"?`)) {
        try {
          console.log('🗑️ Удаление шаблона:', template.id)
          await axios.delete(`/api/lessor/proposal-templates/${template.id}`)
          await loadTemplates()
          alert('✅ Шаблон успешно удален!')
        } catch (error) {
          console.error('❌ Ошибка удаления шаблона:', error)
          alert('❌ Ошибка удаления шаблона: ' + (error.response?.data?.message || error.message))
        }
      }
    }

    const updateTemplateStatus = async (template) => {
      try {
        console.log('🔄 Обновление статуса шаблона:', template.id, 'новый статус:', template.is_active)
        await axios.put(`/api/lessor/proposal-templates/${template.id}`, {
          is_active: template.is_active
        })
        alert('✅ Статус шаблона обновлен!')
      } catch (error) {
        console.error('❌ Ошибка обновления статуса:', error)
        template.is_active = !template.is_active
        alert('❌ Ошибка обновления статуса: ' + (error.response?.data?.message || error.message))
      }
    }

    const quickApply = (template) => {
      console.log('⚡ Быстрое применение шаблона:', template.id)
      selectedTemplate.value = template
      showQuickApplyModal.value = true
    }

    const confirmQuickApply = async () => {
      try {
        console.log('✅ Подтверждение быстрого применения:', selectedTemplate.value)

        emit('template-applied', {
          template: selectedTemplate.value,
          data: {
            message: selectedTemplate.value.message,
            proposed_price: selectedTemplate.value.proposed_price,
            response_time: selectedTemplate.value.response_time,
            additional_terms: selectedTemplate.value.additional_terms,
            // 🔥 Добавляем информацию об A/B тесте
            is_ab_test: selectedTemplate.value.is_ab_test,
            ab_test_variants: selectedTemplate.value.ab_test_variants
          }
        })

        showQuickApplyModal.value = false
        alert('✅ Шаблон успешно применен!')
      } catch (error) {
        console.error('❌ Ошибка применения шаблона:', error)
        alert('❌ Ошибка применения шаблона: ' + error.message)
      }
    }

    const truncateMessage = (message) => {
      if (!message) return 'Текст сообщения не указан'
      return message.length > 150 ? message.substring(0, 150) + '...' : message
    }

    const getSuccessRateClass = (rate) => {
      if (rate >= 70) return 'text-success'
      if (rate >= 40) return 'text-warning'
      return 'text-danger'
    }

    // 🔥 УПРАВЛЕНИЕ SCROLL ДЛЯ BOOTSTRAP МОДАЛЬНЫХ ОКОН
    watch(showCreateModal, (newVal) => {
      if (newVal) {
        document.body.classList.add('modal-open')
        document.body.style.overflow = 'hidden'
        document.body.style.paddingRight = '15px'
      } else {
        document.body.classList.remove('modal-open')
        document.body.style.overflow = ''
        document.body.style.paddingRight = ''
      }
    })

    watch(showQuickApplyModal, (newVal) => {
      if (newVal) {
        document.body.classList.add('modal-open')
        document.body.style.overflow = 'hidden'
        document.body.style.paddingRight = '15px'
      } else {
        document.body.classList.remove('modal-open')
        document.body.style.overflow = ''
        document.body.style.paddingRight = ''
      }
    })

    watch(showAbStatsModal, (newVal) => {
      if (newVal) {
        document.body.classList.add('modal-open')
        document.body.style.overflow = 'hidden'
        document.body.style.paddingRight = '15px'
      } else {
        document.body.classList.remove('modal-open')
        document.body.style.overflow = ''
        document.body.style.paddingRight = ''
      }
    })

    // 🔥 WATCH ДЛЯ АВТОМАТИЧЕСКОГО ДОБАВЛЕНИЯ ВАРИАНТОВ ПРИ ВКЛЮЧЕНИИ A/B ТЕСТА
    watch(() => form.value.is_ab_test, (newVal) => {
      if (newVal && (!form.value.ab_test_variants || form.value.ab_test_variants.length === 0)) {
        form.value.ab_test_variants = [
          {
            name: 'Вариант A',
            message: form.value.message,
            proposed_price: form.value.proposed_price,
            additional_terms: form.value.additional_terms,
            response_time: form.value.response_time
          },
          {
            name: 'Вариант B',
            message: form.value.message,
            proposed_price: form.value.proposed_price * 0.9,
            additional_terms: form.value.additional_terms,
            response_time: form.value.response_time
          }
        ]
      }
    })

    onMounted(() => {
      console.log('✅ ProposalTemplates mounted successfully')
      loadTemplates()
    })

    return {
      templates,
      availableCategories,
      loading,
      saving,
      statsCards,
      filters,
      form,
      showCreateModal,
      showQuickApplyModal,
      showAbStatsModal,
      editingTemplate,
      selectedTemplate,
      abTestStats,
      activeAbTests,
      loadTemplates,
      saveTemplate,
      editTemplate,
      duplicateTemplate,
      deleteTemplate,
      updateTemplateStatus,
      quickApply,
      confirmQuickApply,
      closeModal,
      truncateMessage,
      getSuccessRateClass,
      getCategoryName,
      formatCurrency,
      // 🔥 A/B тестирование методы
      addVariant,
      removeVariant,
      startAbTest,
      stopAbTest,
      viewAbTestStats,
      declareWinner,
      getTestDuration,
      getConversionRateClass,
      getRecommendationClass
    }
  }
}
</script>

<style scoped>
.proposal-templates {
  padding: 20px;
}

.template-card {
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
}

.template-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.template-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-top: 12px;
}

.stat-item {
  text-align: center;
  padding: 8px;
  background: #f8f9fa;
  border-radius: 4px;
}

.stat-item strong {
  display: block;
  font-size: 1.1em;
}

.stat-card {
  border: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card .card-body {
  padding: 1.5rem 1rem;
}

.stat-icon {
  font-size: 1.5rem;
}

.message-preview {
  font-size: 0.85rem;
  line-height: 1.4;
  color: #6c757d;
}

.empty-state {
  padding: 3rem 1rem;
}

.price-info {
  padding: 12px;
  background: #f8f9fa;
  border-radius: 6px;
  border-left: 3px solid #0d6efd;
}

/* 🔥 СТИЛИ ДЛЯ A/B ТЕСТИРОВАНИЯ */
.ab-test-card {
  background: #fffaf0;
  border: 1px solid #ffeaa7;
}

.ab-test-section {
  border-top: 2px solid #f8f9fa;
}

.variant-card {
  border: 1px solid #e9ecef;
}

.variant-card .card-header {
  background: #f8f9fa;
  padding: 0.75rem 1rem;
}

.variant-previews {
  max-height: 100px;
  overflow-y: auto;
}

.variant-preview {
  padding: 4px 8px;
  background: #f8f9fa;
  border-radius: 4px;
  border-left: 3px solid #6c757d;
}

.ab-variants-preview {
  border-top: 1px solid #e9ecef;
  padding-top: 12px;
}

/* Статистика A/B тестов */
.metrics-grid {
  display: grid;
  gap: 8px;
}

.metric-item {
  display: flex;
  justify-content: between;
  padding: 8px;
  background: #f8f9fa;
  border-radius: 4px;
}

.metric-label {
  flex: 1;
  font-weight: 500;
}

.metric-value {
  font-weight: bold;
  color: #0d6efd;
}

/* Обертка для модальных окон */
.content-modal-wrapper .modal {
  padding-left: 250px;
  z-index: 1060;
}

.content-modal-wrapper .modal-dialog {
  margin: 1rem auto;
  max-width: 90%;
}

.content-modal-wrapper .modal-lg {
  max-width: 800px;
}

.content-modal-wrapper .modal-xl {
  max-width: 1200px;
}

.content-modal-wrapper .modal.show {
  background: rgba(0,0,0,0.5) !important;
}

@media (max-width: 768px) {
  .content-modal-wrapper .modal {
    padding-left: 0;
  }

  .content-modal-wrapper .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }

  .template-stats {
    grid-template-columns: 1fr 1fr;
  }

  .btn-group .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>
