var __defProp = Object.defineProperty;
var __defProps = Object.defineProperties;
var __getOwnPropDescs = Object.getOwnPropertyDescriptors;
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
var __spreadProps = (a, b) => __defProps(a, __getOwnPropDescs(b));
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
import { g as resolveComponent, a as createElementBlock, o as openBlock, b as createBaseVNode, e as createCommentVNode, t as toDisplayString, d as createTextVNode, F as Fragment, r as renderList, n as normalizeClass, h as createStaticVNode, i as createVNode, w as withDirectives, v as vModelSelect, j as vModelText, k as ref, l as watch, m as onMounted } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import ProposalTemplates from "./ProposalTemplates-DKXx8w66.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  name: "RentalRequestDetail",
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
    const activeTab = ref("info");
    const showProposalModal = ref(false);
    const sendingProposal = ref(false);
    const loadingEquipment = ref(false);
    const apiError = ref("");
    const fieldErrors = ref({});
    const recommendedTemplates = ref([]);
    const recommendationsLoaded = ref(false);
    const recommendationStats = ref({
      total_recommendations: 0,
      application_rate: 0,
      conversion_rate: 0,
      average_score: 0
    });
    const availableEquipment = ref([]);
    const proposalForm = ref({
      equipment_id: "",
      proposed_price: "",
      quantity: 1,
      response_time: 24,
      message: "",
      additional_terms: ""
    });
    const loadTemplateRecommendations = () => __async(null, null, function* () {
      try {
        console.log("🤖 Загрузка рекомендаций для заявки:", props.request.id);
        const response = yield axios.get(`/api/lessor/rental-requests/${props.request.id}/recommendations`);
        recommendedTemplates.value = response.data.recommendations || [];
        recommendationsLoaded.value = true;
        console.log("✅ Рекомендации загружены:", recommendedTemplates.value);
      } catch (error) {
        console.error("❌ Ошибка загрузки рекомендаций:", error);
        recommendationsLoaded.value = true;
      }
    });
    const applyRecommendedTemplate = (recommendation) => {
      console.log("⚡ Применение рекомендованного шаблона:", recommendation);
      saveRecommendationFeedback(recommendation, true);
      handleTemplateApplied({
        template: recommendation.template,
        data: {
          proposed_price: recommendation.template.proposed_price,
          response_time: recommendation.template.response_time,
          message: recommendation.template.message,
          additional_terms: recommendation.template.additional_terms
        }
      });
    };
    const saveRecommendationFeedback = (recommendation, applied) => __async(null, null, function* () {
      try {
        yield axios.post("/api/lessor/recommendation-feedback", {
          template_id: recommendation.template.id,
          request_id: props.request.id,
          applied,
          score: recommendation.score
        });
        console.log("✅ Фидбек рекомендации сохранен");
      } catch (error) {
        console.error("❌ Ошибка сохранения фидбека:", error);
      }
    });
    const viewTemplateDetails = (template) => {
      activeTab.value = "templates";
      console.log("👀 Просмотр шаблона:", template);
    };
    const getConfidenceBadgeClass = (confidenceLevel) => {
      const classes = {
        "high": "bg-success",
        "medium": "bg-info",
        "low": "bg-warning",
        "very-low": "bg-secondary"
      };
      return classes[confidenceLevel] || "bg-secondary";
    };
    const loadRecommendationStats = () => __async(null, null, function* () {
      try {
        const response = yield axios.get("/api/lessor/recommendations/stats");
        recommendationStats.value = response.data.stats || {};
      } catch (error) {
        console.error("❌ Ошибка загрузки статистики рекомендаций:", error);
      }
    });
    const viewRecommendationStats = () => {
      console.log("📊 Просмотр статистики рекомендаций");
      alert(`Статистика рекомендаций:
Всего рекомендаций: ${recommendationStats.value.total_recommendations}
Применяемость: ${recommendationStats.value.application_rate}%
Конверсия: ${recommendationStats.value.conversion_rate}%`);
    };
    const loadAvailableEquipment = () => __async(null, null, function* () {
      var _a, _b, _c, _d, _e, _f, _g, _h;
      loadingEquipment.value = true;
      clearErrors();
      try {
        console.log("🔍 ========== ПРОВЕРКА ДОСТУПНОСТИ ОБОРУДОВАНИЯ ==========");
        console.log("📋 Информация о заявке:", {
          id: props.request.id,
          title: props.request.title,
          категории: (_a = props.request.items) == null ? void 0 : _a.map((item) => {
            var _a2, _b2;
            return {
              id: (_a2 = item.category) == null ? void 0 : _a2.id,
              name: (_b2 = item.category) == null ? void 0 : _b2.name,
              количество: item.quantity
            };
          }),
          период: {
            start: props.request.rental_period_start,
            end: props.request.rental_period_end
          },
          локация: (_b = props.request.location) == null ? void 0 : _b.name,
          доставка: props.request.delivery_required ? "Требуется" : "Не требуется"
        });
        let response;
        try {
          response = yield axios.get(`/api/rental-requests/${props.request.id}/available-equipment`);
          console.log("✅ Оборудование загружено через специализированный endpoint:", response.data);
        } catch (error) {
          if (((_c = error.response) == null ? void 0 : _c.status) === 404) {
            console.log("🔧 Специализированный endpoint не найден, используем альтернативный метод...");
            response = yield axios.get("/api/lessor/equipment/my-equipment");
            console.log("✅ Оборудование загружено через общий endpoint:", response.data);
          } else {
            throw error;
          }
        }
        if ((_d = response.data.data) == null ? void 0 : _d.available_equipment) {
          availableEquipment.value = response.data.data.available_equipment.map((item) => __spreadProps(__spreadValues({}, item.equipment), {
            availability_status: "available",
            recommended_price: item.recommended_lessor_price
          }));
        } else if (Array.isArray(response.data.data)) {
          availableEquipment.value = response.data.data.map((equipment) => __spreadProps(__spreadValues({}, equipment), {
            availability_status: "available"
          }));
        } else {
          availableEquipment.value = [];
        }
        console.log("📦 Обработанное оборудование:", availableEquipment.value);
        if (availableEquipment.value.length > 0) {
          const requestCategoryIds = ((_e = props.request.items) == null ? void 0 : _e.map((item) => {
            var _a2;
            return (_a2 = item.category) == null ? void 0 : _a2.id;
          }).filter(Boolean)) || [];
          console.log("🎯 Категории заявки:", requestCategoryIds);
          if (requestCategoryIds.length > 0) {
            const filteredEquipment = availableEquipment.value.filter(
              (equipment) => requestCategoryIds.includes(equipment.category_id)
            );
            console.log("🔍 Оборудование после фильтрации по категориям:", {
              было: availableEquipment.value.length,
              стало: filteredEquipment.length,
              отфильтровано: availableEquipment.value.length - filteredEquipment.length
            });
            availableEquipment.value = filteredEquipment;
          }
        }
        if (availableEquipment.value.length > 0) {
          console.log("📅 Проверка доступности оборудования в период заявки...");
        }
        console.log("🎯 Итоговое доступное оборудование:", availableEquipment.value);
        if (availableEquipment.value.length === 0) {
          console.warn("⚠️ Нет подходящего оборудования для заявки");
          apiError.value = "Нет доступного оборудования, соответствующего требованиям заявки";
        }
      } catch (error) {
        console.error("❌ Ошибка проверки доступности оборудования:", error);
        if (((_f = error.response) == null ? void 0 : _f.status) === 404) {
          apiError.value = "Endpoint проверки доступности не найден. Обратитесь к администратору.";
        } else if ((_h = (_g = error.response) == null ? void 0 : _g.data) == null ? void 0 : _h.message) {
          apiError.value = "Ошибка загрузки оборудования: " + error.response.data.message;
        } else {
          apiError.value = "Ошибка проверки доступности оборудования: " + error.message;
        }
        availableEquipment.value = [];
      } finally {
        loadingEquipment.value = false;
      }
    });
    const getAvailabilityBadgeClass = (status) => {
      const statusClasses = {
        "available": "bg-success",
        "unavailable": "bg-danger",
        "maintenance": "bg-secondary",
        "delivery": "bg-warning",
        "temp_reserve": "bg-info"
      };
      return statusClasses[status] || "bg-secondary";
    };
    const getAvailabilityStatusText = (status) => {
      const statusTexts = {
        "available": "Доступно",
        "unavailable": "Недоступно",
        "maintenance": "Обслуживание",
        "delivery": "В доставке",
        "temp_reserve": "Временный резерв"
      };
      return statusTexts[status] || status;
    };
    const clearErrors = () => {
      apiError.value = "";
      fieldErrors.value = {};
    };
    const closeProposalModal = () => {
      showProposalModal.value = false;
      clearErrors();
    };
    const openProposalModal = () => __async(null, null, function* () {
      showProposalModal.value = true;
      clearErrors();
      yield loadAvailableEquipment();
    });
    const handleTemplateApplied = (templateData) => {
      console.log("✅ Шаблон применен:", templateData);
      clearErrors();
      proposalForm.value = __spreadProps(__spreadValues({}, proposalForm.value), {
        proposed_price: templateData.data.proposed_price,
        response_time: templateData.data.response_time,
        message: templateData.data.message,
        additional_terms: templateData.data.additional_terms
      });
      openProposalModal();
    };
    const submitProposal = () => __async(null, null, function* () {
      var _a, _b, _c, _d, _e, _f, _g;
      clearErrors();
      if (!proposalForm.value.equipment_id) {
        apiError.value = "Пожалуйста, выберите технику";
        return;
      }
      if (!proposalForm.value.proposed_price || proposalForm.value.proposed_price <= 0) {
        apiError.value = "Пожалуйста, укажите корректную цену (больше 0)";
        return;
      }
      if (!proposalForm.value.quantity || proposalForm.value.quantity <= 0) {
        apiError.value = "Пожалуйста, укажите корректное количество";
        return;
      }
      if (!((_a = proposalForm.value.message) == null ? void 0 : _a.trim())) {
        apiError.value = "Пожалуйста, введите сообщение для арендатора";
        return;
      }
      sendingProposal.value = true;
      try {
        const proposalData = {
          equipment_items: [
            {
              equipment_id: parseInt(proposalForm.value.equipment_id),
              proposed_price: parseFloat(proposalForm.value.proposed_price),
              quantity: parseInt(proposalForm.value.quantity) || 1
            }
          ],
          message: proposalForm.value.message.trim(),
          additional_terms: ((_b = proposalForm.value.additional_terms) == null ? void 0 : _b.trim()) || "",
          response_time: parseInt(proposalForm.value.response_time) || 24
        };
        console.log("📤 ========== ОТПРАВКА ПРЕДЛОЖЕНИЯ ==========");
        console.log("📦 Данные для отправки:", JSON.stringify(proposalData, null, 2));
        console.log("🔗 Endpoint:", `/api/rental-requests/${props.request.id}/proposals`);
        console.log("👤 Текущий пользователь ID:", ((_c = window.authUser) == null ? void 0 : _c.id) || "Не определен");
        console.log("🏢 ID заявки:", props.request.id);
        console.log("🔧 Выбранное оборудование ID:", proposalForm.value.equipment_id);
        const response = yield axios.post(`/api/rental-requests/${props.request.id}/proposals`, proposalData, {
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
          },
          timeout: 1e4
          // 10 секунд таймаут
        });
        console.log("📥 ========== ОТВЕТ СЕРВЕРА ==========");
        console.log("🔧 Статус ответа:", response.status);
        console.log("📄 Данные ответа:", response.data);
        console.log("✅ Успех:", response.data.success);
        if (response.data.success) {
          alert("✅ Ваше предложение успешно отправлено!");
          showProposalModal.value = false;
          proposalForm.value = {
            equipment_id: "",
            proposed_price: "",
            quantity: 1,
            response_time: 24,
            message: "",
            additional_terms: ""
          };
          if (typeof window.updateProposalHistory === "function") {
            window.updateProposalHistory();
          }
          if (typeof window.refreshAnalytics === "function") {
            window.refreshAnalytics();
          }
          console.log("🎉 Предложение успешно создано в базе данных");
        } else {
          throw new Error(response.data.message || "Неизвестная ошибка сервера");
        }
      } catch (error) {
        console.error("❌ ========== ОШИБКА ОТПРАВКИ ПРЕДЛОЖЕНИЯ ==========");
        console.error("🔧 Код ошибки:", error.code);
        console.error("📡 URL запроса:", (_d = error.config) == null ? void 0 : _d.url);
        console.error("🔧 Метод запроса:", (_e = error.config) == null ? void 0 : _e.method);
        console.error("📦 Данные запроса:", (_f = error.config) == null ? void 0 : _f.data);
        if (error.response) {
          console.error("📊 Ответ сервера:", error.response.data);
          console.error("🔢 Статус ошибки:", error.response.status);
          console.error("📋 Заголовки ответа:", error.response.headers);
          if (error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            fieldErrors.value = validationErrors;
            apiError.value = "Пожалуйста, исправьте ошибки в форме";
            console.error("❌ Ошибки валидации:", validationErrors);
          } else if (error.response.status === 403) {
            apiError.value = "Недостаточно прав для создания предложения";
          } else if (error.response.status === 404) {
            apiError.value = "Заявка не найдена или была удалена";
          } else if (error.response.status === 401) {
            apiError.value = "Необходимо авторизоваться";
          } else if ((_g = error.response.data) == null ? void 0 : _g.message) {
            apiError.value = error.response.data.message;
          } else {
            apiError.value = "Ошибка сервера при создании предложения";
          }
        } else if (error.request) {
          console.error("🌐 Ошибка сети:", error.request);
          apiError.value = "Ошибка сети: не удалось подключиться к серверу";
        } else if (error.code === "ECONNABORTED") {
          apiError.value = "Превышено время ожидания ответа от сервера";
        } else {
          console.error("⚡ Другая ошибка:", error.message);
          apiError.value = `Ошибка: ${error.message}`;
        }
      } finally {
        sendingProposal.value = false;
      }
    });
    const calculateRentalDays = () => {
      if (!props.request.rental_period_start || !props.request.rental_period_end) {
        return 0;
      }
      const start = new Date(props.request.rental_period_start);
      const end = new Date(props.request.rental_period_end);
      return Math.ceil((end - start) / (1e3 * 3600 * 24)) + 1;
    };
    const calculateItemPrice = (item) => {
      var _a, _b;
      const basePrice = ((_b = (_a = props.lessorPricing) == null ? void 0 : _a.category_prices) == null ? void 0 : _b[item.category_id]) || 1e3;
      return basePrice;
    };
    const formatDate = (dateString) => {
      if (!dateString) return "—";
      try {
        return new Date(dateString).toLocaleDateString("ru-RU");
      } catch (error) {
        console.error("Ошибка форматирования даты:", error);
        return "—";
      }
    };
    const formatCurrency = (amount) => {
      if (!amount && amount !== 0) return "0 ₽";
      try {
        return new Intl.NumberFormat("ru-RU", {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount) + " ₽";
      } catch (error) {
        console.error("Ошибка форматирования валюты:", error);
        return "0 ₽";
      }
    };
    const formatConditionKey = (key) => {
      const conditionNames = {
        "hours_per_shift": "Часов в смену",
        "shifts_per_day": "Смен в день",
        "operator_required": "Требуется оператор",
        "fuel_included": "Топливо включено",
        "maintenance_included": "Обслуживание включено",
        "gsm_payment": "Оплата ГСМ",
        "payment_type": "Тип оплаты",
        "operator_included": "Оператор включен",
        "accommodation_payment": "Оплата проживания",
        "extension_possibility": "Возможность продления",
        "transportation_organized_by": "Организация транспортировки",
        "insurance_included": "Страховка включена",
        "fuel_provided_by": "Топливо предоставляет",
        "maintenance_responsibility": "Обслуживание отвечает"
      };
      return conditionNames[key] || key;
    };
    const formatConditionValue = (key, value) => {
      if (typeof value === "boolean") {
        return value ? "Да" : "Нет";
      }
      const valueMappings = {
        "gsm_payment": {
          "included": "Включено",
          "extra": "Дополнительно",
          "not_included": "Не включено"
        },
        "payment_type": {
          "hourly": "Почасовая",
          "daily": "Посуточная",
          "weekly": "Понедельная",
          "monthly": "Помесячная"
        },
        "transportation_organized_by": {
          "lessor": "Арендодателем",
          "lessee": "Арендатором",
          "third_party": "Третьей стороной"
        },
        "fuel_provided_by": {
          "lessor": "Арендодатель",
          "lessee": "Арендатор"
        },
        "maintenance_responsibility": {
          "lessor": "Арендодатель",
          "lessee": "Арендатор"
        }
      };
      if (valueMappings[key] && valueMappings[key][value]) {
        return valueMappings[key][value];
      }
      return value;
    };
    const getStatusBadgeClass = (status) => {
      const statusClasses = {
        "pending": "bg-warning",
        "accepted": "bg-success",
        "rejected": "bg-danger",
        "expired": "bg-secondary"
      };
      return statusClasses[status] || "bg-secondary";
    };
    const getStatusText = (status) => {
      const statusTexts = {
        "pending": "Ожидает",
        "accepted": "Принято",
        "rejected": "Отклонено",
        "expired": "Истекло"
      };
      return statusTexts[status] || status;
    };
    const getPriceDifferenceClass = (difference) => {
      if (difference > 10) return "text-danger";
      if (difference > 0) return "text-warning";
      if (difference < -10) return "text-success";
      return "text-info";
    };
    const addToFavorites = () => {
      console.log("⭐ Добавление в избранное:", props.request.id);
      alert("Заявка добавлена в избранное!");
    };
    const viewProposalDetails = (proposal) => {
      console.log("👀 Просмотр деталей предложения:", proposal);
    };
    watch(showProposalModal, (newVal) => {
      if (newVal) {
        document.body.classList.add("modal-open");
        document.body.style.overflow = "hidden";
        document.body.style.paddingRight = "15px";
      } else {
        document.body.classList.remove("modal-open");
        document.body.style.overflow = "";
        document.body.style.paddingRight = "";
      }
    });
    onMounted(() => {
      console.log("✅ RentalRequestDetail mounted");
      console.log("📦 Request data:", props.request);
      console.log("📊 Analytics:", props.analytics);
      console.log("💰 Pricing:", props.lessorPricing);
      console.log("📋 Templates:", props.templates);
      loadTemplateRecommendations();
      loadRecommendationStats();
    });
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
    };
  }
};
const _hoisted_1 = { class: "rental-request-detail" };
const _hoisted_2 = { class: "request-header card mb-4" };
const _hoisted_3 = { class: "card-body" };
const _hoisted_4 = { class: "row align-items-center" };
const _hoisted_5 = { class: "col-md-8" };
const _hoisted_6 = { class: "card-title mb-2" };
const _hoisted_7 = { class: "card-text text-muted mb-3" };
const _hoisted_8 = { class: "request-meta" };
const _hoisted_9 = { class: "row" };
const _hoisted_10 = { class: "col-md-6" };
const _hoisted_11 = { class: "meta-item mb-2" };
const _hoisted_12 = { class: "ms-2 text-success fw-bold" };
const _hoisted_13 = { class: "meta-item mb-2" };
const _hoisted_14 = { class: "ms-2" };
const _hoisted_15 = { class: "col-md-6" };
const _hoisted_16 = { class: "meta-item mb-2" };
const _hoisted_17 = { class: "ms-2" };
const _hoisted_18 = { class: "meta-item mb-2" };
const _hoisted_19 = { class: "ms-2" };
const _hoisted_20 = { class: "col-md-4 text-end" };
const _hoisted_21 = { class: "action-buttons" };
const _hoisted_22 = { class: "stats-badges mt-3" };
const _hoisted_23 = { class: "badge bg-info me-2" };
const _hoisted_24 = { class: "badge bg-warning" };
const _hoisted_25 = {
  key: 0,
  class: "smart-recommendations card mb-4"
};
const _hoisted_26 = { class: "card-header bg-primary text-white" };
const _hoisted_27 = { class: "mb-0" };
const _hoisted_28 = { class: "badge bg-light text-primary ms-2" };
const _hoisted_29 = { class: "card-body" };
const _hoisted_30 = { class: "recommendations-grid" };
const _hoisted_31 = { class: "recommendation-header d-flex justify-content-between align-items-start mb-2" };
const _hoisted_32 = { class: "reason text-muted" };
const _hoisted_33 = { class: "template-preview mb-3" };
const _hoisted_34 = { class: "d-block mb-1" };
const _hoisted_35 = { class: "price text-success fw-bold mb-1" };
const _hoisted_36 = { class: "stats small text-muted" };
const _hoisted_37 = { class: "mb-1" };
const _hoisted_38 = { class: "recommendation-actions d-flex gap-2" };
const _hoisted_39 = ["onClick"];
const _hoisted_40 = ["onClick"];
const _hoisted_41 = {
  key: 1,
  class: "smart-recommendations card mb-4"
};
const _hoisted_42 = { class: "card-body text-center py-4" };
const _hoisted_43 = { class: "request-tabs card mb-4" };
const _hoisted_44 = { class: "card-header" };
const _hoisted_45 = { class: "nav nav-tabs card-header-tabs" };
const _hoisted_46 = { class: "nav-item" };
const _hoisted_47 = { class: "nav-item" };
const _hoisted_48 = {
  key: 0,
  class: "badge bg-primary ms-1"
};
const _hoisted_49 = { class: "nav-item" };
const _hoisted_50 = {
  key: 0,
  class: "badge bg-info ms-1"
};
const _hoisted_51 = { class: "nav-item" };
const _hoisted_52 = { class: "nav-item" };
const _hoisted_53 = {
  key: 0,
  class: "badge bg-success ms-1"
};
const _hoisted_54 = { class: "card-body" };
const _hoisted_55 = {
  key: 0,
  class: "tab-content-info"
};
const _hoisted_56 = { class: "row" };
const _hoisted_57 = { class: "col-lg-8" };
const _hoisted_58 = { class: "card-body" };
const _hoisted_59 = { class: "row align-items-center" };
const _hoisted_60 = { class: "col-md-8" };
const _hoisted_61 = { class: "card-title" };
const _hoisted_62 = { class: "badge bg-primary" };
const _hoisted_63 = {
  key: 0,
  class: "specifications mt-2"
};
const _hoisted_64 = {
  key: 1,
  class: "text-muted small mt-2"
};
const _hoisted_65 = { class: "col-md-4 text-end" };
const _hoisted_66 = { class: "price-estimate" };
const _hoisted_67 = { class: "text-success fw-bold" };
const _hoisted_68 = { class: "col-lg-4" };
const _hoisted_69 = { class: "additional-info" };
const _hoisted_70 = { class: "info-section mb-4" };
const _hoisted_71 = {
  key: 0,
  class: "conditions-list"
};
const _hoisted_72 = {
  key: 1,
  class: "text-muted small"
};
const _hoisted_73 = {
  key: 1,
  class: "tab-content-templates"
};
const _hoisted_74 = {
  key: 2,
  class: "tab-content-proposals"
};
const _hoisted_75 = {
  key: 0,
  class: "proposals-list"
};
const _hoisted_76 = { class: "card-body" };
const _hoisted_77 = { class: "row align-items-center" };
const _hoisted_78 = { class: "col-md-3" };
const _hoisted_79 = { class: "proposal-price" };
const _hoisted_80 = { class: "text-success fs-5" };
const _hoisted_81 = { class: "col-md-4" };
const _hoisted_82 = { class: "proposal-equipment" };
const _hoisted_83 = { class: "small text-muted" };
const _hoisted_84 = { class: "col-md-3" };
const _hoisted_85 = { class: "col-md-2 text-end" };
const _hoisted_86 = ["onClick"];
const _hoisted_87 = {
  key: 1,
  class: "text-center py-5"
};
const _hoisted_88 = { class: "empty-state" };
const _hoisted_89 = {
  key: 3,
  class: "tab-content-analytics"
};
const _hoisted_90 = { class: "row" };
const _hoisted_91 = { class: "col-md-6" };
const _hoisted_92 = { class: "card" };
const _hoisted_93 = { class: "card-body" };
const _hoisted_94 = { class: "analytics-item mb-3" };
const _hoisted_95 = { class: "d-flex justify-content-between" };
const _hoisted_96 = { class: "analytics-item mb-3" };
const _hoisted_97 = { class: "d-flex justify-content-between" };
const _hoisted_98 = { class: "text-info" };
const _hoisted_99 = { class: "analytics-item" };
const _hoisted_100 = { class: "d-flex justify-content-between" };
const _hoisted_101 = { class: "text-success" };
const _hoisted_102 = { class: "col-md-6" };
const _hoisted_103 = { class: "card" };
const _hoisted_104 = { class: "card-body" };
const _hoisted_105 = { class: "analytics-item mb-3" };
const _hoisted_106 = { class: "d-flex justify-content-between" };
const _hoisted_107 = { class: "text-warning" };
const _hoisted_108 = { class: "analytics-item mb-3" };
const _hoisted_109 = { class: "d-flex justify-content-between" };
const _hoisted_110 = { class: "text-secondary" };
const _hoisted_111 = { class: "analytics-item" };
const _hoisted_112 = { class: "d-flex justify-content-between" };
const _hoisted_113 = {
  key: 0,
  class: "card mt-4"
};
const _hoisted_114 = { class: "card-body" };
const _hoisted_115 = { class: "row text-center" };
const _hoisted_116 = { class: "col-md-4" };
const _hoisted_117 = { class: "price-comparison-item" };
const _hoisted_118 = { class: "price-value text-success" };
const _hoisted_119 = { class: "col-md-4" };
const _hoisted_120 = { class: "price-comparison-item" };
const _hoisted_121 = { class: "price-value text-info" };
const _hoisted_122 = { class: "col-md-4" };
const _hoisted_123 = { class: "price-comparison-item" };
const _hoisted_124 = { class: "difference-value" };
const _hoisted_125 = { class: "difference-label" };
const _hoisted_126 = {
  key: 4,
  class: "tab-content-recommendations"
};
const _hoisted_127 = { class: "row" };
const _hoisted_128 = { class: "col-md-8" };
const _hoisted_129 = { class: "recommendation-stats card mb-4" };
const _hoisted_130 = { class: "card-body" };
const _hoisted_131 = { class: "row text-center" };
const _hoisted_132 = { class: "col-md-4" };
const _hoisted_133 = { class: "stat-item" };
const _hoisted_134 = { class: "stat-value text-primary" };
const _hoisted_135 = { class: "col-md-4" };
const _hoisted_136 = { class: "stat-item" };
const _hoisted_137 = { class: "stat-value text-success" };
const _hoisted_138 = { class: "col-md-4" };
const _hoisted_139 = { class: "stat-item" };
const _hoisted_140 = { class: "stat-value text-warning" };
const _hoisted_141 = {
  key: 0,
  class: "recommendations-list"
};
const _hoisted_142 = { class: "card-body" };
const _hoisted_143 = { class: "row align-items-center" };
const _hoisted_144 = { class: "col-md-1" };
const _hoisted_145 = { class: "recommendation-rank" };
const _hoisted_146 = { class: "col-md-7" };
const _hoisted_147 = { class: "mb-1" };
const _hoisted_148 = { class: "text-muted small mb-2" };
const _hoisted_149 = { class: "template-details small" };
const _hoisted_150 = { class: "me-3" };
const _hoisted_151 = { class: "me-3" };
const _hoisted_152 = { class: "col-md-4 text-end" };
const _hoisted_153 = { class: "confidence-level mb-2" };
const _hoisted_154 = { class: "recommendation-actions" };
const _hoisted_155 = ["onClick"];
const _hoisted_156 = ["onClick"];
const _hoisted_157 = {
  key: 1,
  class: "text-center py-5"
};
const _hoisted_158 = { class: "empty-state" };
const _hoisted_159 = { class: "col-md-4" };
const _hoisted_160 = { class: "recommendation-actions-card card mt-4" };
const _hoisted_161 = { class: "card-body" };
const _hoisted_162 = { class: "action-list" };
const _hoisted_163 = { class: "modal-dialog modal-lg modal-dialog-centered" };
const _hoisted_164 = { class: "modal-content" };
const _hoisted_165 = { class: "modal-header" };
const _hoisted_166 = { class: "modal-body" };
const _hoisted_167 = {
  key: 0,
  class: "alert alert-danger"
};
const _hoisted_168 = {
  key: 1,
  class: "text-center py-3"
};
const _hoisted_169 = { key: 2 };
const _hoisted_170 = { class: "card mb-3" };
const _hoisted_171 = { class: "card-body" };
const _hoisted_172 = { class: "row" };
const _hoisted_173 = { class: "col-md-6" };
const _hoisted_174 = { class: "col-md-6" };
const _hoisted_175 = { class: "mb-3" };
const _hoisted_176 = ["disabled"];
const _hoisted_177 = ["value"];
const _hoisted_178 = {
  key: 0,
  class: "invalid-feedback"
};
const _hoisted_179 = {
  key: 1,
  class: "alert alert-warning mt-2"
};
const _hoisted_180 = {
  key: 2,
  class: "text-muted small mt-1"
};
const _hoisted_181 = { class: "row mb-3" };
const _hoisted_182 = { class: "col-md-6" };
const _hoisted_183 = {
  key: 0,
  class: "invalid-feedback"
};
const _hoisted_184 = { class: "col-md-6" };
const _hoisted_185 = {
  key: 0,
  class: "invalid-feedback"
};
const _hoisted_186 = { class: "row mb-3" };
const _hoisted_187 = { class: "col-md-6" };
const _hoisted_188 = { class: "mb-3" };
const _hoisted_189 = {
  key: 0,
  class: "invalid-feedback"
};
const _hoisted_190 = { class: "mb-3" };
const _hoisted_191 = { class: "modal-footer" };
const _hoisted_192 = ["disabled"];
const _hoisted_193 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _a, _b;
  const _component_ProposalTemplates = resolveComponent("ProposalTemplates");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createBaseVNode("div", _hoisted_4, [
          createBaseVNode("div", _hoisted_5, [
            createBaseVNode("h2", _hoisted_6, toDisplayString($props.request.title), 1),
            createBaseVNode("p", _hoisted_7, toDisplayString($props.request.description), 1),
            createBaseVNode("div", _hoisted_8, [
              createBaseVNode("div", _hoisted_9, [
                createBaseVNode("div", _hoisted_10, [
                  createBaseVNode("div", _hoisted_11, [
                    _cache[22] || (_cache[22] = createBaseVNode("i", { class: "fas fa-ruble-sign text-success me-2" }, null, -1)),
                    _cache[23] || (_cache[23] = createBaseVNode("strong", null, "Бюджет для вас:", -1)),
                    createBaseVNode("span", _hoisted_12, toDisplayString($setup.formatCurrency(((_a = $props.lessorPricing) == null ? void 0 : _a.total_lessor_budget) || 0)), 1)
                  ]),
                  createBaseVNode("div", _hoisted_13, [
                    _cache[24] || (_cache[24] = createBaseVNode("i", { class: "fas fa-map-marker-alt text-danger me-2" }, null, -1)),
                    _cache[25] || (_cache[25] = createBaseVNode("strong", null, "Локация:", -1)),
                    createBaseVNode("span", _hoisted_14, toDisplayString(((_b = $props.request.location) == null ? void 0 : _b.name) || "Не указана"), 1)
                  ])
                ]),
                createBaseVNode("div", _hoisted_15, [
                  createBaseVNode("div", _hoisted_16, [
                    _cache[26] || (_cache[26] = createBaseVNode("i", { class: "fas fa-calendar-alt text-primary me-2" }, null, -1)),
                    _cache[27] || (_cache[27] = createBaseVNode("strong", null, "Срок аренды:", -1)),
                    createBaseVNode("span", _hoisted_17, toDisplayString($setup.formatDate($props.request.rental_period_start)) + " - " + toDisplayString($setup.formatDate($props.request.rental_period_end)) + " (" + toDisplayString($setup.calculateRentalDays()) + " дней) ", 1)
                  ]),
                  createBaseVNode("div", _hoisted_18, [
                    _cache[28] || (_cache[28] = createBaseVNode("i", { class: "fas fa-truck text-warning me-2" }, null, -1)),
                    _cache[29] || (_cache[29] = createBaseVNode("strong", null, "Доставка:", -1)),
                    createBaseVNode("span", _hoisted_19, toDisplayString($props.request.delivery_required ? "Требуется" : "Не требуется"), 1)
                  ])
                ])
              ])
            ])
          ]),
          createBaseVNode("div", _hoisted_20, [
            createBaseVNode("div", _hoisted_21, [
              createBaseVNode("button", {
                onClick: _cache[0] || (_cache[0] = (...args) => $setup.openProposalModal && $setup.openProposalModal(...args)),
                class: "btn btn-primary btn-lg w-100 mb-2"
              }, [..._cache[30] || (_cache[30] = [
                createBaseVNode("i", { class: "fas fa-paper-plane me-2" }, null, -1),
                createTextVNode(" Предложить технику ", -1)
              ])]),
              createBaseVNode("button", {
                class: "btn btn-outline-secondary w-100 mb-2",
                onClick: _cache[1] || (_cache[1] = (...args) => $setup.addToFavorites && $setup.addToFavorites(...args))
              }, [..._cache[31] || (_cache[31] = [
                createBaseVNode("i", { class: "fas fa-star me-2" }, null, -1),
                createTextVNode("В избранное ", -1)
              ])]),
              createBaseVNode("div", _hoisted_22, [
                createBaseVNode("span", _hoisted_23, [
                  _cache[32] || (_cache[32] = createBaseVNode("i", { class: "fas fa-eye me-1" }, null, -1)),
                  createTextVNode(" " + toDisplayString($props.request.views_count || 0) + " просмотров ", 1)
                ]),
                createBaseVNode("span", _hoisted_24, [
                  _cache[33] || (_cache[33] = createBaseVNode("i", { class: "fas fa-paper-plane me-1" }, null, -1)),
                  createTextVNode(" " + toDisplayString($props.request.total_proposals_count || 0) + " предложений ", 1)
                ])
              ])
            ])
          ])
        ])
      ])
    ]),
    $setup.recommendedTemplates.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_25, [
      createBaseVNode("div", _hoisted_26, [
        createBaseVNode("h6", _hoisted_27, [
          _cache[34] || (_cache[34] = createBaseVNode("i", { class: "fas fa-robot me-2" }, null, -1)),
          _cache[35] || (_cache[35] = createTextVNode("Умные рекомендации шаблонов ", -1)),
          createBaseVNode("span", _hoisted_28, toDisplayString($setup.recommendedTemplates.length), 1)
        ])
      ]),
      createBaseVNode("div", _hoisted_29, [
        createBaseVNode("div", _hoisted_30, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($setup.recommendedTemplates, (recommendation) => {
            return openBlock(), createElementBlock("div", {
              key: recommendation.template.id,
              class: normalizeClass(["recommendation-card", "confidence-" + recommendation.confidence_level])
            }, [
              createBaseVNode("div", _hoisted_31, [
                createBaseVNode("span", {
                  class: normalizeClass(["confidence-badge badge", $setup.getConfidenceBadgeClass(recommendation.confidence_level)])
                }, toDisplayString(recommendation.confidence) + " (" + toDisplayString(recommendation.score) + "%) ", 3),
                createBaseVNode("small", _hoisted_32, toDisplayString(recommendation.reason), 1)
              ]),
              createBaseVNode("div", _hoisted_33, [
                createBaseVNode("strong", _hoisted_34, toDisplayString(recommendation.template.name), 1),
                createBaseVNode("div", _hoisted_35, toDisplayString($setup.formatCurrency(recommendation.template.proposed_price)) + "/час ", 1),
                createBaseVNode("div", _hoisted_36, [
                  createBaseVNode("div", _hoisted_37, [
                    _cache[36] || (_cache[36] = createBaseVNode("i", { class: "fas fa-chart-line me-1" }, null, -1)),
                    createTextVNode(" Конверсия: " + toDisplayString(recommendation.template.success_rate || 0) + "% ", 1)
                  ]),
                  createBaseVNode("div", null, [
                    _cache[37] || (_cache[37] = createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1)),
                    createTextVNode(" Ответ: " + toDisplayString(recommendation.template.response_time) + "ч ", 1)
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_38, [
                createBaseVNode("button", {
                  onClick: ($event) => $setup.applyRecommendedTemplate(recommendation),
                  class: "btn btn-sm btn-primary flex-fill"
                }, [..._cache[38] || (_cache[38] = [
                  createBaseVNode("i", { class: "fas fa-bolt me-1" }, null, -1),
                  createTextVNode("Применить ", -1)
                ])], 8, _hoisted_39),
                createBaseVNode("button", {
                  onClick: ($event) => $setup.viewTemplateDetails(recommendation.template),
                  class: "btn btn-sm btn-outline-secondary"
                }, [..._cache[39] || (_cache[39] = [
                  createBaseVNode("i", { class: "fas fa-eye" }, null, -1)
                ])], 8, _hoisted_40)
              ])
            ], 2);
          }), 128))
        ])
      ])
    ])) : $setup.recommendationsLoaded ? (openBlock(), createElementBlock("div", _hoisted_41, [
      createBaseVNode("div", _hoisted_42, [
        _cache[41] || (_cache[41] = createBaseVNode("i", { class: "fas fa-robot fa-2x text-muted mb-3" }, null, -1)),
        _cache[42] || (_cache[42] = createBaseVNode("h6", { class: "text-muted" }, "Анализируем заявку...", -1)),
        _cache[43] || (_cache[43] = createBaseVNode("p", { class: "text-muted small mb-0" }, "Нужно больше данных для персонализированных рекомендаций", -1)),
        createBaseVNode("button", {
          class: "btn btn-outline-primary btn-sm mt-2",
          onClick: _cache[2] || (_cache[2] = (...args) => $setup.loadTemplateRecommendations && $setup.loadTemplateRecommendations(...args))
        }, [..._cache[40] || (_cache[40] = [
          createBaseVNode("i", { class: "fas fa-refresh me-1" }, null, -1),
          createTextVNode("Попробовать снова ", -1)
        ])])
      ])
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_43, [
      createBaseVNode("div", _hoisted_44, [
        createBaseVNode("ul", _hoisted_45, [
          createBaseVNode("li", _hoisted_46, [
            createBaseVNode("button", {
              class: normalizeClass(["nav-link", { "active": $setup.activeTab === "info" }]),
              onClick: _cache[3] || (_cache[3] = ($event) => $setup.activeTab = "info")
            }, [..._cache[44] || (_cache[44] = [
              createBaseVNode("i", { class: "fas fa-info-circle me-2" }, null, -1),
              createTextVNode(" Информация ", -1)
            ])], 2)
          ]),
          createBaseVNode("li", _hoisted_47, [
            createBaseVNode("button", {
              class: normalizeClass(["nav-link", { "active": $setup.activeTab === "templates" }]),
              onClick: _cache[4] || (_cache[4] = ($event) => $setup.activeTab = "templates")
            }, [
              _cache[45] || (_cache[45] = createBaseVNode("i", { class: "fas fa-file-alt me-2" }, null, -1)),
              _cache[46] || (_cache[46] = createTextVNode(" Шаблоны ", -1)),
              $props.templates.length > 0 ? (openBlock(), createElementBlock("span", _hoisted_48, toDisplayString($props.templates.length), 1)) : createCommentVNode("", true)
            ], 2)
          ]),
          createBaseVNode("li", _hoisted_49, [
            createBaseVNode("button", {
              class: normalizeClass(["nav-link", { "active": $setup.activeTab === "proposals" }]),
              onClick: _cache[5] || (_cache[5] = ($event) => $setup.activeTab = "proposals")
            }, [
              _cache[47] || (_cache[47] = createBaseVNode("i", { class: "fas fa-history me-2" }, null, -1)),
              _cache[48] || (_cache[48] = createTextVNode(" История предложений ", -1)),
              $props.proposalHistory.length > 0 ? (openBlock(), createElementBlock("span", _hoisted_50, toDisplayString($props.proposalHistory.length), 1)) : createCommentVNode("", true)
            ], 2)
          ]),
          createBaseVNode("li", _hoisted_51, [
            createBaseVNode("button", {
              class: normalizeClass(["nav-link", { "active": $setup.activeTab === "analytics" }]),
              onClick: _cache[6] || (_cache[6] = ($event) => $setup.activeTab = "analytics")
            }, [..._cache[49] || (_cache[49] = [
              createBaseVNode("i", { class: "fas fa-chart-bar me-2" }, null, -1),
              createTextVNode(" Аналитика ", -1)
            ])], 2)
          ]),
          createBaseVNode("li", _hoisted_52, [
            createBaseVNode("button", {
              class: normalizeClass(["nav-link", { "active": $setup.activeTab === "recommendations" }]),
              onClick: _cache[7] || (_cache[7] = ($event) => $setup.activeTab = "recommendations")
            }, [
              _cache[50] || (_cache[50] = createBaseVNode("i", { class: "fas fa-robot me-2" }, null, -1)),
              _cache[51] || (_cache[51] = createTextVNode(" Рекомендации ", -1)),
              $setup.recommendedTemplates.length > 0 ? (openBlock(), createElementBlock("span", _hoisted_53, toDisplayString($setup.recommendedTemplates.length), 1)) : createCommentVNode("", true)
            ], 2)
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_54, [
        $setup.activeTab === "info" ? (openBlock(), createElementBlock("div", _hoisted_55, [
          createBaseVNode("div", _hoisted_56, [
            createBaseVNode("div", _hoisted_57, [
              _cache[54] || (_cache[54] = createBaseVNode("h5", { class: "mb-3" }, "Технические требования", -1)),
              (openBlock(true), createElementBlock(Fragment, null, renderList($props.request.items, (item, index) => {
                var _a2;
                return openBlock(), createElementBlock("div", {
                  key: index,
                  class: "position-item card mb-3"
                }, [
                  createBaseVNode("div", _hoisted_58, [
                    createBaseVNode("div", _hoisted_59, [
                      createBaseVNode("div", _hoisted_60, [
                        createBaseVNode("h6", _hoisted_61, [
                          createTextVNode(toDisplayString(((_a2 = item.category) == null ? void 0 : _a2.name) || "Без категории") + " ", 1),
                          createBaseVNode("span", _hoisted_62, "× " + toDisplayString(item.quantity), 1)
                        ]),
                        item.formatted_specifications && item.formatted_specifications.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_63, [
                          (openBlock(true), createElementBlock(Fragment, null, renderList(item.formatted_specifications, (spec, specIndex) => {
                            return openBlock(), createElementBlock("div", {
                              key: specIndex,
                              class: "spec-item badge bg-light text-dark me-1 mb-1"
                            }, toDisplayString(spec.formatted || spec), 1);
                          }), 128))
                        ])) : (openBlock(), createElementBlock("div", _hoisted_64, [..._cache[52] || (_cache[52] = [
                          createBaseVNode("i", { class: "fas fa-info-circle me-1" }, null, -1),
                          createTextVNode(" Спецификации не указаны ", -1)
                        ])]))
                      ]),
                      createBaseVNode("div", _hoisted_65, [
                        createBaseVNode("div", _hoisted_66, [
                          createBaseVNode("div", _hoisted_67, toDisplayString($setup.formatCurrency($setup.calculateItemPrice(item))) + "/час ", 1),
                          _cache[53] || (_cache[53] = createBaseVNode("small", { class: "text-muted" }, "Примерная цена", -1))
                        ])
                      ])
                    ])
                  ])
                ]);
              }), 128))
            ]),
            createBaseVNode("div", _hoisted_68, [
              createBaseVNode("div", _hoisted_69, [
                _cache[56] || (_cache[56] = createBaseVNode("h5", { class: "mb-3" }, "Дополнительная информация", -1)),
                createBaseVNode("div", _hoisted_70, [
                  _cache[55] || (_cache[55] = createBaseVNode("h6", { class: "text-muted mb-2" }, "Условия аренды", -1)),
                  $props.request.rental_conditions ? (openBlock(), createElementBlock("div", _hoisted_71, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList($props.request.rental_conditions, (value, key) => {
                      return openBlock(), createElementBlock("div", {
                        key,
                        class: "condition-item small mb-1"
                      }, [
                        createBaseVNode("strong", null, toDisplayString($setup.formatConditionKey(key)) + ":", 1),
                        createTextVNode(" " + toDisplayString($setup.formatConditionValue(key, value)), 1)
                      ]);
                    }), 128))
                  ])) : (openBlock(), createElementBlock("div", _hoisted_72, " Условия не указаны "))
                ]),
                _cache[57] || (_cache[57] = createStaticVNode('<div class="info-section" data-v-697ac056><h6 class="text-muted mb-2" data-v-697ac056>Информация о платформе</h6><div class="platform-info small" data-v-697ac056><div class="platform-item mb-1" data-v-697ac056><i class="fas fa-building me-2" data-v-697ac056></i><strong data-v-697ac056>Платформа:</strong> ФАП </div><div class="platform-item mb-1" data-v-697ac056><i class="fas fa-user me-2" data-v-697ac056></i><strong data-v-697ac056>Менеджер:</strong> Иван Петров </div><div class="platform-item mb-1" data-v-697ac056><i class="fas fa-phone me-2" data-v-697ac056></i><strong data-v-697ac056>Телефон:</strong> +7 (495) 123-45-67 </div><div class="platform-item mb-1" data-v-697ac056><i class="fas fa-envelope me-2" data-v-697ac056></i><strong data-v-697ac056>Email:</strong> office@fap24.ru </div></div></div>', 1))
              ])
            ])
          ])
        ])) : createCommentVNode("", true),
        $setup.activeTab === "templates" ? (openBlock(), createElementBlock("div", _hoisted_73, [
          createVNode(_component_ProposalTemplates, {
            categories: $props.categories,
            "rental-request-id": $props.request.id,
            onTemplateApplied: $setup.handleTemplateApplied
          }, null, 8, ["categories", "rental-request-id", "onTemplateApplied"])
        ])) : createCommentVNode("", true),
        $setup.activeTab === "proposals" ? (openBlock(), createElementBlock("div", _hoisted_74, [
          _cache[63] || (_cache[63] = createBaseVNode("h5", { class: "mb-3" }, "История ваших предложений", -1)),
          $props.proposalHistory.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_75, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($props.proposalHistory, (proposal, index) => {
              return openBlock(), createElementBlock("div", {
                key: index,
                class: "proposal-item card mb-3"
              }, [
                createBaseVNode("div", _hoisted_76, [
                  createBaseVNode("div", _hoisted_77, [
                    createBaseVNode("div", _hoisted_78, [
                      createBaseVNode("div", _hoisted_79, [
                        createBaseVNode("strong", _hoisted_80, toDisplayString($setup.formatCurrency(proposal.proposed_price)) + "/час ", 1)
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_81, [
                      createBaseVNode("div", _hoisted_82, [
                        createBaseVNode("strong", null, toDisplayString(proposal.equipment_title), 1),
                        createBaseVNode("div", _hoisted_83, toDisplayString($setup.formatDate(proposal.created_at)), 1)
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_84, [
                      createBaseVNode("span", {
                        class: normalizeClass(["badge", $setup.getStatusBadgeClass(proposal.status)])
                      }, toDisplayString($setup.getStatusText(proposal.status)), 3)
                    ]),
                    createBaseVNode("div", _hoisted_85, [
                      createBaseVNode("button", {
                        class: "btn btn-outline-primary btn-sm",
                        onClick: ($event) => $setup.viewProposalDetails(proposal)
                      }, [..._cache[58] || (_cache[58] = [
                        createBaseVNode("i", { class: "fas fa-eye" }, null, -1)
                      ])], 8, _hoisted_86)
                    ])
                  ])
                ])
              ]);
            }), 128))
          ])) : (openBlock(), createElementBlock("div", _hoisted_87, [
            createBaseVNode("div", _hoisted_88, [
              _cache[60] || (_cache[60] = createBaseVNode("i", { class: "fas fa-inbox fa-3x text-muted mb-3" }, null, -1)),
              _cache[61] || (_cache[61] = createBaseVNode("h5", null, "Предложений нет", -1)),
              _cache[62] || (_cache[62] = createBaseVNode("p", { class: "text-muted" }, "Вы еще не отправляли предложений по этой заявке", -1)),
              createBaseVNode("button", {
                onClick: _cache[8] || (_cache[8] = (...args) => $setup.openProposalModal && $setup.openProposalModal(...args)),
                class: "btn btn-primary"
              }, [..._cache[59] || (_cache[59] = [
                createBaseVNode("i", { class: "fas fa-paper-plane me-2" }, null, -1),
                createTextVNode(" Сделать предложение ", -1)
              ])])
            ])
          ]))
        ])) : createCommentVNode("", true),
        $setup.activeTab === "analytics" ? (openBlock(), createElementBlock("div", _hoisted_89, [
          _cache[75] || (_cache[75] = createBaseVNode("h5", { class: "mb-3" }, "Аналитика по заявке", -1)),
          createBaseVNode("div", _hoisted_90, [
            createBaseVNode("div", _hoisted_91, [
              createBaseVNode("div", _hoisted_92, [
                _cache[67] || (_cache[67] = createBaseVNode("div", { class: "card-header" }, [
                  createBaseVNode("h6", { class: "card-title mb-0" }, "Конкуренция")
                ], -1)),
                createBaseVNode("div", _hoisted_93, [
                  createBaseVNode("div", _hoisted_94, [
                    createBaseVNode("div", _hoisted_95, [
                      _cache[64] || (_cache[64] = createBaseVNode("span", null, "Всего предложений:", -1)),
                      createBaseVNode("strong", null, toDisplayString($props.analytics.total_proposals || 0), 1)
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_96, [
                    createBaseVNode("div", _hoisted_97, [
                      _cache[65] || (_cache[65] = createBaseVNode("span", null, "Ваших предложений:", -1)),
                      createBaseVNode("strong", _hoisted_98, toDisplayString($props.analytics.my_proposals || 0), 1)
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_99, [
                    createBaseVNode("div", _hoisted_100, [
                      _cache[66] || (_cache[66] = createBaseVNode("span", null, "Принято ваших:", -1)),
                      createBaseVNode("strong", _hoisted_101, toDisplayString($props.analytics.my_accepted_proposals || 0), 1)
                    ])
                  ])
                ])
              ])
            ]),
            createBaseVNode("div", _hoisted_102, [
              createBaseVNode("div", _hoisted_103, [
                _cache[71] || (_cache[71] = createBaseVNode("div", { class: "card-header" }, [
                  createBaseVNode("h6", { class: "card-title mb-0" }, "Эффективность")
                ], -1)),
                createBaseVNode("div", _hoisted_104, [
                  createBaseVNode("div", _hoisted_105, [
                    createBaseVNode("div", _hoisted_106, [
                      _cache[68] || (_cache[68] = createBaseVNode("span", null, "Ваша конверсия:", -1)),
                      createBaseVNode("strong", _hoisted_107, toDisplayString($props.analytics.my_conversion_rate || 0) + "%", 1)
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_108, [
                    createBaseVNode("div", _hoisted_109, [
                      _cache[69] || (_cache[69] = createBaseVNode("span", null, "Конверсия рынка:", -1)),
                      createBaseVNode("strong", _hoisted_110, toDisplayString($props.analytics.market_conversion_rate || 0) + "%", 1)
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_111, [
                    createBaseVNode("div", _hoisted_112, [
                      _cache[70] || (_cache[70] = createBaseVNode("span", null, "Просмотры заявки:", -1)),
                      createBaseVNode("strong", null, toDisplayString($props.request.views_count || 0), 1)
                    ])
                  ])
                ])
              ])
            ])
          ]),
          $props.analytics.price_comparison ? (openBlock(), createElementBlock("div", _hoisted_113, [
            _cache[74] || (_cache[74] = createBaseVNode("div", { class: "card-header" }, [
              createBaseVNode("h6", { class: "card-title mb-0" }, "Сравнение цен")
            ], -1)),
            createBaseVNode("div", _hoisted_114, [
              createBaseVNode("div", _hoisted_115, [
                createBaseVNode("div", _hoisted_116, [
                  createBaseVNode("div", _hoisted_117, [
                    createBaseVNode("div", _hoisted_118, toDisplayString($setup.formatCurrency($props.analytics.price_comparison.my_avg_price)), 1),
                    _cache[72] || (_cache[72] = createBaseVNode("div", { class: "price-label" }, "Ваша средняя", -1))
                  ])
                ]),
                createBaseVNode("div", _hoisted_119, [
                  createBaseVNode("div", _hoisted_120, [
                    createBaseVNode("div", _hoisted_121, toDisplayString($setup.formatCurrency($props.analytics.price_comparison.market_avg_price)), 1),
                    _cache[73] || (_cache[73] = createBaseVNode("div", { class: "price-label" }, "Средняя по рынку", -1))
                  ])
                ]),
                createBaseVNode("div", _hoisted_122, [
                  createBaseVNode("div", _hoisted_123, [
                    createBaseVNode("div", {
                      class: normalizeClass(["price-difference", $setup.getPriceDifferenceClass($props.analytics.price_comparison.price_difference_percent)])
                    }, [
                      createBaseVNode("div", _hoisted_124, toDisplayString(Math.abs($props.analytics.price_comparison.price_difference_percent)) + "% ", 1),
                      createBaseVNode("div", _hoisted_125, toDisplayString($props.analytics.price_comparison.price_difference_percent > 0 ? "Выше рынка" : "Ниже рынка"), 1)
                    ], 2)
                  ])
                ])
              ])
            ])
          ])) : createCommentVNode("", true)
        ])) : createCommentVNode("", true),
        $setup.activeTab === "recommendations" ? (openBlock(), createElementBlock("div", _hoisted_126, [
          createBaseVNode("div", _hoisted_127, [
            createBaseVNode("div", _hoisted_128, [
              _cache[88] || (_cache[88] = createBaseVNode("h5", { class: "mb-3" }, [
                createBaseVNode("i", { class: "fas fa-robot me-2 text-primary" }),
                createTextVNode(" Умные рекомендации для этой заявки ")
              ], -1)),
              createBaseVNode("div", _hoisted_129, [
                createBaseVNode("div", _hoisted_130, [
                  createBaseVNode("div", _hoisted_131, [
                    createBaseVNode("div", _hoisted_132, [
                      createBaseVNode("div", _hoisted_133, [
                        createBaseVNode("div", _hoisted_134, toDisplayString($setup.recommendationStats.total_recommendations || 0), 1),
                        _cache[76] || (_cache[76] = createBaseVNode("div", { class: "stat-label" }, "Всего рекомендаций", -1))
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_135, [
                      createBaseVNode("div", _hoisted_136, [
                        createBaseVNode("div", _hoisted_137, toDisplayString($setup.recommendationStats.application_rate || 0) + "%", 1),
                        _cache[77] || (_cache[77] = createBaseVNode("div", { class: "stat-label" }, "Применяемость", -1))
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_138, [
                      createBaseVNode("div", _hoisted_139, [
                        createBaseVNode("div", _hoisted_140, toDisplayString($setup.recommendationStats.conversion_rate || 0) + "%", 1),
                        _cache[78] || (_cache[78] = createBaseVNode("div", { class: "stat-label" }, "Конверсия", -1))
                      ])
                    ])
                  ])
                ])
              ]),
              $setup.recommendedTemplates.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_141, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($setup.recommendedTemplates, (recommendation, index) => {
                  return openBlock(), createElementBlock("div", {
                    key: recommendation.template.id,
                    class: normalizeClass(["recommendation-item card mb-3", "confidence-" + recommendation.confidence_level])
                  }, [
                    createBaseVNode("div", _hoisted_142, [
                      createBaseVNode("div", _hoisted_143, [
                        createBaseVNode("div", _hoisted_144, [
                          createBaseVNode("div", _hoisted_145, [
                            createBaseVNode("span", {
                              class: normalizeClass(["badge", $setup.getConfidenceBadgeClass(recommendation.confidence_level)])
                            }, " #" + toDisplayString(index + 1), 3)
                          ])
                        ]),
                        createBaseVNode("div", _hoisted_146, [
                          createBaseVNode("h6", _hoisted_147, toDisplayString(recommendation.template.name), 1),
                          createBaseVNode("p", _hoisted_148, toDisplayString(recommendation.reason), 1),
                          createBaseVNode("div", _hoisted_149, [
                            createBaseVNode("span", _hoisted_150, [
                              _cache[79] || (_cache[79] = createBaseVNode("i", { class: "fas fa-ruble-sign me-1" }, null, -1)),
                              createTextVNode(" " + toDisplayString($setup.formatCurrency(recommendation.template.proposed_price)) + "/час ", 1)
                            ]),
                            createBaseVNode("span", _hoisted_151, [
                              _cache[80] || (_cache[80] = createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1)),
                              createTextVNode(" " + toDisplayString(recommendation.template.response_time) + "ч ответ ", 1)
                            ]),
                            createBaseVNode("span", null, [
                              _cache[81] || (_cache[81] = createBaseVNode("i", { class: "fas fa-chart-line me-1" }, null, -1)),
                              createTextVNode(" " + toDisplayString(recommendation.template.success_rate || 0) + "% конверсия ", 1)
                            ])
                          ])
                        ]),
                        createBaseVNode("div", _hoisted_152, [
                          createBaseVNode("div", _hoisted_153, [
                            createBaseVNode("span", {
                              class: normalizeClass(["badge", $setup.getConfidenceBadgeClass(recommendation.confidence_level)])
                            }, toDisplayString(recommendation.confidence) + " (" + toDisplayString(recommendation.score) + "%) ", 3)
                          ]),
                          createBaseVNode("div", _hoisted_154, [
                            createBaseVNode("button", {
                              onClick: ($event) => $setup.applyRecommendedTemplate(recommendation),
                              class: "btn btn-sm btn-primary me-2"
                            }, [..._cache[82] || (_cache[82] = [
                              createBaseVNode("i", { class: "fas fa-bolt me-1" }, null, -1),
                              createTextVNode("Применить ", -1)
                            ])], 8, _hoisted_155),
                            createBaseVNode("button", {
                              onClick: ($event) => $setup.viewTemplateDetails(recommendation.template),
                              class: "btn btn-sm btn-outline-secondary"
                            }, [..._cache[83] || (_cache[83] = [
                              createBaseVNode("i", { class: "fas fa-eye" }, null, -1)
                            ])], 8, _hoisted_156)
                          ])
                        ])
                      ])
                    ])
                  ], 2);
                }), 128))
              ])) : (openBlock(), createElementBlock("div", _hoisted_157, [
                createBaseVNode("div", _hoisted_158, [
                  _cache[85] || (_cache[85] = createBaseVNode("i", { class: "fas fa-robot fa-3x text-muted mb-3" }, null, -1)),
                  _cache[86] || (_cache[86] = createBaseVNode("h5", null, "Рекомендации не найдены", -1)),
                  _cache[87] || (_cache[87] = createBaseVNode("p", { class: "text-muted" }, "Попробуйте создать шаблоны для категорий этой заявки", -1)),
                  createBaseVNode("button", {
                    class: "btn btn-primary",
                    onClick: _cache[9] || (_cache[9] = ($event) => $setup.activeTab = "templates")
                  }, [..._cache[84] || (_cache[84] = [
                    createBaseVNode("i", { class: "fas fa-file-alt me-2" }, null, -1),
                    createTextVNode("Перейти к шаблонам ", -1)
                  ])])
                ])
              ]))
            ]),
            createBaseVNode("div", _hoisted_159, [
              _cache[93] || (_cache[93] = createStaticVNode('<div class="algorithm-info card" data-v-697ac056><div class="card-header" data-v-697ac056><h6 class="card-title mb-0" data-v-697ac056>Как работают рекомендации?</h6></div><div class="card-body" data-v-697ac056><div class="algorithm-steps" data-v-697ac056><div class="step-item mb-3" data-v-697ac056><div class="step-icon bg-primary" data-v-697ac056><i class="fas fa-filter" data-v-697ac056></i></div><div class="step-content" data-v-697ac056><strong data-v-697ac056>Соответствие категории</strong><small class="text-muted" data-v-697ac056>Шаблоны подбираются по категориям заявки</small></div></div><div class="step-item mb-3" data-v-697ac056><div class="step-icon bg-success" data-v-697ac056><i class="fas fa-chart-line" data-v-697ac056></i></div><div class="step-content" data-v-697ac056><strong data-v-697ac056>Историческая успешность</strong><small class="text-muted" data-v-697ac056>Учитывается конверсия шаблонов</small></div></div><div class="step-item mb-3" data-v-697ac056><div class="step-icon bg-info" data-v-697ac056><i class="fas fa-ruble-sign" data-v-697ac056></i></div><div class="step-content" data-v-697ac056><strong data-v-697ac056>Соответствие бюджету</strong><small class="text-muted" data-v-697ac056>Цены сравниваются с бюджетом заявки</small></div></div><div class="step-item" data-v-697ac056><div class="step-icon bg-warning" data-v-697ac056><i class="fas fa-clock" data-v-697ac056></i></div><div class="step-content" data-v-697ac056><strong data-v-697ac056>Скорость ответа</strong><small class="text-muted" data-v-697ac056>Быстрые шаблоны получают бонус</small></div></div></div></div></div>', 1)),
              createBaseVNode("div", _hoisted_160, [
                createBaseVNode("div", _hoisted_161, [
                  _cache[92] || (_cache[92] = createBaseVNode("h6", { class: "card-title" }, "Улучшите рекомендации", -1)),
                  createBaseVNode("div", _hoisted_162, [
                    createBaseVNode("button", {
                      class: "btn btn-outline-primary btn-sm w-100 mb-2",
                      onClick: _cache[10] || (_cache[10] = (...args) => $setup.loadTemplateRecommendations && $setup.loadTemplateRecommendations(...args))
                    }, [..._cache[89] || (_cache[89] = [
                      createBaseVNode("i", { class: "fas fa-refresh me-1" }, null, -1),
                      createTextVNode("Обновить рекомендации ", -1)
                    ])]),
                    createBaseVNode("button", {
                      class: "btn btn-outline-success btn-sm w-100 mb-2",
                      onClick: _cache[11] || (_cache[11] = ($event) => $setup.activeTab = "templates")
                    }, [..._cache[90] || (_cache[90] = [
                      createBaseVNode("i", { class: "fas fa-plus me-1" }, null, -1),
                      createTextVNode("Создать шаблон ", -1)
                    ])]),
                    createBaseVNode("button", {
                      class: "btn btn-outline-info btn-sm w-100",
                      onClick: _cache[12] || (_cache[12] = (...args) => $setup.viewRecommendationStats && $setup.viewRecommendationStats(...args))
                    }, [..._cache[91] || (_cache[91] = [
                      createBaseVNode("i", { class: "fas fa-chart-bar me-1" }, null, -1),
                      createTextVNode("Статистика рекомендаций ", -1)
                    ])])
                  ])
                ])
              ])
            ])
          ])
        ])) : createCommentVNode("", true)
      ])
    ]),
    $setup.showProposalModal ? (openBlock(), createElementBlock("div", {
      key: 2,
      class: normalizeClass(["modal fade", { "show d-block": $setup.showProposalModal }]),
      style: { "background": "rgba(0,0,0,0.5)" }
    }, [
      createBaseVNode("div", _hoisted_163, [
        createBaseVNode("div", _hoisted_164, [
          createBaseVNode("div", _hoisted_165, [
            _cache[94] || (_cache[94] = createBaseVNode("h5", { class: "modal-title" }, [
              createBaseVNode("i", { class: "fas fa-paper-plane me-2" }),
              createTextVNode(" Предложить технику ")
            ], -1)),
            createBaseVNode("button", {
              type: "button",
              class: "btn-close",
              onClick: _cache[13] || (_cache[13] = (...args) => $setup.closeProposalModal && $setup.closeProposalModal(...args))
            })
          ]),
          createBaseVNode("div", _hoisted_166, [
            $setup.apiError ? (openBlock(), createElementBlock("div", _hoisted_167, [
              _cache[95] || (_cache[95] = createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1)),
              createTextVNode(" " + toDisplayString($setup.apiError), 1)
            ])) : createCommentVNode("", true),
            $setup.loadingEquipment ? (openBlock(), createElementBlock("div", _hoisted_168, [..._cache[96] || (_cache[96] = [
              createBaseVNode("div", {
                class: "spinner-border text-primary",
                role: "status"
              }, [
                createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
              ], -1),
              createBaseVNode("p", { class: "mt-2 text-muted" }, "Проверка доступности оборудования...", -1)
            ])])) : (openBlock(), createElementBlock("div", _hoisted_169, [
              _cache[108] || (_cache[108] = createBaseVNode("div", { class: "alert alert-info" }, [
                createBaseVNode("i", { class: "fas fa-info-circle me-2" }),
                createTextVNode(" Заполните форму для отправки предложения арендатору ")
              ], -1)),
              createBaseVNode("div", _hoisted_170, [
                _cache[99] || (_cache[99] = createBaseVNode("div", { class: "card-header bg-light" }, [
                  createBaseVNode("h6", { class: "mb-0" }, "Требования заявки")
                ], -1)),
                createBaseVNode("div", _hoisted_171, [
                  createBaseVNode("div", _hoisted_172, [
                    createBaseVNode("div", _hoisted_173, [
                      _cache[97] || (_cache[97] = createBaseVNode("small", { class: "text-muted" }, "Категории:", -1)),
                      createBaseVNode("div", null, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList($props.request.items, (item) => {
                          var _a2;
                          return openBlock(), createElementBlock("span", {
                            key: item.id,
                            class: "badge bg-primary me-1"
                          }, toDisplayString((_a2 = item.category) == null ? void 0 : _a2.name), 1);
                        }), 128))
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_174, [
                      _cache[98] || (_cache[98] = createBaseVNode("small", { class: "text-muted" }, "Период:", -1)),
                      createBaseVNode("div", null, toDisplayString($setup.formatDate($props.request.rental_period_start)) + " - " + toDisplayString($setup.formatDate($props.request.rental_period_end)), 1)
                    ])
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_175, [
                _cache[102] || (_cache[102] = createBaseVNode("label", { class: "form-label" }, "Выберите технику *", -1)),
                withDirectives(createBaseVNode("select", {
                  class: normalizeClass(["form-select", { "is-invalid": $setup.fieldErrors.equipment_id }]),
                  "onUpdate:modelValue": _cache[14] || (_cache[14] = ($event) => $setup.proposalForm.equipment_id = $event),
                  disabled: $setup.availableEquipment.length === 0
                }, [
                  _cache[100] || (_cache[100] = createBaseVNode("option", { value: "" }, "Выберите технику из вашего каталога", -1)),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($setup.availableEquipment, (equipment) => {
                    return openBlock(), createElementBlock("option", {
                      key: equipment.id,
                      value: equipment.id
                    }, [
                      createTextVNode(toDisplayString(equipment.title) + " " + toDisplayString(equipment.model ? `(${equipment.model})` : "") + " - " + toDisplayString($setup.formatCurrency(equipment.hourly_rate || 0)) + "/час ", 1),
                      equipment.availability_status ? (openBlock(), createElementBlock("span", {
                        key: 0,
                        class: normalizeClass(["badge ms-1", $setup.getAvailabilityBadgeClass(equipment.availability_status)])
                      }, toDisplayString($setup.getAvailabilityStatusText(equipment.availability_status)), 3)) : createCommentVNode("", true)
                    ], 8, _hoisted_177);
                  }), 128))
                ], 10, _hoisted_176), [
                  [vModelSelect, $setup.proposalForm.equipment_id]
                ]),
                $setup.fieldErrors.equipment_id ? (openBlock(), createElementBlock("div", _hoisted_178, toDisplayString($setup.fieldErrors.equipment_id[0]), 1)) : createCommentVNode("", true),
                $setup.availableEquipment.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_179, [..._cache[101] || (_cache[101] = [
                  createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1),
                  createBaseVNode("strong", null, "У вас нет доступного оборудования для этой заявки", -1),
                  createBaseVNode("div", { class: "mt-1 small" }, [
                    createTextVNode(" Возможные причины: "),
                    createBaseVNode("ul", { class: "mb-0" }, [
                      createBaseVNode("li", null, "Оборудование не соответствует категориям заявки"),
                      createBaseVNode("li", null, "Оборудование занято в указанный период"),
                      createBaseVNode("li", null, "Оборудование находится на обслуживании"),
                      createBaseVNode("li", null, "Локация оборудования не подходит для доставки")
                    ])
                  ], -1)
                ])])) : (openBlock(), createElementBlock("div", _hoisted_180, " Найдено " + toDisplayString($setup.availableEquipment.length) + " единиц техники, подходящих для заявки ", 1))
              ]),
              createBaseVNode("div", _hoisted_181, [
                createBaseVNode("div", _hoisted_182, [
                  _cache[103] || (_cache[103] = createBaseVNode("label", { class: "form-label" }, "Предлагаемая цена (₽/час) *", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: "number",
                    class: normalizeClass(["form-control", { "is-invalid": $setup.fieldErrors.proposed_price }]),
                    "onUpdate:modelValue": _cache[15] || (_cache[15] = ($event) => $setup.proposalForm.proposed_price = $event),
                    min: "0"
                  }, null, 2), [
                    [vModelText, $setup.proposalForm.proposed_price]
                  ]),
                  $setup.fieldErrors.proposed_price ? (openBlock(), createElementBlock("div", _hoisted_183, toDisplayString($setup.fieldErrors.proposed_price[0]), 1)) : createCommentVNode("", true)
                ]),
                createBaseVNode("div", _hoisted_184, [
                  _cache[104] || (_cache[104] = createBaseVNode("label", { class: "form-label" }, "Количество *", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: "number",
                    class: normalizeClass(["form-control", { "is-invalid": $setup.fieldErrors.quantity }]),
                    "onUpdate:modelValue": _cache[16] || (_cache[16] = ($event) => $setup.proposalForm.quantity = $event),
                    min: "1",
                    max: "10",
                    value: "1"
                  }, null, 2), [
                    [vModelText, $setup.proposalForm.quantity]
                  ]),
                  $setup.fieldErrors.quantity ? (openBlock(), createElementBlock("div", _hoisted_185, toDisplayString($setup.fieldErrors.quantity[0]), 1)) : createCommentVNode("", true)
                ])
              ]),
              createBaseVNode("div", _hoisted_186, [
                createBaseVNode("div", _hoisted_187, [
                  _cache[105] || (_cache[105] = createBaseVNode("label", { class: "form-label" }, "Время ответа (часы)", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: "number",
                    class: "form-control",
                    "onUpdate:modelValue": _cache[17] || (_cache[17] = ($event) => $setup.proposalForm.response_time = $event),
                    min: "1",
                    max: "168",
                    value: "24"
                  }, null, 512), [
                    [vModelText, $setup.proposalForm.response_time]
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_188, [
                _cache[106] || (_cache[106] = createBaseVNode("label", { class: "form-label" }, "Сообщение для арендатора *", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: normalizeClass(["form-control", { "is-invalid": $setup.fieldErrors.message }]),
                  rows: "4",
                  "onUpdate:modelValue": _cache[18] || (_cache[18] = ($event) => $setup.proposalForm.message = $event),
                  placeholder: "Опишите ваше предложение, условия доставки, доступность техники..."
                }, null, 2), [
                  [vModelText, $setup.proposalForm.message]
                ]),
                $setup.fieldErrors.message ? (openBlock(), createElementBlock("div", _hoisted_189, toDisplayString($setup.fieldErrors.message[0]), 1)) : createCommentVNode("", true)
              ]),
              createBaseVNode("div", _hoisted_190, [
                _cache[107] || (_cache[107] = createBaseVNode("label", { class: "form-label" }, "Дополнительные условия", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: "form-control",
                  rows: "3",
                  "onUpdate:modelValue": _cache[19] || (_cache[19] = ($event) => $setup.proposalForm.additional_terms = $event),
                  placeholder: "Минимальный срок аренды, условия оплаты, гарантии..."
                }, null, 512), [
                  [vModelText, $setup.proposalForm.additional_terms]
                ])
              ])
            ]))
          ]),
          createBaseVNode("div", _hoisted_191, [
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-secondary",
              onClick: _cache[20] || (_cache[20] = (...args) => $setup.closeProposalModal && $setup.closeProposalModal(...args))
            }, "Отмена"),
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-primary",
              onClick: _cache[21] || (_cache[21] = (...args) => $setup.submitProposal && $setup.submitProposal(...args)),
              disabled: $setup.sendingProposal || $setup.availableEquipment.length === 0
            }, [
              $setup.sendingProposal ? (openBlock(), createElementBlock("span", _hoisted_193)) : createCommentVNode("", true),
              createTextVNode(" " + toDisplayString($setup.sendingProposal ? "Отправка..." : "Отправить предложение"), 1)
            ], 8, _hoisted_192)
          ])
        ])
      ])
    ], 2)) : createCommentVNode("", true)
  ]);
}
const RentalRequestDetail = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-697ac056"]]);
export {
  RentalRequestDetail as default
};
