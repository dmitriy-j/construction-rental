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
import { a as RentalConditions, R as RequestItems } from "./RequestItems-Cig7CHK3.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { a as createElementBlock, o as openBlock, b as createBaseVNode, d as createTextVNode, t as toDisplayString, e as createCommentVNode, w as withDirectives, v as vModelSelect, F as Fragment, r as renderList, j as vModelText, g as resolveComponent, i as createVNode, s as vModelCheckbox, u as withModifiers } from "./runtime-dom.esm-bundler-BObhqzw5.js";
const _sfc_main$2 = {
  name: "BudgetCalculator",
  props: {
    hourlyRate: Number,
    rentalPeriodStart: String,
    rentalPeriodEnd: String,
    equipmentQuantity: Number,
    rentalConditions: Object
  },
  data() {
    return {
      totalBudget: 0,
      costPerShift: 0,
      costPerDay: 0,
      costPerPeriod: 0
    };
  },
  computed: {
    rentalDays() {
      if (!this.rentalPeriodStart || !this.rentalPeriodEnd) return 0;
      const start = new Date(this.rentalPeriodStart);
      const end = new Date(this.rentalPeriodEnd);
      return Math.ceil((end - start) / (1e3 * 60 * 60 * 24)) + 1;
    },
    hoursPerShift() {
      var _a;
      return ((_a = this.rentalConditions) == null ? void 0 : _a.hours_per_shift) || 8;
    },
    shiftsPerDay() {
      var _a;
      return ((_a = this.rentalConditions) == null ? void 0 : _a.shifts_per_day) || 1;
    }
  },
  watch: {
    hourlyRate: "calculateBudget",
    rentalPeriodStart: "calculateBudget",
    rentalPeriodEnd: "calculateBudget",
    equipmentQuantity: "calculateBudget",
    rentalConditions: {
      handler: "calculateBudget",
      deep: true
    }
  },
  methods: {
    calculateBudget() {
      if (!this.hourlyRate || this.rentalDays <= 0 || this.equipmentQuantity <= 0) {
        this.resetCalculation();
        return;
      }
      this.costPerShift = this.hourlyRate * this.hoursPerShift;
      this.costPerDay = this.costPerShift * this.shiftsPerDay;
      this.costPerPeriod = this.costPerDay * this.rentalDays;
      this.totalBudget = this.costPerPeriod * this.equipmentQuantity;
      this.$emit("budget-calculated", { from: this.totalBudget, to: this.totalBudget });
    },
    resetCalculation() {
      this.totalBudget = 0;
      this.costPerShift = 0;
      this.costPerDay = 0;
      this.costPerPeriod = 0;
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat("ru-RU", {
        style: "currency",
        currency: "RUB",
        minimumFractionDigits: 0
      }).format(amount);
    }
  }
};
const _hoisted_1$2 = { class: "budget-calculator card" };
const _hoisted_2$2 = { class: "card-body" };
const _hoisted_3$2 = { class: "calculation-details mb-3" };
const _hoisted_4$2 = { class: "row g-2 text-center" };
const _hoisted_5$2 = { class: "col-md-2" };
const _hoisted_6$2 = { class: "fw-bold text-primary" };
const _hoisted_7$2 = { class: "col-md-2" };
const _hoisted_8$2 = { class: "fw-bold" };
const _hoisted_9$2 = { class: "col-md-2" };
const _hoisted_10$2 = { class: "fw-bold" };
const _hoisted_11$2 = { class: "col-md-2" };
const _hoisted_12$2 = { class: "fw-bold" };
const _hoisted_13$1 = { class: "col-md-2" };
const _hoisted_14$1 = { class: "fw-bold" };
const _hoisted_15$1 = { class: "col-md-2" };
const _hoisted_16$1 = { class: "fw-bold text-success" };
const _hoisted_17$1 = {
  key: 0,
  class: "budget-result"
};
const _hoisted_18$1 = { class: "alert alert-success" };
const _hoisted_19$1 = { class: "text-center" };
const _hoisted_20$1 = { class: "h3 mb-0 mt-1" };
const _hoisted_21$1 = { class: "calculation-breakdown" };
const _hoisted_22$1 = { class: "calculation-steps" };
const _hoisted_23$1 = { class: "step" };
const _hoisted_24$1 = { class: "step" };
const _hoisted_25$1 = { class: "step" };
const _hoisted_26$1 = { class: "step" };
const _hoisted_27$1 = {
  key: 1,
  class: "text-center text-muted py-3"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$2, [
    _cache[10] || (_cache[10] = createBaseVNode("div", { class: "card-header bg-light" }, [
      createBaseVNode("h6", { class: "mb-0" }, [
        createBaseVNode("i", { class: "fas fa-calculator me-2" }),
        createTextVNode("Калькулятор бюджета ")
      ])
    ], -1)),
    createBaseVNode("div", _hoisted_2$2, [
      createBaseVNode("div", _hoisted_3$2, [
        createBaseVNode("div", _hoisted_4$2, [
          createBaseVNode("div", _hoisted_5$2, [
            _cache[0] || (_cache[0] = createBaseVNode("small", { class: "text-muted" }, "Час", -1)),
            createBaseVNode("div", _hoisted_6$2, toDisplayString($options.formatCurrency($props.hourlyRate)), 1)
          ]),
          createBaseVNode("div", _hoisted_7$2, [
            _cache[1] || (_cache[1] = createBaseVNode("small", { class: "text-muted" }, "× Часов/смену", -1)),
            createBaseVNode("div", _hoisted_8$2, toDisplayString($options.hoursPerShift), 1)
          ]),
          createBaseVNode("div", _hoisted_9$2, [
            _cache[2] || (_cache[2] = createBaseVNode("small", { class: "text-muted" }, "× Смен/день", -1)),
            createBaseVNode("div", _hoisted_10$2, toDisplayString($options.shiftsPerDay), 1)
          ]),
          createBaseVNode("div", _hoisted_11$2, [
            _cache[3] || (_cache[3] = createBaseVNode("small", { class: "text-muted" }, "× Дней", -1)),
            createBaseVNode("div", _hoisted_12$2, toDisplayString($options.rentalDays), 1)
          ]),
          createBaseVNode("div", _hoisted_13$1, [
            _cache[4] || (_cache[4] = createBaseVNode("small", { class: "text-muted" }, "× Количество", -1)),
            createBaseVNode("div", _hoisted_14$1, toDisplayString($props.equipmentQuantity), 1)
          ]),
          createBaseVNode("div", _hoisted_15$1, [
            _cache[5] || (_cache[5] = createBaseVNode("small", { class: "text-muted" }, "= ИТОГО", -1)),
            createBaseVNode("div", _hoisted_16$1, toDisplayString($options.formatCurrency($data.totalBudget)), 1)
          ])
        ])
      ]),
      $data.totalBudget > 0 ? (openBlock(), createElementBlock("div", _hoisted_17$1, [
        createBaseVNode("div", _hoisted_18$1, [
          createBaseVNode("div", _hoisted_19$1, [
            _cache[6] || (_cache[6] = createBaseVNode("strong", null, "Общий бюджет заявки:", -1)),
            createBaseVNode("div", _hoisted_20$1, toDisplayString($options.formatCurrency($data.totalBudget)), 1),
            _cache[7] || (_cache[7] = createBaseVNode("small", { class: "text-muted" }, "Точный расчет на основе введенных параметров", -1))
          ])
        ]),
        createBaseVNode("div", _hoisted_21$1, [
          _cache[8] || (_cache[8] = createBaseVNode("h6", { class: "text-muted mb-2" }, "Детали расчета:", -1)),
          createBaseVNode("div", _hoisted_22$1, [
            createBaseVNode("div", _hoisted_23$1, "Стоимость смены: " + toDisplayString($options.formatCurrency($props.hourlyRate)) + " × " + toDisplayString($options.hoursPerShift) + " = " + toDisplayString($options.formatCurrency($data.costPerShift)), 1),
            createBaseVNode("div", _hoisted_24$1, "Стоимость дня: " + toDisplayString($options.formatCurrency($data.costPerShift)) + " × " + toDisplayString($options.shiftsPerDay) + " = " + toDisplayString($options.formatCurrency($data.costPerDay)), 1),
            createBaseVNode("div", _hoisted_25$1, "Стоимость периода: " + toDisplayString($options.formatCurrency($data.costPerDay)) + " × " + toDisplayString($options.rentalDays) + " = " + toDisplayString($options.formatCurrency($data.costPerPeriod)), 1),
            createBaseVNode("div", _hoisted_26$1, "Общая стоимость: " + toDisplayString($options.formatCurrency($data.costPerPeriod)) + " × " + toDisplayString($props.equipmentQuantity) + " = " + toDisplayString($options.formatCurrency($data.totalBudget)), 1)
          ])
        ])
      ])) : (openBlock(), createElementBlock("div", _hoisted_27$1, [..._cache[9] || (_cache[9] = [
        createBaseVNode("i", { class: "fas fa-calculator fa-2x mb-2" }, null, -1),
        createBaseVNode("p", null, "Заполните данные для расчета бюджета", -1)
      ])]))
    ])
  ]);
}
const BudgetCalculator = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-055befa9"]]);
const _sfc_main$1 = {
  name: "LocationSelector",
  props: {
    existingLocations: {
      type: Array,
      default: () => []
    },
    value: {
      type: Number,
      default: null
    }
  },
  data() {
    return {
      selectedLocationId: this.value,
      showNewLocationForm: false,
      newLocation: {
        name: "",
        address: "",
        latitude: "",
        longitude: ""
      },
      selectedLocation: null
    };
  },
  computed: {
    isNewLocationValid() {
      return this.newLocation.name.trim() && this.newLocation.address.trim();
    }
  },
  watch: {
    value(newVal) {
      this.selectedLocationId = newVal;
      this.updateSelectedLocation();
    }
  },
  methods: {
    initAddressAutocomplete() {
      console.log("Address autocomplete initialization");
    },
    // ⚠️ ИСПРАВЛЕНИЕ: Обновленный метод onLocationChange с обработкой ошибок
    onLocationChange(event) {
      try {
        const selectedId = event.target.value;
        console.log("LocationSelector: selected ID:", selectedId);
        if (!selectedId) {
          this.$emit("input", null);
          this.$emit("location-selected", null);
          this.selectedLocation = null;
          return;
        }
        if (selectedId === "new") {
          this.showNewLocationForm = true;
          this.selectedLocationId = null;
          this.$emit("input", null);
          this.$emit("location-selected", null);
          this.selectedLocation = null;
        } else {
          this.showNewLocationForm = false;
          const locationId = parseInt(selectedId);
          this.$emit("input", locationId);
          this.updateSelectedLocation();
          if (this.selectedLocation) {
            this.$emit("location-selected", this.selectedLocation);
          } else {
            console.warn("Location not found for ID:", locationId);
            this.$emit("location-selected", null);
          }
        }
      } catch (error) {
        console.error("Error in LocationSelector:", error);
        this.$emit("input", null);
        this.$emit("location-selected", null);
        this.selectedLocation = null;
      }
    },
    saveNewLocation() {
      return __async(this, null, function* () {
        if (!this.isNewLocationValid) return;
        try {
          const response = yield fetch("/lessee/locations", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
              "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify(this.newLocation)
          });
          const data = yield response.json();
          if (data.success) {
            this.$emit("location-created", data.location);
            this.selectedLocationId = data.location.id;
            this.$emit("input", data.location.id);
            this.cancelNewLocation();
            this.$emit("location-selected", data.location);
            console.log("New location created and selected:", data.location.id);
          } else {
            alert("Ошибка при создании локации: " + data.message);
          }
        } catch (error) {
          console.error("Error:", error);
          alert("Произошла ошибка при создании локации");
        }
      });
    },
    cancelNewLocation() {
      this.showNewLocationForm = false;
      this.newLocation = {
        name: "",
        address: "",
        latitude: "",
        longitude: ""
      };
      this.selectedLocationId = null;
    },
    updateSelectedLocation() {
      if (this.selectedLocationId) {
        this.selectedLocation = this.existingLocations.find(
          (loc) => loc.id === this.selectedLocationId
        );
      } else {
        this.selectedLocation = null;
      }
    }
  },
  mounted() {
    this.updateSelectedLocation();
    this.initAddressAutocomplete();
  }
};
const _hoisted_1$1 = { class: "location-selector" };
const _hoisted_2$1 = { class: "mb-3" };
const _hoisted_3$1 = ["value"];
const _hoisted_4$1 = {
  key: 0,
  class: "card p-3 mb-3"
};
const _hoisted_5$1 = { class: "row g-3" };
const _hoisted_6$1 = { class: "col-md-6" };
const _hoisted_7$1 = { class: "col-md-6" };
const _hoisted_8$1 = { class: "col-md-6" };
const _hoisted_9$1 = { class: "col-md-6" };
const _hoisted_10$1 = { class: "col-12" };
const _hoisted_11$1 = ["disabled"];
const _hoisted_12$1 = {
  key: 1,
  class: "alert alert-info"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    _cache[18] || (_cache[18] = createBaseVNode("label", { class: "form-label" }, "Локация объекта *", -1)),
    createBaseVNode("div", _hoisted_2$1, [
      withDirectives(createBaseVNode("select", {
        class: "form-select",
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.selectedLocationId = $event),
        onChange: _cache[1] || (_cache[1] = (...args) => $options.onLocationChange && $options.onLocationChange(...args))
      }, [
        _cache[8] || (_cache[8] = createBaseVNode("option", { value: "" }, "Выберите существующую локацию", -1)),
        (openBlock(true), createElementBlock(Fragment, null, renderList($props.existingLocations, (location) => {
          return openBlock(), createElementBlock("option", {
            key: location.id,
            value: location.id
          }, toDisplayString(location.name) + " - " + toDisplayString(location.address), 9, _hoisted_3$1);
        }), 128)),
        _cache[9] || (_cache[9] = createBaseVNode("option", { value: "new" }, "+ Добавить новую локацию", -1))
      ], 544), [
        [vModelSelect, $data.selectedLocationId]
      ])
    ]),
    $data.showNewLocationForm ? (openBlock(), createElementBlock("div", _hoisted_4$1, [
      _cache[15] || (_cache[15] = createBaseVNode("h6", { class: "mb-3" }, "Добавить новую локацию", -1)),
      createBaseVNode("div", _hoisted_5$1, [
        createBaseVNode("div", _hoisted_6$1, [
          _cache[10] || (_cache[10] = createBaseVNode("label", { class: "form-label" }, "Название локации *", -1)),
          withDirectives(createBaseVNode("input", {
            type: "text",
            class: "form-control",
            "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.newLocation.name = $event),
            placeholder: "Например: Строительная площадка №1"
          }, null, 512), [
            [vModelText, $data.newLocation.name]
          ])
        ]),
        createBaseVNode("div", _hoisted_7$1, [
          _cache[11] || (_cache[11] = createBaseVNode("label", { class: "form-label" }, "Адрес *", -1)),
          withDirectives(createBaseVNode("input", {
            type: "text",
            class: "form-control",
            "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.newLocation.address = $event),
            placeholder: "Начните вводить адрес"
          }, null, 512), [
            [vModelText, $data.newLocation.address]
          ])
        ]),
        createBaseVNode("div", _hoisted_8$1, [
          _cache[12] || (_cache[12] = createBaseVNode("label", { class: "form-label" }, "Широта", -1)),
          withDirectives(createBaseVNode("input", {
            type: "text",
            class: "form-control",
            "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.newLocation.latitude = $event),
            placeholder: "55.7558"
          }, null, 512), [
            [vModelText, $data.newLocation.latitude]
          ])
        ]),
        createBaseVNode("div", _hoisted_9$1, [
          _cache[13] || (_cache[13] = createBaseVNode("label", { class: "form-label" }, "Долгота", -1)),
          withDirectives(createBaseVNode("input", {
            type: "text",
            class: "form-control",
            "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.newLocation.longitude = $event),
            placeholder: "37.6173"
          }, null, 512), [
            [vModelText, $data.newLocation.longitude]
          ])
        ]),
        createBaseVNode("div", _hoisted_10$1, [
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-success btn-sm",
            onClick: _cache[6] || (_cache[6] = (...args) => $options.saveNewLocation && $options.saveNewLocation(...args)),
            disabled: !$options.isNewLocationValid
          }, [..._cache[14] || (_cache[14] = [
            createBaseVNode("i", { class: "fas fa-save me-1" }, null, -1),
            createTextVNode("Сохранить локацию ", -1)
          ])], 8, _hoisted_11$1),
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-outline-secondary btn-sm ms-2",
            onClick: _cache[7] || (_cache[7] = (...args) => $options.cancelNewLocation && $options.cancelNewLocation(...args))
          }, " Отмена ")
        ])
      ])
    ])) : createCommentVNode("", true),
    $data.selectedLocation ? (openBlock(), createElementBlock("div", _hoisted_12$1, [
      _cache[16] || (_cache[16] = createBaseVNode("strong", null, "Выбрана локация:", -1)),
      createTextVNode(" " + toDisplayString($data.selectedLocation.name), 1),
      _cache[17] || (_cache[17] = createBaseVNode("br", null, null, -1)),
      createBaseVNode("small", null, toDisplayString($data.selectedLocation.address), 1)
    ])) : createCommentVNode("", true)
  ]);
}
const LocationSelector = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "CreateRentalRequestForm",
  components: {
    RequestItems,
    RentalConditions,
    BudgetCalculator,
    LocationSelector
  },
  props: {
    categories: {
      type: Array,
      required: true,
      default: () => []
    },
    locations: {
      type: Array,
      required: true,
      default: () => []
    },
    storeUrl: {
      type: String,
      required: true,
      default: ""
    },
    editMode: {
      type: Boolean,
      default: false
    },
    initialData: {
      type: Object,
      default: null
    },
    requestId: {
      type: [String, Number],
      default: null
    },
    csrfToken: {
      type: String,
      required: true,
      default: ""
    }
  },
  data() {
    const defaultFormData = {
      title: "",
      description: "",
      hourly_rate: 0,
      rental_period_start: "",
      rental_period_end: "",
      location_id: "",
      rental_conditions: this.getDefaultConditions(),
      items: [],
      delivery_required: false
      // 🔥 ЯВНО УКАЗЫВАЕМ false по умолчанию
    };
    return {
      formData: this.editMode && this.initialData ? __spreadValues(__spreadValues({}, defaultFormData), this.initialData) : __spreadValues({}, defaultFormData),
      activeField: "",
      loading: false,
      totalBudget: 0,
      totalQuantity: 0,
      minDate: (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
      submitting: false,
      error: null,
      generalHourlyRate: 0
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
    formattedBudget() {
      if (typeof this.totalBudget !== "number" || isNaN(this.totalBudget)) {
        return "0 ₽";
      }
      return this.formatCurrency(this.totalBudget);
    }
  },
  watch: {
    "formData.hourly_rate": {
      handler(newRate) {
        console.log("🔄 hourly_rate изменен:", newRate, typeof newRate);
        this.generalHourlyRate = this.ensureNumber(newRate);
      },
      immediate: true
    }
  },
  methods: {
    onHourlyRateChange(value) {
      console.log("🔧 Обработка изменения hourly rate:", value);
      const numValue = value === "" ? 0 : Number(value);
      this.formData.hourly_rate = isNaN(numValue) ? 0 : numValue;
      this.generalHourlyRate = this.formData.hourly_rate;
    },
    ensureNumber(value) {
      if (value === null || value === void 0 || value === "") {
        return 0;
      }
      const num = Number(value);
      return isNaN(num) ? 0 : num;
    },
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
    getDefaultFormData() {
      return {
        title: "",
        description: "",
        hourly_rate: 0,
        rental_period_start: "",
        rental_period_end: "",
        location_id: "",
        rental_conditions: this.getDefaultConditions(),
        items: [{
          category_id: null,
          quantity: 1,
          hourly_rate: null,
          use_individual_conditions: false,
          individual_conditions: {},
          specifications: {}
        }],
        delivery_required: false
      };
    },
    deepProcessFormData(data) {
      const processValue = (value) => {
        if (value === "" || value === null || value === void 0) {
          return null;
        }
        if (typeof value === "number") {
          return value;
        }
        if (typeof value === "string") {
          const num = Number(value);
          return isNaN(num) ? value : num;
        }
        if (Array.isArray(value)) {
          return value.map((item) => this.deepProcessFormData(item));
        }
        if (typeof value === "object") {
          const result = {};
          Object.keys(value).forEach((key) => {
            if (key === "specifications" || key.startsWith("custom_")) {
              result[key] = this.processSpecifications(value[key]);
            } else {
              result[key] = this.deepProcessFormData(value[key]);
            }
          });
          return result;
        }
        return value;
      };
      return processValue(data);
    },
    processSpecifications(specs) {
      if (!specs || typeof specs !== "object") {
        return {};
      }
      const processed = {};
      if (specs.values && typeof specs.values === "object") {
        Object.keys(specs.values).forEach((key) => {
          const value = specs.values[key];
          processed[key] = this.convertToNumberOrNull(value);
        });
      } else {
        Object.keys(specs).forEach((key) => {
          const value = specs[key];
          processed[key] = this.convertToNumberOrNull(value);
        });
      }
      return processed;
    },
    convertToNumberOrNull(value) {
      if (value === "" || value === null || value === void 0) {
        return null;
      }
      const num = Number(value);
      return isNaN(num) ? null : num;
    },
    onItemsUpdated(items) {
      this.formData.items = items;
      this.totalQuantity = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
      this.calculateTotalBudget();
    },
    onTotalBudgetUpdated(budget) {
      this.totalBudget = budget;
    },
    onConditionsUpdated(conditions) {
      this.formData.rental_conditions = conditions;
      this.calculateTotalBudget();
    },
    calculateTotalBudget() {
      if (this.formData.items.length === 0) {
        this.totalBudget = 0;
        return;
      }
      let total = 0;
      const days = this.rentalDays;
      const hourlyRate = this.ensureNumber(this.formData.hourly_rate);
      this.formData.items.forEach((item) => {
        const itemHourlyRate = item.hourly_rate ? this.ensureNumber(item.hourly_rate) : hourlyRate;
        total += itemHourlyRate * 8 * 1 * days * item.quantity;
      });
      this.totalBudget = total;
    },
    setActiveField(fieldName) {
      this.activeField = fieldName;
    },
    clearActiveField() {
      this.activeField = "";
    },
    onLocationCreated(newLocation) {
      this.locations.push(newLocation);
    },
    onLocationSelected(location) {
      console.log("Selected location:", location);
      if (location && location.id) {
        console.log("Selected location id:", location.id);
        this.formData.location_id = location.id;
      } else {
        console.log("Location is null, resetting location_id");
        this.formData.location_id = null;
      }
    },
    submitForm() {
      return __async(this, null, function* () {
        var _a, _b;
        try {
          this.error = null;
          if (this.editMode) {
            yield this.updateRequest();
          } else {
            yield this.createRequest();
          }
        } catch (error) {
          console.error("Ошибка при отправке формы:", error);
          this.error = error.message || "Произошла ошибка при отправке формы";
          if ((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.errors) {
            console.error("Детали ошибки:", error.response.data.errors);
          }
        }
      });
    },
    createRequest() {
      return __async(this, null, function* () {
        var _a;
        this.$emit("loading-start");
        this.submitting = true;
        if (!this.isFormValid) {
          this.error = "Пожалуйста, заполните все обязательные поля и добавьте хотя бы одну позицию";
          this.$emit("loading-end");
          this.submitting = false;
          return;
        }
        try {
          const preparedData = this.prepareFormData();
          console.log("🚚 Данные доставки при отправке:", {
            delivery_required: preparedData.delivery_required,
            type: typeof preparedData.delivery_required,
            value: preparedData.delivery_required
          });
          console.log("📤 Final data for create request:", {
            delivery_required: preparedData.delivery_required,
            full_data: preparedData
          });
          const response = yield fetch(this.storeUrl, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": this.csrfToken,
              "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify(preparedData)
          });
          const data = yield response.json();
          if (data.success) {
            console.log("✅ Заявка создана успешно:", {
              request_id: data.request_id,
              delivery_required_in_response: (_a = data.data) == null ? void 0 : _a.delivery_required
            });
            this.$emit("saved", data.data);
            window.location.href = data.redirect_url;
          } else {
            throw new Error(data.message || "Ошибка при создании заявки");
          }
        } catch (error) {
          console.error("Error:", error);
          this.error = error.message || "Произошла ошибка при создании заявки";
          throw error;
        } finally {
          this.submitting = false;
          this.$emit("loading-end");
        }
      });
    },
    updateRequest() {
      return __async(this, null, function* () {
        this.submitting = true;
        try {
          const response = yield fetch(`/api/lessee/rental-requests/${this.requestId}`, {
            method: "PUT",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": this.csrfToken,
              "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify(this.prepareFormData())
          });
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          const data = yield response.json();
          if (data.success) {
            this.$emit("saved", data.data);
          } else {
            throw new Error(data.message || "Ошибка при обновлении заявки");
          }
        } catch (error) {
          console.error("Update error:", error);
          this.error = error.message || "Произошла ошибка при обновлении заявки";
          throw error;
        } finally {
          this.submitting = false;
        }
      });
    },
    prepareFormData() {
      let formData = {
        title: this.formData.title,
        description: this.formData.description,
        hourly_rate: this.ensureNumber(this.formData.hourly_rate),
        rental_period_start: this.formData.rental_period_start,
        rental_period_end: this.formData.rental_period_end,
        location_id: this.formData.location_id,
        rental_conditions: this.formData.rental_conditions,
        // 🔥 ГАРАНТИРУЕМ ПРАВИЛЬНЫЙ ФОРМАТ ДЛЯ delivery_required
        delivery_required: Boolean(this.formData.delivery_required),
        items: this.formData.items.map((item) => {
          const preparedItem = {
            category_id: item.category_id,
            quantity: parseInt(item.quantity) || 1,
            hourly_rate: item.hourly_rate ? this.ensureNumber(item.hourly_rate) : null,
            use_individual_conditions: Boolean(item.use_individual_conditions),
            individual_conditions: item.use_individual_conditions ? item.individual_conditions : {}
          };
          if (item.specifications) {
            const { standard = {}, custom = {} } = this.prepareSpecifications(item.specifications);
            preparedItem.standard_specifications = standard;
            preparedItem.custom_specifications = custom;
            preparedItem.specifications = __spreadValues(__spreadValues({}, standard), this.extractCustomValues(custom));
            const customMetadata = {};
            Object.keys(custom).forEach((key) => {
              const spec = custom[key];
              customMetadata[key] = {
                name: spec.label || key,
                dataType: spec.dataType || "string",
                unit: spec.unit || ""
              };
            });
            preparedItem.custom_specs_metadata = customMetadata;
          } else {
            preparedItem.standard_specifications = {};
            preparedItem.custom_specifications = {};
            preparedItem.specifications = {};
            preparedItem.custom_specs_metadata = {};
          }
          console.log("📦 Prepared item specs:", {
            standard: Object.keys(preparedItem.standard_specifications),
            custom: Object.keys(preparedItem.custom_specifications),
            legacy: Object.keys(preparedItem.specifications)
          });
          return preparedItem;
        })
      };
      if (this.editMode) {
        formData._method = "PUT";
      }
      console.log("📤 Final prepared form data:", formData);
      return formData;
    },
    prepareSpecifications(specs) {
      if (!specs || typeof specs !== "object") {
        return { standard: {}, custom: {} };
      }
      const standard = {};
      const custom = {};
      Object.keys(specs).forEach((key) => {
        const value = specs[key];
        if (this.isStandardSpecification(key)) {
          standard[key] = this.normalizeSpecValue(value);
        } else {
          if (typeof value === "object" && value !== null) {
            custom[key] = {
              label: value.label || key,
              value: this.normalizeSpecValue(value.value),
              unit: value.unit || "",
              dataType: value.dataType || "string"
            };
          } else {
            custom[key] = {
              label: this.formatLabel(key),
              value: this.normalizeSpecValue(value),
              unit: "",
              dataType: typeof value === "number" ? "number" : "string"
            };
          }
        }
      });
      return { standard, custom };
    },
    isStandardSpecification(key) {
      const standardKeys = [
        "bucket_volume",
        "max_digging_depth",
        "power",
        "weight",
        "engine_power",
        "lifting_capacity",
        "boom_length"
      ];
      return standardKeys.includes(key) || !key.startsWith("custom_");
    },
    normalizeSpecValue(value) {
      if (value === null || value === void 0 || value === "") {
        return null;
      }
      if (typeof value === "string" && value.includes(",")) {
        const numValue = parseFloat(value.replace(",", "."));
        return isNaN(numValue) ? value : numValue;
      }
      if (typeof value === "string" && !isNaN(value) && value.trim() !== "") {
        return parseFloat(value);
      }
      return value;
    },
    extractCustomValues(customSpecs) {
      const values = {};
      Object.keys(customSpecs).forEach((key) => {
        values[key] = customSpecs[key].value;
      });
      return values;
    },
    formatLabel(key) {
      return key.replace(/_/g, " ").replace(/(?:^|\s)\S/g, (char) => char.toUpperCase());
    },
    cancel() {
      if (confirm("Отменить создание заявки?")) {
        if (this.editMode) {
          this.$emit("cancelled");
        } else {
          window.history.back();
        }
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat("ru-RU", {
        style: "currency",
        currency: "RUB",
        minimumFractionDigits: 0
      }).format(amount);
    },
    initializeFormWithData() {
      if (this.editMode && this.initialData) {
        console.log("Initializing form with data:", this.initialData);
      }
    }
  },
  mounted() {
    var _a, _b;
    console.log("CreateRentalRequestForm mounted", {
      editMode: this.editMode,
      requestId: this.requestId,
      categories: (_a = this.categories) == null ? void 0 : _a.length,
      locations: (_b = this.locations) == null ? void 0 : _b.length,
      formData: this.formData,
      generalHourlyRate: this.generalHourlyRate,
      hourly_rate_type: typeof this.formData.hourly_rate,
      delivery_required: this.formData.delivery_required,
      delivery_required_type: typeof this.formData.delivery_required
    });
    this.generalHourlyRate = this.ensureNumber(this.formData.hourly_rate);
    if (this.editMode) {
      this.initializeFormWithData();
    }
  }
};
const _hoisted_1 = { class: "create-rental-request" };
const _hoisted_2 = {
  key: 0,
  class: "text-center py-5"
};
const _hoisted_3 = {
  key: 1,
  class: "alert alert-danger"
};
const _hoisted_4 = { key: 2 };
const _hoisted_5 = { class: "card mb-4" };
const _hoisted_6 = { class: "card-body" };
const _hoisted_7 = { class: "row g-3" };
const _hoisted_8 = { class: "col-md-12" };
const _hoisted_9 = { class: "col-md-12" };
const _hoisted_10 = { class: "col-md-6" };
const _hoisted_11 = ["min"];
const _hoisted_12 = { class: "col-md-6" };
const _hoisted_13 = ["min"];
const _hoisted_14 = { class: "col-md-6" };
const _hoisted_15 = { class: "col-md-6" };
const _hoisted_16 = { class: "col-12" };
const _hoisted_17 = { class: "form-check form-switch" };
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
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_location_selector = resolveComponent("location-selector");
  const _component_RequestItems = resolveComponent("RequestItems");
  const _component_RentalConditions = resolveComponent("RentalConditions");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    $data.loading && $props.editMode ? (openBlock(), createElementBlock("div", _hoisted_2, [..._cache[10] || (_cache[10] = [
      createBaseVNode("div", {
        class: "spinner-border text-primary",
        role: "status"
      }, [
        createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
      ], -1),
      createBaseVNode("p", { class: "mt-2" }, "Загрузка данных заявки...", -1)
    ])])) : createCommentVNode("", true),
    $data.error ? (openBlock(), createElementBlock("div", _hoisted_3, [
      _cache[11] || (_cache[11] = createBaseVNode("strong", null, "Ошибка:", -1)),
      createTextVNode(" " + toDisplayString($data.error), 1)
    ])) : (openBlock(), createElementBlock("div", _hoisted_4, [
      createBaseVNode("form", {
        onSubmit: _cache[9] || (_cache[9] = withModifiers((...args) => $options.submitForm && $options.submitForm(...args), ["prevent"]))
      }, [
        createBaseVNode("div", _hoisted_5, [
          _cache[20] || (_cache[20] = createBaseVNode("div", { class: "card-header" }, [
            createBaseVNode("h5", { class: "card-title mb-0" }, "Основная информация")
          ], -1)),
          createBaseVNode("div", _hoisted_6, [
            createBaseVNode("div", _hoisted_7, [
              createBaseVNode("div", _hoisted_8, [
                _cache[12] || (_cache[12] = createBaseVNode("label", { class: "form-label" }, "Название заявки *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "text",
                  class: "form-control",
                  "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.formData.title = $event),
                  required: ""
                }, null, 512), [
                  [vModelText, $data.formData.title]
                ])
              ]),
              createBaseVNode("div", _hoisted_9, [
                _cache[13] || (_cache[13] = createBaseVNode("label", { class: "form-label" }, "Описание *", -1)),
                withDirectives(createBaseVNode("textarea", {
                  class: "form-control",
                  "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.formData.description = $event),
                  rows: "4",
                  required: ""
                }, null, 512), [
                  [vModelText, $data.formData.description]
                ])
              ]),
              createBaseVNode("div", _hoisted_10, [
                _cache[14] || (_cache[14] = createBaseVNode("label", { class: "form-label" }, "Дата начала *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "date",
                  class: "form-control",
                  "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.formData.rental_period_start = $event),
                  min: $data.minDate,
                  required: ""
                }, null, 8, _hoisted_11), [
                  [vModelText, $data.formData.rental_period_start]
                ])
              ]),
              createBaseVNode("div", _hoisted_12, [
                _cache[15] || (_cache[15] = createBaseVNode("label", { class: "form-label" }, "Дата окончания *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "date",
                  class: "form-control",
                  "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.formData.rental_period_end = $event),
                  min: $data.formData.rental_period_start,
                  required: ""
                }, null, 8, _hoisted_13), [
                  [vModelText, $data.formData.rental_period_end]
                ])
              ]),
              createBaseVNode("div", _hoisted_14, [
                createVNode(_component_location_selector, {
                  "existing-locations": $props.locations,
                  modelValue: $data.formData.location_id,
                  "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.formData.location_id = $event),
                  onLocationCreated: $options.onLocationCreated,
                  onLocationSelected: $options.onLocationSelected
                }, null, 8, ["existing-locations", "modelValue", "onLocationCreated", "onLocationSelected"])
              ]),
              createBaseVNode("div", _hoisted_15, [
                _cache[16] || (_cache[16] = createBaseVNode("label", { class: "form-label" }, "Базовая стоимость часа (₽) *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "number",
                  class: "form-control",
                  "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.formData.hourly_rate = $event),
                  min: "0",
                  step: "50",
                  onChange: _cache[6] || (_cache[6] = ($event) => $options.onHourlyRateChange($event.target.value)),
                  required: ""
                }, null, 544), [
                  [
                    vModelText,
                    $data.formData.hourly_rate,
                    void 0,
                    { number: true }
                  ]
                ]),
                _cache[17] || (_cache[17] = createBaseVNode("small", { class: "text-muted" }, "Будет использована для позиций без индивидуальной стоимости", -1))
              ]),
              createBaseVNode("div", _hoisted_16, [
                createBaseVNode("div", _hoisted_17, [
                  withDirectives(createBaseVNode("input", {
                    class: "form-check-input",
                    type: "checkbox",
                    "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.formData.delivery_required = $event),
                    id: "delivery_required",
                    "true-value": "1",
                    "false-value": "0"
                  }, null, 512), [
                    [vModelCheckbox, $data.formData.delivery_required]
                  ]),
                  _cache[18] || (_cache[18] = createBaseVNode("label", {
                    class: "form-check-label",
                    for: "delivery_required"
                  }, [
                    createBaseVNode("i", { class: "fas fa-truck me-2" }),
                    createTextVNode("Требуется доставка техники к объекту ")
                  ], -1)),
                  _cache[19] || (_cache[19] = createBaseVNode("small", { class: "form-text text-muted d-block" }, " Отметьте, если вам необходима доставка оборудования к месту проведения работ. Это повлияет на расчет стоимости аренды. ", -1))
                ])
              ])
            ])
          ])
        ]),
        createVNode(_component_RequestItems, {
          categories: $props.categories,
          "general-hourly-rate": $data.generalHourlyRate,
          "general-conditions": $data.formData.rental_conditions,
          "rental-period": $options.rentalPeriod,
          onItemsUpdated: $options.onItemsUpdated,
          onTotalBudgetUpdated: $options.onTotalBudgetUpdated
        }, null, 8, ["categories", "general-hourly-rate", "general-conditions", "rental-period", "onItemsUpdated", "onTotalBudgetUpdated"]),
        createBaseVNode("div", _hoisted_18, [
          _cache[21] || (_cache[21] = createBaseVNode("div", { class: "card-header" }, [
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
          _cache[23] || (_cache[23] = createBaseVNode("div", { class: "card-header bg-success text-white" }, [
            createBaseVNode("h5", { class: "card-title mb-0" }, [
              createBaseVNode("i", { class: "fas fa-calculator me-2" }),
              createTextVNode("Итоговый бюджет заявки ")
            ])
          ], -1)),
          createBaseVNode("div", _hoisted_21, [
            createBaseVNode("div", _hoisted_22, toDisplayString($options.formattedBudget), 1),
            createBaseVNode("p", _hoisted_23, [
              createTextVNode(" Общая стоимость для " + toDisplayString($data.totalQuantity) + " единиц техники на период " + toDisplayString($options.rentalDays) + " дней ", 1),
              $data.formData.delivery_required ? (openBlock(), createElementBlock("span", _hoisted_24, [..._cache[22] || (_cache[22] = [
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
            createTextVNode(" " + toDisplayString($props.editMode ? "Обновить заявку" : "Создать заявку"), 1)
          ], 8, _hoisted_26),
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-outline-secondary ms-2",
            onClick: _cache[8] || (_cache[8] = ($event) => _ctx.$emit("cancelled"))
          }, " Отмена ")
        ])
      ], 32)
    ]))
  ]);
}
const CreateRentalRequestForm = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  BudgetCalculator as B,
  CreateRentalRequestForm as C
};
