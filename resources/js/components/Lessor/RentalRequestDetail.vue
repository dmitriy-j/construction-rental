<template>
  <div class="rental-request-detail">
    <!-- Хедер с основной информацией -->
    <div class="request-header card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2 class="card-title mb-2">{{ request.title }}</h2>
            <p class="card-text text-muted mb-3">{{ request.description }}</p>

            <div class="request-meta">
              <div class="row">
                <div class="col-md-6">
                  <div class="meta-item mb-2">
                    <i class="fas fa-ruble-sign text-success me-2"></i>
                    <strong>Бюджет для вас:</strong>
                    <span class="ms-2 text-success fw-bold">
                      {{ formatCurrency(lessorPricing?.total_lessor_budget || 0) }}
                    </span>
                  </div>
                  <div class="meta-item mb-2">
                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                    <strong>Локация:</strong>
                    <span class="ms-2">{{ request.location?.name || 'Не указана' }}</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="meta-item mb-2">
                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                    <strong>Срок аренды:</strong>
                    <span class="ms-2">
                      {{ formatDate(request.rental_period_start) }} - {{ formatDate(request.rental_period_end) }}
                      ({{ calculateRentalDays() }} дней)
                    </span>
                  </div>
                  <div class="meta-item mb-2">
                    <i class="fas fa-truck text-warning me-2"></i>
                    <strong>Доставка:</strong>
                    <span class="ms-2">
                      {{ request.delivery_required ? 'Требуется' : 'Не требуется' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 text-end">
            <div class="action-buttons">
              <button @click="openProposalModal"
                      class="btn btn-primary btn-lg w-100 mb-2">
                <i class="fas fa-paper-plane me-2"></i>
                Предложить технику
              </button>
              <button class="btn btn-outline-secondary w-100 mb-2" @click="addToFavorites">
                <i class="fas fa-star me-2"></i>В избранное
              </button>
              <div class="stats-badges mt-3">
                <span class="badge bg-info me-2">
                  <i class="fas fa-eye me-1"></i>
                  {{ request.views_count || 0 }} просмотров
                </span>
                <span class="badge bg-warning">
                  <i class="fas fa-paper-plane me-1"></i>
                  {{ request.total_proposals_count || 0 }} предложений
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔥 БЛОК УМНЫХ РЕКОМЕНДАЦИЙ -->
    <div class="smart-recommendations card mb-4" v-if="recommendedTemplates.length > 0">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0">
          <i class="fas fa-robot me-2"></i>Умные рекомендации шаблонов
          <span class="badge bg-light text-primary ms-2">{{ recommendedTemplates.length }}</span>
        </h6>
      </div>
      <div class="card-body">
        <div class="recommendations-grid">
          <div v-for="recommendation in recommendedTemplates"
               :key="recommendation.template.id"
               class="recommendation-card"
               :class="'confidence-' + recommendation.confidence_level">
            <div class="recommendation-header d-flex justify-content-between align-items-start mb-2">
              <span class="confidence-badge badge"
                    :class="getConfidenceBadgeClass(recommendation.confidence_level)">
                {{ recommendation.confidence }} ({{ recommendation.score }}%)
              </span>
              <small class="reason text-muted">{{ recommendation.reason }}</small>
            </div>

            <div class="template-preview mb-3">
              <strong class="d-block mb-1">{{ recommendation.template.name }}</strong>
              <div class="price text-success fw-bold mb-1">
                {{ formatCurrency(recommendation.template.proposed_price) }}/час
              </div>
              <div class="stats small text-muted">
                <div class="mb-1">
                  <i class="fas fa-chart-line me-1"></i>
                  Конверсия: {{ recommendation.template.success_rate || 0 }}%
                </div>
                <div>
                  <i class="fas fa-clock me-1"></i>
                  Ответ: {{ recommendation.template.response_time }}ч
                </div>
              </div>
            </div>

            <div class="recommendation-actions d-flex gap-2">
              <button @click="applyRecommendedTemplate(recommendation)"
                      class="btn btn-sm btn-primary flex-fill">
                <i class="fas fa-bolt me-1"></i>Применить
              </button>
              <button @click="viewTemplateDetails(recommendation.template)"
                      class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔥 ЕСЛИ НЕТ РЕКОМЕНДАЦИЙ -->
    <div class="smart-recommendations card mb-4" v-else-if="recommendationsLoaded">
      <div class="card-body text-center py-4">
        <i class="fas fa-robot fa-2x text-muted mb-3"></i>
        <h6 class="text-muted">Анализируем заявку...</h6>
        <p class="text-muted small mb-0">Нужно больше данных для персонализированных рекомендаций</p>
        <button class="btn btn-outline-primary btn-sm mt-2" @click="loadTemplateRecommendations">
          <i class="fas fa-refresh me-1"></i>Попробовать снова
        </button>
      </div>
    </div>

    <!-- Вкладки -->
    <div class="request-tabs card mb-4">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
          <li class="nav-item">
            <button class="nav-link"
                    :class="{ 'active': activeTab === 'info' }"
                    @click="activeTab = 'info'">
              <i class="fas fa-info-circle me-2"></i>
              Информация
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link"
                    :class="{ 'active': activeTab === 'templates' }"
                    @click="activeTab = 'templates'">
              <i class="fas fa-file-alt me-2"></i>
              Шаблоны
              <span v-if="templates.length > 0" class="badge bg-primary ms-1">
                {{ templates.length }}
              </span>
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link"
                    :class="{ 'active': activeTab === 'proposals' }"
                    @click="activeTab = 'proposals'">
              <i class="fas fa-history me-2"></i>
              История предложений
              <span v-if="proposalHistory.length > 0" class="badge bg-info ms-1">
                {{ proposalHistory.length }}
              </span>
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link"
                    :class="{ 'active': activeTab === 'analytics' }"
                    @click="activeTab = 'analytics'">
              <i class="fas fa-chart-bar me-2"></i>
              Аналитика
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link"
                    :class="{ 'active': activeTab === 'recommendations' }"
                    @click="activeTab = 'recommendations'">
              <i class="fas fa-robot me-2"></i>
              Рекомендации
              <span v-if="recommendedTemplates.length > 0" class="badge bg-success ms-1">
                {{ recommendedTemplates.length }}
              </span>
            </button>
          </li>
        </ul>
      </div>

      <!-- Контент вкладок -->
      <div class="card-body">
        <div v-if="activeTab === 'info'" class="tab-content-info">
          <!-- Детальная информация о заявке -->
          <div class="row">
            <div class="col-lg-8">
              <h5 class="mb-3">Технические требования</h5>

              <div v-for="(item, index) in request.items" :key="index" class="position-item card mb-3">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-md-8">
                      <h6 class="card-title">
                        {{ item.category?.name || 'Без категории' }}
                        <span class="badge bg-primary">× {{ item.quantity }}</span>
                      </h6>

                      <div v-if="item.formatted_specifications && item.formatted_specifications.length > 0"
                           class="specifications mt-2">
                        <div v-for="(spec, specIndex) in item.formatted_specifications"
                             :key="specIndex"
                             class="spec-item badge bg-light text-dark me-1 mb-1">
                          {{ spec.formatted || spec }}
                        </div>
                      </div>

                      <div v-else class="text-muted small mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Спецификации не указаны
                      </div>
                    </div>
                    <div class="col-md-4 text-end">
                      <div class="price-estimate">
                        <div class="text-success fw-bold">
                          {{ formatCurrency(calculateItemPrice(item)) }}/час
                        </div>
                        <small class="text-muted">Примерная цена</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="additional-info">
                <h5 class="mb-3">Дополнительная информация</h5>

                <div class="info-section mb-4">
                  <h6 class="text-muted mb-2">Условия аренды</h6>
                  <div v-if="request.rental_conditions" class="conditions-list">
                    <div v-for="(value, key) in request.rental_conditions"
                         :key="key"
                         class="condition-item small mb-1">
                      <strong>{{ formatConditionKey(key) }}:</strong> {{ formatConditionValue(key, value) }}
                    </div>
                  </div>
                  <div v-else class="text-muted small">
                    Условия не указаны
                  </div>
                </div>

                <div class="info-section">
                  <h6 class="text-muted mb-2">Информация о платформе</h6>
                  <div class="platform-info small">
                    <div class="platform-item mb-1">
                      <i class="fas fa-building me-2"></i>
                      <strong>Платформа:</strong> ФАП
                    </div>
                    <div class="platform-item mb-1">
                      <i class="fas fa-user me-2"></i>
                      <strong>Менеджер:</strong> Иван Петров
                    </div>
                    <div class="platform-item mb-1">
                      <i class="fas fa-phone me-2"></i>
                      <strong>Телефон:</strong> +7 (495) 123-45-67
                    </div>
                    <div class="platform-item mb-1">
                      <i class="fas fa-envelope me-2"></i>
                      <strong>Email:</strong> office@fap24.ru
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'templates'" class="tab-content-templates">
          <ProposalTemplates
            :categories="categories"
            :rental-request-id="request.id"
            @template-applied="handleTemplateApplied"
          />
        </div>

        <div v-if="activeTab === 'proposals'" class="tab-content-proposals">
          <h5 class="mb-3">История ваших предложений</h5>

          <div v-if="proposalHistory.length > 0" class="proposals-list">
            <div v-for="(proposal, index) in proposalHistory"
                 :key="index"
                 class="proposal-item card mb-3">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-md-3">
                    <div class="proposal-price">
                      <strong class="text-success fs-5">
                        {{ formatCurrency(proposal.proposed_price) }}/час
                      </strong>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="proposal-equipment">
                      <strong>{{ proposal.equipment_title }}</strong>
                      <div class="small text-muted">
                        {{ formatDate(proposal.created_at) }}
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <span class="badge"
                          :class="getStatusBadgeClass(proposal.status)">
                      {{ getStatusText(proposal.status) }}
                    </span>
                  </div>
                  <div class="col-md-2 text-end">
                    <button class="btn btn-outline-primary btn-sm"
                            @click="viewProposalDetails(proposal)">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-5">
            <div class="empty-state">
              <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
              <h5>Предложений нет</h5>
              <p class="text-muted">Вы еще не отправляли предложений по этой заявке</p>
              <button @click="openProposalModal" class="btn btn-primary">
                <i class="fas fa-paper-plane me-2"></i>
                Сделать предложение
              </button>
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'analytics'" class="tab-content-analytics">
          <h5 class="mb-3">Аналитика по заявке</h5>

          <div class="row">
            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <h6 class="card-title mb-0">Конкуренция</h6>
                </div>
                <div class="card-body">
                  <div class="analytics-item mb-3">
                    <div class="d-flex justify-content-between">
                      <span>Всего предложений:</span>
                      <strong>{{ analytics.total_proposals || 0 }}</strong>
                    </div>
                  </div>
                  <div class="analytics-item mb-3">
                    <div class="d-flex justify-content-between">
                      <span>Ваших предложений:</span>
                      <strong class="text-info">{{ analytics.my_proposals || 0 }}</strong>
                    </div>
                  </div>
                  <div class="analytics-item">
                    <div class="d-flex justify-content-between">
                      <span>Принято ваших:</span>
                      <strong class="text-success">{{ analytics.my_accepted_proposals || 0 }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <h6 class="card-title mb-0">Эффективность</h6>
                </div>
                <div class="card-body">
                  <div class="analytics-item mb-3">
                    <div class="d-flex justify-content-between">
                      <span>Ваша конверсия:</span>
                      <strong class="text-warning">{{ analytics.my_conversion_rate || 0 }}%</strong>
                    </div>
                  </div>
                  <div class="analytics-item mb-3">
                    <div class="d-flex justify-content-between">
                      <span>Конверсия рынка:</span>
                      <strong class="text-secondary">{{ analytics.market_conversion_rate || 0 }}%</strong>
                    </div>
                  </div>
                  <div class="analytics-item">
                    <div class="d-flex justify-content-between">
                      <span>Просмотры заявки:</span>
                      <strong>{{ request.views_count || 0 }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Сравнение цен -->
          <div v-if="analytics.price_comparison" class="card mt-4">
            <div class="card-header">
              <h6 class="card-title mb-0">Сравнение цен</h6>
            </div>
            <div class="card-body">
              <div class="row text-center">
                <div class="col-md-4">
                  <div class="price-comparison-item">
                    <div class="price-value text-success">
                      {{ formatCurrency(analytics.price_comparison.my_avg_price) }}
                    </div>
                    <div class="price-label">Ваша средняя</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="price-comparison-item">
                    <div class="price-value text-info">
                      {{ formatCurrency(analytics.price_comparison.market_avg_price) }}
                    </div>
                    <div class="price-label">Средняя по рынку</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="price-comparison-item">
                    <div class="price-difference"
                         :class="getPriceDifferenceClass(analytics.price_comparison.price_difference_percent)">
                      <div class="difference-value">
                        {{ Math.abs(analytics.price_comparison.price_difference_percent) }}%
                      </div>
                      <div class="difference-label">
                        {{ analytics.price_comparison.price_difference_percent > 0 ? 'Выше рынка' : 'Ниже рынка' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 🔥 ВКЛАДКА РЕКОМЕНДАЦИЙ -->
        <div v-if="activeTab === 'recommendations'" class="tab-content-recommendations">
          <div class="row">
            <div class="col-md-8">
              <h5 class="mb-3">
                <i class="fas fa-robot me-2 text-primary"></i>
                Умные рекомендации для этой заявки
              </h5>

              <!-- Статистика рекомендаций -->
              <div class="recommendation-stats card mb-4">
                <div class="card-body">
                  <div class="row text-center">
                    <div class="col-md-4">
                      <div class="stat-item">
                        <div class="stat-value text-primary">{{ recommendationStats.total_recommendations || 0 }}</div>
                        <div class="stat-label">Всего рекомендаций</div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="stat-item">
                        <div class="stat-value text-success">{{ recommendationStats.application_rate || 0 }}%</div>
                        <div class="stat-label">Применяемость</div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="stat-item">
                        <div class="stat-value text-warning">{{ recommendationStats.conversion_rate || 0 }}%</div>
                        <div class="stat-label">Конверсия</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Список рекомендаций -->
              <div v-if="recommendedTemplates.length > 0" class="recommendations-list">
                <div v-for="(recommendation, index) in recommendedTemplates"
                     :key="recommendation.template.id"
                     class="recommendation-item card mb-3"
                     :class="'confidence-' + recommendation.confidence_level">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-md-1">
                        <div class="recommendation-rank">
                          <span class="badge" :class="getConfidenceBadgeClass(recommendation.confidence_level)">
                            #{{ index + 1 }}
                          </span>
                        </div>
                      </div>
                      <div class="col-md-7">
                        <h6 class="mb-1">{{ recommendation.template.name }}</h6>
                        <p class="text-muted small mb-2">{{ recommendation.reason }}</p>
                        <div class="template-details small">
                          <span class="me-3">
                            <i class="fas fa-ruble-sign me-1"></i>
                            {{ formatCurrency(recommendation.template.proposed_price) }}/час
                          </span>
                          <span class="me-3">
                            <i class="fas fa-clock me-1"></i>
                            {{ recommendation.template.response_time }}ч ответ
                          </span>
                          <span>
                            <i class="fas fa-chart-line me-1"></i>
                            {{ recommendation.template.success_rate || 0 }}% конверсия
                          </span>
                        </div>
                      </div>
                      <div class="col-md-4 text-end">
                        <div class="confidence-level mb-2">
                          <span class="badge" :class="getConfidenceBadgeClass(recommendation.confidence_level)">
                            {{ recommendation.confidence }} ({{ recommendation.score }}%)
                          </span>
                        </div>
                        <div class="recommendation-actions">
                          <button @click="applyRecommendedTemplate(recommendation)"
                                  class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-bolt me-1"></i>Применить
                          </button>
                          <button @click="viewTemplateDetails(recommendation.template)"
                                  class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-eye"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-5">
                <div class="empty-state">
                  <i class="fas fa-robot fa-3x text-muted mb-3"></i>
                  <h5>Рекомендации не найдены</h5>
                  <p class="text-muted">Попробуйте создать шаблоны для категорий этой заявки</p>
                  <button class="btn btn-primary" @click="activeTab = 'templates'">
                    <i class="fas fa-file-alt me-2"></i>Перейти к шаблонам
                  </button>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <!-- Информация о алгоритме -->
              <div class="algorithm-info card">
                <div class="card-header">
                  <h6 class="card-title mb-0">Как работают рекомендации?</h6>
                </div>
                <div class="card-body">
                  <div class="algorithm-steps">
                    <div class="step-item mb-3">
                      <div class="step-icon bg-primary">
                        <i class="fas fa-filter"></i>
                      </div>
                      <div class="step-content">
                        <strong>Соответствие категории</strong>
                        <small class="text-muted">Шаблоны подбираются по категориям заявки</small>
                      </div>
                    </div>
                    <div class="step-item mb-3">
                      <div class="step-icon bg-success">
                        <i class="fas fa-chart-line"></i>
                      </div>
                      <div class="step-content">
                        <strong>Историческая успешность</strong>
                        <small class="text-muted">Учитывается конверсия шаблонов</small>
                      </div>
                    </div>
                    <div class="step-item mb-3">
                      <div class="step-icon bg-info">
                        <i class="fas fa-ruble-sign"></i>
                      </div>
                      <div class="step-content">
                        <strong>Соответствие бюджету</strong>
                        <small class="text-muted">Цены сравниваются с бюджетом заявки</small>
                      </div>
                    </div>
                    <div class="step-item">
                      <div class="step-icon bg-warning">
                        <i class="fas fa-clock"></i>
                      </div>
                      <div class="step-content">
                        <strong>Скорость ответа</strong>
                        <small class="text-muted">Быстрые шаблоны получают бонус</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Действия -->
              <div class="recommendation-actions-card card mt-4">
                <div class="card-body">
                  <h6 class="card-title">Улучшите рекомендации</h6>
                  <div class="action-list">
                    <button class="btn btn-outline-primary btn-sm w-100 mb-2" @click="loadTemplateRecommendations">
                      <i class="fas fa-refresh me-1"></i>Обновить рекомендации
                    </button>
                    <button class="btn btn-outline-success btn-sm w-100 mb-2" @click="activeTab = 'templates'">
                      <i class="fas fa-plus me-1"></i>Создать шаблон
                    </button>
                    <button class="btn btn-outline-info btn-sm w-100" @click="viewRecommendationStats">
                      <i class="fas fa-chart-bar me-1"></i>Статистика рекомендаций
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно предложения техники -->
    <div class="modal fade" :class="{ 'show d-block': showProposalModal }" v-if="showProposalModal" style="background: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-paper-plane me-2"></i>
              Предложить технику
            </h5>
            <button type="button" class="btn-close" @click="closeProposalModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="apiError" class="alert alert-danger">
              <i class="fas fa-exclamation-triangle me-2"></i>
              {{ apiError }}
            </div>

            <div v-if="loadingEquipment" class="text-center py-3">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
              </div>
              <p class="mt-2 text-muted">Проверка доступности оборудования...</p>
            </div>

            <div v-else>
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Заполните форму для отправки предложения арендатору
              </div>

              <!-- Информация о заявке -->
              <div class="card mb-3">
                <div class="card-header bg-light">
                  <h6 class="mb-0">Требования заявки</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <small class="text-muted">Категории:</small>
                      <div>
                        <span v-for="item in request.items" :key="item.id" class="badge bg-primary me-1">
                          {{ item.category?.name }}
                        </span>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted">Период:</small>
                      <div>{{ formatDate(request.rental_period_start) }} - {{ formatDate(request.rental_period_end) }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Выберите технику *</label>
                <select class="form-select" v-model="proposalForm.equipment_id"
                        :class="{ 'is-invalid': fieldErrors.equipment_id }"
                        :disabled="availableEquipment.length === 0">
                  <option value="">Выберите технику из вашего каталога</option>
                  <option v-for="equipment in availableEquipment" :key="equipment.id" :value="equipment.id">
                    {{ equipment.title }} {{ equipment.model ? `(${equipment.model})` : '' }} - {{ formatCurrency(equipment.hourly_rate || 0) }}/час
                    <span v-if="equipment.availability_status" class="badge ms-1" :class="getAvailabilityBadgeClass(equipment.availability_status)">
                      {{ getAvailabilityStatusText(equipment.availability_status) }}
                    </span>
                  </option>
                </select>
                <div v-if="fieldErrors.equipment_id" class="invalid-feedback">
                  {{ fieldErrors.equipment_id[0] }}
                </div>
                <div v-if="availableEquipment.length === 0" class="alert alert-warning mt-2">
                  <i class="fas fa-exclamation-triangle me-2"></i>
                  <strong>У вас нет доступного оборудования для этой заявки</strong>
                  <div class="mt-1 small">
                    Возможные причины:
                    <ul class="mb-0">
                      <li>Оборудование не соответствует категориям заявки</li>
                      <li>Оборудование занято в указанный период</li>
                      <li>Оборудование находится на обслуживании</li>
                      <li>Локация оборудования не подходит для доставки</li>
                    </ul>
                  </div>
                </div>
                <div v-else class="text-muted small mt-1">
                  Найдено {{ availableEquipment.length }} единиц техники, подходящих для заявки
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Предлагаемая цена (₽/час) *</label>
                  <input type="number" class="form-control" v-model="proposalForm.proposed_price" min="0"
                         :class="{ 'is-invalid': fieldErrors.proposed_price }">
                  <div v-if="fieldErrors.proposed_price" class="invalid-feedback">
                    {{ fieldErrors.proposed_price[0] }}
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Количество *</label>
                  <input type="number" class="form-control" v-model="proposalForm.quantity" min="1" max="10" value="1"
                         :class="{ 'is-invalid': fieldErrors.quantity }">
                  <div v-if="fieldErrors.quantity" class="invalid-feedback">
                    {{ fieldErrors.quantity[0] }}
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Время ответа (часы)</label>
                  <input type="number" class="form-control" v-model="proposalForm.response_time" min="1" max="168" value="24">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Сообщение для арендатора *</label>
                <textarea class="form-control" rows="4" v-model="proposalForm.message"
                          placeholder="Опишите ваше предложение, условия доставки, доступность техники..."
                          :class="{ 'is-invalid': fieldErrors.message }"></textarea>
                <div v-if="fieldErrors.message" class="invalid-feedback">
                  {{ fieldErrors.message[0] }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Дополнительные условия</label>
                <textarea class="form-control" rows="3" v-model="proposalForm.additional_terms"
                          placeholder="Минимальный срок аренды, условия оплаты, гарантии..."></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeProposalModal">Отмена</button>
            <button type="button" class="btn btn-primary"
                    @click="submitProposal"
                    :disabled="sendingProposal || availableEquipment.length === 0">
              <span v-if="sendingProposal" class="spinner-border spinner-border-sm me-1"></span>
              {{ sendingProposal ? 'Отправка...' : 'Отправить предложение' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue'
import ProposalTemplates from './ProposalTemplates.vue'

export default {
  name: 'RentalRequestDetail',
  components: {
    ProposalTemplates
  },
  props: {
    request: {
      type: Object,
      required: true
    },
    analytics: {
      type: Object,
      default: () => ({})
    },
    lessorPricing: {
      type: Object,
      default: () => ({})
    },
    proposalHistory: {
      type: Array,
      default: () => []
    },
    templates: {
      type: Array,
      default: () => []
    },
    categories: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const activeTab = ref('info')
    const showProposalModal = ref(false)
    const sendingProposal = ref(false)
    const loadingEquipment = ref(false)
    const apiError = ref('')
    const fieldErrors = ref({})

    // 🔥 ДАННЫЕ ДЛЯ РЕКОМЕНДАЦИЙ
    const recommendedTemplates = ref([])
    const recommendationsLoaded = ref(false)
    const recommendationStats = ref({
      total_recommendations: 0,
      application_rate: 0,
      conversion_rate: 0,
      average_score: 0
    })

    const availableEquipment = ref([])

    const proposalForm = ref({
      equipment_id: '',
      proposed_price: '',
      quantity: 1,
      response_time: 24,
      message: '',
      additional_terms: ''
    })

    // 🔥 МЕТОДЫ ДЛЯ РЕКОМЕНДАЦИЙ
    const loadTemplateRecommendations = async () => {
      try {
        console.log('🤖 Загрузка рекомендаций для заявки:', props.request.id);

        const response = await axios.get(`/api/lessor/rental-requests/${props.request.id}/recommendations`);
        recommendedTemplates.value = response.data.recommendations || [];
        recommendationsLoaded.value = true;

        console.log('✅ Рекомендации загружены:', recommendedTemplates.value);
      } catch (error) {
        console.error('❌ Ошибка загрузки рекомендаций:', error);
        recommendationsLoaded.value = true;
      }
    }

    const applyRecommendedTemplate = (recommendation) => {
      console.log('⚡ Применение рекомендованного шаблона:', recommendation);

      // Сохраняем фидбек
      saveRecommendationFeedback(recommendation, true);

      // Применяем шаблон
      handleTemplateApplied({
        template: recommendation.template,
        data: {
          proposed_price: recommendation.template.proposed_price,
          response_time: recommendation.template.response_time,
          message: recommendation.template.message,
          additional_terms: recommendation.template.additional_terms
        }
      });
    }

    const saveRecommendationFeedback = async (recommendation, applied) => {
      try {
        await axios.post('/api/lessor/recommendation-feedback', {
          template_id: recommendation.template.id,
          request_id: props.request.id,
          applied: applied,
          score: recommendation.score
        });
        console.log('✅ Фидбек рекомендации сохранен');
      } catch (error) {
        console.error('❌ Ошибка сохранения фидбека:', error);
      }
    }

    const viewTemplateDetails = (template) => {
      // Переход к редактированию шаблона
      activeTab.value = 'templates';
      console.log('👀 Просмотр шаблона:', template);
    }

    const getConfidenceBadgeClass = (confidenceLevel) => {
      const classes = {
        'high': 'bg-success',
        'medium': 'bg-info',
        'low': 'bg-warning',
        'very-low': 'bg-secondary'
      };
      return classes[confidenceLevel] || 'bg-secondary';
    }

    const loadRecommendationStats = async () => {
      try {
        const response = await axios.get('/api/lessor/recommendations/stats');
        recommendationStats.value = response.data.stats || {};
      } catch (error) {
        console.error('❌ Ошибка загрузки статистики рекомендаций:', error);
      }
    }

    const viewRecommendationStats = () => {
      console.log('📊 Просмотр статистики рекомендаций');
      // Можно открыть модальное окно с детальной статистикой
      alert('Статистика рекомендаций:\n' +
            `Всего рекомендаций: ${recommendationStats.value.total_recommendations}\n` +
            `Применяемость: ${recommendationStats.value.application_rate}%\n` +
            `Конверсия: ${recommendationStats.value.conversion_rate}%`);
    }

    // 🔥 ДЕТАЛЬНАЯ ПРОВЕРКА ДОСТУПНОСТИ ОБОРУДОВАНИЯ
    const loadAvailableEquipment = async () => {
      loadingEquipment.value = true
      clearErrors()

      try {
        console.log('🔍 ========== ПРОВЕРКА ДОСТУПНОСТИ ОБОРУДОВАНИЯ ==========')
        console.log('📋 Информация о заявке:', {
          id: props.request.id,
          title: props.request.title,
          категории: props.request.items?.map(item => ({
            id: item.category?.id,
            name: item.category?.name,
            количество: item.quantity
          })),
          период: {
            start: props.request.rental_period_start,
            end: props.request.rental_period_end
          },
          локация: props.request.location?.name,
          доставка: props.request.delivery_required ? 'Требуется' : 'Не требуется'
        })

        // 🔥 ВАРИАНТ 1: Специальный endpoint для проверки доступности
        let response
        try {
          response = await axios.get(`/api/rental-requests/${props.request.id}/available-equipment`)
          console.log('✅ Оборудование загружено через специализированный endpoint:', response.data)
        } catch (error) {
          if (error.response?.status === 404) {
            console.log('🔧 Специализированный endpoint не найден, используем альтернативный метод...')
            response = await axios.get('/api/lessor/equipment/my-equipment')
            console.log('✅ Оборудование загружено через общий endpoint:', response.data)
          } else {
            throw error
          }
        }

        // Обработка ответа
        if (response.data.data?.available_equipment) {
          // Формат специализированного endpoint
          availableEquipment.value = response.data.data.available_equipment.map(item => ({
            ...item.equipment,
            availability_status: 'available',
            recommended_price: item.recommended_lessor_price
          }))
        } else if (Array.isArray(response.data.data)) {
          // Формат общего endpoint
          availableEquipment.value = response.data.data.map(equipment => ({
            ...equipment,
            availability_status: 'available'
          }))
        } else {
          availableEquipment.value = []
        }

        console.log('📦 Обработанное оборудование:', availableEquipment.value)

        // 🔥 ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА СООТВЕТСТВИЯ КАТЕГОРИЯМ
        if (availableEquipment.value.length > 0) {
          const requestCategoryIds = props.request.items?.map(item => item.category?.id).filter(Boolean) || []
          console.log('🎯 Категории заявки:', requestCategoryIds)

          if (requestCategoryIds.length > 0) {
            const filteredEquipment = availableEquipment.value.filter(equipment =>
              requestCategoryIds.includes(equipment.category_id)
            )
            console.log('🔍 Оборудование после фильтрации по категориям:', {
              было: availableEquipment.value.length,
              стало: filteredEquipment.length,
              отфильтровано: availableEquipment.value.length - filteredEquipment.length
            })
            availableEquipment.value = filteredEquipment
          }
        }

        // 🔥 ПРОВЕРКА ДАТ ДОСТУПНОСТИ
        if (availableEquipment.value.length > 0) {
          console.log('📅 Проверка доступности оборудования в период заявки...')
          // Здесь можно добавить дополнительную проверку дат через API
        }

        console.log('🎯 Итоговое доступное оборудование:', availableEquipment.value)

        if (availableEquipment.value.length === 0) {
          console.warn('⚠️ Нет подходящего оборудования для заявки')
          apiError.value = 'Нет доступного оборудования, соответствующего требованиям заявки'
        }

      } catch (error) {
        console.error('❌ Ошибка проверки доступности оборудования:', error)

        if (error.response?.status === 404) {
          apiError.value = 'Endpoint проверки доступности не найден. Обратитесь к администратору.'
        } else if (error.response?.data?.message) {
          apiError.value = 'Ошибка загрузки оборудования: ' + error.response.data.message
        } else {
          apiError.value = 'Ошибка проверки доступности оборудования: ' + error.message
        }

        availableEquipment.value = []
      } finally {
        loadingEquipment.value = false
      }
    }

    // 🔥 КЛАССЫ ДЛЯ СТАТУСОВ ДОСТУПНОСТИ
    const getAvailabilityBadgeClass = (status) => {
      const statusClasses = {
        'available': 'bg-success',
        'unavailable': 'bg-danger',
        'maintenance': 'bg-secondary',
        'delivery': 'bg-warning',
        'temp_reserve': 'bg-info'
      }
      return statusClasses[status] || 'bg-secondary'
    }

    // 🔥 ТЕКСТ ДЛЯ СТАТУСОВ ДОСТУПНОСТИ
    const getAvailabilityStatusText = (status) => {
      const statusTexts = {
        'available': 'Доступно',
        'unavailable': 'Недоступно',
        'maintenance': 'Обслуживание',
        'delivery': 'В доставке',
        'temp_reserve': 'Временный резерв'
      }
      return statusTexts[status] || status
    }

    // 🔥 ОЧИСТКА ОШИБОК
    const clearErrors = () => {
      apiError.value = ''
      fieldErrors.value = {}
    }

    // 🔥 ЗАКРЫТИЕ МОДАЛЬНОГО ОКНА
    const closeProposalModal = () => {
      showProposalModal.value = false
      clearErrors()
    }

    // 🔥 ОТКРЫТИЕ МОДАЛЬНОГО ОКНА С ПРОВЕРКОЙ ДОСТУПНОСТИ
    const openProposalModal = async () => {
      showProposalModal.value = true
      clearErrors()
      await loadAvailableEquipment()
    }

    // 🔥 ОБРАБОТКА ПРИМЕНЕНИЯ ШАБЛОНА
    const handleTemplateApplied = (templateData) => {
      console.log('✅ Шаблон применен:', templateData)
      clearErrors()

      // Автозаполнение формы предложения из шаблона
      proposalForm.value = {
        ...proposalForm.value,
        proposed_price: templateData.data.proposed_price,
        response_time: templateData.data.response_time,
        message: templateData.data.message,
        additional_terms: templateData.data.additional_terms
      }
      openProposalModal()
    }

    // 🔥 ОТПРАВКА ПРЕДЛОЖЕНИЯ
    const submitProposal = async () => {
      clearErrors()

      // Валидация обязательных полей на фронтенде
      if (!proposalForm.value.equipment_id) {
        apiError.value = 'Пожалуйста, выберите технику'
        return
      }

      if (!proposalForm.value.proposed_price || proposalForm.value.proposed_price <= 0) {
        apiError.value = 'Пожалуйста, укажите корректную цену (больше 0)'
        return
      }

      if (!proposalForm.value.quantity || proposalForm.value.quantity <= 0) {
        apiError.value = 'Пожалуйста, укажите корректное количество'
        return
      }

      if (!proposalForm.value.message?.trim()) {
        apiError.value = 'Пожалуйста, введите сообщение для арендатора'
        return
      }

      sendingProposal.value = true

      try {
        // 🔥 ПРАВИЛЬНАЯ СТРУКТУРА ДАННЫХ ДЛЯ ОТПРАВКИ
        const proposalData = {
          equipment_items: [
            {
              equipment_id: parseInt(proposalForm.value.equipment_id),
              proposed_price: parseFloat(proposalForm.value.proposed_price),
              quantity: parseInt(proposalForm.value.quantity) || 1
            }
          ],
          message: proposalForm.value.message.trim(),
          additional_terms: proposalForm.value.additional_terms?.trim() || '',
          response_time: parseInt(proposalForm.value.response_time) || 24
        }

        console.log('📤 ========== ОТПРАВКА ПРЕДЛОЖЕНИЯ ==========')
        console.log('📦 Данные для отправки:', JSON.stringify(proposalData, null, 2))
        console.log('🔗 Endpoint:', `/api/rental-requests/${props.request.id}/proposals`)
        console.log('👤 Текущий пользователь ID:', window.authUser?.id || 'Не определен')
        console.log('🏢 ID заявки:', props.request.id)
        console.log('🔧 Выбранное оборудование ID:', proposalForm.value.equipment_id)

        // 🔥 РЕАЛЬНЫЙ API ЗАПРОС
        const response = await axios.post(`/api/rental-requests/${props.request.id}/proposals`, proposalData, {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          timeout: 10000 // 10 секунд таймаут
        })

        console.log('📥 ========== ОТВЕТ СЕРВЕРА ==========')
        console.log('🔧 Статус ответа:', response.status)
        console.log('📄 Данные ответа:', response.data)
        console.log('✅ Успех:', response.data.success)

        if (response.data.success) {
          // 🔥 УСПЕШНОЕ СОХРАНЕНИЕ
          alert('✅ Ваше предложение успешно отправлено!')
          showProposalModal.value = false

          // Сброс формы
          proposalForm.value = {
            equipment_id: '',
            proposed_price: '',
            quantity: 1,
            response_time: 24,
            message: '',
            additional_terms: ''
          }

          // 🔥 ОБНОВЛЕНИЕ ИСТОРИИ ПРЕДЛОЖЕНИЙ
          if (typeof window.updateProposalHistory === 'function') {
            window.updateProposalHistory()
          }

          // 🔥 ОБНОВЛЕНИЕ СТАТИСТИКИ
          if (typeof window.refreshAnalytics === 'function') {
            window.refreshAnalytics()
          }

          console.log('🎉 Предложение успешно создано в базе данных')
        } else {
          throw new Error(response.data.message || 'Неизвестная ошибка сервера')
        }

      } catch (error) {
        console.error('❌ ========== ОШИБКА ОТПРАВКИ ПРЕДЛОЖЕНИЯ ==========')
        console.error('🔧 Код ошибки:', error.code)
        console.error('📡 URL запроса:', error.config?.url)
        console.error('🔧 Метод запроса:', error.config?.method)
        console.error('📦 Данные запроса:', error.config?.data)

        // Детальная информация об ошибке
        if (error.response) {
          console.error('📊 Ответ сервера:', error.response.data)
          console.error('🔢 Статус ошибки:', error.response.status)
          console.error('📋 Заголовки ответа:', error.response.headers)

          if (error.response.status === 422) {
            // Ошибки валидации
            const validationErrors = error.response.data.errors
            fieldErrors.value = validationErrors
            apiError.value = 'Пожалуйста, исправьте ошибки в форме'
            console.error('❌ Ошибки валидации:', validationErrors)
          } else if (error.response.status === 403) {
            apiError.value = 'Недостаточно прав для создания предложения'
          } else if (error.response.status === 404) {
            apiError.value = 'Заявка не найдена или была удалена'
          } else if (error.response.status === 401) {
            apiError.value = 'Необходимо авторизоваться'
          } else if (error.response.data?.message) {
            apiError.value = error.response.data.message
          } else {
            apiError.value = 'Ошибка сервера при создании предложения'
          }
        } else if (error.request) {
          console.error('🌐 Ошибка сети:', error.request)
          apiError.value = 'Ошибка сети: не удалось подключиться к серверу'
        } else if (error.code === 'ECONNABORTED') {
          apiError.value = 'Превышено время ожидания ответа от сервера'
        } else {
          console.error('⚡ Другая ошибка:', error.message)
          apiError.value = `Ошибка: ${error.message}`
        }
      } finally {
        sendingProposal.value = false
      }
    }

    // 🔥 РАСЧЕТ ДНЕЙ АРЕНДЫ
    const calculateRentalDays = () => {
      if (!props.request.rental_period_start || !props.request.rental_period_end) {
        return 0
      }
      const start = new Date(props.request.rental_period_start)
      const end = new Date(props.request.rental_period_end)
      return Math.ceil((end - start) / (1000 * 3600 * 24)) + 1
    }

    // 🔥 РАСЧЕТ ЦЕНЫ ДЛЯ ПОЗИЦИИ
    const calculateItemPrice = (item) => {
      const basePrice = props.lessorPricing?.category_prices?.[item.category_id] || 1000
      return basePrice
    }

    // 🔥 ФОРМАТИРОВАНИЕ ДАТЫ
    const formatDate = (dateString) => {
      if (!dateString) return '—'
      try {
        return new Date(dateString).toLocaleDateString('ru-RU')
      } catch (error) {
        console.error('Ошибка форматирования даты:', error)
        return '—'
      }
    }

    // 🔥 ФОРМАТИРОВАНИЕ ВАЛЮТЫ
    const formatCurrency = (amount) => {
      if (!amount && amount !== 0) return '0 ₽'
      try {
        return new Intl.NumberFormat('ru-RU', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount) + ' ₽'
      } catch (error) {
        console.error('Ошибка форматирования валюты:', error)
        return '0 ₽'
      }
    }

    // 🔥 ФОРМАТИРОВАНИЕ КЛЮЧЕЙ УСЛОВИЙ АРЕНДЫ
    const formatConditionKey = (key) => {
      const conditionNames = {
        'hours_per_shift': 'Часов в смену',
        'shifts_per_day': 'Смен в день',
        'operator_required': 'Требуется оператор',
        'fuel_included': 'Топливо включено',
        'maintenance_included': 'Обслуживание включено',
        'gsm_payment': 'Оплата ГСМ',
        'payment_type': 'Тип оплаты',
        'operator_included': 'Оператор включен',
        'accommodation_payment': 'Оплата проживания',
        'extension_possibility': 'Возможность продления',
        'transportation_organized_by': 'Организация транспортировки',
        'insurance_included': 'Страховка включена',
        'fuel_provided_by': 'Топливо предоставляет',
        'maintenance_responsibility': 'Обслуживание отвечает'
      }
      return conditionNames[key] || key
    }

    // 🔥 ФОРМАТИРОВАНИЕ ЗНАЧЕНИЙ УСЛОВИЙ АРЕНДЫ
    const formatConditionValue = (key, value) => {
      if (typeof value === 'boolean') {
        return value ? 'Да' : 'Нет'
      }

      const valueMappings = {
        'gsm_payment': {
          'included': 'Включено',
          'extra': 'Дополнительно',
          'not_included': 'Не включено'
        },
        'payment_type': {
          'hourly': 'Почасовая',
          'daily': 'Посуточная',
          'weekly': 'Понедельная',
          'monthly': 'Помесячная'
        },
        'transportation_organized_by': {
          'lessor': 'Арендодателем',
          'lessee': 'Арендатором',
          'third_party': 'Третьей стороной'
        },
        'fuel_provided_by': {
          'lessor': 'Арендодатель',
          'lessee': 'Арендатор'
        },
        'maintenance_responsibility': {
          'lessor': 'Арендодатель',
          'lessee': 'Арендатор'
        }
      }

      if (valueMappings[key] && valueMappings[key][value]) {
        return valueMappings[key][value]
      }

      return value
    }

    // 🔥 КЛАССЫ ДЛЯ СТАТУСОВ ПРЕДЛОЖЕНИЙ
    const getStatusBadgeClass = (status) => {
      const statusClasses = {
        'pending': 'bg-warning',
        'accepted': 'bg-success',
        'rejected': 'bg-danger',
        'expired': 'bg-secondary'
      }
      return statusClasses[status] || 'bg-secondary'
    }

    // 🔥 ТЕКСТ ДЛЯ СТАТУСОВ ПРЕДЛОЖЕНИЙ
    const getStatusText = (status) => {
      const statusTexts = {
        'pending': 'Ожидает',
        'accepted': 'Принято',
        'rejected': 'Отклонено',
        'expired': 'Истекло'
      }
      return statusTexts[status] || status
    }

    // 🔥 КЛАСС ДЛЯ РАЗНИЦЫ В ЦЕНАХ
    const getPriceDifferenceClass = (difference) => {
      if (difference > 10) return 'text-danger'
      if (difference > 0) return 'text-warning'
      if (difference < -10) return 'text-success'
      return 'text-info'
    }

    // 🔥 ДОБАВЛЕНИЕ В ИЗБРАННОЕ
    const addToFavorites = () => {
      console.log('⭐ Добавление в избранное:', props.request.id)
      alert('Заявка добавлена в избранное!')
    }

    // 🔥 ПРОСМОТР ДЕТАЛЕЙ ПРЕДЛОЖЕНИЯ
    const viewProposalDetails = (proposal) => {
      console.log('👀 Просмотр деталей предложения:', proposal)
    }

    // 🔥 УПРАВЛЕНИЕ SCROLL ДЛЯ МОДАЛЬНОГО ОКНА
    watch(showProposalModal, (newVal) => {
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

    onMounted(() => {
      console.log('✅ RentalRequestDetail mounted')
      console.log('📦 Request data:', props.request)
      console.log('📊 Analytics:', props.analytics)
      console.log('💰 Pricing:', props.lessorPricing)
      console.log('📋 Templates:', props.templates)

      // 🔥 ЗАГРУЖАЕМ РЕКОМЕНДАЦИИ ПРИ ЗАГРУЗКЕ КОМПОНЕНТА
      loadTemplateRecommendations()
      loadRecommendationStats()
    })

    return {
      activeTab,
      showProposalModal,
      sendingProposal,
      loadingEquipment,
      apiError,
      fieldErrors,
      availableEquipment,
      proposalForm,
      // 🔥 ДАННЫЕ РЕКОМЕНДАЦИЙ
      recommendedTemplates,
      recommendationsLoaded,
      recommendationStats,
      // 🔥 МЕТОДЫ
      openProposalModal,
      handleTemplateApplied,
      submitProposal,
      closeProposalModal,
      getAvailabilityBadgeClass,
      getAvailabilityStatusText,
      calculateRentalDays,
      calculateItemPrice,
      formatDate,
      formatCurrency,
      formatConditionKey,
      formatConditionValue,
      getStatusBadgeClass,
      getStatusText,
      getPriceDifferenceClass,
      addToFavorites,
      viewProposalDetails,
      // 🔥 МЕТОДЫ РЕКОМЕНДАЦИЙ
      loadTemplateRecommendations,
      applyRecommendedTemplate,
      viewTemplateDetails,
      getConfidenceBadgeClass,
      viewRecommendationStats
    }
  }

}

</script>

<style scoped>
.rental-request-detail {
  padding: 0;
}

.request-header .card-title {
  color: #2c3e50;
  font-weight: 600;
}

.meta-item {
  display: flex;
  align-items: center;
}

.action-buttons {
  min-width: 200px;
}

.stats-badges {
  font-size: 0.9rem;
}

/* Стили для вкладок */
.request-tabs .nav-tabs .nav-link {
  color: #6c757d;
  border: none;
  padding: 12px 20px;
  transition: all 0.3s ease;
}

.request-tabs .nav-tabs .nav-link.active {
  color: #0d6efd;
  background: transparent;
  border-bottom: 3px solid #0d6efd;
}

.request-tabs .nav-tabs .nav-link:hover {
  color: #0d6efd;
  background: rgba(13, 110, 253, 0.1);
}

/* Стили для позиций техники */
.position-item {
  border-left: 4px solid #0d6efd;
  transition: all 0.3s ease;
}

.position-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.specifications {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.spec-item {
  font-size: 0.8rem;
  padding: 4px 8px;
}

/* Стили для аналитики */
.analytics-item {
  padding: 8px 0;
  border-bottom: 1px solid #e9ecef;
}

.analytics-item:last-child {
  border-bottom: none;
}

.price-comparison-item {
  padding: 20px 0;
}

.price-value {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.price-label {
  color: #6c757d;
  font-size: 0.9rem;
}

.difference-value {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.difference-label {
  font-size: 0.9rem;
}

/* Стили для истории предложений */
.proposal-item {
  border-left: 4px solid transparent;
  transition: all 0.3s ease;
}

.proposal-item:hover {
  border-left-color: #0d6efd;
  background-color: #f8f9fa;
}

/* Стили для информации о платформе */
.platform-info {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 6px;
  border-left: 3px solid #0d6efd;
}

.platform-item {
  display: flex;
  align-items: center;
}

/* Стили для условий аренды */
.conditions-list {
  background: #f8f9fa;
  padding: 12px;
  border-radius: 6px;
  border-left: 3px solid #28a745;
}

.condition-item {
  padding: 4px 0;
  border-bottom: 1px solid #dee2e6;
}

.condition-item:last-child {
  border-bottom: none;
}

/* 🔥 СТИЛИ ДЛЯ РЕКОМЕНДАЦИЙ */
.smart-recommendations {
  border-left: 4px solid #0d6efd;
  animation: slideIn 0.5s ease-out;
}

.recommendations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.recommendation-card {
  padding: 1rem;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  background: #f8f9fa;
  transition: all 0.3s ease;
}

.recommendation-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.recommendation-card.confidence-high {
  border-left: 4px solid #28a745;
}

.recommendation-card.confidence-medium {
  border-left: 4px solid #17a2b8;
}

.recommendation-card.confidence-low {
  border-left: 4px solid #ffc107;
}

.recommendation-card.confidence-very-low {
  border-left: 4px solid #6c757d;
}

.recommendation-header {
  font-size: 0.875rem;
}

.confidence-badge {
  font-size: 0.75rem;
}

.reason {
  font-size: 0.8rem;
  text-align: right;
  flex: 1;
  margin-left: 0.5rem;
}

/* Стили для вкладки рекомендаций */
.recommendation-stats .stat-item {
  padding: 1rem 0;
}

.recommendation-stats .stat-value {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.recommendation-stats .stat-label {
  color: #6c757d;
  font-size: 0.9rem;
}

.recommendation-item {
  border-left: 4px solid #e9ecef;
  transition: all 0.3s ease;
}

.recommendation-item.confidence-high {
  border-left-color: #28a745;
}

.recommendation-item.confidence-medium {
  border-left-color: #17a2b8;
}

.recommendation-item.confidence-low {
  border-left-color: #ffc107;
}

.recommendation-item.confidence-very-low {
  border-left-color: #6c757d;
}

.recommendation-item:hover {
  transform: translateX(5px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.recommendation-rank {
  text-align: center;
}

.template-details {
  color: #6c757d;
}

.algorithm-steps .step-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.step-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.step-content {
  flex: 1;
}

.step-content strong {
  display: block;
  margin-bottom: 0.25rem;
}

.recommendation-actions-card .action-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Анимации */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.empty-state {
  padding: 3rem 1rem;
}

.empty-state i {
  opacity: 0.5;
}

/* Стили для модального окна */
.modal.show {
  background: rgba(0,0,0,0.5) !important;
}

.modal-dialog {
  margin: 1rem auto;
  max-width: 90%;
}

.modal-lg {
  max-width: 800px;
}

@media (max-width: 768px) {
  .modal-dialog {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }
}

/* Стили для ошибок */
.invalid-feedback {
  display: block;
}

.is-invalid {
  border-color: #dc3545;
}

/* Адаптивность */
@media (max-width: 768px) {
  .request-header .row {
    flex-direction: column;
  }

  .action-buttons {
    margin-top: 20px;
    width: 100%;
  }

  .request-tabs .nav-tabs .nav-link {
    padding: 8px 12px;
    font-size: 0.9rem;
  }

  .specifications {
    justify-content: flex-start;
  }

  /* Адаптивность для рекомендаций */
  .recommendations-grid {
    grid-template-columns: 1fr;
  }

  .recommendation-header {
    flex-direction: column;
    gap: 0.5rem;
  }

  .reason {
    text-align: left;
    margin-left: 0;
  }

  .recommendation-item .row {
    flex-direction: column;
    gap: 1rem;
  }

  .recommendation-rank {
    text-align: left;
  }

  .recommendation-actions {
    text-align: left;
  }

  .algorithm-steps .step-item {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }
}

/* Дополнительные стили для улучшения UX */
.tab-content > div {
  animation: fadeIn 0.3s ease-in;
}

.card {
  transition: all 0.3s ease;
}

.card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn {
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

/* Стили для состояний загрузки */
.spinner-border {
  animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
  to {
    transform: rotate(360deg);
  }
}

/* Улучшенные стили для бейджей */
.badge {
  font-weight: 500;
}

.bg-success {
  background: linear-gradient(135deg, #28a745, #20c997);
}

.bg-info {
  background: linear-gradient(135deg, #17a2b8, #6f42c1);
}

.bg-warning {
  background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.bg-primary {
  background: linear-gradient(135deg, #0d6efd, #6610f2);
}

/* Стили для текста */
.text-muted {
  opacity: 0.8;
}

.text-success {
  color: #28a745 !important;
}

.text-primary {
  color: #0d6efd !important;
}

.text-warning {
  color: #ffc107 !important;
}

/* Стили для иконок */
.fas, .fab {
  opacity: 0.9;
}
</style>
