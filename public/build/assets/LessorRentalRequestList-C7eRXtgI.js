var __defProp = Object.defineProperty;
var __getOwnPropSymbols = Object.getOwnPropertySymbols;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __propIsEnum = Object.prototype.propertyIsEnumerable;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __spreadValues = (a, b) => {
  for (var prop in b || (b = {}))
    if (__hasOwnProp.call(b, prop))
      __defNormalProp(a, prop, b[prop]);
  if (__getOwnPropSymbols)
    for (var prop of __getOwnPropSymbols(b)) {
      if (__propIsEnum.call(b, prop))
        __defNormalProp(a, prop, b[prop]);
    }
  return a;
};
var __async = (__this, __arguments, generator) => {
  return new Promise((resolve, reject) => {
    var fulfilled = (value) => {
      try {
        step(generator.next(value));
      } catch (e) {
        reject(e);
      }
    };
    var rejected = (value) => {
      try {
        step(generator.throw(value));
      } catch (e) {
        reject(e);
      }
    };
    var step = (x) => x.done ? resolve(x.value) : Promise.resolve(x.value).then(fulfilled, rejected);
    step((generator = generator.apply(__this, __arguments)).next());
  });
};
import AnalyticsDashboard from "./AnalyticsDashboard-CEbJfY5w.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { a as createElementBlock, e as createCommentVNode, o as openBlock, b as createBaseVNode, n as normalizeClass, F as Fragment, r as renderList, t as toDisplayString, g as resolveComponent, i as createVNode, x as createBlock, w as withDirectives, v as vModelSelect, h as createStaticVNode, d as createTextVNode, u as withModifiers, j as vModelText, s as vModelCheckbox } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import "./RealTimeAnalytics-CyTuuux6.js";
import "./StrategicAnalytics-fHkTu3z6.js";
import "./ProposalTemplates-DKXx8w66.js";
import "./QuickActionCard-Dqb4OqtC.js";
import "./sweetalert2.esm.all-DkqDp_b4.js";
const _sfc_main$1 = {
  name: "ProfessionalPagination",
  props: {
    currentPage: {
      type: Number,
      required: true,
      default: 1
    },
    totalItems: {
      type: Number,
      required: true,
      default: 0
    },
    perPage: {
      type: Number,
      default: 10
    },
    maxVisiblePages: {
      type: Number,
      default: 5
    }
  },
  computed: {
    totalPages() {
      return Math.ceil(this.totalItems / this.perPage);
    },
    shouldShowPagination() {
      return this.totalPages > 1;
    },
    showingStart() {
      return (this.currentPage - 1) * this.perPage + 1;
    },
    showingEnd() {
      const end = this.currentPage * this.perPage;
      return end > this.totalItems ? this.totalItems : end;
    },
    visiblePages() {
      const pages = [];
      const half = Math.floor(this.maxVisiblePages / 2);
      let start = Math.max(1, this.currentPage - half);
      let end = Math.min(this.totalPages, start + this.maxVisiblePages - 1);
      if (end - start + 1 < this.maxVisiblePages) {
        start = Math.max(1, end - this.maxVisiblePages + 1);
      }
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    },
    showFirstPage() {
      return this.visiblePages[0] > 1;
    },
    showLastPage() {
      return this.visiblePages[this.visiblePages.length - 1] < this.totalPages;
    },
    showLeftEllipsis() {
      return this.visiblePages[0] > 2;
    },
    showRightEllipsis() {
      return this.visiblePages[this.visiblePages.length - 1] < this.totalPages - 1;
    }
  },
  methods: {
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
        this.$emit("page-changed", page);
      }
    }
  }
};
const _hoisted_1$1 = {
  key: 0,
  class: "professional-pagination"
};
const _hoisted_2$1 = { "aria-label": "Навигация по страницам" };
const _hoisted_3$1 = { class: "pagination justify-content-center mb-0" };
const _hoisted_4$1 = ["disabled"];
const _hoisted_5$1 = {
  key: 0,
  class: "page-item"
};
const _hoisted_6$1 = {
  key: 1,
  class: "page-item disabled"
};
const _hoisted_7$1 = ["onClick", "aria-current"];
const _hoisted_8$1 = {
  key: 2,
  class: "page-item disabled"
};
const _hoisted_9$1 = {
  key: 3,
  class: "page-item"
};
const _hoisted_10$1 = ["disabled"];
const _hoisted_11$1 = { class: "pagination-info text-center mt-2" };
const _hoisted_12$1 = { class: "text-muted" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return $options.shouldShowPagination ? (openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("nav", _hoisted_2$1, [
      createBaseVNode("ul", _hoisted_3$1, [
        createBaseVNode("li", {
          class: normalizeClass(["page-item", { "disabled": $props.currentPage === 1 }])
        }, [
          createBaseVNode("button", {
            class: "page-link",
            onClick: _cache[0] || (_cache[0] = ($event) => $options.goToPage($props.currentPage - 1)),
            disabled: $props.currentPage === 1,
            "aria-label": "Предыдущая страница"
          }, [..._cache[4] || (_cache[4] = [
            createBaseVNode("i", { class: "fas fa-chevron-left" }, null, -1)
          ])], 8, _hoisted_4$1)
        ], 2),
        $options.showFirstPage ? (openBlock(), createElementBlock("li", _hoisted_5$1, [
          createBaseVNode("button", {
            class: "page-link",
            onClick: _cache[1] || (_cache[1] = ($event) => $options.goToPage(1))
          }, "1")
        ])) : createCommentVNode("", true),
        $options.showLeftEllipsis ? (openBlock(), createElementBlock("li", _hoisted_6$1, [..._cache[5] || (_cache[5] = [
          createBaseVNode("span", { class: "page-link" }, "...", -1)
        ])])) : createCommentVNode("", true),
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.visiblePages, (page) => {
          return openBlock(), createElementBlock("li", {
            key: page,
            class: normalizeClass(["page-item", { "active": page === $props.currentPage }])
          }, [
            createBaseVNode("button", {
              class: "page-link",
              onClick: ($event) => $options.goToPage(page),
              "aria-current": page === $props.currentPage ? "page" : null
            }, toDisplayString(page), 9, _hoisted_7$1)
          ], 2);
        }), 128)),
        $options.showRightEllipsis ? (openBlock(), createElementBlock("li", _hoisted_8$1, [..._cache[6] || (_cache[6] = [
          createBaseVNode("span", { class: "page-link" }, "...", -1)
        ])])) : createCommentVNode("", true),
        $options.showLastPage ? (openBlock(), createElementBlock("li", _hoisted_9$1, [
          createBaseVNode("button", {
            class: "page-link",
            onClick: _cache[2] || (_cache[2] = ($event) => $options.goToPage($options.totalPages))
          }, toDisplayString($options.totalPages), 1)
        ])) : createCommentVNode("", true),
        createBaseVNode("li", {
          class: normalizeClass(["page-item", { "disabled": $props.currentPage === $options.totalPages }])
        }, [
          createBaseVNode("button", {
            class: "page-link",
            onClick: _cache[3] || (_cache[3] = ($event) => $options.goToPage($props.currentPage + 1)),
            disabled: $props.currentPage === $options.totalPages,
            "aria-label": "Следующая страница"
          }, [..._cache[7] || (_cache[7] = [
            createBaseVNode("i", { class: "fas fa-chevron-right" }, null, -1)
          ])], 8, _hoisted_10$1)
        ], 2)
      ])
    ]),
    createBaseVNode("div", _hoisted_11$1, [
      createBaseVNode("small", _hoisted_12$1, " Показано " + toDisplayString($options.showingStart) + "-" + toDisplayString($options.showingEnd) + " из " + toDisplayString($props.totalItems) + " заявок ", 1)
    ])
  ])) : createCommentVNode("", true);
}
const ProfessionalPagination = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-0c68e5a5"]]);
const _sfc_main = {
  name: "LessorRentalRequestList",
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
        message: "",
        additional_terms: ""
      },
      equipmentCheckResult: null,
      // 🔥 ДОБАВЛЕНО: Данные для модального окна предложения
      showProposalModal: false,
      submittingProposal: false,
      proposalData: {
        proposed_price: null,
        response_time: 24,
        message: "",
        additional_terms: "",
        selected_equipment: []
      },
      availableEquipment: [],
      strategicAnalytics: {
        conversion: {
          myConversionRate: 0,
          marketConversionRate: 0,
          trend: "stable"
        },
        pricing: {
          myAvgPrice: 0,
          marketAvgPrice: 0,
          priceDifferencePercent: 0
        },
        recommendations: [
          {
            id: 1,
            icon: "fas fa-arrow-up text-success",
            message: "Повысьте скорость ответа на заявки для увеличения конверсии",
            priority: "medium",
            action: () => this.showResponseTimeTips(),
            actionText: "Улучшить"
          },
          {
            id: 2,
            icon: "fas fa-tag text-warning",
            message: "Ваши цены на 15% выше средних по рынку",
            priority: "high",
            action: () => this.showPricingRecommendations(),
            actionText: "Оптимизировать"
          }
        ]
      },
      localFilters: {
        category_id: "",
        location_id: "",
        sort: "newest",
        my_proposals: ""
      }
    };
  },
  computed: {
    // 🔥 ДОБАВЛЕНО: Проверка доступности оборудования
    isEquipmentAvailable() {
      return !this.equipmentCheckResult || this.equipmentCheckResult.available;
    },
    // 🔥 ДОБАВЛЕНО: Валидация формы предложения
    isProposalValid() {
      return this.proposalData.proposed_price > 0 && this.proposalData.response_time > 0 && this.proposalData.message.trim().length > 0 && this.proposalData.selected_equipment.length > 0;
    },
    // 🔥 ИСПРАВЛЕНО: Вычисляемое свойство для срочных заявок
    urgentRequests() {
      return this.requests.filter((request) => this.isUrgentRequest(request));
    },
    // 🔥 ИСПРАВЛЕНО: Вычисляемое свойство для количества моих предложений
    myProposalsComputedCount() {
      if (this.analytics && this.analytics.my_proposals_count !== void 0) {
        return this.analytics.my_proposals_count;
      }
      if (this.analytics && this.analytics.total_proposals !== void 0) {
        return this.analytics.total_proposals;
      }
      return this.requests.reduce((total, request) => {
        return total + (request.my_proposals_count || 0);
      }, 0);
    }
  },
  methods: {
    // 🔥 ОСНОВНЫЕ МЕТОДЫ
    formatCurrency(amount) {
      if (!amount && amount !== 0) return "0 ₽";
      try {
        return new Intl.NumberFormat("ru-RU", {
          style: "currency",
          currency: "RUB",
          minimumFractionDigits: 0
        }).format(amount);
      } catch (error) {
        console.error("Ошибка форматирования валюты:", error);
        return "0 ₽";
      }
    },
    formatDate(dateString) {
      if (!dateString) return "—";
      try {
        return new Date(dateString).toLocaleDateString("ru-RU");
      } catch (error) {
        return "—";
      }
    },
    getCategoryName(categoryId) {
      const category = this.categories.find((cat) => cat.id === categoryId);
      return (category == null ? void 0 : category.name) || "Неизвестная категория";
    },
    viewDetails(requestId) {
      window.location.href = `/lessor/rental-requests/${requestId}`;
    },
    viewRequestDetails(requestId) {
      this.viewDetails(requestId);
    },
    getRequestById(requestId) {
      return this.requests.find((req) => req.id === requestId);
    },
    getRequestTitle(requestId) {
      const request = this.getRequestById(requestId);
      return (request == null ? void 0 : request.title) || "Без названия";
    },
    // 🔥 МЕТОДЫ ПАГИНАЦИИ
    handlePageChange(page) {
      return __async(this, null, function* () {
        this.pagination.currentPage = page;
        yield this.loadRequests();
        this.$nextTick(() => {
          window.scrollTo({ top: 0, behavior: "smooth" });
        });
      });
    },
    changePerPage(count) {
      return __async(this, null, function* () {
        this.pagination.perPage = count;
        this.pagination.currentPage = 1;
        yield this.loadRequests();
      });
    },
    // 🔥 ОБНОВЛЕННЫЙ МЕТОД ЗАГРУЗКИ ДАННЫХ
    loadRequests() {
      return __async(this, null, function* () {
        try {
          this.loading = true;
          yield new Promise((resolve) => setTimeout(resolve, 500));
          const startIndex = (this.pagination.currentPage - 1) * this.pagination.perPage;
          const endIndex = startIndex + this.pagination.perPage;
          this.requests = this.initialRequests.slice(startIndex, endIndex);
          this.pagination.total = this.initialRequests.length;
          this.pagination.lastPage = Math.ceil(this.initialRequests.length / this.pagination.perPage);
          yield this.loadQuickRecommendations();
        } catch (error) {
          console.error("Ошибка загрузки заявок:", error);
          this.$notify({
            title: "Ошибка",
            text: "Не удалось загрузить заявки",
            type: "error",
            duration: 3e3
          });
        } finally {
          this.loading = false;
        }
      });
    },
    // 🔥 МЕТОДЫ ДЛЯ РЕКОМЕНДАЦИЙ
    getQuickRecommendations(request) {
      if (!this.quickRecommendationsCache) return [];
      return this.quickRecommendationsCache.filter((rec) => rec.request_id === request.id).slice(0, 3);
    },
    loadQuickRecommendations() {
      return __async(this, null, function* () {
        var _a;
        try {
          const requestIds = this.requests.map((req) => req.id);
          if (requestIds.length === 0) {
            this.quickRecommendationsCache = [];
            this.globalRecommendations = [];
            return;
          }
          console.log("🚀 Загрузка быстрых рекомендаций для заявок:", requestIds);
          const response = yield axios.post("/api/lessor/recommendations/quick", {
            request_ids: requestIds
          });
          console.log("📨 Ответ быстрых рекомендаций:", response);
          if (response.data.success) {
            this.quickRecommendationsCache = response.data.recommendations || [];
            console.log("✅ Быстрые рекомендации загружены:", this.quickRecommendationsCache);
            this.generateGlobalRecommendations();
          } else {
            console.warn("⚠️ Сервер вернул ошибку:", response.data.message);
            this.quickRecommendationsCache = [];
            this.globalRecommendations = [];
          }
        } catch (error) {
          console.error("💥 ОШИБКА загрузки быстрых рекомендаций:", error);
          console.error("🔧 Детали ошибки:", (_a = error.response) == null ? void 0 : _a.data);
          this.quickRecommendationsCache = [];
          this.globalRecommendations = [];
        }
      });
    },
    generateGlobalRecommendations() {
      if (!this.quickRecommendationsCache.length) return;
      const sortedRecommendations = [...this.quickRecommendationsCache].sort((a, b) => {
        const scoreA = this.calculateQuickScore(a.confidence);
        const scoreB = this.calculateQuickScore(b.confidence);
        return scoreB - scoreA;
      });
      const uniqueRequests = /* @__PURE__ */ new Set();
      this.globalRecommendations = sortedRecommendations.filter((rec) => {
        if (!uniqueRequests.has(rec.request_id)) {
          uniqueRequests.add(rec.request_id);
          return true;
        }
        return false;
      }).slice(0, 6);
    },
    applyQuickTemplate(recommendation, request) {
      console.log("⚡ Быстрое применение шаблона:", recommendation);
      this.saveQuickRecommendationFeedback(recommendation, true);
      this.applyTemplate(recommendation.template, request);
    },
    saveQuickRecommendationFeedback(recommendation, applied) {
      return __async(this, null, function* () {
        try {
          yield axios.post("/api/lessor/recommendation-feedback", {
            template_id: recommendation.template.id,
            request_id: recommendation.request_id,
            applied,
            score: this.calculateQuickScore(recommendation.confidence)
          });
        } catch (error) {
          console.error("❌ Ошибка сохранения фидбека:", error);
        }
      });
    },
    calculateQuickScore(confidence) {
      const scores = {
        "Очень высокая": 95,
        "Высокая": 85,
        "Средняя": 75,
        "Низкая": 65
      };
      return scores[confidence] || 70;
    },
    // 🔥 ИСПРАВЛЕНО: Открытие модального окна вместо перехода на страницу
    openProposalModal(request) {
      var _a;
      console.log("📝 Открытие модального окна для заявки:", request.id);
      this.selectedRequest = request;
      this.showProposalModal = true;
      document.body.classList.add("modal-open");
      document.body.style.overflow = "hidden";
      document.body.style.paddingRight = "15px";
      this.loadAvailableEquipment(request.id);
      this.resetProposalForm();
      if ((_a = request.lessor_pricing) == null ? void 0 : _a.recommended_price) {
        this.proposalData.proposed_price = request.lessor_pricing.recommended_price;
      }
    },
    // 🔥 ДОБАВЛЕНО: Загрузка доступного оборудования
    loadAvailableEquipment(requestId) {
      return __async(this, null, function* () {
        try {
          console.log("🔧 Загрузка доступного оборудования для заявки:", requestId);
          const response = yield axios.get(`/api/rental-requests/${requestId}/available-equipment`);
          this.availableEquipment = response.data.data || [];
          console.log("✅ Загружено оборудование:", this.availableEquipment.length);
        } catch (error) {
          console.error("❌ Ошибка загрузки оборудования:", error);
          this.availableEquipment = [];
          this.loadEquipmentByRequestCategories();
        }
      });
    },
    // 🔥 ДОБАВЛЕНО: Загрузка оборудования по категориям заявки
    loadEquipmentByRequestCategories() {
      return __async(this, null, function* () {
        var _a;
        if (!((_a = this.selectedRequest) == null ? void 0 : _a.items)) return;
        try {
          const categoryIds = this.selectedRequest.items.map((item) => item.category_id);
          console.log("🔧 Загрузка оборудования по категориям:", categoryIds);
          const response = yield axios.post("/api/lessor/equipment/available-for-request", {
            category_ids: categoryIds,
            rental_period_start: this.selectedRequest.rental_period_start,
            rental_period_end: this.selectedRequest.rental_period_end
          });
          this.availableEquipment = response.data.data || [];
          console.log("✅ Загружено оборудование по категориям:", this.availableEquipment.length);
        } catch (error) {
          console.error("❌ Ошибка загрузки оборудования по категориям:", error);
          this.availableEquipment = [];
        }
      });
    },
    // 🔥 ДОБАВЛЕНО: Сброс формы предложения
    resetProposalForm() {
      this.proposalData = {
        proposed_price: null,
        response_time: 24,
        message: "",
        additional_terms: "",
        selected_equipment: []
      };
    },
    // 🔥 ДОБАВЛЕНО: Быстрый выбор шаблона в модальном окне
    selectQuickTemplate(template) {
      console.log("⚡ Быстрый выбор шаблона:", template.name);
      this.proposalData = {
        proposed_price: template.proposed_price,
        response_time: template.response_time,
        message: template.message,
        additional_terms: template.additional_terms,
        selected_equipment: [...this.proposalData.selected_equipment]
      };
      this.$notify({
        title: "✅ Шаблон выбран",
        text: `Шаблон "${template.name}" применен к форме`,
        type: "success",
        duration: 3e3
      });
    },
    // 🔥 ДОБАВЛЕНО: Отправка предложения
    submitProposal() {
      return __async(this, null, function* () {
        var _a, _b, _c, _d;
        if (!this.isProposalValid) {
          this.$notify({
            title: "❌ Ошибка",
            text: "Заполните все обязательные поля и выберите оборудование",
            type: "error",
            duration: 5e3
          });
          return;
        }
        this.submittingProposal = true;
        try {
          console.log("📤 Отправка предложения для заявки:", this.selectedRequest.id);
          const response = yield axios.post(`/api/rental-requests/${this.selectedRequest.id}/proposals`, {
            proposed_price: this.proposalData.proposed_price,
            response_time: this.proposalData.response_time,
            message: this.proposalData.message,
            additional_terms: this.proposalData.additional_terms,
            equipment_ids: this.proposalData.selected_equipment
          });
          this.updateRequestStatus(this.selectedRequest.id, {
            my_proposals_count: (this.selectedRequest.my_proposals_count || 0) + 1
          });
          this.closeProposalModal();
          this.$notify({
            title: "✅ Предложение отправлено!",
            text: "Ваше предложение успешно отправлено арендатору",
            type: "success",
            duration: 5e3
          });
        } catch (error) {
          console.error("❌ Ошибка отправки предложения:", error);
          let errorMessage = "Неизвестная ошибка";
          if ((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) {
            errorMessage = error.response.data.message;
          } else if ((_d = (_c = error.response) == null ? void 0 : _c.data) == null ? void 0 : _d.errors) {
            errorMessage = Object.values(error.response.data.errors).flat().join(", ");
          }
          this.$notify({
            title: "❌ Ошибка",
            text: `Ошибка отправки предложения: ${errorMessage}`,
            type: "error",
            duration: 5e3
          });
        } finally {
          this.submittingProposal = false;
        }
      });
    },
    // 🔥 ИСПРАВЛЕНО: Закрытие модального окна предложения
    closeProposalModal() {
      this.showProposalModal = false;
      this.selectedRequest = null;
      this.resetProposalForm();
      this.availableEquipment = [];
      document.body.classList.remove("modal-open");
      document.body.style.overflow = "";
      document.body.style.paddingRight = "";
    },
    // 🔥 СИСТЕМА ШАБЛОНОВ - ОСНОВНЫЕ МЕТОДЫ
    loadTemplates() {
      return __async(this, null, function* () {
        if (this.templatesLoaded && this.templates.length > 0) {
          console.log("✅ Шаблоны уже загружены, используем кэш");
          return;
        }
        try {
          console.log("📥 Загрузка шаблонов предложений...");
          const response = yield axios.get("/api/lessor/proposal-templates", {
            params: {
              status: "active",
              per_page: 100
            }
          });
          this.templates = response.data.data || [];
          this.templatesLoaded = true;
          localStorage.setItem("proposal_templates_cache", JSON.stringify({
            data: this.templates,
            timestamp: Date.now()
          }));
          console.log(`✅ Загружено ${this.templates.length} шаблонов`);
        } catch (error) {
          console.error("❌ Ошибка загрузки шаблонов:", error);
          const cached = this.getCachedTemplates();
          if (cached) {
            this.templates = cached;
            console.log("✅ Используем кэшированные шаблоны");
          }
        }
      });
    },
    getCachedTemplates() {
      try {
        const cached = localStorage.getItem("proposal_templates_cache");
        if (cached) {
          const { data, timestamp } = JSON.parse(cached);
          if (Date.now() - timestamp < 36e5) {
            return data;
          }
        }
      } catch (error) {
        console.error("❌ Ошибка чтения кэша шаблонов:", error);
      }
      return null;
    },
    // 🔥 МЕТОДЫ ДЛЯ РАБОТЫ С ШАБЛОНАМИ
    matchingTemplates(request) {
      if (!this.templates.length || !request.items) return [];
      const requestCategoryIds = request.items.map((item) => item.category_id);
      return this.templates.filter(
        (template) => template.is_active && requestCategoryIds.includes(template.category_id)
      ).slice(0, 5);
    },
    matchingTemplatesCount(request) {
      return this.matchingTemplates(request).length;
    },
    hasMatchingTemplates(request) {
      return this.matchingTemplatesCount(request) > 0;
    },
    isHighConversionRequest(request) {
      var _a;
      const hasTemplates = this.hasMatchingTemplates(request);
      const lowCompetition = (request.active_proposals_count || 0) < 3;
      const goodBudget = ((_a = request.lessor_pricing) == null ? void 0 : _a.total_lessor_budget) > 5e3;
      const hasRecommendations = this.getQuickRecommendations(request).length > 0;
      return (hasTemplates || hasRecommendations) && lowCompetition && goodBudget;
    },
    isUrgentRequest(request) {
      const created = new Date(request.created_at);
      const now = /* @__PURE__ */ new Date();
      const hoursDiff = (now - created) / (1e3 * 60 * 60);
      return hoursDiff < 2;
    },
    getRequestCardClass(request) {
      const classes = [];
      if (this.isHighConversionRequest(request)) classes.push("high-conversion");
      if (this.isUrgentRequest(request)) classes.push("urgent-request");
      if (this.hasMatchingTemplates(request)) classes.push("has-templates");
      if (this.getQuickRecommendations(request).length > 0) classes.push("has-recommendations");
      return classes.join(" ");
    },
    // 🔥 МЕТОДЫ ПРИМЕНЕНИЯ ШАБЛОНОВ
    applyTemplate(template, request) {
      return __async(this, null, function* () {
        console.log("⚡ Применение шаблона:", template.name, "к заявке:", request.id);
        this.selectedTemplate = template;
        this.selectedRequest = request;
        this.applyData = {
          proposed_price: template.proposed_price,
          response_time: template.response_time,
          message: template.message,
          additional_terms: template.additional_terms
        };
        yield this.checkEquipmentAvailability(request.id, template.category_id);
        this.showApplyTemplateModal = true;
      });
    },
    checkEquipmentAvailability(requestId, categoryId) {
      return __async(this, null, function* () {
        try {
          console.log("🔍 Проверка доступности оборудования...");
          const response = yield axios.post("/api/lessor/equipment/available-for-request", {
            rental_request_id: requestId,
            category_id: categoryId
          });
          this.equipmentCheckResult = {
            available: response.data.available,
            message: response.data.message,
            unavailable_items: response.data.unavailable_items || []
          };
          console.log("✅ Результат проверки оборудования:", this.equipmentCheckResult);
        } catch (error) {
          console.error("❌ Ошибка проверки оборудования:", error);
          this.equipmentCheckResult = {
            available: false,
            message: "Ошибка проверки доступности оборудования",
            unavailable_items: []
          };
        }
      });
    },
    confirmApplyTemplate() {
      return __async(this, null, function* () {
        var _a, _b, _c, _d;
        if (!this.selectedTemplate || !this.selectedRequest) return;
        this.applyingTemplate = true;
        try {
          console.log("✅ Подтверждение применения шаблона:", {
            template: this.selectedTemplate.id,
            request: this.selectedRequest.id,
            data: this.applyData
          });
          const response = yield axios.post(`/api/lessor/rental-requests/${this.selectedRequest.id}/apply-template`, {
            template_id: this.selectedTemplate.id,
            customizations: this.applyData,
            check_equipment: true
          });
          this.updateRequestStatus(this.selectedRequest.id, {
            my_proposals_count: (this.selectedRequest.my_proposals_count || 0) + 1,
            has_applied_template: true
          });
          this.closeApplyTemplateModal();
          this.$notify({
            title: "✅ Шаблон применен!",
            text: `Шаблон "${this.selectedTemplate.name}" успешно применен к заявке`,
            type: "success",
            duration: 5e3
          });
        } catch (error) {
          console.error("❌ Ошибка применения шаблона:", error);
          let errorMessage = "Неизвестная ошибка";
          if ((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) {
            errorMessage = error.response.data.message;
          } else if ((_d = (_c = error.response) == null ? void 0 : _c.data) == null ? void 0 : _d.errors) {
            errorMessage = Object.values(error.response.data.errors).flat().join(", ");
          }
          this.$notify({
            title: "❌ Ошибка",
            text: `Ошибка применения шаблона: ${errorMessage}`,
            type: "error",
            duration: 5e3
          });
        } finally {
          this.applyingTemplate = false;
        }
      });
    },
    closeApplyTemplateModal() {
      this.showApplyTemplateModal = false;
      this.selectedTemplate = null;
      this.selectedRequest = null;
      this.applyData = {
        proposed_price: null,
        response_time: null,
        message: "",
        additional_terms: ""
      };
      this.equipmentCheckResult = null;
    },
    updateRequestStatus(requestId, updates) {
      const requestIndex = this.requests.findIndex((req) => req.id === requestId);
      if (requestIndex !== -1) {
        this.requests[requestIndex] = __spreadValues(__spreadValues({}, this.requests[requestIndex]), updates);
      }
    },
    // 🔥 ФИЛЬТРАЦИЯ И СОРТИРОВКА
    applyFilters() {
      this.pagination.currentPage = 1;
      this.loadRequests();
    },
    resetFilters() {
      this.localFilters = {
        category_id: "",
        location_id: "",
        sort: "newest",
        my_proposals: ""
      };
      this.pagination.currentPage = 1;
      this.loadRequests();
    },
    // 🔥 СУЩЕСТВУЮЩИЕ МЕТОДЫ АНАЛИТИКИ
    showUrgentRequests() {
      this.localFilters.sort = "newest";
      this.applyFilters();
      this.$notify({
        title: "Срочные заявки",
        text: "Показаны самые новые заявки, требующие быстрого ответа",
        type: "info",
        duration: 3e3
      });
    },
    showTemplatesModal(request = null) {
      if (request) {
        console.log("📋 Показ шаблонов для заявки:", request.id);
      }
      this.$notify({
        title: "Управление шаблонами",
        text: "Модальное окно шаблонов предложений - в разработке",
        type: "info",
        duration: 3e3
      });
    },
    showMyProposals() {
      this.localFilters.my_proposals = "with_proposals";
      this.applyFilters();
      this.$notify({
        title: "Мои предложения",
        text: "Показаны заявки с вашими предложениями",
        type: "info",
        duration: 3e3
      });
    },
    showQuickProposalModal() {
      console.log("Быстрое предложение");
      if (this.requests.length > 0) {
        this.openProposalModal(this.requests[0]);
      } else {
        this.$notify({
          title: "Нет заявок",
          text: "Нет доступных заявок для быстрого предложения",
          type: "warning",
          duration: 3e3
        });
      }
    },
    showFavorites() {
      console.log("Показать избранные заявки");
      this.$notify({
        title: "Избранные заявки",
        text: "Функционал избранных заявок - в разработке",
        type: "info",
        duration: 3e3
      });
    },
    exportAnalyticsData() {
      console.log("Экспорт данных аналитики");
      const data = {
        realTimeAnalytics: this.analytics,
        strategicAnalytics: this.strategicAnalytics,
        requests: this.requests.map((req) => ({
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
        exportDate: (/* @__PURE__ */ new Date()).toISOString(),
        exportedBy: "Lessor Dashboard"
      };
      const blob = new Blob([JSON.stringify(data, null, 2)], {
        type: "application/json"
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `lessor-analytics-${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}.json`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      this.$notify({
        title: "Экспорт завершен",
        text: "Данные аналитики успешно экспортированы",
        type: "success",
        duration: 3e3
      });
    },
    showResponseTimeTips() {
      this.$notify({
        title: "Советы по времени ответа",
        text: "• Используйте шаблоны предложений\n• Настройте уведомления\n• Проверяйте заявки утром и после обеда",
        type: "info",
        duration: 5e3
      });
    },
    showPricingRecommendations() {
      this.$notify({
        title: "Рекомендации по ценообразованию",
        text: "• Проанализируйте цены конкурентов\n• Учитывайте сезонность\n• Предлагайте гибкие условия для долгосрочной аренды",
        type: "info",
        duration: 5e3
      });
    },
    refreshData() {
      return __async(this, null, function* () {
        try {
          console.log("Обновление данных...");
          yield this.loadTemplates();
          yield this.loadQuickRecommendations();
          this.$notify({
            title: "Данные обновлены",
            text: "Актуальная информация загружена",
            type: "success",
            duration: 3e3
          });
        } catch (error) {
          console.error("Ошибка обновления данных:", error);
          this.$notify({
            title: "Ошибка",
            text: "Не удалось обновить данные",
            type: "error",
            duration: 3e3
          });
        }
      });
    },
    // 🔥 ДОБАВЛЕНО: Обработчик клавиши Escape
    handleEscapeKey(event) {
      if (event.key === "Escape" && this.showProposalModal) {
        this.closeProposalModal();
      }
      if (event.key === "Escape" && this.showApplyTemplateModal) {
        this.closeApplyTemplateModal();
      }
    }
  },
  watch: {
    analytics: {
      handler(newAnalytics) {
        if (newAnalytics && newAnalytics.conversion_rate) {
          this.strategicAnalytics.conversion.myConversionRate = newAnalytics.conversion_rate;
          this.strategicAnalytics.conversion.marketConversionRate = Math.max(0, newAnalytics.conversion_rate - 5 + Math.random() * 10);
          this.strategicAnalytics.conversion.trend = newAnalytics.conversion_rate > 60 ? "up" : newAnalytics.conversion_rate < 40 ? "down" : "stable";
        }
      },
      deep: true,
      immediate: true
    }
  },
  mounted() {
    return __async(this, null, function* () {
      console.log("✅ LessorRentalRequestList mounted!", {
        requestsCount: this.requests.length,
        hasAnalytics: !!this.analytics,
        categoriesCount: this.categories.length,
        locationsCount: this.locations.length,
        myProposalsCount: this.myProposalsComputedCount
      });
      yield this.loadTemplates();
      yield this.loadQuickRecommendations();
      if (this.analytics && this.analytics.total_proposals) {
        this.strategicAnalytics.pricing.myAvgPrice = 2450;
        this.strategicAnalytics.pricing.marketAvgPrice = 2200;
        this.strategicAnalytics.pricing.priceDifferencePercent = ((2450 - 2200) / 2200 * 100).toFixed(1);
      }
      document.addEventListener("keydown", this.handleEscapeKey);
    });
  },
  beforeUnmount() {
    document.removeEventListener("keydown", this.handleEscapeKey);
    document.body.classList.remove("modal-open");
    document.body.style.overflow = "";
    document.body.style.paddingRight = "";
  }
};
const _hoisted_1 = { class: "lessor-rental-requests" };
const _hoisted_2 = { class: "card mb-4" };
const _hoisted_3 = { class: "card-body" };
const _hoisted_4 = { class: "row g-3" };
const _hoisted_5 = { class: "col-md-3" };
const _hoisted_6 = ["value"];
const _hoisted_7 = { class: "col-md-3" };
const _hoisted_8 = ["value"];
const _hoisted_9 = { class: "col-md-3" };
const _hoisted_10 = { class: "col-md-3" };
const _hoisted_11 = { class: "row align-items-center mb-3" };
const _hoisted_12 = { class: "col-md-6" };
const _hoisted_13 = { class: "d-flex align-items-center" };
const _hoisted_14 = { class: "col-md-6 text-end" };
const _hoisted_15 = { class: "pagination-summary text-muted small" };
const _hoisted_16 = { key: 0 };
const _hoisted_17 = {
  key: 0,
  class: "text-center py-5"
};
const _hoisted_18 = {
  key: 1,
  class: "global-recommendations card mb-4"
};
const _hoisted_19 = { class: "card-header bg-warning text-dark" };
const _hoisted_20 = { class: "mb-0" };
const _hoisted_21 = { class: "badge bg-light text-warning ms-2" };
const _hoisted_22 = { class: "card-body" };
const _hoisted_23 = { class: "global-recommendations-grid" };
const _hoisted_24 = { class: "recommendation-content" };
const _hoisted_25 = { class: "request-info" };
const _hoisted_26 = { class: "d-block" };
const _hoisted_27 = { class: "text-muted" };
const _hoisted_28 = { class: "template-info" };
const _hoisted_29 = { class: "template-name" };
const _hoisted_30 = { class: "template-price" };
const _hoisted_31 = { class: "confidence-badge" };
const _hoisted_32 = { class: "recommendation-actions" };
const _hoisted_33 = ["onClick"];
const _hoisted_34 = ["onClick"];
const _hoisted_35 = {
  key: 2,
  class: "row"
};
const _hoisted_36 = { class: "card-body" };
const _hoisted_37 = { class: "request-indicators mb-2" };
const _hoisted_38 = {
  key: 0,
  class: "badge bg-success me-2"
};
const _hoisted_39 = {
  key: 1,
  class: "badge bg-primary me-2"
};
const _hoisted_40 = {
  key: 2,
  class: "badge bg-warning me-2"
};
const _hoisted_41 = {
  key: 3,
  class: "badge bg-danger me-2"
};
const _hoisted_42 = {
  key: 4,
  class: "badge bg-info me-2"
};
const _hoisted_43 = { class: "card-title" };
const _hoisted_44 = { class: "card-text" };
const _hoisted_45 = { class: "request-categories mb-2" };
const _hoisted_46 = {
  key: 0,
  class: "quick-recommendations mt-2"
};
const _hoisted_47 = { class: "d-flex flex-wrap gap-1" };
const _hoisted_48 = ["onClick", "title"];
const _hoisted_49 = { class: "d-flex justify-content-between text-muted small mt-2" };
const _hoisted_50 = { class: "badge bg-primary" };
const _hoisted_51 = {
  key: 1,
  class: "budget-info mt-2"
};
const _hoisted_52 = { class: "badge bg-success" };
const _hoisted_53 = { class: "mt-3" };
const _hoisted_54 = ["onClick"];
const _hoisted_55 = { class: "btn-group quick-actions" };
const _hoisted_56 = ["onClick"];
const _hoisted_57 = ["disabled", "title"];
const _hoisted_58 = { class: "dropdown-menu" };
const _hoisted_59 = ["onClick"];
const _hoisted_60 = { class: "text-muted d-block" };
const _hoisted_61 = ["onClick"];
const _hoisted_62 = {
  key: 3,
  class: "alert alert-info text-center py-4"
};
const _hoisted_63 = { class: "modal-dialog modal-lg" };
const _hoisted_64 = { class: "modal-content" };
const _hoisted_65 = { class: "modal-header" };
const _hoisted_66 = { class: "modal-body" };
const _hoisted_67 = { key: 0 };
const _hoisted_68 = { class: "alert alert-info" };
const _hoisted_69 = { class: "mb-1" };
const _hoisted_70 = { class: "row mb-3" };
const _hoisted_71 = { class: "col-md-6" };
const _hoisted_72 = ["placeholder"];
const _hoisted_73 = { class: "col-md-6" };
const _hoisted_74 = ["placeholder"];
const _hoisted_75 = { class: "mb-3" };
const _hoisted_76 = ["placeholder"];
const _hoisted_77 = { class: "mb-3" };
const _hoisted_78 = ["placeholder"];
const _hoisted_79 = {
  key: 0,
  class: "mt-2"
};
const _hoisted_80 = { class: "mb-0" };
const _hoisted_81 = { class: "modal-footer" };
const _hoisted_82 = ["disabled"];
const _hoisted_83 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1"
};
const _hoisted_84 = { class: "modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" };
const _hoisted_85 = { class: "modal-content" };
const _hoisted_86 = { class: "modal-header" };
const _hoisted_87 = { class: "modal-body" };
const _hoisted_88 = { key: 0 };
const _hoisted_89 = { class: "alert alert-info mb-4" };
const _hoisted_90 = { class: "mb-1" };
const _hoisted_91 = { class: "mt-2" };
const _hoisted_92 = { class: "text-muted" };
const _hoisted_93 = { class: "text-muted ms-3" };
const _hoisted_94 = {
  key: 0,
  class: "mb-4"
};
const _hoisted_95 = { class: "row" };
const _hoisted_96 = ["onClick"];
const _hoisted_97 = { class: "card-body p-3" };
const _hoisted_98 = { class: "card-title mb-1" };
const _hoisted_99 = { class: "card-text small text-muted mb-2" };
const _hoisted_100 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_101 = { class: "text-primary" };
const _hoisted_102 = { class: "text-muted" };
const _hoisted_103 = {
  key: 1,
  class: "mb-4"
};
const _hoisted_104 = { class: "ai-recommendations" };
const _hoisted_105 = ["onClick"];
const _hoisted_106 = { class: "card-body p-3" };
const _hoisted_107 = { class: "d-flex justify-content-between align-items-start" };
const _hoisted_108 = { class: "flex-grow-1" };
const _hoisted_109 = { class: "mb-1" };
const _hoisted_110 = { class: "small text-muted mb-2" };
const _hoisted_111 = { class: "d-flex gap-3 small" };
const _hoisted_112 = { class: "text-primary" };
const _hoisted_113 = { class: "text-muted" };
const _hoisted_114 = { class: "text-end" };
const _hoisted_115 = { class: "proposal-form" };
const _hoisted_116 = { class: "row mb-3" };
const _hoisted_117 = { class: "col-md-6" };
const _hoisted_118 = { class: "form-text" };
const _hoisted_119 = { class: "col-md-6" };
const _hoisted_120 = { class: "mb-3" };
const _hoisted_121 = { class: "mb-3" };
const _hoisted_122 = { class: "mb-3" };
const _hoisted_123 = {
  key: 0,
  class: "equipment-list"
};
const _hoisted_124 = ["value", "id"];
const _hoisted_125 = ["for"];
const _hoisted_126 = { class: "d-flex justify-content-between align-items-start" };
const _hoisted_127 = { class: "text-muted d-block" };
const _hoisted_128 = { class: "text-end" };
const _hoisted_129 = { class: "text-primary fw-bold" };
const _hoisted_130 = {
  key: 0,
  class: "text-success"
};
const _hoisted_131 = {
  key: 1,
  class: "text-danger"
};
const _hoisted_132 = {
  key: 1,
  class: "alert alert-warning"
};
const _hoisted_133 = {
  key: 2,
  class: "mt-2"
};
const _hoisted_134 = { class: "text-success" };
const _hoisted_135 = {
  key: 0,
  class: "alert alert-light border"
};
const _hoisted_136 = { class: "row small" };
const _hoisted_137 = { class: "col-md-6" };
const _hoisted_138 = { class: "col-md-6" };
const _hoisted_139 = { class: "text-success" };
const _hoisted_140 = { class: "modal-footer" };
const _hoisted_141 = ["disabled"];
const _hoisted_142 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _a, _b;
  const _component_AnalyticsDashboard = resolveComponent("AnalyticsDashboard");
  const _component_ProfessionalPagination = resolveComponent("ProfessionalPagination");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_AnalyticsDashboard, {
      "real-time-metrics": $data.analytics,
      "strategic-metrics": $data.strategicAnalytics,
      categories: $props.categories,
      "urgent-requests": $options.urgentRequests,
      templates: $data.templates,
      "my-proposals-count": $options.myProposalsComputedCount,
      onShowUrgentRequests: $options.showUrgentRequests,
      onShowTemplates: $options.showTemplatesModal,
      onShowMyProposals: $options.showMyProposals,
      onQuickProposal: $options.showQuickProposalModal,
      onShowTemplatesModal: $options.showTemplatesModal,
      onShowFavorites: $options.showFavorites,
      onExportAnalytics: $options.exportAnalyticsData
    }, null, 8, ["real-time-metrics", "strategic-metrics", "categories", "urgent-requests", "templates", "my-proposals-count", "onShowUrgentRequests", "onShowTemplates", "onShowMyProposals", "onQuickProposal", "onShowTemplatesModal", "onShowFavorites", "onExportAnalytics"]),
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createBaseVNode("div", _hoisted_4, [
          createBaseVNode("div", _hoisted_5, [
            _cache[27] || (_cache[27] = createBaseVNode("label", { class: "form-label" }, "Категория", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.localFilters.category_id = $event),
              class: "form-select",
              onChange: _cache[1] || (_cache[1] = (...args) => $options.applyFilters && $options.applyFilters(...args))
            }, [
              _cache[26] || (_cache[26] = createBaseVNode("option", { value: "" }, "Все категории", -1)),
              (openBlock(true), createElementBlock(Fragment, null, renderList($props.categories, (category) => {
                return openBlock(), createElementBlock("option", {
                  key: category.id,
                  value: category.id
                }, toDisplayString(category.name), 9, _hoisted_6);
              }), 128))
            ], 544), [
              [vModelSelect, $data.localFilters.category_id]
            ])
          ]),
          createBaseVNode("div", _hoisted_7, [
            _cache[29] || (_cache[29] = createBaseVNode("label", { class: "form-label" }, "Локация", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.localFilters.location_id = $event),
              class: "form-select",
              onChange: _cache[3] || (_cache[3] = (...args) => $options.applyFilters && $options.applyFilters(...args))
            }, [
              _cache[28] || (_cache[28] = createBaseVNode("option", { value: "" }, "Все локации", -1)),
              (openBlock(true), createElementBlock(Fragment, null, renderList($props.locations, (location) => {
                return openBlock(), createElementBlock("option", {
                  key: location.id,
                  value: location.id
                }, toDisplayString(location.name), 9, _hoisted_8);
              }), 128))
            ], 544), [
              [vModelSelect, $data.localFilters.location_id]
            ])
          ]),
          createBaseVNode("div", _hoisted_9, [
            _cache[31] || (_cache[31] = createBaseVNode("label", { class: "form-label" }, "Сортировка", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.localFilters.sort = $event),
              class: "form-select",
              onChange: _cache[5] || (_cache[5] = (...args) => $options.applyFilters && $options.applyFilters(...args))
            }, [..._cache[30] || (_cache[30] = [
              createStaticVNode('<option value="newest" data-v-898122ca>Сначала новые</option><option value="budget" data-v-898122ca>По бюджету</option><option value="proposals" data-v-898122ca>По предложениям</option><option value="templates" data-v-898122ca>С подходящими шаблонами</option><option value="recommendations" data-v-898122ca>По рекомендациям</option>', 5)
            ])], 544), [
              [vModelSelect, $data.localFilters.sort]
            ])
          ]),
          createBaseVNode("div", _hoisted_10, [
            _cache[33] || (_cache[33] = createBaseVNode("label", { class: "form-label" }, "Статус", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.localFilters.my_proposals = $event),
              class: "form-select",
              onChange: _cache[7] || (_cache[7] = (...args) => $options.applyFilters && $options.applyFilters(...args))
            }, [..._cache[32] || (_cache[32] = [
              createStaticVNode('<option value="" data-v-898122ca>Все заявки</option><option value="with_proposals" data-v-898122ca>С моими предложениями</option><option value="without_proposals" data-v-898122ca>Без моих предложений</option><option value="with_templates" data-v-898122ca>С подходящими шаблонами</option><option value="with_recommendations" data-v-898122ca>С рекомендациями</option>', 5)
            ])], 544), [
              [vModelSelect, $data.localFilters.my_proposals]
            ])
          ])
        ])
      ])
    ]),
    createBaseVNode("div", _hoisted_11, [
      createBaseVNode("div", _hoisted_12, [
        createBaseVNode("div", _hoisted_13, [
          _cache[35] || (_cache[35] = createBaseVNode("label", { class: "form-label mb-0 me-2" }, "Показывать по:", -1)),
          withDirectives(createBaseVNode("select", {
            "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.pagination.perPage = $event),
            onChange: _cache[9] || (_cache[9] = ($event) => $options.changePerPage($data.pagination.perPage)),
            class: "form-select form-select-sm",
            style: { "width": "auto" }
          }, [..._cache[34] || (_cache[34] = [
            createBaseVNode("option", { value: "10" }, "10", -1),
            createBaseVNode("option", { value: "25" }, "25", -1),
            createBaseVNode("option", { value: "50" }, "50", -1),
            createBaseVNode("option", { value: "100" }, "100", -1)
          ])], 544), [
            [vModelSelect, $data.pagination.perPage]
          ]),
          _cache[36] || (_cache[36] = createBaseVNode("span", { class: "text-muted small ms-2" }, " заявок на странице ", -1))
        ])
      ]),
      createBaseVNode("div", _hoisted_14, [
        createBaseVNode("div", _hoisted_15, [
          createTextVNode(" Найдено заявок: " + toDisplayString($data.pagination.total) + " ", 1),
          $data.pagination.lastPage > 1 ? (openBlock(), createElementBlock("span", _hoisted_16, " • Страница " + toDisplayString($data.pagination.currentPage) + " из " + toDisplayString($data.pagination.lastPage), 1)) : createCommentVNode("", true)
        ])
      ])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_17, [..._cache[37] || (_cache[37] = [
      createBaseVNode("div", {
        class: "spinner-border text-primary",
        role: "status",
        style: { "width": "3rem", "height": "3rem" }
      }, [
        createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
      ], -1),
      createBaseVNode("div", { class: "mt-3 text-muted" }, "Загрузка заявок...", -1)
    ])])) : createCommentVNode("", true),
    $data.globalRecommendations.length > 0 && !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_18, [
      createBaseVNode("div", _hoisted_19, [
        createBaseVNode("h6", _hoisted_20, [
          _cache[38] || (_cache[38] = createBaseVNode("i", { class: "fas fa-robot me-2" }, null, -1)),
          _cache[39] || (_cache[39] = createTextVNode("Лучшие рекомендации для текущих заявок ", -1)),
          createBaseVNode("span", _hoisted_21, toDisplayString($data.globalRecommendations.length), 1)
        ])
      ]),
      createBaseVNode("div", _hoisted_22, [
        createBaseVNode("div", _hoisted_23, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.globalRecommendations.slice(0, 4), (rec) => {
            return openBlock(), createElementBlock("div", {
              key: `${rec.request_id}-${rec.template.id}`,
              class: "global-recommendation-card"
            }, [
              createBaseVNode("div", _hoisted_24, [
                createBaseVNode("div", _hoisted_25, [
                  createBaseVNode("strong", _hoisted_26, toDisplayString($options.getRequestTitle(rec.request_id)), 1),
                  createBaseVNode("small", _hoisted_27, toDisplayString(rec.reason), 1)
                ]),
                createBaseVNode("div", _hoisted_28, [
                  createBaseVNode("span", _hoisted_29, toDisplayString(rec.template.name), 1),
                  createBaseVNode("span", _hoisted_30, toDisplayString($options.formatCurrency(rec.template.proposed_price)) + "/час", 1)
                ]),
                createBaseVNode("div", _hoisted_31, [
                  createBaseVNode("span", {
                    class: normalizeClass(["badge", "bg-" + rec.color])
                  }, toDisplayString(rec.confidence), 3)
                ])
              ]),
              createBaseVNode("div", _hoisted_32, [
                createBaseVNode("button", {
                  class: "btn btn-sm btn-primary",
                  onClick: ($event) => $options.applyQuickTemplate(rec, $options.getRequestById(rec.request_id)),
                  title: "Быстро применить шаблон"
                }, [..._cache[40] || (_cache[40] = [
                  createBaseVNode("i", { class: "fas fa-bolt" }, null, -1)
                ])], 8, _hoisted_33),
                createBaseVNode("button", {
                  class: "btn btn-sm btn-outline-secondary",
                  onClick: ($event) => $options.viewRequestDetails(rec.request_id),
                  title: "Перейти к заявке"
                }, [..._cache[41] || (_cache[41] = [
                  createBaseVNode("i", { class: "fas fa-external-link-alt" }, null, -1)
                ])], 8, _hoisted_34)
              ])
            ]);
          }), 128))
        ])
      ])
    ])) : createCommentVNode("", true),
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_35, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.requests, (request) => {
        var _a2;
        return openBlock(), createElementBlock("div", {
          class: "col-12",
          key: request.id
        }, [
          createBaseVNode("div", {
            class: normalizeClass(["card mb-3 request-card", $options.getRequestCardClass(request)])
          }, [
            createBaseVNode("div", _hoisted_36, [
              createBaseVNode("div", _hoisted_37, [
                $options.hasMatchingTemplates(request) ? (openBlock(), createElementBlock("span", _hoisted_38, [
                  _cache[42] || (_cache[42] = createBaseVNode("i", { class: "fas fa-bolt me-1" }, null, -1)),
                  createTextVNode("Есть шаблоны (" + toDisplayString($options.matchingTemplatesCount(request)) + ") ", 1)
                ])) : createCommentVNode("", true),
                request.my_proposals_count > 0 ? (openBlock(), createElementBlock("span", _hoisted_39, [..._cache[43] || (_cache[43] = [
                  createBaseVNode("i", { class: "fas fa-check me-1" }, null, -1),
                  createTextVNode("Предложение отправлено ", -1)
                ])])) : createCommentVNode("", true),
                $options.isHighConversionRequest(request) ? (openBlock(), createElementBlock("span", _hoisted_40, [..._cache[44] || (_cache[44] = [
                  createBaseVNode("i", { class: "fas fa-rocket me-1" }, null, -1),
                  createTextVNode("Высокий шанс ", -1)
                ])])) : createCommentVNode("", true),
                $options.isUrgentRequest(request) ? (openBlock(), createElementBlock("span", _hoisted_41, [..._cache[45] || (_cache[45] = [
                  createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1),
                  createTextVNode("Срочно ", -1)
                ])])) : createCommentVNode("", true),
                $options.getQuickRecommendations(request).length > 0 ? (openBlock(), createElementBlock("span", _hoisted_42, [
                  _cache[46] || (_cache[46] = createBaseVNode("i", { class: "fas fa-robot me-1" }, null, -1)),
                  createTextVNode("Рекомендации (" + toDisplayString($options.getQuickRecommendations(request).length) + ") ", 1)
                ])) : createCommentVNode("", true)
              ]),
              createBaseVNode("h5", _hoisted_43, toDisplayString(request.title || "Без названия"), 1),
              createBaseVNode("p", _hoisted_44, toDisplayString(request.description || "Описание отсутствует"), 1),
              createBaseVNode("div", _hoisted_45, [
                (openBlock(true), createElementBlock(Fragment, null, renderList(request.items, (item) => {
                  return openBlock(), createElementBlock("span", {
                    key: item.id,
                    class: "badge bg-light text-dark me-1"
                  }, toDisplayString($options.getCategoryName(item.category_id)), 1);
                }), 128))
              ]),
              $options.getQuickRecommendations(request).length > 0 ? (openBlock(), createElementBlock("div", _hoisted_46, [
                createBaseVNode("div", _hoisted_47, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($options.getQuickRecommendations(request), (rec) => {
                    return openBlock(), createElementBlock("span", {
                      key: rec.template.id,
                      class: normalizeClass(["badge recommendation-badge", "bg-" + rec.color]),
                      onClick: ($event) => $options.applyQuickTemplate(rec, request),
                      title: "Применить: " + rec.reason
                    }, [
                      createTextVNode(toDisplayString(rec.template.name) + " (" + toDisplayString(rec.confidence) + ") ", 1),
                      _cache[47] || (_cache[47] = createBaseVNode("i", { class: "fas fa-bolt ms-1" }, null, -1))
                    ], 10, _hoisted_48);
                  }), 128))
                ])
              ])) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_49, [
                createBaseVNode("span", null, [
                  _cache[48] || (_cache[48] = createBaseVNode("i", { class: "fas fa-map-marker-alt" }, null, -1)),
                  createTextVNode(" " + toDisplayString(((_a2 = request.location) == null ? void 0 : _a2.name) || "Локация не указана"), 1)
                ]),
                createBaseVNode("span", null, [
                  _cache[49] || (_cache[49] = createBaseVNode("i", { class: "fas fa-calendar-alt" }, null, -1)),
                  createTextVNode(" " + toDisplayString($options.formatDate(request.rental_period_start)) + " - " + toDisplayString($options.formatDate(request.rental_period_end)), 1)
                ]),
                createBaseVNode("span", _hoisted_50, toDisplayString(request.active_proposals_count || 0) + " предложений ", 1)
              ]),
              request.lessor_pricing ? (openBlock(), createElementBlock("div", _hoisted_51, [
                createBaseVNode("span", _hoisted_52, [
                  _cache[50] || (_cache[50] = createBaseVNode("i", { class: "fas fa-ruble-sign me-1" }, null, -1)),
                  createTextVNode(" Бюджет для вас: " + toDisplayString($options.formatCurrency(request.lessor_pricing.total_lessor_budget)), 1)
                ])
              ])) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_53, [
                createBaseVNode("button", {
                  class: "btn btn-primary btn-sm me-2",
                  onClick: ($event) => $options.viewDetails(request.id)
                }, [..._cache[51] || (_cache[51] = [
                  createBaseVNode("i", { class: "fas fa-eye me-1" }, null, -1),
                  createTextVNode("Подробнее ", -1)
                ])], 8, _hoisted_54),
                createBaseVNode("div", _hoisted_55, [
                  createBaseVNode("button", {
                    class: "btn btn-outline-success btn-sm",
                    onClick: ($event) => $options.openProposalModal(request)
                  }, [..._cache[52] || (_cache[52] = [
                    createBaseVNode("i", { class: "fas fa-paper-plane me-1" }, null, -1),
                    createTextVNode("Предложить ", -1)
                  ])], 8, _hoisted_56),
                  createBaseVNode("button", {
                    class: "btn btn-outline-success btn-sm dropdown-toggle dropdown-toggle-split",
                    "data-bs-toggle": "dropdown",
                    "aria-expanded": "false",
                    disabled: !$options.hasMatchingTemplates(request),
                    title: $options.hasMatchingTemplates(request) ? "Быстрые шаблоны" : "Нет подходящих шаблонов"
                  }, [..._cache[53] || (_cache[53] = [
                    createBaseVNode("span", { class: "visually-hidden" }, "Быстрые шаблоны", -1)
                  ])], 8, _hoisted_57),
                  createBaseVNode("ul", _hoisted_58, [
                    $options.hasMatchingTemplates(request) ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
                      (openBlock(true), createElementBlock(Fragment, null, renderList($options.matchingTemplates(request), (template) => {
                        return openBlock(), createElementBlock("li", {
                          key: template.id
                        }, [
                          createBaseVNode("a", {
                            class: "dropdown-item",
                            href: "#",
                            onClick: withModifiers(($event) => $options.applyTemplate(template, request), ["prevent"])
                          }, [
                            _cache[54] || (_cache[54] = createBaseVNode("i", { class: "fas fa-bolt me-1 text-warning" }, null, -1)),
                            createTextVNode(" " + toDisplayString(template.name) + " (" + toDisplayString($options.formatCurrency(template.proposed_price)) + "/час) ", 1),
                            createBaseVNode("small", _hoisted_60, toDisplayString(template.response_time) + "ч ответ", 1)
                          ], 8, _hoisted_59)
                        ]);
                      }), 128)),
                      _cache[55] || (_cache[55] = createBaseVNode("li", null, [
                        createBaseVNode("hr", { class: "dropdown-divider" })
                      ], -1))
                    ], 64)) : createCommentVNode("", true),
                    createBaseVNode("li", null, [
                      createBaseVNode("a", {
                        class: "dropdown-item",
                        href: "#",
                        onClick: withModifiers(($event) => $options.showTemplatesModal(request), ["prevent"])
                      }, [..._cache[56] || (_cache[56] = [
                        createBaseVNode("i", { class: "fas fa-cog me-1" }, null, -1),
                        createTextVNode("Управление шаблонами ", -1)
                      ])], 8, _hoisted_61)
                    ])
                  ])
                ])
              ])
            ])
          ], 2)
        ]);
      }), 128))
    ])) : createCommentVNode("", true),
    $data.requests.length === 0 && !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_62, [
      _cache[58] || (_cache[58] = createBaseVNode("i", { class: "fas fa-inbox fa-3x mb-3 text-muted" }, null, -1)),
      _cache[59] || (_cache[59] = createBaseVNode("h5", null, "Заявки не найдены", -1)),
      _cache[60] || (_cache[60] = createBaseVNode("p", { class: "text-muted" }, "Попробуйте изменить параметры фильтрации", -1)),
      createBaseVNode("button", {
        class: "btn btn-primary",
        onClick: _cache[10] || (_cache[10] = (...args) => $options.resetFilters && $options.resetFilters(...args))
      }, [..._cache[57] || (_cache[57] = [
        createBaseVNode("i", { class: "fas fa-refresh me-1" }, null, -1),
        createTextVNode("Сбросить фильтры ", -1)
      ])])
    ])) : createCommentVNode("", true),
    $data.pagination.total > $data.pagination.perPage && !$data.loading ? (openBlock(), createBlock(_component_ProfessionalPagination, {
      key: 4,
      "current-page": $data.pagination.currentPage,
      "total-items": $data.pagination.total,
      "per-page": $data.pagination.perPage,
      onPageChanged: $options.handlePageChange,
      class: "mt-4"
    }, null, 8, ["current-page", "total-items", "per-page", "onPageChanged"])) : createCommentVNode("", true),
    $data.showApplyTemplateModal ? (openBlock(), createElementBlock("div", {
      key: 5,
      class: normalizeClass(["modal fade", { "show d-block": $data.showApplyTemplateModal }]),
      style: { "background": "rgba(0,0,0,0.5)" }
    }, [
      createBaseVNode("div", _hoisted_63, [
        createBaseVNode("div", _hoisted_64, [
          createBaseVNode("div", _hoisted_65, [
            _cache[61] || (_cache[61] = createBaseVNode("h5", { class: "modal-title" }, [
              createBaseVNode("i", { class: "fas fa-bolt me-2 text-warning" }),
              createTextVNode(" Применение шаблона ")
            ], -1)),
            createBaseVNode("button", {
              type: "button",
              class: "btn-close",
              onClick: _cache[11] || (_cache[11] = (...args) => $options.closeApplyTemplateModal && $options.closeApplyTemplateModal(...args))
            })
          ]),
          createBaseVNode("div", _hoisted_66, [
            $data.selectedTemplate && $data.selectedRequest ? (openBlock(), createElementBlock("div", _hoisted_67, [
              createBaseVNode("div", _hoisted_68, [
                createBaseVNode("h6", null, [
                  _cache[62] || (_cache[62] = createTextVNode("Шаблон: ", -1)),
                  createBaseVNode("strong", null, toDisplayString($data.selectedTemplate.name), 1)
                ]),
                createBaseVNode("p", _hoisted_69, "Заявка: " + toDisplayString($data.selectedRequest.title || "Без названия"), 1)
              ]),
              createBaseVNode("div", _hoisted_70, [
                createBaseVNode("div", _hoisted_71, [
                  _cache[63] || (_cache[63] = createBaseVNode("label", { class: "form-label" }, "Цена за час", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: "number",
                    class: "form-control",
                    "onUpdate:modelValue": _cache[12] || (_cache[12] = ($event) => $data.applyData.proposed_price = $event),
                    placeholder: $data.selectedTemplate.proposed_price
                  }, null, 8, _hoisted_72), [
                    [vModelText, $data.applyData.proposed_price]
                  ])
                ]),
                createBaseVNode("div", _hoisted_73, [
                  _cache[64] || (_cache[64] = createBaseVNode("label", { class: "form-label" }, "Время ответа (часы)", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: "number",
                    class: "form-control",
                    "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => $data.applyData.response_time = $event),
                    placeholder: $data.selectedTemplate.response_time
                  }, null, 8, _hoisted_74), [
                    [vModelText, $data.applyData.response_time]
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_75, [
                _cache[65] || (_cache[65] = createBaseVNode("label", { class: "form-label" }, "Сообщение арендатору", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: "form-control",
                  rows: "4",
                  "onUpdate:modelValue": _cache[14] || (_cache[14] = ($event) => $data.applyData.message = $event),
                  placeholder: $data.selectedTemplate.message
                }, null, 8, _hoisted_76), [
                  [vModelText, $data.applyData.message]
                ])
              ]),
              createBaseVNode("div", _hoisted_77, [
                _cache[66] || (_cache[66] = createBaseVNode("label", { class: "form-label" }, "Дополнительные условия", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: "form-control",
                  rows: "3",
                  "onUpdate:modelValue": _cache[15] || (_cache[15] = ($event) => $data.applyData.additional_terms = $event),
                  placeholder: $data.selectedTemplate.additional_terms
                }, null, 8, _hoisted_78), [
                  [vModelText, $data.applyData.additional_terms]
                ])
              ]),
              $data.equipmentCheckResult ? (openBlock(), createElementBlock("div", {
                key: 0,
                class: normalizeClass(["alert", $data.equipmentCheckResult.available ? "alert-success" : "alert-warning"])
              }, [
                createBaseVNode("i", {
                  class: normalizeClass(["fas", $data.equipmentCheckResult.available ? "fa-check-circle" : "fa-exclamation-triangle"])
                }, null, 2),
                createTextVNode(" " + toDisplayString($data.equipmentCheckResult.message) + " ", 1),
                $data.equipmentCheckResult.unavailable_items && $data.equipmentCheckResult.unavailable_items.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_79, [
                  _cache[67] || (_cache[67] = createBaseVNode("strong", null, "Недоступно:", -1)),
                  createBaseVNode("ul", _hoisted_80, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList($data.equipmentCheckResult.unavailable_items, (item) => {
                      return openBlock(), createElementBlock("li", {
                        key: item.id
                      }, toDisplayString(item.name) + " (" + toDisplayString(item.category_name) + ") ", 1);
                    }), 128))
                  ])
                ])) : createCommentVNode("", true)
              ], 2)) : createCommentVNode("", true)
            ])) : createCommentVNode("", true)
          ]),
          createBaseVNode("div", _hoisted_81, [
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-secondary",
              onClick: _cache[16] || (_cache[16] = (...args) => $options.closeApplyTemplateModal && $options.closeApplyTemplateModal(...args))
            }, "Отмена"),
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-primary",
              onClick: _cache[17] || (_cache[17] = (...args) => $options.confirmApplyTemplate && $options.confirmApplyTemplate(...args)),
              disabled: $data.applyingTemplate || !$options.isEquipmentAvailable
            }, [
              $data.applyingTemplate ? (openBlock(), createElementBlock("span", _hoisted_83)) : createCommentVNode("", true),
              createTextVNode(" " + toDisplayString($data.applyingTemplate ? "Применение..." : "Применить шаблон"), 1)
            ], 8, _hoisted_82)
          ])
        ])
      ])
    ], 2)) : createCommentVNode("", true),
    $data.showProposalModal ? (openBlock(), createElementBlock("div", {
      key: 6,
      class: normalizeClass(["modal fade", { "show d-block": $data.showProposalModal }]),
      style: { "background": "rgba(0,0,0,0.5)" }
    }, [
      createBaseVNode("div", _hoisted_84, [
        createBaseVNode("div", _hoisted_85, [
          createBaseVNode("div", _hoisted_86, [
            _cache[68] || (_cache[68] = createBaseVNode("h5", { class: "modal-title" }, [
              createBaseVNode("i", { class: "fas fa-paper-plane me-2 text-primary" }),
              createTextVNode(" Создание предложения ")
            ], -1)),
            createBaseVNode("button", {
              type: "button",
              class: "btn-close",
              onClick: _cache[18] || (_cache[18] = (...args) => $options.closeProposalModal && $options.closeProposalModal(...args))
            })
          ]),
          createBaseVNode("div", _hoisted_87, [
            $data.selectedRequest ? (openBlock(), createElementBlock("div", _hoisted_88, [
              createBaseVNode("div", _hoisted_89, [
                createBaseVNode("h6", null, [
                  _cache[69] || (_cache[69] = createTextVNode("Заявка: ", -1)),
                  createBaseVNode("strong", null, toDisplayString($data.selectedRequest.title || "Без названия"), 1)
                ]),
                createBaseVNode("p", _hoisted_90, toDisplayString($data.selectedRequest.description || "Описание отсутствует"), 1),
                createBaseVNode("div", _hoisted_91, [
                  createBaseVNode("small", _hoisted_92, [
                    _cache[70] || (_cache[70] = createBaseVNode("i", { class: "fas fa-calendar-alt me-1" }, null, -1)),
                    createTextVNode(" " + toDisplayString($options.formatDate($data.selectedRequest.rental_period_start)) + " - " + toDisplayString($options.formatDate($data.selectedRequest.rental_period_end)), 1)
                  ]),
                  createBaseVNode("small", _hoisted_93, [
                    _cache[71] || (_cache[71] = createBaseVNode("i", { class: "fas fa-map-marker-alt me-1" }, null, -1)),
                    createTextVNode(" " + toDisplayString(((_a = $data.selectedRequest.location) == null ? void 0 : _a.name) || "Локация не указана"), 1)
                  ])
                ])
              ]),
              $options.hasMatchingTemplates($data.selectedRequest) ? (openBlock(), createElementBlock("div", _hoisted_94, [
                _cache[72] || (_cache[72] = createBaseVNode("h6", { class: "mb-3" }, [
                  createBaseVNode("i", { class: "fas fa-bolt me-1 text-warning" }),
                  createTextVNode(" Быстрые шаблоны ")
                ], -1)),
                createBaseVNode("div", _hoisted_95, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($options.matchingTemplates($data.selectedRequest), (template) => {
                    return openBlock(), createElementBlock("div", {
                      key: template.id,
                      class: "col-md-6 mb-2"
                    }, [
                      createBaseVNode("div", {
                        class: "card template-quick-card h-100",
                        onClick: ($event) => $options.selectQuickTemplate(template)
                      }, [
                        createBaseVNode("div", _hoisted_97, [
                          createBaseVNode("h6", _hoisted_98, toDisplayString(template.name), 1),
                          createBaseVNode("p", _hoisted_99, toDisplayString(template.description), 1),
                          createBaseVNode("div", _hoisted_100, [
                            createBaseVNode("strong", _hoisted_101, toDisplayString($options.formatCurrency(template.proposed_price)) + "/час", 1),
                            createBaseVNode("small", _hoisted_102, toDisplayString(template.response_time) + "ч", 1)
                          ])
                        ])
                      ], 8, _hoisted_96)
                    ]);
                  }), 128))
                ])
              ])) : createCommentVNode("", true),
              $options.getQuickRecommendations($data.selectedRequest).length > 0 ? (openBlock(), createElementBlock("div", _hoisted_103, [
                _cache[75] || (_cache[75] = createBaseVNode("h6", { class: "mb-3" }, [
                  createBaseVNode("i", { class: "fas fa-robot me-1 text-primary" }),
                  createTextVNode(" Умные рекомендации ")
                ], -1)),
                createBaseVNode("div", _hoisted_104, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($options.getQuickRecommendations($data.selectedRequest), (rec) => {
                    return openBlock(), createElementBlock("div", {
                      key: rec.template.id,
                      class: "ai-recommendation-card card mb-2",
                      onClick: ($event) => $options.selectQuickTemplate(rec.template)
                    }, [
                      createBaseVNode("div", _hoisted_106, [
                        createBaseVNode("div", _hoisted_107, [
                          createBaseVNode("div", _hoisted_108, [
                            createBaseVNode("h6", _hoisted_109, toDisplayString(rec.template.name), 1),
                            createBaseVNode("p", _hoisted_110, toDisplayString(rec.reason), 1),
                            createBaseVNode("div", _hoisted_111, [
                              createBaseVNode("span", _hoisted_112, [
                                _cache[73] || (_cache[73] = createBaseVNode("i", { class: "fas fa-ruble-sign me-1" }, null, -1)),
                                createTextVNode(" " + toDisplayString($options.formatCurrency(rec.template.proposed_price)) + "/час ", 1)
                              ]),
                              createBaseVNode("span", _hoisted_113, [
                                _cache[74] || (_cache[74] = createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1)),
                                createTextVNode(" " + toDisplayString(rec.template.response_time) + "ч ", 1)
                              ])
                            ])
                          ]),
                          createBaseVNode("div", _hoisted_114, [
                            createBaseVNode("span", {
                              class: normalizeClass(["badge", "bg-" + rec.color])
                            }, toDisplayString(rec.confidence), 3)
                          ])
                        ])
                      ])
                    ], 8, _hoisted_105);
                  }), 128))
                ])
              ])) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_115, [
                _cache[92] || (_cache[92] = createBaseVNode("h6", { class: "mb-3" }, [
                  createBaseVNode("i", { class: "fas fa-edit me-1" }),
                  createTextVNode(" Детали предложения ")
                ], -1)),
                createBaseVNode("div", _hoisted_116, [
                  createBaseVNode("div", _hoisted_117, [
                    _cache[76] || (_cache[76] = createBaseVNode("label", { class: "form-label" }, "Цена за час *", -1)),
                    withDirectives(createBaseVNode("input", {
                      type: "number",
                      class: "form-control",
                      "onUpdate:modelValue": _cache[19] || (_cache[19] = ($event) => $data.proposalData.proposed_price = $event),
                      placeholder: "Введите цену в рублях",
                      required: ""
                    }, null, 512), [
                      [vModelText, $data.proposalData.proposed_price]
                    ]),
                    createBaseVNode("div", _hoisted_118, "Рекомендуемая цена: " + toDisplayString($options.formatCurrency((_b = $data.selectedRequest.lessor_pricing) == null ? void 0 : _b.recommended_price)), 1)
                  ]),
                  createBaseVNode("div", _hoisted_119, [
                    _cache[77] || (_cache[77] = createBaseVNode("label", { class: "form-label" }, "Время ответа (часы) *", -1)),
                    withDirectives(createBaseVNode("input", {
                      type: "number",
                      class: "form-control",
                      "onUpdate:modelValue": _cache[20] || (_cache[20] = ($event) => $data.proposalData.response_time = $event),
                      min: "1",
                      max: "168",
                      placeholder: "24",
                      required: ""
                    }, null, 512), [
                      [vModelText, $data.proposalData.response_time]
                    ]),
                    _cache[78] || (_cache[78] = createBaseVNode("div", { class: "form-text" }, "В течение скольки часов вы готовы ответить", -1))
                  ])
                ]),
                createBaseVNode("div", _hoisted_120, [
                  _cache[79] || (_cache[79] = createBaseVNode("label", { class: "form-label" }, "Сообщение арендатору *", -1)),
                  withDirectives(createBaseVNode("textarea", {
                    class: "form-control",
                    rows: "4",
                    "onUpdate:modelValue": _cache[21] || (_cache[21] = ($event) => $data.proposalData.message = $event),
                    placeholder: "Опишите ваше предложение, условия аренды...",
                    required: ""
                  }, null, 512), [
                    [vModelText, $data.proposalData.message]
                  ]),
                  _cache[80] || (_cache[80] = createBaseVNode("div", { class: "form-text" }, "Расскажите о вашем оборудовании и условиях аренды", -1))
                ]),
                createBaseVNode("div", _hoisted_121, [
                  _cache[81] || (_cache[81] = createBaseVNode("label", { class: "form-label" }, "Дополнительные условия", -1)),
                  withDirectives(createBaseVNode("textarea", {
                    class: "form-control",
                    rows: "3",
                    "onUpdate:modelValue": _cache[22] || (_cache[22] = ($event) => $data.proposalData.additional_terms = $event),
                    placeholder: "Дополнительные условия доставки, оплаты..."
                  }, null, 512), [
                    [vModelText, $data.proposalData.additional_terms]
                  ]),
                  _cache[82] || (_cache[82] = createBaseVNode("div", { class: "form-text" }, "Необязательные условия, которые важны для вас", -1))
                ]),
                createBaseVNode("div", _hoisted_122, [
                  _cache[87] || (_cache[87] = createBaseVNode("label", { class: "form-label" }, "Выберите оборудование *", -1)),
                  $data.availableEquipment.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_123, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList($data.availableEquipment, (equipment) => {
                      return openBlock(), createElementBlock("div", {
                        key: equipment.id,
                        class: "form-check mb-2 equipment-item"
                      }, [
                        withDirectives(createBaseVNode("input", {
                          class: "form-check-input",
                          type: "checkbox",
                          value: equipment.id,
                          "onUpdate:modelValue": _cache[23] || (_cache[23] = ($event) => $data.proposalData.selected_equipment = $event),
                          id: "equipment-" + equipment.id
                        }, null, 8, _hoisted_124), [
                          [vModelCheckbox, $data.proposalData.selected_equipment]
                        ]),
                        createBaseVNode("label", {
                          class: "form-check-label w-100",
                          for: "equipment-" + equipment.id
                        }, [
                          createBaseVNode("div", _hoisted_126, [
                            createBaseVNode("div", null, [
                              createBaseVNode("strong", null, toDisplayString(equipment.name), 1),
                              createBaseVNode("small", _hoisted_127, toDisplayString(equipment.description), 1)
                            ]),
                            createBaseVNode("div", _hoisted_128, [
                              createBaseVNode("div", _hoisted_129, toDisplayString($options.formatCurrency(equipment.hourly_rate)) + "/час", 1),
                              equipment.is_available ? (openBlock(), createElementBlock("small", _hoisted_130, [..._cache[83] || (_cache[83] = [
                                createBaseVNode("i", { class: "fas fa-check-circle me-1" }, null, -1),
                                createTextVNode("Доступно ", -1)
                              ])])) : (openBlock(), createElementBlock("small", _hoisted_131, [..._cache[84] || (_cache[84] = [
                                createBaseVNode("i", { class: "fas fa-times-circle me-1" }, null, -1),
                                createTextVNode("Недоступно ", -1)
                              ])]))
                            ])
                          ])
                        ], 8, _hoisted_125)
                      ]);
                    }), 128))
                  ])) : (openBlock(), createElementBlock("div", _hoisted_132, [..._cache[85] || (_cache[85] = [
                    createBaseVNode("i", { class: "fas fa-exclamation-triangle me-1" }, null, -1),
                    createTextVNode(" Нет доступного оборудования для категорий этой заявки ", -1)
                  ])])),
                  $data.proposalData.selected_equipment.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_133, [
                    createBaseVNode("small", _hoisted_134, [
                      _cache[86] || (_cache[86] = createBaseVNode("i", { class: "fas fa-check me-1" }, null, -1)),
                      createTextVNode(" Выбрано оборудования: " + toDisplayString($data.proposalData.selected_equipment.length), 1)
                    ])
                  ])) : createCommentVNode("", true)
                ]),
                $data.proposalData.selected_equipment.length > 0 && $data.proposalData.proposed_price ? (openBlock(), createElementBlock("div", _hoisted_135, [
                  _cache[91] || (_cache[91] = createBaseVNode("h6", { class: "mb-2" }, [
                    createBaseVNode("i", { class: "fas fa-calculator me-1 text-info" }),
                    createTextVNode(" Расчет стоимости ")
                  ], -1)),
                  createBaseVNode("div", _hoisted_136, [
                    createBaseVNode("div", _hoisted_137, [
                      createBaseVNode("div", null, [
                        _cache[88] || (_cache[88] = createTextVNode("Цена за час: ", -1)),
                        createBaseVNode("strong", null, toDisplayString($options.formatCurrency($data.proposalData.proposed_price)), 1)
                      ]),
                      createBaseVNode("div", null, [
                        _cache[89] || (_cache[89] = createTextVNode("Кол-во единиц: ", -1)),
                        createBaseVNode("strong", null, toDisplayString($data.proposalData.selected_equipment.length), 1)
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_138, [
                      createBaseVNode("div", _hoisted_139, [
                        _cache[90] || (_cache[90] = createTextVNode(" Итого в час: ", -1)),
                        createBaseVNode("strong", null, toDisplayString($options.formatCurrency($data.proposalData.proposed_price * $data.proposalData.selected_equipment.length)), 1)
                      ])
                    ])
                  ])
                ])) : createCommentVNode("", true)
              ])
            ])) : createCommentVNode("", true)
          ]),
          createBaseVNode("div", _hoisted_140, [
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-secondary",
              onClick: _cache[24] || (_cache[24] = (...args) => $options.closeProposalModal && $options.closeProposalModal(...args))
            }, "Отмена"),
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-primary",
              onClick: _cache[25] || (_cache[25] = (...args) => $options.submitProposal && $options.submitProposal(...args)),
              disabled: $data.submittingProposal || !$options.isProposalValid
            }, [
              $data.submittingProposal ? (openBlock(), createElementBlock("span", _hoisted_142)) : createCommentVNode("", true),
              createTextVNode(" " + toDisplayString($data.submittingProposal ? "Отправка..." : "Отправить предложение"), 1)
            ], 8, _hoisted_141)
          ])
        ])
      ])
    ], 2)) : createCommentVNode("", true)
  ]);
}
const LessorRentalRequestList = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-898122ca"]]);
export {
  LessorRentalRequestList as default
};
