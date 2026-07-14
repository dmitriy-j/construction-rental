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
import { a as createElementBlock, o as openBlock, e as createCommentVNode, u as withModifiers, b as createBaseVNode, d as createTextVNode, t as toDisplayString, w as withDirectives, v as vModelSelect, F as Fragment, r as renderList, n as normalizeClass, s as vModelCheckbox, j as vModelText, g as resolveComponent, x as createBlock, h as createStaticVNode, i as createVNode, c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { a as axios } from "./index-DM4mtReV.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main$4 = {
  name: "PublicProposalModal",
  props: {
    show: {
      type: Boolean,
      required: true
    },
    request: {
      type: Object,
      required: true
    }
  },
  emits: ["close", "proposal-created"],
  data() {
    return {
      loadingEquipment: false,
      availableEquipment: [],
      selectedEquipmentIds: [],
      selectedEquipmentItems: {},
      csrfToken: null,
      proposalData: {
        message: ""
      },
      debugMode: true,
      deliveryCalculation: {
        loading: false,
        delivery_required: false,
        delivery_cost: 0,
        distance_km: 0,
        vehicle_type: null,
        rate_per_km: 0,
        from_location: null,
        to_location: null,
        error: null
      },
      submitting: false,
      minPrice: 100,
      maxPrice: 1e4,
      // 🔥 ДАННЫЕ ДЛЯ ШАБЛОНОВ
      availableTemplates: [],
      selectedTemplateId: null,
      templatePreview: {
        show: false,
        loading: false,
        data: {}
      },
      showTemplatesManagement: false,
      templatesLoading: false,
      templatesStats: {}
    };
  },
  computed: {
    isBulkProposal() {
      return this.selectedEquipmentIds.length > 1;
    },
    canSubmitProposal() {
      return this.selectedEquipmentIds.length > 0 && this.proposalData.message.trim().length >= 10 && !this.submitting;
    },
    submitButtonText() {
      if (this.selectedEquipmentIds.length === 0) return "Выберите технику";
      if (this.proposalData.message.trim().length < 10) return "Добавьте сообщение";
      return this.isBulkProposal ? "Отправить комплексное предложение" : "Отправить предложение";
    },
    selectedEquipmentDetails() {
      return this.selectedEquipmentIds.map((id) => {
        var _a;
        const item = this.selectedEquipmentItems[id];
        const equipment = (_a = this.availableEquipment.find((e) => e.equipment.id === id)) == null ? void 0 : _a.equipment;
        return {
          equipment,
          proposed_price: (item == null ? void 0 : item.proposed_price) || 0,
          item_total: (item == null ? void 0 : item.item_total) || 0
        };
      });
    },
    totalLessorPrice() {
      return this.selectedEquipmentDetails.reduce((total, item) => total + item.item_total, 0);
    },
    deliveryCostPerItem() {
      if (!this.deliveryCalculation.delivery_required || this.selectedEquipmentIds.length === 0) return 0;
      return this.deliveryCalculation.delivery_cost / this.selectedEquipmentIds.length;
    },
    totalPriceWithDelivery() {
      const basePrice = this.totalLessorPrice;
      const deliveryCost = this.deliveryCalculation.delivery_required ? this.deliveryCalculation.delivery_cost : 0;
      return basePrice + deliveryCost;
    }
  },
  mounted() {
    this.csrfToken = this.getCsrfToken();
  },
  watch: {
    show: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          console.log("🔄 Modal opened for request:", this.request);
          console.log("🚚 Delivery required:", this.request.delivery_required);
          this.loadAvailableEquipment();
          this.loadAvailableTemplates();
          if (this.request.delivery_required) {
            console.log("📦 Calculating delivery because request requires it");
          } else {
            console.log("ℹ️ Delivery not required for this request");
          }
          document.addEventListener("keydown", this.handleEscape);
        } else {
          this.resetForm();
          document.removeEventListener("keydown", this.handleEscape);
        }
      }
    },
    selectedEquipmentIds: {
      handler(newVal) {
        console.log("🔄 Selected equipment changed:", newVal);
        this.handleEquipmentSelectionChange(newVal);
      },
      deep: true
    }
  },
  methods: {
    // 🔥 МЕТОДЫ ДЛЯ РАБОТЫ С ШАБЛОНАМИ
    loadAvailableTemplates() {
      return __async(this, null, function* () {
        try {
          const params = {
            category_id: this.request.category_id
          };
          const response = yield axios.get("/api/lessor/proposal-templates", {
            params,
            withCredentials: true
          });
          if (response.data.success) {
            this.availableTemplates = response.data.data || [];
            console.log("✅ Templates loaded:", this.availableTemplates.length);
          } else {
            console.error("❌ Failed to load templates:", response.data.message);
            this.availableTemplates = [];
          }
        } catch (error) {
          console.error("❌ Error loading templates:", error);
          this.availableTemplates = [];
        }
      });
    },
    loadTemplatesStats() {
      return __async(this, null, function* () {
        try {
          const response = yield axios.get("/api/lessor/proposal-templates/stats", {
            withCredentials: true
          });
          if (response.data.success) {
            this.templatesStats = response.data.data || {};
          }
        } catch (error) {
          console.error("Error loading templates stats:", error);
        }
      });
    },
    onTemplateSelect() {
      if (this.selectedTemplateId) {
        this.previewTemplate();
      } else {
        this.templatePreview.show = false;
      }
    },
    previewTemplate() {
      return __async(this, null, function* () {
        if (!this.selectedTemplateId || this.selectedEquipmentIds.length === 0) {
          return;
        }
        this.templatePreview.loading = true;
        this.templatePreview.show = false;
        try {
          const response = yield axios.post(
            `/api/lessor/proposal-templates/${this.selectedTemplateId}/preview-apply/${this.request.id}`,
            {
              equipment_ids: this.selectedEquipmentIds
            },
            {
              withCredentials: true
            }
          );
          if (response.data.success) {
            this.templatePreview.data = response.data.data;
            this.templatePreview.show = true;
            console.log("✅ Template preview loaded:", response.data.data);
          } else {
            throw new Error(response.data.message || "Ошибка предпросмотра шаблона");
          }
        } catch (error) {
          console.error("❌ Error previewing template:", error);
          alert("Ошибка при предпросмотре шаблона: " + error.message);
        } finally {
          this.templatePreview.loading = false;
        }
      });
    },
    applyTemplate() {
      return __async(this, null, function* () {
        if (!this.selectedTemplateId) {
          return;
        }
        if (!this.templatePreview.show) {
          yield this.previewTemplate();
        }
      });
    },
    confirmTemplateApply() {
      return __async(this, null, function* () {
        try {
          const response = yield axios.post(
            `/api/lessor/proposal-templates/${this.selectedTemplateId}/apply/${this.request.id}`,
            {
              equipment_ids: this.selectedEquipmentIds
            },
            {
              withCredentials: true
            }
          );
          if (response.data.success) {
            const templateData = response.data.data;
            if (templateData.message) {
              this.proposalData.message = templateData.message;
            }
            if (templateData.prices) {
              Object.keys(templateData.prices).forEach((equipmentId) => {
                const price = templateData.prices[equipmentId];
                if (this.selectedEquipmentItems[equipmentId]) {
                  this.selectedEquipmentItems[equipmentId].proposed_price = price;
                }
              });
              this.recalculatePricing();
            }
            yield this.loadAvailableTemplates();
            this.templatePreview.show = false;
            console.log("✅ Template applied successfully");
            this.$notify({
              type: "success",
              title: "Шаблон применен",
              text: "Данные шаблона успешно применены к предложению"
            });
          } else {
            throw new Error(response.data.message || "Ошибка применения шаблона");
          }
        } catch (error) {
          console.error("❌ Error applying template:", error);
          alert("Ошибка при применении шаблона: " + error.message);
        }
      });
    },
    cancelTemplateApply() {
      this.templatePreview.show = false;
      this.selectedTemplateId = null;
    },
    clearTemplate() {
      this.selectedTemplateId = null;
      this.templatePreview.show = false;
      this.templatePreview.data = {};
    },
    showTemplatesModal() {
      this.showTemplatesManagement = true;
      this.loadTemplatesStats();
    },
    applyTemplateFromManagement(template) {
      return __async(this, null, function* () {
        try {
          this.selectedTemplateId = template.id;
          const response = yield axios.post(
            `/api/lessor/proposal-templates/${template.id}/apply/${this.request.id}`,
            {
              equipment_ids: this.selectedEquipmentIds
            },
            {
              withCredentials: true
            }
          );
          if (response.data.success) {
            const templateData = response.data.data;
            if (templateData.message) {
              this.proposalData.message = templateData.message;
            }
            if (templateData.prices) {
              Object.keys(templateData.prices).forEach((equipmentId) => {
                const price = templateData.prices[equipmentId];
                if (this.selectedEquipmentItems[equipmentId]) {
                  this.selectedEquipmentItems[equipmentId].proposed_price = price;
                }
              });
              this.recalculatePricing();
            }
            this.showTemplatesManagement = false;
            yield this.loadAvailableTemplates();
            this.$notify({
              type: "success",
              title: "Шаблон применен",
              text: `Шаблон "${template.name}" успешно применен`
            });
          }
        } catch (error) {
          console.error("Error applying template from management:", error);
          alert("Ошибка применения шаблона");
        }
      });
    },
    createNewTemplate() {
      window.location.href = "/portal/proposal-templates/create";
    },
    editTemplate(template) {
      window.location.href = `/portal/proposal-templates/${template.id}/edit`;
    },
    deleteTemplate(template) {
      return __async(this, null, function* () {
        if (!confirm(`Удалить шаблон "${template.name}"?`)) {
          return;
        }
        try {
          const response = yield axios.delete(`/api/lessor/proposal-templates/${template.id}`, {
            withCredentials: true
          });
          if (response.data.success) {
            yield this.loadAvailableTemplates();
            yield this.loadTemplatesStats();
            this.$notify({
              type: "success",
              title: "Шаблон удален",
              text: `Шаблон "${template.name}" успешно удален`
            });
          } else {
            throw new Error(response.data.message);
          }
        } catch (error) {
          console.error("Error deleting template:", error);
          alert("Ошибка удаления шаблона");
        }
      });
    },
    getEquipmentName(equipmentId) {
      const equipment = this.availableEquipment.find((e) => e.equipment.id == equipmentId);
      return equipment ? equipment.equipment.title : `Техника #${equipmentId}`;
    },
    // 🔥 СУЩЕСТВУЮЩИЕ МЕТОДЫ
    isEquipmentSelected(equipmentId) {
      return this.selectedEquipmentIds.includes(equipmentId);
    },
    getSelectedEquipment(equipmentId) {
      if (!this.selectedEquipmentItems[equipmentId]) {
        const equipment = this.availableEquipment.find((e) => e.equipment.id === equipmentId);
        this.selectedEquipmentItems[equipmentId] = {
          equipment_id: equipmentId,
          proposed_price: (equipment == null ? void 0 : equipment.recommended_lessor_price) || 0,
          quantity: 1,
          item_total: 0
        };
      }
      return this.selectedEquipmentItems[equipmentId];
    },
    handleEquipmentSelectionChange(newIds) {
      newIds.forEach((id) => {
        if (!this.selectedEquipmentItems[id]) {
          const equipment = this.availableEquipment.find(
            (item) => item && item.equipment && item.equipment.id === id
          );
          this.selectedEquipmentItems[id] = {
            equipment_id: id,
            proposed_price: (equipment == null ? void 0 : equipment.recommended_lessor_price) || 0,
            quantity: 1,
            item_total: 0
          };
        }
      });
      Object.keys(this.selectedEquipmentItems).forEach((id) => {
        if (!newIds.includes(parseInt(id))) {
          delete this.selectedEquipmentItems[id];
        }
      });
      this.recalculatePricing();
      if (newIds.length > 0 && this.request && this.request.delivery_required) {
        console.log("🚚 Equipment selection changed, recalculating delivery...");
        this.calculateDelivery();
      } else {
        console.log("ℹ️ No equipment selected or delivery not required");
        this.deliveryCalculation = {
          loading: false,
          delivery_required: false,
          delivery_cost: 0,
          distance_km: 0,
          vehicle_type: null,
          error: newIds.length === 0 ? "Выберите технику для расчета доставки" : null
        };
      }
    },
    removeEquipment(equipmentId) {
      this.selectedEquipmentIds = this.selectedEquipmentIds.filter((id) => id !== equipmentId);
      delete this.selectedEquipmentItems[equipmentId];
    },
    getVehicleTypeName(vehicleType) {
      const types = {
        "truck_25t": "Грузовик 25т",
        "truck_45t": "Грузовик 45т",
        "truck_110t": "Трал 110т"
      };
      return types[vehicleType] || vehicleType;
    },
    formatLocationName(location) {
      return (location == null ? void 0 : location.name) || (location == null ? void 0 : location.address) || "Неизвестно";
    },
    forceRecalculateDelivery() {
      if (this.selectedEquipmentIds.length > 0) {
        this.calculateDelivery();
      }
    },
    getCsrfToken() {
      const metaTag = document.querySelector('meta[name="csrf-token"]');
      return metaTag ? metaTag.getAttribute("content") : null;
    },
    loadAvailableEquipment() {
      return __async(this, null, function* () {
        var _a;
        this.loadingEquipment = true;
        try {
          const response = yield axios.get(`/api/rental-requests/${this.request.id}/available-equipment`, {
            withCredentials: true
          });
          if (response.data.success) {
            this.availableEquipment = ((_a = response.data.data) == null ? void 0 : _a.available_equipment) || [];
            console.log("✅ Available equipment loaded:", this.availableEquipment.length);
          } else {
            console.error("❌ Failed to load equipment:", response.data.message);
            this.availableEquipment = [];
          }
        } catch (error) {
          console.error("❌ Error loading available equipment:", error);
          this.availableEquipment = [];
        } finally {
          this.loadingEquipment = false;
        }
      });
    },
    submitProposal() {
      return __async(this, null, function* () {
        this.submitting = true;
        try {
          const equipmentItems = this.selectedEquipmentIds.map((id) => {
            const item = this.selectedEquipmentItems[id];
            return {
              equipment_id: id,
              proposed_price: item.proposed_price,
              quantity: item.quantity || 1
            };
          });
          const response = yield axios.post(
            `/api/rental-requests/${this.request.id}/proposals`,
            {
              equipment_items: equipmentItems,
              message: this.proposalData.message
            },
            {
              withCredentials: true
            }
          );
          if (response.data.success) {
            this.$emit("proposal-created", response.data.data);
            this.closeModal();
          } else {
            throw new Error(response.data.message || "Ошибка отправки предложения");
          }
        } catch (error) {
          console.error("Ошибка отправки предложения:", error);
          alert("Ошибка при отправке предложения: " + error.message);
        } finally {
          this.submitting = false;
        }
      });
    },
    calculateWorkingHours() {
      if (!this.request || !this.request.rental_period_start || !this.request.rental_period_end) {
        return 8;
      }
      try {
        const start = new Date(this.request.rental_period_start);
        const end = new Date(this.request.rental_period_end);
        const days = Math.ceil((end - start) / (1e3 * 3600 * 24)) + 1;
        const rentalConditions = this.request.rental_conditions || {};
        const shiftHours = rentalConditions["hours_per_shift"] || 8;
        const shiftsPerDay = rentalConditions["shifts_per_day"] || 1;
        return days * shiftHours * shiftsPerDay;
      } catch (error) {
        console.error("❌ Error calculating working hours:", error);
        return 8;
      }
    },
    calculateDelivery() {
      return __async(this, null, function* () {
        var _a, _b;
        const ids = this.selectedEquipmentIds;
        console.log("🚚 Starting delivery calculation with equipment:", ids);
        if (ids.length === 0) {
          console.log("❌ No equipment selected, skipping delivery calculation");
          this.deliveryCalculation = {
            loading: false,
            delivery_required: false,
            delivery_cost: 0,
            distance_km: 0,
            vehicle_type: null,
            error: "Выберите технику для расчета доставки"
          };
          return;
        }
        if (this.deliveryCalculation.loading) {
          console.log("⚠️ Delivery calculation already in progress, skipping");
          return;
        }
        this.deliveryCalculation.loading = true;
        this.deliveryCalculation.error = null;
        try {
          const equipmentItems = ids.map((id) => {
            const item = this.selectedEquipmentItems[id];
            return {
              equipment_id: id,
              quantity: (item == null ? void 0 : item.quantity) || 1
            };
          });
          console.log("📤 Sending delivery calculation request:", {
            rental_request_id: this.request.id,
            equipment_items: equipmentItems
          });
          const response = yield axios.post(
            `/api/rental-requests/${this.request.id}/calculate-delivery`,
            {
              equipment_items: equipmentItems
            },
            {
              headers: {
                "X-CSRF-TOKEN": this.csrfToken,
                "X-Requested-With": "XMLHttpRequest"
              },
              timeout: 3e4
            }
          );
          console.log("📦 Delivery calculation response:", response.data);
          if (response.data.success) {
            console.log("✅ Delivery calculation successful:", response.data.data);
            this.deliveryCalculation = __spreadProps(__spreadValues({}, response.data.data), {
              loading: false,
              error: null
            });
            this.recalculatePricing();
          } else {
            throw new Error(response.data.message || "Ошибка расчета доставки");
          }
        } catch (error) {
          console.error("❌ Delivery calculation failed:", error);
          this.deliveryCalculation = {
            loading: false,
            delivery_required: false,
            delivery_cost: 0,
            distance_km: 0,
            vehicle_type: null,
            error: ((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) || error.message || "Не удалось рассчитать доставку"
          };
        }
      });
    },
    recalculatePricing() {
      const ids = this.selectedEquipmentIds;
      let totalLessorPrice = 0;
      const workingHours = this.calculateWorkingHours();
      ids.forEach((id) => {
        const selectedItem = this.selectedEquipmentItems[id];
        if (selectedItem) {
          const itemTotal = selectedItem.proposed_price * workingHours * (selectedItem.quantity || 1);
          selectedItem.item_total = itemTotal;
          totalLessorPrice += itemTotal;
        }
      });
      console.log("💰 Recalculated pricing with delivery:", {
        totalLessorPrice,
        deliveryCost: this.deliveryCalculation.delivery_cost,
        totalCustomerPrice: totalLessorPrice + (this.deliveryCalculation.delivery_cost || 0)
      });
    },
    closeModal() {
      this.$emit("close");
    },
    handleEscape(event) {
      if (event.key === "Escape") {
        this.closeModal();
      }
    },
    resetForm() {
      this.selectedEquipmentIds = [];
      this.selectedEquipmentItems = {};
      this.proposalData.message = "";
      this.deliveryCalculation = {
        loading: false,
        delivery_required: false,
        delivery_cost: 0,
        distance_km: 0,
        vehicle_type: null,
        rate_per_km: 0,
        from_location: null,
        to_location: null,
        error: null
      };
      this.selectedTemplateId = null;
      this.templatePreview = {
        show: false,
        loading: false,
        data: {}
      };
      this.showTemplatesManagement = false;
    },
    getFormattedSpecifications(equipment) {
      if (!equipment.specifications) return [];
      return equipment.formatted_specifications || [];
    },
    formatDate(dateString) {
      if (!dateString) return "—";
      try {
        return new Date(dateString).toLocaleDateString("ru-RU");
      } catch (error) {
        console.error("Ошибка форматирования даты:", error);
        return "—";
      }
    },
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
    }
  },
  beforeUnmount() {
    document.removeEventListener("keydown", this.handleEscape);
  }
};
const _hoisted_1$4 = { class: "modal-container modal-xl" };
const _hoisted_2$4 = { class: "modal-content" };
const _hoisted_3$4 = { class: "modal-header" };
const _hoisted_4$4 = { class: "modal-title" };
const _hoisted_5$4 = { class: "modal-body" };
const _hoisted_6$3 = { class: "template-section mb-4" };
const _hoisted_7$3 = { class: "d-flex justify-content-between align-items-center mb-3" };
const _hoisted_8$3 = { class: "template-controls" };
const _hoisted_9$2 = { class: "row g-2" };
const _hoisted_10$2 = { class: "col-md-6" };
const _hoisted_11$2 = ["value"];
const _hoisted_12$1 = { key: 0 };
const _hoisted_13$1 = { class: "col-md-3" };
const _hoisted_14$1 = ["disabled"];
const _hoisted_15$1 = { class: "col-md-3" };
const _hoisted_16$1 = {
  key: 0,
  class: "template-preview mt-3 p-3 border rounded bg-light"
};
const _hoisted_17$1 = { class: "preview-changes" };
const _hoisted_18$1 = {
  key: 0,
  class: "preview-item mb-2"
};
const _hoisted_19$1 = { class: "preview-text small text-muted mt-1" };
const _hoisted_20$1 = {
  key: 1,
  class: "preview-item"
};
const _hoisted_21$1 = { class: "preview-prices mt-1" };
const _hoisted_22$1 = {
  key: 2,
  class: "preview-item mt-2"
};
const _hoisted_23$1 = { class: "preview-conditions small text-muted mt-1" };
const _hoisted_24$1 = { class: "preview-actions mt-3" };
const _hoisted_25$1 = {
  key: 1,
  class: "alert alert-warning mt-2"
};
const _hoisted_26$1 = {
  key: 0,
  class: "alert alert-danger"
};
const _hoisted_27$1 = { class: "request-info mb-4 p-3 bg-light rounded" };
const _hoisted_28$1 = { class: "mb-2 text-muted" };
const _hoisted_29$1 = { class: "row small text-muted" };
const _hoisted_30$1 = { class: "col-md-6" };
const _hoisted_31$1 = { class: "col-md-6" };
const _hoisted_32$1 = {
  key: 0,
  class: "badge bg-warning ms-2"
};
const _hoisted_33$1 = {
  key: 0,
  class: "delivery-section mb-4"
};
const _hoisted_34$1 = ["disabled"];
const _hoisted_35$1 = {
  key: 0,
  class: "alert alert-info"
};
const _hoisted_36$1 = {
  key: 1,
  class: "alert alert-warning"
};
const _hoisted_37$1 = {
  key: 2,
  class: "alert alert-success"
};
const _hoisted_38$1 = { class: "row" };
const _hoisted_39$1 = { class: "col-md-4" };
const _hoisted_40$1 = { class: "col-md-4" };
const _hoisted_41$1 = { class: "col-md-4" };
const _hoisted_42$1 = { class: "fw-bold text-success" };
const _hoisted_43$1 = {
  key: 0,
  class: "mt-2 small"
};
const _hoisted_44$1 = {
  key: 3,
  class: "alert alert-secondary"
};
const _hoisted_45$1 = {
  key: 1,
  class: "bulk-proposal-info alert alert-info mb-4"
};
const _hoisted_46$1 = { class: "mb-0" };
const _hoisted_47$1 = { class: "equipment-selection mb-4" };
const _hoisted_48$1 = { class: "d-flex justify-content-between align-items-center mb-3" };
const _hoisted_49$1 = {
  key: 0,
  class: "badge bg-primary"
};
const _hoisted_50$1 = {
  key: 0,
  class: "text-center py-3"
};
const _hoisted_51$1 = {
  key: 1,
  class: "alert alert-warning"
};
const _hoisted_52$1 = {
  key: 2,
  class: "equipment-list"
};
const _hoisted_53$1 = { class: "card-body" };
const _hoisted_54$1 = { class: "row align-items-center" };
const _hoisted_55$1 = { class: "col-md-1" };
const _hoisted_56$1 = ["id", "value"];
const _hoisted_57$1 = { class: "col-md-3" };
const _hoisted_58$1 = ["for"];
const _hoisted_59$1 = { class: "small text-muted" };
const _hoisted_60$1 = { class: "col-md-4" };
const _hoisted_61$1 = {
  key: 0,
  class: "specifications small"
};
const _hoisted_62$1 = {
  key: 1,
  class: "text-muted small"
};
const _hoisted_63$1 = { class: "col-md-2 text-end" };
const _hoisted_64$1 = { class: "fw-bold text-success" };
const _hoisted_65$1 = {
  key: 0,
  class: "selected-equipment-details mt-3 p-3 bg-light rounded"
};
const _hoisted_66$1 = { class: "row align-items-end" };
const _hoisted_67$1 = { class: "col-md-8" };
const _hoisted_68$1 = ["onUpdate:modelValue", "min", "max"];
const _hoisted_69$1 = { class: "form-text" };
const _hoisted_70 = { class: "col-md-2" };
const _hoisted_71 = { class: "small text-muted" };
const _hoisted_72 = { class: "fw-bold text-success fs-6" };
const _hoisted_73 = { class: "text-muted" };
const _hoisted_74 = { class: "col-md-2 text-end" };
const _hoisted_75 = ["onClick"];
const _hoisted_76 = {
  key: 2,
  class: "proposal-summary"
};
const _hoisted_77 = { class: "selected-equipment-table mb-4" };
const _hoisted_78 = { class: "table-responsive" };
const _hoisted_79 = { class: "table table-sm table-bordered" };
const _hoisted_80 = { class: "table-light" };
const _hoisted_81 = {
  key: 0,
  class: "text-end"
};
const _hoisted_82 = { class: "small text-muted" };
const _hoisted_83 = { class: "text-end" };
const _hoisted_84 = { class: "text-end fw-bold text-success" };
const _hoisted_85 = {
  key: 0,
  class: "text-end"
};
const _hoisted_86 = { class: "text-end fw-bold text-success" };
const _hoisted_87 = { class: "table-light" };
const _hoisted_88 = ["colspan"];
const _hoisted_89 = { class: "text-end fw-bold fs-6 text-primary" };
const _hoisted_90 = { class: "pricing-info alert alert-info" };
const _hoisted_91 = { class: "alert-heading" };
const _hoisted_92 = { class: "mb-2 small" };
const _hoisted_93 = {
  key: 0,
  class: "mb-2 small"
};
const _hoisted_94 = { class: "text-muted" };
const _hoisted_95 = { class: "mb-0 small" };
const _hoisted_96 = { class: "mb-3" };
const _hoisted_97 = { class: "form-text text-end" };
const _hoisted_98 = { class: "modal-footer" };
const _hoisted_99 = ["disabled"];
const _hoisted_100 = { class: "modal-container modal-lg" };
const _hoisted_101 = { class: "modal-content" };
const _hoisted_102 = { class: "modal-header" };
const _hoisted_103 = { class: "modal-body" };
const _hoisted_104 = { class: "templates-management" };
const _hoisted_105 = { class: "stats-section mb-4 p-3 bg-light rounded" };
const _hoisted_106 = { class: "row text-center" };
const _hoisted_107 = { class: "col-md-4" };
const _hoisted_108 = { class: "stat-value text-primary" };
const _hoisted_109 = { class: "col-md-4" };
const _hoisted_110 = { class: "stat-value text-success" };
const _hoisted_111 = { class: "col-md-4" };
const _hoisted_112 = { class: "stat-value text-info" };
const _hoisted_113 = { class: "templates-list" };
const _hoisted_114 = { class: "d-flex justify-content-between align-items-center mb-3" };
const _hoisted_115 = {
  key: 0,
  class: "text-center py-3"
};
const _hoisted_116 = {
  key: 1,
  class: "alert alert-info"
};
const _hoisted_117 = {
  key: 2,
  class: "template-items"
};
const _hoisted_118 = { class: "card-body" };
const _hoisted_119 = { class: "row align-items-center" };
const _hoisted_120 = { class: "col-md-8" };
const _hoisted_121 = { class: "card-title mb-1" };
const _hoisted_122 = {
  key: 0,
  class: "badge bg-secondary ms-2"
};
const _hoisted_123 = { class: "card-text small text-muted mb-1" };
const _hoisted_124 = { class: "template-meta small text-muted" };
const _hoisted_125 = { class: "me-3" };
const _hoisted_126 = { class: "me-3" };
const _hoisted_127 = { class: "col-md-4 text-end" };
const _hoisted_128 = ["onClick", "disabled"];
const _hoisted_129 = ["onClick"];
const _hoisted_130 = ["onClick"];
const _hoisted_131 = { class: "modal-footer" };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  var _a;
  return openBlock(), createElementBlock(Fragment, null, [
    $props.show ? (openBlock(), createElementBlock("div", {
      key: 0,
      class: "modal-overlay",
      onClick: _cache[14] || (_cache[14] = withModifiers((...args) => $options.closeModal && $options.closeModal(...args), ["self"]))
    }, [
      createBaseVNode("div", _hoisted_1$4, [
        createBaseVNode("div", _hoisted_2$4, [
          createBaseVNode("div", _hoisted_3$4, [
            createBaseVNode("h5", _hoisted_4$4, [
              _cache[19] || (_cache[19] = createBaseVNode("i", { class: "fas fa-paper-plane me-2 text-primary" }, null, -1)),
              createTextVNode(" " + toDisplayString($options.isBulkProposal ? "Предложить несколько видов техники" : "Предложить технику для заявки"), 1)
            ]),
            createBaseVNode("button", {
              type: "button",
              class: "btn-close",
              onClick: _cache[0] || (_cache[0] = (...args) => $options.closeModal && $options.closeModal(...args)),
              "aria-label": "Close"
            })
          ]),
          createBaseVNode("div", _hoisted_5$4, [
            createBaseVNode("div", _hoisted_6$3, [
              createBaseVNode("div", _hoisted_7$3, [
                _cache[21] || (_cache[21] = createBaseVNode("h6", { class: "mb-0" }, [
                  createBaseVNode("i", { class: "fas fa-bolt me-2 text-warning" }),
                  createTextVNode(" Быстрые шаблоны ")
                ], -1)),
                createBaseVNode("button", {
                  class: "btn btn-outline-secondary btn-sm",
                  onClick: _cache[1] || (_cache[1] = (...args) => $options.showTemplatesModal && $options.showTemplatesModal(...args))
                }, [..._cache[20] || (_cache[20] = [
                  createBaseVNode("i", { class: "fas fa-cog me-1" }, null, -1),
                  createTextVNode("Управление шаблонами ", -1)
                ])])
              ]),
              createBaseVNode("div", _hoisted_8$3, [
                createBaseVNode("div", _hoisted_9$2, [
                  createBaseVNode("div", _hoisted_10$2, [
                    _cache[23] || (_cache[23] = createBaseVNode("label", { class: "form-label small" }, "Выберите шаблон", -1)),
                    withDirectives(createBaseVNode("select", {
                      "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.selectedTemplateId = $event),
                      class: "form-select form-select-sm",
                      onChange: _cache[3] || (_cache[3] = (...args) => $options.onTemplateSelect && $options.onTemplateSelect(...args))
                    }, [
                      _cache[22] || (_cache[22] = createBaseVNode("option", { value: "" }, "-- Выберите шаблон --", -1)),
                      (openBlock(true), createElementBlock(Fragment, null, renderList($data.availableTemplates, (template) => {
                        return openBlock(), createElementBlock("option", {
                          key: template.id,
                          value: template.id
                        }, [
                          createTextVNode(toDisplayString(template.name) + " ", 1),
                          template.usage_count ? (openBlock(), createElementBlock("span", _hoisted_12$1, "(использован " + toDisplayString(template.usage_count) + " раз)", 1)) : createCommentVNode("", true)
                        ], 8, _hoisted_11$2);
                      }), 128))
                    ], 544), [
                      [vModelSelect, $data.selectedTemplateId]
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_13$1, [
                    _cache[25] || (_cache[25] = createBaseVNode("label", { class: "form-label small" }, " ", -1)),
                    createBaseVNode("button", {
                      type: "button",
                      class: "btn btn-primary btn-sm w-100",
                      disabled: !$data.selectedTemplateId || $data.templatePreview.loading || $data.selectedEquipmentIds.length === 0,
                      onClick: _cache[4] || (_cache[4] = (...args) => $options.applyTemplate && $options.applyTemplate(...args))
                    }, [
                      _cache[24] || (_cache[24] = createBaseVNode("i", { class: "fas fa-magic me-1" }, null, -1)),
                      createTextVNode(" " + toDisplayString($data.templatePreview.loading ? "Применение..." : "Применить"), 1)
                    ], 8, _hoisted_14$1)
                  ]),
                  createBaseVNode("div", _hoisted_15$1, [
                    _cache[27] || (_cache[27] = createBaseVNode("label", { class: "form-label small" }, " ", -1)),
                    createBaseVNode("button", {
                      type: "button",
                      class: "btn btn-outline-secondary btn-sm w-100",
                      onClick: _cache[5] || (_cache[5] = (...args) => $options.clearTemplate && $options.clearTemplate(...args))
                    }, [..._cache[26] || (_cache[26] = [
                      createBaseVNode("i", { class: "fas fa-times me-1" }, null, -1),
                      createTextVNode(" Очистить ", -1)
                    ])])
                  ])
                ]),
                $data.templatePreview.show ? (openBlock(), createElementBlock("div", _hoisted_16$1, [
                  _cache[32] || (_cache[32] = createBaseVNode("h6", { class: "text-primary mb-2" }, [
                    createBaseVNode("i", { class: "fas fa-eye me-1" }),
                    createTextVNode(" Предпросмотр изменений ")
                  ], -1)),
                  createBaseVNode("div", _hoisted_17$1, [
                    $data.templatePreview.data.message ? (openBlock(), createElementBlock("div", _hoisted_18$1, [
                      _cache[28] || (_cache[28] = createBaseVNode("strong", null, "Сообщение:", -1)),
                      createBaseVNode("div", _hoisted_19$1, toDisplayString($data.templatePreview.data.message), 1)
                    ])) : createCommentVNode("", true),
                    $data.templatePreview.data.prices && Object.keys($data.templatePreview.data.prices).length > 0 ? (openBlock(), createElementBlock("div", _hoisted_20$1, [
                      _cache[29] || (_cache[29] = createBaseVNode("strong", null, "Цены:", -1)),
                      createBaseVNode("div", _hoisted_21$1, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList($data.templatePreview.data.prices, (price, equipmentId) => {
                          return openBlock(), createElementBlock("div", {
                            key: equipmentId,
                            class: "small text-muted"
                          }, toDisplayString($options.getEquipmentName(equipmentId)) + ": " + toDisplayString($options.formatCurrency(price)) + "/час ", 1);
                        }), 128))
                      ])
                    ])) : createCommentVNode("", true),
                    $data.templatePreview.data.conditions ? (openBlock(), createElementBlock("div", _hoisted_22$1, [
                      _cache[30] || (_cache[30] = createBaseVNode("strong", null, "Условия:", -1)),
                      createBaseVNode("div", _hoisted_23$1, toDisplayString($data.templatePreview.data.conditions), 1)
                    ])) : createCommentVNode("", true)
                  ]),
                  createBaseVNode("div", _hoisted_24$1, [
                    createBaseVNode("button", {
                      type: "button",
                      class: "btn btn-success btn-sm me-2",
                      onClick: _cache[6] || (_cache[6] = (...args) => $options.confirmTemplateApply && $options.confirmTemplateApply(...args))
                    }, [..._cache[31] || (_cache[31] = [
                      createBaseVNode("i", { class: "fas fa-check me-1" }, null, -1),
                      createTextVNode(" Подтвердить применение ", -1)
                    ])]),
                    createBaseVNode("button", {
                      type: "button",
                      class: "btn btn-outline-secondary btn-sm",
                      onClick: _cache[7] || (_cache[7] = (...args) => $options.cancelTemplateApply && $options.cancelTemplateApply(...args))
                    }, " Отмена ")
                  ])
                ])) : createCommentVNode("", true),
                $data.selectedEquipmentIds.length === 0 && $data.selectedTemplateId ? (openBlock(), createElementBlock("div", _hoisted_25$1, [..._cache[33] || (_cache[33] = [
                  createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1),
                  createTextVNode(" Выберите технику для применения шаблона ", -1)
                ])])) : createCommentVNode("", true)
              ])
            ]),
            !$props.request ? (openBlock(), createElementBlock("div", _hoisted_26$1, [..._cache[34] || (_cache[34] = [
              createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1),
              createTextVNode(" Ошибка: данные заявки не загружены ", -1)
            ])])) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
              createBaseVNode("div", _hoisted_27$1, [
                createBaseVNode("h6", null, toDisplayString($props.request.title), 1),
                createBaseVNode("p", _hoisted_28$1, toDisplayString($props.request.description), 1),
                createBaseVNode("div", _hoisted_29$1, [
                  createBaseVNode("div", _hoisted_30$1, [
                    _cache[35] || (_cache[35] = createBaseVNode("i", { class: "fas fa-calendar-alt me-1" }, null, -1)),
                    createTextVNode(" " + toDisplayString($options.formatDate($props.request.rental_period_start)) + " - " + toDisplayString($options.formatDate($props.request.rental_period_end)), 1)
                  ]),
                  createBaseVNode("div", _hoisted_31$1, [
                    _cache[37] || (_cache[37] = createBaseVNode("i", { class: "fas fa-map-marker-alt me-1" }, null, -1)),
                    createTextVNode(" " + toDisplayString((_a = $props.request.location) == null ? void 0 : _a.name) + " ", 1),
                    $props.request.delivery_required ? (openBlock(), createElementBlock("span", _hoisted_32$1, [..._cache[36] || (_cache[36] = [
                      createBaseVNode("i", { class: "fas fa-truck me-1" }, null, -1),
                      createTextVNode("Требуется доставка ", -1)
                    ])])) : createCommentVNode("", true)
                  ])
                ])
              ]),
              $props.request.delivery_required ? (openBlock(), createElementBlock("div", _hoisted_33$1, [
                _cache[48] || (_cache[48] = createBaseVNode("h6", { class: "mb-3" }, [
                  createBaseVNode("i", { class: "fas fa-truck me-2" }),
                  createTextVNode(" Информация о доставке ")
                ], -1)),
                createBaseVNode("button", {
                  type: "button",
                  class: "btn btn-sm btn-outline-secondary mb-3",
                  onClick: _cache[8] || (_cache[8] = (...args) => $options.forceRecalculateDelivery && $options.forceRecalculateDelivery(...args)),
                  disabled: $data.deliveryCalculation.loading
                }, [
                  _cache[38] || (_cache[38] = createBaseVNode("i", { class: "fas fa-redo me-1" }, null, -1)),
                  createTextVNode(" " + toDisplayString($data.deliveryCalculation.loading ? "Расчет..." : "Пересчитать"), 1)
                ], 8, _hoisted_34$1),
                $data.deliveryCalculation.loading ? (openBlock(), createElementBlock("div", _hoisted_35$1, [..._cache[39] || (_cache[39] = [
                  createBaseVNode("div", {
                    class: "spinner-border spinner-border-sm me-2",
                    role: "status"
                  }, null, -1),
                  createTextVNode(" Расчет стоимости доставки... ", -1)
                ])])) : $data.deliveryCalculation.error ? (openBlock(), createElementBlock("div", _hoisted_36$1, [
                  _cache[40] || (_cache[40] = createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1)),
                  createTextVNode(" " + toDisplayString($data.deliveryCalculation.error), 1)
                ])) : $data.deliveryCalculation.delivery_required ? (openBlock(), createElementBlock("div", _hoisted_37$1, [
                  createBaseVNode("div", _hoisted_38$1, [
                    createBaseVNode("div", _hoisted_39$1, [
                      _cache[41] || (_cache[41] = createBaseVNode("strong", null, "Расстояние:", -1)),
                      createTextVNode(" " + toDisplayString($data.deliveryCalculation.distance_km) + " км ", 1)
                    ]),
                    createBaseVNode("div", _hoisted_40$1, [
                      _cache[42] || (_cache[42] = createBaseVNode("strong", null, "Тип транспорта:", -1)),
                      createTextVNode(" " + toDisplayString($options.getVehicleTypeName($data.deliveryCalculation.vehicle_type)), 1)
                    ]),
                    createBaseVNode("div", _hoisted_41$1, [
                      _cache[43] || (_cache[43] = createBaseVNode("strong", null, "Стоимость доставки:", -1)),
                      createBaseVNode("span", _hoisted_42$1, toDisplayString($options.formatCurrency($data.deliveryCalculation.delivery_cost)), 1)
                    ])
                  ]),
                  $data.deliveryCalculation.from_location && $data.deliveryCalculation.to_location ? (openBlock(), createElementBlock("div", _hoisted_43$1, [
                    _cache[44] || (_cache[44] = createBaseVNode("i", { class: "fas fa-route me-1" }, null, -1)),
                    _cache[45] || (_cache[45] = createTextVNode(" Маршрут: ", -1)),
                    createBaseVNode("strong", null, toDisplayString($options.formatLocationName($data.deliveryCalculation.from_location)), 1),
                    _cache[46] || (_cache[46] = createTextVNode(" → ", -1)),
                    createBaseVNode("strong", null, toDisplayString($options.formatLocationName($data.deliveryCalculation.to_location)), 1)
                  ])) : createCommentVNode("", true)
                ])) : (openBlock(), createElementBlock("div", _hoisted_44$1, [..._cache[47] || (_cache[47] = [
                  createBaseVNode("i", { class: "fas fa-info-circle me-2" }, null, -1),
                  createTextVNode(" Доставка не требуется или не может быть рассчитана ", -1)
                ])]))
              ])) : createCommentVNode("", true),
              $options.isBulkProposal ? (openBlock(), createElementBlock("div", _hoisted_45$1, [
                _cache[51] || (_cache[51] = createBaseVNode("h6", null, [
                  createBaseVNode("i", { class: "fas fa-layer-group me-2" }),
                  createTextVNode("Комплексное предложение")
                ], -1)),
                createBaseVNode("p", _hoisted_46$1, [
                  _cache[49] || (_cache[49] = createTextVNode(" Вы предлагаете ", -1)),
                  createBaseVNode("strong", null, toDisplayString($data.selectedEquipmentIds.length) + " видов техники", 1),
                  _cache[50] || (_cache[50] = createTextVNode(". Арендатор увидит конкретные модели из вашего каталога. ", -1))
                ])
              ])) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_47$1, [
                createBaseVNode("div", _hoisted_48$1, [
                  _cache[52] || (_cache[52] = createBaseVNode("h6", { class: "mb-0" }, "Выберите технику из вашего каталога", -1)),
                  $data.selectedEquipmentIds.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_49$1, " Выбрано: " + toDisplayString($data.selectedEquipmentIds.length), 1)) : createCommentVNode("", true)
                ]),
                $data.loadingEquipment ? (openBlock(), createElementBlock("div", _hoisted_50$1, [..._cache[53] || (_cache[53] = [
                  createBaseVNode("div", {
                    class: "spinner-border text-primary",
                    role: "status"
                  }, [
                    createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
                  ], -1),
                  createBaseVNode("p", { class: "mt-2 small text-muted" }, "Загрузка вашей техники...", -1)
                ])])) : $data.availableEquipment.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_51$1, [..._cache[54] || (_cache[54] = [
                  createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1),
                  createTextVNode(" У вас нет подходящей техники для этой заявки ", -1)
                ])])) : (openBlock(), createElementBlock("div", _hoisted_52$1, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($data.availableEquipment, (item) => {
                    return openBlock(), createElementBlock("div", {
                      key: item.equipment.id,
                      class: normalizeClass(["equipment-item card mb-3", { "border-primary": $options.isEquipmentSelected(item.equipment.id) }])
                    }, [
                      createBaseVNode("div", _hoisted_53$1, [
                        createBaseVNode("div", _hoisted_54$1, [
                          createBaseVNode("div", _hoisted_55$1, [
                            withDirectives(createBaseVNode("input", {
                              type: "checkbox",
                              id: `equipment_${item.equipment.id}`,
                              value: item.equipment.id,
                              "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => $data.selectedEquipmentIds = $event),
                              class: "form-check-input"
                            }, null, 8, _hoisted_56$1), [
                              [vModelCheckbox, $data.selectedEquipmentIds]
                            ])
                          ]),
                          createBaseVNode("div", _hoisted_57$1, [
                            createBaseVNode("label", {
                              for: `equipment_${item.equipment.id}`,
                              class: "form-check-label cursor-pointer"
                            }, [
                              createBaseVNode("strong", null, toDisplayString(item.equipment.title), 1)
                            ], 8, _hoisted_58$1),
                            createBaseVNode("div", _hoisted_59$1, toDisplayString(item.equipment.brand) + " " + toDisplayString(item.equipment.model), 1)
                          ]),
                          createBaseVNode("div", _hoisted_60$1, [
                            item.equipment.specifications ? (openBlock(), createElementBlock("div", _hoisted_61$1, [
                              (openBlock(true), createElementBlock(Fragment, null, renderList($options.getFormattedSpecifications(item.equipment), (spec) => {
                                return openBlock(), createElementBlock("div", {
                                  key: spec.key,
                                  class: "spec-item text-muted"
                                }, toDisplayString(spec.formatted || spec), 1);
                              }), 128))
                            ])) : (openBlock(), createElementBlock("div", _hoisted_62$1, " Нет спецификаций "))
                          ]),
                          createBaseVNode("div", _hoisted_63$1, [
                            createBaseVNode("div", _hoisted_64$1, toDisplayString($options.formatCurrency(item.recommended_lessor_price)) + "/час ", 1),
                            _cache[55] || (_cache[55] = createBaseVNode("small", { class: "text-muted" }, " Рекомендуемая цена ", -1))
                          ]),
                          _cache[56] || (_cache[56] = createBaseVNode("div", { class: "col-md-1" }, [
                            createBaseVNode("span", { class: "badge bg-success" }, " Доступно ")
                          ], -1))
                        ]),
                        $options.isEquipmentSelected(item.equipment.id) ? (openBlock(), createElementBlock("div", _hoisted_65$1, [
                          createBaseVNode("div", _hoisted_66$1, [
                            createBaseVNode("div", _hoisted_67$1, [
                              _cache[57] || (_cache[57] = createBaseVNode("label", { class: "form-label small" }, "Ваша цена за эту технику (₽/час)", -1)),
                              withDirectives(createBaseVNode("input", {
                                type: "number",
                                "onUpdate:modelValue": ($event) => $options.getSelectedEquipment(item.equipment.id).proposed_price = $event,
                                class: "form-control",
                                min: $data.minPrice,
                                max: $data.maxPrice,
                                step: "50",
                                onInput: _cache[10] || (_cache[10] = (...args) => $options.recalculatePricing && $options.recalculatePricing(...args))
                              }, null, 40, _hoisted_68$1), [
                                [vModelText, $options.getSelectedEquipment(item.equipment.id).proposed_price]
                              ]),
                              createBaseVNode("div", _hoisted_69$1, " Рекомендуемая: " + toDisplayString($options.formatCurrency(item.recommended_lessor_price)), 1)
                            ]),
                            createBaseVNode("div", _hoisted_70, [
                              createBaseVNode("div", _hoisted_71, [
                                _cache[58] || (_cache[58] = createBaseVNode("div", null, "Стоимость:", -1)),
                                createBaseVNode("div", _hoisted_72, toDisplayString($options.formatCurrency($options.getSelectedEquipment(item.equipment.id).item_total)), 1),
                                createBaseVNode("div", _hoisted_73, " за " + toDisplayString($options.calculateWorkingHours()) + " часов ", 1)
                              ])
                            ]),
                            createBaseVNode("div", _hoisted_74, [
                              createBaseVNode("button", {
                                type: "button",
                                class: "btn btn-outline-danger btn-sm",
                                onClick: ($event) => $options.removeEquipment(item.equipment.id)
                              }, [..._cache[59] || (_cache[59] = [
                                createBaseVNode("i", { class: "fas fa-times" }, null, -1),
                                createTextVNode(" Убрать ", -1)
                              ])], 8, _hoisted_75)
                            ])
                          ])
                        ])) : createCommentVNode("", true)
                      ])
                    ], 2);
                  }), 128))
                ]))
              ]),
              $data.selectedEquipmentIds.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_76, [
                _cache[70] || (_cache[70] = createBaseVNode("h6", { class: "mb-3" }, "Сводка предложения", -1)),
                createBaseVNode("div", _hoisted_77, [
                  createBaseVNode("div", _hoisted_78, [
                    createBaseVNode("table", _hoisted_79, [
                      createBaseVNode("thead", _hoisted_80, [
                        createBaseVNode("tr", null, [
                          _cache[60] || (_cache[60] = createBaseVNode("th", null, "Техника", -1)),
                          _cache[61] || (_cache[61] = createBaseVNode("th", { class: "text-end" }, "Цена (₽/час)", -1)),
                          _cache[62] || (_cache[62] = createBaseVNode("th", { class: "text-end" }, "Стоимость", -1)),
                          $data.deliveryCalculation.delivery_required ? (openBlock(), createElementBlock("th", _hoisted_81, "Доставка")) : createCommentVNode("", true),
                          _cache[63] || (_cache[63] = createBaseVNode("th", { class: "text-end" }, "Итого", -1))
                        ])
                      ]),
                      createBaseVNode("tbody", null, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList($options.selectedEquipmentDetails, (item) => {
                          return openBlock(), createElementBlock("tr", {
                            key: item.equipment.id
                          }, [
                            createBaseVNode("td", null, [
                              createBaseVNode("strong", null, toDisplayString(item.equipment.title), 1),
                              createBaseVNode("div", _hoisted_82, toDisplayString(item.equipment.brand) + " " + toDisplayString(item.equipment.model), 1)
                            ]),
                            createBaseVNode("td", _hoisted_83, toDisplayString($options.formatCurrency(item.proposed_price)), 1),
                            createBaseVNode("td", _hoisted_84, toDisplayString($options.formatCurrency(item.item_total)), 1),
                            $data.deliveryCalculation.delivery_required ? (openBlock(), createElementBlock("td", _hoisted_85, toDisplayString($options.formatCurrency($options.deliveryCostPerItem)), 1)) : createCommentVNode("", true),
                            createBaseVNode("td", _hoisted_86, toDisplayString($options.formatCurrency($data.deliveryCalculation.delivery_required ? item.item_total + $options.deliveryCostPerItem : item.item_total)), 1)
                          ]);
                        }), 128))
                      ]),
                      createBaseVNode("tfoot", _hoisted_87, [
                        createBaseVNode("tr", null, [
                          createBaseVNode("td", {
                            class: "text-end fw-bold",
                            colspan: $data.deliveryCalculation.delivery_required ? 4 : 3
                          }, " Общая стоимость: ", 8, _hoisted_88),
                          createBaseVNode("td", _hoisted_89, toDisplayString($options.formatCurrency($options.totalPriceWithDelivery)), 1)
                        ])
                      ])
                    ])
                  ])
                ]),
                createBaseVNode("div", _hoisted_90, [
                  createBaseVNode("h6", _hoisted_91, [
                    _cache[64] || (_cache[64] = createBaseVNode("i", { class: "fas fa-info-circle me-2" }, null, -1)),
                    createTextVNode(" " + toDisplayString($options.isBulkProposal ? "Комплексное предложение" : "Предложение"), 1)
                  ]),
                  createBaseVNode("p", _hoisted_92, [
                    _cache[65] || (_cache[65] = createBaseVNode("strong", null, "Ваш общий доход:", -1)),
                    createTextVNode(" " + toDisplayString($options.formatCurrency($options.totalLessorPrice)), 1)
                  ]),
                  $data.deliveryCalculation.delivery_required ? (openBlock(), createElementBlock("p", _hoisted_93, [
                    _cache[66] || (_cache[66] = createBaseVNode("strong", null, "Стоимость доставки:", -1)),
                    createTextVNode(" " + toDisplayString($options.formatCurrency($data.deliveryCalculation.delivery_cost)) + " ", 1),
                    createBaseVNode("span", _hoisted_94, "(" + toDisplayString($data.deliveryCalculation.distance_km) + " км)", 1)
                  ])) : createCommentVNode("", true),
                  createBaseVNode("p", _hoisted_95, [
                    _cache[67] || (_cache[67] = createBaseVNode("strong", null, "Общая стоимость для арендатора:", -1)),
                    createTextVNode(" " + toDisplayString($options.formatCurrency($options.totalPriceWithDelivery)), 1)
                  ]),
                  _cache[68] || (_cache[68] = createBaseVNode("p", { class: "mb-0 small text-muted mt-1" }, [
                    createBaseVNode("i", { class: "fas fa-check-circle text-success me-1" }),
                    createTextVNode(" Арендатор увидит полную стоимость с доставкой ")
                  ], -1))
                ]),
                createBaseVNode("div", _hoisted_96, [
                  _cache[69] || (_cache[69] = createBaseVNode("label", { class: "form-label" }, "Сообщение для арендатора", -1)),
                  withDirectives(createBaseVNode("textarea", {
                    "onUpdate:modelValue": _cache[11] || (_cache[11] = ($event) => $data.proposalData.message = $event),
                    class: "form-control",
                    rows: "3",
                    placeholder: "Расскажите о вашей технике и условиях...",
                    maxlength: 1e3
                  }, null, 512), [
                    [vModelText, $data.proposalData.message]
                  ]),
                  createBaseVNode("div", _hoisted_97, toDisplayString($data.proposalData.message.length) + "/1000 символов ", 1)
                ])
              ])) : createCommentVNode("", true)
            ], 64))
          ]),
          createBaseVNode("div", _hoisted_98, [
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-secondary",
              onClick: _cache[12] || (_cache[12] = (...args) => $options.closeModal && $options.closeModal(...args))
            }, [..._cache[71] || (_cache[71] = [
              createBaseVNode("i", { class: "fas fa-times me-2" }, null, -1),
              createTextVNode("Отмена ", -1)
            ])]),
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-primary",
              disabled: !$options.canSubmitProposal,
              onClick: _cache[13] || (_cache[13] = (...args) => $options.submitProposal && $options.submitProposal(...args))
            }, [
              _cache[72] || (_cache[72] = createBaseVNode("i", { class: "fas fa-paper-plane me-2" }, null, -1)),
              createTextVNode(" " + toDisplayString($data.submitting ? "Отправка..." : $options.submitButtonText), 1)
            ], 8, _hoisted_99)
          ])
        ])
      ])
    ])) : createCommentVNode("", true),
    $data.showTemplatesManagement ? (openBlock(), createElementBlock("div", {
      key: 1,
      class: "modal-overlay",
      onClick: _cache[18] || (_cache[18] = withModifiers(($event) => $data.showTemplatesManagement = false, ["self"]))
    }, [
      createBaseVNode("div", _hoisted_100, [
        createBaseVNode("div", _hoisted_101, [
          createBaseVNode("div", _hoisted_102, [
            _cache[73] || (_cache[73] = createBaseVNode("h5", { class: "modal-title" }, [
              createBaseVNode("i", { class: "fas fa-cogs me-2" }),
              createTextVNode(" Управление шаблонами предложений ")
            ], -1)),
            createBaseVNode("button", {
              type: "button",
              class: "btn-close",
              onClick: _cache[15] || (_cache[15] = ($event) => $data.showTemplatesManagement = false),
              "aria-label": "Close"
            })
          ]),
          createBaseVNode("div", _hoisted_103, [
            createBaseVNode("div", _hoisted_104, [
              createBaseVNode("div", _hoisted_105, [
                _cache[77] || (_cache[77] = createBaseVNode("h6", null, "Статистика шаблонов", -1)),
                createBaseVNode("div", _hoisted_106, [
                  createBaseVNode("div", _hoisted_107, [
                    createBaseVNode("div", _hoisted_108, toDisplayString($data.templatesStats.total_templates || 0), 1),
                    _cache[74] || (_cache[74] = createBaseVNode("div", { class: "stat-label small text-muted" }, "Всего шаблонов", -1))
                  ]),
                  createBaseVNode("div", _hoisted_109, [
                    createBaseVNode("div", _hoisted_110, toDisplayString($data.templatesStats.total_usage || 0), 1),
                    _cache[75] || (_cache[75] = createBaseVNode("div", { class: "stat-label small text-muted" }, "Всего применений", -1))
                  ]),
                  createBaseVNode("div", _hoisted_111, [
                    createBaseVNode("div", _hoisted_112, toDisplayString($data.templatesStats.average_success_rate || 0) + "%", 1),
                    _cache[76] || (_cache[76] = createBaseVNode("div", { class: "stat-label small text-muted" }, "Успешность", -1))
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_113, [
                createBaseVNode("div", _hoisted_114, [
                  _cache[79] || (_cache[79] = createBaseVNode("h6", { class: "mb-0" }, "Мои шаблоны", -1)),
                  createBaseVNode("button", {
                    class: "btn btn-primary btn-sm",
                    onClick: _cache[16] || (_cache[16] = (...args) => $options.createNewTemplate && $options.createNewTemplate(...args))
                  }, [..._cache[78] || (_cache[78] = [
                    createBaseVNode("i", { class: "fas fa-plus me-1" }, null, -1),
                    createTextVNode("Создать шаблон ", -1)
                  ])])
                ]),
                $data.templatesLoading ? (openBlock(), createElementBlock("div", _hoisted_115, [..._cache[80] || (_cache[80] = [
                  createBaseVNode("div", {
                    class: "spinner-border text-primary",
                    role: "status"
                  }, [
                    createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
                  ], -1)
                ])])) : $data.availableTemplates.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_116, [..._cache[81] || (_cache[81] = [
                  createBaseVNode("i", { class: "fas fa-info-circle me-2" }, null, -1),
                  createTextVNode(" У вас пока нет шаблонов предложений ", -1)
                ])])) : (openBlock(), createElementBlock("div", _hoisted_117, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($data.availableTemplates, (template) => {
                    var _a2, _b;
                    return openBlock(), createElementBlock("div", {
                      key: template.id,
                      class: normalizeClass(["template-item card mb-3", { "border-success": template.is_active, "border-secondary": !template.is_active }])
                    }, [
                      createBaseVNode("div", _hoisted_118, [
                        createBaseVNode("div", _hoisted_119, [
                          createBaseVNode("div", _hoisted_120, [
                            createBaseVNode("h6", _hoisted_121, [
                              createTextVNode(toDisplayString(template.name) + " ", 1),
                              !template.is_active ? (openBlock(), createElementBlock("span", _hoisted_122, "Неактивен")) : createCommentVNode("", true)
                            ]),
                            createBaseVNode("p", _hoisted_123, toDisplayString((_a2 = template.message) == null ? void 0 : _a2.substring(0, 100)) + "... ", 1),
                            createBaseVNode("div", _hoisted_124, [
                              createBaseVNode("span", _hoisted_125, [
                                _cache[82] || (_cache[82] = createBaseVNode("i", { class: "fas fa-tag me-1" }, null, -1)),
                                createTextVNode(" " + toDisplayString(((_b = template.category) == null ? void 0 : _b.name) || "Без категории"), 1)
                              ]),
                              createBaseVNode("span", _hoisted_126, [
                                _cache[83] || (_cache[83] = createBaseVNode("i", { class: "fas fa-ruble-sign me-1" }, null, -1)),
                                createTextVNode(" " + toDisplayString($options.formatCurrency(template.proposed_price)) + "/час ", 1)
                              ]),
                              createBaseVNode("span", null, [
                                _cache[84] || (_cache[84] = createBaseVNode("i", { class: "fas fa-play-circle me-1" }, null, -1)),
                                createTextVNode(" Использован " + toDisplayString(template.usage_count || 0) + " раз ", 1)
                              ])
                            ])
                          ]),
                          createBaseVNode("div", _hoisted_127, [
                            createBaseVNode("button", {
                              class: "btn btn-success btn-sm me-1",
                              onClick: ($event) => $options.applyTemplateFromManagement(template),
                              disabled: $data.selectedEquipmentIds.length === 0
                            }, [..._cache[85] || (_cache[85] = [
                              createBaseVNode("i", { class: "fas fa-magic me-1" }, null, -1),
                              createTextVNode("Применить ", -1)
                            ])], 8, _hoisted_128),
                            createBaseVNode("button", {
                              class: "btn btn-outline-primary btn-sm me-1",
                              onClick: ($event) => $options.editTemplate(template)
                            }, [..._cache[86] || (_cache[86] = [
                              createBaseVNode("i", { class: "fas fa-edit" }, null, -1)
                            ])], 8, _hoisted_129),
                            createBaseVNode("button", {
                              class: "btn btn-outline-danger btn-sm",
                              onClick: ($event) => $options.deleteTemplate(template)
                            }, [..._cache[87] || (_cache[87] = [
                              createBaseVNode("i", { class: "fas fa-trash" }, null, -1)
                            ])], 8, _hoisted_130)
                          ])
                        ])
                      ])
                    ], 2);
                  }), 128))
                ]))
              ])
            ])
          ]),
          createBaseVNode("div", _hoisted_131, [
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-secondary",
              onClick: _cache[17] || (_cache[17] = ($event) => $data.showTemplatesManagement = false)
            }, " Закрыть ")
          ])
        ])
      ])
    ])) : createCommentVNode("", true)
  ], 64);
}
const PublicProposalModal = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__scopeId", "data-v-bca22720"]]);
const _sfc_main$3 = {
  name: "ConditionItem",
  props: {
    condition: {
      type: Object,
      required: true
    }
  }
};
const _hoisted_1$3 = { class: "condition-item" };
const _hoisted_2$3 = { class: "condition-icon" };
const _hoisted_3$3 = { class: "condition-content" };
const _hoisted_4$3 = { class: "condition-label" };
const _hoisted_5$3 = { class: "condition-value" };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$3, [
    createBaseVNode("div", _hoisted_2$3, [
      createBaseVNode("i", {
        class: normalizeClass(["fas", $props.condition.icon])
      }, null, 2)
    ]),
    createBaseVNode("div", _hoisted_3$3, [
      createBaseVNode("div", _hoisted_4$3, toDisplayString($props.condition.label), 1),
      createBaseVNode("div", _hoisted_5$3, toDisplayString($props.condition.value), 1)
    ])
  ]);
}
const ConditionItem = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-4eed2d50"]]);
const _sfc_main$2 = {
  name: "PublicRentalConditionsDisplay",
  components: {
    ConditionItem
  },
  props: {
    conditions: {
      type: Object,
      default: () => ({})
    },
    showFull: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    hasConditions() {
      return this.conditions && Object.keys(this.conditions).length > 0;
    },
    hasExtendedConditions() {
      const extendedKeys = ["transportation_organized_by", "gsm_payment", "accommodation_payment", "extension_possibility", "minimum_rental_period"];
      return extendedKeys.some((key) => this.conditions[key] !== void 0);
    },
    basicConditions() {
      if (!this.hasConditions) return [];
      const basicKeys = ["payment_type", "hours_per_shift", "shifts_per_day", "operator_included"];
      return this.filterAndFormatConditions(basicKeys);
    },
    extendedConditions() {
      if (!this.hasConditions || !this.showFull) return [];
      const extendedKeys = ["transportation_organized_by", "gsm_payment", "accommodation_payment", "extension_possibility", "minimum_rental_period"];
      return this.filterAndFormatConditions(extendedKeys);
    }
  },
  methods: {
    filterAndFormatConditions(keys) {
      return keys.filter((key) => this.conditions[key] !== void 0).map((key) => ({
        key,
        label: this.getConditionLabel(key),
        value: this.formatConditionValue(key, this.conditions[key]),
        icon: this.getConditionIcon(key)
      }));
    },
    getConditionLabel(key) {
      const labels = {
        "payment_type": "Тип оплаты",
        "hours_per_shift": "Часов в смену",
        "shifts_per_day": "Смен в день",
        "operator_included": "Оператор включен",
        "transportation_organized_by": "Организация транспортировки",
        "gsm_payment": "Оплата ГСМ",
        "accommodation_payment": "Оплата проживания",
        "extension_possibility": "Возможность продления",
        "minimum_rental_period": "Минимальный период аренды"
      };
      return labels[key] || key;
    },
    getConditionIcon(key) {
      const icons = {
        "payment_type": "fa-money-bill-wave",
        "hours_per_shift": "fa-clock",
        "shifts_per_day": "fa-calendar-day",
        "operator_included": "fa-user-hard-hat",
        "transportation_organized_by": "fa-truck-moving",
        "gsm_payment": "fa-gas-pump",
        "accommodation_payment": "fa-hotel",
        "extension_possibility": "fa-calendar-plus",
        "minimum_rental_period": "fa-calendar-alt"
      };
      return icons[key] || "fa-cog";
    },
    formatConditionValue(key, value) {
      switch (key) {
        case "payment_type":
          return value === "hourly" ? "Почасовая" : value === "daily" ? "Посуточная" : value === "monthly" ? "Помесячная" : value;
        case "operator_included":
        case "accommodation_payment":
        case "extension_possibility":
          return value ? "Да" : "Нет";
        case "transportation_organized_by":
          return value === "lessor" ? "Арендодателем" : value === "lessee" ? "Арендатором" : value;
        case "gsm_payment":
          return value === "included" ? "Включена" : value === "separate" ? "Отдельно" : value;
        case "minimum_rental_period":
          return `${value} ${this.getPeriodUnit(value)}`;
        default:
          return value;
      }
    },
    getPeriodUnit(days) {
      if (days === 1) return "день";
      if (days > 1 && days < 5) return "дня";
      return "дней";
    }
  }
};
const _hoisted_1$2 = { class: "public-rental-conditions" };
const _hoisted_2$2 = {
  key: 0,
  class: "conditions-container"
};
const _hoisted_3$2 = { class: "basic-conditions" };
const _hoisted_4$2 = { class: "conditions-grid" };
const _hoisted_5$2 = {
  key: 0,
  class: "extended-conditions mt-4"
};
const _hoisted_6$2 = { class: "conditions-grid" };
const _hoisted_7$2 = {
  key: 1,
  class: "extended-conditions-info mt-3 p-3 bg-light rounded"
};
const _hoisted_8$2 = {
  key: 1,
  class: "no-conditions text-center py-4"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ConditionItem = resolveComponent("ConditionItem");
  return openBlock(), createElementBlock("div", _hoisted_1$2, [
    $options.hasConditions ? (openBlock(), createElementBlock("div", _hoisted_2$2, [
      createBaseVNode("div", _hoisted_3$2, [
        _cache[0] || (_cache[0] = createBaseVNode("h6", { class: "section-title" }, [
          createBaseVNode("i", { class: "fas fa-clipboard-check me-2 text-primary" }),
          createTextVNode(" Основные условия аренды ")
        ], -1)),
        createBaseVNode("div", _hoisted_4$2, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.basicConditions, (condition) => {
            return openBlock(), createBlock(_component_ConditionItem, {
              key: condition.key,
              condition
            }, null, 8, ["condition"]);
          }), 128))
        ])
      ]),
      $props.showFull && $options.hasExtendedConditions ? (openBlock(), createElementBlock("div", _hoisted_5$2, [
        _cache[1] || (_cache[1] = createBaseVNode("h6", { class: "section-title" }, [
          createBaseVNode("i", { class: "fas fa-list-alt me-2 text-success" }),
          createTextVNode(" Дополнительные условия ")
        ], -1)),
        createBaseVNode("div", _hoisted_6$2, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.extendedConditions, (condition) => {
            return openBlock(), createBlock(_component_ConditionItem, {
              key: condition.key,
              condition
            }, null, 8, ["condition"]);
          }), 128))
        ])
      ])) : !$props.showFull && $options.hasExtendedConditions ? (openBlock(), createElementBlock("div", _hoisted_7$2, [..._cache[2] || (_cache[2] = [
        createStaticVNode('<div class="text-center" data-v-e491ad59><i class="fas fa-lock me-2 text-muted" data-v-e491ad59></i><small class="text-muted" data-v-e491ad59> Полный список условий доступен после авторизации как арендодатель </small><div class="mt-2" data-v-e491ad59><a href="/login" class="btn btn-sm btn-outline-primary me-2" data-v-e491ad59>Войти</a><a href="/register?type=lessor" class="btn btn-sm btn-primary" data-v-e491ad59>Зарегистрироваться</a></div></div>', 1)
      ])])) : createCommentVNode("", true)
    ])) : (openBlock(), createElementBlock("div", _hoisted_8$2, [..._cache[3] || (_cache[3] = [
      createBaseVNode("i", { class: "fas fa-info-circle fa-2x text-muted mb-3" }, null, -1),
      createBaseVNode("p", { class: "text-muted mb-0" }, "Стандартные условия аренды применяются по умолчанию", -1)
    ])]))
  ]);
}
const PublicRentalConditionsDisplay = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-e491ad59"]]);
const _sfc_main$1 = {
  name: "PublicCategoryGroup",
  props: {
    category: {
      type: Object,
      required: true
    },
    initiallyExpanded: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isExpanded: this.initiallyExpanded
    };
  },
  methods: {
    toggleExpanded() {
      this.isExpanded = !this.isExpanded;
    },
    // 🔥 ПРОВЕРКА НАЛИЧИЯ СПЕЦИФИКАЦИЙ
    hasSpecifications(item) {
      return item.formatted_specs && Array.isArray(item.formatted_specs) && item.formatted_specs.length > 0;
    },
    // 🔥 ПОЛУЧЕНИЕ СПЕЦИФИКАЦИЙ ДЛЯ ОТОБРАЖЕНИЯ
    getDisplaySpecifications(item) {
      if (!this.hasSpecifications(item)) return [];
      if (typeof item.formatted_specs[0] === "object") {
        return item.formatted_specs;
      }
      return item.formatted_specs.map((spec) => {
        if (typeof spec === "string") {
          return { formatted: spec };
        }
        return spec;
      });
    },
    // 🔥 ПОЛУЧЕНИЕ КЛЮЧА ДЛЯ v-for
    getSpecKey(spec) {
      if (typeof spec === "string") return spec;
      if (spec.key) return spec.key;
      if (spec.formatted) return spec.formatted;
      return JSON.stringify(spec);
    },
    // 🔥 ПОЛУЧЕНИЕ ТЕКСТА ДЛЯ ОТОБРАЖЕНИЯ
    getSpecDisplayText(spec) {
      if (typeof spec === "string") return spec;
      if (spec.formatted) return spec.formatted;
      if (spec.label && spec.value !== void 0) {
        const unit = spec.unit ? ` ${spec.unit}` : "";
        return `${spec.label}: ${spec.value}${unit}`;
      }
      return JSON.stringify(spec);
    }
  }
};
const _hoisted_1$1 = { class: "public-category-group" };
const _hoisted_2$1 = { class: "header-content" };
const _hoisted_3$1 = { class: "category-name" };
const _hoisted_4$1 = { class: "category-stats" };
const _hoisted_5$1 = { class: "stat" };
const _hoisted_6$1 = { class: "stat" };
const _hoisted_7$1 = { class: "expand-icon" };
const _hoisted_8$1 = {
  key: 0,
  class: "category-items"
};
const _hoisted_9$1 = { class: "position-header" };
const _hoisted_10$1 = {
  key: 0,
  class: "specifications mt-2"
};
const _hoisted_11$1 = {
  key: 1,
  class: "text-muted small"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", {
      class: "category-header",
      onClick: _cache[0] || (_cache[0] = (...args) => $options.toggleExpanded && $options.toggleExpanded(...args))
    }, [
      createBaseVNode("div", _hoisted_2$1, [
        createBaseVNode("h5", _hoisted_3$1, [
          createBaseVNode("i", {
            class: normalizeClass(["fas", $data.isExpanded ? "fa-folder-open" : "fa-folder"])
          }, null, 2),
          createTextVNode(" " + toDisplayString($props.category.category_name), 1)
        ]),
        createBaseVNode("div", _hoisted_4$1, [
          createBaseVNode("span", _hoisted_5$1, toDisplayString($props.category.items_count) + " позиций", 1),
          createBaseVNode("span", _hoisted_6$1, "× " + toDisplayString($props.category.total_quantity) + " ед.", 1)
        ])
      ]),
      createBaseVNode("div", _hoisted_7$1, [
        createBaseVNode("i", {
          class: normalizeClass(["fas", $data.isExpanded ? "fa-chevron-up" : "fa-chevron-down"])
        }, null, 2)
      ])
    ]),
    $data.isExpanded ? (openBlock(), createElementBlock("div", _hoisted_8$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($props.category.items, (item) => {
        return openBlock(), createElementBlock("div", {
          key: item.id,
          class: "public-position-item"
        }, [
          createBaseVNode("div", _hoisted_9$1, [
            createBaseVNode("strong", null, "Количество: " + toDisplayString(item.quantity || 1), 1)
          ]),
          $options.hasSpecifications(item) ? (openBlock(), createElementBlock("div", _hoisted_10$1, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.getDisplaySpecifications(item), (spec) => {
              return openBlock(), createElementBlock("div", {
                key: $options.getSpecKey(spec),
                class: "spec-item small text-muted"
              }, toDisplayString($options.getSpecDisplayText(spec)), 1);
            }), 128))
          ])) : (openBlock(), createElementBlock("div", _hoisted_11$1, " Нет спецификаций "))
        ]);
      }), 128))
    ])) : createCommentVNode("", true)
  ]);
}
const PublicCategoryGroup = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-a0615e16"]]);
const _sfc_main = {
  name: "PublicRentalRequestShow",
  components: {
    PublicProposalModal,
    PublicRentalConditionsDisplay,
    PublicCategoryGroup
  },
  data() {
    return {
      loading: true,
      error: null,
      request: null,
      showProposalModal: false,
      currentUser: null,
      authChecked: false,
      groupedByCategory: [],
      summary: {
        total_items: 0,
        total_quantity: 0,
        categories_count: 0
      }
    };
  },
  computed: {
    isAuthenticatedLessor() {
      var _a, _b, _c;
      const isLessor = this.currentUser && this.currentUser.company && this.currentUser.company.is_lessor === 1;
      console.log("🔐 Проверка роли пользователя:", {
        currentUser: this.currentUser,
        company: (_a = this.currentUser) == null ? void 0 : _a.company,
        is_lessor: (_c = (_b = this.currentUser) == null ? void 0 : _b.company) == null ? void 0 : _c.is_lessor,
        result: isLessor
      });
      return isLessor;
    },
    totalEquipmentQuantity() {
      if (!this.request.items) return 0;
      return this.request.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
    },
    canMakeProposal() {
      if (!this.isAuthenticatedLessor) {
        console.log("❌ Не может делать предложение: не арендодатель");
        return false;
      }
      if (!this.request) {
        console.log("❌ Не может делать предложение: нет данных заявки");
        return false;
      }
      const isActive = this.request.status === "active";
      const notExpired = !this.request.expires_at || new Date(this.request.expires_at) > /* @__PURE__ */ new Date();
      console.log("📋 Проверка возможности предложения:", {
        isActive,
        notExpired,
        status: this.request.status,
        expires_at: this.request.expires_at
      });
      return isActive && notExpired;
    }
  },
  methods: {
    loadUser() {
      return __async(this, null, function* () {
        var _a;
        try {
          console.log("🔄 Загрузка данных пользователя...");
          const response = yield fetch("/api/user", {
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          if (response.ok) {
            const userData = yield response.json();
            console.log("📊 Полные данные пользователя из API:", JSON.stringify(userData, null, 2));
            if (userData.company) {
              this.currentUser = userData;
            } else if (userData.data && userData.data.company) {
              this.currentUser = userData.data;
            } else if (userData.original && userData.original.company) {
              this.currentUser = userData.original;
            } else {
              this.currentUser = __spreadProps(__spreadValues({}, userData), {
                company: userData.company || null
              });
              console.warn("⚠️ Компания не найдена в ответе API");
            }
            console.log("✅ Обработанные данные пользователя:", {
              id: this.currentUser.id,
              name: this.currentUser.name,
              hasCompany: !!this.currentUser.company,
              company: this.currentUser.company,
              is_lessor: (_a = this.currentUser.company) == null ? void 0 : _a.is_lessor
            });
          } else {
            console.log("⚠️ Пользователь не авторизован, статус:", response.status);
            this.currentUser = null;
          }
        } catch (error) {
          console.error("❌ Ошибка загрузки пользователя:", error);
          this.currentUser = null;
        } finally {
          this.authChecked = true;
        }
      });
    },
    debugRequestData() {
      var _a, _b, _c, _d, _e, _f, _g, _h;
      console.log("🔍 Отладочная информация о заявке:", {
        id: (_a = this.request) == null ? void 0 : _a.id,
        rental_period_start: (_b = this.request) == null ? void 0 : _b.rental_period_start,
        rental_period_end: (_c = this.request) == null ? void 0 : _c.rental_period_end,
        rental_period: (_d = this.request) == null ? void 0 : _d.rental_period,
        total_budget: (_e = this.request) == null ? void 0 : _e.total_budget,
        hourly_rate: (_f = this.request) == null ? void 0 : _f.hourly_rate,
        max_hourly_rate: (_g = this.request) == null ? void 0 : _g.max_hourly_rate,
        rental_conditions: (_h = this.request) == null ? void 0 : _h.rental_conditions,
        raw_request: this.request
      });
    },
    loadRequest() {
      return __async(this, null, function* () {
        this.loading = true;
        this.error = null;
        try {
          const requestId = this.getRequestIdFromUrl();
          const apiUrl = `/api/public/rental-requests/${requestId}`;
          console.log("🔄 Загрузка публичной заявки...", {
            requestId,
            apiUrl,
            fullUrl: window.location.origin + apiUrl
          });
          const response = yield fetch(apiUrl, {
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest",
              "Content-Type": "application/json"
            },
            credentials: "include"
          });
          console.log("📡 Ответ сервера:", {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok
          });
          if (!response.ok) {
            if (response.status === 404) {
              throw new Error("Заявка не найдена или недоступна");
            } else if (response.status === 403) {
              throw new Error("Доступ запрещен");
            } else {
              throw new Error(`HTTP ошибка! Статус: ${response.status}`);
            }
          }
          const data = yield response.json();
          console.log("📦 Данные от API:", data);
          if (data.success) {
            this.request = data.data;
            this.processRequestData();
            this.loading = false;
            this.$nextTick(() => {
              console.log("🔄 Принудительное обновление UI после загрузки данных");
              this.loading = false;
            });
          } else {
            throw new Error(data.message || "Ошибка загрузки заявки");
          }
        } catch (error) {
          console.error("❌ Ошибка загрузки заявки:", error);
          this.error = `Не удалось загрузить заявку: ${error.message}`;
          this.loading = false;
        }
      });
    },
    getRequestIdFromUrl() {
      const path = window.location.pathname;
      console.log("🔍 Анализ пути:", path);
      const matches = path.match(/\/portal\/rental-requests\/(\d+)/);
      const requestId = matches ? matches[1] : null;
      console.log("📋 Извлеченный ID заявки:", requestId);
      if (!requestId) {
        this.error = "Неверный URL заявки";
        this.loading = false;
        return null;
      }
      return requestId;
    },
    processRequestData() {
      if (!this.request) {
        console.error("❌ Нет данных заявки для обработки");
        this.loading = false;
        return;
      }
      console.log("🔍 Данные заявки для обработки:", this.request);
      if (!this.request.rental_period_display) {
        this.request.rental_period_display = this.getRentalPeriodDisplay(
          this.request.rental_period_start,
          this.request.rental_period_end
        );
      }
      if (!this.request.rental_days) {
        this.request.rental_days = this.calculateRentalDays(
          this.request.rental_period_start,
          this.request.rental_period_end
        );
      }
      const items = this.request.items || [];
      const uniqueCategories = new Set(items.map((item) => {
        var _a;
        return ((_a = item.category) == null ? void 0 : _a.name) || "Без категории";
      }));
      this.summary = {
        total_items: items.length,
        total_quantity: items.reduce((sum, item) => sum + (item.quantity || 0), 0),
        categories_count: uniqueCategories.size
      };
      this.groupedByCategory = this.groupItemsByCategory(items);
      this.$nextTick(() => {
        console.log("🔄 UI обновлен после обработки данных");
        this.loading = false;
      });
      this.debugRequestData();
    },
    getRentalPeriodDisplay(startDate, endDate) {
      console.log("📅 Получены даты:", { startDate, endDate });
      if (!startDate || !endDate) {
        return "Период не указан";
      }
      try {
        const start = this.formatDate(startDate);
        const end = this.formatDate(endDate);
        return `${start} - ${end}`;
      } catch (error) {
        console.error("Ошибка форматирования периода аренды:", error, { startDate, endDate });
        return "Ошибка даты";
      }
    },
    groupItemsByCategory(items) {
      console.log("🔄 Начинаем группировку items по категориям:", items);
      if (!items || !Array.isArray(items) || items.length === 0) {
        console.warn("❌ Нет items для группировки");
        return [];
      }
      const grouped = {};
      items.forEach((item, index) => {
        var _a, _b;
        console.log(`📋 Обрабатываем item ${index + 1}:`, item);
        const categoryName = ((_a = item.category) == null ? void 0 : _a.name) || "Без категории";
        const categoryKey = categoryName;
        if (!grouped[categoryKey]) {
          grouped[categoryKey] = {
            category_id: ((_b = item.category) == null ? void 0 : _b.id) || categoryKey,
            category_name: categoryName,
            items: [],
            total_quantity: 0,
            items_count: 0
          };
          console.log(`✅ Создана новая группа категории: ${categoryName}`);
        }
        grouped[categoryKey].items.push(item);
        grouped[categoryKey].total_quantity += item.quantity || 0;
        grouped[categoryKey].items_count += 1;
        console.log(`📥 Добавлен item в категорию "${categoryName}":`, item);
      });
      const result = Object.values(grouped);
      console.log("🎯 Результат группировки:", result);
      return result;
    },
    getStatusBadgeClass(status) {
      const classes = {
        "active": "bg-success",
        "paused": "bg-warning",
        "processing": "bg-warning",
        "completed": "bg-primary",
        "cancelled": "bg-danger"
      };
      return classes[status] || "bg-light";
    },
    getStatusDisplayText(status) {
      const texts = {
        "active": "Активна",
        "paused": "Приостановлена",
        "processing": "В обработке",
        "completed": "Завершена",
        "cancelled": "Отменена"
      };
      return texts[status] || status;
    },
    openProposalModal() {
      var _a;
      console.log("🔄 Открытие модального окна предложения");
      if (!this.canMakeProposal) {
        console.log("❌ Нельзя сделать предложение:", {
          isAuthenticatedLessor: this.isAuthenticatedLessor,
          requestStatus: (_a = this.request) == null ? void 0 : _a.status,
          canMakeProposal: this.canMakeProposal
        });
        this.redirectToLogin();
        return;
      }
      this.showProposalModal = true;
      console.log("✅ Модальное окно открыто");
    },
    onProposalCreated(proposalData) {
      console.log("✅ Предложение создано:", proposalData);
      this.showProposalModal = false;
      this.showToast("success", "Предложение успешно отправлено!");
      this.loadRequest();
    },
    addToFavorites() {
      this.showToast("info", "Добавлено в избранное");
    },
    redirectToLogin() {
      window.location.href = "/login?redirect=" + encodeURIComponent(window.location.pathname);
    },
    formatDate(dateString) {
      if (!dateString) return "—";
      try {
        return new Date(dateString).toLocaleDateString("ru-RU");
      } catch (error) {
        console.error("Ошибка форматирования даты:", error, dateString);
        return "—";
      }
    },
    formatCurrency(amount) {
      if (!amount && amount !== 0) return "0 ₽";
      try {
        return new Intl.NumberFormat("ru-RU", {
          style: "currency",
          currency: "RUB",
          minimumFractionDigits: 0
        }).format(amount);
      } catch (error) {
        console.error("Ошибка форматирования валюты:", error, amount);
        return "0 ₽";
      }
    },
    calculateRentalDays(startDate, endDate) {
      if (!startDate || !endDate) return 0;
      try {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const timeDiff = end.getTime() - start.getTime();
        const dayDiff = Math.ceil(timeDiff / (1e3 * 3600 * 24)) + 1;
        return dayDiff > 0 ? dayDiff : 0;
      } catch (error) {
        console.error("Ошибка расчета дней аренды:", error);
        return 0;
      }
    },
    showToast(type, message) {
      const toast = document.createElement("div");
      toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      toast.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
      toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
      document.body.appendChild(toast);
      setTimeout(() => {
        toast.remove();
      }, 5e3);
    }
  },
  mounted() {
    return __async(this, null, function* () {
      console.log("🚀 Компонент PublicRentalRequestShow mounted");
      yield this.loadUser();
      yield this.loadRequest();
      console.log("✅ Инициализация компонента завершена");
    });
  }
};
const _hoisted_1 = { class: "public-rental-request-show" };
const _hoisted_2 = { key: 0 };
const _hoisted_3 = { class: "container-fluid px-4" };
const _hoisted_4 = { class: "row" };
const _hoisted_5 = { class: "col-12" };
const _hoisted_6 = { class: "page-header d-flex justify-content-between align-items-center mb-4" };
const _hoisted_7 = { class: "page-title" };
const _hoisted_8 = { class: "row mb-4" };
const _hoisted_9 = { class: "col-12" };
const _hoisted_10 = { class: "public-stats-card card" };
const _hoisted_11 = { class: "card-body" };
const _hoisted_12 = { class: "stats-grid" };
const _hoisted_13 = { class: "stat-item" };
const _hoisted_14 = { class: "stat-value" };
const _hoisted_15 = { class: "stat-item" };
const _hoisted_16 = { class: "stat-value" };
const _hoisted_17 = { class: "stat-item" };
const _hoisted_18 = { class: "stat-value" };
const _hoisted_19 = { class: "stat-item" };
const _hoisted_20 = { class: "stat-value" };
const _hoisted_21 = { class: "row" };
const _hoisted_22 = { class: "col-lg-8" };
const _hoisted_23 = { class: "card mb-4" };
const _hoisted_24 = { class: "card-body" };
const _hoisted_25 = { class: "row" };
const _hoisted_26 = { class: "col-md-6" };
const _hoisted_27 = { class: "info-item mb-3" };
const _hoisted_28 = { class: "mb-0" };
const _hoisted_29 = { class: "info-item mb-3" };
const _hoisted_30 = { class: "mb-0" };
const _hoisted_31 = { class: "text-muted" };
const _hoisted_32 = { class: "col-md-6" };
const _hoisted_33 = { class: "info-item mb-3" };
const _hoisted_34 = { class: "mb-0" };
const _hoisted_35 = {
  key: 0,
  class: "text-muted"
};
const _hoisted_36 = {
  key: 0,
  class: "info-item mb-3"
};
const _hoisted_37 = { class: "mb-0 fs-5 text-success fw-bold" };
const _hoisted_38 = { class: "pricing-details mt-2" };
const _hoisted_39 = { class: "rental-info small text-muted mt-2" };
const _hoisted_40 = {
  key: 1,
  class: "info-item mb-3"
};
const _hoisted_41 = {
  key: 2,
  class: "info-item mb-3"
};
const _hoisted_42 = { class: "card mb-4" };
const _hoisted_43 = { class: "card-body" };
const _hoisted_44 = { class: "card mb-4" };
const _hoisted_45 = { class: "card-header" };
const _hoisted_46 = { class: "card-title mb-0" };
const _hoisted_47 = { class: "badge bg-primary ms-2" };
const _hoisted_48 = { class: "card-body p-0" };
const _hoisted_49 = { class: "categories-list" };
const _hoisted_50 = { class: "col-lg-4" };
const _hoisted_51 = { class: "card mb-4" };
const _hoisted_52 = { class: "card-body" };
const _hoisted_53 = { class: "d-flex align-items-center" };
const _hoisted_54 = { class: "text-muted" };
const _hoisted_55 = { class: "mt-2" };
const _hoisted_56 = { class: "text-muted" };
const _hoisted_57 = {
  key: 0,
  class: "card mb-4"
};
const _hoisted_58 = { class: "card-body" };
const _hoisted_59 = ["disabled"];
const _hoisted_60 = {
  key: 1,
  class: "card mb-4"
};
const _hoisted_61 = {
  key: 2,
  class: "card"
};
const _hoisted_62 = { class: "card-body" };
const _hoisted_63 = { class: "contact-info" };
const _hoisted_64 = { class: "mb-2" };
const _hoisted_65 = { class: "small text-muted mb-1" };
const _hoisted_66 = { class: "small text-muted mb-0" };
const _hoisted_67 = {
  key: 1,
  class: "text-center py-5"
};
const _hoisted_68 = {
  key: 2,
  class: "alert alert-danger text-center"
};
const _hoisted_69 = {
  key: 3,
  class: "alert alert-warning text-center"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _a, _b, _c, _d;
  const _component_PublicRentalConditionsDisplay = resolveComponent("PublicRentalConditionsDisplay");
  const _component_PublicCategoryGroup = resolveComponent("PublicCategoryGroup");
  const _component_PublicProposalModal = resolveComponent("PublicProposalModal");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    $data.request && !$data.loading && !$data.error ? (openBlock(), createElementBlock("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createBaseVNode("div", _hoisted_4, [
          createBaseVNode("div", _hoisted_5, [
            createBaseVNode("div", _hoisted_6, [
              createBaseVNode("h1", _hoisted_7, "Публичная заявка: " + toDisplayString($data.request.title), 1),
              _cache[4] || (_cache[4] = createBaseVNode("div", null, [
                createBaseVNode("a", {
                  href: "/requests",
                  class: "btn btn-outline-secondary me-2"
                }, [
                  createBaseVNode("i", { class: "fas fa-arrow-left me-2" }),
                  createTextVNode("Назад к списку ")
                ])
              ], -1))
            ])
          ])
        ]),
        createBaseVNode("div", _hoisted_8, [
          createBaseVNode("div", _hoisted_9, [
            createBaseVNode("div", _hoisted_10, [
              createBaseVNode("div", _hoisted_11, [
                createBaseVNode("div", _hoisted_12, [
                  createBaseVNode("div", _hoisted_13, [
                    createBaseVNode("div", _hoisted_14, toDisplayString($data.summary.total_items), 1),
                    _cache[5] || (_cache[5] = createBaseVNode("div", { class: "stat-label" }, "Позиций", -1))
                  ]),
                  createBaseVNode("div", _hoisted_15, [
                    createBaseVNode("div", _hoisted_16, toDisplayString($data.summary.total_quantity), 1),
                    _cache[6] || (_cache[6] = createBaseVNode("div", { class: "stat-label" }, "Единиц техники", -1))
                  ]),
                  createBaseVNode("div", _hoisted_17, [
                    createBaseVNode("div", _hoisted_18, toDisplayString($data.summary.categories_count), 1),
                    _cache[7] || (_cache[7] = createBaseVNode("div", { class: "stat-label" }, "Категорий", -1))
                  ]),
                  createBaseVNode("div", _hoisted_19, [
                    createBaseVNode("div", _hoisted_20, toDisplayString($data.request.active_proposals_count || 0), 1),
                    _cache[8] || (_cache[8] = createBaseVNode("div", { class: "stat-label" }, "Предложений", -1))
                  ])
                ])
              ])
            ])
          ])
        ]),
        createBaseVNode("div", _hoisted_21, [
          createBaseVNode("div", _hoisted_22, [
            createBaseVNode("div", _hoisted_23, [
              _cache[20] || (_cache[20] = createBaseVNode("div", { class: "card-header" }, [
                createBaseVNode("h5", { class: "card-title mb-0" }, [
                  createBaseVNode("i", { class: "fas fa-info-circle me-2" }),
                  createTextVNode("Основная информация ")
                ])
              ], -1)),
              createBaseVNode("div", _hoisted_24, [
                createBaseVNode("div", _hoisted_25, [
                  createBaseVNode("div", _hoisted_26, [
                    createBaseVNode("div", _hoisted_27, [
                      _cache[9] || (_cache[9] = createBaseVNode("label", { class: "text-muted small" }, "Описание проекта", -1)),
                      createBaseVNode("p", _hoisted_28, toDisplayString($data.request.description), 1)
                    ]),
                    createBaseVNode("div", _hoisted_29, [
                      _cache[12] || (_cache[12] = createBaseVNode("label", { class: "text-muted small" }, "Локация объекта", -1)),
                      createBaseVNode("p", _hoisted_30, [
                        _cache[10] || (_cache[10] = createBaseVNode("i", { class: "fas fa-map-marker-alt text-danger me-2" }, null, -1)),
                        createTextVNode(" " + toDisplayString(((_a = $data.request.location) == null ? void 0 : _a.name) || "Не указана") + " ", 1),
                        _cache[11] || (_cache[11] = createBaseVNode("br", null, null, -1)),
                        createBaseVNode("small", _hoisted_31, toDisplayString(((_b = $data.request.location) == null ? void 0 : _b.address) || ""), 1)
                      ])
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_32, [
                    createBaseVNode("div", _hoisted_33, [
                      _cache[15] || (_cache[15] = createBaseVNode("label", { class: "text-muted small" }, "Период аренды", -1)),
                      createBaseVNode("p", _hoisted_34, [
                        _cache[13] || (_cache[13] = createBaseVNode("i", { class: "fas fa-calendar-alt text-primary me-2" }, null, -1)),
                        createTextVNode(" " + toDisplayString($data.request.rental_period_display || "Период не указан") + " ", 1),
                        _cache[14] || (_cache[14] = createBaseVNode("br", null, null, -1)),
                        $data.request.rental_days ? (openBlock(), createElementBlock("small", _hoisted_35, toDisplayString($data.request.rental_days) + " дней ", 1)) : createCommentVNode("", true)
                      ])
                    ]),
                    $options.isAuthenticatedLessor && $data.request.lessor_pricing ? (openBlock(), createElementBlock("div", _hoisted_36, [
                      _cache[17] || (_cache[17] = createBaseVNode("label", { class: "text-muted small" }, "Бюджет для вас", -1)),
                      createBaseVNode("p", _hoisted_37, toDisplayString($options.formatCurrency($data.request.lessor_pricing.total_lessor_budget || 0)), 1),
                      createBaseVNode("div", _hoisted_38, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList($data.request.lessor_pricing.items, (item) => {
                          return openBlock(), createElementBlock("div", {
                            key: item.item_id,
                            class: "price-item small text-muted mb-1"
                          }, [
                            createBaseVNode("strong", null, toDisplayString(item.category_name), 1),
                            createTextVNode(": " + toDisplayString(item.quantity) + " шт. × " + toDisplayString($options.formatCurrency(item.lessor_price)) + "/час ", 1)
                          ]);
                        }), 128))
                      ]),
                      createBaseVNode("div", _hoisted_39, [
                        _cache[16] || (_cache[16] = createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1)),
                        createTextVNode(" " + toDisplayString($data.request.lessor_pricing.working_hours) + " часов (" + toDisplayString($data.request.lessor_pricing.rental_days) + " дней) ", 1)
                      ])
                    ])) : $options.isAuthenticatedLessor ? (openBlock(), createElementBlock("div", _hoisted_40, [..._cache[18] || (_cache[18] = [
                      createBaseVNode("label", { class: "text-muted small" }, "Бюджет", -1),
                      createBaseVNode("p", { class: "mb-0 text-muted" }, [
                        createBaseVNode("i", { class: "fas fa-info-circle me-2" }),
                        createTextVNode(" Бюджет загружается... ")
                      ], -1)
                    ])])) : (openBlock(), createElementBlock("div", _hoisted_41, [..._cache[19] || (_cache[19] = [
                      createBaseVNode("label", { class: "text-muted small" }, "Бюджет", -1),
                      createBaseVNode("p", { class: "mb-0 text-muted" }, [
                        createBaseVNode("i", { class: "fas fa-lock me-2" }),
                        createTextVNode(" Войдите как арендодатель для просмотра бюджета ")
                      ], -1)
                    ])]))
                  ])
                ])
              ])
            ]),
            createBaseVNode("div", _hoisted_42, [
              _cache[21] || (_cache[21] = createBaseVNode("div", { class: "card-header" }, [
                createBaseVNode("h5", { class: "card-title mb-0" }, [
                  createBaseVNode("i", { class: "fas fa-clipboard-list me-2" }),
                  createTextVNode("Условия аренды ")
                ])
              ], -1)),
              createBaseVNode("div", _hoisted_43, [
                createVNode(_component_PublicRentalConditionsDisplay, {
                  conditions: $data.request.rental_conditions,
                  "show-full": $options.isAuthenticatedLessor
                }, null, 8, ["conditions", "show-full"])
              ])
            ]),
            createBaseVNode("div", _hoisted_44, [
              createBaseVNode("div", _hoisted_45, [
                createBaseVNode("h5", _hoisted_46, [
                  _cache[22] || (_cache[22] = createBaseVNode("i", { class: "fas fa-cogs me-2" }, null, -1)),
                  _cache[23] || (_cache[23] = createTextVNode("Технические требования ", -1)),
                  createBaseVNode("span", _hoisted_47, toDisplayString(((_c = $data.request.grouped_items) == null ? void 0 : _c.length) || 0) + " категорий", 1)
                ])
              ]),
              createBaseVNode("div", _hoisted_48, [
                createBaseVNode("div", _hoisted_49, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($data.request.grouped_items, (category) => {
                    return openBlock(), createBlock(_component_PublicCategoryGroup, {
                      key: category.category_name,
                      category,
                      "initially-expanded": true
                    }, null, 8, ["category"]);
                  }), 128))
                ])
              ])
            ])
          ]),
          createBaseVNode("div", _hoisted_50, [
            createBaseVNode("div", _hoisted_51, [
              _cache[25] || (_cache[25] = createBaseVNode("div", { class: "card-header" }, [
                createBaseVNode("h6", { class: "card-title mb-0" }, "Статус заявки")
              ], -1)),
              createBaseVNode("div", _hoisted_52, [
                createBaseVNode("div", _hoisted_53, [
                  createBaseVNode("span", {
                    class: normalizeClass(["badge me-2", $options.getStatusBadgeClass($data.request.status)])
                  }, toDisplayString($options.getStatusDisplayText($data.request.status)), 3),
                  createBaseVNode("small", _hoisted_54, " Опубликована " + toDisplayString($options.formatDate($data.request.created_at)), 1)
                ]),
                createBaseVNode("div", _hoisted_55, [
                  createBaseVNode("small", _hoisted_56, [
                    _cache[24] || (_cache[24] = createBaseVNode("i", { class: "fas fa-eye me-1" }, null, -1)),
                    createTextVNode(" " + toDisplayString($data.request.views_count || 0) + " просмотров ", 1)
                  ])
                ])
              ])
            ]),
            $options.isAuthenticatedLessor ? (openBlock(), createElementBlock("div", _hoisted_57, [
              _cache[28] || (_cache[28] = createBaseVNode("div", { class: "card-header" }, [
                createBaseVNode("h6", { class: "card-title mb-0" }, "Ваши действия")
              ], -1)),
              createBaseVNode("div", _hoisted_58, [
                createBaseVNode("button", {
                  class: "btn btn-primary w-100 mb-2",
                  onClick: _cache[0] || (_cache[0] = (...args) => $options.openProposalModal && $options.openProposalModal(...args)),
                  disabled: !$options.canMakeProposal
                }, [..._cache[26] || (_cache[26] = [
                  createBaseVNode("i", { class: "fas fa-paper-plane me-2" }, null, -1),
                  createTextVNode(" Предложить технику ", -1)
                ])], 8, _hoisted_59),
                createBaseVNode("button", {
                  class: "btn btn-outline-secondary w-100",
                  onClick: _cache[1] || (_cache[1] = (...args) => $options.addToFavorites && $options.addToFavorites(...args))
                }, [..._cache[27] || (_cache[27] = [
                  createBaseVNode("i", { class: "fas fa-star me-2" }, null, -1),
                  createTextVNode(" В избранное ", -1)
                ])])
              ])
            ])) : (openBlock(), createElementBlock("div", _hoisted_60, [..._cache[29] || (_cache[29] = [
              createStaticVNode('<div class="card-header" data-v-d4549e52><h6 class="card-title mb-0" data-v-d4549e52>Хотите предложить технику?</h6></div><div class="card-body text-center" data-v-d4549e52><p class="small text-muted mb-3" data-v-d4549e52> Зарегистрируйтесь как арендодатель для доступа к полной информации и возможности делать предложения </p><a href="/register?type=lessor" class="btn btn-primary w-100 mb-2" data-v-d4549e52> Зарегистрироваться </a><a href="/login" class="btn btn-outline-primary w-100" data-v-d4549e52> Войти </a></div>', 2)
            ])])),
            $options.isAuthenticatedLessor && $data.request.company ? (openBlock(), createElementBlock("div", _hoisted_61, [
              _cache[32] || (_cache[32] = createBaseVNode("div", { class: "card-header" }, [
                createBaseVNode("h6", { class: "card-title mb-0" }, "Контактная информация")
              ], -1)),
              createBaseVNode("div", _hoisted_62, [
                createBaseVNode("div", _hoisted_63, [
                  createBaseVNode("p", _hoisted_64, [
                    createBaseVNode("strong", null, toDisplayString($data.request.company.legal_name), 1)
                  ]),
                  createBaseVNode("p", _hoisted_65, [
                    _cache[30] || (_cache[30] = createBaseVNode("i", { class: "fas fa-user me-2" }, null, -1)),
                    createTextVNode(" " + toDisplayString(((_d = $data.request.user) == null ? void 0 : _d.name) || "Контактное лицо"), 1)
                  ]),
                  createBaseVNode("p", _hoisted_66, [
                    _cache[31] || (_cache[31] = createBaseVNode("i", { class: "fas fa-map-marker-alt me-2" }, null, -1)),
                    createTextVNode(" " + toDisplayString($data.request.company.legal_address), 1)
                  ])
                ])
              ])
            ])) : createCommentVNode("", true)
          ])
        ]),
        createVNode(_component_PublicProposalModal, {
          show: $data.showProposalModal,
          request: $data.request,
          onClose: _cache[2] || (_cache[2] = ($event) => $data.showProposalModal = false),
          onProposalCreated: $options.onProposalCreated
        }, null, 8, ["show", "request", "onProposalCreated"])
      ])
    ])) : $data.loading ? (openBlock(), createElementBlock("div", _hoisted_67, [..._cache[33] || (_cache[33] = [
      createBaseVNode("div", {
        class: "spinner-border text-primary",
        role: "status"
      }, [
        createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
      ], -1),
      createBaseVNode("p", { class: "mt-2" }, "Загрузка заявки...", -1)
    ])])) : $data.error ? (openBlock(), createElementBlock("div", _hoisted_68, [
      _cache[34] || (_cache[34] = createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1)),
      createTextVNode(" " + toDisplayString($data.error) + " ", 1),
      _cache[35] || (_cache[35] = createBaseVNode("br", null, null, -1)),
      createBaseVNode("button", {
        class: "btn btn-outline-danger btn-sm mt-2",
        onClick: _cache[3] || (_cache[3] = (...args) => $options.loadRequest && $options.loadRequest(...args))
      }, " Попробовать снова ")
    ])) : (openBlock(), createElementBlock("div", _hoisted_69, [..._cache[36] || (_cache[36] = [
      createBaseVNode("i", { class: "fas fa-exclamation-circle me-2" }, null, -1),
      createTextVNode(" Заявка не найдена или данные не загружены ", -1)
    ])]))
  ]);
}
const PublicRentalRequestShow = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-d4549e52"]]);
function initPublicRentalRequestShowApp() {
  const appManager = window.vueAppManager;
  const appElement = document.getElementById("public-rental-request-show-app");
  if (appElement && appManager.canInitialize("public-rental-request-show-app")) {
    console.log("🚀 Initializing PublicRentalRequestShow app...");
    const app = createApp(PublicRentalRequestShow);
    app.component("PublicRentalConditionsDisplay", PublicRentalConditionsDisplay);
    app.component("PublicCategoryGroup", PublicCategoryGroup);
    app.component("PublicProposalModal", PublicProposalModal);
    app.component("ConditionItem", ConditionItem);
    app.config.errorHandler = (err, instance, info) => {
      console.error("🚨 Vue Error:", err, "Info:", info);
    };
    try {
      app.mount("#public-rental-request-show-app");
      appManager.registerApp("public-rental-request-show-app", app);
      console.log("✅ PublicRentalRequestShow app mounted successfully");
    } catch (error) {
      console.error("❌ Ошибка монтирования:", error);
    }
  } else {
    console.log("⚠️ PublicRentalRequestShow app initialization skipped:", {
      elementExists: !!appElement,
      canInitialize: appManager.canInitialize("public-rental-request-show-app"),
      hasApp: appManager.hasApp("public-rental-request-show-app")
    });
  }
}
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(initPublicRentalRequestShowApp, 50);
  });
} else {
  setTimeout(initPublicRentalRequestShowApp, 100);
}
