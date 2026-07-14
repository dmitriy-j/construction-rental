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
import { k as ref, p as computed, l as watch, m as onMounted, a as createElementBlock, o as openBlock, b as createBaseVNode, e as createCommentVNode, d as createTextVNode, n as normalizeClass, F as Fragment, r as renderList, t as toDisplayString, w as withDirectives, v as vModelSelect, j as vModelText, q as withKeys, s as vModelCheckbox } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  name: "ProposalTemplates",
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
  emits: ["template-applied"],
  setup(props, { emit }) {
    console.log("🔄 ProposalTemplates setup started");
    console.log("📦 Получены категории из props:", props.categories);
    const templates = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    const showCreateModal = ref(false);
    const showQuickApplyModal = ref(false);
    const showAbStatsModal = ref(false);
    const editingTemplate = ref(null);
    const selectedTemplate = ref(null);
    const abTestStats = ref(null);
    const filters = ref({
      category_id: "",
      status: "",
      search: "",
      ab_test: ""
    });
    const form = ref({
      name: "",
      description: "",
      category_id: "",
      proposed_price: "",
      response_time: 24,
      message: "",
      additional_terms: "",
      is_active: true,
      // 🔥 Новые поля для A/B тестирования
      is_ab_test: false,
      ab_test_variants: [],
      test_distribution: "50-50",
      test_metric: "conversion"
    });
    const activeAbTests = computed(() => {
      return templates.value.filter(
        (template) => template.is_ab_test && template.ab_test_status === "active"
      );
    });
    const availableCategories = computed(() => {
      console.log("📋 Доступные категории:", props.categories);
      return props.categories || [];
    });
    const computedStats = computed(() => {
      const totalTemplates = templates.value.length;
      const totalUsage = templates.value.reduce((sum, template) => sum + (template.usage_count || 0), 0);
      const activeAbTestsCount = activeAbTests.value.length;
      const templatesWithUsage = templates.value.filter((t) => t.usage_count > 0);
      const averageSuccessRate = templatesWithUsage.length > 0 ? templatesWithUsage.reduce((sum, template) => sum + (template.success_rate || 0), 0) / templatesWithUsage.length : 0;
      const timeSaved = totalUsage * 0.5;
      return {
        total_templates: totalTemplates,
        total_usage: totalUsage,
        average_success_rate: Math.round(averageSuccessRate * 10) / 10,
        time_saved: timeSaved,
        active_ab_tests: activeAbTestsCount
      };
    });
    const statsCards = computed(() => [
      {
        title: "Всего шаблонов",
        value: computedStats.value.total_templates || 0,
        icon: "fas fa-file-alt",
        color: "text-primary"
      },
      {
        title: "Средняя успешность",
        value: `${computedStats.value.average_success_rate || 0}%`,
        icon: "fas fa-chart-line",
        color: "text-success"
      },
      {
        title: "Всего применений",
        value: computedStats.value.total_usage || 0,
        icon: "fas fa-bolt",
        color: "text-warning"
      },
      {
        title: "A/B тесты",
        value: computedStats.value.active_ab_tests || 0,
        icon: "fas fa-flask",
        color: "text-info"
      }
    ]);
    const addVariant = () => {
      if (form.value.ab_test_variants.length < 4) {
        form.value.ab_test_variants.push({
          name: `Вариант ${String.fromCharCode(65 + form.value.ab_test_variants.length)}`,
          message: form.value.message,
          proposed_price: form.value.proposed_price,
          additional_terms: form.value.additional_terms,
          response_time: form.value.response_time
        });
      }
    };
    const removeVariant = (index) => {
      if (form.value.ab_test_variants.length > 2) {
        form.value.ab_test_variants.splice(index, 1);
      }
    };
    const startAbTest = (template) => __async(null, null, function* () {
      var _a, _b, _c;
      if (confirm(`Запустить A/B тест для шаблона "${template.name}"?`)) {
        try {
          console.log("🚀 Запуск A/B теста для шаблона:", template.id);
          const response = yield axios.post(`/api/lessor/proposal-templates/${template.id}/start-ab-test`);
          if (response.data.success) {
            alert("✅ A/B тест успешно запущен!");
            yield loadTemplates();
          } else {
            alert("❌ Ошибка: " + response.data.message);
          }
        } catch (error) {
          console.error("❌ Ошибка запуска A/B теста:", error);
          console.error("📊 Ответ сервера:", (_a = error.response) == null ? void 0 : _a.data);
          let errorMessage = "Неизвестная ошибка";
          if ((_c = (_b = error.response) == null ? void 0 : _b.data) == null ? void 0 : _c.message) {
            errorMessage = error.response.data.message;
          } else if (error.message) {
            errorMessage = error.message;
          }
          alert("❌ Ошибка запуска A/B теста: " + errorMessage);
        }
      }
    });
    const stopAbTest = (template) => __async(null, null, function* () {
      var _a, _b;
      if (confirm(`Остановить A/B тест для шаблона "${template.name}"?`)) {
        try {
          const response = yield axios.post(`/api/lessor/proposal-templates/${template.id}/stop-ab-test`);
          yield loadTemplates();
          showAbStatsModal.value = false;
          alert("✅ A/B тест остановлен!");
        } catch (error) {
          console.error("❌ Ошибка остановки A/B теста:", error);
          alert("❌ Ошибка остановки A/B теста: " + (((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) || error.message));
        }
      }
    });
    const viewAbTestStats = (template) => __async(null, null, function* () {
      var _a, _b;
      selectedTemplate.value = template;
      showAbStatsModal.value = true;
      try {
        const response = yield axios.get(`/api/lessor/proposal-templates/${template.id}/ab-test-stats`);
        abTestStats.value = response.data.data;
      } catch (error) {
        console.error("❌ Ошибка загрузки статистики A/B теста:", error);
        alert("❌ Ошибка загрузки статистики: " + (((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) || error.message));
      }
    });
    const declareWinner = (variantIndex) => __async(null, null, function* () {
      var _a, _b;
      if (confirm(`Выбрать этот вариант победителем A/B теста?`)) {
        try {
          const response = yield axios.post(`/api/lessor/proposal-templates/${selectedTemplate.value.id}/declare-winner`, {
            winner_index: variantIndex
          });
          yield loadTemplates();
          showAbStatsModal.value = false;
          alert("✅ Победитель A/B теста выбран! Шаблон обновлен.");
        } catch (error) {
          console.error("❌ Ошибка выбора победителя:", error);
          alert("❌ Ошибка выбора победителя: " + (((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) || error.message));
        }
      }
    });
    const getTestDuration = (template) => {
      if (!template.ab_test_started_at) return "0 дней";
      const start = new Date(template.ab_test_started_at);
      const now = /* @__PURE__ */ new Date();
      const diffTime = Math.abs(now - start);
      const diffDays = Math.ceil(diffTime / (1e3 * 60 * 60 * 24));
      return `${diffDays} дней`;
    };
    const getConversionRateClass = (rate) => {
      if (rate >= 30) return "text-success fw-bold";
      if (rate >= 15) return "text-warning";
      return "text-danger";
    };
    const getRecommendationClass = (recommendation) => {
      if (recommendation == null ? void 0 : recommendation.includes("продолжить")) return "alert-warning";
      if (recommendation == null ? void 0 : recommendation.includes("остановить")) return "alert-success";
      return "alert-info";
    };
    const saveTemplate = () => __async(null, null, function* () {
      var _a, _b, _c, _d, _e, _f, _g, _h, _i;
      if (form.value.is_ab_test) {
        if (!form.value.ab_test_variants || form.value.ab_test_variants.length < 2) {
          alert("Для A/B теста необходимо как минимум 2 варианта");
          return;
        }
        for (let i = 0; i < form.value.ab_test_variants.length; i++) {
          const variant = form.value.ab_test_variants[i];
          if (!((_a = variant.name) == null ? void 0 : _a.trim()) || !((_b = variant.message) == null ? void 0 : _b.trim()) || !variant.proposed_price) {
            alert(`Заполните все поля для варианта ${String.fromCharCode(65 + i)}`);
            return;
          }
        }
      }
      if (!((_c = form.value.name) == null ? void 0 : _c.trim())) {
        alert("Пожалуйста, введите название шаблона");
        return;
      }
      if (!form.value.category_id) {
        alert("Пожалуйста, выберите категорию");
        return;
      }
      if (!form.value.proposed_price || form.value.proposed_price <= 0) {
        alert("Пожалуйста, укажите корректную цену (больше 0)");
        return;
      }
      if (!((_d = form.value.message) == null ? void 0 : _d.trim())) {
        alert("Пожалуйста, введите текст сообщения");
        return;
      }
      saving.value = true;
      try {
        console.log("💾 Начало сохранения шаблона...");
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
        };
        console.log("📋 Данные для отправки:", JSON.stringify(formData, null, 2));
        let response;
        if (editingTemplate.value) {
          console.log("📝 Обновление шаблона:", editingTemplate.value.id);
          response = yield axios.put(`/api/lessor/proposal-templates/${editingTemplate.value.id}`, formData);
          console.log("✅ Шаблон успешно обновлен:", response.data);
        } else {
          console.log("🆕 Создание нового шаблона");
          response = yield axios.post("/api/lessor/proposal-templates", formData);
          console.log("✅ Шаблон успешно создан:", response.data);
        }
        closeModal();
        yield loadTemplates();
        alert("✅ Шаблон успешно сохранен!");
      } catch (error) {
        console.error("❌ ПОЛНАЯ ОШИБКА СОХРАНЕНИЯ ШАБЛОНА:", error);
        let errorMessage = "Неизвестная ошибка при сохранении шаблона";
        if ((_f = (_e = error.response) == null ? void 0 : _e.data) == null ? void 0 : _f.message) {
          errorMessage = error.response.data.message;
        } else if ((_h = (_g = error.response) == null ? void 0 : _g.data) == null ? void 0 : _h.errors) {
          const validationErrors = Object.values(error.response.data.errors).flat();
          errorMessage = "Ошибки валидации: " + validationErrors.join(", ");
        } else if (error.code === "NETWORK_ERROR") {
          errorMessage = "Ошибка сети. Проверьте подключение к интернету.";
        } else if (((_i = error.response) == null ? void 0 : _i.status) === 422) {
          errorMessage = "Ошибка валидации данных. Проверьте правильность заполнения полей.";
        } else {
          errorMessage = error.message || "Неизвестная ошибка";
        }
        alert(`❌ Ошибка сохранения шаблона: ${errorMessage}`);
      } finally {
        saving.value = false;
      }
    });
    const editTemplate = (template) => {
      console.log("✏️ Редактирование шаблона:", template);
      editingTemplate.value = template;
      form.value = __spreadProps(__spreadValues({}, template), {
        ab_test_variants: template.ab_test_variants || []
      });
      if (form.value.is_ab_test && (!form.value.ab_test_variants || form.value.ab_test_variants.length === 0)) {
        form.value.ab_test_variants = [
          {
            name: "Вариант A",
            message: template.message,
            proposed_price: template.proposed_price,
            additional_terms: template.additional_terms,
            response_time: template.response_time
          },
          {
            name: "Вариант B",
            message: template.message,
            proposed_price: template.proposed_price * 0.9,
            // -10%
            additional_terms: template.additional_terms,
            response_time: template.response_time
          }
        ];
      }
      showCreateModal.value = true;
    };
    const closeModal = () => {
      console.log("🚪 Закрытие модального окна");
      showCreateModal.value = false;
      editingTemplate.value = null;
      form.value = {
        name: "",
        description: "",
        category_id: "",
        proposed_price: "",
        response_time: 24,
        message: "",
        additional_terms: "",
        is_active: true,
        is_ab_test: false,
        ab_test_variants: [],
        test_distribution: "50-50",
        test_metric: "conversion"
      };
    };
    const getCategoryName = (categoryId) => {
      if (!categoryId) return "Без категории";
      const category = availableCategories.value.find((cat) => cat.id === categoryId);
      return (category == null ? void 0 : category.name) || "Категория не найдена";
    };
    const formatCurrency = (amount) => {
      if (!amount && amount !== 0) return "0 ₽";
      try {
        return new Intl.NumberFormat("ru-RU", {
          minimumFractionDigits: 0,
          maximumFractionDigits: 2
        }).format(amount) + " ₽";
      } catch (error) {
        console.error("Ошибка форматирования валюты:", error);
        return "0 ₽";
      }
    };
    const loadTemplates = () => __async(null, null, function* () {
      loading.value = true;
      try {
        console.log("📥 Загрузка шаблонов с фильтрами:", filters.value);
        const response = yield axios.get("/api/lessor/proposal-templates", {
          params: filters.value
        });
        console.log("✅ Шаблоны загружены:", response.data.data.map((t) => ({
          id: t.id,
          name: t.name,
          is_ab_test: t.is_ab_test,
          ab_test_variants: t.ab_test_variants,
          variants_count: t.ab_test_variants ? t.ab_test_variants.length : 0
        })));
        templates.value = response.data.data || [];
      } catch (error) {
        console.error("❌ Ошибка загрузки шаблонов:", error);
        alert("Ошибка загрузки шаблонов: " + error.message);
      } finally {
        loading.value = false;
      }
    });
    const duplicateTemplate = (template) => __async(null, null, function* () {
      var _a, _b;
      try {
        console.log("📋 Дублирование шаблона:", template.id);
        const response = yield axios.post("/api/lessor/proposal-templates", __spreadProps(__spreadValues({}, template), {
          name: `${template.name} (копия)`,
          usage_count: 0,
          success_rate: 0,
          is_ab_test: false,
          // 🔥 Сбрасываем A/B тест при дублировании
          ab_test_variants: []
        }));
        yield loadTemplates();
        alert("✅ Шаблон успешно дублирован!");
      } catch (error) {
        console.error("❌ Ошибка дублирования шаблона:", error);
        alert("❌ Ошибка дублирования шаблона: " + (((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) || error.message));
      }
    });
    const deleteTemplate = (template) => __async(null, null, function* () {
      var _a, _b;
      if (confirm(`Удалить шаблон "${template.name}"?`)) {
        try {
          console.log("🗑️ Удаление шаблона:", template.id);
          yield axios.delete(`/api/lessor/proposal-templates/${template.id}`);
          yield loadTemplates();
          alert("✅ Шаблон успешно удален!");
        } catch (error) {
          console.error("❌ Ошибка удаления шаблона:", error);
          alert("❌ Ошибка удаления шаблона: " + (((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) || error.message));
        }
      }
    });
    const updateTemplateStatus = (template) => __async(null, null, function* () {
      var _a, _b;
      try {
        console.log("🔄 Обновление статуса шаблона:", template.id, "новый статус:", template.is_active);
        yield axios.put(`/api/lessor/proposal-templates/${template.id}`, {
          is_active: template.is_active
        });
        alert("✅ Статус шаблона обновлен!");
      } catch (error) {
        console.error("❌ Ошибка обновления статуса:", error);
        template.is_active = !template.is_active;
        alert("❌ Ошибка обновления статуса: " + (((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) || error.message));
      }
    });
    const quickApply = (template) => {
      console.log("⚡ Быстрое применение шаблона:", template.id);
      selectedTemplate.value = template;
      showQuickApplyModal.value = true;
    };
    const confirmQuickApply = () => __async(null, null, function* () {
      try {
        console.log("✅ Подтверждение быстрого применения:", selectedTemplate.value);
        emit("template-applied", {
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
        });
        showQuickApplyModal.value = false;
        alert("✅ Шаблон успешно применен!");
      } catch (error) {
        console.error("❌ Ошибка применения шаблона:", error);
        alert("❌ Ошибка применения шаблона: " + error.message);
      }
    });
    const truncateMessage = (message) => {
      if (!message) return "Текст сообщения не указан";
      return message.length > 150 ? message.substring(0, 150) + "..." : message;
    };
    const getSuccessRateClass = (rate) => {
      if (rate >= 70) return "text-success";
      if (rate >= 40) return "text-warning";
      return "text-danger";
    };
    watch(showCreateModal, (newVal) => {
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
    watch(showQuickApplyModal, (newVal) => {
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
    watch(showAbStatsModal, (newVal) => {
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
    watch(() => form.value.is_ab_test, (newVal) => {
      if (newVal && (!form.value.ab_test_variants || form.value.ab_test_variants.length === 0)) {
        form.value.ab_test_variants = [
          {
            name: "Вариант A",
            message: form.value.message,
            proposed_price: form.value.proposed_price,
            additional_terms: form.value.additional_terms,
            response_time: form.value.response_time
          },
          {
            name: "Вариант B",
            message: form.value.message,
            proposed_price: form.value.proposed_price * 0.9,
            additional_terms: form.value.additional_terms,
            response_time: form.value.response_time
          }
        ];
      }
    });
    onMounted(() => {
      console.log("✅ ProposalTemplates mounted successfully");
      loadTemplates();
    });
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
    };
  }
};
const _hoisted_1 = { class: "proposal-templates" };
const _hoisted_2 = { class: "row mb-4" };
const _hoisted_3 = { class: "col-12" };
const _hoisted_4 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_5 = ["disabled"];
const _hoisted_6 = { class: "row mt-3" };
const _hoisted_7 = { class: "card stat-card h-100" };
const _hoisted_8 = { class: "card-body text-center" };
const _hoisted_9 = { class: "card-title mb-1" };
const _hoisted_10 = { class: "card-text small text-muted" };
const _hoisted_11 = {
  key: 0,
  class: "row mb-4"
};
const _hoisted_12 = { class: "col-12" };
const _hoisted_13 = { class: "card" };
const _hoisted_14 = { class: "card-body" };
const _hoisted_15 = { class: "row" };
const _hoisted_16 = { class: "ab-test-card p-3 border rounded" };
const _hoisted_17 = { class: "d-flex justify-content-between align-items-start mb-2" };
const _hoisted_18 = { class: "mb-0" };
const _hoisted_19 = { class: "ab-test-progress mb-2" };
const _hoisted_20 = { class: "d-flex justify-content-between small text-muted mb-1" };
const _hoisted_21 = { class: "btn-group btn-group-sm" };
const _hoisted_22 = ["onClick"];
const _hoisted_23 = ["onClick"];
const _hoisted_24 = { class: "card mb-4" };
const _hoisted_25 = { class: "card-body" };
const _hoisted_26 = { class: "row g-3" };
const _hoisted_27 = { class: "col-md-3" };
const _hoisted_28 = ["value"];
const _hoisted_29 = { class: "col-md-3" };
const _hoisted_30 = { class: "col-md-3" };
const _hoisted_31 = { class: "col-md-3" };
const _hoisted_32 = { class: "input-group" };
const _hoisted_33 = {
  key: 1,
  class: "row"
};
const _hoisted_34 = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_35 = { class: "mb-0" };
const _hoisted_36 = { class: "d-flex align-items-center" };
const _hoisted_37 = {
  key: 0,
  class: "badge bg-success me-2"
};
const _hoisted_38 = { class: "form-check form-switch" };
const _hoisted_39 = ["onUpdate:modelValue", "onChange"];
const _hoisted_40 = { class: "card-body" };
const _hoisted_41 = { class: "mb-2" };
const _hoisted_42 = { class: "badge bg-secondary" };
const _hoisted_43 = {
  key: 0,
  class: "badge bg-warning ms-1"
};
const _hoisted_44 = {
  key: 1,
  class: "badge bg-info ms-1"
};
const _hoisted_45 = {
  key: 0,
  class: "card-text text-muted small"
};
const _hoisted_46 = { class: "template-info mb-3" };
const _hoisted_47 = { class: "price-info" };
const _hoisted_48 = { class: "text-primary" };
const _hoisted_49 = { class: "text-muted d-block" };
const _hoisted_50 = { class: "template-stats" };
const _hoisted_51 = { class: "stat-item" };
const _hoisted_52 = { class: "stat-item" };
const _hoisted_53 = {
  key: 1,
  class: "ab-variants-preview mt-3"
};
const _hoisted_54 = { class: "variant-previews" };
const _hoisted_55 = { class: "ms-2" };
const _hoisted_56 = {
  key: 0,
  class: "text-muted small"
};
const _hoisted_57 = { class: "message-preview mt-3 p-2 bg-light rounded small" };
const _hoisted_58 = { class: "card-footer bg-transparent" };
const _hoisted_59 = { class: "btn-group w-100" };
const _hoisted_60 = ["onClick"];
const _hoisted_61 = ["onClick"];
const _hoisted_62 = ["onClick"];
const _hoisted_63 = ["onClick"];
const _hoisted_64 = ["onClick"];
const _hoisted_65 = ["onClick"];
const _hoisted_66 = {
  key: 2,
  class: "text-center py-5"
};
const _hoisted_67 = { class: "empty-state" };
const _hoisted_68 = { class: "content-modal-wrapper" };
const _hoisted_69 = { class: "modal-dialog modal-lg modal-dialog-centered" };
const _hoisted_70 = { class: "modal-content" };
const _hoisted_71 = { class: "modal-header" };
const _hoisted_72 = { class: "modal-title" };
const _hoisted_73 = {
  class: "modal-body",
  style: { "max-height": "70vh", "overflow-y": "auto" }
};
const _hoisted_74 = {
  key: 0,
  class: "alert alert-warning mb-3"
};
const _hoisted_75 = { class: "row" };
const _hoisted_76 = { class: "col-md-6" };
const _hoisted_77 = { class: "mb-3" };
const _hoisted_78 = { class: "col-md-6" };
const _hoisted_79 = { class: "mb-3" };
const _hoisted_80 = ["value"];
const _hoisted_81 = { class: "mb-3" };
const _hoisted_82 = { class: "row" };
const _hoisted_83 = { class: "col-md-6" };
const _hoisted_84 = { class: "mb-3" };
const _hoisted_85 = { class: "col-md-6" };
const _hoisted_86 = { class: "mb-3" };
const _hoisted_87 = { class: "mb-3" };
const _hoisted_88 = { class: "mb-3" };
const _hoisted_89 = { class: "ab-test-section border-top pt-3 mt-3" };
const _hoisted_90 = { class: "form-check form-switch mb-3" };
const _hoisted_91 = {
  key: 0,
  class: "ab-test-config bg-light p-3 rounded"
};
const _hoisted_92 = { class: "row mb-3" };
const _hoisted_93 = { class: "col-md-6" };
const _hoisted_94 = { class: "col-md-6" };
const _hoisted_95 = { class: "variants-section" };
const _hoisted_96 = { class: "variant-list" };
const _hoisted_97 = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_98 = { class: "mb-0" };
const _hoisted_99 = ["onClick", "disabled"];
const _hoisted_100 = { class: "card-body" };
const _hoisted_101 = { class: "row g-2" };
const _hoisted_102 = { class: "col-md-6" };
const _hoisted_103 = ["onUpdate:modelValue"];
const _hoisted_104 = { class: "col-md-6" };
const _hoisted_105 = ["onUpdate:modelValue"];
const _hoisted_106 = { class: "col-12" };
const _hoisted_107 = ["onUpdate:modelValue"];
const _hoisted_108 = { class: "col-12" };
const _hoisted_109 = ["onUpdate:modelValue"];
const _hoisted_110 = ["disabled"];
const _hoisted_111 = { class: "form-check mt-3" };
const _hoisted_112 = { class: "modal-footer" };
const _hoisted_113 = ["disabled"];
const _hoisted_114 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1"
};
const _hoisted_115 = { class: "modal-dialog modal-dialog-centered" };
const _hoisted_116 = { class: "modal-content" };
const _hoisted_117 = { class: "modal-header" };
const _hoisted_118 = { class: "modal-body" };
const _hoisted_119 = { class: "text-muted small" };
const _hoisted_120 = {
  key: 0,
  class: "alert alert-warning small"
};
const _hoisted_121 = { class: "modal-footer" };
const _hoisted_122 = { class: "modal-dialog modal-xl modal-dialog-centered" };
const _hoisted_123 = { class: "modal-content" };
const _hoisted_124 = { class: "modal-header" };
const _hoisted_125 = { class: "modal-body" };
const _hoisted_126 = {
  key: 0,
  class: "ab-test-stats"
};
const _hoisted_127 = { class: "row mb-4" };
const _hoisted_128 = { class: "col-md-6" };
const _hoisted_129 = { class: "text-muted small mb-0" };
const _hoisted_130 = { class: "col-md-6 text-end" };
const _hoisted_131 = { class: "badge bg-success me-2" };
const _hoisted_132 = { class: "table-responsive" };
const _hoisted_133 = { class: "table table-striped" };
const _hoisted_134 = {
  key: 0,
  class: "badge bg-success ms-2"
};
const _hoisted_135 = ["onClick"];
const _hoisted_136 = { class: "row mt-4" };
const _hoisted_137 = { class: "col-md-6" };
const _hoisted_138 = { class: "metrics-grid" };
const _hoisted_139 = { class: "metric-item" };
const _hoisted_140 = { class: "metric-value" };
const _hoisted_141 = { class: "metric-item" };
const _hoisted_142 = { class: "metric-value" };
const _hoisted_143 = { class: "metric-item" };
const _hoisted_144 = { class: "metric-value" };
const _hoisted_145 = { class: "col-md-6" };
const _hoisted_146 = {
  key: 1,
  class: "text-center py-4"
};
const _hoisted_147 = { class: "modal-footer" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _a, _b, _c, _d;
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createBaseVNode("div", _hoisted_4, [
          _cache[34] || (_cache[34] = createBaseVNode("h2", { class: "mb-0" }, [
            createBaseVNode("i", { class: "fas fa-file-alt me-2" }),
            createTextVNode("Шаблоны предложений ")
          ], -1)),
          createBaseVNode("div", null, [
            createBaseVNode("button", {
              class: "btn btn-outline-secondary me-2",
              onClick: _cache[0] || (_cache[0] = (...args) => $setup.loadTemplates && $setup.loadTemplates(...args)),
              disabled: $setup.loading
            }, [
              createBaseVNode("i", {
                class: normalizeClass(["fas fa-refresh", { "fa-spin": $setup.loading }])
              }, null, 2)
            ], 8, _hoisted_5),
            createBaseVNode("button", {
              class: "btn btn-primary",
              onClick: _cache[1] || (_cache[1] = ($event) => $setup.showCreateModal = true)
            }, [..._cache[33] || (_cache[33] = [
              createBaseVNode("i", { class: "fas fa-plus-circle me-1" }, null, -1),
              createTextVNode(" Создать шаблон ", -1)
            ])])
          ])
        ]),
        createBaseVNode("div", _hoisted_6, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($setup.statsCards, (stat) => {
            return openBlock(), createElementBlock("div", {
              key: stat.title,
              class: "col-md-3"
            }, [
              createBaseVNode("div", _hoisted_7, [
                createBaseVNode("div", _hoisted_8, [
                  createBaseVNode("div", {
                    class: normalizeClass(["stat-icon mb-2", stat.color])
                  }, [
                    createBaseVNode("i", {
                      class: normalizeClass(stat.icon)
                    }, null, 2)
                  ], 2),
                  createBaseVNode("h5", _hoisted_9, toDisplayString(stat.value), 1),
                  createBaseVNode("p", _hoisted_10, toDisplayString(stat.title), 1)
                ])
              ])
            ]);
          }), 128))
        ])
      ])
    ]),
    $setup.activeAbTests.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_11, [
      createBaseVNode("div", _hoisted_12, [
        createBaseVNode("div", _hoisted_13, [
          _cache[38] || (_cache[38] = createBaseVNode("div", { class: "card-header bg-warning text-dark" }, [
            createBaseVNode("h6", { class: "mb-0" }, [
              createBaseVNode("i", { class: "fas fa-flask me-2" }),
              createTextVNode("Активные A/B тесты ")
            ])
          ], -1)),
          createBaseVNode("div", _hoisted_14, [
            createBaseVNode("div", _hoisted_15, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($setup.activeAbTests, (test) => {
                var _a2;
                return openBlock(), createElementBlock("div", {
                  key: test.id,
                  class: "col-md-6 mb-3"
                }, [
                  createBaseVNode("div", _hoisted_16, [
                    createBaseVNode("div", _hoisted_17, [
                      createBaseVNode("h6", _hoisted_18, toDisplayString(test.name), 1),
                      _cache[35] || (_cache[35] = createBaseVNode("span", { class: "badge bg-warning" }, "A/B тест", -1))
                    ]),
                    createBaseVNode("div", _hoisted_19, [
                      createBaseVNode("div", _hoisted_20, [
                        createBaseVNode("span", null, "Длительность: " + toDisplayString($setup.getTestDuration(test)), 1),
                        createBaseVNode("span", null, toDisplayString(((_a2 = test.ab_test_variants) == null ? void 0 : _a2.length) || 0) + " вариантов", 1)
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_21, [
                      createBaseVNode("button", {
                        class: "btn btn-outline-info",
                        onClick: ($event) => $setup.viewAbTestStats(test)
                      }, [..._cache[36] || (_cache[36] = [
                        createBaseVNode("i", { class: "fas fa-chart-bar me-1" }, null, -1),
                        createTextVNode("Статистика ", -1)
                      ])], 8, _hoisted_22),
                      createBaseVNode("button", {
                        class: "btn btn-outline-success",
                        onClick: ($event) => $setup.stopAbTest(test)
                      }, [..._cache[37] || (_cache[37] = [
                        createBaseVNode("i", { class: "fas fa-stop me-1" }, null, -1),
                        createTextVNode("Остановить ", -1)
                      ])], 8, _hoisted_23)
                    ])
                  ])
                ]);
              }), 128))
            ])
          ])
        ])
      ])
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_24, [
      createBaseVNode("div", _hoisted_25, [
        createBaseVNode("div", _hoisted_26, [
          createBaseVNode("div", _hoisted_27, [
            _cache[40] || (_cache[40] = createBaseVNode("label", { class: "form-label" }, "Категория", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $setup.filters.category_id = $event),
              class: "form-select",
              onChange: _cache[3] || (_cache[3] = (...args) => $setup.loadTemplates && $setup.loadTemplates(...args))
            }, [
              _cache[39] || (_cache[39] = createBaseVNode("option", { value: "" }, "Все категории", -1)),
              (openBlock(true), createElementBlock(Fragment, null, renderList($setup.availableCategories, (category) => {
                return openBlock(), createElementBlock("option", {
                  key: category.id,
                  value: category.id
                }, toDisplayString(category.name), 9, _hoisted_28);
              }), 128))
            ], 544), [
              [vModelSelect, $setup.filters.category_id]
            ])
          ]),
          createBaseVNode("div", _hoisted_29, [
            _cache[42] || (_cache[42] = createBaseVNode("label", { class: "form-label" }, "Статус", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $setup.filters.status = $event),
              class: "form-select",
              onChange: _cache[5] || (_cache[5] = (...args) => $setup.loadTemplates && $setup.loadTemplates(...args))
            }, [..._cache[41] || (_cache[41] = [
              createBaseVNode("option", { value: "" }, "Все", -1),
              createBaseVNode("option", { value: "active" }, "Активные", -1),
              createBaseVNode("option", { value: "inactive" }, "Неактивные", -1)
            ])], 544), [
              [vModelSelect, $setup.filters.status]
            ])
          ]),
          createBaseVNode("div", _hoisted_30, [
            _cache[44] || (_cache[44] = createBaseVNode("label", { class: "form-label" }, "Тип", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $setup.filters.ab_test = $event),
              class: "form-select",
              onChange: _cache[7] || (_cache[7] = (...args) => $setup.loadTemplates && $setup.loadTemplates(...args))
            }, [..._cache[43] || (_cache[43] = [
              createBaseVNode("option", { value: "" }, "Все шаблоны", -1),
              createBaseVNode("option", { value: "active" }, "A/B тесты", -1),
              createBaseVNode("option", { value: "without" }, "Без A/B тестов", -1)
            ])], 544), [
              [vModelSelect, $setup.filters.ab_test]
            ])
          ]),
          createBaseVNode("div", _hoisted_31, [
            _cache[46] || (_cache[46] = createBaseVNode("label", { class: "form-label" }, "Поиск", -1)),
            createBaseVNode("div", _hoisted_32, [
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: "form-control",
                placeholder: "Название шаблона...",
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $setup.filters.search = $event),
                onKeyup: _cache[9] || (_cache[9] = withKeys((...args) => $setup.loadTemplates && $setup.loadTemplates(...args), ["enter"]))
              }, null, 544), [
                [vModelText, $setup.filters.search]
              ]),
              createBaseVNode("button", {
                class: "btn btn-outline-secondary",
                type: "button",
                onClick: _cache[10] || (_cache[10] = (...args) => $setup.loadTemplates && $setup.loadTemplates(...args))
              }, [..._cache[45] || (_cache[45] = [
                createBaseVNode("i", { class: "fas fa-search" }, null, -1)
              ])])
            ])
          ])
        ])
      ])
    ]),
    $setup.templates.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_33, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($setup.templates, (template) => {
        var _a2;
        return openBlock(), createElementBlock("div", {
          key: template.id,
          class: "col-lg-6 mb-4"
        }, [
          createBaseVNode("div", {
            class: normalizeClass(["card template-card h-100", {
              "border-warning": !template.is_active,
              "border-success": template.is_ab_test
            }])
          }, [
            createBaseVNode("div", _hoisted_34, [
              createBaseVNode("h6", _hoisted_35, toDisplayString(template.name), 1),
              createBaseVNode("div", _hoisted_36, [
                template.is_ab_test ? (openBlock(), createElementBlock("span", _hoisted_37, [..._cache[47] || (_cache[47] = [
                  createBaseVNode("i", { class: "fas fa-flask me-1" }, null, -1),
                  createTextVNode("A/B тест ", -1)
                ])])) : createCommentVNode("", true),
                createBaseVNode("div", _hoisted_38, [
                  withDirectives(createBaseVNode("input", {
                    class: "form-check-input",
                    type: "checkbox",
                    "onUpdate:modelValue": ($event) => template.is_active = $event,
                    onChange: ($event) => $setup.updateTemplateStatus(template)
                  }, null, 40, _hoisted_39), [
                    [vModelCheckbox, template.is_active]
                  ])
                ])
              ])
            ]),
            createBaseVNode("div", _hoisted_40, [
              createBaseVNode("div", _hoisted_41, [
                createBaseVNode("span", _hoisted_42, toDisplayString($setup.getCategoryName(template.category_id)), 1),
                !template.is_active ? (openBlock(), createElementBlock("span", _hoisted_43, "Неактивен")) : createCommentVNode("", true),
                template.is_ab_test ? (openBlock(), createElementBlock("span", _hoisted_44, toDisplayString(((_a2 = template.ab_test_variants) == null ? void 0 : _a2.length) || 0) + " вариантов ", 1)) : createCommentVNode("", true)
              ]),
              template.description ? (openBlock(), createElementBlock("p", _hoisted_45, toDisplayString(template.description), 1)) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_46, [
                createBaseVNode("div", _hoisted_47, [
                  createBaseVNode("strong", _hoisted_48, toDisplayString($setup.formatCurrency(template.proposed_price)) + "/час", 1),
                  createBaseVNode("small", _hoisted_49, "Время ответа: " + toDisplayString(template.response_time) + "ч", 1)
                ])
              ]),
              createBaseVNode("div", _hoisted_50, [
                createBaseVNode("div", _hoisted_51, [
                  createBaseVNode("strong", null, toDisplayString(template.usage_count || 0), 1),
                  _cache[48] || (_cache[48] = createBaseVNode("small", { class: "text-muted" }, "Использований", -1))
                ]),
                createBaseVNode("div", _hoisted_52, [
                  createBaseVNode("strong", {
                    class: normalizeClass($setup.getSuccessRateClass(template.success_rate))
                  }, toDisplayString(template.success_rate || 0) + "% ", 3),
                  _cache[49] || (_cache[49] = createBaseVNode("small", { class: "text-muted" }, "Успех", -1))
                ])
              ]),
              template.is_ab_test && template.ab_test_variants ? (openBlock(), createElementBlock("div", _hoisted_53, [
                _cache[51] || (_cache[51] = createBaseVNode("h6", { class: "small text-muted mb-2" }, "Варианты теста:", -1)),
                createBaseVNode("div", _hoisted_54, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList(template.ab_test_variants.slice(0, 2), (variant, index) => {
                    return openBlock(), createElementBlock("div", {
                      key: index,
                      class: "variant-preview small text-muted mb-1"
                    }, [
                      _cache[50] || (_cache[50] = createBaseVNode("i", { class: "fas fa-cube me-1" }, null, -1)),
                      createTextVNode(toDisplayString(variant.name) + " ", 1),
                      createBaseVNode("span", _hoisted_55, toDisplayString($setup.formatCurrency(variant.proposed_price)) + "/час", 1)
                    ]);
                  }), 128)),
                  template.ab_test_variants.length > 2 ? (openBlock(), createElementBlock("div", _hoisted_56, " + еще " + toDisplayString(template.ab_test_variants.length - 2) + " вариантов ", 1)) : createCommentVNode("", true)
                ])
              ])) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_57, toDisplayString($setup.truncateMessage(template.message)), 1)
            ]),
            createBaseVNode("div", _hoisted_58, [
              createBaseVNode("div", _hoisted_59, [
                createBaseVNode("button", {
                  class: "btn btn-outline-primary btn-sm",
                  onClick: ($event) => $setup.editTemplate(template),
                  title: "Редактировать"
                }, [..._cache[52] || (_cache[52] = [
                  createBaseVNode("i", { class: "fas fa-edit" }, null, -1)
                ])], 8, _hoisted_60),
                createBaseVNode("button", {
                  class: "btn btn-outline-success btn-sm",
                  onClick: ($event) => $setup.quickApply(template),
                  title: "Быстрое применение"
                }, [..._cache[53] || (_cache[53] = [
                  createBaseVNode("i", { class: "fas fa-bolt" }, null, -1)
                ])], 8, _hoisted_61),
                !template.is_ab_test ? (openBlock(), createElementBlock("button", {
                  key: 0,
                  class: "btn btn-outline-warning btn-sm",
                  onClick: ($event) => $setup.startAbTest(template),
                  title: "Запустить A/B тест"
                }, [..._cache[54] || (_cache[54] = [
                  createBaseVNode("i", { class: "fas fa-flask" }, null, -1)
                ])], 8, _hoisted_62)) : (openBlock(), createElementBlock("button", {
                  key: 1,
                  class: "btn btn-outline-info btn-sm",
                  onClick: ($event) => $setup.viewAbTestStats(template),
                  title: "Статистика A/B теста"
                }, [..._cache[55] || (_cache[55] = [
                  createBaseVNode("i", { class: "fas fa-chart-bar" }, null, -1)
                ])], 8, _hoisted_63)),
                createBaseVNode("button", {
                  class: "btn btn-outline-secondary btn-sm",
                  onClick: ($event) => $setup.duplicateTemplate(template),
                  title: "Дублировать"
                }, [..._cache[56] || (_cache[56] = [
                  createBaseVNode("i", { class: "fas fa-copy" }, null, -1)
                ])], 8, _hoisted_64),
                createBaseVNode("button", {
                  class: "btn btn-outline-danger btn-sm",
                  onClick: ($event) => $setup.deleteTemplate(template),
                  title: "Удалить"
                }, [..._cache[57] || (_cache[57] = [
                  createBaseVNode("i", { class: "fas fa-trash" }, null, -1)
                ])], 8, _hoisted_65)
              ])
            ])
          ], 2)
        ]);
      }), 128))
    ])) : (openBlock(), createElementBlock("div", _hoisted_66, [
      createBaseVNode("div", _hoisted_67, [
        _cache[59] || (_cache[59] = createBaseVNode("i", { class: "fas fa-file-alt fa-3x text-muted mb-3" }, null, -1)),
        _cache[60] || (_cache[60] = createBaseVNode("h5", null, "Шаблоны не найдены", -1)),
        _cache[61] || (_cache[61] = createBaseVNode("p", { class: "text-muted" }, "Создайте свой первый шаблон предложения", -1)),
        createBaseVNode("button", {
          class: "btn btn-primary",
          onClick: _cache[11] || (_cache[11] = ($event) => $setup.showCreateModal = true)
        }, [..._cache[58] || (_cache[58] = [
          createBaseVNode("i", { class: "fas fa-plus me-1" }, null, -1),
          createTextVNode("Создать шаблон ", -1)
        ])])
      ])
    ])),
    createBaseVNode("div", _hoisted_68, [
      $setup.showCreateModal ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: normalizeClass(["modal fade", { "show d-block": $setup.showCreateModal }]),
        style: { "background": "rgba(0,0,0,0.5)" }
      }, [
        createBaseVNode("div", _hoisted_69, [
          createBaseVNode("div", _hoisted_70, [
            createBaseVNode("div", _hoisted_71, [
              createBaseVNode("h5", _hoisted_72, toDisplayString($setup.editingTemplate ? "Редактирование шаблона" : "Создание шаблона"), 1),
              createBaseVNode("button", {
                type: "button",
                class: "btn-close",
                onClick: _cache[12] || (_cache[12] = (...args) => $setup.closeModal && $setup.closeModal(...args))
              })
            ]),
            createBaseVNode("div", _hoisted_73, [
              $setup.form.is_ab_test && (!$setup.form.ab_test_variants || $setup.form.ab_test_variants.length < 2) ? (openBlock(), createElementBlock("div", _hoisted_74, [..._cache[62] || (_cache[62] = [
                createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1),
                createTextVNode(" Для A/B теста необходимо как минимум 2 варианта ", -1)
              ])])) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_75, [
                createBaseVNode("div", _hoisted_76, [
                  createBaseVNode("div", _hoisted_77, [
                    _cache[63] || (_cache[63] = createBaseVNode("label", { class: "form-label" }, "Название шаблона *", -1)),
                    withDirectives(createBaseVNode("input", {
                      type: "text",
                      class: "form-control",
                      "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => $setup.form.name = $event),
                      required: ""
                    }, null, 512), [
                      [vModelText, $setup.form.name]
                    ])
                  ])
                ]),
                createBaseVNode("div", _hoisted_78, [
                  createBaseVNode("div", _hoisted_79, [
                    _cache[65] || (_cache[65] = createBaseVNode("label", { class: "form-label" }, "Категория *", -1)),
                    withDirectives(createBaseVNode("select", {
                      class: "form-select",
                      "onUpdate:modelValue": _cache[14] || (_cache[14] = ($event) => $setup.form.category_id = $event),
                      required: ""
                    }, [
                      _cache[64] || (_cache[64] = createBaseVNode("option", { value: "" }, "Выберите категорию", -1)),
                      (openBlock(true), createElementBlock(Fragment, null, renderList($setup.availableCategories, (category) => {
                        return openBlock(), createElementBlock("option", {
                          key: category.id,
                          value: category.id
                        }, toDisplayString(category.name), 9, _hoisted_80);
                      }), 128))
                    ], 512), [
                      [vModelSelect, $setup.form.category_id]
                    ])
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_81, [
                _cache[66] || (_cache[66] = createBaseVNode("label", { class: "form-label" }, "Описание", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: "form-control",
                  rows: "2",
                  "onUpdate:modelValue": _cache[15] || (_cache[15] = ($event) => $setup.form.description = $event),
                  placeholder: "Краткое описание шаблона..."
                }, null, 512), [
                  [vModelText, $setup.form.description]
                ])
              ]),
              createBaseVNode("div", _hoisted_82, [
                createBaseVNode("div", _hoisted_83, [
                  createBaseVNode("div", _hoisted_84, [
                    _cache[67] || (_cache[67] = createBaseVNode("label", { class: "form-label" }, "Предлагаемая цена (₽/час) *", -1)),
                    withDirectives(createBaseVNode("input", {
                      type: "number",
                      step: "0.01",
                      class: "form-control",
                      "onUpdate:modelValue": _cache[16] || (_cache[16] = ($event) => $setup.form.proposed_price = $event),
                      required: ""
                    }, null, 512), [
                      [vModelText, $setup.form.proposed_price]
                    ])
                  ])
                ]),
                createBaseVNode("div", _hoisted_85, [
                  createBaseVNode("div", _hoisted_86, [
                    _cache[68] || (_cache[68] = createBaseVNode("label", { class: "form-label" }, "Время ответа (часы) *", -1)),
                    withDirectives(createBaseVNode("input", {
                      type: "number",
                      class: "form-control",
                      "onUpdate:modelValue": _cache[17] || (_cache[17] = ($event) => $setup.form.response_time = $event),
                      min: "1",
                      max: "168",
                      required: ""
                    }, null, 512), [
                      [vModelText, $setup.form.response_time]
                    ])
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_87, [
                _cache[69] || (_cache[69] = createBaseVNode("label", { class: "form-label" }, "Текст сообщения *", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: "form-control",
                  rows: "4",
                  "onUpdate:modelValue": _cache[18] || (_cache[18] = ($event) => $setup.form.message = $event),
                  required: "",
                  placeholder: "Текст предложения для арендатора..."
                }, null, 512), [
                  [vModelText, $setup.form.message]
                ])
              ]),
              createBaseVNode("div", _hoisted_88, [
                _cache[70] || (_cache[70] = createBaseVNode("label", { class: "form-label" }, "Дополнительные условия", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: "form-control",
                  rows: "3",
                  "onUpdate:modelValue": _cache[19] || (_cache[19] = ($event) => $setup.form.additional_terms = $event),
                  placeholder: "Дополнительные условия аренды..."
                }, null, 512), [
                  [vModelText, $setup.form.additional_terms]
                ])
              ]),
              createBaseVNode("div", _hoisted_89, [
                createBaseVNode("div", _hoisted_90, [
                  withDirectives(createBaseVNode("input", {
                    class: "form-check-input",
                    type: "checkbox",
                    "onUpdate:modelValue": _cache[20] || (_cache[20] = ($event) => $setup.form.is_ab_test = $event),
                    id: "abTestToggle"
                  }, null, 512), [
                    [vModelCheckbox, $setup.form.is_ab_test]
                  ]),
                  _cache[71] || (_cache[71] = createBaseVNode("label", {
                    class: "form-check-label fw-bold",
                    for: "abTestToggle"
                  }, " Включить A/B тестирование ", -1))
                ]),
                $setup.form.is_ab_test ? (openBlock(), createElementBlock("div", _hoisted_91, [
                  _cache[83] || (_cache[83] = createBaseVNode("h6", { class: "mb-3" }, [
                    createBaseVNode("i", { class: "fas fa-flask me-2 text-warning" }),
                    createTextVNode("Настройки A/B теста ")
                  ], -1)),
                  createBaseVNode("div", _hoisted_92, [
                    createBaseVNode("div", _hoisted_93, [
                      _cache[73] || (_cache[73] = createBaseVNode("label", { class: "form-label" }, "Распределение трафика", -1)),
                      withDirectives(createBaseVNode("select", {
                        class: "form-select",
                        "onUpdate:modelValue": _cache[21] || (_cache[21] = ($event) => $setup.form.test_distribution = $event)
                      }, [..._cache[72] || (_cache[72] = [
                        createBaseVNode("option", { value: "50-50" }, "50/50 (два варианта)", -1),
                        createBaseVNode("option", { value: "33-33-33" }, "33/33/33 (три варианта)", -1),
                        createBaseVNode("option", { value: "25-25-25-25" }, "25/25/25/25 (четыре варианта)", -1),
                        createBaseVNode("option", { value: "custom" }, "Произвольное", -1)
                      ])], 512), [
                        [vModelSelect, $setup.form.test_distribution]
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_94, [
                      _cache[75] || (_cache[75] = createBaseVNode("label", { class: "form-label" }, "Метрика успеха", -1)),
                      withDirectives(createBaseVNode("select", {
                        class: "form-select",
                        "onUpdate:modelValue": _cache[22] || (_cache[22] = ($event) => $setup.form.test_metric = $event)
                      }, [..._cache[74] || (_cache[74] = [
                        createBaseVNode("option", { value: "conversion" }, "Конверсия в сделку", -1),
                        createBaseVNode("option", { value: "price" }, "Максимальная цена", -1),
                        createBaseVNode("option", { value: "speed" }, "Скорость ответа", -1)
                      ])], 512), [
                        [vModelSelect, $setup.form.test_metric]
                      ])
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_95, [
                    _cache[82] || (_cache[82] = createBaseVNode("h6", { class: "mb-3" }, "Варианты тестирования", -1)),
                    createBaseVNode("div", _hoisted_96, [
                      (openBlock(true), createElementBlock(Fragment, null, renderList($setup.form.ab_test_variants, (variant, index) => {
                        return openBlock(), createElementBlock("div", {
                          key: index,
                          class: "variant-card card mb-3"
                        }, [
                          createBaseVNode("div", _hoisted_97, [
                            createBaseVNode("h6", _hoisted_98, "Вариант " + toDisplayString(String.fromCharCode(65 + index)), 1),
                            createBaseVNode("button", {
                              type: "button",
                              class: "btn btn-danger btn-sm",
                              onClick: ($event) => $setup.removeVariant(index),
                              disabled: $setup.form.ab_test_variants.length <= 2
                            }, [..._cache[76] || (_cache[76] = [
                              createBaseVNode("i", { class: "fas fa-trash" }, null, -1)
                            ])], 8, _hoisted_99)
                          ]),
                          createBaseVNode("div", _hoisted_100, [
                            createBaseVNode("div", _hoisted_101, [
                              createBaseVNode("div", _hoisted_102, [
                                _cache[77] || (_cache[77] = createBaseVNode("label", { class: "form-label small" }, "Название варианта *", -1)),
                                withDirectives(createBaseVNode("input", {
                                  type: "text",
                                  class: "form-control form-control-sm",
                                  "onUpdate:modelValue": ($event) => variant.name = $event,
                                  placeholder: "e.g., Вариант A",
                                  required: ""
                                }, null, 8, _hoisted_103), [
                                  [vModelText, variant.name]
                                ])
                              ]),
                              createBaseVNode("div", _hoisted_104, [
                                _cache[78] || (_cache[78] = createBaseVNode("label", { class: "form-label small" }, "Цена (₽/час) *", -1)),
                                withDirectives(createBaseVNode("input", {
                                  type: "number",
                                  step: "0.01",
                                  class: "form-control form-control-sm",
                                  "onUpdate:modelValue": ($event) => variant.proposed_price = $event,
                                  required: ""
                                }, null, 8, _hoisted_105), [
                                  [vModelText, variant.proposed_price]
                                ])
                              ]),
                              createBaseVNode("div", _hoisted_106, [
                                _cache[79] || (_cache[79] = createBaseVNode("label", { class: "form-label small" }, "Текст сообщения *", -1)),
                                withDirectives(createBaseVNode("textarea", {
                                  class: "form-control form-control-sm",
                                  rows: "3",
                                  "onUpdate:modelValue": ($event) => variant.message = $event,
                                  placeholder: "Текст предложения для этого варианта...",
                                  required: ""
                                }, null, 8, _hoisted_107), [
                                  [vModelText, variant.message]
                                ])
                              ]),
                              createBaseVNode("div", _hoisted_108, [
                                _cache[80] || (_cache[80] = createBaseVNode("label", { class: "form-label small" }, "Дополнительные условия", -1)),
                                withDirectives(createBaseVNode("textarea", {
                                  class: "form-control form-control-sm",
                                  rows: "2",
                                  "onUpdate:modelValue": ($event) => variant.additional_terms = $event,
                                  placeholder: "Дополнительные условия для этого варианта..."
                                }, null, 8, _hoisted_109), [
                                  [vModelText, variant.additional_terms]
                                ])
                              ])
                            ])
                          ])
                        ]);
                      }), 128))
                    ]),
                    createBaseVNode("button", {
                      type: "button",
                      class: "btn btn-outline-primary btn-sm",
                      onClick: _cache[23] || (_cache[23] = (...args) => $setup.addVariant && $setup.addVariant(...args)),
                      disabled: $setup.form.ab_test_variants.length >= 4
                    }, [..._cache[81] || (_cache[81] = [
                      createBaseVNode("i", { class: "fas fa-plus me-1" }, null, -1),
                      createTextVNode("Добавить вариант ", -1)
                    ])], 8, _hoisted_110)
                  ])
                ])) : createCommentVNode("", true)
              ]),
              createBaseVNode("div", _hoisted_111, [
                withDirectives(createBaseVNode("input", {
                  class: "form-check-input",
                  type: "checkbox",
                  "onUpdate:modelValue": _cache[24] || (_cache[24] = ($event) => $setup.form.is_active = $event)
                }, null, 512), [
                  [vModelCheckbox, $setup.form.is_active]
                ]),
                _cache[84] || (_cache[84] = createBaseVNode("label", { class: "form-check-label" }, "Активный шаблон", -1))
              ])
            ]),
            createBaseVNode("div", _hoisted_112, [
              createBaseVNode("button", {
                type: "button",
                class: "btn btn-secondary",
                onClick: _cache[25] || (_cache[25] = (...args) => $setup.closeModal && $setup.closeModal(...args))
              }, "Отмена"),
              createBaseVNode("button", {
                type: "button",
                class: "btn btn-primary",
                onClick: _cache[26] || (_cache[26] = (...args) => $setup.saveTemplate && $setup.saveTemplate(...args)),
                disabled: $setup.saving
              }, [
                $setup.saving ? (openBlock(), createElementBlock("span", _hoisted_114)) : createCommentVNode("", true),
                createTextVNode(" " + toDisplayString($setup.editingTemplate ? "Обновить" : "Создать"), 1)
              ], 8, _hoisted_113)
            ])
          ])
        ])
      ], 2)) : createCommentVNode("", true),
      $setup.showQuickApplyModal ? (openBlock(), createElementBlock("div", {
        key: 1,
        class: normalizeClass(["modal fade", { "show d-block": $setup.showQuickApplyModal }]),
        style: { "background": "rgba(0,0,0,0.5)" }
      }, [
        createBaseVNode("div", _hoisted_115, [
          createBaseVNode("div", _hoisted_116, [
            createBaseVNode("div", _hoisted_117, [
              _cache[85] || (_cache[85] = createBaseVNode("h5", { class: "modal-title" }, "Быстрое применение шаблона", -1)),
              createBaseVNode("button", {
                type: "button",
                class: "btn-close",
                onClick: _cache[27] || (_cache[27] = ($event) => $setup.showQuickApplyModal = false)
              })
            ]),
            createBaseVNode("div", _hoisted_118, [
              createBaseVNode("p", null, [
                _cache[86] || (_cache[86] = createTextVNode("Применить шаблон ", -1)),
                createBaseVNode("strong", null, '"' + toDisplayString((_a = $setup.selectedTemplate) == null ? void 0 : _a.name) + '"', 1),
                _cache[87] || (_cache[87] = createTextVNode("?", -1))
              ]),
              createBaseVNode("p", _hoisted_119, "Цена: " + toDisplayString($setup.formatCurrency((_b = $setup.selectedTemplate) == null ? void 0 : _b.proposed_price)) + "/час", 1),
              ((_c = $setup.selectedTemplate) == null ? void 0 : _c.is_ab_test) ? (openBlock(), createElementBlock("div", _hoisted_120, [..._cache[88] || (_cache[88] = [
                createBaseVNode("i", { class: "fas fa-flask me-1" }, null, -1),
                createTextVNode(" Этот шаблон участвует в A/B тесте. Будет выбран случайный вариант. ", -1)
              ])])) : createCommentVNode("", true),
              _cache[89] || (_cache[89] = createBaseVNode("div", { class: "alert alert-info small" }, [
                createBaseVNode("i", { class: "fas fa-info-circle me-1" }),
                createTextVNode(" Шаблон будет применен к текущей заявке с автоматическим заполнением данных ")
              ], -1))
            ]),
            createBaseVNode("div", _hoisted_121, [
              createBaseVNode("button", {
                type: "button",
                class: "btn btn-secondary",
                onClick: _cache[28] || (_cache[28] = ($event) => $setup.showQuickApplyModal = false)
              }, "Отмена"),
              createBaseVNode("button", {
                type: "button",
                class: "btn btn-primary",
                onClick: _cache[29] || (_cache[29] = (...args) => $setup.confirmQuickApply && $setup.confirmQuickApply(...args))
              }, " Применить ")
            ])
          ])
        ])
      ], 2)) : createCommentVNode("", true),
      $setup.showAbStatsModal ? (openBlock(), createElementBlock("div", {
        key: 2,
        class: normalizeClass(["modal fade", { "show d-block": $setup.showAbStatsModal }]),
        style: { "background": "rgba(0,0,0,0.5)" }
      }, [
        createBaseVNode("div", _hoisted_122, [
          createBaseVNode("div", _hoisted_123, [
            createBaseVNode("div", _hoisted_124, [
              _cache[90] || (_cache[90] = createBaseVNode("h5", { class: "modal-title" }, [
                createBaseVNode("i", { class: "fas fa-chart-bar me-2" }),
                createTextVNode("Статистика A/B теста ")
              ], -1)),
              createBaseVNode("button", {
                type: "button",
                class: "btn-close",
                onClick: _cache[30] || (_cache[30] = ($event) => $setup.showAbStatsModal = false)
              })
            ]),
            createBaseVNode("div", _hoisted_125, [
              $setup.abTestStats ? (openBlock(), createElementBlock("div", _hoisted_126, [
                createBaseVNode("div", _hoisted_127, [
                  createBaseVNode("div", _hoisted_128, [
                    createBaseVNode("h6", null, toDisplayString((_d = $setup.selectedTemplate) == null ? void 0 : _d.name), 1),
                    createBaseVNode("p", _hoisted_129, " Длительность: " + toDisplayString($setup.abTestStats.total_duration), 1)
                  ]),
                  createBaseVNode("div", _hoisted_130, [
                    createBaseVNode("span", _hoisted_131, " Стат. значимость: " + toDisplayString($setup.abTestStats.statistical_significance) + "% ", 1),
                    createBaseVNode("button", {
                      class: "btn btn-outline-danger btn-sm",
                      onClick: _cache[31] || (_cache[31] = ($event) => $setup.stopAbTest($setup.selectedTemplate))
                    }, " Остановить тест ")
                  ])
                ]),
                createBaseVNode("div", _hoisted_132, [
                  createBaseVNode("table", _hoisted_133, [
                    _cache[91] || (_cache[91] = createBaseVNode("thead", null, [
                      createBaseVNode("tr", null, [
                        createBaseVNode("th", null, "Вариант"),
                        createBaseVNode("th", null, "Показы"),
                        createBaseVNode("th", null, "Применения"),
                        createBaseVNode("th", null, "Конверсии"),
                        createBaseVNode("th", null, "Конверсия"),
                        createBaseVNode("th", null, "Ср. цена"),
                        createBaseVNode("th", null, "Действия")
                      ])
                    ], -1)),
                    createBaseVNode("tbody", null, [
                      (openBlock(true), createElementBlock(Fragment, null, renderList($setup.abTestStats.variants, (variant, index) => {
                        return openBlock(), createElementBlock("tr", {
                          key: index,
                          class: normalizeClass({ "table-success": variant.is_winner })
                        }, [
                          createBaseVNode("td", null, [
                            createBaseVNode("strong", null, toDisplayString(variant.name), 1),
                            variant.is_winner ? (openBlock(), createElementBlock("span", _hoisted_134, "Победитель")) : createCommentVNode("", true)
                          ]),
                          createBaseVNode("td", null, toDisplayString(variant.impressions), 1),
                          createBaseVNode("td", null, toDisplayString(variant.applications), 1),
                          createBaseVNode("td", null, toDisplayString(variant.conversions), 1),
                          createBaseVNode("td", null, [
                            createBaseVNode("span", {
                              class: normalizeClass($setup.getConversionRateClass(variant.conversion_rate))
                            }, toDisplayString(variant.conversion_rate) + "% ", 3)
                          ]),
                          createBaseVNode("td", null, toDisplayString($setup.formatCurrency(variant.average_price)), 1),
                          createBaseVNode("td", null, [
                            !variant.is_winner && $setup.abTestStats.statistical_significance > 95 ? (openBlock(), createElementBlock("button", {
                              key: 0,
                              class: "btn btn-success btn-sm",
                              onClick: ($event) => $setup.declareWinner(index)
                            }, " Выбрать победителем ", 8, _hoisted_135)) : createCommentVNode("", true)
                          ])
                        ], 2);
                      }), 128))
                    ])
                  ])
                ]),
                createBaseVNode("div", _hoisted_136, [
                  createBaseVNode("div", _hoisted_137, [
                    _cache[95] || (_cache[95] = createBaseVNode("h6", null, "Метрики эффективности", -1)),
                    createBaseVNode("div", _hoisted_138, [
                      createBaseVNode("div", _hoisted_139, [
                        _cache[92] || (_cache[92] = createBaseVNode("span", { class: "metric-label" }, "Общие показы:", -1)),
                        createBaseVNode("span", _hoisted_140, toDisplayString($setup.abTestStats.total_impressions), 1)
                      ]),
                      createBaseVNode("div", _hoisted_141, [
                        _cache[93] || (_cache[93] = createBaseVNode("span", { class: "metric-label" }, "Общие применения:", -1)),
                        createBaseVNode("span", _hoisted_142, toDisplayString($setup.abTestStats.total_applications), 1)
                      ]),
                      createBaseVNode("div", _hoisted_143, [
                        _cache[94] || (_cache[94] = createBaseVNode("span", { class: "metric-label" }, "Общая конверсия:", -1)),
                        createBaseVNode("span", _hoisted_144, toDisplayString($setup.abTestStats.total_conversion_rate) + "%", 1)
                      ])
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_145, [
                    _cache[96] || (_cache[96] = createBaseVNode("h6", null, "Рекомендации", -1)),
                    createBaseVNode("div", {
                      class: normalizeClass(["alert", $setup.getRecommendationClass($setup.abTestStats.recommendation)])
                    }, toDisplayString($setup.abTestStats.recommendation), 3)
                  ])
                ])
              ])) : (openBlock(), createElementBlock("div", _hoisted_146, [..._cache[97] || (_cache[97] = [
                createBaseVNode("div", {
                  class: "spinner-border text-primary",
                  role: "status"
                }, null, -1),
                createBaseVNode("p", { class: "mt-2" }, "Загрузка статистики...", -1)
              ])]))
            ]),
            createBaseVNode("div", _hoisted_147, [
              createBaseVNode("button", {
                type: "button",
                class: "btn btn-secondary",
                onClick: _cache[32] || (_cache[32] = ($event) => $setup.showAbStatsModal = false)
              }, "Закрыть")
            ])
          ])
        ])
      ], 2)) : createCommentVNode("", true)
    ])
  ]);
}
const ProposalTemplates = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-ef2f6986"]]);
export {
  ProposalTemplates as default
};
