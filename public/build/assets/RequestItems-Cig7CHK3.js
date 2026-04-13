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
import { a as createElementBlock, o as openBlock, b as createBaseVNode, e as createCommentVNode, w as withDirectives, v as vModelSelect, j as vModelText, s as vModelCheckbox, d as createTextVNode, t as toDisplayString, F as Fragment, r as renderList, y as vModelDynamic, g as resolveComponent, i as createVNode } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main$2 = {
  name: "RentalConditions",
  props: {
    initialConditions: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ["conditions-updated"],
  data() {
    return {
      conditions: __spreadValues(__spreadValues({}, this.getDefaultConditions()), this.initialConditions)
    };
  },
  computed: {
    totalHoursPerDay() {
      return this.conditions.hours_per_shift * this.conditions.shifts_per_day;
    },
    showCalculation() {
      return this.conditions.hours_per_shift > 0 && this.conditions.shifts_per_day > 0;
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
    updateConditions() {
      this.$emit("conditions-updated", this.conditions);
    },
    resetToDefaults() {
      this.conditions = this.getDefaultConditions();
      this.updateConditions();
    }
  },
  watch: {
    initialConditions: {
      handler(newConditions) {
        this.conditions = __spreadValues(__spreadValues({}, this.getDefaultConditions()), newConditions);
      },
      deep: true
    }
  }
};
const _hoisted_1$2 = { class: "rental-conditions" };
const _hoisted_2$2 = { class: "row g-3" };
const _hoisted_3$2 = { class: "col-md-6" };
const _hoisted_4$2 = { class: "col-md-3" };
const _hoisted_5$2 = { class: "col-md-3" };
const _hoisted_6$2 = { class: "col-md-6" };
const _hoisted_7$2 = { class: "col-md-6" };
const _hoisted_8$2 = { class: "col-md-4" };
const _hoisted_9$2 = { class: "form-check form-switch" };
const _hoisted_10$2 = { class: "col-md-4" };
const _hoisted_11$2 = { class: "form-check form-switch" };
const _hoisted_12$2 = { class: "col-md-4" };
const _hoisted_13$2 = { class: "form-check form-switch" };
const _hoisted_14$2 = {
  key: 0,
  class: "calculation-info mt-4 p-3 bg-light rounded"
};
const _hoisted_15$2 = { class: "text-muted" };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$2, [
    createBaseVNode("div", _hoisted_2$2, [
      createBaseVNode("div", _hoisted_3$2, [
        _cache[17] || (_cache[17] = createBaseVNode("label", { class: "form-label" }, "Тип оплаты", -1)),
        withDirectives(createBaseVNode("select", {
          class: "form-select",
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.conditions.payment_type = $event),
          onChange: _cache[1] || (_cache[1] = (...args) => $options.updateConditions && $options.updateConditions(...args))
        }, [..._cache[16] || (_cache[16] = [
          createBaseVNode("option", { value: "hourly" }, "Почасовая", -1),
          createBaseVNode("option", { value: "shift" }, "Посменная", -1),
          createBaseVNode("option", { value: "daily" }, "Посуточная", -1)
        ])], 544), [
          [vModelSelect, $data.conditions.payment_type]
        ])
      ]),
      createBaseVNode("div", _hoisted_4$2, [
        _cache[18] || (_cache[18] = createBaseVNode("label", { class: "form-label" }, "Часов в смене", -1)),
        withDirectives(createBaseVNode("input", {
          type: "number",
          class: "form-control",
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.conditions.hours_per_shift = $event),
          min: "1",
          max: "24",
          onInput: _cache[3] || (_cache[3] = (...args) => $options.updateConditions && $options.updateConditions(...args))
        }, null, 544), [
          [
            vModelText,
            $data.conditions.hours_per_shift,
            void 0,
            { number: true }
          ]
        ])
      ]),
      createBaseVNode("div", _hoisted_5$2, [
        _cache[19] || (_cache[19] = createBaseVNode("label", { class: "form-label" }, "Смен в сутки", -1)),
        withDirectives(createBaseVNode("input", {
          type: "number",
          class: "form-control",
          "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.conditions.shifts_per_day = $event),
          min: "1",
          max: "3",
          onInput: _cache[5] || (_cache[5] = (...args) => $options.updateConditions && $options.updateConditions(...args))
        }, null, 544), [
          [
            vModelText,
            $data.conditions.shifts_per_day,
            void 0,
            { number: true }
          ]
        ])
      ]),
      createBaseVNode("div", _hoisted_6$2, [
        _cache[21] || (_cache[21] = createBaseVNode("label", { class: "form-label" }, "Организация транспортировки", -1)),
        withDirectives(createBaseVNode("select", {
          class: "form-select",
          "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.conditions.transportation_organized_by = $event),
          onChange: _cache[7] || (_cache[7] = (...args) => $options.updateConditions && $options.updateConditions(...args))
        }, [..._cache[20] || (_cache[20] = [
          createBaseVNode("option", { value: "lessor" }, "Арендодателем", -1),
          createBaseVNode("option", { value: "lessee" }, "Арендатором", -1),
          createBaseVNode("option", { value: "shared" }, "Совместно", -1)
        ])], 544), [
          [vModelSelect, $data.conditions.transportation_organized_by]
        ])
      ]),
      createBaseVNode("div", _hoisted_7$2, [
        _cache[23] || (_cache[23] = createBaseVNode("label", { class: "form-label" }, "Оплата ГСМ", -1)),
        withDirectives(createBaseVNode("select", {
          class: "form-select",
          "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.conditions.gsm_payment = $event),
          onChange: _cache[9] || (_cache[9] = (...args) => $options.updateConditions && $options.updateConditions(...args))
        }, [..._cache[22] || (_cache[22] = [
          createBaseVNode("option", { value: "included" }, "Включена в стоимость", -1),
          createBaseVNode("option", { value: "separate" }, "Отдельная оплата", -1)
        ])], 544), [
          [vModelSelect, $data.conditions.gsm_payment]
        ])
      ]),
      createBaseVNode("div", _hoisted_8$2, [
        createBaseVNode("div", _hoisted_9$2, [
          withDirectives(createBaseVNode("input", {
            class: "form-check-input",
            type: "checkbox",
            "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => $data.conditions.operator_included = $event),
            onChange: _cache[11] || (_cache[11] = (...args) => $options.updateConditions && $options.updateConditions(...args))
          }, null, 544), [
            [vModelCheckbox, $data.conditions.operator_included]
          ]),
          _cache[24] || (_cache[24] = createBaseVNode("label", { class: "form-check-label" }, "Оператор включен", -1))
        ])
      ]),
      createBaseVNode("div", _hoisted_10$2, [
        createBaseVNode("div", _hoisted_11$2, [
          withDirectives(createBaseVNode("input", {
            class: "form-check-input",
            type: "checkbox",
            "onUpdate:modelValue": _cache[12] || (_cache[12] = ($event) => $data.conditions.accommodation_payment = $event),
            onChange: _cache[13] || (_cache[13] = (...args) => $options.updateConditions && $options.updateConditions(...args))
          }, null, 544), [
            [vModelCheckbox, $data.conditions.accommodation_payment]
          ]),
          _cache[25] || (_cache[25] = createBaseVNode("label", { class: "form-check-label" }, "Оплата проживания", -1))
        ])
      ]),
      createBaseVNode("div", _hoisted_12$2, [
        createBaseVNode("div", _hoisted_13$2, [
          withDirectives(createBaseVNode("input", {
            class: "form-check-input",
            type: "checkbox",
            "onUpdate:modelValue": _cache[14] || (_cache[14] = ($event) => $data.conditions.extension_possibility = $event),
            onChange: _cache[15] || (_cache[15] = (...args) => $options.updateConditions && $options.updateConditions(...args))
          }, null, 544), [
            [vModelCheckbox, $data.conditions.extension_possibility]
          ]),
          _cache[26] || (_cache[26] = createBaseVNode("label", { class: "form-check-label" }, "Возможно продление", -1))
        ])
      ])
    ]),
    $options.showCalculation ? (openBlock(), createElementBlock("div", _hoisted_14$2, [
      createBaseVNode("small", _hoisted_15$2, [
        _cache[27] || (_cache[27] = createBaseVNode("i", { class: "fas fa-info-circle me-1" }, null, -1)),
        createTextVNode(" Расчет основан: " + toDisplayString($data.conditions.hours_per_shift) + "ч × " + toDisplayString($data.conditions.shifts_per_day) + " смен = ", 1),
        createBaseVNode("strong", null, toDisplayString($options.totalHoursPerDay) + " часов/сутки", 1)
      ])
    ])) : createCommentVNode("", true)
  ]);
}
const RentalConditions = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2]]);
const _sfc_main$1 = {
  name: "EquipmentSpecifications",
  props: {
    categoryId: {
      type: [String, Number],
      required: true
    },
    modelValue: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ["update:modelValue"],
  data() {
    return {
      standardSpecs: [],
      standardValues: {},
      customSpecs: [],
      isLoading: false,
      preventCategoryReload: false,
      isEmittingUpdate: false,
      lastEmittedData: null,
      isInitializing: false,
      isExternalUpdate: false,
      preventReinitialization: false,
      debounceTimer: null
    };
  },
  computed: {
    currentSpecifications() {
      return {
        standard_specifications: __spreadValues({}, this.standardValues),
        custom_specifications: this.prepareCustomSpecificationsForEmit()
      };
    }
  },
  watch: {
    categoryId: {
      immediate: true,
      handler(newCategoryId) {
        if (newCategoryId && !this.preventCategoryReload) {
          console.log("🔄 EquipmentSpecifications: загрузка спецификаций для категории", newCategoryId);
          this.loadCategorySpecifications();
        } else if (!newCategoryId) {
          console.log("🔄 EquipmentSpecifications: сброс спецификаций (нет категории)");
          this.standardSpecs = [];
          this.customSpecs = [];
          this.standardValues = {};
        }
      }
    },
    modelValue: {
      deep: true,
      handler(newValue, oldValue) {
        if (this.isEmittingUpdate) {
          console.log("🛑 EquipmentSpecifications: предотвращена циклическая переинициализация (isEmittingUpdate)");
          return;
        }
        const newValueStr = JSON.stringify(newValue);
        const oldValueStr = JSON.stringify(oldValue);
        const lastEmittedStr = JSON.stringify(this.lastEmittedData);
        if (newValueStr === oldValueStr) {
          console.log("🛑 EquipmentSpecifications: данные не изменились, пропускаем переинициализацию");
          return;
        }
        if (newValueStr === lastEmittedStr) {
          console.log("🛑 EquipmentSpecifications: получены наши же данные, пропускаем переинициализацию");
          return;
        }
        console.log("🔄 EquipmentSpecifications: modelValue изменен (внешнее обновление)", {
          стандартные_новые: Object.keys((newValue == null ? void 0 : newValue.standard_specifications) || {}).length,
          кастомные_новые: Object.keys((newValue == null ? void 0 : newValue.custom_specifications) || {}).length,
          стандартные_старые: Object.keys((oldValue == null ? void 0 : oldValue.standard_specifications) || {}).length,
          кастомные_старые: Object.keys((oldValue == null ? void 0 : oldValue.custom_specifications) || {}).length
        });
        this.initializeFromModelValue(newValue);
      }
    }
  },
  methods: {
    // ✅ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Подготовка кастомных спецификаций для эмита
    prepareCustomSpecificationsForEmit() {
      const customSpecs = {};
      this.customSpecs.forEach((spec, index) => {
        if (spec.label && spec.label.trim()) {
          const key = spec.id || `custom_${Date.now()}_${index}`;
          let unitValue = "";
          if (spec.unit !== null && spec.unit !== void 0 && spec.unit !== "") {
            unitValue = String(spec.unit);
          }
          customSpecs[key] = {
            label: String(spec.label || ""),
            value: spec.dataType === "number" ? spec.value === "" ? null : Number(spec.value) : String(spec.value || ""),
            unit: unitValue,
            dataType: String(spec.dataType || "string")
          };
          console.log("✅ Кастомная спецификация подготовлена:", {
            key,
            label: customSpecs[key].label,
            value: customSpecs[key].value,
            unit: customSpecs[key].unit
          });
        }
      });
      console.log("📊 ИТОГИ подготовки кастомных спецификаций:", {
        количество: Object.keys(customSpecs).length,
        ключи: Object.keys(customSpecs),
        данные: customSpecs
      });
      return customSpecs;
    },
    // ✅ ДОБАВЛЕН НОВЫЙ МЕТОД: Дополнительная защита от null в unit
    ensureUnitIsString(specs) {
      const cleanedSpecs = {};
      Object.keys(specs).forEach((key) => {
        const spec = specs[key];
        if (spec && typeof spec === "object") {
          cleanedSpecs[key] = __spreadProps(__spreadValues({}, spec), {
            unit: spec.unit !== null && spec.unit !== void 0 ? String(spec.unit) : ""
          });
        }
      });
      return cleanedSpecs;
    },
    loadCategorySpecifications() {
      return __async(this, null, function* () {
        this.isLoading = true;
        try {
          console.log("🔧 EquipmentSpecifications: загрузка шаблона для категории", this.categoryId);
          if (this.categoryId && this.isExcavatorCategory(this.categoryId)) {
            console.log("🏗️ Обнаружена категория экскаватора, используем фиксированные спецификации");
            this.standardSpecs = this.getExcavatorSpecifications();
            this.initializeStandardValues();
            this.validateSpecifications();
            return;
          }
          const response = yield fetch(`/api/specifications/template/${this.categoryId}`);
          const data = yield response.json();
          if (data.success) {
            this.standardSpecs = data.data.standard_specifications || [];
            console.log("✅ EquipmentSpecifications: загружено стандартных спецификаций", this.standardSpecs.length);
            this.initializeStandardValues();
          } else {
            console.error("❌ EquipmentSpecifications: API вернул ошибку:", data.message);
            this.standardSpecs = this.getFallbackSpecifications();
            this.initializeStandardValues();
          }
        } catch (error) {
          console.error("❌ EquipmentSpecifications: ошибка загрузки спецификаций:", error);
          this.standardSpecs = this.getFallbackSpecifications();
          this.initializeStandardValues();
        } finally {
          this.isLoading = false;
          this.validateSpecifications();
        }
      });
    },
    isExcavatorCategory(categoryId) {
      const excavatorIds = [1, 2, 3, 4, 5];
      return excavatorIds.includes(Number(categoryId));
    },
    getExcavatorSpecifications() {
      console.log("🏗️ Загрузка спецификаций для экскаватора");
      return [
        {
          "key": "bucket_volume",
          "label": "Объем ковша",
          "unit": "м³",
          "type": "number",
          "placeholder": "1.5",
          "validation": {
            "min": 0.1,
            "max": 20,
            "step": 0.1
          }
        },
        {
          "key": "weight",
          "label": "Вес",
          "unit": "т",
          "type": "number",
          "placeholder": "Введите значение в т"
        },
        {
          "key": "power",
          "label": "Мощность",
          "unit": "л.с.",
          "type": "number",
          "placeholder": "Введите значение в л.с."
        },
        {
          "key": "max_digging_depth",
          "label": "Макс. глубина копания",
          "unit": "м",
          "type": "number",
          "placeholder": "Введите значение в м"
        },
        {
          "key": "engine_power",
          "label": "Мощность двигателя",
          "unit": "кВт",
          "type": "number",
          "placeholder": "Введите значение в кВт"
        }
      ];
    },
    getFallbackSpecifications() {
      console.log("🔄 EquipmentSpecifications: использование fallback спецификаций");
      return [
        {
          "key": "weight",
          "label": "Вес",
          "unit": "т",
          "type": "number",
          "placeholder": "Введите значение в т"
        },
        {
          "key": "power",
          "label": "Мощность",
          "unit": "л.с.",
          "type": "number",
          "placeholder": "Введите значение в л.с."
        }
      ];
    },
    initializeStandardValues() {
      this.standardValues = {};
      this.standardSpecs.forEach((spec) => {
        var _a;
        let initialValue = ((_a = this.modelValue.standard_specifications) == null ? void 0 : _a[spec.key]) || "";
        if (spec.key === "bucket_volume" && initialValue) {
          initialValue = parseFloat(initialValue);
          if (isNaN(initialValue)) {
            initialValue = "";
          }
        }
        this.standardValues[spec.key] = initialValue;
      });
      console.log("✅ EquipmentSpecifications: инициализированы стандартные значения", this.standardValues);
    },
    initializeFromModelValue(modelValue) {
      if (this.isEmittingUpdate || this.isInitializing) {
        console.log("🛑 EquipmentSpecifications: предотвращена циклическая инициализация");
        return;
      }
      this.isInitializing = true;
      console.log("🔄 EquipmentSpecifications: инициализация из modelValue", {
        has_standard: !!(modelValue == null ? void 0 : modelValue.standard_specifications),
        has_custom: !!(modelValue == null ? void 0 : modelValue.custom_specifications),
        standard_count: Object.keys((modelValue == null ? void 0 : modelValue.standard_specifications) || {}).length,
        custom_count: Object.keys((modelValue == null ? void 0 : modelValue.custom_specifications) || {}).length
      });
      try {
        if (modelValue == null ? void 0 : modelValue.standard_specifications) {
          this.standardValues = __spreadValues({}, modelValue.standard_specifications);
          console.log(
            "✅ EquipmentSpecifications: стандартные значения установлены из modelValue",
            Object.keys(this.standardValues).length
          );
        } else {
          this.initializeStandardValues();
        }
        this.customSpecs = [];
        if ((modelValue == null ? void 0 : modelValue.custom_specifications) && Object.keys(modelValue.custom_specifications).length > 0) {
          Object.entries(modelValue.custom_specifications).forEach(([key, spec]) => {
            let normalizedUnit = "";
            if (spec.unit !== null && spec.unit !== void 0) {
              normalizedUnit = String(spec.unit);
            }
            this.customSpecs.push({
              id: key,
              label: spec.label || "",
              value: spec.value || "",
              unit: normalizedUnit,
              dataType: spec.dataType || "string"
            });
          });
          console.log(
            "✅ EquipmentSpecifications: кастомные спецификации восстановлены из modelValue",
            this.customSpecs.length
          );
        } else {
          console.log("✅ EquipmentSpecifications: кастомные спецификации инициализированы пустым массивом");
        }
        console.log("🎯 EquipmentSpecifications: инициализация завершена", {
          стандартные: Object.keys(this.standardValues).length,
          кастомные: this.customSpecs.length
        });
      } catch (error) {
        console.error("❌ EquipmentSpecifications: ошибка инициализации:", error);
      } finally {
        this.isInitializing = false;
      }
    },
    onBucketVolumeChange(value, key) {
      console.log("💧 Изменение объема ковша:", {
        значение: value,
        ключ: key,
        преобразованное: parseFloat(value)
      });
      if (value !== "" && value !== null) {
        const numericValue = parseFloat(value);
        if (!isNaN(numericValue)) {
          this.standardValues[key] = numericValue;
          console.log("✅ Объем ковша преобразован в число:", numericValue);
        }
      }
      this.debouncedEmitUpdate();
    },
    onSpecificationChange() {
      console.log("✏️ EquipmentSpecifications: изменены стандартные спецификации");
      this.debouncedEmitUpdate();
    },
    addCustomSpec() {
      const newSpec = {
        id: "custom_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9),
        label: "",
        value: "",
        unit: "",
        // ✅ Начинаем с пустой строки, а не null
        dataType: "string"
      };
      this.customSpecs.push(newSpec);
      console.log("➕ EquipmentSpecifications: добавлена новая кастомная спецификация", {
        id: newSpec.id,
        всего_кастомных: this.customSpecs.length,
        список: this.customSpecs.map((s) => ({ label: s.label, id: s.id }))
      });
      this.$nextTick(() => {
        this.emitUpdate();
      });
    },
    removeCustomSpec(index) {
      const removedSpec = this.customSpecs[index];
      console.log("➖ EquipmentSpecifications: удалена кастомная спецификация", {
        index,
        label: removedSpec == null ? void 0 : removedSpec.label,
        id: removedSpec == null ? void 0 : removedSpec.id,
        осталось: this.customSpecs.length - 1
      });
      this.customSpecs.splice(index, 1);
      this.emitUpdate();
    },
    onCustomSpecChange(index) {
      const spec = this.customSpecs[index];
      if (spec.unit === null || spec.unit === void 0) {
        spec.unit = "";
        console.log("🔄 EquipmentSpecifications: unit нормализован в пустую строку", {
          index,
          id: spec.id
        });
      }
      console.log("✏️ EquipmentSpecifications: изменена кастомная спецификация", {
        index,
        label: spec.label,
        value: spec.value,
        unit: spec.unit,
        unitType: typeof spec.unit,
        dataType: spec.dataType,
        id: spec.id,
        всего_кастомных: this.customSpecs.length
      });
      if (spec.dataType === "number" && spec.value !== "") {
        const numValue = Number(spec.value);
        if (!isNaN(numValue)) {
          spec.value = numValue;
        }
      }
      this.debouncedEmitUpdate();
    },
    debouncedEmitUpdate() {
      if (this.debounceTimer) {
        clearTimeout(this.debounceTimer);
      }
      this.debounceTimer = setTimeout(() => {
        this.emitUpdate();
      }, 500);
    },
    emitUpdate() {
      if (this.isInitializing) {
        console.log("🛑 EquipmentSpecifications: предотвращен эмит во время инициализации");
        return;
      }
      console.log("🔥 EquipmentSpecifications: EMIT данных спецификаций");
      this.isEmittingUpdate = true;
      try {
        let customSpecs = this.prepareCustomSpecificationsForEmit();
        customSpecs = this.ensureUnitIsString(customSpecs);
        const unifiedSpecs = {
          standard_specifications: __spreadValues({}, this.standardValues),
          custom_specifications: customSpecs
        };
        let hasNullUnit = false;
        Object.keys(unifiedSpecs.custom_specifications).forEach((key) => {
          const spec = unifiedSpecs.custom_specifications[key];
          if (spec.unit === null) {
            console.error(`❌ КРИТИЧЕСКАЯ ОШИБКА: unit всё равно null для ${key}`);
            unifiedSpecs.custom_specifications[key].unit = "";
            hasNullUnit = true;
          }
        });
        if (hasNullUnit) {
          console.error("🚨 ВНИМАНИЕ: Были обнаружены null значения unit, они были заменены на пустые строки");
        }
        this.lastEmittedData = JSON.parse(JSON.stringify(unifiedSpecs));
        console.log("📤 EquipmentSpecifications отправляет:", {
          стандартные_ключи: Object.keys(unifiedSpecs.standard_specifications),
          кастомные_ключи: Object.keys(unifiedSpecs.custom_specifications),
          кастомные_количество: Object.keys(unifiedSpecs.custom_specifications).length,
          кастомные_данные: unifiedSpecs.custom_specifications,
          units_check: Object.values(unifiedSpecs.custom_specifications).map((s) => ({
            unit: s.unit,
            type: typeof s.unit,
            isNull: s.unit === null
          }))
        });
        this.$emit("update:modelValue", unifiedSpecs);
      } catch (error) {
        console.error("❌ EquipmentSpecifications: ошибка при эмите:", error);
      } finally {
        setTimeout(() => {
          this.isEmittingUpdate = false;
        }, 100);
      }
    },
    validateSpecifications() {
      console.log("🔍 ДИАГНОСТИКА ВАЛИДАЦИИ:", {
        стандартные_значения: this.standardValues,
        стандартные_спецификации: this.standardSpecs,
        кастомные_спецификации: this.customSpecs
      });
      const hasBucketVolume = this.standardSpecs.some((spec) => spec.key === "bucket_volume");
      console.log("📦 Есть ли поле bucket_volume:", hasBucketVolume);
      if (hasBucketVolume) {
        const bucketVolumeValue = this.standardValues.bucket_volume;
        console.log("💧 Значение bucket_volume:", {
          значение: bucketVolumeValue,
          тип: typeof bucketVolumeValue,
          преобразованное: parseFloat(bucketVolumeValue),
          isNaN: isNaN(parseFloat(bucketVolumeValue))
        });
      }
    },
    checkComponentState() {
      console.log("🔍 EquipmentSpecifications: ТЕКУЩЕЕ СОСТОЯНИЕ", {
        isEmittingUpdate: this.isEmittingUpdate,
        isInitializing: this.isInitializing,
        standardSpecsCount: this.standardSpecs.length,
        customSpecsCount: this.customSpecs.length,
        standardValuesCount: Object.keys(this.standardValues).length,
        lastEmittedData: this.lastEmittedData ? {
          standard_count: Object.keys(this.lastEmittedData.standard_specifications || {}).length,
          custom_count: Object.keys(this.lastEmittedData.custom_specifications || {}).length
        } : "none"
      });
    }
  },
  mounted() {
    console.log("🔧 EquipmentSpecifications: компонент смонтирован", {
      categoryId: this.categoryId,
      начальные_данные: this.modelValue
    });
    this.initializeFromModelValue(this.modelValue);
    setTimeout(() => {
      this.validateSpecifications();
    }, 1e3);
  },
  beforeUnmount() {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
    }
    console.log("🔧 EquipmentSpecifications: компонент размонтируется, таймеры очищены");
  }
};
const _hoisted_1$1 = { class: "equipment-specifications" };
const _hoisted_2$1 = { class: "specifications-section" };
const _hoisted_3$1 = {
  key: 0,
  class: "standard-specs mb-4"
};
const _hoisted_4$1 = { class: "row g-3" };
const _hoisted_5$1 = { class: "form-label" };
const _hoisted_6$1 = ["placeholder", "onUpdate:modelValue", "step", "min", "max", "onInput"];
const _hoisted_7$1 = ["placeholder", "onUpdate:modelValue"];
const _hoisted_8$1 = ["placeholder", "onUpdate:modelValue"];
const _hoisted_9$1 = {
  key: 3,
  class: "form-text text-muted"
};
const _hoisted_10$1 = {
  key: 4,
  class: "form-text text-info"
};
const _hoisted_11$1 = { class: "custom-specs" };
const _hoisted_12$1 = { class: "d-flex justify-content-between align-items-center mb-3" };
const _hoisted_13$1 = { class: "card-body" };
const _hoisted_14$1 = { class: "row g-3 align-items-end" };
const _hoisted_15$1 = { class: "col-md-4" };
const _hoisted_16$1 = ["onUpdate:modelValue", "onInput"];
const _hoisted_17$1 = { class: "col-md-3" };
const _hoisted_18$1 = ["type", "onUpdate:modelValue", "onInput"];
const _hoisted_19$1 = { class: "col-md-2" };
const _hoisted_20$1 = ["onUpdate:modelValue", "onInput"];
const _hoisted_21$1 = { class: "col-md-2" };
const _hoisted_22$1 = ["onUpdate:modelValue", "onChange"];
const _hoisted_23$1 = { class: "col-md-1" };
const _hoisted_24$1 = ["onClick"];
const _hoisted_25$1 = {
  key: 0,
  class: "text-center py-4 text-muted"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", _hoisted_2$1, [
      $data.standardSpecs.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_3$1, [
        _cache[3] || (_cache[3] = createBaseVNode("h6", { class: "specs-title" }, "Стандартные параметры", -1)),
        createBaseVNode("div", _hoisted_4$1, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.standardSpecs, (spec) => {
            var _a, _b, _c;
            return openBlock(), createElementBlock("div", {
              key: spec.key,
              class: "col-md-6"
            }, [
              createBaseVNode("label", _hoisted_5$1, toDisplayString(spec.label), 1),
              spec.key === "bucket_volume" ? withDirectives((openBlock(), createElementBlock("input", {
                key: 0,
                type: "number",
                class: "form-control",
                placeholder: spec.placeholder,
                "onUpdate:modelValue": ($event) => $data.standardValues[spec.key] = $event,
                step: ((_a = spec.validation) == null ? void 0 : _a.step) || "0.1",
                min: ((_b = spec.validation) == null ? void 0 : _b.min) || "0.1",
                max: ((_c = spec.validation) == null ? void 0 : _c.max) || "20",
                onInput: ($event) => $options.onBucketVolumeChange($event.target.value, spec.key)
              }, null, 40, _hoisted_6$1)), [
                [vModelText, $data.standardValues[spec.key]]
              ]) : spec.type === "number" ? withDirectives((openBlock(), createElementBlock("input", {
                key: 1,
                type: "number",
                class: "form-control",
                placeholder: spec.placeholder,
                "onUpdate:modelValue": ($event) => $data.standardValues[spec.key] = $event,
                onInput: _cache[0] || (_cache[0] = (...args) => $options.onSpecificationChange && $options.onSpecificationChange(...args))
              }, null, 40, _hoisted_7$1)), [
                [vModelText, $data.standardValues[spec.key]]
              ]) : withDirectives((openBlock(), createElementBlock("input", {
                key: 2,
                type: "text",
                class: "form-control",
                placeholder: spec.placeholder,
                "onUpdate:modelValue": ($event) => $data.standardValues[spec.key] = $event,
                onInput: _cache[1] || (_cache[1] = (...args) => $options.onSpecificationChange && $options.onSpecificationChange(...args))
              }, null, 40, _hoisted_8$1)), [
                [vModelText, $data.standardValues[spec.key]]
              ]),
              spec.unit ? (openBlock(), createElementBlock("small", _hoisted_9$1, " Единица измерения: " + toDisplayString(spec.unit), 1)) : createCommentVNode("", true),
              spec.key === "bucket_volume" ? (openBlock(), createElementBlock("small", _hoisted_10$1, " ⚠️ Стандартные объемы: 0.8, 1.0, 1.2, 1.5, 2.0 м³ ")) : createCommentVNode("", true)
            ]);
          }), 128))
        ])
      ])) : createCommentVNode("", true),
      createBaseVNode("div", _hoisted_11$1, [
        createBaseVNode("div", _hoisted_12$1, [
          _cache[5] || (_cache[5] = createBaseVNode("h6", { class: "specs-title mb-0" }, "Дополнительные параметры", -1)),
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-sm btn-outline-primary",
            onClick: _cache[2] || (_cache[2] = (...args) => $options.addCustomSpec && $options.addCustomSpec(...args))
          }, [..._cache[4] || (_cache[4] = [
            createBaseVNode("i", { class: "fas fa-plus me-1" }, null, -1),
            createTextVNode("Добавить параметр ", -1)
          ])])
        ]),
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.customSpecs, (spec, index) => {
          return openBlock(), createElementBlock("div", {
            key: spec.id,
            class: "custom-spec-item card mb-3"
          }, [
            createBaseVNode("div", _hoisted_13$1, [
              createBaseVNode("div", _hoisted_14$1, [
                createBaseVNode("div", _hoisted_15$1, [
                  _cache[6] || (_cache[6] = createBaseVNode("label", { class: "form-label" }, "Название параметра *", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: "text",
                    class: "form-control",
                    "onUpdate:modelValue": ($event) => spec.label = $event,
                    placeholder: "Например: Количество осей",
                    onInput: ($event) => $options.onCustomSpecChange(index),
                    required: ""
                  }, null, 40, _hoisted_16$1), [
                    [vModelText, spec.label]
                  ])
                ]),
                createBaseVNode("div", _hoisted_17$1, [
                  _cache[7] || (_cache[7] = createBaseVNode("label", { class: "form-label" }, "Значение *", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: spec.dataType === "number" ? "number" : "text",
                    class: "form-control",
                    "onUpdate:modelValue": ($event) => spec.value = $event,
                    onInput: ($event) => $options.onCustomSpecChange(index),
                    required: ""
                  }, null, 40, _hoisted_18$1), [
                    [vModelDynamic, spec.value]
                  ])
                ]),
                createBaseVNode("div", _hoisted_19$1, [
                  _cache[8] || (_cache[8] = createBaseVNode("label", { class: "form-label" }, "Единица измерения", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: "text",
                    class: "form-control",
                    "onUpdate:modelValue": ($event) => spec.unit = $event,
                    placeholder: "шт, кг, м",
                    onInput: ($event) => $options.onCustomSpecChange(index)
                  }, null, 40, _hoisted_20$1), [
                    [vModelText, spec.unit]
                  ])
                ]),
                createBaseVNode("div", _hoisted_21$1, [
                  _cache[10] || (_cache[10] = createBaseVNode("label", { class: "form-label" }, "Тип данных", -1)),
                  withDirectives(createBaseVNode("select", {
                    class: "form-select",
                    "onUpdate:modelValue": ($event) => spec.dataType = $event,
                    onChange: ($event) => $options.onCustomSpecChange(index)
                  }, [..._cache[9] || (_cache[9] = [
                    createBaseVNode("option", { value: "string" }, "Текст", -1),
                    createBaseVNode("option", { value: "number" }, "Число", -1)
                  ])], 40, _hoisted_22$1), [
                    [vModelSelect, spec.dataType]
                  ])
                ]),
                createBaseVNode("div", _hoisted_23$1, [
                  createBaseVNode("button", {
                    type: "button",
                    class: "btn btn-danger w-100",
                    onClick: ($event) => $options.removeCustomSpec(index),
                    title: "Удалить параметр"
                  }, [..._cache[11] || (_cache[11] = [
                    createBaseVNode("i", { class: "fas fa-trash" }, null, -1)
                  ])], 8, _hoisted_24$1)
                ])
              ])
            ])
          ]);
        }), 128)),
        $data.customSpecs.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_25$1, [..._cache[12] || (_cache[12] = [
          createBaseVNode("i", { class: "fas fa-list-alt fa-2x mb-2" }, null, -1),
          createBaseVNode("p", null, "Нет дополнительных параметров", -1)
        ])])) : createCommentVNode("", true)
      ])
    ])
  ]);
}
const EquipmentSpecifications = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-5cac99a3"]]);
const _sfc_main = {
  name: "RequestItems",
  components: {
    RentalConditions,
    EquipmentSpecifications
  },
  props: {
    categories: {
      type: Array,
      default: () => []
    },
    generalHourlyRate: {
      type: Number,
      required: true,
      default: 0,
      validator: (value) => {
        return typeof value === "number" && value >= 0;
      }
    },
    generalConditions: {
      type: Object,
      default: () => ({})
    },
    rentalPeriod: {
      type: Object,
      default: () => ({})
    },
    initialItems: {
      type: Array,
      default: () => []
    }
  },
  emits: ["items-updated", "total-budget-updated"],
  data() {
    return {
      items: [],
      isInitialized: false,
      preventUpdateLoop: false,
      debounceTimeout: null,
      hasUnsavedChanges: false,
      // ✅ ДОБАВЛЕНО: Флаг для предотвращения циклических обновлений
      isProcessingExternalUpdate: false
    };
  },
  computed: {
    totalQuantity() {
      return this.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
    },
    uniqueCategories() {
      const categoryIds = this.items.map((item) => item.category_id).filter((id) => id);
      return new Set(categoryIds).size;
    },
    rentalDays() {
      if (!this.rentalPeriod.start || !this.rentalPeriod.end) return 0;
      try {
        const start = new Date(this.rentalPeriod.start);
        const end = new Date(this.rentalPeriod.end);
        const days = Math.ceil((end - start) / (1e3 * 60 * 60 * 24)) + 1;
        return days > 0 ? days : 0;
      } catch (e) {
        console.error("Date calculation error:", e);
        return 0;
      }
    },
    totalBudget() {
      const total = this.items.reduce((sum, item) => sum + this.calculateItemPrice(item), 0);
      return total;
    }
  },
  watch: {
    items: {
      handler(newItems) {
        if (this.isInitialized && !this.preventUpdateLoop && !this.isProcessingExternalUpdate) {
          console.log("🔄 RequestItems: изменения в items, запуск дебаунса");
          this.debouncedUpdateItems();
        }
      },
      deep: true
    },
    generalHourlyRate: {
      handler(newRate) {
        console.log("🔄 RequestItems: generalHourlyRate изменен:", newRate, typeof newRate);
        if (this.isInitialized) {
          this.updateItemsWithGeneralRate(newRate);
          this.debouncedUpdateItems();
        }
      },
      immediate: true
    },
    generalConditions: {
      handler(newConditions) {
        if (this.isInitialized) {
          this.debouncedUpdateItems();
        }
      },
      deep: true
    },
    rentalPeriod: {
      handler(newPeriod) {
        if (this.isInitialized) {
          this.debouncedUpdateItems();
        }
      },
      deep: true
    },
    initialItems: {
      handler(newItems) {
        var _a;
        if (this.preventUpdateLoop || this.isProcessingExternalUpdate) {
          console.log("🛑 RequestItems: предотвращена циклическая обработка initialItems");
          return;
        }
        console.log("🔄 RequestItems: initialItems изменены", {
          newItemsLength: newItems == null ? void 0 : newItems.length,
          currentItemsLength: (_a = this.items) == null ? void 0 : _a.length
        });
        if (newItems && newItems.length > 0) {
          this.isProcessingExternalUpdate = true;
          const normalizedNew = this.normalizeItems(newItems);
          const normalizedCurrent = this.normalizeItems(this.items);
          if (JSON.stringify(normalizedNew) !== JSON.stringify(normalizedCurrent)) {
            console.log("✅ RequestItems: загружаем initialItems в items");
            this.items = normalizedNew;
          }
          setTimeout(() => {
            this.isProcessingExternalUpdate = false;
          }, 100);
        } else if (this.items.length === 0) {
          this.items = [this.createEmptyItem()];
        }
      },
      deep: true,
      immediate: true
    }
  },
  methods: {
    ensureNumber(value) {
      if (value === null || value === void 0 || value === "") {
        return 0;
      }
      const num = Number(value);
      return isNaN(num) ? 0 : num;
    },
    prepareSpecifications(specs) {
      if (!specs || typeof specs !== "object") {
        return {};
      }
      const prepared = {};
      if (specs.values && typeof specs.values === "object") {
        Object.keys(specs.values).forEach((key) => {
          const value = specs.values[key];
          prepared[key] = value === "" || value === null ? null : this.convertToNumber(value);
        });
      } else {
        Object.keys(specs).forEach((key) => {
          const value = specs[key];
          prepared[key] = value === "" || value === null ? null : this.convertToNumber(value);
        });
      }
      return prepared;
    },
    // ⚠️ ИСПРАВЛЕНИЕ: Улучшенный метод подготовки спецификаций для отправки
    prepareSpecificationsForSubmission(specs) {
      if (!specs || typeof specs !== "object") {
        return {};
      }
      const prepared = {};
      if (specs.values && typeof specs.values === "object") {
        Object.keys(specs.values).forEach((key) => {
          var _a, _b;
          const value = specs.values[key];
          if (((_b = (_a = specs.metadata) == null ? void 0 : _a[key]) == null ? void 0 : _b.dataType) === "number") {
            prepared[key] = value === "" || value === null ? null : Number(value);
          } else {
            prepared[key] = value === "" || value === null ? null : value;
          }
        });
      } else {
        Object.keys(specs).forEach((key) => {
          const value = specs[key];
          prepared[key] = value === "" || value === null ? null : value;
        });
      }
      return prepared;
    },
    convertToNumberOrNull(value) {
      if (value === "" || value === null || value === void 0) {
        return null;
      }
      const num = Number(value);
      return isNaN(num) ? null : num;
    },
    convertToNumber(value) {
      if (value === "" || value === null || value === void 0) {
        return null;
      }
      const num = Number(value);
      return isNaN(num) ? value : num;
    },
    normalizeItem(item) {
      var _a, _b;
      const normalized = {
        category_id: item.category_id || null,
        quantity: parseInt(item.quantity) || 1,
        hourly_rate: item.hourly_rate ? this.ensureNumber(item.hourly_rate) : null,
        use_individual_conditions: Boolean(item.use_individual_conditions),
        individual_conditions: item.individual_conditions || {},
        specifications: {
          standard_specifications: ((_a = item.specifications) == null ? void 0 : _a.standard_specifications) || {},
          custom_specifications: ((_b = item.specifications) == null ? void 0 : _b.custom_specifications) || {}
        },
        custom_specs_metadata: item.custom_specs_metadata || {}
      };
      console.log("🔄 Нормализована позиция с новой структурой:", {
        category_id: normalized.category_id,
        standard_specs_count: Object.keys(normalized.specifications.standard_specifications).length,
        custom_specs_count: Object.keys(normalized.specifications.custom_specifications).length,
        metadata_count: Object.keys(normalized.custom_specs_metadata).length
      });
      return normalized;
    },
    isEmptyObject(obj) {
      return obj && Object.keys(obj).length === 0 && obj.constructor === Object;
    },
    normalizeItems(items) {
      return items.map((item) => this.normalizeItem(item));
    },
    safeEmitUpdates() {
      if (this.preventUpdateLoop) {
        console.log("🛑 Предотвращена циклическая отправка");
        return;
      }
      this.preventUpdateLoop = true;
      this.emitUpdates();
      this.$nextTick(() => {
        this.preventUpdateLoop = false;
      });
    },
    // ✅ ИСПРАВЛЕННЫЙ МЕТОД: Дебаунс для обновления items
    debouncedUpdateItems() {
      if (this.debounceTimeout) {
        clearTimeout(this.debounceTimeout);
      }
      this.debounceTimeout = setTimeout(() => {
        this.emitUpdates();
      }, 300);
    },
    getCategoryName(categoryId) {
      if (!categoryId) return "Без категории";
      const category = this.categories.find((cat) => cat.id == categoryId);
      return (category == null ? void 0 : category.name) || "Категория не найдена";
    },
    // ✅ ИСПРАВЛЕННЫЙ МЕТОД: Эмит обновлений с защитой от циклов
    emitUpdates() {
      if (this.preventUpdateLoop || this.isProcessingExternalUpdate) {
        console.log("🛑 RequestItems: предотвращена циклическая отправка в emitUpdates");
        return;
      }
      console.log("📤 RequestItems: отправка обновленных данных");
      try {
        const preparedItems = this.items.map((item, index) => {
          var _a, _b;
          const preparedItem = __spreadProps(__spreadValues({}, item), {
            specifications: {
              standard_specifications: ((_a = item.specifications) == null ? void 0 : _a.standard_specifications) || {},
              custom_specifications: ((_b = item.specifications) == null ? void 0 : _b.custom_specifications) || {}
            },
            custom_specs_metadata: item.custom_specs_metadata || {}
          });
          console.log(`📦 Позиция ${index} для отправки:`, {
            category_id: preparedItem.category_id,
            standard_specs_count: Object.keys(preparedItem.specifications.standard_specifications).length,
            custom_specs_count: Object.keys(preparedItem.specifications.custom_specifications).length,
            metadata_count: Object.keys(preparedItem.custom_specs_metadata).length
          });
          return preparedItem;
        });
        this.$emit("items-updated", preparedItems);
        this.$emit("total-budget-updated", this.totalBudget);
        console.log("✅ RequestItems: данные успешно отправлены", {
          items_count: preparedItems.length,
          total_budget: this.totalBudget
        });
      } catch (error) {
        console.error("❌ RequestItems: ошибка при отправке данных:", error);
      }
    },
    addItem() {
      const newItem = this.createEmptyItem();
      this.items.push(newItem);
      this.emitUpdates();
      console.log("➕ Добавлена новая позиция");
    },
    removeItem(index) {
      if (this.items.length > 1) {
        this.items.splice(index, 1);
        this.emitUpdates();
        console.log("➖ Удалена позиция", index);
      }
    },
    createEmptyItem() {
      return {
        category_id: null,
        quantity: 1,
        hourly_rate: this.ensureNumber(this.generalHourlyRate),
        use_individual_conditions: false,
        individual_conditions: {},
        specifications: {},
        custom_specs_metadata: {}
      };
    },
    onCategoryChange(item, index) {
      this.items[index].specifications = {};
      this.items[index].custom_specs_metadata = {};
      this.emitUpdates();
    },
    // ✅ ИСПРАВЛЕННЫЙ МЕТОД: Обработка обновлений спецификаций
    onSpecificationsUpdate(index, specifications) {
      console.log("🔄 RequestItems: получены обновленные спецификации для позиции", index, {
        стандартные_ключи: Object.keys((specifications == null ? void 0 : specifications.standard_specifications) || {}),
        кастомные_ключи: Object.keys((specifications == null ? void 0 : specifications.custom_specifications) || {}),
        кастомные_количество: Object.keys((specifications == null ? void 0 : specifications.custom_specifications) || {}).length
      });
      if (specifications == null ? void 0 : specifications.custom_specifications) {
        let hasNullUnit = false;
        Object.keys(specifications.custom_specifications).forEach((key) => {
          const spec = specifications.custom_specifications[key];
          if (spec) {
            if (spec.unit === null || spec.unit === void 0) {
              console.error(`❌ RequestItems: КРИТИЧЕСКАЯ ОШИБКА - unit null/undefined для ${key}`);
              specifications.custom_specifications[key].unit = "";
              hasNullUnit = true;
            } else if (typeof spec.unit !== "string") {
              console.warn(`⚠️ RequestItems: исправляем тип unit для ${key}`, spec.unit);
              specifications.custom_specifications[key].unit = String(spec.unit);
            }
            if (typeof spec.label !== "string") {
              specifications.custom_specifications[key].label = String(spec.label || "");
            }
            if (typeof spec.dataType !== "string") {
              specifications.custom_specifications[key].dataType = "string";
            }
            if (spec.dataType === "number" && spec.value !== null && spec.value !== void 0) {
              const numValue = Number(spec.value);
              if (isNaN(numValue)) {
                console.warn(`⚠️ RequestItems: невалидное числовое значение для ${key}`, spec.value);
                specifications.custom_specifications[key].value = null;
              }
            }
          }
        });
        if (hasNullUnit) {
          console.error("🚨 RequestItems: ВНИМАНИЕ - были обнаружены null значения unit в полученных данных от EquipmentSpecifications");
        }
      }
      if (this.preventUpdateLoop) {
        console.log("🛑 RequestItems: предотвращен циклический вызов onSpecificationsUpdate");
        return;
      }
      this.preventUpdateLoop = true;
      try {
        this.items[index].specifications = __spreadValues({}, specifications);
        if (specifications && specifications.custom_specifications) {
          const customMetadata = {};
          Object.keys(specifications.custom_specifications).forEach((key) => {
            const spec = specifications.custom_specifications[key];
            let unitValue = spec.unit || "";
            if (unitValue === null || unitValue === void 0) {
              unitValue = "";
              console.error(`❌ RequestItems: unit null в метаданных для ${key}`);
            }
            customMetadata[key] = {
              name: spec.label,
              dataType: spec.dataType || "string",
              unit: unitValue
            };
          });
          this.items[index].custom_specs_metadata = customMetadata;
          console.log("💾 RequestItems: сохранены метаданные для позиции:", {
            index,
            custom_specs_count: Object.keys(specifications.custom_specifications).length,
            metadata_count: Object.keys(customMetadata).length
          });
        }
        this.hasUnsavedChanges = true;
        setTimeout(() => {
          this.debouncedUpdateItems();
          this.preventUpdateLoop = false;
        }, 50);
      } catch (error) {
        console.error("❌ RequestItems: ошибка в onSpecificationsUpdate:", error);
        this.preventUpdateLoop = false;
      }
    },
    toggleIndividualConditions(index, event) {
      const isChecked = event.target.checked;
      this.items[index].use_individual_conditions = isChecked;
      if (isChecked) {
        this.items[index].individual_conditions = __spreadValues({}, this.generalConditions);
      } else {
        this.items[index].individual_conditions = {};
      }
      this.emitUpdates();
    },
    updateItemConditions(index, conditions) {
      this.items[index].individual_conditions = conditions;
      this.emitUpdates();
    },
    updateItemsWithGeneralRate(newRate) {
      const safeRate = this.ensureNumber(newRate);
      console.log("🔄 Обновление позиций с новой ставкой:", safeRate);
      this.items.forEach((item) => {
        if (!item.hourly_rate && safeRate > 0) {
          item.hourly_rate = safeRate;
        }
      });
    },
    calculateItemPrice(item) {
      if (!item.quantity || item.quantity <= 0) {
        return 0;
      }
      const hourlyRate = this.getItemHourlyRate(item);
      if (!hourlyRate || hourlyRate <= 0) {
        return 0;
      }
      const days = this.rentalDays;
      if (days <= 0) {
        return 0;
      }
      const conditions = this.getItemConditions(item);
      const hoursPerShift = conditions.hours_per_shift || 8;
      const shiftsPerDay = conditions.shifts_per_day || 1;
      const price = hourlyRate * hoursPerShift * shiftsPerDay * days * item.quantity;
      return price;
    },
    getItemHourlyRate(item) {
      return this.ensureNumber(item.hourly_rate || this.generalHourlyRate);
    },
    getItemConditions(item) {
      return item.use_individual_conditions && item.individual_conditions ? item.individual_conditions : this.generalConditions;
    },
    formatCurrency(amount) {
      if (!amount) return "0 ₽";
      return new Intl.NumberFormat("ru-RU", {
        style: "currency",
        currency: "RUB",
        minimumFractionDigits: 0
      }).format(amount);
    },
    // ✅ МЕТОД ДЛЯ ПРОВЕРКИ СОСТОЯНИЯ (для отладки)
    checkItemsState() {
      console.log("🔍 RequestItems: ТЕКУЩЕЕ СОСТОЯНИЕ ITEMS");
      this.items.forEach((item, index) => {
        var _a, _b, _c, _d;
        console.log(`  Позиция ${index}:`, {
          category_id: item.category_id,
          specifications_type: typeof item.specifications,
          has_standard_specs: !!((_a = item.specifications) == null ? void 0 : _a.standard_specifications),
          has_custom_specs: !!((_b = item.specifications) == null ? void 0 : _b.custom_specifications),
          standard_specs_count: Object.keys(((_c = item.specifications) == null ? void 0 : _c.standard_specifications) || {}).length,
          custom_specs_count: Object.keys(((_d = item.specifications) == null ? void 0 : _d.custom_specifications) || {}).length
        });
      });
    }
  },
  mounted() {
    var _a;
    console.log("🔍 RequestItems mounted DEBUG:", {
      initialItems: this.initialItems,
      items: this.items,
      categoriesCount: (_a = this.categories) == null ? void 0 : _a.length,
      generalHourlyRate: this.generalHourlyRate,
      generalHourlyRate_type: typeof this.generalHourlyRate,
      rentalPeriod: this.rentalPeriod
    });
    this.isInitialized = true;
    if (this.items.length === 0) {
      this.items = [this.createEmptyItem()];
    }
    setTimeout(() => {
      this.emitUpdates();
    }, 500);
  },
  // ✅ ГЛОБАЛЬНАЯ ЗАЩИТА ОТ ЦИКЛОВ
  beforeUnmount() {
    if (this.debounceTimeout) {
      clearTimeout(this.debounceTimeout);
    }
    console.log("🔧 RequestItems: компонент размонтируется, таймеры очищены");
  }
};
const _hoisted_1 = { class: "request-items" };
const _hoisted_2 = { class: "d-flex justify-content-between align-items-center mb-3" };
const _hoisted_3 = { class: "items-list" };
const _hoisted_4 = { class: "card-body" };
const _hoisted_5 = { class: "row g-3" };
const _hoisted_6 = { class: "col-md-4" };
const _hoisted_7 = ["onUpdate:modelValue", "onChange"];
const _hoisted_8 = ["value"];
const _hoisted_9 = { class: "col-md-2" };
const _hoisted_10 = ["onUpdate:modelValue"];
const _hoisted_11 = { class: "col-md-3" };
const _hoisted_12 = ["onUpdate:modelValue"];
const _hoisted_13 = {
  key: 0,
  class: "text-muted"
};
const _hoisted_14 = { class: "col-md-2" };
const _hoisted_15 = { class: "form-control bg-light" };
const _hoisted_16 = { class: "col-md-1" };
const _hoisted_17 = ["onClick", "disabled"];
const _hoisted_18 = {
  key: 0,
  class: "mt-3"
};
const _hoisted_19 = { class: "mt-3" };
const _hoisted_20 = { class: "form-check form-switch" };
const _hoisted_21 = ["onUpdate:modelValue", "onChange"];
const _hoisted_22 = {
  key: 0,
  class: "individual-conditions mt-3 p-3 bg-light rounded"
};
const _hoisted_23 = { class: "card bg-light" };
const _hoisted_24 = { class: "card-body" };
const _hoisted_25 = { class: "row text-center" };
const _hoisted_26 = { class: "col-md-3" };
const _hoisted_27 = { class: "badge bg-primary ms-2" };
const _hoisted_28 = { class: "col-md-3" };
const _hoisted_29 = { class: "badge bg-success ms-2" };
const _hoisted_30 = { class: "col-md-3" };
const _hoisted_31 = { class: "badge bg-info ms-2" };
const _hoisted_32 = { class: "col-md-3" };
const _hoisted_33 = { class: "badge bg-warning ms-2" };
const _hoisted_34 = {
  key: 0,
  class: "calculation-details mt-3 p-3 bg-white rounded"
};
const _hoisted_35 = { class: "calculation-items" };
const _hoisted_36 = { class: "d-block" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_EquipmentSpecifications = resolveComponent("EquipmentSpecifications");
  const _component_RentalConditions = resolveComponent("RentalConditions");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      _cache[4] || (_cache[4] = createBaseVNode("h6", { class: "mb-0" }, "Позиции заявки", -1)),
      createBaseVNode("button", {
        type: "button",
        class: "btn btn-sm btn-primary",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.addItem && $options.addItem(...args))
      }, [..._cache[3] || (_cache[3] = [
        createBaseVNode("i", { class: "fas fa-plus me-1" }, null, -1),
        createTextVNode("Добавить позицию ", -1)
      ])])
    ]),
    createBaseVNode("div", _hoisted_3, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.items, (item, index) => {
        return openBlock(), createElementBlock("div", {
          key: index,
          class: "item-card card mb-3"
        }, [
          createBaseVNode("div", _hoisted_4, [
            createBaseVNode("div", _hoisted_5, [
              createBaseVNode("div", _hoisted_6, [
                _cache[6] || (_cache[6] = createBaseVNode("label", { class: "form-label" }, "Категория техники *", -1)),
                withDirectives(createBaseVNode("select", {
                  class: "form-select",
                  "onUpdate:modelValue": ($event) => item.category_id = $event,
                  onChange: ($event) => $options.onCategoryChange(item, index),
                  required: ""
                }, [
                  _cache[5] || (_cache[5] = createBaseVNode("option", { value: "" }, "Выберите категорию", -1)),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($props.categories, (category) => {
                    return openBlock(), createElementBlock("option", {
                      value: category.id,
                      key: category.id
                    }, toDisplayString(category.name), 9, _hoisted_8);
                  }), 128))
                ], 40, _hoisted_7), [
                  [vModelSelect, item.category_id]
                ])
              ]),
              createBaseVNode("div", _hoisted_9, [
                _cache[7] || (_cache[7] = createBaseVNode("label", { class: "form-label" }, "Количество *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "number",
                  class: "form-control",
                  "onUpdate:modelValue": ($event) => item.quantity = $event,
                  min: "1",
                  max: "1000",
                  onInput: _cache[1] || (_cache[1] = (...args) => $options.debouncedUpdateItems && $options.debouncedUpdateItems(...args)),
                  required: ""
                }, null, 40, _hoisted_10), [
                  [
                    vModelText,
                    item.quantity,
                    void 0,
                    { number: true }
                  ]
                ])
              ]),
              createBaseVNode("div", _hoisted_11, [
                _cache[8] || (_cache[8] = createBaseVNode("label", { class: "form-label" }, [
                  createTextVNode(" Стоимость часа (₽) "),
                  createBaseVNode("small", { class: "text-muted" }, "*")
                ], -1)),
                withDirectives(createBaseVNode("input", {
                  type: "number",
                  class: "form-control",
                  "onUpdate:modelValue": ($event) => item.hourly_rate = $event,
                  min: "0",
                  step: "50",
                  onInput: _cache[2] || (_cache[2] = (...args) => $options.debouncedUpdateItems && $options.debouncedUpdateItems(...args)),
                  required: ""
                }, null, 40, _hoisted_12), [
                  [
                    vModelText,
                    item.hourly_rate,
                    void 0,
                    { number: true }
                  ]
                ]),
                !item.hourly_rate ? (openBlock(), createElementBlock("small", _hoisted_13, " Будет использована общая стоимость: " + toDisplayString($options.formatCurrency($props.generalHourlyRate)), 1)) : createCommentVNode("", true)
              ]),
              createBaseVNode("div", _hoisted_14, [
                _cache[9] || (_cache[9] = createBaseVNode("label", { class: "form-label" }, "Стоимость позиции", -1)),
                createBaseVNode("div", _hoisted_15, [
                  createBaseVNode("strong", null, toDisplayString($options.formatCurrency($options.calculateItemPrice(item))), 1)
                ])
              ]),
              createBaseVNode("div", _hoisted_16, [
                _cache[11] || (_cache[11] = createBaseVNode("label", { class: "form-label" }, " ", -1)),
                createBaseVNode("button", {
                  type: "button",
                  class: "btn btn-danger w-100",
                  onClick: ($event) => $options.removeItem(index),
                  disabled: $data.items.length <= 1
                }, [..._cache[10] || (_cache[10] = [
                  createBaseVNode("i", { class: "fas fa-trash" }, null, -1)
                ])], 8, _hoisted_17)
              ])
            ]),
            item.category_id ? (openBlock(), createElementBlock("div", _hoisted_18, [
              createVNode(_component_EquipmentSpecifications, {
                "category-id": item.category_id,
                modelValue: item.specifications,
                "onUpdate:modelValue": [($event) => item.specifications = $event, ($event) => $options.onSpecificationsUpdate(index, $event)]
              }, null, 8, ["category-id", "modelValue", "onUpdate:modelValue"])
            ])) : createCommentVNode("", true),
            createBaseVNode("div", _hoisted_19, [
              createBaseVNode("div", _hoisted_20, [
                withDirectives(createBaseVNode("input", {
                  class: "form-check-input",
                  type: "checkbox",
                  "onUpdate:modelValue": ($event) => item.use_individual_conditions = $event,
                  onChange: ($event) => $options.toggleIndividualConditions(index, $event)
                }, null, 40, _hoisted_21), [
                  [vModelCheckbox, item.use_individual_conditions]
                ]),
                _cache[12] || (_cache[12] = createBaseVNode("label", { class: "form-check-label" }, " Использовать индивидуальные условия для этой позиции ", -1))
              ]),
              item.use_individual_conditions ? (openBlock(), createElementBlock("div", _hoisted_22, [
                _cache[13] || (_cache[13] = createBaseVNode("h6", { class: "mb-3" }, "Индивидуальные условия для позиции", -1)),
                createVNode(_component_RentalConditions, {
                  "initial-conditions": item.individual_conditions,
                  onConditionsUpdated: (conditions) => $options.updateItemConditions(index, conditions)
                }, null, 8, ["initial-conditions", "onConditionsUpdated"])
              ])) : createCommentVNode("", true)
            ])
          ])
        ]);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_23, [
      createBaseVNode("div", _hoisted_24, [
        createBaseVNode("div", _hoisted_25, [
          createBaseVNode("div", _hoisted_26, [
            _cache[14] || (_cache[14] = createBaseVNode("strong", null, "Позиций:", -1)),
            createBaseVNode("span", _hoisted_27, toDisplayString($data.items.length), 1)
          ]),
          createBaseVNode("div", _hoisted_28, [
            _cache[15] || (_cache[15] = createBaseVNode("strong", null, "Общее количество:", -1)),
            createBaseVNode("span", _hoisted_29, toDisplayString($options.totalQuantity) + " ед.", 1)
          ]),
          createBaseVNode("div", _hoisted_30, [
            _cache[16] || (_cache[16] = createBaseVNode("strong", null, "Категорий:", -1)),
            createBaseVNode("span", _hoisted_31, toDisplayString($options.uniqueCategories), 1)
          ]),
          createBaseVNode("div", _hoisted_32, [
            _cache[17] || (_cache[17] = createBaseVNode("strong", null, "Общий бюджет:", -1)),
            createBaseVNode("span", _hoisted_33, toDisplayString($options.formatCurrency($options.totalBudget)), 1)
          ])
        ]),
        $options.totalBudget > 0 ? (openBlock(), createElementBlock("div", _hoisted_34, [
          _cache[18] || (_cache[18] = createBaseVNode("h6", { class: "text-center mb-3" }, "Детали расчета", -1)),
          createBaseVNode("div", _hoisted_35, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.items, (item, index) => {
              return openBlock(), createElementBlock("div", {
                key: index,
                class: "calculation-item mb-2"
              }, [
                createBaseVNode("small", _hoisted_36, [
                  createBaseVNode("strong", null, toDisplayString($options.getCategoryName(item.category_id)) + ":", 1),
                  createTextVNode(" " + toDisplayString($options.formatCurrency($options.calculateItemPrice(item))) + " (" + toDisplayString(item.quantity) + " шт. × " + toDisplayString($options.formatCurrency($options.getItemHourlyRate(item))) + "/час × " + toDisplayString($options.rentalDays) + " дн.) ", 1)
                ])
              ]);
            }), 128))
          ])
        ])) : createCommentVNode("", true)
      ])
    ])
  ]);
}
const RequestItems = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-565c5ba5"]]);
export {
  RequestItems as R,
  RentalConditions as a
};
