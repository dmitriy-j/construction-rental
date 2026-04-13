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
import { g as resolveComponent, a as createElementBlock, o as openBlock, b as createBaseVNode, t as toDisplayString, e as createCommentVNode, i as createVNode, w as withDirectives, j as vModelText, v as vModelSelect, F as Fragment, r as renderList, s as vModelCheckbox, d as createTextVNode, u as withModifiers, c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { a as RentalConditions, R as RequestItems } from "./RequestItems-Cig7CHK3.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  name: "EditRentalRequestForm",
  components: {
    RequestItems,
    RentalConditions
  },
  props: {
    requestId: { type: [String, Number], required: true },
    apiUrl: { type: String, required: true },
    updateUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    categories: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] }
  },
  data() {
    return {
      loading: true,
      error: null,
      formData: this.getDefaultFormData(),
      totalBudget: 0,
      totalQuantity: 0,
      minDate: (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
      submitting: false,
      showDebug: false,
      hasUnsavedChanges: false,
      preventUpdateLoop: false,
      isProcessingItemsUpdate: false
    };
  },
  computed: {
    rentalPeriod() {
      return {
        start: this.formData.rental_period_start,
        end: this.formData.rental_period_end
      };
    },
    rentalDays() {
      if (!this.formData.rental_period_start || !this.formData.rental_period_end) return 0;
      const start = new Date(this.formData.rental_period_start);
      const end = new Date(this.formData.rental_period_end);
      return Math.ceil((end - start) / (1e3 * 60 * 60 * 24)) + 1;
    },
    isFormValid() {
      return this.formData.title && this.formData.description && this.formData.hourly_rate > 0 && this.formData.rental_period_start && this.formData.rental_period_end && this.formData.location_id && this.formData.items.length > 0 && this.formData.items.every((item) => item.category_id && item.quantity > 0);
    },
    debugInfo() {
      return {
        requestId: this.requestId,
        apiUrl: this.apiUrl,
        updateUrl: this.updateUrl,
        formData: this.formData,
        loading: this.loading,
        error: this.error,
        hasUnsavedChanges: this.hasUnsavedChanges,
        totalBudget: this.totalBudget,
        totalQuantity: this.totalQuantity,
        isProcessingItemsUpdate: this.isProcessingItemsUpdate,
        preventUpdateLoop: this.preventUpdateLoop
      };
    }
  },
  methods: {
    getDefaultConditions() {
      return {
        payment_type: "hourly",
        hours_per_shift: 8,
        shifts_per_day: 1,
        transportation_organized_by: "lessor",
        gsm_payment: "included",
        operator_included: false,
        accommodation_payment: false,
        extension_possibility: true
      };
    },
    // ✅ ДОБАВЛЕН НОВЫЙ МЕТОД: Гарантированная очистка unit перед отправкой
    ensureUnitIsString(specs) {
      if (!specs || typeof specs !== "object") return specs;
      const cleanedSpecs = {};
      Object.keys(specs).forEach((key) => {
        const spec = specs[key];
        if (spec && typeof spec === "object") {
          cleanedSpecs[key] = __spreadProps(__spreadValues({}, spec), {
            unit: spec.unit !== null && spec.unit !== void 0 ? String(spec.unit) : ""
          });
          if (cleanedSpecs[key].unit === null) {
            console.error(`❌ ensureUnitIsString: unit всё равно null для ${key}`);
            cleanedSpecs[key].unit = "";
          }
        }
      });
      console.log("🔄 ensureUnitIsString выполнено:", {
        входные: Object.keys(specs).length,
        выходные: Object.keys(cleanedSpecs).length,
        units: Object.values(cleanedSpecs).map((s) => ({ unit: s.unit, type: typeof s.unit }))
      });
      return cleanedSpecs;
    },
    // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Подготовка кастомных спецификаций с гарантией типов
    prepareCustomSpecificationsForBackend(customSpecs) {
      const prepared = {};
      Object.keys(customSpecs).forEach((key) => {
        const spec = customSpecs[key];
        if (spec && spec.label) {
          let unitValue = "";
          if (spec.unit !== null && spec.unit !== void 0) {
            unitValue = String(spec.unit);
          }
          console.log("🔍 EditRentalRequestForm: подготовка кастомной спецификации для бэкенда:", {
            key,
            label: spec.label,
            value: spec.value,
            originalUnit: spec.unit,
            normalizedUnit: unitValue,
            unitType: typeof unitValue,
            isNull: unitValue === null
          });
          const preparedSpec = {
            label: String(spec.label || ""),
            value: this.normalizeCustomSpecValue(spec.value, spec.dataType),
            unit: unitValue,
            // ✅ Всегда строка, никогда null
            dataType: String(spec.dataType || "string")
          };
          if (preparedSpec.unit === null) {
            console.error("❌ EditRentalRequestForm: КРИТИЧЕСКАЯ ОШИБКА - unit всё равно null после всех преобразований!");
            preparedSpec.unit = "";
          }
          console.log("✅ EditRentalRequestForm: финальная проверка спецификации:", {
            key,
            unit: preparedSpec.unit,
            unitType: typeof preparedSpec.unit,
            isNull: preparedSpec.unit === null
          });
          prepared[key] = preparedSpec;
        }
      });
      console.log("🔧 Подготовлены кастомные спецификации для бэкенда:", {
        количество: Object.keys(prepared).length,
        данные: prepared,
        units_check: Object.values(prepared).map((s) => ({
          unit: s.unit,
          type: typeof s.unit,
          isNull: s.unit === null
        }))
      });
      return prepared;
    },
    getDefaultFormData() {
      return {
        title: "",
        description: "",
        hourly_rate: 0,
        rental_period_start: "",
        rental_period_end: "",
        location_id: "",
        rental_conditions: this.getDefaultConditions(),
        items: [],
        delivery_required: false
        // 🔥 ДОБАВЛЕНО
      };
    },
    loadRequestData() {
      return __async(this, null, function* () {
        this.loading = true;
        this.error = null;
        try {
          yield new Promise((resolve) => setTimeout(resolve, 1e3));
          console.log("🔄 EditRentalRequestForm: загрузка данных заявки:", this.apiUrl);
          const response = yield fetch(this.apiUrl, {
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          if (!response.ok) {
            throw new Error(`HTTP ошибка! Статус: ${response.status}`);
          }
          const data = yield response.json();
          if (data.success) {
            this.initializeFormData(data.data);
          } else {
            throw new Error(data.message || "Ошибка загрузки данных");
          }
        } catch (error) {
          console.error("❌ EditRentalRequestForm: ошибка загрузки:", error);
          this.error = error.message;
          if (error.message.includes("429")) {
            this.error = "Слишком много запросов. Подождите несколько секунд и попробуйте снова.";
          }
        } finally {
          this.loading = false;
        }
      });
    },
    initializeFormData(requestData) {
      const formatDateForInput = (dateString) => {
        if (!dateString) return "";
        const date = new Date(dateString);
        return date.toISOString().split("T")[0];
      };
      this.formData = {
        title: requestData.title || "",
        description: requestData.description || "",
        hourly_rate: parseFloat(requestData.hourly_rate) || 0,
        rental_period_start: formatDateForInput(requestData.rental_period_start),
        rental_period_end: formatDateForInput(requestData.rental_period_end),
        location_id: requestData.location_id || "",
        rental_conditions: requestData.rental_conditions || this.getDefaultConditions(),
        delivery_required: Boolean(requestData.delivery_required),
        // 🔥 ДОБАВЛЕНО
        items: requestData.items ? requestData.items.map((item) => {
          var _a, _b;
          return {
            category_id: item.category_id,
            quantity: item.quantity,
            hourly_rate: item.hourly_rate,
            use_individual_conditions: item.use_individual_conditions || false,
            individual_conditions: item.individual_conditions || {},
            specifications: {
              standard_specifications: item.standard_specifications || ((_a = item.specifications) == null ? void 0 : _a.standard_specifications) || {},
              custom_specifications: item.custom_specifications || ((_b = item.specifications) == null ? void 0 : _b.custom_specifications) || {}
            }
          };
        }) : []
      };
      this.totalQuantity = this.formData.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
      this.calculateTotalBudget();
      console.log("📝 EditRentalRequestForm: форма инициализирована с данными:", {
        items_count: this.formData.items.length,
        items_with_custom_specs: this.formData.items.filter(
          (item) => {
            var _a;
            return ((_a = item.specifications) == null ? void 0 : _a.custom_specifications) && Object.keys(item.specifications.custom_specifications).length > 0;
          }
        ).length,
        delivery_required: this.formData.delivery_required
        // 🔥 ДОБАВЛЕНО
      });
    },
    onItemsUpdated(items) {
      if (this.preventUpdateLoop || this.isProcessingItemsUpdate) {
        console.log("🛑 EditRentalRequestForm: предотвращен циклический вызов onItemsUpdated");
        return;
      }
      const currentItemsStr = JSON.stringify(this.formData.items);
      const newItemsStr = JSON.stringify(items);
      if (currentItemsStr !== newItemsStr) {
        console.log("✅ EditRentalRequestForm: приняты новые items от RequestItems", {
          количество: items.length,
          позиции_с_кастомными_спецификациями: items.filter(
            (item) => {
              var _a;
              return ((_a = item.specifications) == null ? void 0 : _a.custom_specifications) && Object.keys(item.specifications.custom_specifications).length > 0;
            }
          ).length
        });
        this.isProcessingItemsUpdate = true;
        this.formData.items = items;
        this.totalQuantity = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
        this.calculateTotalBudget();
        this.hasUnsavedChanges = true;
        setTimeout(() => {
          this.isProcessingItemsUpdate = false;
        }, 100);
      } else {
        console.log("🛑 EditRentalRequestForm: данные items не изменились, пропускаем обновление");
      }
    },
    onTotalBudgetUpdated(budget) {
      this.totalBudget = budget;
    },
    onConditionsUpdated(conditions) {
      this.formData.rental_conditions = conditions;
      this.hasUnsavedChanges = true;
      this.calculateTotalBudget();
    },
    calculateTotalBudget() {
      if (this.formData.items.length === 0) {
        this.totalBudget = 0;
        return;
      }
      let total = 0;
      const days = this.rentalDays;
      const hourlyRate = this.formData.hourly_rate;
      this.formData.items.forEach((item) => {
        const itemHourlyRate = item.hourly_rate || hourlyRate;
        const conditions = item.use_individual_conditions && item.individual_conditions ? item.individual_conditions : this.formData.rental_conditions;
        const hoursPerShift = conditions.hours_per_shift || 8;
        const shiftsPerDay = conditions.shifts_per_day || 1;
        total += itemHourlyRate * hoursPerShift * shiftsPerDay * days * item.quantity;
      });
      this.totalBudget = total;
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat("ru-RU", {
        style: "currency",
        currency: "RUB",
        minimumFractionDigits: 0
      }).format(amount);
    },
    submitForm() {
      return __async(this, null, function* () {
        if (!this.isFormValid) {
          alert("Пожалуйста, заполните все обязательные поля и добавьте хотя бы одну позицию");
          return;
        }
        this.submitting = true;
        try {
          const response = yield fetch(this.updateUrl, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": this.csrfToken,
              "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify(this.prepareFormData())
          });
          const data = yield response.json();
          if (data.success) {
            this.hasUnsavedChanges = false;
            alert("Заявка успешно обновлена!");
            window.location.href = `/lessee/rental-requests/${this.requestId}`;
          } else {
            throw new Error(data.message || "Ошибка при обновлении заявки");
          }
        } catch (error) {
          console.error("❌ EditRentalRequestForm: ошибка сохранения:", error);
          alert("Ошибка: " + error.message);
        } finally {
          this.submitting = false;
        }
      });
    },
    prepareFormData() {
      const formData = {
        title: this.formData.title,
        description: this.formData.description,
        hourly_rate: parseFloat(this.formData.hourly_rate) || 0,
        rental_period_start: this.formData.rental_period_start,
        rental_period_end: this.formData.rental_period_end,
        location_id: this.formData.location_id,
        rental_conditions: this.formData.rental_conditions,
        delivery_required: Boolean(this.formData.delivery_required),
        // 🔥 ДОБАВЛЕНО
        items: this.formData.items.map((item) => {
          const preparedItem = {
            category_id: item.category_id,
            quantity: parseInt(item.quantity) || 1,
            hourly_rate: item.hourly_rate ? parseFloat(item.hourly_rate) : null,
            use_individual_conditions: Boolean(item.use_individual_conditions),
            individual_conditions: item.use_individual_conditions ? item.individual_conditions : {}
          };
          if (item.specifications) {
            preparedItem.standard_specifications = this.prepareStandardSpecifications(
              item.specifications.standard_specifications || {}
            );
            const rawCustomSpecs = item.specifications.custom_specifications || {};
            let processedCustomSpecs = this.prepareCustomSpecificationsForBackend(rawCustomSpecs);
            preparedItem.custom_specifications = this.ensureUnitIsString(processedCustomSpecs);
            Object.keys(preparedItem.custom_specifications).forEach((key) => {
              const spec = preparedItem.custom_specifications[key];
              if (spec.unit === null) {
                console.error(`❌ КРИТИЧЕСКАЯ ОШИБКА В prepareFormData: unit null для ${key}`);
                preparedItem.custom_specifications[key].unit = "";
              }
            });
            preparedItem.specifications = __spreadValues(__spreadValues({}, preparedItem.standard_specifications), this.extractCustomValues(preparedItem.custom_specifications));
            console.log("📦 Prepared item specs for backend:", {
              стандартные: Object.keys(preparedItem.standard_specifications).length,
              кастомные: Object.keys(preparedItem.custom_specifications).length,
              кастомные_данные: preparedItem.custom_specifications,
              units_final_check: Object.values(preparedItem.custom_specifications).map((s) => ({
                unit: s.unit,
                type: typeof s.unit,
                isNull: s.unit === null
              }))
            });
          } else {
            preparedItem.standard_specifications = {};
            preparedItem.custom_specifications = {};
            preparedItem.specifications = {};
          }
          return preparedItem;
        })
      };
      formData._method = "PUT";
      console.log("🔍 ФИНАЛЬНАЯ ПРОВЕРКА ДАННЫХ ПЕРЕД ОТПРАВКОЙ:");
      let totalNullUnits = 0;
      formData.items.forEach((item, index) => {
        console.log(`Item ${index} custom specs:`, item.custom_specifications);
        Object.keys(item.custom_specifications || {}).forEach((key) => {
          const spec = item.custom_specifications[key];
          console.log(`  ${key}:`, {
            label: spec.label,
            value: spec.value,
            unit: spec.unit,
            unitType: typeof spec.unit,
            isNull: spec.unit === null
          });
          if (spec.unit === null) {
            totalNullUnits++;
            console.error(`❌ ОБНАРУЖЕН NULL UNIT: item ${index}, key ${key}`);
          }
        });
      });
      if (totalNullUnits > 0) {
        console.error(`🚨 КРИТИЧЕСКАЯ ОШИБКА: Обнаружено ${totalNullUnits} полей unit со значением null!`);
      }
      console.log("📤 EditRentalRequestForm: Final prepared form data for update:", {
        items_count: formData.items.length,
        items_with_custom_specs: formData.items.filter(
          (item) => item.custom_specifications && Object.keys(item.custom_specifications).length > 0
        ).length,
        total_custom_specs: formData.items.reduce((sum, item) => sum + Object.keys(item.custom_specifications || {}).length, 0),
        total_null_units: totalNullUnits,
        delivery_required: formData.delivery_required
        // 🔥 ДОБАВЛЕНО
      });
      return formData;
    },
    prepareStandardSpecifications(standardSpecs) {
      const prepared = {};
      Object.keys(standardSpecs).forEach((key) => {
        const value = standardSpecs[key];
        if (value !== null && value !== void 0 && value !== "") {
          if (typeof value === "string" && !isNaN(value) && value.trim() !== "") {
            prepared[key] = Number(value);
          } else {
            prepared[key] = value;
          }
        }
      });
      return prepared;
    },
    normalizeCustomSpecValue(value, dataType) {
      if (value === null || value === void 0 || value === "") {
        return null;
      }
      if (dataType === "number") {
        const numValue = Number(value);
        return isNaN(numValue) ? null : numValue;
      } else {
        return String(value);
      }
    },
    extractCustomValues(customSpecs) {
      const values = {};
      Object.keys(customSpecs).forEach((key) => {
        const spec = customSpecs[key];
        if (spec && spec.value !== null && spec.value !== void 0) {
          const labelKey = spec.label || key;
          values[labelKey] = spec.value;
        }
      });
      return values;
    },
    cancel() {
      if (this.hasUnsavedChanges) {
        if (!confirm("У вас есть несохраненные изменения. Вы уверены, что хотите отменить?")) {
          return;
        }
      }
      window.history.back();
    }
  },
  mounted() {
    return __async(this, null, function* () {
      console.log("✅ EditRentalRequestForm: компонент редактирования смонтирован");
      console.log("📊 Параметры:", {
        requestId: this.requestId,
        apiUrl: this.apiUrl,
        updateUrl: this.updateUrl,
        categoriesCount: this.categories.length,
        locationsCount: this.locations.length
      });
      yield this.loadRequestData();
    });
  },
  beforeUnmount() {
    if (this.hasUnsavedChanges) {
      const confirmationMessage = "У вас есть несохраненные изменения. Вы уверены, что хотите уйти?";
      if (!confirm(confirmationMessage)) {
        return false;
      }
    }
  }
};
const _hoisted_1 = {
  key: 0,
  class: "text-center py-5"
};
const _hoisted_2 = {
  key: 1,
  class: "alert alert-danger"
};
const _hoisted_3 = { key: 2 };
const _hoisted_4 = { class: "card mb-4" };
const _hoisted_5 = { class: "card-body" };
const _hoisted_6 = { class: "row g-3" };
const _hoisted_7 = { class: "col-md-12" };
const _hoisted_8 = { class: "col-md-12" };
const _hoisted_9 = { class: "col-md-6" };
const _hoisted_10 = ["min"];
const _hoisted_11 = { class: "col-md-6" };
const _hoisted_12 = ["min"];
const _hoisted_13 = { class: "col-md-6" };
const _hoisted_14 = ["value"];
const _hoisted_15 = { class: "col-md-6" };
const _hoisted_16 = { class: "col-12" };
const _hoisted_17 = { class: "form-check" };
const _hoisted_18 = { class: "card mb-4" };
const _hoisted_19 = { class: "card-body" };
const _hoisted_20 = { class: "card mb-4" };
const _hoisted_21 = { class: "card-body text-center" };
const _hoisted_22 = { class: "display-4 text-success mb-2" };
const _hoisted_23 = { class: "text-muted" };
const _hoisted_24 = {
  key: 0,
  class: "badge bg-info ms-2"
};
const _hoisted_25 = { class: "form-actions mt-4" };
const _hoisted_26 = ["disabled"];
const _hoisted_27 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-2"
};
const _hoisted_28 = {
  key: 0,
  class: "card mt-4"
};
const _hoisted_29 = { class: "card-body" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_RequestItems = resolveComponent("RequestItems");
  const _component_RentalConditions = resolveComponent("RentalConditions");
  return openBlock(), createElementBlock("div", null, [
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_1, [..._cache[10] || (_cache[10] = [
      createBaseVNode("div", {
        class: "spinner-border text-primary",
        role: "status"
      }, [
        createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
      ], -1),
      createBaseVNode("p", { class: "mt-2" }, "Загрузка данных заявки...", -1)
    ])])) : $data.error ? (openBlock(), createElementBlock("div", _hoisted_2, toDisplayString($data.error), 1)) : (openBlock(), createElementBlock("div", _hoisted_3, [
      createBaseVNode("form", {
        onSubmit: _cache[9] || (_cache[9] = withModifiers((...args) => $options.submitForm && $options.submitForm(...args), ["prevent"]))
      }, [
        createBaseVNode("div", _hoisted_4, [
          _cache[21] || (_cache[21] = createBaseVNode("div", { class: "card-header" }, [
            createBaseVNode("h5", { class: "card-title mb-0" }, "Основная информация")
          ], -1)),
          createBaseVNode("div", _hoisted_5, [
            createBaseVNode("div", _hoisted_6, [
              createBaseVNode("div", _hoisted_7, [
                _cache[11] || (_cache[11] = createBaseVNode("label", { class: "form-label" }, "Название заявки *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "text",
                  class: "form-control",
                  "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.formData.title = $event),
                  required: ""
                }, null, 512), [
                  [vModelText, $data.formData.title]
                ])
              ]),
              createBaseVNode("div", _hoisted_8, [
                _cache[12] || (_cache[12] = createBaseVNode("label", { class: "form-label" }, "Описание *", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: "form-control",
                  "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.formData.description = $event),
                  rows: "4",
                  required: ""
                }, null, 512), [
                  [vModelText, $data.formData.description]
                ])
              ]),
              createBaseVNode("div", _hoisted_9, [
                _cache[13] || (_cache[13] = createBaseVNode("label", { class: "form-label" }, "Дата начала *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "date",
                  class: "form-control",
                  "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.formData.rental_period_start = $event),
                  min: $data.minDate,
                  required: ""
                }, null, 8, _hoisted_10), [
                  [vModelText, $data.formData.rental_period_start]
                ])
              ]),
              createBaseVNode("div", _hoisted_11, [
                _cache[14] || (_cache[14] = createBaseVNode("label", { class: "form-label" }, "Дата окончания *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "date",
                  class: "form-control",
                  "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.formData.rental_period_end = $event),
                  min: $data.formData.rental_period_start,
                  required: ""
                }, null, 8, _hoisted_12), [
                  [vModelText, $data.formData.rental_period_end]
                ])
              ]),
              createBaseVNode("div", _hoisted_13, [
                _cache[16] || (_cache[16] = createBaseVNode("label", { class: "form-label" }, "Локация *", -1)),
                withDirectives(createBaseVNode("select", {
                  class: "form-select",
                  "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.formData.location_id = $event),
                  required: ""
                }, [
                  _cache[15] || (_cache[15] = createBaseVNode("option", { value: "" }, "Выберите локацию", -1)),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($props.locations, (location) => {
                    return openBlock(), createElementBlock("option", {
                      value: location.id,
                      key: location.id
                    }, toDisplayString(location.name) + " - " + toDisplayString(location.address), 9, _hoisted_14);
                  }), 128))
                ], 512), [
                  [vModelSelect, $data.formData.location_id]
                ])
              ]),
              createBaseVNode("div", _hoisted_15, [
                _cache[17] || (_cache[17] = createBaseVNode("label", { class: "form-label" }, "Базовая стоимость часа (₽) *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "number",
                  class: "form-control",
                  "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.formData.hourly_rate = $event),
                  min: "0",
                  step: "50",
                  required: ""
                }, null, 512), [
                  [
                    vModelText,
                    $data.formData.hourly_rate,
                    void 0,
                    { number: true }
                  ]
                ]),
                _cache[18] || (_cache[18] = createBaseVNode("small", { class: "text-muted" }, "Будет использована для позиций без индивидуальной стоимости", -1))
              ]),
              createBaseVNode("div", _hoisted_16, [
                createBaseVNode("div", _hoisted_17, [
                  withDirectives(createBaseVNode("input", {
                    class: "form-check-input",
                    type: "checkbox",
                    "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.formData.delivery_required = $event),
                    id: "delivery_required"
                  }, null, 512), [
                    [vModelCheckbox, $data.formData.delivery_required]
                  ]),
                  _cache[19] || (_cache[19] = createBaseVNode("label", {
                    class: "form-check-label",
                    for: "delivery_required"
                  }, [
                    createBaseVNode("i", { class: "fas fa-truck me-2" }),
                    createTextVNode("Требуется доставка техники к объекту ")
                  ], -1)),
                  _cache[20] || (_cache[20] = createBaseVNode("small", { class: "form-text text-muted" }, " Отметьте, если вам необходима доставка оборудования к месту проведения работ ", -1))
                ])
              ])
            ])
          ])
        ]),
        createVNode(_component_RequestItems, {
          categories: $props.categories,
          "general-hourly-rate": $data.formData.hourly_rate,
          "general-conditions": $data.formData.rental_conditions,
          "rental-period": $options.rentalPeriod,
          "initial-items": $data.formData.items,
          onItemsUpdated: $options.onItemsUpdated,
          onTotalBudgetUpdated: $options.onTotalBudgetUpdated
        }, null, 8, ["categories", "general-hourly-rate", "general-conditions", "rental-period", "initial-items", "onItemsUpdated", "onTotalBudgetUpdated"]),
        createBaseVNode("div", _hoisted_18, [
          _cache[22] || (_cache[22] = createBaseVNode("div", { class: "card-header" }, [
            createBaseVNode("h5", { class: "card-title mb-0" }, "Общие условия аренды"),
            createBaseVNode("small", { class: "text-muted" }, "Применяются ко всем позициям, если не указаны индивидуальные условия")
          ], -1)),
          createBaseVNode("div", _hoisted_19, [
            createVNode(_component_RentalConditions, {
              "initial-conditions": $data.formData.rental_conditions,
              onConditionsUpdated: $options.onConditionsUpdated
            }, null, 8, ["initial-conditions", "onConditionsUpdated"])
          ])
        ]),
        createBaseVNode("div", _hoisted_20, [
          _cache[24] || (_cache[24] = createBaseVNode("div", { class: "card-header bg-success text-white" }, [
            createBaseVNode("h5", { class: "card-title mb-0" }, [
              createBaseVNode("i", { class: "fas fa-calculator me-2" }),
              createTextVNode("Итоговый бюджет заявки ")
            ])
          ], -1)),
          createBaseVNode("div", _hoisted_21, [
            createBaseVNode("div", _hoisted_22, toDisplayString($options.formatCurrency($data.totalBudget)), 1),
            createBaseVNode("p", _hoisted_23, [
              createTextVNode(" Общая стоимость для " + toDisplayString($data.totalQuantity) + " единиц техники на период " + toDisplayString($options.rentalDays) + " дней ", 1),
              $data.formData.delivery_required ? (openBlock(), createElementBlock("span", _hoisted_24, [..._cache[23] || (_cache[23] = [
                createBaseVNode("i", { class: "fas fa-truck me-1" }, null, -1),
                createTextVNode("С доставкой ", -1)
              ])])) : createCommentVNode("", true)
            ])
          ])
        ]),
        createBaseVNode("div", _hoisted_25, [
          createBaseVNode("button", {
            type: "submit",
            class: "btn btn-primary",
            disabled: $data.submitting
          }, [
            $data.submitting ? (openBlock(), createElementBlock("span", _hoisted_27)) : createCommentVNode("", true),
            createTextVNode(" " + toDisplayString($data.submitting ? "Сохранение..." : "Обновить заявку"), 1)
          ], 8, _hoisted_26),
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-outline-secondary ms-2",
            onClick: _cache[7] || (_cache[7] = (...args) => $options.cancel && $options.cancel(...args))
          }, " Отмена "),
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-outline-info ms-auto",
            onClick: _cache[8] || (_cache[8] = ($event) => $data.showDebug = !$data.showDebug)
          }, toDisplayString($data.showDebug ? "Скрыть отладку" : "Показать отладку"), 1)
        ])
      ], 32),
      $data.showDebug ? (openBlock(), createElementBlock("div", _hoisted_28, [
        _cache[25] || (_cache[25] = createBaseVNode("div", { class: "card-header" }, [
          createBaseVNode("h6", { class: "mb-0" }, "Отладочная информация")
        ], -1)),
        createBaseVNode("div", _hoisted_29, [
          createBaseVNode("pre", null, toDisplayString($options.debugInfo), 1)
        ])
      ])) : createCommentVNode("", true)
    ]))
  ]);
}
const EditRentalRequestForm = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-67425c9a"]]);
console.log("🎯 rental-request-edit.js: Скрипт начал выполнение");
function fixPageStructure() {
  console.log("🔧 Исправляем структуру страницы...");
  const appElement = document.getElementById("rental-request-edit-app");
  if (!appElement) return;
  appElement.style.minHeight = "auto";
  appElement.style.height = "auto";
  appElement.style.overflow = "visible";
  const mainContent = document.querySelector(".main-content");
  const contentContainer = document.querySelector(".content-container");
  const footer = document.querySelector(".site-footer");
  if (mainContent) {
    mainContent.style.minHeight = "auto";
    mainContent.style.height = "auto";
    mainContent.style.flex = "1";
  }
  if (contentContainer) {
    contentContainer.style.minHeight = "auto";
    contentContainer.style.height = "auto";
    contentContainer.style.flex = "1";
  }
  if (footer) {
    footer.style.marginTop = "auto";
    footer.style.flexShrink = "0";
    footer.style.position = "relative";
    footer.style.zIndex = "10";
  }
  console.log("✅ Структура страницы исправлена");
}
function initializeVueApp() {
  console.log("🔄 Инициализация Vue приложения...");
  const appElement = document.getElementById("rental-request-edit-app");
  if (!appElement) {
    console.error("❌ Элемент #rental-request-edit-app не найден");
    return;
  }
  try {
    fixPageStructure();
    const app = createApp(EditRentalRequestForm, {
      requestId: appElement.dataset.requestId,
      apiUrl: appElement.dataset.apiUrl,
      updateUrl: appElement.dataset.updateUrl,
      csrfToken: appElement.dataset.csrfToken,
      categories: JSON.parse(appElement.dataset.categories || "[]"),
      locations: JSON.parse(appElement.dataset.locations || "[]")
    });
    app.mount("#rental-request-edit-app");
    console.log("✅ Vue приложение смонтировано успешно");
    setTimeout(() => {
      fixPageStructure();
      checkFooterPosition();
    }, 500);
  } catch (error) {
    console.error("❌ Ошибка при монтировании Vue:", error);
  }
}
function checkFooterPosition() {
  const footer = document.querySelector(".site-footer");
  const app = document.getElementById("app");
  const mainContent = document.querySelector(".main-content-wrapper");
  if (!footer || !app || !mainContent) return;
  const windowHeight = window.innerHeight;
  const appHeight = app.offsetHeight;
  const mainContentHeight = mainContent.offsetHeight;
  const footerRect = footer.getBoundingClientRect();
  console.log("📊 Проверка позиции футера:", {
    windowHeight,
    appHeight,
    mainContentHeight,
    footerTop: footerRect.top,
    footerBottom: footerRect.bottom,
    documentHeight: document.documentElement.scrollHeight
  });
  if (footerRect.top < windowHeight - 100) {
    console.log("⚠️ Футер не внизу, применяем экстренный фикс");
    applyEmergencyFix();
  }
}
function applyEmergencyFix() {
  const app = document.getElementById("app");
  const mainContent = document.querySelector(".main-content-wrapper");
  const footer = document.querySelector(".site-footer");
  if (app && mainContent && footer) {
    app.style.display = "flex";
    app.style.flexDirection = "column";
    app.style.minHeight = "100vh";
    mainContent.style.flex = "1";
    mainContent.style.minHeight = "auto";
    footer.style.marginTop = "auto";
    footer.style.flexShrink = "0";
    footer.style.position = "relative";
    console.log("🚨 Экстренный фикс применен");
  }
}
document.addEventListener("DOMContentLoaded", function() {
  console.log("📄 DOM готов");
  setTimeout(() => {
    initializeVueApp();
  }, 100);
});
window.addEventListener("load", function() {
  console.log("🖼️ Страница полностью загружена");
  setTimeout(() => {
    fixPageStructure();
    checkFooterPosition();
  }, 1e3);
});
window.addEventListener("resize", function() {
  setTimeout(checkFooterPosition, 100);
});
