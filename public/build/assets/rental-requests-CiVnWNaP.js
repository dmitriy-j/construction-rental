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
import { a as createElementBlock, o as openBlock, e as createCommentVNode, b as createBaseVNode, t as toDisplayString, u as withModifiers, w as withDirectives, v as vModelSelect, F as Fragment, r as renderList, j as vModelText, d as createTextVNode, g as resolveComponent, x as createBlock, n as normalizeClass, c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
/* empty css                                                                        */
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main$1 = {
  name: "ProposalModal",
  props: {
    show: {
      type: Boolean,
      default: false
    },
    request: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      submitting: false,
      myEquipment: [],
      form: {
        equipment_id: "",
        proposed_price: 0,
        proposed_quantity: 1,
        message: ""
      }
    };
  },
  computed: {
    calculatedPrice() {
      if (!this.form.proposed_price || !this.request) return null;
      const clientSaving = Math.max(0, (this.request.max_hourly_rate || this.request.hourly_rate) - this.form.proposed_price);
      const fixedMarkup = 100;
      const percentageMarkup = clientSaving * 0.3;
      const totalMarkup = fixedMarkup + percentageMarkup;
      const finalPrice = this.form.proposed_price + totalMarkup;
      return {
        client_saving: clientSaving,
        platform_markup: {
          fixed: fixedMarkup,
          percentage: percentageMarkup,
          total: totalMarkup
        },
        final_price: finalPrice
      };
    }
  },
  methods: {
    loadMyEquipment() {
      return __async(this, null, function* () {
        try {
          const response = yield fetch("/api/lessor/equipment/my");
          const data = yield response.json();
          if (data.success) {
            this.myEquipment = data.data;
          }
        } catch (error) {
          console.error("Ошибка загрузки оборудования:", error);
        }
      });
    },
    submitProposal() {
      return __async(this, null, function* () {
        this.submitting = true;
        try {
          const response = yield fetch(`/api/rental-requests/${this.request.id}/proposals`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
              "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify(this.form)
          });
          const data = yield response.json();
          if (data.success) {
            this.$emit("proposal-created", data.data);
            this.$emit("close");
            alert("Предложение успешно отправлено!");
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          console.error("Ошибка отправки предложения:", error);
          alert("Ошибка: " + error.message);
        } finally {
          this.submitting = false;
        }
      });
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat("ru-RU", {
        style: "currency",
        currency: "RUB",
        minimumFractionDigits: 0
      }).format(amount);
    },
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString("ru-RU");
    }
  },
  watch: {
    show: {
      immediate: true,
      handler(newVal) {
        var _a;
        if (newVal) {
          this.loadMyEquipment();
          this.form = {
            equipment_id: "",
            proposed_price: ((_a = this.request) == null ? void 0 : _a.hourly_rate) || 0,
            proposed_quantity: 1,
            message: ""
          };
        }
      }
    }
  }
};
const _hoisted_1$1 = {
  key: 0,
  class: "modal fade show d-block",
  tabindex: "-1",
  role: "dialog"
};
const _hoisted_2$1 = {
  class: "modal-dialog modal-lg",
  role: "document"
};
const _hoisted_3$1 = { class: "modal-content" };
const _hoisted_4$1 = { class: "modal-header" };
const _hoisted_5$1 = { class: "modal-body" };
const _hoisted_6$1 = {
  key: 0,
  class: "request-info mb-3 p-3 bg-light rounded"
};
const _hoisted_7$1 = { class: "mb-1" };
const _hoisted_8$1 = { class: "mb-3" };
const _hoisted_9$1 = ["value"];
const _hoisted_10$1 = { class: "mb-3" };
const _hoisted_11$1 = { class: "mb-3" };
const _hoisted_12$1 = ["max"];
const _hoisted_13$1 = { class: "mb-3" };
const _hoisted_14$1 = {
  key: 0,
  class: "price-calculation p-3 bg-light rounded mb-3"
};
const _hoisted_15$1 = { class: "d-flex justify-content-between" };
const _hoisted_16$1 = { class: "d-flex justify-content-between" };
const _hoisted_17$1 = { class: "d-flex justify-content-between fw-bold" };
const _hoisted_18$1 = { class: "text-success" };
const _hoisted_19$1 = { class: "modal-footer" };
const _hoisted_20$1 = ["disabled"];
const _hoisted_21$1 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-2"
};
const _hoisted_22$1 = {
  key: 1,
  class: "modal-backdrop fade show"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(Fragment, null, [
    $props.show ? (openBlock(), createElementBlock("div", _hoisted_1$1, [
      createBaseVNode("div", _hoisted_2$1, [
        createBaseVNode("div", _hoisted_3$1, [
          createBaseVNode("div", _hoisted_4$1, [
            _cache[8] || (_cache[8] = createBaseVNode("h5", { class: "modal-title" }, "Отправить предложение", -1)),
            createBaseVNode("button", {
              type: "button",
              class: "btn-close",
              onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
            })
          ]),
          createBaseVNode("div", _hoisted_5$1, [
            $props.request ? (openBlock(), createElementBlock("div", _hoisted_6$1, [
              createBaseVNode("h6", null, "Заявка: " + toDisplayString($props.request.title), 1),
              createBaseVNode("p", _hoisted_7$1, "Период: " + toDisplayString($options.formatDate($props.request.rental_period.start)) + " - " + toDisplayString($options.formatDate($props.request.rental_period.end)), 1)
            ])) : createCommentVNode("", true),
            createBaseVNode("form", {
              onSubmit: _cache[5] || (_cache[5] = withModifiers((...args) => $options.submitProposal && $options.submitProposal(...args), ["prevent"]))
            }, [
              createBaseVNode("div", _hoisted_8$1, [
                _cache[10] || (_cache[10] = createBaseVNode("label", { class: "form-label" }, "Выберите оборудование *", -1)),
                withDirectives(createBaseVNode("select", {
                  "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.form.equipment_id = $event),
                  class: "form-select",
                  required: ""
                }, [
                  _cache[9] || (_cache[9] = createBaseVNode("option", { value: "" }, "Выберите оборудование", -1)),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($data.myEquipment, (equipment) => {
                    return openBlock(), createElementBlock("option", {
                      key: equipment.id,
                      value: equipment.id
                    }, toDisplayString(equipment.title) + " (" + toDisplayString(equipment.brand) + " " + toDisplayString(equipment.model) + ") ", 9, _hoisted_9$1);
                  }), 128))
                ], 512), [
                  [vModelSelect, $data.form.equipment_id]
                ])
              ]),
              createBaseVNode("div", _hoisted_10$1, [
                _cache[11] || (_cache[11] = createBaseVNode("label", { class: "form-label" }, "Предлагаемая цена (₽/час) *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "number",
                  "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.form.proposed_price = $event),
                  class: "form-control",
                  min: "0",
                  step: "50",
                  required: ""
                }, null, 512), [
                  [
                    vModelText,
                    $data.form.proposed_price,
                    void 0,
                    { number: true }
                  ]
                ])
              ]),
              createBaseVNode("div", _hoisted_11$1, [
                _cache[12] || (_cache[12] = createBaseVNode("label", { class: "form-label" }, "Количество *", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "number",
                  "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.form.proposed_quantity = $event),
                  class: "form-control",
                  min: "1",
                  max: $props.request.total_quantity,
                  required: ""
                }, null, 8, _hoisted_12$1), [
                  [
                    vModelText,
                    $data.form.proposed_quantity,
                    void 0,
                    { number: true }
                  ]
                ])
              ]),
              createBaseVNode("div", _hoisted_13$1, [
                _cache[13] || (_cache[13] = createBaseVNode("label", { class: "form-label" }, "Сообщение арендатору *", -1)),
                withDirectives(createBaseVNode("textarea", {
                  "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.form.message = $event),
                  class: "form-control",
                  rows: "4",
                  placeholder: "Опишите ваше предложение...",
                  required: ""
                }, null, 512), [
                  [vModelText, $data.form.message]
                ])
              ]),
              $options.calculatedPrice ? (openBlock(), createElementBlock("div", _hoisted_14$1, [
                _cache[17] || (_cache[17] = createBaseVNode("h6", null, "Расчет стоимости:", -1)),
                createBaseVNode("div", _hoisted_15$1, [
                  _cache[14] || (_cache[14] = createBaseVNode("span", null, "Ваша цена:", -1)),
                  createBaseVNode("span", null, toDisplayString($options.formatCurrency($data.form.proposed_price)) + "/час", 1)
                ]),
                createBaseVNode("div", _hoisted_16$1, [
                  _cache[15] || (_cache[15] = createBaseVNode("span", null, "Наценка платформы:", -1)),
                  createBaseVNode("span", null, "+ " + toDisplayString($options.formatCurrency($options.calculatedPrice.platform_markup.total)), 1)
                ]),
                createBaseVNode("div", _hoisted_17$1, [
                  _cache[16] || (_cache[16] = createBaseVNode("span", null, "Итог для арендатора:", -1)),
                  createBaseVNode("span", _hoisted_18$1, toDisplayString($options.formatCurrency($options.calculatedPrice.final_price)) + "/час", 1)
                ])
              ])) : createCommentVNode("", true)
            ], 32)
          ]),
          createBaseVNode("div", _hoisted_19$1, [
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-outline-secondary",
              onClick: _cache[6] || (_cache[6] = ($event) => _ctx.$emit("close"))
            }, "Отмена"),
            createBaseVNode("button", {
              type: "submit",
              class: "btn btn-primary",
              disabled: $data.submitting,
              onClick: _cache[7] || (_cache[7] = (...args) => $options.submitProposal && $options.submitProposal(...args))
            }, [
              $data.submitting ? (openBlock(), createElementBlock("span", _hoisted_21$1)) : createCommentVNode("", true),
              _cache[18] || (_cache[18] = createTextVNode(" Отправить предложение ", -1))
            ], 8, _hoisted_20$1)
          ])
        ])
      ])
    ])) : createCommentVNode("", true),
    $props.show ? (openBlock(), createElementBlock("div", _hoisted_22$1)) : createCommentVNode("", true)
  ], 64);
}
const ProposalModal = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-d7e7afe4"]]);
const _sfc_main = {
  name: "PublicRentalRequests",
  components: { ProposalModal },
  props: {
    userRole: String,
    authUser: Object
  },
  data() {
    return {
      loading: true,
      error: null,
      requests: { data: [], meta: {}, links: {} },
      filterCategories: [],
      locations: [],
      filters: {
        category_id: "",
        location_id: "",
        sort: "newest"
      },
      showModal: false,
      selectedRequest: null,
      currentUser: null,
      authChecked: false
    };
  },
  computed: {
    pages() {
      if (!this.requests.meta) return [];
      const current = this.requests.meta.current_page;
      const last = this.requests.meta.last_page;
      const range = 2;
      let start = Math.max(1, current - range);
      let end = Math.min(last, current + range);
      if (end - start < range * 2) {
        if (current < last / 2) {
          end = Math.min(last, start + range * 2);
        } else {
          start = Math.max(1, end - range * 2);
        }
      }
      const pages = [];
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    },
    isAuthenticatedLessor() {
      return this.authUser && this.authUser.company && this.authUser.company.is_lessor;
    },
    // 🎯 Ключевое исправление: обрабатываем данные заявок
    processedRequests() {
      if (!this.requests.data || !Array.isArray(this.requests.data)) {
        return [];
      }
      return this.requests.data.map((request) => {
        const processed = __spreadProps(__spreadValues({}, request), {
          rental_period_display: this.getRentalPeriodDisplay(
            request.rental_period_start,
            request.rental_period_end
          ),
          rental_days: this.calculateRentalDays(
            request.rental_period_start,
            request.rental_period_end
          ),
          created_at_display: this.formatDate(request.created_at),
          items: (request.items || []).map((item) => __spreadProps(__spreadValues({}, item), {
            formatted_specifications: item.formatted_specifications || this.formatSpecifications(item.specifications)
          }))
        });
        if (this.isAuthenticatedLessor && request.lessor_pricing) {
          processed.lessor_pricing = request.lessor_pricing;
        }
        return processed;
      });
    }
  },
  methods: {
    // 🔧 Исправленный метод для отображения периода аренды
    getRentalPeriodDisplay(startDate, endDate) {
      if (!startDate || !endDate) {
        return "Период не указан";
      }
      try {
        const start = this.formatDate(startDate);
        const end = this.formatDate(endDate);
        return `${start} - ${end}`;
      } catch (error) {
        console.error("Ошибка форматирования периода аренды:", error);
        return "Ошибка даты";
      }
    },
    // 🔧 Исправленный расчет дней аренды
    calculateRentalDays(startDate, endDate) {
      if (!startDate || !endDate) {
        return 0;
      }
      try {
        const start = new Date(startDate);
        const end = new Date(endDate);
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
          return 0;
        }
        const timeDiff = end.getTime() - start.getTime();
        const dayDiff = Math.ceil(timeDiff / (1e3 * 3600 * 24)) + 1;
        return dayDiff > 0 ? dayDiff : 0;
      } catch (error) {
        console.error("Ошибка расчета дней аренды:", error);
        return 0;
      }
    },
    // 🔧 Улучшенное форматирование спецификаций
    formatSpecifications(specs) {
      if (!specs || !Array.isArray(specs)) {
        return [];
      }
      return specs.map((spec) => {
        if (typeof spec === "string") {
          return { formatted: spec };
        }
        if (spec.formatted) {
          return spec;
        }
        if (spec.label && spec.value) {
          const unit = spec.unit ? ` ${spec.unit}` : "";
          return __spreadProps(__spreadValues({}, spec), {
            formatted: `${spec.label}: ${spec.value}${unit}`
          });
        }
        return { formatted: JSON.stringify(spec) };
      });
    },
    loadUser() {
      return __async(this, null, function* () {
        try {
          const response = yield fetch("/api/user", {
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          if (response.ok) {
            this.currentUser = yield response.json();
            console.log("✅ Пользователь загружен:", this.currentUser);
          } else {
            console.log("⚠️ Пользователь не авторизован");
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
    loadRequests(page = 1) {
      return __async(this, null, function* () {
        var _a, _b;
        this.loading = true;
        this.error = null;
        try {
          const params = new URLSearchParams(__spreadValues({
            page
          }, this.filters));
          const apiUrl = `/api/public/rental-requests?${params}`;
          const response = yield fetch(apiUrl, {
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
            this.requests = data.data;
            this.filterCategories = ((_a = data.filters) == null ? void 0 : _a.categories) || [];
            this.locations = ((_b = data.filters) == null ? void 0 : _b.locations) || [];
            console.log(
              "✅ Заявки загружены с преобразованными ценами:",
              this.requests.data.map((r) => {
                var _a2;
                return {
                  id: r.id,
                  has_lessor_pricing: !!r.lessor_pricing,
                  lessor_budget: (_a2 = r.lessor_pricing) == null ? void 0 : _a2.total_lessor_budget
                };
              })
            );
          } else {
            throw new Error(data.message || "Ошибка сервера");
          }
        } catch (error) {
          console.error("❌ Ошибка загрузки заявок:", error);
          this.error = `Не удалось загрузить заявки: ${error.message}`;
          this.requests = { data: [], meta: { total: 0, current_page: 1, last_page: 1 } };
        } finally {
          this.loading = false;
        }
      });
    },
    canMakeProposal(request) {
      return this.isAuthenticatedLessor;
    },
    changePage(page) {
      if (page >= 1 && page <= this.requests.meta.last_page) {
        this.loadRequests(page);
      }
    },
    viewRequest(id) {
      if (!id) {
        console.error("ID заявки не указан");
        return;
      }
      window.location.href = `/portal/rental-requests/${id}`;
    },
    showProposalModal(request) {
      if (!this.canMakeProposal(request)) {
        this.redirectToLogin();
        return;
      }
      this.selectedRequest = request;
      this.showModal = true;
    },
    onProposalCreated() {
      this.showModal = false;
      this.loadRequests(this.requests.meta.current_page);
      alert("Предложение успешно отправлено!");
    },
    redirectToLogin() {
      window.location.href = "/login";
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
    }
  },
  mounted() {
    return __async(this, null, function* () {
      yield this.loadUser();
      yield this.loadRequests();
      console.log("Vue Component mounted. User role prop:", this.userRole);
      console.log("Auth user prop:", this.authUser);
      console.log("Is authenticated lessor (computed):", this.isAuthenticatedLessor);
      console.log("📋 Обработанные заявки:", this.processedRequests);
    });
  }
};
const _hoisted_1 = { class: "public-rental-requests" };
const _hoisted_2 = { key: 0 };
const _hoisted_3 = { key: 1 };
const _hoisted_4 = { class: "filters-section bg-light p-4 mb-4" };
const _hoisted_5 = { class: "row g-3" };
const _hoisted_6 = { class: "col-md-4" };
const _hoisted_7 = ["value"];
const _hoisted_8 = { class: "col-md-4" };
const _hoisted_9 = ["value"];
const _hoisted_10 = { class: "col-md-4" };
const _hoisted_11 = { class: "requests-list" };
const _hoisted_12 = {
  key: 0,
  class: "text-center py-5"
};
const _hoisted_13 = {
  key: 1,
  class: "alert alert-danger text-center"
};
const _hoisted_14 = {
  key: 2,
  class: "alert alert-info text-center"
};
const _hoisted_15 = {
  key: 3,
  class: "row"
};
const _hoisted_16 = { class: "card h-100 rental-request-card" };
const _hoisted_17 = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_18 = { class: "card-title mb-0" };
const _hoisted_19 = { class: "badge bg-primary" };
const _hoisted_20 = { class: "card-body" };
const _hoisted_21 = { class: "card-text" };
const _hoisted_22 = { class: "request-meta mb-3" };
const _hoisted_23 = { class: "d-flex justify-content-between text-muted small mb-2" };
const _hoisted_24 = { class: "d-flex justify-content-between text-muted small" };
const _hoisted_25 = {
  key: 0,
  class: "request-items"
};
const _hoisted_26 = {
  key: 0,
  class: "specifications small text-muted mt-1"
};
const _hoisted_27 = {
  key: 1,
  class: "budget-info mt-3 p-3 bg-light rounded"
};
const _hoisted_28 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_29 = { class: "text-success fw-bold" };
const _hoisted_30 = { class: "pricing-details mt-2" };
const _hoisted_31 = { class: "rental-info small text-muted mt-2" };
const _hoisted_32 = {
  key: 2,
  class: "budget-info mt-3 p-3 bg-light rounded"
};
const _hoisted_33 = {
  key: 3,
  class: "budget-info mt-3 p-3 bg-light rounded"
};
const _hoisted_34 = { class: "card-footer" };
const _hoisted_35 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_36 = ["onClick"];
const _hoisted_37 = ["onClick", "disabled"];
const _hoisted_38 = {
  key: 4,
  class: "mt-4"
};
const _hoisted_39 = { class: "pagination justify-content-center" };
const _hoisted_40 = ["onClick"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _a, _b;
  const _component_ProposalModal = resolveComponent("ProposalModal");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    $props.userRole === "lessor" ? (openBlock(), createElementBlock("h2", _hoisted_2, "Панель арендодателя: " + toDisplayString((_b = (_a = $props.authUser) == null ? void 0 : _a.company) == null ? void 0 : _b.legal_name), 1)) : (openBlock(), createElementBlock("h2", _hoisted_3, "Публичные заявки на аренду")),
    createBaseVNode("div", _hoisted_4, [
      createBaseVNode("div", _hoisted_5, [
        createBaseVNode("div", _hoisted_6, [
          _cache[11] || (_cache[11] = createBaseVNode("label", { class: "form-label" }, "Категория", -1)),
          withDirectives(createBaseVNode("select", {
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.filters.category_id = $event),
            class: "form-select",
            onChange: _cache[1] || (_cache[1] = (...args) => $options.loadRequests && $options.loadRequests(...args))
          }, [
            _cache[10] || (_cache[10] = createBaseVNode("option", { value: "" }, "Все категории", -1)),
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.filterCategories, (category) => {
              return openBlock(), createElementBlock("option", {
                key: category.id,
                value: category.id
              }, toDisplayString(category.name), 9, _hoisted_7);
            }), 128))
          ], 544), [
            [vModelSelect, $data.filters.category_id]
          ])
        ]),
        createBaseVNode("div", _hoisted_8, [
          _cache[13] || (_cache[13] = createBaseVNode("label", { class: "form-label" }, "Локация", -1)),
          withDirectives(createBaseVNode("select", {
            "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.filters.location_id = $event),
            class: "form-select",
            onChange: _cache[3] || (_cache[3] = (...args) => $options.loadRequests && $options.loadRequests(...args))
          }, [
            _cache[12] || (_cache[12] = createBaseVNode("option", { value: "" }, "Все локации", -1)),
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.locations, (location) => {
              return openBlock(), createElementBlock("option", {
                key: location.id,
                value: location.id
              }, toDisplayString(location.name), 9, _hoisted_9);
            }), 128))
          ], 544), [
            [vModelSelect, $data.filters.location_id]
          ])
        ]),
        createBaseVNode("div", _hoisted_10, [
          _cache[15] || (_cache[15] = createBaseVNode("label", { class: "form-label" }, "Сортировка", -1)),
          withDirectives(createBaseVNode("select", {
            "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.filters.sort = $event),
            class: "form-select",
            onChange: _cache[5] || (_cache[5] = (...args) => $options.loadRequests && $options.loadRequests(...args))
          }, [..._cache[14] || (_cache[14] = [
            createBaseVNode("option", { value: "newest" }, "Сначала новые", -1),
            createBaseVNode("option", { value: "budget" }, "По бюджету", -1),
            createBaseVNode("option", { value: "proposals" }, "По количеству предложений", -1)
          ])], 544), [
            [vModelSelect, $data.filters.sort]
          ])
        ])
      ])
    ]),
    createBaseVNode("div", _hoisted_11, [
      $data.loading ? (openBlock(), createElementBlock("div", _hoisted_12, [..._cache[16] || (_cache[16] = [
        createBaseVNode("div", {
          class: "spinner-border text-primary",
          role: "status"
        }, [
          createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
        ], -1)
      ])])) : $data.error ? (openBlock(), createElementBlock("div", _hoisted_13, toDisplayString($data.error), 1)) : $data.requests.data.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_14, " Публичные заявки не найдены ")) : (openBlock(), createElementBlock("div", _hoisted_15, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.processedRequests, (request) => {
          var _a2;
          return openBlock(), createElementBlock("div", {
            class: "col-lg-6 mb-4",
            key: request.id
          }, [
            createBaseVNode("div", _hoisted_16, [
              createBaseVNode("div", _hoisted_17, [
                createBaseVNode("h5", _hoisted_18, toDisplayString(request.title || "Без названия"), 1),
                createBaseVNode("span", _hoisted_19, toDisplayString(request.active_proposals_count || 0) + " предложений", 1)
              ]),
              createBaseVNode("div", _hoisted_20, [
                createBaseVNode("p", _hoisted_21, toDisplayString(request.description || "Описание отсутствует"), 1),
                createBaseVNode("div", _hoisted_22, [
                  createBaseVNode("div", _hoisted_23, [
                    createBaseVNode("span", null, [
                      _cache[17] || (_cache[17] = createBaseVNode("i", { class: "fas fa-calendar-alt me-1" }, null, -1)),
                      createTextVNode(" " + toDisplayString(request.rental_period_display), 1)
                    ]),
                    createBaseVNode("span", null, toDisplayString(request.rental_days) + " дней", 1)
                  ]),
                  createBaseVNode("div", _hoisted_24, [
                    createBaseVNode("span", null, [
                      _cache[18] || (_cache[18] = createBaseVNode("i", { class: "fas fa-map-marker-alt me-1" }, null, -1)),
                      createTextVNode(" " + toDisplayString(((_a2 = request.location) == null ? void 0 : _a2.name) || "Локация не указана"), 1)
                    ]),
                    createBaseVNode("span", null, toDisplayString(request.created_at_display), 1)
                  ])
                ]),
                request.items && request.items.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_25, [
                  _cache[19] || (_cache[19] = createBaseVNode("h6", { class: "mb-2" }, "Требуемая техника:", -1)),
                  (openBlock(true), createElementBlock(Fragment, null, renderList(request.items, (item, index) => {
                    var _a3;
                    return openBlock(), createElementBlock("div", {
                      key: index,
                      class: "request-item mb-2"
                    }, [
                      createBaseVNode("strong", null, toDisplayString(((_a3 = item.category) == null ? void 0 : _a3.name) || "Без категории"), 1),
                      createTextVNode(" × " + toDisplayString(item.quantity || 1) + " ", 1),
                      item.specifications && item.specifications.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_26, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList(item.formatted_specifications || item.specifications, (spec) => {
                          return openBlock(), createElementBlock("div", {
                            key: spec.key || spec
                          }, toDisplayString(spec.formatted || spec.label || spec), 1);
                        }), 128))
                      ])) : createCommentVNode("", true)
                    ]);
                  }), 128))
                ])) : createCommentVNode("", true),
                $options.isAuthenticatedLessor && request.lessor_pricing ? (openBlock(), createElementBlock("div", _hoisted_27, [
                  createBaseVNode("div", _hoisted_28, [
                    _cache[20] || (_cache[20] = createBaseVNode("span", { class: "fw-bold" }, "Бюджет для вас:", -1)),
                    createBaseVNode("span", _hoisted_29, toDisplayString($options.formatCurrency(request.lessor_pricing.total_lessor_budget || 0)), 1)
                  ]),
                  createBaseVNode("div", _hoisted_30, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList(request.lessor_pricing.items, (item) => {
                      return openBlock(), createElementBlock("div", {
                        key: item.item_id,
                        class: "price-item small text-muted mb-1"
                      }, toDisplayString(item.category_name) + ": " + toDisplayString(item.quantity) + " шт. × " + toDisplayString($options.formatCurrency(item.lessor_price)) + "/час ", 1);
                    }), 128))
                  ]),
                  createBaseVNode("div", _hoisted_31, [
                    _cache[21] || (_cache[21] = createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1)),
                    createTextVNode(" " + toDisplayString(request.lessor_pricing.working_hours) + " часов (" + toDisplayString(request.lessor_pricing.rental_days) + " дней) ", 1)
                  ])
                ])) : $options.isAuthenticatedLessor ? (openBlock(), createElementBlock("div", _hoisted_32, [..._cache[22] || (_cache[22] = [
                  createBaseVNode("div", { class: "text-center text-muted" }, [
                    createBaseVNode("i", { class: "fas fa-info-circle me-1" }),
                    createTextVNode(" Бюджет заявки доступен при просмотре деталей ")
                  ], -1)
                ])])) : (openBlock(), createElementBlock("div", _hoisted_33, [..._cache[23] || (_cache[23] = [
                  createBaseVNode("div", { class: "text-center text-muted" }, [
                    createBaseVNode("i", { class: "fas fa-info-circle me-1" }),
                    createTextVNode(" Войдите как арендодатель для просмотра бюджета ")
                  ], -1)
                ])]))
              ]),
              createBaseVNode("div", _hoisted_34, [
                createBaseVNode("div", _hoisted_35, [
                  createBaseVNode("button", {
                    class: "btn btn-outline-primary btn-sm",
                    onClick: ($event) => $options.viewRequest(request.id)
                  }, [..._cache[24] || (_cache[24] = [
                    createBaseVNode("i", { class: "fas fa-eye me-1" }, null, -1),
                    createTextVNode("Подробнее ", -1)
                  ])], 8, _hoisted_36),
                  $options.isAuthenticatedLessor ? (openBlock(), createElementBlock("button", {
                    key: 0,
                    class: "btn btn-primary btn-sm",
                    onClick: ($event) => $options.showProposalModal(request),
                    disabled: !$options.canMakeProposal(request)
                  }, [..._cache[25] || (_cache[25] = [
                    createBaseVNode("i", { class: "fas fa-paper-plane me-1" }, null, -1),
                    createTextVNode("Предложить ", -1)
                  ])], 8, _hoisted_37)) : (openBlock(), createElementBlock("button", {
                    key: 1,
                    class: "btn btn-outline-secondary btn-sm",
                    onClick: _cache[6] || (_cache[6] = (...args) => $options.redirectToLogin && $options.redirectToLogin(...args))
                  }, " Войдите для предложения "))
                ])
              ])
            ])
          ]);
        }), 128))
      ])),
      $data.requests.meta && $data.requests.meta.last_page > 1 ? (openBlock(), createElementBlock("nav", _hoisted_38, [
        createBaseVNode("ul", _hoisted_39, [
          createBaseVNode("li", {
            class: normalizeClass(["page-item", { disabled: !$data.requests.links || !$data.requests.links.prev }])
          }, [
            createBaseVNode("button", {
              class: "page-link",
              onClick: _cache[7] || (_cache[7] = ($event) => $options.changePage($data.requests.meta.current_page - 1))
            }, "Назад")
          ], 2),
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.pages, (page) => {
            var _a2;
            return openBlock(), createElementBlock("li", {
              key: page,
              class: normalizeClass(["page-item", { active: page === (((_a2 = $data.requests.meta) == null ? void 0 : _a2.current_page) || 1) }])
            }, [
              createBaseVNode("button", {
                class: "page-link",
                onClick: ($event) => $options.changePage(page)
              }, toDisplayString(page), 9, _hoisted_40)
            ], 2);
          }), 128)),
          createBaseVNode("li", {
            class: normalizeClass(["page-item", { disabled: !$data.requests.links || !$data.requests.links.next }])
          }, [
            createBaseVNode("button", {
              class: "page-link",
              onClick: _cache[8] || (_cache[8] = ($event) => $options.changePage($data.requests.meta.current_page + 1))
            }, "Вперед")
          ], 2)
        ])
      ])) : createCommentVNode("", true),
      $data.showModal ? (openBlock(), createBlock(_component_ProposalModal, {
        key: 5,
        request: $data.selectedRequest,
        onClose: _cache[9] || (_cache[9] = ($event) => $data.showModal = false),
        onProposalCreated: $options.onProposalCreated
      }, null, 8, ["request", "onProposalCreated"])) : createCommentVNode("", true)
    ])
  ]);
}
const RentalRequests = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-c0fe6b41"]]);
console.log("🟢 rental-requests.js - Инициализация Vue приложения для публичных заявок");
document.addEventListener("DOMContentLoaded", function() {
  const appContainer = document.getElementById("rental-requests-app");
  if (appContainer) {
    console.log("✅ Найден контейнер #rental-requests-app");
    try {
      const userRole = appContainer.dataset.userRole || "guest";
      let authUser = null;
      try {
        authUser = appContainer.dataset.authUser ? JSON.parse(appContainer.dataset.authUser) : null;
      } catch (e) {
      }
      const app = createApp(RentalRequests, {
        userRole,
        authUser
      });
      app.mount("#rental-requests-app");
      console.log("🎉 Vue приложение публичных заявок успешно смонтировано");
    } catch (error) {
      console.error("❌ Ошибка монтирования Vue приложения:", error);
    }
  } else {
    console.log("❌ Контейнер #rental-requests-app не найден, ищем альтернативный...");
    const altContainer = document.getElementById("rental-request-app");
    if (altContainer) {
      try {
        const app = createApp(RentalRequests);
        app.mount("#rental-request-app");
        console.log("🎉 Vue смонтирован на #rental-request-app (альтернативный)");
      } catch (error) {
        console.error("❌ Ошибка монтирования альтернативного приложения:", error);
      }
    }
  }
});
