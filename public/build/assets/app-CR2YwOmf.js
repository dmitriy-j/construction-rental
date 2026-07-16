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
var _d;
import { a as createElementBlock, o as openBlock, b as createBaseVNode, e as createCommentVNode, d as createTextVNode, w as withDirectives, j as vModelText, v as vModelSelect, F as Fragment, r as renderList, t as toDisplayString, n as normalizeClass, c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { C as Chart, r as registerables, S as Swal } from "./sweetalert2.esm.all-DkqDp_b4.js";
import { a as axios } from "./index-DM4mtReV.js";
class EventBus {
  constructor() {
    this.listeners = {};
  }
  on(event, callback) {
    if (!this.listeners[event]) {
      this.listeners[event] = [];
    }
    this.listeners[event].push(callback);
    return () => {
      this.listeners[event] = this.listeners[event].filter((cb) => cb !== callback);
    };
  }
  emit(event, data2) {
    if (this.listeners[event]) {
      this.listeners[event].forEach((cb) => cb(data2));
    }
  }
}
window.cartBus = new EventBus();
const _sfc_main$1 = {
  data() {
    return {
      equipment: [],
      meta: { current_page: 1, last_page: 1, total: 0 },
      filters: { category: "", location: "", min_price: null, max_price: null, search: "" },
      sort: "newest",
      perPage: 12,
      loading: false,
      filterOptions: { categories: [], locations: [] },
      searchTimer: null,
      showModal: false,
      modalEquipment: null,
      form: {
        start_date: "",
        end_date: "",
        shifts_per_day: 1,
        hours_per_shift: 8,
        quantity: 1,
        address: ""
      },
      priceData: null,
      adding: false,
      error: null
    };
  },
  computed: {
    total() {
      return this.meta.total;
    },
    minDate() {
      const d = /* @__PURE__ */ new Date();
      d.setDate(d.getDate() + 1);
      return d.toISOString().split("T")[0];
    },
    pages() {
      let p = [], start2 = Math.max(1, this.meta.current_page - 2);
      let end = Math.min(this.meta.last_page, start2 + 4);
      for (let i = start2; i <= end; i++) p.push(i);
      return p;
    }
  },
  methods: {
    loadEquipment() {
      return __async(this, null, function* () {
        this.loading = true;
        const params = new URLSearchParams({
          page: this.meta.current_page,
          per_page: this.perPage,
          sort: this.sort,
          category: this.filters.category,
          location: this.filters.location,
          min_price: this.filters.min_price || "",
          max_price: this.filters.max_price || "",
          search: this.filters.search
        });
        try {
          const res = yield fetch("/api/equipment?" + params.toString());
          const data2 = yield res.json();
          this.equipment = data2.data || [];
          this.meta = data2.meta || { current_page: 1, last_page: 1, total: 0 };
          if (data2.filters) this.filterOptions = data2.filters;
        } catch (e) {
          console.error(e);
        }
        this.loading = false;
      });
    },
    changePage(page) {
      if (page < 1 || page > this.meta.last_page) return;
      this.meta.current_page = page;
      this.loadEquipment();
    },
    onSearchInput() {
      clearTimeout(this.searchTimer);
      this.searchTimer = setTimeout(() => this.loadEquipment(), 300);
    },
    resetFilters() {
      this.filters = { category: "", location: "", min_price: null, max_price: null, search: "" };
      this.sort = "newest";
      this.meta.current_page = 1;
      this.loadEquipment();
    },
    openAddModal(eq) {
      this.modalEquipment = eq;
      const d = /* @__PURE__ */ new Date();
      d.setDate(d.getDate() + 1);
      const tomorrow = d.toISOString().split("T")[0];
      const dayAfter = new Date(d);
      dayAfter.setDate(dayAfter.getDate() + 1);
      this.form = {
        start_date: tomorrow,
        end_date: dayAfter.toISOString().split("T")[0],
        shifts_per_day: 1,
        hours_per_shift: 8,
        quantity: 1,
        address: ""
      };
      this.priceData = null;
      this.error = null;
      this.showModal = true;
      document.body.classList.add("modal-open");
      this.recalculatePrice();
    },
    closeModal() {
      this.showModal = false;
      document.body.classList.remove("modal-open");
    },
    recalculatePrice() {
      return __async(this, null, function* () {
        if (!this.form.start_date || !this.form.end_date || !this.modalEquipment) return;
        try {
          const params = new URLSearchParams({
            start_date: this.form.start_date,
            end_date: this.form.end_date,
            shifts_per_day: this.form.shifts_per_day || 1,
            hours_per_shift: this.form.hours_per_shift || 8,
            quantity: this.form.quantity || 1
          });
          const res = yield fetch(`/api/equipment/${this.modalEquipment.id}/price?` + params.toString());
          const data2 = yield res.json();
          if (data2.success) {
            this.priceData = data2;
          } else {
            console.error("Price API error:", data2);
          }
        } catch (e) {
          console.error("Price recalculation error:", e);
        }
      });
    },
    addToCart() {
      return __async(this, null, function* () {
        if (!this.form.start_date || !this.form.end_date) {
          this.error = "Выберите даты аренды";
          return;
        }
        if (!this.priceData || !this.priceData.is_available) {
          this.error = "Техника недоступна на выбранные даты";
          return;
        }
        this.adding = true;
        this.error = null;
        try {
          const res = yield fetch("/api/cart", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": window.csrfToken },
            body: JSON.stringify({
              equipment_id: this.modalEquipment.id,
              start_date: this.form.start_date,
              end_date: this.form.end_date,
              shifts_per_day: this.form.shifts_per_day,
              hours_per_shift: this.form.hours_per_shift,
              quantity: this.form.quantity,
              address: this.form.address
            })
          });
          const data2 = yield res.json();
          if (data2.success) {
            this.closeModal();
            if (window.cartBus) {
              window.cartBus.emit("cart-updated", data2);
            }
          } else {
            this.error = data2.error || "Ошибка добавления в корзину";
          }
        } catch (e) {
          console.error("Add to cart error:", e);
          this.error = "Ошибка соединения с сервером";
        }
        this.adding = false;
      });
    }
  },
  mounted() {
    this.loadEquipment();
  }
};
const _hoisted_1$1 = { class: "container-fluid" };
const _hoisted_2$1 = { class: "row" };
const _hoisted_3$1 = { class: "col-lg-3 col-xl-2 mb-4" };
const _hoisted_4$1 = { class: "card shadow-sm" };
const _hoisted_5$1 = { class: "card-header bg-white d-flex justify-content-between align-items-center" };
const _hoisted_6$1 = { class: "card-body" };
const _hoisted_7$1 = { class: "mb-3" };
const _hoisted_8$1 = { class: "mb-3" };
const _hoisted_9$1 = ["value"];
const _hoisted_10$1 = { class: "mb-3" };
const _hoisted_11$1 = ["value"];
const _hoisted_12$1 = { class: "mb-3" };
const _hoisted_13$1 = { class: "row g-1" };
const _hoisted_14$1 = { class: "col-6" };
const _hoisted_15$1 = { class: "col-6" };
const _hoisted_16$1 = { class: "mb-3" };
const _hoisted_17$1 = { class: "mb-3" };
const _hoisted_18$1 = { class: "col-lg-9 col-xl-10" };
const _hoisted_19$1 = { class: "d-flex justify-content-between align-items-center mb-3" };
const _hoisted_20$1 = { class: "mb-0" };
const _hoisted_21$1 = { class: "text-muted fs-6" };
const _hoisted_22$1 = {
  key: 0,
  class: "text-muted small"
};
const _hoisted_23$1 = {
  key: 0,
  class: "row g-3"
};
const _hoisted_24$1 = { class: "card h-100 shadow-sm" };
const _hoisted_25$1 = { class: "position-relative" };
const _hoisted_26$1 = ["src"];
const _hoisted_27$1 = {
  key: 0,
  class: "position-absolute top-0 end-0 m-2 badge bg-primary"
};
const _hoisted_28$1 = { class: "card-body d-flex flex-column" };
const _hoisted_29$1 = { class: "fw-bold text-truncate" };
const _hoisted_30$1 = { class: "small text-muted mb-1" };
const _hoisted_31$1 = { class: "d-flex justify-content-between small text-muted mb-2" };
const _hoisted_32$1 = { class: "mt-auto" };
const _hoisted_33$1 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_34$1 = { class: "fs-5 fw-bold text-primary" };
const _hoisted_35$1 = {
  key: 0,
  class: "text-warning small"
};
const _hoisted_36$1 = { class: "d-grid gap-1 mt-2" };
const _hoisted_37$1 = ["href"];
const _hoisted_38$1 = ["onClick"];
const _hoisted_39$1 = {
  key: 1,
  class: "text-center py-5"
};
const _hoisted_40$1 = {
  key: 2,
  class: "text-center py-5"
};
const _hoisted_41$1 = {
  key: 3,
  class: "mt-4 d-flex justify-content-between align-items-center"
};
const _hoisted_42$1 = { class: "small text-muted" };
const _hoisted_43$1 = { class: "pagination pagination-sm mb-0" };
const _hoisted_44$1 = ["onClick"];
const _hoisted_45$1 = {
  key: 1,
  class: "modal fade show d-block",
  tabindex: "-1",
  style: { "z-index": "10060" }
};
const _hoisted_46$1 = {
  class: "modal-dialog modal-lg modal-dialog-centered",
  style: { "max-width": "700px" }
};
const _hoisted_47$1 = { class: "modal-content" };
const _hoisted_48$1 = { class: "modal-header bg-white" };
const _hoisted_49$1 = { class: "modal-title" };
const _hoisted_50$1 = { class: "modal-body" };
const _hoisted_51$1 = { class: "row g-3" };
const _hoisted_52$1 = { class: "col-md-6" };
const _hoisted_53$1 = { class: "card bg-light h-100" };
const _hoisted_54$1 = { class: "card-body" };
const _hoisted_55$1 = { class: "mb-3" };
const _hoisted_56$1 = ["min"];
const _hoisted_57$1 = { class: "mb-3" };
const _hoisted_58$1 = ["min"];
const _hoisted_59$1 = { class: "mb-3" };
const _hoisted_60$1 = { class: "col-md-6" };
const _hoisted_61$1 = { class: "card bg-light h-100" };
const _hoisted_62$1 = { class: "card-body" };
const _hoisted_63$1 = { class: "mb-3" };
const _hoisted_64$1 = { class: "mb-3" };
const _hoisted_65$1 = ["value"];
const _hoisted_66$1 = { class: "mb-3" };
const _hoisted_67$1 = {
  key: 0,
  class: "card mt-3 border-primary"
};
const _hoisted_68$1 = { class: "card-body" };
const _hoisted_69$1 = { class: "row text-center" };
const _hoisted_70$1 = { class: "col-4 border-end" };
const _hoisted_71$1 = { class: "fs-5 fw-bold" };
const _hoisted_72$1 = { class: "col-4 border-end" };
const _hoisted_73$1 = { class: "fs-5 fw-bold" };
const _hoisted_74$1 = { class: "col-4" };
const _hoisted_75 = { class: "fs-5 fw-bold text-primary" };
const _hoisted_76 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_77 = { class: "small text-muted" };
const _hoisted_78 = { class: "text-end" };
const _hoisted_79 = { class: "fs-4 fw-bold text-success" };
const _hoisted_80 = {
  key: 0,
  class: "alert alert-warning mt-2 mb-0 py-2 small"
};
const _hoisted_81 = {
  key: 1,
  class: "alert alert-danger mt-3 py-2 small"
};
const _hoisted_82 = { class: "modal-footer bg-light" };
const _hoisted_83 = ["disabled"];
const _hoisted_84 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1"
};
const _hoisted_85 = {
  key: 1,
  class: "bi bi-cart-plus me-1"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  var _a;
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("div", _hoisted_1$1, [
      createBaseVNode("div", _hoisted_2$1, [
        createBaseVNode("div", _hoisted_3$1, [
          createBaseVNode("div", _hoisted_4$1, [
            createBaseVNode("div", _hoisted_5$1, [
              _cache[32] || (_cache[32] = createBaseVNode("h5", { class: "mb-0" }, [
                createBaseVNode("i", { class: "bi bi-funnel text-primary" }),
                createTextVNode(" Фильтры")
              ], -1)),
              createBaseVNode("button", {
                class: "btn btn-sm btn-outline-secondary",
                onClick: _cache[0] || (_cache[0] = (...args) => $options.resetFilters && $options.resetFilters(...args))
              }, "Сброс")
            ]),
            createBaseVNode("div", _hoisted_6$1, [
              createBaseVNode("div", _hoisted_7$1, [
                _cache[33] || (_cache[33] = createBaseVNode("label", { class: "form-label fw-semibold small" }, "Поиск", -1)),
                withDirectives(createBaseVNode("input", {
                  type: "text",
                  class: "form-control form-control-sm",
                  "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.filters.search = $event),
                  onInput: _cache[2] || (_cache[2] = (...args) => $options.onSearchInput && $options.onSearchInput(...args))
                }, null, 544), [
                  [vModelText, $data.filters.search]
                ])
              ]),
              createBaseVNode("div", _hoisted_8$1, [
                _cache[35] || (_cache[35] = createBaseVNode("label", { class: "form-label fw-semibold small" }, "Категория", -1)),
                withDirectives(createBaseVNode("select", {
                  class: "form-select form-select-sm",
                  "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.filters.category = $event),
                  onChange: _cache[4] || (_cache[4] = (...args) => $options.loadEquipment && $options.loadEquipment(...args))
                }, [
                  _cache[34] || (_cache[34] = createBaseVNode("option", { value: "" }, "Все", -1)),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($data.filterOptions.categories, (c) => {
                    return openBlock(), createElementBlock("option", {
                      key: c.id,
                      value: c.id
                    }, toDisplayString(c.name), 9, _hoisted_9$1);
                  }), 128))
                ], 544), [
                  [vModelSelect, $data.filters.category]
                ])
              ]),
              createBaseVNode("div", _hoisted_10$1, [
                _cache[37] || (_cache[37] = createBaseVNode("label", { class: "form-label fw-semibold small" }, "Локация", -1)),
                withDirectives(createBaseVNode("select", {
                  class: "form-select form-select-sm",
                  "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.filters.location = $event),
                  onChange: _cache[6] || (_cache[6] = (...args) => $options.loadEquipment && $options.loadEquipment(...args))
                }, [
                  _cache[36] || (_cache[36] = createBaseVNode("option", { value: "" }, "Все", -1)),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($data.filterOptions.locations, (l) => {
                    return openBlock(), createElementBlock("option", {
                      key: l.id,
                      value: l.id
                    }, toDisplayString(l.name), 9, _hoisted_11$1);
                  }), 128))
                ], 544), [
                  [vModelSelect, $data.filters.location]
                ])
              ]),
              createBaseVNode("div", _hoisted_12$1, [
                _cache[38] || (_cache[38] = createBaseVNode("label", { class: "form-label fw-semibold small" }, "Цена (₽/час)", -1)),
                createBaseVNode("div", _hoisted_13$1, [
                  createBaseVNode("div", _hoisted_14$1, [
                    withDirectives(createBaseVNode("input", {
                      type: "number",
                      class: "form-control form-control-sm",
                      placeholder: "от",
                      "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.filters.min_price = $event),
                      onChange: _cache[8] || (_cache[8] = (...args) => $options.loadEquipment && $options.loadEquipment(...args))
                    }, null, 544), [
                      [
                        vModelText,
                        $data.filters.min_price,
                        void 0,
                        { number: true }
                      ]
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_15$1, [
                    withDirectives(createBaseVNode("input", {
                      type: "number",
                      class: "form-control form-control-sm",
                      placeholder: "до",
                      "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => $data.filters.max_price = $event),
                      onChange: _cache[10] || (_cache[10] = (...args) => $options.loadEquipment && $options.loadEquipment(...args))
                    }, null, 544), [
                      [
                        vModelText,
                        $data.filters.max_price,
                        void 0,
                        { number: true }
                      ]
                    ])
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_16$1, [
                _cache[40] || (_cache[40] = createBaseVNode("label", { class: "form-label fw-semibold small" }, "Сортировка", -1)),
                withDirectives(createBaseVNode("select", {
                  class: "form-select form-select-sm",
                  "onUpdate:modelValue": _cache[11] || (_cache[11] = ($event) => $data.sort = $event),
                  onChange: _cache[12] || (_cache[12] = (...args) => $options.loadEquipment && $options.loadEquipment(...args))
                }, [..._cache[39] || (_cache[39] = [
                  createBaseVNode("option", { value: "newest" }, "Новые", -1),
                  createBaseVNode("option", { value: "price_asc" }, "Дешёвые", -1),
                  createBaseVNode("option", { value: "price_desc" }, "Дорогие", -1),
                  createBaseVNode("option", { value: "popular" }, "Популярные", -1)
                ])], 544), [
                  [vModelSelect, $data.sort]
                ])
              ]),
              createBaseVNode("div", _hoisted_17$1, [
                _cache[42] || (_cache[42] = createBaseVNode("label", { class: "form-label fw-semibold small" }, "На странице", -1)),
                withDirectives(createBaseVNode("select", {
                  class: "form-select form-select-sm",
                  "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => $data.perPage = $event),
                  onChange: _cache[14] || (_cache[14] = (...args) => $options.loadEquipment && $options.loadEquipment(...args))
                }, [..._cache[41] || (_cache[41] = [
                  createBaseVNode("option", { value: "12" }, "12", -1),
                  createBaseVNode("option", { value: "24" }, "24", -1),
                  createBaseVNode("option", { value: "48" }, "48", -1)
                ])], 544), [
                  [vModelSelect, $data.perPage]
                ])
              ])
            ])
          ])
        ]),
        createBaseVNode("div", _hoisted_18$1, [
          createBaseVNode("div", _hoisted_19$1, [
            createBaseVNode("h2", _hoisted_20$1, [
              _cache[43] || (_cache[43] = createTextVNode("Каталог техники ", -1)),
              createBaseVNode("small", _hoisted_21$1, "(" + toDisplayString($options.total) + " ед.)", 1)
            ]),
            $data.loading ? (openBlock(), createElementBlock("span", _hoisted_22$1, [..._cache[44] || (_cache[44] = [
              createBaseVNode("i", { class: "bi bi-hourglass-split" }, null, -1),
              createTextVNode(" Загрузка...", -1)
            ])])) : createCommentVNode("", true)
          ]),
          !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_23$1, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.equipment, (eq) => {
              return openBlock(), createElementBlock("div", {
                key: eq.id,
                class: "col-xl-3 col-lg-4 col-md-6"
              }, [
                createBaseVNode("div", _hoisted_24$1, [
                  createBaseVNode("div", _hoisted_25$1, [
                    createBaseVNode("img", {
                      src: eq.main_image_url || "/images/no-image.svg",
                      class: "card-img-top",
                      alt: "",
                      style: { "height": "200px", "object-fit": "cover" }
                    }, null, 8, _hoisted_26$1),
                    eq.is_platform_owned ? (openBlock(), createElementBlock("span", _hoisted_27$1, [..._cache[45] || (_cache[45] = [
                      createBaseVNode("i", { class: "bi bi-building-gear" }, null, -1),
                      createTextVNode(" Платформа", -1)
                    ])])) : createCommentVNode("", true)
                  ]),
                  createBaseVNode("div", _hoisted_28$1, [
                    createBaseVNode("h6", _hoisted_29$1, toDisplayString(eq.brand) + " " + toDisplayString(eq.model), 1),
                    createBaseVNode("p", _hoisted_30$1, toDisplayString(eq.title), 1),
                    createBaseVNode("div", _hoisted_31$1, [
                      createBaseVNode("span", null, [
                        _cache[46] || (_cache[46] = createBaseVNode("i", { class: "bi bi-calendar" }, null, -1)),
                        createTextVNode(" " + toDisplayString(eq.year), 1)
                      ]),
                      createBaseVNode("span", null, [
                        _cache[47] || (_cache[47] = createBaseVNode("i", { class: "bi bi-geo-alt" }, null, -1)),
                        createTextVNode(" " + toDisplayString(eq.location_name), 1)
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_32$1, [
                      createBaseVNode("div", _hoisted_33$1, [
                        createBaseVNode("span", _hoisted_34$1, [
                          createTextVNode(toDisplayString(eq.final_price) + " ₽", 1),
                          _cache[48] || (_cache[48] = createBaseVNode("small", { class: "fw-normal text-muted fs-6" }, "/час", -1))
                        ]),
                        eq.rating > 0 ? (openBlock(), createElementBlock("span", _hoisted_35$1, [
                          _cache[49] || (_cache[49] = createBaseVNode("i", { class: "bi bi-star-fill" }, null, -1)),
                          createTextVNode(" " + toDisplayString(eq.rating), 1)
                        ])) : createCommentVNode("", true)
                      ]),
                      createBaseVNode("div", _hoisted_36$1, [
                        createBaseVNode("a", {
                          href: "/catalog/" + eq.id,
                          class: "btn btn-outline-primary btn-sm"
                        }, "Подробнее", 8, _hoisted_37$1),
                        createBaseVNode("button", {
                          class: "btn btn-primary btn-sm",
                          onClick: ($event) => $options.openAddModal(eq)
                        }, [..._cache[50] || (_cache[50] = [
                          createBaseVNode("i", { class: "bi bi-cart-plus" }, null, -1),
                          createTextVNode(" В корзину", -1)
                        ])], 8, _hoisted_38$1)
                      ])
                    ])
                  ])
                ])
              ]);
            }), 128))
          ])) : createCommentVNode("", true),
          $data.loading ? (openBlock(), createElementBlock("div", _hoisted_39$1, [..._cache[51] || (_cache[51] = [
            createBaseVNode("div", { class: "spinner-border text-primary" }, null, -1)
          ])])) : createCommentVNode("", true),
          !$data.loading && $data.equipment.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_40$1, [..._cache[52] || (_cache[52] = [
            createBaseVNode("i", { class: "bi bi-box-seam display-1 text-muted" }, null, -1),
            createBaseVNode("p", { class: "mt-3 text-muted" }, "Техника не найдена", -1)
          ])])) : createCommentVNode("", true),
          $data.meta.last_page > 1 ? (openBlock(), createElementBlock("nav", _hoisted_41$1, [
            createBaseVNode("span", _hoisted_42$1, "Стр. " + toDisplayString($data.meta.current_page) + "/" + toDisplayString($data.meta.last_page), 1),
            createBaseVNode("ul", _hoisted_43$1, [
              createBaseVNode("li", {
                class: normalizeClass(["page-item", { disabled: $data.meta.current_page <= 1 }])
              }, [
                createBaseVNode("button", {
                  class: "page-link",
                  onClick: _cache[15] || (_cache[15] = ($event) => $options.changePage($data.meta.current_page - 1))
                }, "«")
              ], 2),
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.pages, (p) => {
                return openBlock(), createElementBlock("li", {
                  class: normalizeClass(["page-item", { active: p === $data.meta.current_page }]),
                  key: p
                }, [
                  createBaseVNode("button", {
                    class: "page-link",
                    onClick: ($event) => $options.changePage(p)
                  }, toDisplayString(p), 9, _hoisted_44$1)
                ], 2);
              }), 128)),
              createBaseVNode("li", {
                class: normalizeClass(["page-item", { disabled: $data.meta.current_page >= $data.meta.last_page }])
              }, [
                createBaseVNode("button", {
                  class: "page-link",
                  onClick: _cache[16] || (_cache[16] = ($event) => $options.changePage($data.meta.current_page + 1))
                }, "»")
              ], 2)
            ])
          ])) : createCommentVNode("", true)
        ])
      ])
    ]),
    $data.showModal ? (openBlock(), createElementBlock("div", {
      key: 0,
      class: "modal-backdrop fade show",
      onClick: _cache[17] || (_cache[17] = (...args) => $options.closeModal && $options.closeModal(...args))
    })) : createCommentVNode("", true),
    $data.showModal ? (openBlock(), createElementBlock("div", _hoisted_45$1, [
      createBaseVNode("div", _hoisted_46$1, [
        createBaseVNode("div", _hoisted_47$1, [
          createBaseVNode("div", _hoisted_48$1, [
            createBaseVNode("h5", _hoisted_49$1, [
              _cache[53] || (_cache[53] = createBaseVNode("i", { class: "bi bi-cart-plus text-primary me-2" }, null, -1)),
              createTextVNode(" Добавить: " + toDisplayString($data.modalEquipment.brand) + " " + toDisplayString($data.modalEquipment.model), 1)
            ]),
            createBaseVNode("button", {
              type: "button",
              class: "btn-close",
              onClick: _cache[18] || (_cache[18] = (...args) => $options.closeModal && $options.closeModal(...args))
            })
          ]),
          createBaseVNode("div", _hoisted_50$1, [
            createBaseVNode("div", _hoisted_51$1, [
              createBaseVNode("div", _hoisted_52$1, [
                createBaseVNode("div", _hoisted_53$1, [
                  createBaseVNode("div", _hoisted_54$1, [
                    _cache[57] || (_cache[57] = createBaseVNode("h6", { class: "fw-bold mb-3" }, [
                      createBaseVNode("i", { class: "bi bi-calendar-range me-2" }),
                      createTextVNode("Период аренды")
                    ], -1)),
                    createBaseVNode("div", _hoisted_55$1, [
                      _cache[54] || (_cache[54] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Дата начала", -1)),
                      withDirectives(createBaseVNode("input", {
                        type: "date",
                        class: "form-control",
                        "onUpdate:modelValue": _cache[19] || (_cache[19] = ($event) => $data.form.start_date = $event),
                        min: $options.minDate,
                        onChange: _cache[20] || (_cache[20] = (...args) => $options.recalculatePrice && $options.recalculatePrice(...args))
                      }, null, 40, _hoisted_56$1), [
                        [vModelText, $data.form.start_date]
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_57$1, [
                      _cache[55] || (_cache[55] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Дата окончания", -1)),
                      withDirectives(createBaseVNode("input", {
                        type: "date",
                        class: "form-control",
                        "onUpdate:modelValue": _cache[21] || (_cache[21] = ($event) => $data.form.end_date = $event),
                        min: $data.form.start_date || $options.minDate,
                        onChange: _cache[22] || (_cache[22] = (...args) => $options.recalculatePrice && $options.recalculatePrice(...args))
                      }, null, 40, _hoisted_58$1), [
                        [vModelText, $data.form.end_date]
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_59$1, [
                      _cache[56] || (_cache[56] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Адрес доставки (опционально)", -1)),
                      withDirectives(createBaseVNode("input", {
                        type: "text",
                        class: "form-control",
                        "onUpdate:modelValue": _cache[23] || (_cache[23] = ($event) => $data.form.address = $event),
                        placeholder: "г. Москва, ул. Строителей, д. 10"
                      }, null, 512), [
                        [vModelText, $data.form.address]
                      ])
                    ])
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_60$1, [
                createBaseVNode("div", _hoisted_61$1, [
                  createBaseVNode("div", _hoisted_62$1, [
                    _cache[62] || (_cache[62] = createBaseVNode("h6", { class: "fw-bold mb-3" }, [
                      createBaseVNode("i", { class: "bi bi-gear me-2" }),
                      createTextVNode("Условия работы")
                    ], -1)),
                    createBaseVNode("div", _hoisted_63$1, [
                      _cache[59] || (_cache[59] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Смен в сутки", -1)),
                      withDirectives(createBaseVNode("select", {
                        class: "form-select",
                        "onUpdate:modelValue": _cache[24] || (_cache[24] = ($event) => $data.form.shifts_per_day = $event),
                        onChange: _cache[25] || (_cache[25] = (...args) => $options.recalculatePrice && $options.recalculatePrice(...args))
                      }, [..._cache[58] || (_cache[58] = [
                        createBaseVNode("option", { value: 1 }, "1 смена", -1),
                        createBaseVNode("option", { value: 2 }, "2 смены", -1)
                      ])], 544), [
                        [
                          vModelSelect,
                          $data.form.shifts_per_day,
                          void 0,
                          { number: true }
                        ]
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_64$1, [
                      _cache[60] || (_cache[60] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Часов в смене", -1)),
                      withDirectives(createBaseVNode("select", {
                        class: "form-select",
                        "onUpdate:modelValue": _cache[26] || (_cache[26] = ($event) => $data.form.hours_per_shift = $event),
                        onChange: _cache[27] || (_cache[27] = (...args) => $options.recalculatePrice && $options.recalculatePrice(...args))
                      }, [
                        (openBlock(), createElementBlock(Fragment, null, renderList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], (h) => {
                          return createBaseVNode("option", {
                            key: h,
                            value: h
                          }, toDisplayString(h) + " ч", 9, _hoisted_65$1);
                        }), 64))
                      ], 544), [
                        [
                          vModelSelect,
                          $data.form.hours_per_shift,
                          void 0,
                          { number: true }
                        ]
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_66$1, [
                      _cache[61] || (_cache[61] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Количество единиц", -1)),
                      withDirectives(createBaseVNode("input", {
                        type: "number",
                        class: "form-control",
                        "onUpdate:modelValue": _cache[28] || (_cache[28] = ($event) => $data.form.quantity = $event),
                        min: "1",
                        onChange: _cache[29] || (_cache[29] = (...args) => $options.recalculatePrice && $options.recalculatePrice(...args))
                      }, null, 544), [
                        [
                          vModelText,
                          $data.form.quantity,
                          void 0,
                          { number: true }
                        ]
                      ])
                    ])
                  ])
                ])
              ])
            ]),
            $data.priceData ? (openBlock(), createElementBlock("div", _hoisted_67$1, [
              createBaseVNode("div", _hoisted_68$1, [
                _cache[68] || (_cache[68] = createBaseVNode("h6", { class: "fw-bold text-primary mb-3" }, [
                  createBaseVNode("i", { class: "bi bi-calculator me-2" }),
                  createTextVNode("Предварительный расчёт")
                ], -1)),
                createBaseVNode("div", _hoisted_69$1, [
                  createBaseVNode("div", _hoisted_70$1, [
                    _cache[63] || (_cache[63] = createBaseVNode("div", { class: "small text-muted" }, "Дней аренды", -1)),
                    createBaseVNode("div", _hoisted_71$1, toDisplayString($data.priceData.days), 1)
                  ]),
                  createBaseVNode("div", _hoisted_72$1, [
                    _cache[64] || (_cache[64] = createBaseVNode("div", { class: "small text-muted" }, "Всего часов", -1)),
                    createBaseVNode("div", _hoisted_73$1, toDisplayString($data.priceData.total_hours), 1)
                  ]),
                  createBaseVNode("div", _hoisted_74$1, [
                    _cache[65] || (_cache[65] = createBaseVNode("div", { class: "small text-muted" }, "Ставка/час", -1)),
                    createBaseVNode("div", _hoisted_75, toDisplayString($data.priceData.final_price_per_hour) + " ₽", 1)
                  ])
                ]),
                _cache[69] || (_cache[69] = createBaseVNode("hr", null, null, -1)),
                createBaseVNode("div", _hoisted_76, [
                  createBaseVNode("div", _hoisted_77, toDisplayString($data.priceData.days) + " дн. × " + toDisplayString($data.priceData.total_hours) + " ч работы", 1),
                  createBaseVNode("div", _hoisted_78, [
                    _cache[66] || (_cache[66] = createBaseVNode("div", { class: "small text-muted" }, "Итоговая стоимость", -1)),
                    createBaseVNode("div", _hoisted_79, toDisplayString($data.priceData.total_final) + " ₽", 1)
                  ])
                ]),
                !$data.priceData.is_available ? (openBlock(), createElementBlock("div", _hoisted_80, [..._cache[67] || (_cache[67] = [
                  createBaseVNode("i", { class: "bi bi-exclamation-triangle me-1" }, null, -1),
                  createTextVNode(" Техника недоступна на выбранные даты ", -1)
                ])])) : createCommentVNode("", true)
              ])
            ])) : createCommentVNode("", true),
            $data.error ? (openBlock(), createElementBlock("div", _hoisted_81, toDisplayString($data.error), 1)) : createCommentVNode("", true)
          ]),
          createBaseVNode("div", _hoisted_82, [
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-secondary",
              onClick: _cache[30] || (_cache[30] = (...args) => $options.closeModal && $options.closeModal(...args))
            }, "Отмена"),
            createBaseVNode("button", {
              type: "button",
              class: "btn btn-primary",
              onClick: _cache[31] || (_cache[31] = (...args) => $options.addToCart && $options.addToCart(...args)),
              disabled: $data.adding || !((_a = $data.priceData) == null ? void 0 : _a.is_available)
            }, [
              $data.adding ? (openBlock(), createElementBlock("span", _hoisted_84)) : (openBlock(), createElementBlock("i", _hoisted_85)),
              createTextVNode(" " + toDisplayString($data.adding ? "Добавление..." : "Добавить в корзину"), 1)
            ], 8, _hoisted_83)
          ])
        ])
      ])
    ])) : createCommentVNode("", true)
  ]);
}
const CatalogApp = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const MONTHS = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
const DAYS_OF_WEEK = ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"];
const _sfc_main = {
  props: {
    equipmentId: { type: [Number, String], required: true }
  },
  data() {
    return {
      equipment: {},
      loading: true,
      form: {
        start_date: "",
        end_date: "",
        shifts_per_day: 1,
        hours_per_shift: 8,
        quantity: 1,
        address: ""
      },
      priceData: null,
      adding: false,
      error: null,
      // Календарь
      currentMonth: (/* @__PURE__ */ new Date()).getMonth(),
      currentYear: (/* @__PURE__ */ new Date()).getFullYear(),
      bookedDates: [],
      calendarDays: []
    };
  },
  computed: {
    minDate() {
      const d = /* @__PURE__ */ new Date();
      d.setDate(d.getDate() + 1);
      return d.toISOString().split("T")[0];
    },
    currentMonthName() {
      return MONTHS[this.currentMonth] || "";
    }
  },
  methods: {
    loadEquipment() {
      return __async(this, null, function* () {
        try {
          const res = yield fetch(`/api/equipment/${this.equipmentId}`);
          const data2 = yield res.json();
          if (data2.error) {
            this.error = data2.error;
            return;
          }
          this.equipment = data2;
          const tomorrow = data2.default_start || this.minDate;
          const d = new Date(tomorrow);
          d.setDate(d.getDate() + 1);
          this.form.start_date = tomorrow;
          this.form.end_date = d.toISOString().split("T")[0];
          this.loadAvailability();
          this.recalculatePrice();
        } catch (e) {
          this.error = "Ошибка загрузки данных";
        }
        this.loading = false;
      });
    },
    loadAvailability() {
      return __async(this, null, function* () {
        try {
          const res = yield fetch(`/api/equipment/${this.equipmentId}/availability?month=${this.currentMonth + 1}&year=${this.currentYear}`);
          const data2 = yield res.json();
          if (data2.success) {
            this.bookedDates = data2.booked_dates || [];
          } else {
            this.bookedDates = [];
          }
        } catch (e) {
          this.bookedDates = [];
        }
        this.buildCalendar();
      });
    },
    buildCalendar() {
      const days = [];
      const firstDay = new Date(this.currentYear, this.currentMonth, 1);
      const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
      const startOffset = firstDay.getDay();
      for (let i = 0; i < startOffset; i++) {
        days.push({ empty: true });
      }
      for (let d = 1; d <= lastDay.getDate(); d++) {
        const date = new Date(this.currentYear, this.currentMonth, d);
        const dateStr = date.toISOString().split("T")[0];
        const today = /* @__PURE__ */ new Date();
        today.setHours(0, 0, 0, 0);
        days.push({
          empty: false,
          date: dateStr,
          dateNum: d,
          dayOfWeek: DAYS_OF_WEEK[date.getDay()],
          isBooked: this.bookedDates.includes(dateStr),
          isPast: date <= today,
          isToday: date.getTime() === today.getTime()
        });
      }
      this.calendarDays = days;
    },
    dayClass(day) {
      if (day.empty) return "";
      if (day.isBooked || day.isPast) return "bg-danger text-white";
      return "bg-success text-white";
    },
    selectDay(day) {
      if (day.empty || day.isBooked || day.isPast) return;
      if (!this.form.start_date || this.form.start_date && this.form.end_date) {
        this.form.start_date = day.date;
        this.form.end_date = "";
      } else {
        if (day.date >= this.form.start_date) {
          this.form.end_date = day.date;
        } else {
          this.form.start_date = day.date;
        }
      }
      this.recalculatePrice();
    },
    onDateChange() {
      this.recalculatePrice();
    },
    prevMonth() {
      if (this.currentMonth === 0) {
        this.currentMonth = 11;
        this.currentYear--;
      } else {
        this.currentMonth--;
      }
      this.loadAvailability();
    },
    nextMonth() {
      if (this.currentMonth === 11) {
        this.currentMonth = 0;
        this.currentYear++;
      } else {
        this.currentMonth++;
      }
      this.loadAvailability();
    },
    recalculatePrice() {
      return __async(this, null, function* () {
        if (!this.form.start_date || !this.form.end_date) return;
        try {
          const params = new URLSearchParams({
            start_date: this.form.start_date,
            end_date: this.form.end_date,
            shifts_per_day: this.form.shifts_per_day || 1,
            hours_per_shift: this.form.hours_per_shift || 8,
            quantity: this.form.quantity || 1
          });
          const res = yield fetch(`/api/equipment/${this.equipmentId}/price?` + params.toString());
          const data2 = yield res.json();
          if (data2.success) {
            this.priceData = data2;
          }
        } catch (e) {
          console.error("Price recalculation error:", e);
        }
      });
    },
    addToCart() {
      return __async(this, null, function* () {
        if (!this.form.start_date || !this.form.end_date) {
          this.error = "Выберите даты аренды";
          return;
        }
        if (!this.priceData || !this.priceData.is_available) {
          this.error = "Техника недоступна на выбранные даты";
          return;
        }
        this.adding = true;
        this.error = null;
        try {
          const res = yield fetch("/api/cart", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": window.csrfToken },
            body: JSON.stringify({
              equipment_id: this.equipmentId,
              start_date: this.form.start_date,
              end_date: this.form.end_date,
              shifts_per_day: this.form.shifts_per_day,
              hours_per_shift: this.form.hours_per_shift,
              quantity: this.form.quantity,
              address: this.form.address
            })
          });
          const data2 = yield res.json();
          if (data2.success) {
            if (window.cartBus) {
              window.cartBus.emit("cart-updated", data2);
            }
            alert("Техника добавлена в корзину!");
          } else {
            this.error = data2.error || "Ошибка добавления в корзину";
          }
        } catch (e) {
          console.error("Add to cart error:", e);
          this.error = "Ошибка соединения с сервером";
        }
        this.adding = false;
      });
    }
  },
  mounted() {
    this.loadEquipment();
  }
};
const _hoisted_1 = { class: "container-fluid" };
const _hoisted_2 = {
  "aria-label": "breadcrumb",
  class: "mb-3"
};
const _hoisted_3 = { class: "breadcrumb" };
const _hoisted_4 = { class: "breadcrumb-item active" };
const _hoisted_5 = { class: "row g-4" };
const _hoisted_6 = { class: "col-lg-6" };
const _hoisted_7 = { class: "card shadow-sm" };
const _hoisted_8 = { class: "card-body p-2" };
const _hoisted_9 = {
  id: "detailCarousel",
  class: "carousel slide",
  "data-bs-ride": "carousel"
};
const _hoisted_10 = { class: "carousel-inner" };
const _hoisted_11 = ["src", "alt"];
const _hoisted_12 = {
  key: 0,
  class: "carousel-item active"
};
const _hoisted_13 = {
  key: 0,
  class: "carousel-control-prev",
  type: "button",
  "data-bs-target": "#detailCarousel",
  "data-bs-slide": "prev"
};
const _hoisted_14 = {
  key: 1,
  class: "carousel-control-next",
  type: "button",
  "data-bs-target": "#detailCarousel",
  "data-bs-slide": "next"
};
const _hoisted_15 = { class: "col-lg-6" };
const _hoisted_16 = { class: "card shadow-sm" };
const _hoisted_17 = { class: "card-body" };
const _hoisted_18 = { class: "d-flex justify-content-between align-items-start mb-2" };
const _hoisted_19 = { class: "mb-1" };
const _hoisted_20 = { class: "text-muted mb-0" };
const _hoisted_21 = {
  key: 0,
  class: "badge bg-primary fs-6"
};
const _hoisted_22 = {
  key: 0,
  class: "mb-2"
};
const _hoisted_23 = { class: "small text-muted ms-1" };
const _hoisted_24 = { class: "mb-3" };
const _hoisted_25 = { class: "d-flex align-items-baseline gap-2" };
const _hoisted_26 = { class: "display-6 fw-bold text-primary" };
const _hoisted_27 = { class: "row g-2 mb-3" };
const _hoisted_28 = { class: "col-6" };
const _hoisted_29 = { class: "bg-light rounded p-2 text-center" };
const _hoisted_30 = { class: "col-6" };
const _hoisted_31 = { class: "bg-light rounded p-2 text-center" };
const _hoisted_32 = { class: "col-6" };
const _hoisted_33 = { class: "bg-light rounded p-2 text-center" };
const _hoisted_34 = { class: "col-6" };
const _hoisted_35 = { class: "bg-light rounded p-2 text-center" };
const _hoisted_36 = { class: "card bg-light mb-3" };
const _hoisted_37 = { class: "card-body" };
const _hoisted_38 = { class: "mb-2" };
const _hoisted_39 = { class: "row g-1" };
const _hoisted_40 = ["onClick"];
const _hoisted_41 = { class: "small fw-bold" };
const _hoisted_42 = { class: "small" };
const _hoisted_43 = { class: "d-flex justify-content-between mb-2" };
const _hoisted_44 = { class: "card bg-light mb-3" };
const _hoisted_45 = { class: "card-body" };
const _hoisted_46 = { class: "row g-2 mb-2" };
const _hoisted_47 = { class: "col-6" };
const _hoisted_48 = ["min"];
const _hoisted_49 = { class: "col-6" };
const _hoisted_50 = ["min"];
const _hoisted_51 = { class: "row g-2 mb-2" };
const _hoisted_52 = { class: "col-6" };
const _hoisted_53 = { class: "col-6" };
const _hoisted_54 = ["value"];
const _hoisted_55 = { class: "mb-2" };
const _hoisted_56 = {
  key: 1,
  class: "card border-primary mb-3"
};
const _hoisted_57 = { class: "card-body py-2" };
const _hoisted_58 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_59 = { class: "small text-muted" };
const _hoisted_60 = { class: "text-end" };
const _hoisted_61 = { class: "fs-4 fw-bold text-success" };
const _hoisted_62 = {
  key: 0,
  class: "alert alert-warning mt-2 mb-0 py-1 small"
};
const _hoisted_63 = {
  key: 2,
  class: "alert alert-danger py-2 small"
};
const _hoisted_64 = { class: "d-grid gap-2" };
const _hoisted_65 = ["disabled"];
const _hoisted_66 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1"
};
const _hoisted_67 = {
  key: 1,
  class: "bi bi-cart-plus me-1"
};
const _hoisted_68 = {
  key: 0,
  class: "card shadow-sm mt-4"
};
const _hoisted_69 = { class: "card-body" };
const _hoisted_70 = { class: "mb-0" };
const _hoisted_71 = {
  key: 1,
  class: "card shadow-sm mt-4"
};
const _hoisted_72 = { class: "card-body" };
const _hoisted_73 = { class: "table table-sm" };
const _hoisted_74 = {
  class: "text-muted",
  style: { "width": "40%" }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _a;
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("nav", _hoisted_2, [
      createBaseVNode("ol", _hoisted_3, [
        _cache[12] || (_cache[12] = createBaseVNode("li", { class: "breadcrumb-item" }, [
          createBaseVNode("a", { href: "/catalog" }, "Каталог")
        ], -1)),
        createBaseVNode("li", _hoisted_4, toDisplayString($data.equipment.brand) + " " + toDisplayString($data.equipment.model), 1)
      ])
    ]),
    createBaseVNode("div", _hoisted_5, [
      createBaseVNode("div", _hoisted_6, [
        createBaseVNode("div", _hoisted_7, [
          createBaseVNode("div", _hoisted_8, [
            createBaseVNode("div", _hoisted_9, [
              createBaseVNode("div", _hoisted_10, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.equipment.images, (img, key) => {
                  return openBlock(), createElementBlock("div", {
                    key,
                    class: normalizeClass(["carousel-item", { active: key === 0 }])
                  }, [
                    createBaseVNode("img", {
                      src: img,
                      class: "d-block w-100 rounded",
                      alt: $data.equipment.title,
                      style: { "height": "400px", "object-fit": "cover" }
                    }, null, 8, _hoisted_11)
                  ], 2);
                }), 128)),
                !$data.equipment.images || $data.equipment.images.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_12, [..._cache[13] || (_cache[13] = [
                  createBaseVNode("div", {
                    class: "bg-light d-flex align-items-center justify-content-center rounded",
                    style: { "height": "400px" }
                  }, [
                    createBaseVNode("i", { class: "bi bi-image display-1 text-muted" })
                  ], -1)
                ])])) : createCommentVNode("", true)
              ]),
              $data.equipment.images && $data.equipment.images.length > 1 ? (openBlock(), createElementBlock("button", _hoisted_13, [..._cache[14] || (_cache[14] = [
                createBaseVNode("span", { class: "carousel-control-prev-icon bg-dark rounded-circle" }, null, -1)
              ])])) : createCommentVNode("", true),
              $data.equipment.images && $data.equipment.images.length > 1 ? (openBlock(), createElementBlock("button", _hoisted_14, [..._cache[15] || (_cache[15] = [
                createBaseVNode("span", { class: "carousel-control-next-icon bg-dark rounded-circle" }, null, -1)
              ])])) : createCommentVNode("", true)
            ])
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_15, [
        createBaseVNode("div", _hoisted_16, [
          createBaseVNode("div", _hoisted_17, [
            createBaseVNode("div", _hoisted_18, [
              createBaseVNode("div", null, [
                createBaseVNode("h2", _hoisted_19, toDisplayString($data.equipment.brand) + " " + toDisplayString($data.equipment.model), 1),
                createBaseVNode("p", _hoisted_20, toDisplayString($data.equipment.title), 1)
              ]),
              $data.equipment.is_platform_owned ? (openBlock(), createElementBlock("span", _hoisted_21, [..._cache[16] || (_cache[16] = [
                createBaseVNode("i", { class: "bi bi-building-gear" }, null, -1),
                createTextVNode(" Техника платформы", -1)
              ])])) : createCommentVNode("", true)
            ]),
            $data.equipment.rating > 0 ? (openBlock(), createElementBlock("div", _hoisted_22, [
              (openBlock(), createElementBlock(Fragment, null, renderList(5, (i) => {
                return createBaseVNode("i", {
                  key: i,
                  class: normalizeClass(["bi", i <= Math.round($data.equipment.rating) ? "bi-star-fill" : i - 0.5 <= $data.equipment.rating ? "bi-star-half" : "bi-star"]),
                  style: { "color": "#ffc107" }
                }, null, 2);
              }), 64)),
              createBaseVNode("span", _hoisted_23, toDisplayString($data.equipment.rating), 1)
            ])) : createCommentVNode("", true),
            createBaseVNode("div", _hoisted_24, [
              createBaseVNode("div", _hoisted_25, [
                createBaseVNode("span", _hoisted_26, toDisplayString($data.priceData ? $data.priceData.final_price_per_hour : $data.equipment.final_price) + " ₽", 1),
                _cache[17] || (_cache[17] = createBaseVNode("span", { class: "text-muted" }, "/ час", -1))
              ])
            ]),
            _cache[36] || (_cache[36] = createBaseVNode("hr", null, null, -1)),
            createBaseVNode("div", _hoisted_27, [
              createBaseVNode("div", _hoisted_28, [
                createBaseVNode("div", _hoisted_29, [
                  _cache[18] || (_cache[18] = createBaseVNode("small", { class: "text-muted d-block" }, "Год выпуска", -1)),
                  createBaseVNode("strong", null, toDisplayString($data.equipment.year), 1)
                ])
              ]),
              createBaseVNode("div", _hoisted_30, [
                createBaseVNode("div", _hoisted_31, [
                  _cache[19] || (_cache[19] = createBaseVNode("small", { class: "text-muted d-block" }, "Наработка", -1)),
                  createBaseVNode("strong", null, toDisplayString($data.equipment.hours_worked) + " ч", 1)
                ])
              ]),
              createBaseVNode("div", _hoisted_32, [
                createBaseVNode("div", _hoisted_33, [
                  _cache[20] || (_cache[20] = createBaseVNode("small", { class: "text-muted d-block" }, "Габариты", -1)),
                  createBaseVNode("strong", null, toDisplayString($data.equipment.dimensions), 1)
                ])
              ]),
              createBaseVNode("div", _hoisted_34, [
                createBaseVNode("div", _hoisted_35, [
                  _cache[21] || (_cache[21] = createBaseVNode("small", { class: "text-muted d-block" }, "Локация", -1)),
                  createBaseVNode("strong", null, toDisplayString($data.equipment.location), 1)
                ])
              ])
            ]),
            createBaseVNode("div", _hoisted_36, [
              createBaseVNode("div", _hoisted_37, [
                _cache[25] || (_cache[25] = createBaseVNode("h6", { class: "fw-bold mb-3" }, [
                  createBaseVNode("i", { class: "bi bi-calendar-check me-2" }),
                  createTextVNode("Календарь доступности")
                ], -1)),
                createBaseVNode("div", _hoisted_38, [
                  _cache[22] || (_cache[22] = createBaseVNode("div", { class: "d-flex gap-1 small mb-2" }, [
                    createBaseVNode("span", { class: "badge bg-success" }, "Свободно"),
                    createBaseVNode("span", { class: "badge bg-danger" }, "Занято")
                  ], -1)),
                  createBaseVNode("div", _hoisted_39, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList($data.calendarDays, (day, idx) => {
                      return openBlock(), createElementBlock("div", {
                        key: idx,
                        class: "col",
                        style: { "min-width": "14%" }
                      }, [
                        createBaseVNode("div", {
                          class: normalizeClass(["text-center p-1 rounded", $options.dayClass(day)]),
                          onClick: ($event) => $options.selectDay(day)
                        }, [
                          createBaseVNode("div", _hoisted_41, toDisplayString(day.dayOfWeek), 1),
                          createBaseVNode("div", _hoisted_42, toDisplayString(day.dateNum), 1)
                        ], 10, _hoisted_40)
                      ]);
                    }), 128))
                  ])
                ]),
                createBaseVNode("div", _hoisted_43, [
                  createBaseVNode("button", {
                    class: "btn btn-sm btn-outline-secondary",
                    onClick: _cache[0] || (_cache[0] = (...args) => $options.prevMonth && $options.prevMonth(...args))
                  }, [..._cache[23] || (_cache[23] = [
                    createBaseVNode("i", { class: "bi bi-chevron-left" }, null, -1)
                  ])]),
                  createBaseVNode("strong", null, toDisplayString($options.currentMonthName) + " " + toDisplayString($data.currentYear), 1),
                  createBaseVNode("button", {
                    class: "btn btn-sm btn-outline-secondary",
                    onClick: _cache[1] || (_cache[1] = (...args) => $options.nextMonth && $options.nextMonth(...args))
                  }, [..._cache[24] || (_cache[24] = [
                    createBaseVNode("i", { class: "bi bi-chevron-right" }, null, -1)
                  ])])
                ])
              ])
            ]),
            createBaseVNode("div", _hoisted_44, [
              createBaseVNode("div", _hoisted_45, [
                _cache[32] || (_cache[32] = createBaseVNode("h6", { class: "fw-bold mb-3" }, [
                  createBaseVNode("i", { class: "bi bi-gear me-2" }),
                  createTextVNode("Параметры аренды")
                ], -1)),
                createBaseVNode("div", _hoisted_46, [
                  createBaseVNode("div", _hoisted_47, [
                    _cache[26] || (_cache[26] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Дата начала", -1)),
                    withDirectives(createBaseVNode("input", {
                      type: "date",
                      class: "form-control",
                      "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.form.start_date = $event),
                      min: $options.minDate,
                      onChange: _cache[3] || (_cache[3] = (...args) => $options.onDateChange && $options.onDateChange(...args))
                    }, null, 40, _hoisted_48), [
                      [vModelText, $data.form.start_date]
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_49, [
                    _cache[27] || (_cache[27] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Дата окончания", -1)),
                    withDirectives(createBaseVNode("input", {
                      type: "date",
                      class: "form-control",
                      "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.form.end_date = $event),
                      min: $data.form.start_date || $options.minDate,
                      onChange: _cache[5] || (_cache[5] = (...args) => $options.onDateChange && $options.onDateChange(...args))
                    }, null, 40, _hoisted_50), [
                      [vModelText, $data.form.end_date]
                    ])
                  ])
                ]),
                createBaseVNode("div", _hoisted_51, [
                  createBaseVNode("div", _hoisted_52, [
                    _cache[29] || (_cache[29] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Смен в сутки", -1)),
                    withDirectives(createBaseVNode("select", {
                      class: "form-select",
                      "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.form.shifts_per_day = $event),
                      onChange: _cache[7] || (_cache[7] = (...args) => $options.recalculatePrice && $options.recalculatePrice(...args))
                    }, [..._cache[28] || (_cache[28] = [
                      createBaseVNode("option", { value: 1 }, "1 смена", -1),
                      createBaseVNode("option", { value: 2 }, "2 смены", -1)
                    ])], 544), [
                      [
                        vModelSelect,
                        $data.form.shifts_per_day,
                        void 0,
                        { number: true }
                      ]
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_53, [
                    _cache[30] || (_cache[30] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Часов в смене", -1)),
                    withDirectives(createBaseVNode("select", {
                      class: "form-select",
                      "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.form.hours_per_shift = $event),
                      onChange: _cache[9] || (_cache[9] = (...args) => $options.recalculatePrice && $options.recalculatePrice(...args))
                    }, [
                      (openBlock(), createElementBlock(Fragment, null, renderList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], (h) => {
                        return createBaseVNode("option", {
                          key: h,
                          value: h
                        }, toDisplayString(h) + " ч", 9, _hoisted_54);
                      }), 64))
                    ], 544), [
                      [
                        vModelSelect,
                        $data.form.hours_per_shift,
                        void 0,
                        { number: true }
                      ]
                    ])
                  ])
                ]),
                createBaseVNode("div", _hoisted_55, [
                  _cache[31] || (_cache[31] = createBaseVNode("label", { class: "form-label small fw-semibold" }, "Адрес доставки (опционально)", -1)),
                  withDirectives(createBaseVNode("input", {
                    type: "text",
                    class: "form-control",
                    "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => $data.form.address = $event),
                    placeholder: "г. Москва, ул. Строителей, д. 10"
                  }, null, 512), [
                    [vModelText, $data.form.address]
                  ])
                ])
              ])
            ]),
            $data.priceData ? (openBlock(), createElementBlock("div", _hoisted_56, [
              createBaseVNode("div", _hoisted_57, [
                createBaseVNode("div", _hoisted_58, [
                  createBaseVNode("div", _hoisted_59, toDisplayString($data.priceData.days) + " дн. × " + toDisplayString($data.priceData.total_hours) + " ч", 1),
                  createBaseVNode("div", _hoisted_60, [
                    _cache[33] || (_cache[33] = createBaseVNode("div", { class: "small text-muted" }, "Итого", -1)),
                    createBaseVNode("div", _hoisted_61, toDisplayString($data.priceData.total_final) + " ₽", 1)
                  ])
                ]),
                !$data.priceData.is_available ? (openBlock(), createElementBlock("div", _hoisted_62, [..._cache[34] || (_cache[34] = [
                  createBaseVNode("i", { class: "bi bi-exclamation-triangle me-1" }, null, -1),
                  createTextVNode(" Техника недоступна на выбранные даты ", -1)
                ])])) : createCommentVNode("", true)
              ])
            ])) : createCommentVNode("", true),
            $data.error ? (openBlock(), createElementBlock("div", _hoisted_63, toDisplayString($data.error), 1)) : createCommentVNode("", true),
            createBaseVNode("div", _hoisted_64, [
              createBaseVNode("button", {
                class: "btn btn-primary btn-lg",
                onClick: _cache[11] || (_cache[11] = (...args) => $options.addToCart && $options.addToCart(...args)),
                disabled: $data.adding || !((_a = $data.priceData) == null ? void 0 : _a.is_available)
              }, [
                $data.adding ? (openBlock(), createElementBlock("span", _hoisted_66)) : (openBlock(), createElementBlock("i", _hoisted_67)),
                createTextVNode(" " + toDisplayString($data.adding ? "Добавление..." : "Добавить в корзину"), 1)
              ], 8, _hoisted_65),
              _cache[35] || (_cache[35] = createBaseVNode("a", {
                href: "/catalog",
                class: "btn btn-outline-secondary"
              }, [
                createBaseVNode("i", { class: "bi bi-arrow-left" }),
                createTextVNode(" Вернуться в каталог ")
              ], -1))
            ])
          ])
        ])
      ])
    ]),
    $data.equipment.description ? (openBlock(), createElementBlock("div", _hoisted_68, [
      _cache[37] || (_cache[37] = createBaseVNode("div", { class: "card-header bg-white" }, [
        createBaseVNode("h5", { class: "mb-0" }, "Описание")
      ], -1)),
      createBaseVNode("div", _hoisted_69, [
        createBaseVNode("p", _hoisted_70, toDisplayString($data.equipment.description), 1)
      ])
    ])) : createCommentVNode("", true),
    $data.equipment.specifications && $data.equipment.specifications.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_71, [
      _cache[38] || (_cache[38] = createBaseVNode("div", { class: "card-header bg-white" }, [
        createBaseVNode("h5", { class: "mb-0" }, "Характеристики")
      ], -1)),
      createBaseVNode("div", _hoisted_72, [
        createBaseVNode("table", _hoisted_73, [
          createBaseVNode("tbody", null, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.equipment.specifications, (spec) => {
              return openBlock(), createElementBlock("tr", {
                key: spec.key
              }, [
                createBaseVNode("th", _hoisted_74, toDisplayString(spec.key), 1),
                createBaseVNode("td", null, toDisplayString(spec.value), 1)
              ]);
            }), 128))
          ])
        ])
      ])
    ])) : createCommentVNode("", true)
  ]);
}
const CatalogDetail = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
const CartIcon = {
  data() {
    return { count: 0, items: [], proposalItems: [], total: 0 };
  },
  methods: {
    loadCart() {
      return __async(this, null, function* () {
        try {
          const res = yield fetch("/api/cart");
          const data2 = yield res.json();
          this.count = data2.count || 0;
          this.items = (data2.items || []).map((item) => {
            const base = parseFloat(item.base_price) || 0;
            const fee = parseFloat(item.platform_fee) || 0;
            const period = parseInt(item.period_count) || 1;
            const qty = parseInt(item.quantity) || 1;
            item.total_price = parseFloat(item.total_price) || (base + fee) * period * qty;
            return item;
          });
          this.total = data2.total || this.items.reduce((s, i) => s + (i.total_price || 0), 0);
          const totalDelivery = this.items.reduce((s, i) => s + (parseFloat(i.delivery_cost) || 0), 0);
          this.total += totalDelivery;
          try {
            const res2 = yield fetch("/api/proposal-cart");
            const data22 = yield res2.json();
            this.proposalItems = (data22.items || []).map((item) => {
              item.total_price = parseFloat(item.total_price) || 0;
              return item;
            });
            this.count += data22.count || 0;
          } catch (e) {
          }
        } catch (e) {
          console.error(e);
        }
      });
    },
    openPanel() {
      document.getElementById("cartPanel").classList.add("show");
      document.body.classList.add("modal-open");
    },
    closePanel() {
      document.getElementById("cartPanel").classList.remove("show");
      document.body.classList.remove("modal-open");
    },
    removeItem(id) {
      fetch("/api/cart/" + id, { method: "DELETE", headers: { "X-CSRF-TOKEN": window.csrfToken } }).then((r) => r.json()).then((d) => {
        if (d.success) this.loadCart();
      });
    },
    removeProposalItem(id) {
      fetch("/api/proposal-cart/" + id, { method: "DELETE", headers: { "X-CSRF-TOKEN": window.csrfToken } }).then((r) => r.json()).then((d) => {
        if (d.success) this.loadCart();
      });
    },
    cleanupBrokenItems() {
      if (!confirm("Удалить ВСЕ позиции из корзины?")) return;
      const promises = this.items.map(
        (item) => fetch("/api/cart/" + item.id, { method: "DELETE", headers: { "X-CSRF-TOKEN": window.csrfToken } })
      );
      Promise.all(promises).then(() => {
        alert("Корзина очищена");
        this.loadCart();
      });
    },
    formatPrice(val) {
      return Number(val || 0).toLocaleString("ru-RU", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
  },
  mounted() {
    this.loadCart();
    if (window.cartBus) {
      window.cartBus.on("cart-updated", () => {
        this.loadCart();
      });
    }
    document.addEventListener("cart-refresh", () => {
      this.loadCart();
    });
  },
  template: `
    <div>
        <button class="btn btn-primary rounded-circle position-fixed shadow cart-icon" @click="openPanel" style="bottom:20px;right:20px;width:60px;height:60px;z-index:9999;">
            <i class="bi bi-cart fs-4"></i>
            <span v-if="count > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ count }}</span>
        </button>
        <div class="offcanvas offcanvas-end" id="cartPanel" tabindex="-1" style="z-index:10060;">
            <div class="offcanvas-header">
                <h5>Корзина <small v-if="total > 0" class="text-muted fs-6">({{ formatPrice(total) }} ₽)</small></h5>
                <button type="button" class="btn-close" @click="closePanel"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#cartItems">Техника</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#proposalItems">Заявки</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="cartItems">
                        <div v-for="item in items" :key="item.id" class="card mb-2">
                            <div class="card-body p-2">
                                <div class="d-flex gap-2">
                                    <img :src="item.equipment?.main_image_url || (item.equipment?.mainImage?.path ? '/storage/' + item.equipment.mainImage.path : '/images/no-image.svg')" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                                    <div class="flex-grow-1 small">
                                        <strong>{{ item.equipment?.title || item.equipment?.brand + ' ' + item.equipment?.model || 'Техника' }}</strong><br>
                                        <span v-if="item.start_date">{{ item.start_date }} — {{ item.end_date }}</span><br>
                                        <span v-if="item.hours_per_shift">{{ item.shifts_per_day }} см. × {{ item.hours_per_shift }} ч</span>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span class="text-muted">{{ formatPrice(item.base_price) }} ₽/ч</span>
                                            <span class="fw-bold text-primary">{{ formatPrice(item.total_price) }} ₽</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger" @click="removeItem(item.id)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <div v-if="items.length === 0" class="text-muted small text-center py-3">Корзина пуста</div>
                        <div v-if="items.length > 0">
                            <div v-for="item in items" :key="item.id">
                                <div v-if="item.delivery_cost > 0 && item.address && item.address.trim() !== ''" class="small border-top pt-1 mt-1" :title="''">
                                    <div class="small text-muted">
                                        <i class="bi bi-truck me-1"></i><strong>Доставка:</strong>
                                        <span class="fw-bold text-primary ms-1">{{ formatPrice(item.delivery_cost) }} ₽</span>
                                    </div>
                                    <div v-if="item.equipment?.location_name" class="small text-muted ms-1" :title="''">
                                        📍 Откуда: {{ item.equipment.location_name }}
                                    </div>
                                    <div v-if="item.address" class="small text-muted ms-1" :title="''">
                                        📍 Куда: {{ item.address }}
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-outline-danger btn-sm w-100 mt-2" @click="cleanupBrokenItems">
                                <i class="bi bi-trash3 me-1"></i>Очистить корзину полностью
                            </button>
                            <a href="/checkout" class="btn btn-primary w-100 mt-2">Перейти к оформлению</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="proposalItems">
                        <div v-for="item in proposalItems" :key="item.id" class="card mb-2">
                            <div class="card-body p-2 small">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ item.equipment?.title || 'Предложение' }}</strong><br>
                                        Цена: <span class="text-primary fw-bold">{{ formatPrice(item.total_price) }} ₽</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger" @click="removeProposalItem(item.id)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <div v-if="proposalItems.length === 0" class="text-muted small text-center py-3">Нет предложений</div>
                        <a v-if="proposalItems.length > 0" href="/checkout" class="btn btn-primary w-100 mt-3">Перейти к оформлению</a>
                    </div>
                </div>
            </div>
        </div>
    </div>`
};
function initRipple() {
  document.addEventListener("click", function(e) {
    const rippleBtn = e.target.closest(".ripple");
    if (!rippleBtn) return;
    const rect = rippleBtn.getBoundingClientRect();
    const size2 = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size2 / 2;
    const y = e.clientY - rect.top - size2 / 2;
    const ripple = document.createElement("span");
    ripple.className = "ripple-effect";
    ripple.style.width = `${size2}px`;
    ripple.style.height = `${size2}px`;
    ripple.style.left = `${x}px`;
    ripple.style.top = `${y}px`;
    rippleBtn.appendChild(ripple);
    setTimeout(() => {
      ripple.remove();
    }, 600);
  });
}
function initTheme() {
  const themeToggles = document.querySelectorAll("[data-theme-toggle]");
  const html = document.documentElement;
  const lightTheme = {
    "--primary-500": "#0b5ed7",
    "--primary-600": "#0a50b9",
    "--accent-400": "#00d2ff",
    "--text-primary": "#212529",
    "--text-secondary": "#495057",
    "--bg-surface": "#ffffff",
    "--bg-secondary": "#f8f9fa",
    "--divider": "#e9ecef",
    "--bg-gradient": "linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)"
  };
  const darkTheme = {
    "--primary-500": "#4D9DFF",
    "--primary-600": "#2B8CFF",
    "--accent-400": "#00F0FF",
    "--text-primary": "#F5F9FF",
    "--text-secondary": "#E2E8F0",
    "--bg-surface": "#1E293B",
    "--bg-secondary": "#0F172A",
    "--divider": "#475569",
    "--bg-gradient": "linear-gradient(135deg, #1a1c23 0%, #232630 50%, #1a1c23 100%)"
  };
  function applyTheme(theme) {
    const html2 = document.documentElement;
    html2.style.transition = "background-color 0.3s ease, color 0.3s ease";
    const themeVars = theme === "dark" ? darkTheme : lightTheme;
    Object.entries(themeVars).forEach(([key, value]) => {
      document.documentElement.style.setProperty(key, value);
    });
    html2.setAttribute("data-theme", theme);
    localStorage.setItem("theme", theme);
    updateIcons(theme);
    setTimeout(() => {
      html2.style.transition = "";
    }, 300);
  }
  function updateIcons(theme) {
    themeToggles.forEach((toggle) => {
      const icon = toggle.querySelector("i");
      if (!icon) return;
      if (theme === "dark") {
        icon.classList.remove("bi-sun-fill");
        icon.classList.add("bi-moon-fill");
      } else {
        icon.classList.remove("bi-moon-fill");
        icon.classList.add("bi-sun-fill");
      }
    });
  }
  function getInitialTheme() {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) return savedTheme;
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    if (prefersDark) return "dark";
    return "light";
  }
  const initialTheme = getInitialTheme();
  applyTheme(initialTheme);
  themeToggles.forEach((toggle) => {
    toggle.addEventListener("click", () => {
      const currentTheme = html.getAttribute("data-theme");
      const newTheme = currentTheme === "light" ? "dark" : "light";
      applyTheme(newTheme);
    });
  });
  window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (e) => {
    if (!localStorage.getItem("theme")) {
      applyTheme(e.matches ? "dark" : "light");
    }
  });
}
function initSmartNavbar() {
  const navbar = document.querySelector(".navbar");
  if (!navbar) return;
  const isMobile = window.innerWidth < 992;
  if (isMobile) {
    navbar.classList.remove("navbar--hidden", "navbar--scrolled");
    document.documentElement.style.setProperty("--navbar-height", `${navbar.offsetHeight}px`);
    return;
  }
  let lastScrollY = window.scrollY;
  let ticking = false;
  const navbarHeight = navbar.offsetHeight;
  let isDropdownOpen = false;
  document.addEventListener("show.bs.dropdown", () => {
    isDropdownOpen = true;
    navbar.classList.remove("navbar--hidden");
    document.documentElement.style.setProperty("--navbar-height", `${navbarHeight}px`);
  });
  document.addEventListener("hide.bs.dropdown", () => {
    isDropdownOpen = false;
  });
  const updateNavbarState = () => {
    if (isDropdownOpen) return;
    const scrollY = window.scrollY;
    if (scrollY <= 50) {
      navbar.classList.remove("navbar--hidden", "navbar--scrolled");
      document.documentElement.style.setProperty("--navbar-height", `${navbarHeight}px`);
      lastScrollY = scrollY;
      ticking = false;
      return;
    }
    if (scrollY > lastScrollY && scrollY > 100) {
      navbar.classList.add("navbar--hidden");
      document.documentElement.style.setProperty("--navbar-height", "0px");
    } else if (scrollY < lastScrollY) {
      navbar.classList.remove("navbar--hidden");
      document.documentElement.style.setProperty("--navbar-height", `${navbarHeight}px`);
    }
    if (scrollY > 50) {
      navbar.classList.add("navbar--scrolled");
    } else {
      navbar.classList.remove("navbar--scrolled");
    }
    lastScrollY = scrollY;
    ticking = false;
  };
  const onScroll = () => {
    if (!ticking && !isDropdownOpen) {
      window.requestAnimationFrame(updateNavbarState);
      ticking = true;
    }
  };
  window.addEventListener("scroll", onScroll);
  window.addEventListener("scroll", () => {
    if (window.scrollY === 0) {
      navbar.classList.remove("navbar--hidden", "navbar--scrolled");
      document.documentElement.style.setProperty("--navbar-height", `${navbarHeight}px`);
    }
  });
  window.addEventListener("resize", () => {
    if (!navbar.classList.contains("navbar--hidden")) {
      document.documentElement.style.setProperty("--navbar-height", `${navbar.offsetHeight}px`);
    }
  });
  updateNavbarState();
}
Chart.register(...registerables);
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
window.axios = axios;
var flushPending = false;
var flushing = false;
var queue = [];
var lastFlushedIndex = -1;
function scheduler(callback) {
  queueJob(callback);
}
function queueJob(job) {
  if (!queue.includes(job))
    queue.push(job);
  queueFlush();
}
function dequeueJob(job) {
  let index = queue.indexOf(job);
  if (index !== -1 && index > lastFlushedIndex)
    queue.splice(index, 1);
}
function queueFlush() {
  if (!flushing && !flushPending) {
    flushPending = true;
    queueMicrotask(flushJobs);
  }
}
function flushJobs() {
  flushPending = false;
  flushing = true;
  for (let i = 0; i < queue.length; i++) {
    queue[i]();
    lastFlushedIndex = i;
  }
  queue.length = 0;
  lastFlushedIndex = -1;
  flushing = false;
}
var reactive;
var effect;
var release;
var raw;
var shouldSchedule = true;
function disableEffectScheduling(callback) {
  shouldSchedule = false;
  callback();
  shouldSchedule = true;
}
function setReactivityEngine(engine) {
  reactive = engine.reactive;
  release = engine.release;
  effect = (callback) => engine.effect(callback, { scheduler: (task) => {
    if (shouldSchedule) {
      scheduler(task);
    } else {
      task();
    }
  } });
  raw = engine.raw;
}
function overrideEffect(override) {
  effect = override;
}
function elementBoundEffect(el) {
  let cleanup2 = () => {
  };
  let wrappedEffect = (callback) => {
    let effectReference = effect(callback);
    if (!el._x_effects) {
      el._x_effects = /* @__PURE__ */ new Set();
      el._x_runEffects = () => {
        el._x_effects.forEach((i) => i());
      };
    }
    el._x_effects.add(effectReference);
    cleanup2 = () => {
      if (effectReference === void 0)
        return;
      el._x_effects.delete(effectReference);
      release(effectReference);
    };
    return effectReference;
  };
  return [wrappedEffect, () => {
    cleanup2();
  }];
}
function watch(getter, callback) {
  let firstTime = true;
  let oldValue;
  let effectReference = effect(() => {
    let value = getter();
    JSON.stringify(value);
    if (!firstTime) {
      queueMicrotask(() => {
        callback(value, oldValue);
        oldValue = value;
      });
    } else {
      oldValue = value;
    }
    firstTime = false;
  });
  return () => release(effectReference);
}
var onAttributeAddeds = [];
var onElRemoveds = [];
var onElAddeds = [];
function onElAdded(callback) {
  onElAddeds.push(callback);
}
function onElRemoved(el, callback) {
  if (typeof callback === "function") {
    if (!el._x_cleanups)
      el._x_cleanups = [];
    el._x_cleanups.push(callback);
  } else {
    callback = el;
    onElRemoveds.push(callback);
  }
}
function onAttributesAdded(callback) {
  onAttributeAddeds.push(callback);
}
function onAttributeRemoved(el, name, callback) {
  if (!el._x_attributeCleanups)
    el._x_attributeCleanups = {};
  if (!el._x_attributeCleanups[name])
    el._x_attributeCleanups[name] = [];
  el._x_attributeCleanups[name].push(callback);
}
function cleanupAttributes(el, names) {
  if (!el._x_attributeCleanups)
    return;
  Object.entries(el._x_attributeCleanups).forEach(([name, value]) => {
    if (names === void 0 || names.includes(name)) {
      value.forEach((i) => i());
      delete el._x_attributeCleanups[name];
    }
  });
}
function cleanupElement(el) {
  var _a, _b;
  (_a = el._x_effects) == null ? void 0 : _a.forEach(dequeueJob);
  while ((_b = el._x_cleanups) == null ? void 0 : _b.length)
    el._x_cleanups.pop()();
}
var observer = new MutationObserver(onMutate);
var currentlyObserving = false;
function startObservingMutations() {
  observer.observe(document, { subtree: true, childList: true, attributes: true, attributeOldValue: true });
  currentlyObserving = true;
}
function stopObservingMutations() {
  flushObserver();
  observer.disconnect();
  currentlyObserving = false;
}
var queuedMutations = [];
function flushObserver() {
  let records = observer.takeRecords();
  queuedMutations.push(() => records.length > 0 && onMutate(records));
  let queueLengthWhenTriggered = queuedMutations.length;
  queueMicrotask(() => {
    if (queuedMutations.length === queueLengthWhenTriggered) {
      while (queuedMutations.length > 0)
        queuedMutations.shift()();
    }
  });
}
function mutateDom(callback) {
  if (!currentlyObserving)
    return callback();
  stopObservingMutations();
  let result = callback();
  startObservingMutations();
  return result;
}
var isCollecting = false;
var deferredMutations = [];
function deferMutations() {
  isCollecting = true;
}
function flushAndStopDeferringMutations() {
  isCollecting = false;
  onMutate(deferredMutations);
  deferredMutations = [];
}
function onMutate(mutations) {
  if (isCollecting) {
    deferredMutations = deferredMutations.concat(mutations);
    return;
  }
  let addedNodes = [];
  let removedNodes = /* @__PURE__ */ new Set();
  let addedAttributes = /* @__PURE__ */ new Map();
  let removedAttributes = /* @__PURE__ */ new Map();
  for (let i = 0; i < mutations.length; i++) {
    if (mutations[i].target._x_ignoreMutationObserver)
      continue;
    if (mutations[i].type === "childList") {
      mutations[i].removedNodes.forEach((node) => {
        if (node.nodeType !== 1)
          return;
        if (!node._x_marker)
          return;
        removedNodes.add(node);
      });
      mutations[i].addedNodes.forEach((node) => {
        if (node.nodeType !== 1)
          return;
        if (removedNodes.has(node)) {
          removedNodes.delete(node);
          return;
        }
        if (node._x_marker)
          return;
        addedNodes.push(node);
      });
    }
    if (mutations[i].type === "attributes") {
      let el = mutations[i].target;
      let name = mutations[i].attributeName;
      let oldValue = mutations[i].oldValue;
      let add2 = () => {
        if (!addedAttributes.has(el))
          addedAttributes.set(el, []);
        addedAttributes.get(el).push({ name, value: el.getAttribute(name) });
      };
      let remove = () => {
        if (!removedAttributes.has(el))
          removedAttributes.set(el, []);
        removedAttributes.get(el).push(name);
      };
      if (el.hasAttribute(name) && oldValue === null) {
        add2();
      } else if (el.hasAttribute(name)) {
        remove();
        add2();
      } else {
        remove();
      }
    }
  }
  removedAttributes.forEach((attrs, el) => {
    cleanupAttributes(el, attrs);
  });
  addedAttributes.forEach((attrs, el) => {
    onAttributeAddeds.forEach((i) => i(el, attrs));
  });
  for (let node of removedNodes) {
    if (addedNodes.some((i) => i.contains(node)))
      continue;
    onElRemoveds.forEach((i) => i(node));
  }
  for (let node of addedNodes) {
    if (!node.isConnected)
      continue;
    onElAddeds.forEach((i) => i(node));
  }
  addedNodes = null;
  removedNodes = null;
  addedAttributes = null;
  removedAttributes = null;
}
function scope(node) {
  return mergeProxies(closestDataStack(node));
}
function addScopeToNode(node, data2, referenceNode) {
  node._x_dataStack = [data2, ...closestDataStack(referenceNode || node)];
  return () => {
    node._x_dataStack = node._x_dataStack.filter((i) => i !== data2);
  };
}
function closestDataStack(node) {
  if (node._x_dataStack)
    return node._x_dataStack;
  if (typeof ShadowRoot === "function" && node instanceof ShadowRoot) {
    return closestDataStack(node.host);
  }
  if (!node.parentNode) {
    return [];
  }
  return closestDataStack(node.parentNode);
}
function mergeProxies(objects) {
  return new Proxy({ objects }, mergeProxyTrap);
}
var mergeProxyTrap = {
  ownKeys({ objects }) {
    return Array.from(
      new Set(objects.flatMap((i) => Object.keys(i)))
    );
  },
  has({ objects }, name) {
    if (name == Symbol.unscopables)
      return false;
    return objects.some(
      (obj) => Object.prototype.hasOwnProperty.call(obj, name) || Reflect.has(obj, name)
    );
  },
  get({ objects }, name, thisProxy) {
    if (name == "toJSON")
      return collapseProxies;
    return Reflect.get(
      objects.find(
        (obj) => Reflect.has(obj, name)
      ) || {},
      name,
      thisProxy
    );
  },
  set({ objects }, name, value, thisProxy) {
    const target = objects.find(
      (obj) => Object.prototype.hasOwnProperty.call(obj, name)
    ) || objects[objects.length - 1];
    const descriptor = Object.getOwnPropertyDescriptor(target, name);
    if ((descriptor == null ? void 0 : descriptor.set) && (descriptor == null ? void 0 : descriptor.get))
      return descriptor.set.call(thisProxy, value) || true;
    return Reflect.set(target, name, value);
  }
};
function collapseProxies() {
  let keys = Reflect.ownKeys(this);
  return keys.reduce((acc, key) => {
    acc[key] = Reflect.get(this, key);
    return acc;
  }, {});
}
function initInterceptors(data2) {
  let isObject2 = (val) => typeof val === "object" && !Array.isArray(val) && val !== null;
  let recurse = (obj, basePath = "") => {
    Object.entries(Object.getOwnPropertyDescriptors(obj)).forEach(([key, { value, enumerable }]) => {
      if (enumerable === false || value === void 0)
        return;
      if (typeof value === "object" && value !== null && value.__v_skip)
        return;
      let path = basePath === "" ? key : `${basePath}.${key}`;
      if (typeof value === "object" && value !== null && value._x_interceptor) {
        obj[key] = value.initialize(data2, path, key);
      } else {
        if (isObject2(value) && value !== obj && !(value instanceof Element)) {
          recurse(value, path);
        }
      }
    });
  };
  return recurse(data2);
}
function interceptor(callback, mutateObj = () => {
}) {
  let obj = {
    initialValue: void 0,
    _x_interceptor: true,
    initialize(data2, path, key) {
      return callback(this.initialValue, () => get(data2, path), (value) => set(data2, path, value), path, key);
    }
  };
  mutateObj(obj);
  return (initialValue) => {
    if (typeof initialValue === "object" && initialValue !== null && initialValue._x_interceptor) {
      let initialize = obj.initialize.bind(obj);
      obj.initialize = (data2, path, key) => {
        let innerValue = initialValue.initialize(data2, path, key);
        obj.initialValue = innerValue;
        return initialize(data2, path, key);
      };
    } else {
      obj.initialValue = initialValue;
    }
    return obj;
  };
}
function get(obj, path) {
  return path.split(".").reduce((carry, segment) => carry[segment], obj);
}
function set(obj, path, value) {
  if (typeof path === "string")
    path = path.split(".");
  if (path.length === 1)
    obj[path[0]] = value;
  else if (path.length === 0)
    throw error;
  else {
    if (obj[path[0]])
      return set(obj[path[0]], path.slice(1), value);
    else {
      obj[path[0]] = {};
      return set(obj[path[0]], path.slice(1), value);
    }
  }
}
var magics = {};
function magic(name, callback) {
  magics[name] = callback;
}
function injectMagics(obj, el) {
  let memoizedUtilities = getUtilities(el);
  Object.entries(magics).forEach(([name, callback]) => {
    Object.defineProperty(obj, `$${name}`, {
      get() {
        return callback(el, memoizedUtilities);
      },
      enumerable: false
    });
  });
  return obj;
}
function getUtilities(el) {
  let [utilities, cleanup2] = getElementBoundUtilities(el);
  let utils = __spreadValues({ interceptor }, utilities);
  onElRemoved(el, cleanup2);
  return utils;
}
function tryCatch(el, expression, callback, ...args) {
  try {
    return callback(...args);
  } catch (e) {
    handleError(e, el, expression);
  }
}
function handleError(error2, el, expression = void 0) {
  error2 = Object.assign(
    error2 != null ? error2 : { message: "No error message given." },
    { el, expression }
  );
  console.warn(`Alpine Expression Error: ${error2.message}

${expression ? 'Expression: "' + expression + '"\n\n' : ""}`, el);
  setTimeout(() => {
    throw error2;
  }, 0);
}
var shouldAutoEvaluateFunctions = true;
function dontAutoEvaluateFunctions(callback) {
  let cache = shouldAutoEvaluateFunctions;
  shouldAutoEvaluateFunctions = false;
  let result = callback();
  shouldAutoEvaluateFunctions = cache;
  return result;
}
function evaluate(el, expression, extras = {}) {
  let result;
  evaluateLater(el, expression)((value) => result = value, extras);
  return result;
}
function evaluateLater(...args) {
  return theEvaluatorFunction(...args);
}
var theEvaluatorFunction = normalEvaluator;
function setEvaluator(newEvaluator) {
  theEvaluatorFunction = newEvaluator;
}
function normalEvaluator(el, expression) {
  let overriddenMagics = {};
  injectMagics(overriddenMagics, el);
  let dataStack = [overriddenMagics, ...closestDataStack(el)];
  let evaluator = typeof expression === "function" ? generateEvaluatorFromFunction(dataStack, expression) : generateEvaluatorFromString(dataStack, expression, el);
  return tryCatch.bind(null, el, expression, evaluator);
}
function generateEvaluatorFromFunction(dataStack, func) {
  return (receiver = () => {
  }, { scope: scope2 = {}, params = [], context } = {}) => {
    let result = func.apply(mergeProxies([scope2, ...dataStack]), params);
    runIfTypeOfFunction(receiver, result);
  };
}
var evaluatorMemo = {};
function generateFunctionFromString(expression, el) {
  if (evaluatorMemo[expression]) {
    return evaluatorMemo[expression];
  }
  let AsyncFunction = Object.getPrototypeOf(function() {
    return __async(this, null, function* () {
    });
  }).constructor;
  let rightSideSafeExpression = /^[\n\s]*if.*\(.*\)/.test(expression.trim()) || /^(let|const)\s/.test(expression.trim()) ? `(async()=>{ ${expression} })()` : expression;
  const safeAsyncFunction = () => {
    try {
      let func2 = new AsyncFunction(
        ["__self", "scope"],
        `with (scope) { __self.result = ${rightSideSafeExpression} }; __self.finished = true; return __self.result;`
      );
      Object.defineProperty(func2, "name", {
        value: `[Alpine] ${expression}`
      });
      return func2;
    } catch (error2) {
      handleError(error2, el, expression);
      return Promise.resolve();
    }
  };
  let func = safeAsyncFunction();
  evaluatorMemo[expression] = func;
  return func;
}
function generateEvaluatorFromString(dataStack, expression, el) {
  let func = generateFunctionFromString(expression, el);
  return (receiver = () => {
  }, { scope: scope2 = {}, params = [], context } = {}) => {
    func.result = void 0;
    func.finished = false;
    let completeScope = mergeProxies([scope2, ...dataStack]);
    if (typeof func === "function") {
      let promise = func.call(context, func, completeScope).catch((error2) => handleError(error2, el, expression));
      if (func.finished) {
        runIfTypeOfFunction(receiver, func.result, completeScope, params, el);
        func.result = void 0;
      } else {
        promise.then((result) => {
          runIfTypeOfFunction(receiver, result, completeScope, params, el);
        }).catch((error2) => handleError(error2, el, expression)).finally(() => func.result = void 0);
      }
    }
  };
}
function runIfTypeOfFunction(receiver, value, scope2, params, el) {
  if (shouldAutoEvaluateFunctions && typeof value === "function") {
    let result = value.apply(scope2, params);
    if (result instanceof Promise) {
      result.then((i) => runIfTypeOfFunction(receiver, i, scope2, params)).catch((error2) => handleError(error2, el, value));
    } else {
      receiver(result);
    }
  } else if (typeof value === "object" && value instanceof Promise) {
    value.then((i) => receiver(i));
  } else {
    receiver(value);
  }
}
var prefixAsString = "x-";
function prefix(subject = "") {
  return prefixAsString + subject;
}
function setPrefix(newPrefix) {
  prefixAsString = newPrefix;
}
var directiveHandlers = {};
function directive(name, callback) {
  directiveHandlers[name] = callback;
  return {
    before(directive2) {
      if (!directiveHandlers[directive2]) {
        console.warn(String.raw`Cannot find directive \`${directive2}\`. \`${name}\` will use the default order of execution`);
        return;
      }
      const pos = directiveOrder.indexOf(directive2);
      directiveOrder.splice(pos >= 0 ? pos : directiveOrder.indexOf("DEFAULT"), 0, name);
    }
  };
}
function directiveExists(name) {
  return Object.keys(directiveHandlers).includes(name);
}
function directives(el, attributes, originalAttributeOverride) {
  attributes = Array.from(attributes);
  if (el._x_virtualDirectives) {
    let vAttributes = Object.entries(el._x_virtualDirectives).map(([name, value]) => ({ name, value }));
    let staticAttributes = attributesOnly(vAttributes);
    vAttributes = vAttributes.map((attribute) => {
      if (staticAttributes.find((attr) => attr.name === attribute.name)) {
        return {
          name: `x-bind:${attribute.name}`,
          value: `"${attribute.value}"`
        };
      }
      return attribute;
    });
    attributes = attributes.concat(vAttributes);
  }
  let transformedAttributeMap = {};
  let directives2 = attributes.map(toTransformedAttributes((newName, oldName) => transformedAttributeMap[newName] = oldName)).filter(outNonAlpineAttributes).map(toParsedDirectives(transformedAttributeMap, originalAttributeOverride)).sort(byPriority);
  return directives2.map((directive2) => {
    return getDirectiveHandler(el, directive2);
  });
}
function attributesOnly(attributes) {
  return Array.from(attributes).map(toTransformedAttributes()).filter((attr) => !outNonAlpineAttributes(attr));
}
var isDeferringHandlers = false;
var directiveHandlerStacks = /* @__PURE__ */ new Map();
var currentHandlerStackKey = Symbol();
function deferHandlingDirectives(callback) {
  isDeferringHandlers = true;
  let key = Symbol();
  currentHandlerStackKey = key;
  directiveHandlerStacks.set(key, []);
  let flushHandlers = () => {
    while (directiveHandlerStacks.get(key).length)
      directiveHandlerStacks.get(key).shift()();
    directiveHandlerStacks.delete(key);
  };
  let stopDeferring = () => {
    isDeferringHandlers = false;
    flushHandlers();
  };
  callback(flushHandlers);
  stopDeferring();
}
function getElementBoundUtilities(el) {
  let cleanups = [];
  let cleanup2 = (callback) => cleanups.push(callback);
  let [effect3, cleanupEffect] = elementBoundEffect(el);
  cleanups.push(cleanupEffect);
  let utilities = {
    Alpine: alpine_default,
    effect: effect3,
    cleanup: cleanup2,
    evaluateLater: evaluateLater.bind(evaluateLater, el),
    evaluate: evaluate.bind(evaluate, el)
  };
  let doCleanup = () => cleanups.forEach((i) => i());
  return [utilities, doCleanup];
}
function getDirectiveHandler(el, directive2) {
  let noop = () => {
  };
  let handler4 = directiveHandlers[directive2.type] || noop;
  let [utilities, cleanup2] = getElementBoundUtilities(el);
  onAttributeRemoved(el, directive2.original, cleanup2);
  let fullHandler = () => {
    if (el._x_ignore || el._x_ignoreSelf)
      return;
    handler4.inline && handler4.inline(el, directive2, utilities);
    handler4 = handler4.bind(handler4, el, directive2, utilities);
    isDeferringHandlers ? directiveHandlerStacks.get(currentHandlerStackKey).push(handler4) : handler4();
  };
  fullHandler.runCleanups = cleanup2;
  return fullHandler;
}
var startingWith = (subject, replacement) => ({ name, value }) => {
  if (name.startsWith(subject))
    name = name.replace(subject, replacement);
  return { name, value };
};
var into = (i) => i;
function toTransformedAttributes(callback = () => {
}) {
  return ({ name, value }) => {
    let { name: newName, value: newValue } = attributeTransformers.reduce((carry, transform) => {
      return transform(carry);
    }, { name, value });
    if (newName !== name)
      callback(newName, name);
    return { name: newName, value: newValue };
  };
}
var attributeTransformers = [];
function mapAttributes(callback) {
  attributeTransformers.push(callback);
}
function outNonAlpineAttributes({ name }) {
  return alpineAttributeRegex().test(name);
}
var alpineAttributeRegex = () => new RegExp(`^${prefixAsString}([^:^.]+)\\b`);
function toParsedDirectives(transformedAttributeMap, originalAttributeOverride) {
  return ({ name, value }) => {
    let typeMatch = name.match(alpineAttributeRegex());
    let valueMatch = name.match(/:([a-zA-Z0-9\-_:]+)/);
    let modifiers = name.match(/\.[^.\]]+(?=[^\]]*$)/g) || [];
    let original = originalAttributeOverride || transformedAttributeMap[name] || name;
    return {
      type: typeMatch ? typeMatch[1] : null,
      value: valueMatch ? valueMatch[1] : null,
      modifiers: modifiers.map((i) => i.replace(".", "")),
      expression: value,
      original
    };
  };
}
var DEFAULT = "DEFAULT";
var directiveOrder = [
  "ignore",
  "ref",
  "data",
  "id",
  "anchor",
  "bind",
  "init",
  "for",
  "model",
  "modelable",
  "transition",
  "show",
  "if",
  DEFAULT,
  "teleport"
];
function byPriority(a, b) {
  let typeA = directiveOrder.indexOf(a.type) === -1 ? DEFAULT : a.type;
  let typeB = directiveOrder.indexOf(b.type) === -1 ? DEFAULT : b.type;
  return directiveOrder.indexOf(typeA) - directiveOrder.indexOf(typeB);
}
function dispatch(el, name, detail = {}) {
  el.dispatchEvent(
    new CustomEvent(name, {
      detail,
      bubbles: true,
      // Allows events to pass the shadow DOM barrier.
      composed: true,
      cancelable: true
    })
  );
}
function walk(el, callback) {
  if (typeof ShadowRoot === "function" && el instanceof ShadowRoot) {
    Array.from(el.children).forEach((el2) => walk(el2, callback));
    return;
  }
  let skip = false;
  callback(el, () => skip = true);
  if (skip)
    return;
  let node = el.firstElementChild;
  while (node) {
    walk(node, callback);
    node = node.nextElementSibling;
  }
}
function warn(message, ...args) {
  console.warn(`Alpine Warning: ${message}`, ...args);
}
var started = false;
function start() {
  if (started)
    warn("Alpine has already been initialized on this page. Calling Alpine.start() more than once can cause problems.");
  started = true;
  if (!document.body)
    warn("Unable to initialize. Trying to load Alpine before `<body>` is available. Did you forget to add `defer` in Alpine's `<script>` tag?");
  dispatch(document, "alpine:init");
  dispatch(document, "alpine:initializing");
  startObservingMutations();
  onElAdded((el) => initTree(el, walk));
  onElRemoved((el) => destroyTree(el));
  onAttributesAdded((el, attrs) => {
    directives(el, attrs).forEach((handle) => handle());
  });
  let outNestedComponents = (el) => !closestRoot(el.parentElement, true);
  Array.from(document.querySelectorAll(allSelectors().join(","))).filter(outNestedComponents).forEach((el) => {
    initTree(el);
  });
  dispatch(document, "alpine:initialized");
  setTimeout(() => {
    warnAboutMissingPlugins();
  });
}
var rootSelectorCallbacks = [];
var initSelectorCallbacks = [];
function rootSelectors() {
  return rootSelectorCallbacks.map((fn) => fn());
}
function allSelectors() {
  return rootSelectorCallbacks.concat(initSelectorCallbacks).map((fn) => fn());
}
function addRootSelector(selectorCallback) {
  rootSelectorCallbacks.push(selectorCallback);
}
function addInitSelector(selectorCallback) {
  initSelectorCallbacks.push(selectorCallback);
}
function closestRoot(el, includeInitSelectors = false) {
  return findClosest(el, (element) => {
    const selectors = includeInitSelectors ? allSelectors() : rootSelectors();
    if (selectors.some((selector) => element.matches(selector)))
      return true;
  });
}
function findClosest(el, callback) {
  if (!el)
    return;
  if (callback(el))
    return el;
  if (el._x_teleportBack)
    el = el._x_teleportBack;
  if (!el.parentElement)
    return;
  return findClosest(el.parentElement, callback);
}
function isRoot(el) {
  return rootSelectors().some((selector) => el.matches(selector));
}
var initInterceptors2 = [];
function interceptInit(callback) {
  initInterceptors2.push(callback);
}
var markerDispenser = 1;
function initTree(el, walker = walk, intercept = () => {
}) {
  if (findClosest(el, (i) => i._x_ignore))
    return;
  deferHandlingDirectives(() => {
    walker(el, (el2, skip) => {
      if (el2._x_marker)
        return;
      intercept(el2, skip);
      initInterceptors2.forEach((i) => i(el2, skip));
      directives(el2, el2.attributes).forEach((handle) => handle());
      if (!el2._x_ignore)
        el2._x_marker = markerDispenser++;
      el2._x_ignore && skip();
    });
  });
}
function destroyTree(root, walker = walk) {
  walker(root, (el) => {
    cleanupElement(el);
    cleanupAttributes(el);
    delete el._x_marker;
  });
}
function warnAboutMissingPlugins() {
  let pluginDirectives = [
    ["ui", "dialog", ["[x-dialog], [x-popover]"]],
    ["anchor", "anchor", ["[x-anchor]"]],
    ["sort", "sort", ["[x-sort]"]]
  ];
  pluginDirectives.forEach(([plugin2, directive2, selectors]) => {
    if (directiveExists(directive2))
      return;
    selectors.some((selector) => {
      if (document.querySelector(selector)) {
        warn(`found "${selector}", but missing ${plugin2} plugin`);
        return true;
      }
    });
  });
}
var tickStack = [];
var isHolding = false;
function nextTick(callback = () => {
}) {
  queueMicrotask(() => {
    isHolding || setTimeout(() => {
      releaseNextTicks();
    });
  });
  return new Promise((res) => {
    tickStack.push(() => {
      callback();
      res();
    });
  });
}
function releaseNextTicks() {
  isHolding = false;
  while (tickStack.length)
    tickStack.shift()();
}
function holdNextTicks() {
  isHolding = true;
}
function setClasses(el, value) {
  if (Array.isArray(value)) {
    return setClassesFromString(el, value.join(" "));
  } else if (typeof value === "object" && value !== null) {
    return setClassesFromObject(el, value);
  } else if (typeof value === "function") {
    return setClasses(el, value());
  }
  return setClassesFromString(el, value);
}
function setClassesFromString(el, classString) {
  let missingClasses = (classString2) => classString2.split(" ").filter((i) => !el.classList.contains(i)).filter(Boolean);
  let addClassesAndReturnUndo = (classes) => {
    el.classList.add(...classes);
    return () => {
      el.classList.remove(...classes);
    };
  };
  classString = classString === true ? classString = "" : classString || "";
  return addClassesAndReturnUndo(missingClasses(classString));
}
function setClassesFromObject(el, classObject) {
  let split = (classString) => classString.split(" ").filter(Boolean);
  let forAdd = Object.entries(classObject).flatMap(([classString, bool]) => bool ? split(classString) : false).filter(Boolean);
  let forRemove = Object.entries(classObject).flatMap(([classString, bool]) => !bool ? split(classString) : false).filter(Boolean);
  let added = [];
  let removed = [];
  forRemove.forEach((i) => {
    if (el.classList.contains(i)) {
      el.classList.remove(i);
      removed.push(i);
    }
  });
  forAdd.forEach((i) => {
    if (!el.classList.contains(i)) {
      el.classList.add(i);
      added.push(i);
    }
  });
  return () => {
    removed.forEach((i) => el.classList.add(i));
    added.forEach((i) => el.classList.remove(i));
  };
}
function setStyles(el, value) {
  if (typeof value === "object" && value !== null) {
    return setStylesFromObject(el, value);
  }
  return setStylesFromString(el, value);
}
function setStylesFromObject(el, value) {
  let previousStyles = {};
  Object.entries(value).forEach(([key, value2]) => {
    previousStyles[key] = el.style[key];
    if (!key.startsWith("--")) {
      key = kebabCase(key);
    }
    el.style.setProperty(key, value2);
  });
  setTimeout(() => {
    if (el.style.length === 0) {
      el.removeAttribute("style");
    }
  });
  return () => {
    setStyles(el, previousStyles);
  };
}
function setStylesFromString(el, value) {
  let cache = el.getAttribute("style", value);
  el.setAttribute("style", value);
  return () => {
    el.setAttribute("style", cache || "");
  };
}
function kebabCase(subject) {
  return subject.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
}
function once(callback, fallback = () => {
}) {
  let called = false;
  return function() {
    if (!called) {
      called = true;
      callback.apply(this, arguments);
    } else {
      fallback.apply(this, arguments);
    }
  };
}
directive("transition", (el, { value, modifiers, expression }, { evaluate: evaluate2 }) => {
  if (typeof expression === "function")
    expression = evaluate2(expression);
  if (expression === false)
    return;
  if (!expression || typeof expression === "boolean") {
    registerTransitionsFromHelper(el, modifiers, value);
  } else {
    registerTransitionsFromClassString(el, expression, value);
  }
});
function registerTransitionsFromClassString(el, classString, stage) {
  registerTransitionObject(el, setClasses, "");
  let directiveStorageMap = {
    "enter": (classes) => {
      el._x_transition.enter.during = classes;
    },
    "enter-start": (classes) => {
      el._x_transition.enter.start = classes;
    },
    "enter-end": (classes) => {
      el._x_transition.enter.end = classes;
    },
    "leave": (classes) => {
      el._x_transition.leave.during = classes;
    },
    "leave-start": (classes) => {
      el._x_transition.leave.start = classes;
    },
    "leave-end": (classes) => {
      el._x_transition.leave.end = classes;
    }
  };
  directiveStorageMap[stage](classString);
}
function registerTransitionsFromHelper(el, modifiers, stage) {
  registerTransitionObject(el, setStyles);
  let doesntSpecify = !modifiers.includes("in") && !modifiers.includes("out") && !stage;
  let transitioningIn = doesntSpecify || modifiers.includes("in") || ["enter"].includes(stage);
  let transitioningOut = doesntSpecify || modifiers.includes("out") || ["leave"].includes(stage);
  if (modifiers.includes("in") && !doesntSpecify) {
    modifiers = modifiers.filter((i, index) => index < modifiers.indexOf("out"));
  }
  if (modifiers.includes("out") && !doesntSpecify) {
    modifiers = modifiers.filter((i, index) => index > modifiers.indexOf("out"));
  }
  let wantsAll = !modifiers.includes("opacity") && !modifiers.includes("scale");
  let wantsOpacity = wantsAll || modifiers.includes("opacity");
  let wantsScale = wantsAll || modifiers.includes("scale");
  let opacityValue = wantsOpacity ? 0 : 1;
  let scaleValue = wantsScale ? modifierValue(modifiers, "scale", 95) / 100 : 1;
  let delay = modifierValue(modifiers, "delay", 0) / 1e3;
  let origin = modifierValue(modifiers, "origin", "center");
  let property = "opacity, transform";
  let durationIn = modifierValue(modifiers, "duration", 150) / 1e3;
  let durationOut = modifierValue(modifiers, "duration", 75) / 1e3;
  let easing = `cubic-bezier(0.4, 0.0, 0.2, 1)`;
  if (transitioningIn) {
    el._x_transition.enter.during = {
      transformOrigin: origin,
      transitionDelay: `${delay}s`,
      transitionProperty: property,
      transitionDuration: `${durationIn}s`,
      transitionTimingFunction: easing
    };
    el._x_transition.enter.start = {
      opacity: opacityValue,
      transform: `scale(${scaleValue})`
    };
    el._x_transition.enter.end = {
      opacity: 1,
      transform: `scale(1)`
    };
  }
  if (transitioningOut) {
    el._x_transition.leave.during = {
      transformOrigin: origin,
      transitionDelay: `${delay}s`,
      transitionProperty: property,
      transitionDuration: `${durationOut}s`,
      transitionTimingFunction: easing
    };
    el._x_transition.leave.start = {
      opacity: 1,
      transform: `scale(1)`
    };
    el._x_transition.leave.end = {
      opacity: opacityValue,
      transform: `scale(${scaleValue})`
    };
  }
}
function registerTransitionObject(el, setFunction, defaultValue = {}) {
  if (!el._x_transition)
    el._x_transition = {
      enter: { during: defaultValue, start: defaultValue, end: defaultValue },
      leave: { during: defaultValue, start: defaultValue, end: defaultValue },
      in(before = () => {
      }, after = () => {
      }) {
        transition(el, setFunction, {
          during: this.enter.during,
          start: this.enter.start,
          end: this.enter.end
        }, before, after);
      },
      out(before = () => {
      }, after = () => {
      }) {
        transition(el, setFunction, {
          during: this.leave.during,
          start: this.leave.start,
          end: this.leave.end
        }, before, after);
      }
    };
}
window.Element.prototype._x_toggleAndCascadeWithTransitions = function(el, value, show, hide) {
  const nextTick2 = document.visibilityState === "visible" ? requestAnimationFrame : setTimeout;
  let clickAwayCompatibleShow = () => nextTick2(show);
  if (value) {
    if (el._x_transition && (el._x_transition.enter || el._x_transition.leave)) {
      el._x_transition.enter && (Object.entries(el._x_transition.enter.during).length || Object.entries(el._x_transition.enter.start).length || Object.entries(el._x_transition.enter.end).length) ? el._x_transition.in(show) : clickAwayCompatibleShow();
    } else {
      el._x_transition ? el._x_transition.in(show) : clickAwayCompatibleShow();
    }
    return;
  }
  el._x_hidePromise = el._x_transition ? new Promise((resolve, reject) => {
    el._x_transition.out(() => {
    }, () => resolve(hide));
    el._x_transitioning && el._x_transitioning.beforeCancel(() => reject({ isFromCancelledTransition: true }));
  }) : Promise.resolve(hide);
  queueMicrotask(() => {
    let closest = closestHide(el);
    if (closest) {
      if (!closest._x_hideChildren)
        closest._x_hideChildren = [];
      closest._x_hideChildren.push(el);
    } else {
      nextTick2(() => {
        let hideAfterChildren = (el2) => {
          let carry = Promise.all([
            el2._x_hidePromise,
            ...(el2._x_hideChildren || []).map(hideAfterChildren)
          ]).then(([i]) => i == null ? void 0 : i());
          delete el2._x_hidePromise;
          delete el2._x_hideChildren;
          return carry;
        };
        hideAfterChildren(el).catch((e) => {
          if (!e.isFromCancelledTransition)
            throw e;
        });
      });
    }
  });
};
function closestHide(el) {
  let parent = el.parentNode;
  if (!parent)
    return;
  return parent._x_hidePromise ? parent : closestHide(parent);
}
function transition(el, setFunction, { during, start: start2, end } = {}, before = () => {
}, after = () => {
}) {
  if (el._x_transitioning)
    el._x_transitioning.cancel();
  if (Object.keys(during).length === 0 && Object.keys(start2).length === 0 && Object.keys(end).length === 0) {
    before();
    after();
    return;
  }
  let undoStart, undoDuring, undoEnd;
  performTransition(el, {
    start() {
      undoStart = setFunction(el, start2);
    },
    during() {
      undoDuring = setFunction(el, during);
    },
    before,
    end() {
      undoStart();
      undoEnd = setFunction(el, end);
    },
    after,
    cleanup() {
      undoDuring();
      undoEnd();
    }
  });
}
function performTransition(el, stages) {
  let interrupted, reachedBefore, reachedEnd;
  let finish = once(() => {
    mutateDom(() => {
      interrupted = true;
      if (!reachedBefore)
        stages.before();
      if (!reachedEnd) {
        stages.end();
        releaseNextTicks();
      }
      stages.after();
      if (el.isConnected)
        stages.cleanup();
      delete el._x_transitioning;
    });
  });
  el._x_transitioning = {
    beforeCancels: [],
    beforeCancel(callback) {
      this.beforeCancels.push(callback);
    },
    cancel: once(function() {
      while (this.beforeCancels.length) {
        this.beforeCancels.shift()();
      }
      finish();
    }),
    finish
  };
  mutateDom(() => {
    stages.start();
    stages.during();
  });
  holdNextTicks();
  requestAnimationFrame(() => {
    if (interrupted)
      return;
    let duration = Number(getComputedStyle(el).transitionDuration.replace(/,.*/, "").replace("s", "")) * 1e3;
    let delay = Number(getComputedStyle(el).transitionDelay.replace(/,.*/, "").replace("s", "")) * 1e3;
    if (duration === 0)
      duration = Number(getComputedStyle(el).animationDuration.replace("s", "")) * 1e3;
    mutateDom(() => {
      stages.before();
    });
    reachedBefore = true;
    requestAnimationFrame(() => {
      if (interrupted)
        return;
      mutateDom(() => {
        stages.end();
      });
      releaseNextTicks();
      setTimeout(el._x_transitioning.finish, duration + delay);
      reachedEnd = true;
    });
  });
}
function modifierValue(modifiers, key, fallback) {
  if (modifiers.indexOf(key) === -1)
    return fallback;
  const rawValue = modifiers[modifiers.indexOf(key) + 1];
  if (!rawValue)
    return fallback;
  if (key === "scale") {
    if (isNaN(rawValue))
      return fallback;
  }
  if (key === "duration" || key === "delay") {
    let match = rawValue.match(/([0-9]+)ms/);
    if (match)
      return match[1];
  }
  if (key === "origin") {
    if (["top", "right", "left", "center", "bottom"].includes(modifiers[modifiers.indexOf(key) + 2])) {
      return [rawValue, modifiers[modifiers.indexOf(key) + 2]].join(" ");
    }
  }
  return rawValue;
}
var isCloning = false;
function skipDuringClone(callback, fallback = () => {
}) {
  return (...args) => isCloning ? fallback(...args) : callback(...args);
}
function onlyDuringClone(callback) {
  return (...args) => isCloning && callback(...args);
}
var interceptors = [];
function interceptClone(callback) {
  interceptors.push(callback);
}
function cloneNode(from, to) {
  interceptors.forEach((i) => i(from, to));
  isCloning = true;
  dontRegisterReactiveSideEffects(() => {
    initTree(to, (el, callback) => {
      callback(el, () => {
      });
    });
  });
  isCloning = false;
}
var isCloningLegacy = false;
function clone(oldEl, newEl) {
  if (!newEl._x_dataStack)
    newEl._x_dataStack = oldEl._x_dataStack;
  isCloning = true;
  isCloningLegacy = true;
  dontRegisterReactiveSideEffects(() => {
    cloneTree(newEl);
  });
  isCloning = false;
  isCloningLegacy = false;
}
function cloneTree(el) {
  let hasRunThroughFirstEl = false;
  let shallowWalker = (el2, callback) => {
    walk(el2, (el3, skip) => {
      if (hasRunThroughFirstEl && isRoot(el3))
        return skip();
      hasRunThroughFirstEl = true;
      callback(el3, skip);
    });
  };
  initTree(el, shallowWalker);
}
function dontRegisterReactiveSideEffects(callback) {
  let cache = effect;
  overrideEffect((callback2, el) => {
    let storedEffect = cache(callback2);
    release(storedEffect);
    return () => {
    };
  });
  callback();
  overrideEffect(cache);
}
function bind(el, name, value, modifiers = []) {
  if (!el._x_bindings)
    el._x_bindings = reactive({});
  el._x_bindings[name] = value;
  name = modifiers.includes("camel") ? camelCase(name) : name;
  switch (name) {
    case "value":
      bindInputValue(el, value);
      break;
    case "style":
      bindStyles(el, value);
      break;
    case "class":
      bindClasses(el, value);
      break;
    case "selected":
    case "checked":
      bindAttributeAndProperty(el, name, value);
      break;
    default:
      bindAttribute(el, name, value);
      break;
  }
}
function bindInputValue(el, value) {
  if (isRadio(el)) {
    if (el.attributes.value === void 0) {
      el.value = value;
    }
    if (window.fromModel) {
      if (typeof value === "boolean") {
        el.checked = safeParseBoolean(el.value) === value;
      } else {
        el.checked = checkedAttrLooseCompare(el.value, value);
      }
    }
  } else if (isCheckbox(el)) {
    if (Number.isInteger(value)) {
      el.value = value;
    } else if (!Array.isArray(value) && typeof value !== "boolean" && ![null, void 0].includes(value)) {
      el.value = String(value);
    } else {
      if (Array.isArray(value)) {
        el.checked = value.some((val) => checkedAttrLooseCompare(val, el.value));
      } else {
        el.checked = !!value;
      }
    }
  } else if (el.tagName === "SELECT") {
    updateSelect(el, value);
  } else {
    if (el.value === value)
      return;
    el.value = value === void 0 ? "" : value;
  }
}
function bindClasses(el, value) {
  if (el._x_undoAddedClasses)
    el._x_undoAddedClasses();
  el._x_undoAddedClasses = setClasses(el, value);
}
function bindStyles(el, value) {
  if (el._x_undoAddedStyles)
    el._x_undoAddedStyles();
  el._x_undoAddedStyles = setStyles(el, value);
}
function bindAttributeAndProperty(el, name, value) {
  bindAttribute(el, name, value);
  setPropertyIfChanged(el, name, value);
}
function bindAttribute(el, name, value) {
  if ([null, void 0, false].includes(value) && attributeShouldntBePreservedIfFalsy(name)) {
    el.removeAttribute(name);
  } else {
    if (isBooleanAttr(name))
      value = name;
    setIfChanged(el, name, value);
  }
}
function setIfChanged(el, attrName, value) {
  if (el.getAttribute(attrName) != value) {
    el.setAttribute(attrName, value);
  }
}
function setPropertyIfChanged(el, propName, value) {
  if (el[propName] !== value) {
    el[propName] = value;
  }
}
function updateSelect(el, value) {
  const arrayWrappedValue = [].concat(value).map((value2) => {
    return value2 + "";
  });
  Array.from(el.options).forEach((option) => {
    option.selected = arrayWrappedValue.includes(option.value);
  });
}
function camelCase(subject) {
  return subject.toLowerCase().replace(/-(\w)/g, (match, char) => char.toUpperCase());
}
function checkedAttrLooseCompare(valueA, valueB) {
  return valueA == valueB;
}
function safeParseBoolean(rawValue) {
  if ([1, "1", "true", "on", "yes", true].includes(rawValue)) {
    return true;
  }
  if ([0, "0", "false", "off", "no", false].includes(rawValue)) {
    return false;
  }
  return rawValue ? Boolean(rawValue) : null;
}
var booleanAttributes = /* @__PURE__ */ new Set([
  "allowfullscreen",
  "async",
  "autofocus",
  "autoplay",
  "checked",
  "controls",
  "default",
  "defer",
  "disabled",
  "formnovalidate",
  "inert",
  "ismap",
  "itemscope",
  "loop",
  "multiple",
  "muted",
  "nomodule",
  "novalidate",
  "open",
  "playsinline",
  "readonly",
  "required",
  "reversed",
  "selected",
  "shadowrootclonable",
  "shadowrootdelegatesfocus",
  "shadowrootserializable"
]);
function isBooleanAttr(attrName) {
  return booleanAttributes.has(attrName);
}
function attributeShouldntBePreservedIfFalsy(name) {
  return !["aria-pressed", "aria-checked", "aria-expanded", "aria-selected"].includes(name);
}
function getBinding(el, name, fallback) {
  if (el._x_bindings && el._x_bindings[name] !== void 0)
    return el._x_bindings[name];
  return getAttributeBinding(el, name, fallback);
}
function extractProp(el, name, fallback, extract = true) {
  if (el._x_bindings && el._x_bindings[name] !== void 0)
    return el._x_bindings[name];
  if (el._x_inlineBindings && el._x_inlineBindings[name] !== void 0) {
    let binding = el._x_inlineBindings[name];
    binding.extract = extract;
    return dontAutoEvaluateFunctions(() => {
      return evaluate(el, binding.expression);
    });
  }
  return getAttributeBinding(el, name, fallback);
}
function getAttributeBinding(el, name, fallback) {
  let attr = el.getAttribute(name);
  if (attr === null)
    return typeof fallback === "function" ? fallback() : fallback;
  if (attr === "")
    return true;
  if (isBooleanAttr(name)) {
    return !![name, "true"].includes(attr);
  }
  return attr;
}
function isCheckbox(el) {
  return el.type === "checkbox" || el.localName === "ui-checkbox" || el.localName === "ui-switch";
}
function isRadio(el) {
  return el.type === "radio" || el.localName === "ui-radio";
}
function debounce(func, wait) {
  let timeout;
  return function() {
    const context = this, args = arguments;
    const later = function() {
      timeout = null;
      func.apply(context, args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}
function throttle(func, limit) {
  let inThrottle;
  return function() {
    let context = this, args = arguments;
    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}
function entangle({ get: outerGet, set: outerSet }, { get: innerGet, set: innerSet }) {
  let firstRun = true;
  let outerHash;
  let reference = effect(() => {
    let outer = outerGet();
    let inner = innerGet();
    if (firstRun) {
      innerSet(cloneIfObject(outer));
      firstRun = false;
    } else {
      let outerHashLatest = JSON.stringify(outer);
      let innerHashLatest = JSON.stringify(inner);
      if (outerHashLatest !== outerHash) {
        innerSet(cloneIfObject(outer));
      } else if (outerHashLatest !== innerHashLatest) {
        outerSet(cloneIfObject(inner));
      } else ;
    }
    outerHash = JSON.stringify(outerGet());
    JSON.stringify(innerGet());
  });
  return () => {
    release(reference);
  };
}
function cloneIfObject(value) {
  return typeof value === "object" ? JSON.parse(JSON.stringify(value)) : value;
}
function plugin(callback) {
  let callbacks = Array.isArray(callback) ? callback : [callback];
  callbacks.forEach((i) => i(alpine_default));
}
var stores = {};
var isReactive = false;
function store(name, value) {
  if (!isReactive) {
    stores = reactive(stores);
    isReactive = true;
  }
  if (value === void 0) {
    return stores[name];
  }
  stores[name] = value;
  initInterceptors(stores[name]);
  if (typeof value === "object" && value !== null && value.hasOwnProperty("init") && typeof value.init === "function") {
    stores[name].init();
  }
}
function getStores() {
  return stores;
}
var binds = {};
function bind2(name, bindings) {
  let getBindings = typeof bindings !== "function" ? () => bindings : bindings;
  if (name instanceof Element) {
    return applyBindingsObject(name, getBindings());
  } else {
    binds[name] = getBindings;
  }
  return () => {
  };
}
function injectBindingProviders(obj) {
  Object.entries(binds).forEach(([name, callback]) => {
    Object.defineProperty(obj, name, {
      get() {
        return (...args) => {
          return callback(...args);
        };
      }
    });
  });
  return obj;
}
function applyBindingsObject(el, obj, original) {
  let cleanupRunners = [];
  while (cleanupRunners.length)
    cleanupRunners.pop()();
  let attributes = Object.entries(obj).map(([name, value]) => ({ name, value }));
  let staticAttributes = attributesOnly(attributes);
  attributes = attributes.map((attribute) => {
    if (staticAttributes.find((attr) => attr.name === attribute.name)) {
      return {
        name: `x-bind:${attribute.name}`,
        value: `"${attribute.value}"`
      };
    }
    return attribute;
  });
  directives(el, attributes, original).map((handle) => {
    cleanupRunners.push(handle.runCleanups);
    handle();
  });
  return () => {
    while (cleanupRunners.length)
      cleanupRunners.pop()();
  };
}
var datas = {};
function data(name, callback) {
  datas[name] = callback;
}
function injectDataProviders(obj, context) {
  Object.entries(datas).forEach(([name, callback]) => {
    Object.defineProperty(obj, name, {
      get() {
        return (...args) => {
          return callback.bind(context)(...args);
        };
      },
      enumerable: false
    });
  });
  return obj;
}
var Alpine = {
  get reactive() {
    return reactive;
  },
  get release() {
    return release;
  },
  get effect() {
    return effect;
  },
  get raw() {
    return raw;
  },
  version: "3.15.0",
  flushAndStopDeferringMutations,
  dontAutoEvaluateFunctions,
  disableEffectScheduling,
  startObservingMutations,
  stopObservingMutations,
  setReactivityEngine,
  onAttributeRemoved,
  onAttributesAdded,
  closestDataStack,
  skipDuringClone,
  onlyDuringClone,
  addRootSelector,
  addInitSelector,
  interceptClone,
  addScopeToNode,
  deferMutations,
  mapAttributes,
  evaluateLater,
  interceptInit,
  setEvaluator,
  mergeProxies,
  extractProp,
  findClosest,
  onElRemoved,
  closestRoot,
  destroyTree,
  interceptor,
  // INTERNAL: not public API and is subject to change without major release.
  transition,
  // INTERNAL
  setStyles,
  // INTERNAL
  mutateDom,
  directive,
  entangle,
  throttle,
  debounce,
  evaluate,
  initTree,
  nextTick,
  prefixed: prefix,
  prefix: setPrefix,
  plugin,
  magic,
  store,
  start,
  clone,
  // INTERNAL
  cloneNode,
  // INTERNAL
  bound: getBinding,
  $data: scope,
  watch,
  walk,
  data,
  bind: bind2
};
var alpine_default = Alpine;
function makeMap(str, expectsLowerCase) {
  const map = /* @__PURE__ */ Object.create(null);
  const list = str.split(",");
  for (let i = 0; i < list.length; i++) {
    map[list[i]] = true;
  }
  return (val) => !!map[val];
}
var EMPTY_OBJ = Object.freeze({});
var hasOwnProperty = Object.prototype.hasOwnProperty;
var hasOwn = (val, key) => hasOwnProperty.call(val, key);
var isArray = Array.isArray;
var isMap = (val) => toTypeString(val) === "[object Map]";
var isString = (val) => typeof val === "string";
var isSymbol = (val) => typeof val === "symbol";
var isObject = (val) => val !== null && typeof val === "object";
var objectToString = Object.prototype.toString;
var toTypeString = (value) => objectToString.call(value);
var toRawType = (value) => {
  return toTypeString(value).slice(8, -1);
};
var isIntegerKey = (key) => isString(key) && key !== "NaN" && key[0] !== "-" && "" + parseInt(key, 10) === key;
var cacheStringFunction = (fn) => {
  const cache = /* @__PURE__ */ Object.create(null);
  return (str) => {
    const hit = cache[str];
    return hit || (cache[str] = fn(str));
  };
};
var capitalize = cacheStringFunction((str) => str.charAt(0).toUpperCase() + str.slice(1));
var hasChanged = (value, oldValue) => value !== oldValue && (value === value || oldValue === oldValue);
var targetMap = /* @__PURE__ */ new WeakMap();
var effectStack = [];
var activeEffect;
var ITERATE_KEY = Symbol("iterate");
var MAP_KEY_ITERATE_KEY = Symbol("Map key iterate");
function isEffect(fn) {
  return fn && fn._isEffect === true;
}
function effect2(fn, options = EMPTY_OBJ) {
  if (isEffect(fn)) {
    fn = fn.raw;
  }
  const effect3 = createReactiveEffect(fn, options);
  if (!options.lazy) {
    effect3();
  }
  return effect3;
}
function stop(effect3) {
  if (effect3.active) {
    cleanup(effect3);
    if (effect3.options.onStop) {
      effect3.options.onStop();
    }
    effect3.active = false;
  }
}
var uid = 0;
function createReactiveEffect(fn, options) {
  const effect3 = function reactiveEffect() {
    if (!effect3.active) {
      return fn();
    }
    if (!effectStack.includes(effect3)) {
      cleanup(effect3);
      try {
        enableTracking();
        effectStack.push(effect3);
        activeEffect = effect3;
        return fn();
      } finally {
        effectStack.pop();
        resetTracking();
        activeEffect = effectStack[effectStack.length - 1];
      }
    }
  };
  effect3.id = uid++;
  effect3.allowRecurse = !!options.allowRecurse;
  effect3._isEffect = true;
  effect3.active = true;
  effect3.raw = fn;
  effect3.deps = [];
  effect3.options = options;
  return effect3;
}
function cleanup(effect3) {
  const { deps } = effect3;
  if (deps.length) {
    for (let i = 0; i < deps.length; i++) {
      deps[i].delete(effect3);
    }
    deps.length = 0;
  }
}
var shouldTrack = true;
var trackStack = [];
function pauseTracking() {
  trackStack.push(shouldTrack);
  shouldTrack = false;
}
function enableTracking() {
  trackStack.push(shouldTrack);
  shouldTrack = true;
}
function resetTracking() {
  const last = trackStack.pop();
  shouldTrack = last === void 0 ? true : last;
}
function track(target, type, key) {
  if (!shouldTrack || activeEffect === void 0) {
    return;
  }
  let depsMap = targetMap.get(target);
  if (!depsMap) {
    targetMap.set(target, depsMap = /* @__PURE__ */ new Map());
  }
  let dep = depsMap.get(key);
  if (!dep) {
    depsMap.set(key, dep = /* @__PURE__ */ new Set());
  }
  if (!dep.has(activeEffect)) {
    dep.add(activeEffect);
    activeEffect.deps.push(dep);
    if (activeEffect.options.onTrack) {
      activeEffect.options.onTrack({
        effect: activeEffect,
        target,
        type,
        key
      });
    }
  }
}
function trigger(target, type, key, newValue, oldValue, oldTarget) {
  const depsMap = targetMap.get(target);
  if (!depsMap) {
    return;
  }
  const effects = /* @__PURE__ */ new Set();
  const add2 = (effectsToAdd) => {
    if (effectsToAdd) {
      effectsToAdd.forEach((effect3) => {
        if (effect3 !== activeEffect || effect3.allowRecurse) {
          effects.add(effect3);
        }
      });
    }
  };
  if (type === "clear") {
    depsMap.forEach(add2);
  } else if (key === "length" && isArray(target)) {
    depsMap.forEach((dep, key2) => {
      if (key2 === "length" || key2 >= newValue) {
        add2(dep);
      }
    });
  } else {
    if (key !== void 0) {
      add2(depsMap.get(key));
    }
    switch (type) {
      case "add":
        if (!isArray(target)) {
          add2(depsMap.get(ITERATE_KEY));
          if (isMap(target)) {
            add2(depsMap.get(MAP_KEY_ITERATE_KEY));
          }
        } else if (isIntegerKey(key)) {
          add2(depsMap.get("length"));
        }
        break;
      case "delete":
        if (!isArray(target)) {
          add2(depsMap.get(ITERATE_KEY));
          if (isMap(target)) {
            add2(depsMap.get(MAP_KEY_ITERATE_KEY));
          }
        }
        break;
      case "set":
        if (isMap(target)) {
          add2(depsMap.get(ITERATE_KEY));
        }
        break;
    }
  }
  const run = (effect3) => {
    if (effect3.options.onTrigger) {
      effect3.options.onTrigger({
        effect: effect3,
        target,
        key,
        type,
        newValue,
        oldValue,
        oldTarget
      });
    }
    if (effect3.options.scheduler) {
      effect3.options.scheduler(effect3);
    } else {
      effect3();
    }
  };
  effects.forEach(run);
}
var isNonTrackableKeys = /* @__PURE__ */ makeMap(`__proto__,__v_isRef,__isVue`);
var builtInSymbols = new Set(Object.getOwnPropertyNames(Symbol).map((key) => Symbol[key]).filter(isSymbol));
var get2 = /* @__PURE__ */ createGetter();
var readonlyGet = /* @__PURE__ */ createGetter(true);
var arrayInstrumentations = /* @__PURE__ */ createArrayInstrumentations();
function createArrayInstrumentations() {
  const instrumentations = {};
  ["includes", "indexOf", "lastIndexOf"].forEach((key) => {
    instrumentations[key] = function(...args) {
      const arr = toRaw(this);
      for (let i = 0, l = this.length; i < l; i++) {
        track(arr, "get", i + "");
      }
      const res = arr[key](...args);
      if (res === -1 || res === false) {
        return arr[key](...args.map(toRaw));
      } else {
        return res;
      }
    };
  });
  ["push", "pop", "shift", "unshift", "splice"].forEach((key) => {
    instrumentations[key] = function(...args) {
      pauseTracking();
      const res = toRaw(this)[key].apply(this, args);
      resetTracking();
      return res;
    };
  });
  return instrumentations;
}
function createGetter(isReadonly = false, shallow = false) {
  return function get3(target, key, receiver) {
    if (key === "__v_isReactive") {
      return !isReadonly;
    } else if (key === "__v_isReadonly") {
      return isReadonly;
    } else if (key === "__v_raw" && receiver === (isReadonly ? shallow ? shallowReadonlyMap : readonlyMap : shallow ? shallowReactiveMap : reactiveMap).get(target)) {
      return target;
    }
    const targetIsArray = isArray(target);
    if (!isReadonly && targetIsArray && hasOwn(arrayInstrumentations, key)) {
      return Reflect.get(arrayInstrumentations, key, receiver);
    }
    const res = Reflect.get(target, key, receiver);
    if (isSymbol(key) ? builtInSymbols.has(key) : isNonTrackableKeys(key)) {
      return res;
    }
    if (!isReadonly) {
      track(target, "get", key);
    }
    if (shallow) {
      return res;
    }
    if (isRef(res)) {
      const shouldUnwrap = !targetIsArray || !isIntegerKey(key);
      return shouldUnwrap ? res.value : res;
    }
    if (isObject(res)) {
      return isReadonly ? readonly(res) : reactive2(res);
    }
    return res;
  };
}
var set2 = /* @__PURE__ */ createSetter();
function createSetter(shallow = false) {
  return function set3(target, key, value, receiver) {
    let oldValue = target[key];
    if (!shallow) {
      value = toRaw(value);
      oldValue = toRaw(oldValue);
      if (!isArray(target) && isRef(oldValue) && !isRef(value)) {
        oldValue.value = value;
        return true;
      }
    }
    const hadKey = isArray(target) && isIntegerKey(key) ? Number(key) < target.length : hasOwn(target, key);
    const result = Reflect.set(target, key, value, receiver);
    if (target === toRaw(receiver)) {
      if (!hadKey) {
        trigger(target, "add", key, value);
      } else if (hasChanged(value, oldValue)) {
        trigger(target, "set", key, value, oldValue);
      }
    }
    return result;
  };
}
function deleteProperty(target, key) {
  const hadKey = hasOwn(target, key);
  const oldValue = target[key];
  const result = Reflect.deleteProperty(target, key);
  if (result && hadKey) {
    trigger(target, "delete", key, void 0, oldValue);
  }
  return result;
}
function has(target, key) {
  const result = Reflect.has(target, key);
  if (!isSymbol(key) || !builtInSymbols.has(key)) {
    track(target, "has", key);
  }
  return result;
}
function ownKeys(target) {
  track(target, "iterate", isArray(target) ? "length" : ITERATE_KEY);
  return Reflect.ownKeys(target);
}
var mutableHandlers = {
  get: get2,
  set: set2,
  deleteProperty,
  has,
  ownKeys
};
var readonlyHandlers = {
  get: readonlyGet,
  set(target, key) {
    {
      console.warn(`Set operation on key "${String(key)}" failed: target is readonly.`, target);
    }
    return true;
  },
  deleteProperty(target, key) {
    {
      console.warn(`Delete operation on key "${String(key)}" failed: target is readonly.`, target);
    }
    return true;
  }
};
var toReactive = (value) => isObject(value) ? reactive2(value) : value;
var toReadonly = (value) => isObject(value) ? readonly(value) : value;
var toShallow = (value) => value;
var getProto = (v) => Reflect.getPrototypeOf(v);
function get$1(target, key, isReadonly = false, isShallow = false) {
  target = target[
    "__v_raw"
    /* RAW */
  ];
  const rawTarget = toRaw(target);
  const rawKey = toRaw(key);
  if (key !== rawKey) {
    !isReadonly && track(rawTarget, "get", key);
  }
  !isReadonly && track(rawTarget, "get", rawKey);
  const { has: has2 } = getProto(rawTarget);
  const wrap = isShallow ? toShallow : isReadonly ? toReadonly : toReactive;
  if (has2.call(rawTarget, key)) {
    return wrap(target.get(key));
  } else if (has2.call(rawTarget, rawKey)) {
    return wrap(target.get(rawKey));
  } else if (target !== rawTarget) {
    target.get(key);
  }
}
function has$1(key, isReadonly = false) {
  const target = this[
    "__v_raw"
    /* RAW */
  ];
  const rawTarget = toRaw(target);
  const rawKey = toRaw(key);
  if (key !== rawKey) {
    !isReadonly && track(rawTarget, "has", key);
  }
  !isReadonly && track(rawTarget, "has", rawKey);
  return key === rawKey ? target.has(key) : target.has(key) || target.has(rawKey);
}
function size(target, isReadonly = false) {
  target = target[
    "__v_raw"
    /* RAW */
  ];
  !isReadonly && track(toRaw(target), "iterate", ITERATE_KEY);
  return Reflect.get(target, "size", target);
}
function add(value) {
  value = toRaw(value);
  const target = toRaw(this);
  const proto = getProto(target);
  const hadKey = proto.has.call(target, value);
  if (!hadKey) {
    target.add(value);
    trigger(target, "add", value, value);
  }
  return this;
}
function set$1(key, value) {
  value = toRaw(value);
  const target = toRaw(this);
  const { has: has2, get: get3 } = getProto(target);
  let hadKey = has2.call(target, key);
  if (!hadKey) {
    key = toRaw(key);
    hadKey = has2.call(target, key);
  } else {
    checkIdentityKeys(target, has2, key);
  }
  const oldValue = get3.call(target, key);
  target.set(key, value);
  if (!hadKey) {
    trigger(target, "add", key, value);
  } else if (hasChanged(value, oldValue)) {
    trigger(target, "set", key, value, oldValue);
  }
  return this;
}
function deleteEntry(key) {
  const target = toRaw(this);
  const { has: has2, get: get3 } = getProto(target);
  let hadKey = has2.call(target, key);
  if (!hadKey) {
    key = toRaw(key);
    hadKey = has2.call(target, key);
  } else {
    checkIdentityKeys(target, has2, key);
  }
  const oldValue = get3 ? get3.call(target, key) : void 0;
  const result = target.delete(key);
  if (hadKey) {
    trigger(target, "delete", key, void 0, oldValue);
  }
  return result;
}
function clear() {
  const target = toRaw(this);
  const hadItems = target.size !== 0;
  const oldTarget = isMap(target) ? new Map(target) : new Set(target);
  const result = target.clear();
  if (hadItems) {
    trigger(target, "clear", void 0, void 0, oldTarget);
  }
  return result;
}
function createForEach(isReadonly, isShallow) {
  return function forEach(callback, thisArg) {
    const observed = this;
    const target = observed[
      "__v_raw"
      /* RAW */
    ];
    const rawTarget = toRaw(target);
    const wrap = isShallow ? toShallow : isReadonly ? toReadonly : toReactive;
    !isReadonly && track(rawTarget, "iterate", ITERATE_KEY);
    return target.forEach((value, key) => {
      return callback.call(thisArg, wrap(value), wrap(key), observed);
    });
  };
}
function createIterableMethod(method, isReadonly, isShallow) {
  return function(...args) {
    const target = this[
      "__v_raw"
      /* RAW */
    ];
    const rawTarget = toRaw(target);
    const targetIsMap = isMap(rawTarget);
    const isPair = method === "entries" || method === Symbol.iterator && targetIsMap;
    const isKeyOnly = method === "keys" && targetIsMap;
    const innerIterator = target[method](...args);
    const wrap = isShallow ? toShallow : isReadonly ? toReadonly : toReactive;
    !isReadonly && track(rawTarget, "iterate", isKeyOnly ? MAP_KEY_ITERATE_KEY : ITERATE_KEY);
    return {
      // iterator protocol
      next() {
        const { value, done } = innerIterator.next();
        return done ? { value, done } : {
          value: isPair ? [wrap(value[0]), wrap(value[1])] : wrap(value),
          done
        };
      },
      // iterable protocol
      [Symbol.iterator]() {
        return this;
      }
    };
  };
}
function createReadonlyMethod(type) {
  return function(...args) {
    {
      const key = args[0] ? `on key "${args[0]}" ` : ``;
      console.warn(`${capitalize(type)} operation ${key}failed: target is readonly.`, toRaw(this));
    }
    return type === "delete" ? false : this;
  };
}
function createInstrumentations() {
  const mutableInstrumentations2 = {
    get(key) {
      return get$1(this, key);
    },
    get size() {
      return size(this);
    },
    has: has$1,
    add,
    set: set$1,
    delete: deleteEntry,
    clear,
    forEach: createForEach(false, false)
  };
  const shallowInstrumentations2 = {
    get(key) {
      return get$1(this, key, false, true);
    },
    get size() {
      return size(this);
    },
    has: has$1,
    add,
    set: set$1,
    delete: deleteEntry,
    clear,
    forEach: createForEach(false, true)
  };
  const readonlyInstrumentations2 = {
    get(key) {
      return get$1(this, key, true);
    },
    get size() {
      return size(this, true);
    },
    has(key) {
      return has$1.call(this, key, true);
    },
    add: createReadonlyMethod(
      "add"
      /* ADD */
    ),
    set: createReadonlyMethod(
      "set"
      /* SET */
    ),
    delete: createReadonlyMethod(
      "delete"
      /* DELETE */
    ),
    clear: createReadonlyMethod(
      "clear"
      /* CLEAR */
    ),
    forEach: createForEach(true, false)
  };
  const shallowReadonlyInstrumentations2 = {
    get(key) {
      return get$1(this, key, true, true);
    },
    get size() {
      return size(this, true);
    },
    has(key) {
      return has$1.call(this, key, true);
    },
    add: createReadonlyMethod(
      "add"
      /* ADD */
    ),
    set: createReadonlyMethod(
      "set"
      /* SET */
    ),
    delete: createReadonlyMethod(
      "delete"
      /* DELETE */
    ),
    clear: createReadonlyMethod(
      "clear"
      /* CLEAR */
    ),
    forEach: createForEach(true, true)
  };
  const iteratorMethods = ["keys", "values", "entries", Symbol.iterator];
  iteratorMethods.forEach((method) => {
    mutableInstrumentations2[method] = createIterableMethod(method, false, false);
    readonlyInstrumentations2[method] = createIterableMethod(method, true, false);
    shallowInstrumentations2[method] = createIterableMethod(method, false, true);
    shallowReadonlyInstrumentations2[method] = createIterableMethod(method, true, true);
  });
  return [
    mutableInstrumentations2,
    readonlyInstrumentations2,
    shallowInstrumentations2,
    shallowReadonlyInstrumentations2
  ];
}
var [mutableInstrumentations, readonlyInstrumentations, shallowInstrumentations, shallowReadonlyInstrumentations] = /* @__PURE__ */ createInstrumentations();
function createInstrumentationGetter(isReadonly, shallow) {
  const instrumentations = isReadonly ? readonlyInstrumentations : mutableInstrumentations;
  return (target, key, receiver) => {
    if (key === "__v_isReactive") {
      return !isReadonly;
    } else if (key === "__v_isReadonly") {
      return isReadonly;
    } else if (key === "__v_raw") {
      return target;
    }
    return Reflect.get(hasOwn(instrumentations, key) && key in target ? instrumentations : target, key, receiver);
  };
}
var mutableCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(false)
};
var readonlyCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(true)
};
function checkIdentityKeys(target, has2, key) {
  const rawKey = toRaw(key);
  if (rawKey !== key && has2.call(target, rawKey)) {
    const type = toRawType(target);
    console.warn(`Reactive ${type} contains both the raw and reactive versions of the same object${type === `Map` ? ` as keys` : ``}, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`);
  }
}
var reactiveMap = /* @__PURE__ */ new WeakMap();
var shallowReactiveMap = /* @__PURE__ */ new WeakMap();
var readonlyMap = /* @__PURE__ */ new WeakMap();
var shallowReadonlyMap = /* @__PURE__ */ new WeakMap();
function targetTypeMap(rawType) {
  switch (rawType) {
    case "Object":
    case "Array":
      return 1;
    case "Map":
    case "Set":
    case "WeakMap":
    case "WeakSet":
      return 2;
    default:
      return 0;
  }
}
function getTargetType(value) {
  return value[
    "__v_skip"
    /* SKIP */
  ] || !Object.isExtensible(value) ? 0 : targetTypeMap(toRawType(value));
}
function reactive2(target) {
  if (target && target[
    "__v_isReadonly"
    /* IS_READONLY */
  ]) {
    return target;
  }
  return createReactiveObject(target, false, mutableHandlers, mutableCollectionHandlers, reactiveMap);
}
function readonly(target) {
  return createReactiveObject(target, true, readonlyHandlers, readonlyCollectionHandlers, readonlyMap);
}
function createReactiveObject(target, isReadonly, baseHandlers, collectionHandlers, proxyMap) {
  if (!isObject(target)) {
    {
      console.warn(`value cannot be made reactive: ${String(target)}`);
    }
    return target;
  }
  if (target[
    "__v_raw"
    /* RAW */
  ] && !(isReadonly && target[
    "__v_isReactive"
    /* IS_REACTIVE */
  ])) {
    return target;
  }
  const existingProxy = proxyMap.get(target);
  if (existingProxy) {
    return existingProxy;
  }
  const targetType = getTargetType(target);
  if (targetType === 0) {
    return target;
  }
  const proxy = new Proxy(target, targetType === 2 ? collectionHandlers : baseHandlers);
  proxyMap.set(target, proxy);
  return proxy;
}
function toRaw(observed) {
  return observed && toRaw(observed[
    "__v_raw"
    /* RAW */
  ]) || observed;
}
function isRef(r) {
  return Boolean(r && r.__v_isRef === true);
}
magic("nextTick", () => nextTick);
magic("dispatch", (el) => dispatch.bind(dispatch, el));
magic("watch", (el, { evaluateLater: evaluateLater2, cleanup: cleanup2 }) => (key, callback) => {
  let evaluate2 = evaluateLater2(key);
  let getter = () => {
    let value;
    evaluate2((i) => value = i);
    return value;
  };
  let unwatch = watch(getter, callback);
  cleanup2(unwatch);
});
magic("store", getStores);
magic("data", (el) => scope(el));
magic("root", (el) => closestRoot(el));
magic("refs", (el) => {
  if (el._x_refs_proxy)
    return el._x_refs_proxy;
  el._x_refs_proxy = mergeProxies(getArrayOfRefObject(el));
  return el._x_refs_proxy;
});
function getArrayOfRefObject(el) {
  let refObjects = [];
  findClosest(el, (i) => {
    if (i._x_refs)
      refObjects.push(i._x_refs);
  });
  return refObjects;
}
var globalIdMemo = {};
function findAndIncrementId(name) {
  if (!globalIdMemo[name])
    globalIdMemo[name] = 0;
  return ++globalIdMemo[name];
}
function closestIdRoot(el, name) {
  return findClosest(el, (element) => {
    if (element._x_ids && element._x_ids[name])
      return true;
  });
}
function setIdRoot(el, name) {
  if (!el._x_ids)
    el._x_ids = {};
  if (!el._x_ids[name])
    el._x_ids[name] = findAndIncrementId(name);
}
magic("id", (el, { cleanup: cleanup2 }) => (name, key = null) => {
  let cacheKey = `${name}${key ? `-${key}` : ""}`;
  return cacheIdByNameOnElement(el, cacheKey, cleanup2, () => {
    let root = closestIdRoot(el, name);
    let id = root ? root._x_ids[name] : findAndIncrementId(name);
    return key ? `${name}-${id}-${key}` : `${name}-${id}`;
  });
});
interceptClone((from, to) => {
  if (from._x_id) {
    to._x_id = from._x_id;
  }
});
function cacheIdByNameOnElement(el, cacheKey, cleanup2, callback) {
  if (!el._x_id)
    el._x_id = {};
  if (el._x_id[cacheKey])
    return el._x_id[cacheKey];
  let output = callback();
  el._x_id[cacheKey] = output;
  cleanup2(() => {
    delete el._x_id[cacheKey];
  });
  return output;
}
magic("el", (el) => el);
warnMissingPluginMagic("Focus", "focus", "focus");
warnMissingPluginMagic("Persist", "persist", "persist");
function warnMissingPluginMagic(name, magicName, slug) {
  magic(magicName, (el) => warn(`You can't use [$${magicName}] without first installing the "${name}" plugin here: https://alpinejs.dev/plugins/${slug}`, el));
}
directive("modelable", (el, { expression }, { effect: effect3, evaluateLater: evaluateLater2, cleanup: cleanup2 }) => {
  let func = evaluateLater2(expression);
  let innerGet = () => {
    let result;
    func((i) => result = i);
    return result;
  };
  let evaluateInnerSet = evaluateLater2(`${expression} = __placeholder`);
  let innerSet = (val) => evaluateInnerSet(() => {
  }, { scope: { "__placeholder": val } });
  let initialValue = innerGet();
  innerSet(initialValue);
  queueMicrotask(() => {
    if (!el._x_model)
      return;
    el._x_removeModelListeners["default"]();
    let outerGet = el._x_model.get;
    let outerSet = el._x_model.set;
    let releaseEntanglement = entangle(
      {
        get() {
          return outerGet();
        },
        set(value) {
          outerSet(value);
        }
      },
      {
        get() {
          return innerGet();
        },
        set(value) {
          innerSet(value);
        }
      }
    );
    cleanup2(releaseEntanglement);
  });
});
directive("teleport", (el, { modifiers, expression }, { cleanup: cleanup2 }) => {
  if (el.tagName.toLowerCase() !== "template")
    warn("x-teleport can only be used on a <template> tag", el);
  let target = getTarget(expression);
  let clone2 = el.content.cloneNode(true).firstElementChild;
  el._x_teleport = clone2;
  clone2._x_teleportBack = el;
  el.setAttribute("data-teleport-template", true);
  clone2.setAttribute("data-teleport-target", true);
  if (el._x_forwardEvents) {
    el._x_forwardEvents.forEach((eventName) => {
      clone2.addEventListener(eventName, (e) => {
        e.stopPropagation();
        el.dispatchEvent(new e.constructor(e.type, e));
      });
    });
  }
  addScopeToNode(clone2, {}, el);
  let placeInDom = (clone3, target2, modifiers2) => {
    if (modifiers2.includes("prepend")) {
      target2.parentNode.insertBefore(clone3, target2);
    } else if (modifiers2.includes("append")) {
      target2.parentNode.insertBefore(clone3, target2.nextSibling);
    } else {
      target2.appendChild(clone3);
    }
  };
  mutateDom(() => {
    placeInDom(clone2, target, modifiers);
    skipDuringClone(() => {
      initTree(clone2);
    })();
  });
  el._x_teleportPutBack = () => {
    let target2 = getTarget(expression);
    mutateDom(() => {
      placeInDom(el._x_teleport, target2, modifiers);
    });
  };
  cleanup2(
    () => mutateDom(() => {
      clone2.remove();
      destroyTree(clone2);
    })
  );
});
var teleportContainerDuringClone = document.createElement("div");
function getTarget(expression) {
  let target = skipDuringClone(() => {
    return document.querySelector(expression);
  }, () => {
    return teleportContainerDuringClone;
  })();
  if (!target)
    warn(`Cannot find x-teleport element for selector: "${expression}"`);
  return target;
}
var handler = () => {
};
handler.inline = (el, { modifiers }, { cleanup: cleanup2 }) => {
  modifiers.includes("self") ? el._x_ignoreSelf = true : el._x_ignore = true;
  cleanup2(() => {
    modifiers.includes("self") ? delete el._x_ignoreSelf : delete el._x_ignore;
  });
};
directive("ignore", handler);
directive("effect", skipDuringClone((el, { expression }, { effect: effect3 }) => {
  effect3(evaluateLater(el, expression));
}));
function on(el, event, modifiers, callback) {
  let listenerTarget = el;
  let handler4 = (e) => callback(e);
  let options = {};
  let wrapHandler = (callback2, wrapper) => (e) => wrapper(callback2, e);
  if (modifiers.includes("dot"))
    event = dotSyntax(event);
  if (modifiers.includes("camel"))
    event = camelCase2(event);
  if (modifiers.includes("passive"))
    options.passive = true;
  if (modifiers.includes("capture"))
    options.capture = true;
  if (modifiers.includes("window"))
    listenerTarget = window;
  if (modifiers.includes("document"))
    listenerTarget = document;
  if (modifiers.includes("debounce")) {
    let nextModifier = modifiers[modifiers.indexOf("debounce") + 1] || "invalid-wait";
    let wait = isNumeric(nextModifier.split("ms")[0]) ? Number(nextModifier.split("ms")[0]) : 250;
    handler4 = debounce(handler4, wait);
  }
  if (modifiers.includes("throttle")) {
    let nextModifier = modifiers[modifiers.indexOf("throttle") + 1] || "invalid-wait";
    let wait = isNumeric(nextModifier.split("ms")[0]) ? Number(nextModifier.split("ms")[0]) : 250;
    handler4 = throttle(handler4, wait);
  }
  if (modifiers.includes("prevent"))
    handler4 = wrapHandler(handler4, (next, e) => {
      e.preventDefault();
      next(e);
    });
  if (modifiers.includes("stop"))
    handler4 = wrapHandler(handler4, (next, e) => {
      e.stopPropagation();
      next(e);
    });
  if (modifiers.includes("once")) {
    handler4 = wrapHandler(handler4, (next, e) => {
      next(e);
      listenerTarget.removeEventListener(event, handler4, options);
    });
  }
  if (modifiers.includes("away") || modifiers.includes("outside")) {
    listenerTarget = document;
    handler4 = wrapHandler(handler4, (next, e) => {
      if (el.contains(e.target))
        return;
      if (e.target.isConnected === false)
        return;
      if (el.offsetWidth < 1 && el.offsetHeight < 1)
        return;
      if (el._x_isShown === false)
        return;
      next(e);
    });
  }
  if (modifiers.includes("self"))
    handler4 = wrapHandler(handler4, (next, e) => {
      e.target === el && next(e);
    });
  if (isKeyEvent(event) || isClickEvent(event)) {
    handler4 = wrapHandler(handler4, (next, e) => {
      if (isListeningForASpecificKeyThatHasntBeenPressed(e, modifiers)) {
        return;
      }
      next(e);
    });
  }
  listenerTarget.addEventListener(event, handler4, options);
  return () => {
    listenerTarget.removeEventListener(event, handler4, options);
  };
}
function dotSyntax(subject) {
  return subject.replace(/-/g, ".");
}
function camelCase2(subject) {
  return subject.toLowerCase().replace(/-(\w)/g, (match, char) => char.toUpperCase());
}
function isNumeric(subject) {
  return !Array.isArray(subject) && !isNaN(subject);
}
function kebabCase2(subject) {
  if ([" ", "_"].includes(
    subject
  ))
    return subject;
  return subject.replace(/([a-z])([A-Z])/g, "$1-$2").replace(/[_\s]/, "-").toLowerCase();
}
function isKeyEvent(event) {
  return ["keydown", "keyup"].includes(event);
}
function isClickEvent(event) {
  return ["contextmenu", "click", "mouse"].some((i) => event.includes(i));
}
function isListeningForASpecificKeyThatHasntBeenPressed(e, modifiers) {
  let keyModifiers = modifiers.filter((i) => {
    return !["window", "document", "prevent", "stop", "once", "capture", "self", "away", "outside", "passive", "preserve-scroll"].includes(i);
  });
  if (keyModifiers.includes("debounce")) {
    let debounceIndex = keyModifiers.indexOf("debounce");
    keyModifiers.splice(debounceIndex, isNumeric((keyModifiers[debounceIndex + 1] || "invalid-wait").split("ms")[0]) ? 2 : 1);
  }
  if (keyModifiers.includes("throttle")) {
    let debounceIndex = keyModifiers.indexOf("throttle");
    keyModifiers.splice(debounceIndex, isNumeric((keyModifiers[debounceIndex + 1] || "invalid-wait").split("ms")[0]) ? 2 : 1);
  }
  if (keyModifiers.length === 0)
    return false;
  if (keyModifiers.length === 1 && keyToModifiers(e.key).includes(keyModifiers[0]))
    return false;
  const systemKeyModifiers = ["ctrl", "shift", "alt", "meta", "cmd", "super"];
  const selectedSystemKeyModifiers = systemKeyModifiers.filter((modifier) => keyModifiers.includes(modifier));
  keyModifiers = keyModifiers.filter((i) => !selectedSystemKeyModifiers.includes(i));
  if (selectedSystemKeyModifiers.length > 0) {
    const activelyPressedKeyModifiers = selectedSystemKeyModifiers.filter((modifier) => {
      if (modifier === "cmd" || modifier === "super")
        modifier = "meta";
      return e[`${modifier}Key`];
    });
    if (activelyPressedKeyModifiers.length === selectedSystemKeyModifiers.length) {
      if (isClickEvent(e.type))
        return false;
      if (keyToModifiers(e.key).includes(keyModifiers[0]))
        return false;
    }
  }
  return true;
}
function keyToModifiers(key) {
  if (!key)
    return [];
  key = kebabCase2(key);
  let modifierToKeyMap = {
    "ctrl": "control",
    "slash": "/",
    "space": " ",
    "spacebar": " ",
    "cmd": "meta",
    "esc": "escape",
    "up": "arrow-up",
    "down": "arrow-down",
    "left": "arrow-left",
    "right": "arrow-right",
    "period": ".",
    "comma": ",",
    "equal": "=",
    "minus": "-",
    "underscore": "_"
  };
  modifierToKeyMap[key] = key;
  return Object.keys(modifierToKeyMap).map((modifier) => {
    if (modifierToKeyMap[modifier] === key)
      return modifier;
  }).filter((modifier) => modifier);
}
directive("model", (el, { modifiers, expression }, { effect: effect3, cleanup: cleanup2 }) => {
  let scopeTarget = el;
  if (modifiers.includes("parent")) {
    scopeTarget = el.parentNode;
  }
  let evaluateGet = evaluateLater(scopeTarget, expression);
  let evaluateSet;
  if (typeof expression === "string") {
    evaluateSet = evaluateLater(scopeTarget, `${expression} = __placeholder`);
  } else if (typeof expression === "function" && typeof expression() === "string") {
    evaluateSet = evaluateLater(scopeTarget, `${expression()} = __placeholder`);
  } else {
    evaluateSet = () => {
    };
  }
  let getValue = () => {
    let result;
    evaluateGet((value) => result = value);
    return isGetterSetter(result) ? result.get() : result;
  };
  let setValue = (value) => {
    let result;
    evaluateGet((value2) => result = value2);
    if (isGetterSetter(result)) {
      result.set(value);
    } else {
      evaluateSet(() => {
      }, {
        scope: { "__placeholder": value }
      });
    }
  };
  if (typeof expression === "string" && el.type === "radio") {
    mutateDom(() => {
      if (!el.hasAttribute("name"))
        el.setAttribute("name", expression);
    });
  }
  let event = el.tagName.toLowerCase() === "select" || ["checkbox", "radio"].includes(el.type) || modifiers.includes("lazy") ? "change" : "input";
  let removeListener = isCloning ? () => {
  } : on(el, event, modifiers, (e) => {
    setValue(getInputValue(el, modifiers, e, getValue()));
  });
  if (modifiers.includes("fill")) {
    if ([void 0, null, ""].includes(getValue()) || isCheckbox(el) && Array.isArray(getValue()) || el.tagName.toLowerCase() === "select" && el.multiple) {
      setValue(
        getInputValue(el, modifiers, { target: el }, getValue())
      );
    }
  }
  if (!el._x_removeModelListeners)
    el._x_removeModelListeners = {};
  el._x_removeModelListeners["default"] = removeListener;
  cleanup2(() => el._x_removeModelListeners["default"]());
  if (el.form) {
    let removeResetListener = on(el.form, "reset", [], (e) => {
      nextTick(() => el._x_model && el._x_model.set(getInputValue(el, modifiers, { target: el }, getValue())));
    });
    cleanup2(() => removeResetListener());
  }
  el._x_model = {
    get() {
      return getValue();
    },
    set(value) {
      setValue(value);
    }
  };
  el._x_forceModelUpdate = (value) => {
    if (value === void 0 && typeof expression === "string" && expression.match(/\./))
      value = "";
    window.fromModel = true;
    mutateDom(() => bind(el, "value", value));
    delete window.fromModel;
  };
  effect3(() => {
    let value = getValue();
    if (modifiers.includes("unintrusive") && document.activeElement.isSameNode(el))
      return;
    el._x_forceModelUpdate(value);
  });
});
function getInputValue(el, modifiers, event, currentValue) {
  return mutateDom(() => {
    if (event instanceof CustomEvent && event.detail !== void 0)
      return event.detail !== null && event.detail !== void 0 ? event.detail : event.target.value;
    else if (isCheckbox(el)) {
      if (Array.isArray(currentValue)) {
        let newValue = null;
        if (modifiers.includes("number")) {
          newValue = safeParseNumber(event.target.value);
        } else if (modifiers.includes("boolean")) {
          newValue = safeParseBoolean(event.target.value);
        } else {
          newValue = event.target.value;
        }
        return event.target.checked ? currentValue.includes(newValue) ? currentValue : currentValue.concat([newValue]) : currentValue.filter((el2) => !checkedAttrLooseCompare2(el2, newValue));
      } else {
        return event.target.checked;
      }
    } else if (el.tagName.toLowerCase() === "select" && el.multiple) {
      if (modifiers.includes("number")) {
        return Array.from(event.target.selectedOptions).map((option) => {
          let rawValue = option.value || option.text;
          return safeParseNumber(rawValue);
        });
      } else if (modifiers.includes("boolean")) {
        return Array.from(event.target.selectedOptions).map((option) => {
          let rawValue = option.value || option.text;
          return safeParseBoolean(rawValue);
        });
      }
      return Array.from(event.target.selectedOptions).map((option) => {
        return option.value || option.text;
      });
    } else {
      let newValue;
      if (isRadio(el)) {
        if (event.target.checked) {
          newValue = event.target.value;
        } else {
          newValue = currentValue;
        }
      } else {
        newValue = event.target.value;
      }
      if (modifiers.includes("number")) {
        return safeParseNumber(newValue);
      } else if (modifiers.includes("boolean")) {
        return safeParseBoolean(newValue);
      } else if (modifiers.includes("trim")) {
        return newValue.trim();
      } else {
        return newValue;
      }
    }
  });
}
function safeParseNumber(rawValue) {
  let number = rawValue ? parseFloat(rawValue) : null;
  return isNumeric2(number) ? number : rawValue;
}
function checkedAttrLooseCompare2(valueA, valueB) {
  return valueA == valueB;
}
function isNumeric2(subject) {
  return !Array.isArray(subject) && !isNaN(subject);
}
function isGetterSetter(value) {
  return value !== null && typeof value === "object" && typeof value.get === "function" && typeof value.set === "function";
}
directive("cloak", (el) => queueMicrotask(() => mutateDom(() => el.removeAttribute(prefix("cloak")))));
addInitSelector(() => `[${prefix("init")}]`);
directive("init", skipDuringClone((el, { expression }, { evaluate: evaluate2 }) => {
  if (typeof expression === "string") {
    return !!expression.trim() && evaluate2(expression, {}, false);
  }
  return evaluate2(expression, {}, false);
}));
directive("text", (el, { expression }, { effect: effect3, evaluateLater: evaluateLater2 }) => {
  let evaluate2 = evaluateLater2(expression);
  effect3(() => {
    evaluate2((value) => {
      mutateDom(() => {
        el.textContent = value;
      });
    });
  });
});
directive("html", (el, { expression }, { effect: effect3, evaluateLater: evaluateLater2 }) => {
  let evaluate2 = evaluateLater2(expression);
  effect3(() => {
    evaluate2((value) => {
      mutateDom(() => {
        el.innerHTML = value;
        el._x_ignoreSelf = true;
        initTree(el);
        delete el._x_ignoreSelf;
      });
    });
  });
});
mapAttributes(startingWith(":", into(prefix("bind:"))));
var handler2 = (el, { value, modifiers, expression, original }, { effect: effect3, cleanup: cleanup2 }) => {
  if (!value) {
    let bindingProviders = {};
    injectBindingProviders(bindingProviders);
    let getBindings = evaluateLater(el, expression);
    getBindings((bindings) => {
      applyBindingsObject(el, bindings, original);
    }, { scope: bindingProviders });
    return;
  }
  if (value === "key")
    return storeKeyForXFor(el, expression);
  if (el._x_inlineBindings && el._x_inlineBindings[value] && el._x_inlineBindings[value].extract) {
    return;
  }
  let evaluate2 = evaluateLater(el, expression);
  effect3(() => evaluate2((result) => {
    if (result === void 0 && typeof expression === "string" && expression.match(/\./)) {
      result = "";
    }
    mutateDom(() => bind(el, value, result, modifiers));
  }));
  cleanup2(() => {
    el._x_undoAddedClasses && el._x_undoAddedClasses();
    el._x_undoAddedStyles && el._x_undoAddedStyles();
  });
};
handler2.inline = (el, { value, modifiers, expression }) => {
  if (!value)
    return;
  if (!el._x_inlineBindings)
    el._x_inlineBindings = {};
  el._x_inlineBindings[value] = { expression, extract: false };
};
directive("bind", handler2);
function storeKeyForXFor(el, expression) {
  el._x_keyExpression = expression;
}
addRootSelector(() => `[${prefix("data")}]`);
directive("data", (el, { expression }, { cleanup: cleanup2 }) => {
  if (shouldSkipRegisteringDataDuringClone(el))
    return;
  expression = expression === "" ? "{}" : expression;
  let magicContext = {};
  injectMagics(magicContext, el);
  let dataProviderContext = {};
  injectDataProviders(dataProviderContext, magicContext);
  let data2 = evaluate(el, expression, { scope: dataProviderContext });
  if (data2 === void 0 || data2 === true)
    data2 = {};
  injectMagics(data2, el);
  let reactiveData = reactive(data2);
  initInterceptors(reactiveData);
  let undo = addScopeToNode(el, reactiveData);
  reactiveData["init"] && evaluate(el, reactiveData["init"]);
  cleanup2(() => {
    reactiveData["destroy"] && evaluate(el, reactiveData["destroy"]);
    undo();
  });
});
interceptClone((from, to) => {
  if (from._x_dataStack) {
    to._x_dataStack = from._x_dataStack;
    to.setAttribute("data-has-alpine-state", true);
  }
});
function shouldSkipRegisteringDataDuringClone(el) {
  if (!isCloning)
    return false;
  if (isCloningLegacy)
    return true;
  return el.hasAttribute("data-has-alpine-state");
}
directive("show", (el, { modifiers, expression }, { effect: effect3 }) => {
  let evaluate2 = evaluateLater(el, expression);
  if (!el._x_doHide)
    el._x_doHide = () => {
      mutateDom(() => {
        el.style.setProperty("display", "none", modifiers.includes("important") ? "important" : void 0);
      });
    };
  if (!el._x_doShow)
    el._x_doShow = () => {
      mutateDom(() => {
        if (el.style.length === 1 && el.style.display === "none") {
          el.removeAttribute("style");
        } else {
          el.style.removeProperty("display");
        }
      });
    };
  let hide = () => {
    el._x_doHide();
    el._x_isShown = false;
  };
  let show = () => {
    el._x_doShow();
    el._x_isShown = true;
  };
  let clickAwayCompatibleShow = () => setTimeout(show);
  let toggle = once(
    (value) => value ? show() : hide(),
    (value) => {
      if (typeof el._x_toggleAndCascadeWithTransitions === "function") {
        el._x_toggleAndCascadeWithTransitions(el, value, show, hide);
      } else {
        value ? clickAwayCompatibleShow() : hide();
      }
    }
  );
  let oldValue;
  let firstTime = true;
  effect3(() => evaluate2((value) => {
    if (!firstTime && value === oldValue)
      return;
    if (modifiers.includes("immediate"))
      value ? clickAwayCompatibleShow() : hide();
    toggle(value);
    oldValue = value;
    firstTime = false;
  }));
});
directive("for", (el, { expression }, { effect: effect3, cleanup: cleanup2 }) => {
  let iteratorNames = parseForExpression(expression);
  let evaluateItems = evaluateLater(el, iteratorNames.items);
  let evaluateKey = evaluateLater(
    el,
    // the x-bind:key expression is stored for our use instead of evaluated.
    el._x_keyExpression || "index"
  );
  el._x_prevKeys = [];
  el._x_lookup = {};
  effect3(() => loop(el, iteratorNames, evaluateItems, evaluateKey));
  cleanup2(() => {
    Object.values(el._x_lookup).forEach((el2) => mutateDom(
      () => {
        destroyTree(el2);
        el2.remove();
      }
    ));
    delete el._x_prevKeys;
    delete el._x_lookup;
  });
});
function loop(el, iteratorNames, evaluateItems, evaluateKey) {
  let isObject2 = (i) => typeof i === "object" && !Array.isArray(i);
  let templateEl = el;
  evaluateItems((items) => {
    if (isNumeric3(items) && items >= 0) {
      items = Array.from(Array(items).keys(), (i) => i + 1);
    }
    if (items === void 0)
      items = [];
    let lookup = el._x_lookup;
    let prevKeys = el._x_prevKeys;
    let scopes = [];
    let keys = [];
    if (isObject2(items)) {
      items = Object.entries(items).map(([key, value]) => {
        let scope2 = getIterationScopeVariables(iteratorNames, value, key, items);
        evaluateKey((value2) => {
          if (keys.includes(value2))
            warn("Duplicate key on x-for", el);
          keys.push(value2);
        }, { scope: __spreadValues({ index: key }, scope2) });
        scopes.push(scope2);
      });
    } else {
      for (let i = 0; i < items.length; i++) {
        let scope2 = getIterationScopeVariables(iteratorNames, items[i], i, items);
        evaluateKey((value) => {
          if (keys.includes(value))
            warn("Duplicate key on x-for", el);
          keys.push(value);
        }, { scope: __spreadValues({ index: i }, scope2) });
        scopes.push(scope2);
      }
    }
    let adds = [];
    let moves = [];
    let removes = [];
    let sames = [];
    for (let i = 0; i < prevKeys.length; i++) {
      let key = prevKeys[i];
      if (keys.indexOf(key) === -1)
        removes.push(key);
    }
    prevKeys = prevKeys.filter((key) => !removes.includes(key));
    let lastKey = "template";
    for (let i = 0; i < keys.length; i++) {
      let key = keys[i];
      let prevIndex = prevKeys.indexOf(key);
      if (prevIndex === -1) {
        prevKeys.splice(i, 0, key);
        adds.push([lastKey, i]);
      } else if (prevIndex !== i) {
        let keyInSpot = prevKeys.splice(i, 1)[0];
        let keyForSpot = prevKeys.splice(prevIndex - 1, 1)[0];
        prevKeys.splice(i, 0, keyForSpot);
        prevKeys.splice(prevIndex, 0, keyInSpot);
        moves.push([keyInSpot, keyForSpot]);
      } else {
        sames.push(key);
      }
      lastKey = key;
    }
    for (let i = 0; i < removes.length; i++) {
      let key = removes[i];
      if (!(key in lookup))
        continue;
      mutateDom(() => {
        destroyTree(lookup[key]);
        lookup[key].remove();
      });
      delete lookup[key];
    }
    for (let i = 0; i < moves.length; i++) {
      let [keyInSpot, keyForSpot] = moves[i];
      let elInSpot = lookup[keyInSpot];
      let elForSpot = lookup[keyForSpot];
      let marker = document.createElement("div");
      mutateDom(() => {
        if (!elForSpot)
          warn(`x-for ":key" is undefined or invalid`, templateEl, keyForSpot, lookup);
        elForSpot.after(marker);
        elInSpot.after(elForSpot);
        elForSpot._x_currentIfEl && elForSpot.after(elForSpot._x_currentIfEl);
        marker.before(elInSpot);
        elInSpot._x_currentIfEl && elInSpot.after(elInSpot._x_currentIfEl);
        marker.remove();
      });
      elForSpot._x_refreshXForScope(scopes[keys.indexOf(keyForSpot)]);
    }
    for (let i = 0; i < adds.length; i++) {
      let [lastKey2, index] = adds[i];
      let lastEl = lastKey2 === "template" ? templateEl : lookup[lastKey2];
      if (lastEl._x_currentIfEl)
        lastEl = lastEl._x_currentIfEl;
      let scope2 = scopes[index];
      let key = keys[index];
      let clone2 = document.importNode(templateEl.content, true).firstElementChild;
      let reactiveScope = reactive(scope2);
      addScopeToNode(clone2, reactiveScope, templateEl);
      clone2._x_refreshXForScope = (newScope) => {
        Object.entries(newScope).forEach(([key2, value]) => {
          reactiveScope[key2] = value;
        });
      };
      mutateDom(() => {
        lastEl.after(clone2);
        skipDuringClone(() => initTree(clone2))();
      });
      if (typeof key === "object") {
        warn("x-for key cannot be an object, it must be a string or an integer", templateEl);
      }
      lookup[key] = clone2;
    }
    for (let i = 0; i < sames.length; i++) {
      lookup[sames[i]]._x_refreshXForScope(scopes[keys.indexOf(sames[i])]);
    }
    templateEl._x_prevKeys = keys;
  });
}
function parseForExpression(expression) {
  let forIteratorRE = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/;
  let stripParensRE = /^\s*\(|\)\s*$/g;
  let forAliasRE = /([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/;
  let inMatch = expression.match(forAliasRE);
  if (!inMatch)
    return;
  let res = {};
  res.items = inMatch[2].trim();
  let item = inMatch[1].replace(stripParensRE, "").trim();
  let iteratorMatch = item.match(forIteratorRE);
  if (iteratorMatch) {
    res.item = item.replace(forIteratorRE, "").trim();
    res.index = iteratorMatch[1].trim();
    if (iteratorMatch[2]) {
      res.collection = iteratorMatch[2].trim();
    }
  } else {
    res.item = item;
  }
  return res;
}
function getIterationScopeVariables(iteratorNames, item, index, items) {
  let scopeVariables = {};
  if (/^\[.*\]$/.test(iteratorNames.item) && Array.isArray(item)) {
    let names = iteratorNames.item.replace("[", "").replace("]", "").split(",").map((i) => i.trim());
    names.forEach((name, i) => {
      scopeVariables[name] = item[i];
    });
  } else if (/^\{.*\}$/.test(iteratorNames.item) && !Array.isArray(item) && typeof item === "object") {
    let names = iteratorNames.item.replace("{", "").replace("}", "").split(",").map((i) => i.trim());
    names.forEach((name) => {
      scopeVariables[name] = item[name];
    });
  } else {
    scopeVariables[iteratorNames.item] = item;
  }
  if (iteratorNames.index)
    scopeVariables[iteratorNames.index] = index;
  if (iteratorNames.collection)
    scopeVariables[iteratorNames.collection] = items;
  return scopeVariables;
}
function isNumeric3(subject) {
  return !Array.isArray(subject) && !isNaN(subject);
}
function handler3() {
}
handler3.inline = (el, { expression }, { cleanup: cleanup2 }) => {
  let root = closestRoot(el);
  if (!root._x_refs)
    root._x_refs = {};
  root._x_refs[expression] = el;
  cleanup2(() => delete root._x_refs[expression]);
};
directive("ref", handler3);
directive("if", (el, { expression }, { effect: effect3, cleanup: cleanup2 }) => {
  if (el.tagName.toLowerCase() !== "template")
    warn("x-if can only be used on a <template> tag", el);
  let evaluate2 = evaluateLater(el, expression);
  let show = () => {
    if (el._x_currentIfEl)
      return el._x_currentIfEl;
    let clone2 = el.content.cloneNode(true).firstElementChild;
    addScopeToNode(clone2, {}, el);
    mutateDom(() => {
      el.after(clone2);
      skipDuringClone(() => initTree(clone2))();
    });
    el._x_currentIfEl = clone2;
    el._x_undoIf = () => {
      mutateDom(() => {
        destroyTree(clone2);
        clone2.remove();
      });
      delete el._x_currentIfEl;
    };
    return clone2;
  };
  let hide = () => {
    if (!el._x_undoIf)
      return;
    el._x_undoIf();
    delete el._x_undoIf;
  };
  effect3(() => evaluate2((value) => {
    value ? show() : hide();
  }));
  cleanup2(() => el._x_undoIf && el._x_undoIf());
});
directive("id", (el, { expression }, { evaluate: evaluate2 }) => {
  let names = evaluate2(expression);
  names.forEach((name) => setIdRoot(el, name));
});
interceptClone((from, to) => {
  if (from._x_ids) {
    to._x_ids = from._x_ids;
  }
});
mapAttributes(startingWith("@", into(prefix("on:"))));
directive("on", skipDuringClone((el, { value, modifiers, expression }, { cleanup: cleanup2 }) => {
  let evaluate2 = expression ? evaluateLater(el, expression) : () => {
  };
  if (el.tagName.toLowerCase() === "template") {
    if (!el._x_forwardEvents)
      el._x_forwardEvents = [];
    if (!el._x_forwardEvents.includes(value))
      el._x_forwardEvents.push(value);
  }
  let removeListener = on(el, value, modifiers, (e) => {
    evaluate2(() => {
    }, { scope: { "$event": e }, params: [e] });
  });
  cleanup2(() => removeListener());
}));
warnMissingPluginDirective("Collapse", "collapse", "collapse");
warnMissingPluginDirective("Intersect", "intersect", "intersect");
warnMissingPluginDirective("Focus", "trap", "focus");
warnMissingPluginDirective("Mask", "mask", "mask");
function warnMissingPluginDirective(name, directiveName, slug) {
  directive(directiveName, (el) => warn(`You can't use [x-${directiveName}] without first installing the "${name}" plugin here: https://alpinejs.dev/plugins/${slug}`, el));
}
alpine_default.setEvaluator(normalEvaluator);
alpine_default.setReactivityEngine({ reactive: reactive2, effect: effect2, release: stop, raw: toRaw });
var src_default = alpine_default;
var module_default = src_default;
class VueAppManager {
  constructor() {
    this.initializedApps = /* @__PURE__ */ new Set();
    this.appInstances = /* @__PURE__ */ new Map();
    this.registeredComponents = /* @__PURE__ */ new Map();
    console.log("✅ VueAppManager инициализирован");
  }
  // 🔥 УЛУЧШЕННАЯ ПРОВЕРКА С ПРОВЕРКОЙ DOM
  canInitialize(appId) {
    const appElement = document.getElementById(appId);
    if (!appElement) {
      console.warn(`⚠️ VueAppManager: Элемент ${appId} не найден в DOM`);
      return false;
    }
    if (this.initializedApps.has(appId)) {
      console.warn(`⚠️ VueAppManager: Приложение ${appId} уже инициализировано через менеджер`);
      return false;
    }
    if (appElement.__vue_app__) {
      console.warn(`⚠️ VueAppManager: На элемент ${appId} уже напрямую смонтировано Vue приложение`);
      return false;
    }
    const existingApps = [
      "rental-requests-app",
      "public-rental-request-show-app",
      "rental-request-edit-app",
      "rental-request-app",
      "lessor-rental-requests-app"
    ];
    const hasOtherApp = existingApps.some(
      (id) => id !== appId && document.getElementById(id)
    );
    if (hasOtherApp) {
      console.warn(`⚠️ VueAppManager: Обнаружены другие приложения, пропускаем ${appId}`);
      return false;
    }
    return true;
  }
  // Регистрирует инициализированное приложение
  registerApp(appId, appInstance) {
    this.initializedApps.add(appId);
    this.appInstances.set(appId, appInstance);
    console.log(`✅ VueAppManager: Зарегистрировано приложение ${appId}`);
  }
  // Получает экземпляр приложения
  getApp(appId) {
    return this.appInstances.get(appId);
  }
  // Проверяет существование приложения
  hasApp(appId) {
    return this.initializedApps.has(appId);
  }
  // 🔥 БЕЗОПАСНАЯ ИНИЦИАЛИЗАЦИЯ ПРИЛОЖЕНИЯ
  initializeApp(appId, appInstance) {
    if (!this.canInitialize(appId)) {
      console.warn(`App ${appId} initialization skipped by manager`);
      return false;
    }
    try {
      const appElement = document.getElementById(appId);
      if (!appElement) {
        throw new Error(`Element #${appId} not found`);
      }
      appInstance.config.errorHandler = (err, vm, info) => {
        console.error(`Vue Error in ${appId}:`, err);
        console.error("Component:", vm);
        console.error("Info:", info);
      };
      appInstance.mount(appElement);
      this.registerApp(appId, appInstance);
      console.log(`✅ VueAppManager: Приложение ${appId} успешно смонтировано`);
      return true;
    } catch (error2) {
      console.error(`VueAppManager: Failed to initialize app ${appId}:`, error2);
      this.showFallback(appId);
      return false;
    }
  }
  // Метод для отображения fallback
  showFallback(appId) {
    const fallbackElement = document.getElementById(`${appId}-fallback`);
    if (fallbackElement) {
      fallbackElement.style.display = "block";
      console.log(`✅ VueAppManager: Показан fallback для ${appId}`);
    }
    const vueAppElement = document.getElementById(appId);
    if (vueAppElement) {
      vueAppElement.style.display = "none";
    }
  }
  // 🔥 ДОБАВЛЯЕМ МЕТОД ДЛЯ РЕГИСТРАЦИИ КОМПОНЕНТОВ
  registerComponent(name, component) {
    this.registeredComponents.set(name, component);
    console.log(`✅ VueAppManager: Зарегистрирован компонент ${name}`);
  }
  // 🔥 ДОБАВЛЯЕМ МЕТОД ДЛЯ ПОЛУЧЕНИЯ КОМПОНЕНТА
  getComponent(name) {
    return this.registeredComponents.get(name);
  }
  // 🔥 ДОБАВЛЯЕМ МЕТОД ДЛЯ УНИЧТОЖЕНИЯ ПРИЛОЖЕНИЯ
  unmountApp(appId) {
    const app = this.appInstances.get(appId);
    if (app) {
      try {
        app.unmount();
        this.initializedApps.delete(appId);
        this.appInstances.delete(appId);
        console.log(`✅ VueAppManager: Приложение ${appId} уничтожено`);
      } catch (error2) {
        console.error(`VueAppManager: Ошибка уничтожения приложения ${appId}:`, error2);
      }
    }
  }
}
window.vueAppManager = new VueAppManager();
window.Alpine = module_default;
module_default.start();
window.Chart = Chart;
window.Swal = Swal;
window.csrfToken = ((_d = document.querySelector('meta[name="csrf-token"]')) == null ? void 0 : _d.getAttribute("content")) || "";
if (document.getElementById("catalog-app")) {
  createApp(CatalogApp).mount("#catalog-app");
}
if (document.getElementById("catalog-detail-app") && window.__EQUIPMENT_ID__) {
  const detailApp = createApp(CatalogDetail, {
    equipmentId: window.__EQUIPMENT_ID__
  });
  detailApp.mount("#catalog-detail-app");
}
if (document.getElementById("cart-icon")) {
  const cartApp = createApp({ components: { CartIcon }, template: "<CartIcon />" });
  cartApp.component("CartIcon", CartIcon);
  cartApp.mount("#cart-icon");
}
window.addEventListener("DOMContentLoaded", function() {
  if (window.cartBus) {
    window.cartBus.on("cart-updated", function() {
      const event = new CustomEvent("cart-refresh");
      document.dispatchEvent(event);
    });
  }
});
document.addEventListener("DOMContentLoaded", function() {
  try {
    initTheme();
    initSmartNavbar();
    initRipple();
  } catch (e) {
    console.error(e);
  }
});
window.addEventListener("error", function(e) {
  console.error("Global error:", e.error);
});
window.addEventListener("unhandledrejection", function(e) {
  console.error("Unhandled rejection:", e.reason);
});
