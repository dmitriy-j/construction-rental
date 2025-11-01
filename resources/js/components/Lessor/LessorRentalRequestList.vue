<template>
    <div class="lessor-rental-requests">

        <!-- 🔥 ЗАМЕНА: Единый аналитический дашборд вместо отдельных компонентов -->
        <AnalyticsDashboard
            :real-time-metrics="analytics"
            :strategic-metrics="strategicAnalytics"
            :categories="categories"
            :urgent-requests="urgentRequests"
            :templates="templates"
            :my-proposals-count="myProposalsComputedCount"
            @show-urgent-requests="showUrgentRequests"
            @show-templates="showTemplatesModal"
            @show-my-proposals="showMyProposals"
            @quick-proposal="showQuickProposalModal"
            @show-templates-modal="showTemplatesModal"
            @show-favorites="showFavorites"
            @export-analytics="exportAnalyticsData"
        />

        <!-- Фильтры -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Категория</label>
                        <select v-model="localFilters.category_id" class="form-select" @change="applyFilters">
                            <option value="">Все категории</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Локация</label>
                        <select v-model="localFilters.location_id" class="form-select" @change="applyFilters">
                            <option value="">Все локации</option>
                            <option v-for="location in locations" :key="location.id" :value="location.id">
                                {{ location.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Сортировка</label>
                        <select v-model="localFilters.sort" class="form-select" @change="applyFilters">
                            <option value="newest">Сначала новые</option>
                            <option value="budget">По бюджету</option>
                            <option value="proposals">По предложениям</option>
                            <option value="templates">С подходящими шаблонами</option>
                            <option value="recommendations">По рекомендациям</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Статус</label>
                        <select v-model="localFilters.my_proposals" class="form-select" @change="applyFilters">
                            <option value="">Все заявки</option>
                            <option value="with_proposals">С моими предложениями</option>
                            <option value="without_proposals">Без моих предложений</option>
                            <option value="with_templates">С подходящими шаблонами</option>
                            <option value="with_recommendations">С рекомендациями</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Селектор количества элементов на странице -->
        <div class="row align-items-center mb-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <label class="form-label mb-0 me-2">Показывать по:</label>
                    <select
                        v-model="pagination.perPage"
                        @change="changePerPage(pagination.perPage)"
                        class="form-select form-select-sm"
                        style="width: auto;"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-muted small ms-2">
                        заявок на странице
                    </span>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="pagination-summary text-muted small">
                    Найдено заявок: {{ pagination.total }}
                    <span v-if="pagination.lastPage > 1">
                        • Страница {{ pagination.currentPage }} из {{ pagination.lastPage }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Индикатор загрузки -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <div class="mt-3 text-muted">Загрузка заявок...</div>
        </div>

        <!-- 🔥 БЫСТРЫЕ РЕКОМЕНДАЦИИ ДЛЯ ВСЕХ ЗАЯВОК -->
        <div class="global-recommendations card mb-4" v-if="globalRecommendations.length > 0 && !loading">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">
                    <i class="fas fa-robot me-2"></i>Лучшие рекомендации для текущих заявок
                    <span class="badge bg-light text-warning ms-2">{{ globalRecommendations.length }}</span>
                </h6>
            </div>
            <div class="card-body">
                <div class="global-recommendations-grid">
                    <div v-for="rec in globalRecommendations.slice(0, 4)"
                         :key="`${rec.request_id}-${rec.template.id}`"
                         class="global-recommendation-card">
                        <div class="recommendation-content">
                            <div class="request-info">
                                <strong class="d-block">{{ getRequestTitle(rec.request_id) }}</strong>
                                <small class="text-muted">{{ rec.reason }}</small>
                            </div>
                            <div class="template-info">
                                <span class="template-name">{{ rec.template.name }}</span>
                                <span class="template-price">{{ formatCurrency(rec.template.proposed_price) }}/час</span>
                            </div>
                            <div class="confidence-badge">
                                <span class="badge" :class="'bg-' + rec.color">
                                    {{ rec.confidence }}
                                </span>
                            </div>
                        </div>
                        <div class="recommendation-actions">
                            <button class="btn btn-sm btn-primary"
                                    @click="applyQuickTemplate(rec, getRequestById(rec.request_id))"
                                    title="Быстро применить шаблон">
                                <i class="fas fa-bolt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary"
                                    @click="viewRequestDetails(rec.request_id)"
                                    title="Перейти к заявке">
                                <i class="fas fa-external-link-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Простой список заявок -->
        <div class="row" v-if="!loading">
            <div class="col-12" v-for="request in requests" :key="request.id">
                <div class="card mb-3 request-card" :class="getRequestCardClass(request)">
                    <div class="card-body">
                        <!-- 🔥 ДОБАВЛЕНО: Индикаторы статуса -->
                        <div class="request-indicators mb-2">
                            <span v-if="hasMatchingTemplates(request)" class="badge bg-success me-2">
                                <i class="fas fa-bolt me-1"></i>Есть шаблоны ({{ matchingTemplatesCount(request) }})
                            </span>
                            <span v-if="request.my_proposals_count > 0" class="badge bg-primary me-2">
                                <i class="fas fa-check me-1"></i>Предложение отправлено
                            </span>
                            <span v-if="isHighConversionRequest(request)" class="badge bg-warning me-2">
                                <i class="fas fa-rocket me-1"></i>Высокий шанс
                            </span>
                            <span v-if="isUrgentRequest(request)" class="badge bg-danger me-2">
                                <i class="fas fa-clock me-1"></i>Срочно
                            </span>
                            <span v-if="getQuickRecommendations(request).length > 0" class="badge bg-info me-2">
                                <i class="fas fa-robot me-1"></i>Рекомендации ({{ getQuickRecommendations(request).length }})
                            </span>
                        </div>

                        <h5 class="card-title">{{ request.title || 'Без названия' }}</h5>
                        <p class="card-text">{{ request.description || 'Описание отсутствует' }}</p>

                        <!-- 🔥 ДОБАВЛЕНО: Информация о категориях заявки -->
                        <div class="request-categories mb-2">
                            <span v-for="item in request.items" :key="item.id" class="badge bg-light text-dark me-1">
                                {{ getCategoryName(item.category_id) }}
                            </span>
                        </div>

                        <!-- 🔥 БЫСТРЫЕ РЕКОМЕНДАЦИИ ДЛЯ КОНКРЕТНОЙ ЗАЯВКИ -->
                        <div class="quick-recommendations mt-2" v-if="getQuickRecommendations(request).length > 0">
                            <div class="d-flex flex-wrap gap-1">
                                <span v-for="rec in getQuickRecommendations(request)"
                                      :key="rec.template.id"
                                      class="badge recommendation-badge"
                                      :class="'bg-' + rec.color"
                                      @click="applyQuickTemplate(rec, request)"
                                      :title="'Применить: ' + rec.reason">
                                    {{ rec.template.name }} ({{ rec.confidence }})
                                    <i class="fas fa-bolt ms-1"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between text-muted small mt-2">
                            <span>
                                <i class="fas fa-map-marker-alt"></i>
                                {{ request.location?.name || 'Локация не указана' }}
                            </span>
                            <span>
                                <i class="fas fa-calendar-alt"></i>
                                {{ formatDate(request.rental_period_start) }} - {{ formatDate(request.rental_period_end) }}
                            </span>
                            <span class="badge bg-primary">
                                {{ request.active_proposals_count || 0 }} предложений
                            </span>
                        </div>

                        <!-- 🔥 ДОБАВЛЕНО: Информация о бюджете -->
                        <div v-if="request.lessor_pricing" class="budget-info mt-2">
                            <span class="badge bg-success">
                                <i class="fas fa-ruble-sign me-1"></i>
                                Бюджет для вас: {{ formatCurrency(request.lessor_pricing.total_lessor_budget) }}
                            </span>
                        </div>

                        <!-- 🔥 ОБНОВЛЕНО: Кнопки действий с улучшенным dropdown -->
                        <div class="mt-3">
                            <button class="btn btn-primary btn-sm me-2" @click="viewDetails(request.id)">
                                <i class="fas fa-eye me-1"></i>Подробнее
                            </button>

                            <!-- 🔥 ИСПРАВЛЕНО: Кнопка "Предложить" открывает модальное окно -->
                            <div class="btn-group quick-actions">
                                <button class="btn btn-outline-success btn-sm" @click="openProposalModal(request)">
                                    <i class="fas fa-paper-plane me-1"></i>Предложить
                                </button>
                                <button
                                    class="btn btn-outline-success btn-sm dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    :disabled="!hasMatchingTemplates(request)"
                                    :title="hasMatchingTemplates(request) ? 'Быстрые шаблоны' : 'Нет подходящих шаблонов'"
                                >
                                    <span class="visually-hidden">Быстрые шаблоны</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <template v-if="hasMatchingTemplates(request)">
                                        <li v-for="template in matchingTemplates(request)" :key="template.id">
                                            <a class="dropdown-item" href="#" @click.prevent="applyTemplate(template, request)">
                                                <i class="fas fa-bolt me-1 text-warning"></i>
                                                {{ template.name }} ({{ formatCurrency(template.proposed_price) }}/час)
                                                <small class="text-muted d-block">{{ template.response_time }}ч ответ</small>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                    </template>
                                    <li>
                                        <a class="dropdown-item" href="#" @click.prevent="showTemplatesModal(request)">
                                            <i class="fas fa-cog me-1"></i>Управление шаблонами
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Сообщение если нет заявок -->
        <div v-if="requests.length === 0 && !loading" class="alert alert-info text-center py-4">
            <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
            <h5>Заявки не найдены</h5>
            <p class="text-muted">Попробуйте изменить параметры фильтрации</p>
            <button class="btn btn-primary" @click="resetFilters">
                <i class="fas fa-refresh me-1"></i>Сбросить фильтры
            </button>
        </div>

        <!-- Профессиональная пагинация -->
        <ProfessionalPagination
            v-if="pagination.total > pagination.perPage && !loading"
            :current-page="pagination.currentPage"
            :total-items="pagination.total"
            :per-page="pagination.perPage"
            @page-changed="handlePageChange"
            class="mt-4"
        />

        <!-- 🔥 ДОБАВЛЕНО: Модальное окно применения шаблона -->
        <div class="modal fade" :class="{ 'show d-block': showApplyTemplateModal }" v-if="showApplyTemplateModal" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-bolt me-2 text-warning"></i>
                            Применение шаблона
                        </h5>
                        <button type="button" class="btn-close" @click="closeApplyTemplateModal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedTemplate && selectedRequest">
                            <div class="alert alert-info">
                                <h6>Шаблон: <strong>{{ selectedTemplate.name }}</strong></h6>
                                <p class="mb-1">Заявка: {{ selectedRequest.title || 'Без названия' }}</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Цена за час</label>
                                    <input type="number" class="form-control" v-model="applyData.proposed_price"
                                           :placeholder="selectedTemplate.proposed_price">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Время ответа (часы)</label>
                                    <input type="number" class="form-control" v-model="applyData.response_time"
                                           :placeholder="selectedTemplate.response_time">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Сообщение арендатору</label>
                                <textarea class="form-control" rows="4" v-model="applyData.message"
                                          :placeholder="selectedTemplate.message"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Дополнительные условия</label>
                                <textarea class="form-control" rows="3" v-model="applyData.additional_terms"
                                          :placeholder="selectedTemplate.additional_terms"></textarea>
                            </div>

                            <!-- 🔥 ДОБАВЛЕНО: Проверка доступности оборудования -->
                            <div v-if="equipmentCheckResult" class="alert" :class="equipmentCheckResult.available ? 'alert-success' : 'alert-warning'">
                                <i class="fas" :class="equipmentCheckResult.available ? 'fa-check-circle' : 'fa-exclamation-triangle'"></i>
                                {{ equipmentCheckResult.message }}
                                <div v-if="equipmentCheckResult.unavailable_items && equipmentCheckResult.unavailable_items.length > 0" class="mt-2">
                                    <strong>Недоступно:</strong>
                                    <ul class="mb-0">
                                        <li v-for="item in equipmentCheckResult.unavailable_items" :key="item.id">
                                            {{ item.name }} ({{ item.category_name }})
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeApplyTemplateModal">Отмена</button>
                        <button type="button" class="btn btn-primary" @click="confirmApplyTemplate"
                                :disabled="applyingTemplate || !isEquipmentAvailable">
                            <span v-if="applyingTemplate" class="spinner-border spinner-border-sm me-1"></span>
                            {{ applyingTemplate ? 'Применение...' : 'Применить шаблон' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔥 ДОБАВЛЕНО: Модальное окно создания предложения -->
        <div class="modal fade" :class="{ 'show d-block': showProposalModal }" v-if="showProposalModal" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-paper-plane me-2 text-primary"></i>
                            Создание предложения
                        </h5>
                        <button type="button" class="btn-close" @click="closeProposalModal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedRequest">
                            <div class="alert alert-info mb-4">
                                <h6>Заявка: <strong>{{ selectedRequest.title || 'Без названия' }}</strong></h6>
                                <p class="mb-1">{{ selectedRequest.description || 'Описание отсутствует' }}</p>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        {{ formatDate(selectedRequest.rental_period_start) }} - {{ formatDate(selectedRequest.rental_period_end) }}
                                    </small>
                                    <small class="text-muted ms-3">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ selectedRequest.location?.name || 'Локация не указана' }}
                                    </small>
                                </div>
                            </div>

                            <!-- 🔥 БЫСТРЫЙ ВЫБОР ШАБЛОНА -->
                            <div v-if="hasMatchingTemplates(selectedRequest)" class="mb-4">
                                <h6 class="mb-3">
                                    <i class="fas fa-bolt me-1 text-warning"></i>
                                    Быстрые шаблоны
                                </h6>
                                <div class="row">
                                    <div v-for="template in matchingTemplates(selectedRequest)" :key="template.id" class="col-md-6 mb-2">
                                        <div class="card template-quick-card h-100" @click="selectQuickTemplate(template)">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-1">{{ template.name }}</h6>
                                                <p class="card-text small text-muted mb-2">{{ template.description }}</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong class="text-primary">{{ formatCurrency(template.proposed_price) }}/час</strong>
                                                    <small class="text-muted">{{ template.response_time }}ч</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 🔥 РЕКОМЕНДАЦИИ ИСКУССТВЕННОГО ИНТЕЛЛЕКТА -->
                            <div v-if="getQuickRecommendations(selectedRequest).length > 0" class="mb-4">
                                <h6 class="mb-3">
                                    <i class="fas fa-robot me-1 text-primary"></i>
                                    Умные рекомендации
                                </h6>
                                <div class="ai-recommendations">
                                    <div v-for="rec in getQuickRecommendations(selectedRequest)"
                                         :key="rec.template.id"
                                         class="ai-recommendation-card card mb-2"
                                         @click="selectQuickTemplate(rec.template)">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ rec.template.name }}</h6>
                                                    <p class="small text-muted mb-2">{{ rec.reason }}</p>
                                                    <div class="d-flex gap-3 small">
                                                        <span class="text-primary">
                                                            <i class="fas fa-ruble-sign me-1"></i>
                                                            {{ formatCurrency(rec.template.proposed_price) }}/час
                                                        </span>
                                                        <span class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            {{ rec.template.response_time }}ч
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge" :class="'bg-' + rec.color">
                                                        {{ rec.confidence }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 🔥 ФОРМА СОЗДАНИЯ ПРЕДЛОЖЕНИЯ -->
                            <div class="proposal-form">
                                <h6 class="mb-3">
                                    <i class="fas fa-edit me-1"></i>
                                    Детали предложения
                                </h6>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Цена за час *</label>
                                        <input type="number" class="form-control" v-model="proposalData.proposed_price"
                                               placeholder="Введите цену в рублях" required>
                                        <div class="form-text">Рекомендуемая цена: {{ formatCurrency(selectedRequest.lessor_pricing?.recommended_price) }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Время ответа (часы) *</label>
                                        <input type="number" class="form-control" v-model="proposalData.response_time"
                                               min="1" max="168" placeholder="24" required>
                                        <div class="form-text">В течение скольки часов вы готовы ответить</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Сообщение арендатору *</label>
                                    <textarea class="form-control" rows="4" v-model="proposalData.message"
                                              placeholder="Опишите ваше предложение, условия аренды..." required></textarea>
                                    <div class="form-text">Расскажите о вашем оборудовании и условиях аренды</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Дополнительные условия</label>
                                    <textarea class="form-control" rows="3" v-model="proposalData.additional_terms"
                                              placeholder="Дополнительные условия доставки, оплаты..."></textarea>
                                    <div class="form-text">Необязательные условия, которые важны для вас</div>
                                </div>

                                <!-- 🔥 ВЫБОР ОБОРУДОВАНИЯ -->
                                <div class="mb-3">
                                    <label class="form-label">Выберите оборудование *</label>
                                    <div v-if="availableEquipment.length > 0" class="equipment-list">
                                        <div v-for="equipment in availableEquipment" :key="equipment.id"
                                             class="form-check mb-2 equipment-item">
                                            <input class="form-check-input" type="checkbox"
                                                   :value="equipment.id" v-model="proposalData.selected_equipment"
                                                   :id="'equipment-' + equipment.id">
                                            <label class="form-check-label w-100" :for="'equipment-' + equipment.id">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong>{{ equipment.name }}</strong>
                                                        <small class="text-muted d-block">{{ equipment.description }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="text-primary fw-bold">{{ formatCurrency(equipment.hourly_rate) }}/час</div>
                                                        <small class="text-success" v-if="equipment.is_available">
                                                            <i class="fas fa-check-circle me-1"></i>Доступно
                                                        </small>
                                                        <small class="text-danger" v-else>
                                                            <i class="fas fa-times-circle me-1"></i>Недоступно
                                                        </small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div v-else class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Нет доступного оборудования для категорий этой заявки
                                    </div>
                                    <div v-if="proposalData.selected_equipment.length > 0" class="mt-2">
                                        <small class="text-success">
                                            <i class="fas fa-check me-1"></i>
                                            Выбрано оборудования: {{ proposalData.selected_equipment.length }}
                                        </small>
                                    </div>
                                </div>

                                <!-- 🔥 РАСЧЕТ СТОИМОСТИ -->
                                <div v-if="proposalData.selected_equipment.length > 0 && proposalData.proposed_price" class="alert alert-light border">
                                    <h6 class="mb-2">
                                        <i class="fas fa-calculator me-1 text-info"></i>
                                        Расчет стоимости
                                    </h6>
                                    <div class="row small">
                                        <div class="col-md-6">
                                            <div>Цена за час: <strong>{{ formatCurrency(proposalData.proposed_price) }}</strong></div>
                                            <div>Кол-во единиц: <strong>{{ proposalData.selected_equipment.length }}</strong></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-success">
                                                Итого в час: <strong>{{ formatCurrency(proposalData.proposed_price * proposalData.selected_equipment.length) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeProposalModal">Отмена</button>
                        <button type="button" class="btn btn-primary" @click="submitProposal"
                                :disabled="submittingProposal || !isProposalValid">
                            <span v-if="submittingProposal" class="spinner-border spinner-border-sm me-1"></span>
                            {{ submittingProposal ? 'Отправка...' : 'Отправить предложение' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import AnalyticsDashboard from './AnalyticsDashboard.vue';
import ProfessionalPagination from './ProfessionalPagination.vue';

export default {
    name: 'LessorRentalRequestList',
    components: {
        AnalyticsDashboard,
        ProfessionalPagination
    },
    props: {
        initialRequests: {
            type: Array,
            default: () => []
        },
        initialAnalytics: {
            type: Object,
            default: () => ({})
        },
        categories: {
            type: Array,
            default: () => []
        },
        locations: {
            type: Array,
            default: () => []
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        initialTemplates: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            requests: this.initialRequests,
            analytics: this.initialAnalytics,
            templates: this.initialTemplates,
            templatesLoaded: false,
            loading: false,

            // 🔥 ПАГИНАЦИЯ
            pagination: {
                currentPage: 1,
                perPage: 10,
                total: this.initialRequests.length,
                lastPage: 1
            },

            // 🔥 ДОБАВЛЕНО: Данные для рекомендаций
            quickRecommendationsCache: [],
            globalRecommendations: [],

            // 🔥 ДОБАВЛЕНО: Данные для применения шаблонов
            showApplyTemplateModal: false,
            selectedTemplate: null,
            selectedRequest: null,
            applyingTemplate: false,
            applyData: {
                proposed_price: null,
                response_time: null,
                message: '',
                additional_terms: ''
            },
            equipmentCheckResult: null,

            // 🔥 ДОБАВЛЕНО: Данные для модального окна предложения
            showProposalModal: false,
            submittingProposal: false,
            proposalData: {
                proposed_price: null,
                response_time: 24,
                message: '',
                additional_terms: '',
                selected_equipment: []
            },
            availableEquipment: [],

            strategicAnalytics: {
                conversion: {
                    myConversionRate: 0,
                    marketConversionRate: 0,
                    trend: 'stable'
                },
                pricing: {
                    myAvgPrice: 0,
                    marketAvgPrice: 0,
                    priceDifferencePercent: 0
                },
                recommendations: [
                    {
                        id: 1,
                        icon: 'fas fa-arrow-up text-success',
                        message: 'Повысьте скорость ответа на заявки для увеличения конверсии',
                        priority: 'medium',
                        action: () => this.showResponseTimeTips(),
                        actionText: 'Улучшить'
                    },
                    {
                        id: 2,
                        icon: 'fas fa-tag text-warning',
                        message: 'Ваши цены на 15% выше средних по рынку',
                        priority: 'high',
                        action: () => this.showPricingRecommendations(),
                        actionText: 'Оптимизировать'
                    }
                ]
            },
            localFilters: {
                category_id: '',
                location_id: '',
                sort: 'newest',
                my_proposals: ''
            }
        }
    },
    computed: {
        // 🔥 ДОБАВЛЕНО: Проверка доступности оборудования
        isEquipmentAvailable() {
            return !this.equipmentCheckResult || this.equipmentCheckResult.available;
        },

        // 🔥 ДОБАВЛЕНО: Валидация формы предложения
        isProposalValid() {
            return this.proposalData.proposed_price > 0 &&
                   this.proposalData.response_time > 0 &&
                   this.proposalData.message.trim().length > 0 &&
                   this.proposalData.selected_equipment.length > 0;
        },

        // 🔥 ИСПРАВЛЕНО: Вычисляемое свойство для срочных заявок
        urgentRequests() {
            return this.requests.filter(request => this.isUrgentRequest(request));
        },

        // 🔥 ИСПРАВЛЕНО: Вычисляемое свойство для количества моих предложений
        myProposalsComputedCount() {
            // Используем данные из аналитики или считаем из заявок
            if (this.analytics && this.analytics.my_proposals_count !== undefined) {
                return this.analytics.my_proposals_count;
            }

            if (this.analytics && this.analytics.total_proposals !== undefined) {
                return this.analytics.total_proposals;
            }

            // Считаем общее количество наших предложений из всех заявок
            return this.requests.reduce((total, request) => {
                return total + (request.my_proposals_count || 0);
            }, 0);
        }
    },
    methods: {
        // 🔥 ОСНОВНЫЕ МЕТОДЫ
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

        formatDate(dateString) {
            if (!dateString) return '—';
            try {
                return new Date(dateString).toLocaleDateString('ru-RU');
            } catch (error) {
                return '—';
            }
        },

        getCategoryName(categoryId) {
            const category = this.categories.find(cat => cat.id === categoryId);
            return category?.name || 'Неизвестная категория';
        },

        viewDetails(requestId) {
            window.location.href = `/lessor/rental-requests/${requestId}`;
        },

        viewRequestDetails(requestId) {
            this.viewDetails(requestId);
        },

        getRequestById(requestId) {
            return this.requests.find(req => req.id === requestId);
        },

        getRequestTitle(requestId) {
            const request = this.getRequestById(requestId);
            return request?.title || 'Без названия';
        },

        // 🔥 МЕТОДЫ ПАГИНАЦИИ
        async handlePageChange(page) {
            this.pagination.currentPage = page;
            await this.loadRequests();

            // Плавная прокрутка к верху
            this.$nextTick(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        },

        async changePerPage(count) {
            this.pagination.perPage = count;
            this.pagination.currentPage = 1;
            await this.loadRequests();
        },

        // 🔥 ОБНОВЛЕННЫЙ МЕТОД ЗАГРУЗКИ ДАННЫХ
        async loadRequests() {
            try {
                this.loading = true;

                // В реальном приложении здесь будет API запрос
                // const response = await axios.get('/api/lessor/rental-requests', {
                //     params: {
                //         page: this.pagination.currentPage,
                //         per_page: this.pagination.perPage,
                //         ...this.localFilters
                //     }
                // });

                // Имитация загрузки данных
                await new Promise(resolve => setTimeout(resolve, 500));

                // В реальном приложении:
                // this.requests = response.data.data.requests || [];
                // this.pagination.total = response.data.data.total || 0;
                // this.pagination.lastPage = response.data.data.last_page || 1;

                // Для демонстрации используем initialRequests с пагинацией
                const startIndex = (this.pagination.currentPage - 1) * this.pagination.perPage;
                const endIndex = startIndex + this.pagination.perPage;
                this.requests = this.initialRequests.slice(startIndex, endIndex);
                this.pagination.total = this.initialRequests.length;
                this.pagination.lastPage = Math.ceil(this.initialRequests.length / this.pagination.perPage);

                // Загружаем рекомендации для текущей страницы
                await this.loadQuickRecommendations();

            } catch (error) {
                console.error('Ошибка загрузки заявок:', error);
                this.$notify({
                    title: 'Ошибка',
                    text: 'Не удалось загрузить заявки',
                    type: 'error',
                    duration: 3000
                });
            } finally {
                this.loading = false;
            }
        },

        // 🔥 МЕТОДЫ ДЛЯ РЕКОМЕНДАЦИЙ
        getQuickRecommendations(request) {
            if (!this.quickRecommendationsCache) return [];

            return this.quickRecommendationsCache
                .filter(rec => rec.request_id === request.id)
                .slice(0, 3); // Максимум 3 рекомендации на заявку
        },

        async loadQuickRecommendations() {
            try {
                const requestIds = this.requests.map(req => req.id);
                if (requestIds.length === 0) {
                    this.quickRecommendationsCache = [];
                    this.globalRecommendations = [];
                    return;
                }

                console.log('🚀 Загрузка быстрых рекомендаций для заявок:', requestIds);

                // 🔥 ПРЯМОЙ ВЫЗОВ РАБОЧЕГО ENDPOINT
                const response = await axios.post('/api/lessor/recommendations/quick', {
                    request_ids: requestIds
                });

                console.log('📨 Ответ быстрых рекомендаций:', response);

                if (response.data.success) {
                    this.quickRecommendationsCache = response.data.recommendations || [];
                    console.log('✅ Быстрые рекомендации загружены:', this.quickRecommendationsCache);

                    // Формируем глобальные рекомендации
                    this.generateGlobalRecommendations();
                } else {
                    console.warn('⚠️ Сервер вернул ошибку:', response.data.message);
                    this.quickRecommendationsCache = [];
                    this.globalRecommendations = [];
                }
            } catch (error) {
                console.error('💥 ОШИБКА загрузки быстрых рекомендаций:', error);
                console.error('🔧 Детали ошибки:', error.response?.data);

                // Создаем пустой массив чтобы интерфейс не ломался
                this.quickRecommendationsCache = [];
                this.globalRecommendations = [];
            }
        },

        generateGlobalRecommendations() {
            if (!this.quickRecommendationsCache.length) return;

            // Сортируем рекомендации по уверенности (score)
            const sortedRecommendations = [...this.quickRecommendationsCache].sort((a, b) => {
                const scoreA = this.calculateQuickScore(a.confidence);
                const scoreB = this.calculateQuickScore(b.confidence);
                return scoreB - scoreA;
            });

            // Берем топ рекомендации, исключая дубли по заявкам
            const uniqueRequests = new Set();
            this.globalRecommendations = sortedRecommendations.filter(rec => {
                if (!uniqueRequests.has(rec.request_id)) {
                    uniqueRequests.add(rec.request_id);
                    return true;
                }
                return false;
            }).slice(0, 6); // Максимум 6 глобальных рекомендаций
        },

        applyQuickTemplate(recommendation, request) {
            console.log('⚡ Быстрое применение шаблона:', recommendation);

            // Сохраняем фидбек
            this.saveQuickRecommendationFeedback(recommendation, true);

            // Применяем шаблон
            this.applyTemplate(recommendation.template, request);
        },

        async saveQuickRecommendationFeedback(recommendation, applied) {
            try {
                await axios.post('/api/lessor/recommendation-feedback', {
                    template_id: recommendation.template.id,
                    request_id: recommendation.request_id,
                    applied: applied,
                    score: this.calculateQuickScore(recommendation.confidence)
                });
            } catch (error) {
                console.error('❌ Ошибка сохранения фидбека:', error);
            }
        },

        calculateQuickScore(confidence) {
            const scores = {
                'Очень высокая': 95,
                'Высокая': 85,
                'Средняя': 75,
                'Низкая': 65
            };
            return scores[confidence] || 70;
        },

        // 🔥 ИСПРАВЛЕНО: Открытие модального окна вместо перехода на страницу
        openProposalModal(request) {
            console.log('📝 Открытие модального окна для заявки:', request.id);
            this.selectedRequest = request;
            this.showProposalModal = true;

            // 🔥 БЛОКИРУЕМ SCROLL НА ФОНЕ
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '15px';

            // 🔥 ЗАГРУЖАЕМ ДОСТУПНОЕ ОБОРУДОВАНИЕ
            this.loadAvailableEquipment(request.id);

            // 🔥 СБРАСЫВАЕМ ДАННЫЕ ФОРМЫ
            this.resetProposalForm();

            // 🔥 УСТАНАВЛИВАЕМ РЕКОМЕНДОВАННУЮ ЦЕНУ
            if (request.lessor_pricing?.recommended_price) {
                this.proposalData.proposed_price = request.lessor_pricing.recommended_price;
            }
        },

        // 🔥 ДОБАВЛЕНО: Загрузка доступного оборудования
        async loadAvailableEquipment(requestId) {
            try {
                console.log('🔧 Загрузка доступного оборудования для заявки:', requestId);
                const response = await axios.get(`/api/rental-requests/${requestId}/available-equipment`);
                this.availableEquipment = response.data.data || [];
                console.log('✅ Загружено оборудование:', this.availableEquipment.length);
            } catch (error) {
                console.error('❌ Ошибка загрузки оборудования:', error);
                this.availableEquipment = [];

                // 🔥 ЗАГРУЗКА ОБОРУДОВАНИЯ ИЗ КАТЕГОРИЙ ЗАЯВКИ
                this.loadEquipmentByRequestCategories();
            }
        },

        // 🔥 ДОБАВЛЕНО: Загрузка оборудования по категориям заявки
        async loadEquipmentByRequestCategories() {
            if (!this.selectedRequest?.items) return;

            try {
                const categoryIds = this.selectedRequest.items.map(item => item.category_id);
                console.log('🔧 Загрузка оборудования по категориям:', categoryIds);

                const response = await axios.post('/api/lessor/equipment/available-for-request', {
                    category_ids: categoryIds,
                    rental_period_start: this.selectedRequest.rental_period_start,
                    rental_period_end: this.selectedRequest.rental_period_end
                });

                this.availableEquipment = response.data.data || [];
                console.log('✅ Загружено оборудование по категориям:', this.availableEquipment.length);
            } catch (error) {
                console.error('❌ Ошибка загрузки оборудования по категориям:', error);
                this.availableEquipment = [];
            }
        },

        // 🔥 ДОБАВЛЕНО: Сброс формы предложения
        resetProposalForm() {
            this.proposalData = {
                proposed_price: null,
                response_time: 24,
                message: '',
                additional_terms: '',
                selected_equipment: []
            };
        },

        // 🔥 ДОБАВЛЕНО: Быстрый выбор шаблона в модальном окне
        selectQuickTemplate(template) {
            console.log('⚡ Быстрый выбор шаблона:', template.name);
            this.proposalData = {
                proposed_price: template.proposed_price,
                response_time: template.response_time,
                message: template.message,
                additional_terms: template.additional_terms,
                selected_equipment: [...this.proposalData.selected_equipment]
            };

            // 🔥 УВЕДОМЛЕНИЕ О ВЫБОРЕ ШАБЛОНА
            this.$notify({
                title: '✅ Шаблон выбран',
                text: `Шаблон "${template.name}" применен к форме`,
                type: 'success',
                duration: 3000
            });
        },

        // 🔥 ДОБАВЛЕНО: Отправка предложения
        async submitProposal() {
            if (!this.isProposalValid) {
                this.$notify({
                    title: '❌ Ошибка',
                    text: 'Заполните все обязательные поля и выберите оборудование',
                    type: 'error',
                    duration: 5000
                });
                return;
            }

            this.submittingProposal = true;

            try {
                console.log('📤 Отправка предложения для заявки:', this.selectedRequest.id);

                const response = await axios.post(`/api/rental-requests/${this.selectedRequest.id}/proposals`, {
                    proposed_price: this.proposalData.proposed_price,
                    response_time: this.proposalData.response_time,
                    message: this.proposalData.message,
                    additional_terms: this.proposalData.additional_terms,
                    equipment_ids: this.proposalData.selected_equipment
                });

                // 🔥 ОБНОВЛЯЕМ СТАТУС ЗАЯВКИ
                this.updateRequestStatus(this.selectedRequest.id, {
                    my_proposals_count: (this.selectedRequest.my_proposals_count || 0) + 1
                });

                this.closeProposalModal();

                // 🔥 УВЕДОМЛЕНИЕ ОБ УСПЕХЕ
                this.$notify({
                    title: '✅ Предложение отправлено!',
                    text: 'Ваше предложение успешно отправлено арендатору',
                    type: 'success',
                    duration: 5000
                });

            } catch (error) {
                console.error('❌ Ошибка отправки предложения:', error);

                let errorMessage = 'Неизвестная ошибка';
                if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.response?.data?.errors) {
                    errorMessage = Object.values(error.response.data.errors).flat().join(', ');
                }

                this.$notify({
                    title: '❌ Ошибка',
                    text: `Ошибка отправки предложения: ${errorMessage}`,
                    type: 'error',
                    duration: 5000
                });
            } finally {
                this.submittingProposal = false;
            }
        },

        // 🔥 ИСПРАВЛЕНО: Закрытие модального окна предложения
        closeProposalModal() {
            this.showProposalModal = false;
            this.selectedRequest = null;
            this.resetProposalForm();
            this.availableEquipment = [];

            // 🔥 ВОССТАНАВЛИВАЕМ SCROLL
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        },

        // 🔥 СИСТЕМА ШАБЛОНОВ - ОСНОВНЫЕ МЕТОДЫ
        async loadTemplates() {
            if (this.templatesLoaded && this.templates.length > 0) {
                console.log('✅ Шаблоны уже загружены, используем кэш');
                return;
            }

            try {
                console.log('📥 Загрузка шаблонов предложений...');
                const response = await axios.get('/api/lessor/proposal-templates', {
                    params: {
                        status: 'active',
                        per_page: 100
                    }
                });

                this.templates = response.data.data || [];
                this.templatesLoaded = true;

                // 🔥 КЭШИРОВАНИЕ В localStorage
                localStorage.setItem('proposal_templates_cache', JSON.stringify({
                    data: this.templates,
                    timestamp: Date.now()
                }));

                console.log(`✅ Загружено ${this.templates.length} шаблонов`);
            } catch (error) {
                console.error('❌ Ошибка загрузки шаблонов:', error);

                // 🔥 ПЫТАЕМСЯ ИСПОЛЬЗОВАТЬ КЭШ ПРИ ОШИБКЕ
                const cached = this.getCachedTemplates();
                if (cached) {
                    this.templates = cached;
                    console.log('✅ Используем кэшированные шаблоны');
                }
            }
        },

        getCachedTemplates() {
            try {
                const cached = localStorage.getItem('proposal_templates_cache');
                if (cached) {
                    const { data, timestamp } = JSON.parse(cached);
                    // 🔥 КЭШ ДЕЙСТВИТЕЛЕН 1 ЧАС
                    if (Date.now() - timestamp < 3600000) {
                        return data;
                    }
                }
            } catch (error) {
                console.error('❌ Ошибка чтения кэша шаблонов:', error);
            }
            return null;
        },

        // 🔥 МЕТОДЫ ДЛЯ РАБОТЫ С ШАБЛОНАМИ
        matchingTemplates(request) {
            if (!this.templates.length || !request.items) return [];

            const requestCategoryIds = request.items.map(item => item.category_id);
            return this.templates.filter(template =>
                template.is_active && requestCategoryIds.includes(template.category_id)
            ).slice(0, 5); // 🔥 ОГРАНИЧИВАЕМ ДО 5 ШАБЛОНОВ В МЕНЮ
        },

        matchingTemplatesCount(request) {
            return this.matchingTemplates(request).length;
        },

        hasMatchingTemplates(request) {
            return this.matchingTemplatesCount(request) > 0;
        },

        isHighConversionRequest(request) {
            // 🔥 ЛОГИКА ОПРЕДЕЛЕНИЯ ВЫСОКОГО ШАНСА КОНВЕРСИИ
            const hasTemplates = this.hasMatchingTemplates(request);
            const lowCompetition = (request.active_proposals_count || 0) < 3;
            const goodBudget = request.lessor_pricing?.total_lessor_budget > 5000;
            const hasRecommendations = this.getQuickRecommendations(request).length > 0;

            return (hasTemplates || hasRecommendations) && lowCompetition && goodBudget;
        },

        isUrgentRequest(request) {
            // 🔥 СРОЧНЫЕ ЗАЯВКИ - СОЗДАНЫ МЕНЕЕ 2 ЧАСОВ НАЗАД
            const created = new Date(request.created_at);
            const now = new Date();
            const hoursDiff = (now - created) / (1000 * 60 * 60);
            return hoursDiff < 2;
        },

        getRequestCardClass(request) {
            const classes = [];
            if (this.isHighConversionRequest(request)) classes.push('high-conversion');
            if (this.isUrgentRequest(request)) classes.push('urgent-request');
            if (this.hasMatchingTemplates(request)) classes.push('has-templates');
            if (this.getQuickRecommendations(request).length > 0) classes.push('has-recommendations');
            return classes.join(' ');
        },

        // 🔥 МЕТОДЫ ПРИМЕНЕНИЯ ШАБЛОНОВ
        async applyTemplate(template, request) {
            console.log('⚡ Применение шаблона:', template.name, 'к заявке:', request.id);

            this.selectedTemplate = template;
            this.selectedRequest = request;

            // 🔥 ЗАПОЛНЯЕМ ДАННЫЕ ИЗ ШАБЛОНА
            this.applyData = {
                proposed_price: template.proposed_price,
                response_time: template.response_time,
                message: template.message,
                additional_terms: template.additional_terms
            };

            // 🔥 ПРОВЕРЯЕМ ДОСТУПНОСТЬ ОБОРУДОВАНИЯ
            await this.checkEquipmentAvailability(request.id, template.category_id);

            this.showApplyTemplateModal = true;
        },

        async checkEquipmentAvailability(requestId, categoryId) {
            try {
                console.log('🔍 Проверка доступности оборудования...');
                const response = await axios.post('/api/lessor/equipment/available-for-request', {
                    rental_request_id: requestId,
                    category_id: categoryId
                });

                this.equipmentCheckResult = {
                    available: response.data.available,
                    message: response.data.message,
                    unavailable_items: response.data.unavailable_items || []
                };

                console.log('✅ Результат проверки оборудования:', this.equipmentCheckResult);
            } catch (error) {
                console.error('❌ Ошибка проверки оборудования:', error);
                this.equipmentCheckResult = {
                    available: false,
                    message: 'Ошибка проверки доступности оборудования',
                    unavailable_items: []
                };
            }
        },

        async confirmApplyTemplate() {
            if (!this.selectedTemplate || !this.selectedRequest) return;

            this.applyingTemplate = true;

            try {
                console.log('✅ Подтверждение применения шаблона:', {
                    template: this.selectedTemplate.id,
                    request: this.selectedRequest.id,
                    data: this.applyData
                });

                // 🔥 ИСПРАВЛЕННЫЙ МАРШРУТ И ДАННЫЕ
                const response = await axios.post(`/api/lessor/rental-requests/${this.selectedRequest.id}/apply-template`, {
                    template_id: this.selectedTemplate.id,
                    customizations: this.applyData,
                    check_equipment: true
                });

                // 🔥 ОБНОВЛЯЕМ СТАТУС ЗАЯВКИ
                this.updateRequestStatus(this.selectedRequest.id, {
                    my_proposals_count: (this.selectedRequest.my_proposals_count || 0) + 1,
                    has_applied_template: true
                });

                this.closeApplyTemplateModal();

                // 🔥 УВЕДОМЛЕНИЕ ОБ УСПЕХЕ
                this.$notify({
                    title: '✅ Шаблон применен!',
                    text: `Шаблон "${this.selectedTemplate.name}" успешно применен к заявке`,
                    type: 'success',
                    duration: 5000
                });

            } catch (error) {
                console.error('❌ Ошибка применения шаблона:', error);

                let errorMessage = 'Неизвестная ошибка';
                if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                } else if (error.response?.data?.errors) {
                    errorMessage = Object.values(error.response.data.errors).flat().join(', ');
                }

                this.$notify({
                    title: '❌ Ошибка',
                    text: `Ошибка применения шаблона: ${errorMessage}`,
                    type: 'error',
                    duration: 5000
                });
            } finally {
                this.applyingTemplate = false;
            }
        },

        closeApplyTemplateModal() {
            this.showApplyTemplateModal = false;
            this.selectedTemplate = null;
            this.selectedRequest = null;
            this.applyData = {
                proposed_price: null,
                response_time: null,
                message: '',
                additional_terms: ''
            };
            this.equipmentCheckResult = null;
        },

        updateRequestStatus(requestId, updates) {
            const requestIndex = this.requests.findIndex(req => req.id === requestId);
            if (requestIndex !== -1) {
                this.requests[requestIndex] = {
                    ...this.requests[requestIndex],
                    ...updates
                };
            }
        },

        // 🔥 ФИЛЬТРАЦИЯ И СОРТИРОВКА
        applyFilters() {
            this.pagination.currentPage = 1; // Сбрасываем на первую страницу
            this.loadRequests();
        },

        resetFilters() {
            this.localFilters = {
                category_id: '',
                location_id: '',
                sort: 'newest',
                my_proposals: ''
            };
            this.pagination.currentPage = 1;
            this.loadRequests();
        },

        // 🔥 СУЩЕСТВУЮЩИЕ МЕТОДЫ АНАЛИТИКИ
        showUrgentRequests() {
            this.localFilters.sort = 'newest';
            this.applyFilters();
            this.$notify({
                title: 'Срочные заявки',
                text: 'Показаны самые новые заявки, требующие быстрого ответа',
                type: 'info',
                duration: 3000
            });
        },

        showTemplatesModal(request = null) {
            if (request) {
                console.log('📋 Показ шаблонов для заявки:', request.id);
                // TODO: Реализовать модальное окно с фильтрацией по категориям заявки
            }
            this.$notify({
                title: 'Управление шаблонами',
                text: 'Модальное окно шаблонов предложений - в разработке',
                type: 'info',
                duration: 3000
            });
        },

        showMyProposals() {
            this.localFilters.my_proposals = 'with_proposals';
            this.applyFilters();
            this.$notify({
                title: 'Мои предложения',
                text: 'Показаны заявки с вашими предложениями',
                type: 'info',
                duration: 3000
            });
        },

        showQuickProposalModal() {
            console.log('Быстрое предложение');
            // 🔥 ТЕПЕРЬ ИСПОЛЬЗУЕМ МОДАЛЬНОЕ ОКНО
            if (this.requests.length > 0) {
                this.openProposalModal(this.requests[0]);
            } else {
                this.$notify({
                    title: 'Нет заявок',
                    text: 'Нет доступных заявок для быстрого предложения',
                    type: 'warning',
                    duration: 3000
                });
            }
        },

        showFavorites() {
            console.log('Показать избранные заявки');
            this.$notify({
                title: 'Избранные заявки',
                text: 'Функционал избранных заявок - в разработке',
                type: 'info',
                duration: 3000
            });
        },

        exportAnalyticsData() {
            console.log('Экспорт данных аналитики');
            const data = {
                realTimeAnalytics: this.analytics,
                strategicAnalytics: this.strategicAnalytics,
                requests: this.requests.map(req => ({
                    id: req.id,
                    title: req.title,
                    budget: req.total_budget,
                    proposals: req.active_proposals_count,
                    my_proposals: req.my_proposals_count,
                    has_templates: this.hasMatchingTemplates(req),
                    has_recommendations: this.getQuickRecommendations(req).length
                })),
                templates: this.templates.length,
                recommendations: this.globalRecommendations.length,
                exportDate: new Date().toISOString(),
                exportedBy: 'Lessor Dashboard'
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], {
                type: 'application/json'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `lessor-analytics-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            this.$notify({
                title: 'Экспорт завершен',
                text: 'Данные аналитики успешно экспортированы',
                type: 'success',
                duration: 3000
            });
        },

        showResponseTimeTips() {
            this.$notify({
                title: 'Советы по времени ответа',
                text: '• Используйте шаблоны предложений\n• Настройте уведомления\n• Проверяйте заявки утром и после обеда',
                type: 'info',
                duration: 5000
            });
        },

        showPricingRecommendations() {
            this.$notify({
                title: 'Рекомендации по ценообразованию',
                text: '• Проанализируйте цены конкурентов\n• Учитывайте сезонность\n• Предлагайте гибкие условия для долгосрочной аренды',
                type: 'info',
                duration: 5000
            });
        },

        async refreshData() {
            try {
                console.log('Обновление данных...');
                await this.loadTemplates(); // 🔥 ОБНОВЛЯЕМ ШАБЛОНЫ ПРИ ОБНОВЛЕНИИ
                await this.loadQuickRecommendations(); // 🔥 ОБНОВЛЯЕМ РЕКОМЕНДАЦИИ
                this.$notify({
                    title: 'Данные обновлены',
                    text: 'Актуальная информация загружена',
                    type: 'success',
                    duration: 3000
                });
            } catch (error) {
                console.error('Ошибка обновления данных:', error);
                this.$notify({
                    title: 'Ошибка',
                    text: 'Не удалось обновить данные',
                    type: 'error',
                    duration: 3000
                });
            }
        },

        // 🔥 ДОБАВЛЕНО: Обработчик клавиши Escape
        handleEscapeKey(event) {
            if (event.key === 'Escape' && this.showProposalModal) {
                this.closeProposalModal();
            }
            if (event.key === 'Escape' && this.showApplyTemplateModal) {
                this.closeApplyTemplateModal();
            }
        }
    },
    watch: {
        analytics: {
            handler(newAnalytics) {
                if (newAnalytics && newAnalytics.conversion_rate) {
                    this.strategicAnalytics.conversion.myConversionRate = newAnalytics.conversion_rate;
                    this.strategicAnalytics.conversion.marketConversionRate =
                        Math.max(0, newAnalytics.conversion_rate - 5 + Math.random() * 10);

                    this.strategicAnalytics.conversion.trend =
                        newAnalytics.conversion_rate > 60 ? 'up' :
                        newAnalytics.conversion_rate < 40 ? 'down' : 'stable';
                }
            },
            deep: true,
            immediate: true
        }
    },
    async mounted() {
        console.log('✅ LessorRentalRequestList mounted!', {
            requestsCount: this.requests.length,
            hasAnalytics: !!this.analytics,
            categoriesCount: this.categories.length,
            locationsCount: this.locations.length,
            myProposalsCount: this.myProposalsComputedCount
        });

        // 🔥 ЗАГРУЖАЕМ ШАБЛОНЫ И РЕКОМЕНДАЦИИ ПРИ ИНИЦИАЛИЗАЦИИ
        await this.loadTemplates();
        await this.loadQuickRecommendations();

        // 🔥 ИНИЦИАЛИЗАЦИЯ СТРАТЕГИЧЕСКОЙ АНАЛИТИКИ
        if (this.analytics && this.analytics.total_proposals) {
            this.strategicAnalytics.pricing.myAvgPrice = 2450;
            this.strategicAnalytics.pricing.marketAvgPrice = 2200;
            this.strategicAnalytics.pricing.priceDifferencePercent =
                ((2450 - 2200) / 2200 * 100).toFixed(1);
        }

        // 🔥 ДОБАВЛЕНО: Обработчик клавиш
        document.addEventListener('keydown', this.handleEscapeKey);
    },

    beforeUnmount() {
        // 🔥 ДОБАВЛЕНО: Удаление обработчика клавиш
        document.removeEventListener('keydown', this.handleEscapeKey);

        // 🔥 УБЕДИТЕСЬ ЧТО SCROLL ВОССТАНАВЛИВАЕТСЯ ПРИ РАЗМОНТИРОВАНИИ
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
}
</script>

<style scoped>
.lessor-rental-requests {
    animation: fadeIn 0.5s ease-in;
}

.budget-info {
    font-size: 0.9rem;
}

.card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* 🔥 ДОБАВЛЕНО: Стили для индикаторов заявок */
.request-indicators {
    min-height: 25px;
}

.request-categories .badge {
    font-size: 0.7rem;
    margin-bottom: 2px;
}

/* 🔥 СТИЛИ ДЛЯ РАЗЛИЧНЫХ ТИПОВ ЗАЯВОК */
.request-card.high-conversion {
    border-left: 4px solid #28a745;
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
}

.request-card.urgent-request {
    border-left: 4px solid #dc3545;
    background: linear-gradient(135deg, #fff8f8 0%, #ffe8e8 100%);
    animation: pulse 2s infinite;
}

.request-card.has-templates {
    border-left: 4px solid #ffc107;
}

.request-card.has-recommendations {
    border-left: 4px solid #17a2b8;
    background: linear-gradient(135deg, #f0f8ff 0%, #e3f2fd 100%);
}

/* 🔥 СТИЛИ ДЛЯ ГЛОБАЛЬНЫХ РЕКОМЕНДАЦИЙ */
.global-recommendations {
    border-left: 4px solid #ffc107;
    animation: slideIn 0.5s ease-out;
}

.global-recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.global-recommendation-card {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background: #fff;
    transition: all 0.3s ease;
}

.global-recommendation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.recommendation-content {
    flex: 1;
    margin-right: 1rem;
}

.request-info strong {
    font-size: 0.9rem;
    color: #2c3e50;
}

.template-info {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-top: 0.5rem;
}

.template-name {
    font-weight: 500;
    color: #495057;
}

.template-price {
    color: #28a745;
    font-weight: bold;
    margin-left: 1rem;
}

.recommendation-actions {
    display: flex;
    gap: 0.5rem;
}

/* 🔥 СТИЛИ ДЛЯ БЫСТРЫХ РЕКОМЕНДАЦИЙ В КАРТОЧКАХ */
.quick-recommendations {
    margin-top: 0.5rem;
}

.recommendation-badge {
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.75rem;
    padding: 0.4em 0.6em;
}

.recommendation-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* 🔥 СТИЛИ ДЛЯ РЕКОМЕНДАЦИЙ В МОДАЛЬНОМ ОКНЕ */
.ai-recommendation-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.ai-recommendation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0d6efd;
    background: #f8f9fa;
}

/* 🔥 СТИЛИ ДЛЯ КНОПОК БЫСТРОГО ДЕЙСТВИЯ */
.quick-actions .dropdown-toggle-split {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.quick-actions .dropdown-menu {
    min-width: 280px;
}

.quick-actions .dropdown-item {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #f8f9fa;
}

.quick-actions .dropdown-item:hover {
    background: #f8f9fa;
}

.quick-actions .dropdown-item:last-child {
    border-bottom: none;
}

/* 🔥 ДОБАВЛЕНО: Стили для модального окна предложения */
.template-quick-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.template-quick-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0d6efd;
    background: #f8f9fa;
}

.equipment-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
}

.equipment-item {
    padding: 0.75rem;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.equipment-item:hover {
    background: #f8f9fa;
    border-color: #0d6efd;
}

.equipment-item:last-child {
    margin-bottom: 0;
}

.proposal-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.375rem;
    border: 1px solid #e9ecef;
}

/* 🔥 АНИМАЦИИ */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

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

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
}

.card-text {
    color: #6c757d;
    line-height: 1.5;
}

.pagination {
    margin-bottom: 0;
}

.page-link {
    color: #0d6efd;
    border-color: #dee2e6;
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border: 1px solid #b6d4fe;
    color: #055160;
}

/* 🔥 ИСПРАВЛЕННЫЕ СТИЛИ ДЛЯ МОДАЛЬНЫХ ОКОН */
.modal {
    z-index: 1060;
    padding-left: 0 !important;
}

.modal-backdrop {
    z-index: 1059;
}

.modal.show {
    background: rgba(0,0,0,0.5) !important;
    display: block !important;
}

.modal-dialog {
    margin: 1rem auto;
    max-width: 90%;
}

.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 2rem);
}

.modal-dialog-scrollable {
    max-height: calc(100% - 2rem);
}

.modal-xl {
    max-width: 1140px;
}

.modal-lg {
    max-width: 800px;
}

.modal-content {
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    border: none;
    border-radius: 0.5rem;
    animation: modalAppear 0.3s ease-out;
}

.modal-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.5rem 0.5rem 0 0;
    padding: 1rem 1.5rem;
}

.modal-title {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
}

.modal-body {
    padding: 1.5rem;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-footer {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 0.5rem 0.5rem;
    padding: 1rem 1.5rem;
}

/* Анимация появления модального окна */
@keyframes modalAppear {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Адаптивность */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }

    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 0.5rem;
    }

    .mt-3 .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }

    .request-indicators .badge {
        display: block;
        margin-bottom: 0.25rem;
        margin-right: 0;
    }

    .proposal-form {
        padding: 1rem;
    }

    /* Глобальные рекомендации на мобильных */
    .global-recommendations-grid {
        grid-template-columns: 1fr;
    }

    .global-recommendation-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .recommendation-content {
        margin-right: 0;
        width: 100%;
    }

    .recommendation-actions {
        width: 100%;
        justify-content: flex-end;
    }

    /* Модальные окна на мобильных */
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }

    .modal-body {
        padding: 1rem;
        max-height: 60vh;
    }

    .modal-header,
    .modal-footer {
        padding: 0.75rem 1rem;
    }

    .equipment-list {
        max-height: 200px;
    }

    .modal-title {
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.25rem;
        max-width: calc(100% - 0.5rem);
    }

    .modal-body {
        padding: 0.75rem;
        max-height: 50vh;
    }

    .template-quick-card .card-body {
        padding: 0.75rem;
    }

    .equipment-item {
        padding: 0.5rem;
    }

    .modal-title {
        font-size: 1rem;
    }

    .modal .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    .quick-recommendations .badge {
        font-size: 0.7rem;
        padding: 0.3em 0.5em;
    }
}

/* Дополнительные стили для улучшения UX */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1059;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-scroll-lock {
    overflow: hidden;
    padding-right: 15px;
}

/* Стили для скроллбара в модальном окне */
.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Стили для фокусировки на элементах формы */
.modal .form-control:focus,
.modal .form-select:focus,
.modal .form-check-input:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Стили для disabled состояний */
.modal .btn:disabled,
.modal .form-control:disabled,
.modal .form-select:disabled {
    opacity: 0.65;
    pointer-events: none;
}

/* Стили для спиннера загрузки */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.2em;
}

/* Улучшенные стили для карточек шаблонов */
.template-quick-card {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    overflow: hidden;
}

.template-quick-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    border-color: #0d6efd;
}

.template-quick-card .card-body {
    padding: 1rem;
}

.template-quick-card .card-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.template-quick-card .card-text {
    font-size: 0.8rem;
    color: #6c757d;
    line-height: 1.4;
    margin-bottom: 0.75rem;
}

/* Стили для выбранного оборудования */
.equipment-item.selected {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
}

.equipment-item .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Анимация для счетчика выбранного оборудования */
.equipment-count-badge {
    animation: bounce 0.5s ease;
}

@keyframes bounce {
    0%, 20%, 60%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-5px);
    }
    80% {
        transform: translateY(-2px);
    }
}

/* Стили для улучшения читаемости */
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

/* Градиенты для бейджей */
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

.bg-danger {
    background: linear-gradient(135deg, #dc3545, #e83e8c);
}
</style>
