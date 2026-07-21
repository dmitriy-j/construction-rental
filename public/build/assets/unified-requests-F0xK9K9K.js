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
import { a as createElementBlock, o as openBlock, b as createBaseVNode, d as createCommentVNode, t as toDisplayString, e as createTextVNode, w as withDirectives, v as vModelSelect, F as Fragment, r as renderList, n as normalizeClass, c as createApp } from "./runtime-dom.esm-bundler-DgO_AsNV.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  name: "UnifiedRequests",
  props: {
    userRole: { type: String, default: "guest" },
    authUser: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] }
  },
  data() {
    return {
      loading: true,
      error: null,
      requests: { data: [], meta: { current_page: 1, last_page: 1, total: 0 }, links: {} },
      filters: {
        category_id: "",
        location_id: "",
        sort: "newest"
      }
    };
  },
  computed: {
    isGuest() {
      return this.userRole === "guest";
    },
    isLessee() {
      return this.userRole === "lessee";
    },
    isLessor() {
      return this.userRole === "lessor";
    },
    pageTitle() {
      if (this.isLessee) return "Мои заявки на аренду";
      if (this.isLessor) return "Заявки на аренду";
      return "Публичные заявки на аренду";
    },
    createRoute() {
      return "/lessee/rental-requests/create";
    },
    pages() {
      if (!this.requests.meta) return [];
      const current = this.requests.meta.current_page || 1;
      const last = this.requests.meta.last_page || 1;
      const pages = [];
      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i);
      }
      return pages;
    },
    processedRequests() {
      const data = this.requests.data;
      if (!data || !Array.isArray(data)) return [];
      return data.map((r) => __spreadProps(__spreadValues({}, r), {
        rental_days: this.calcDays(r.rental_period_start, r.rental_period_end)
      }));
    }
  },
  methods: {
    getApiUrl() {
      if (this.isLessee) return "/api/lessee/rental-requests";
      if (this.isLessor) return "/api/lessor/rental-requests";
      return "/api/public/rental-requests";
    },
    loadRequests(page = 1) {
      return __async(this, null, function* () {
        this.loading = true;
        this.error = null;
        try {
          const params = new URLSearchParams(__spreadValues({ page }, this.filters));
          const url = this.getApiUrl() + "?" + params.toString();
          const response = yield fetch(url, {
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          if (!response.ok) throw new Error("HTTP " + response.status);
          const json = yield response.json();
          if (json.success && json.data) {
            this.requests = json.data;
          } else if (json.data) {
            this.requests = json;
          } else {
            this.requests = json;
          }
          if (!Array.isArray(this.requests.data)) {
            this.requests.data = [];
          }
        } catch (e) {
          console.error("Ошибка загрузки:", e);
          this.error = "Не удалось загрузить заявки";
          this.requests = { data: [], meta: { current_page: 1, last_page: 1, total: 0 }, links: {} };
        } finally {
          this.loading = false;
        }
      });
    },
    changePage(page) {
      var _a;
      if (page >= 1 && page <= (((_a = this.requests.meta) == null ? void 0 : _a.last_page) || 1)) {
        this.loadRequests(page);
      }
    },
    redirectToLogin() {
      window.location.href = "/login";
    },
    formatDate(d) {
      if (!d) return "—";
      try {
        return new Date(d).toLocaleDateString("ru-RU");
      } catch (e) {
        return "—";
      }
    },
    formatPeriod(start, end) {
      if (!start || !end) return "Период не указан";
      return this.formatDate(start) + " — " + this.formatDate(end);
    },
    calcDays(start, end) {
      if (!start || !end) return 0;
      try {
        return Math.ceil((new Date(end) - new Date(start)) / (1e3 * 3600 * 24)) + 1;
      } catch (e) {
        return 0;
      }
    },
    formatCurrency(v) {
      if (!v && v !== 0) return "—";
      return new Intl.NumberFormat("ru-RU", { style: "currency", currency: "RUB", minimumFractionDigits: 0 }).format(v);
    },
    getStatusColor(s) {
      const colors = { draft: "secondary", active: "success", paused: "warning", processing: "warning", completed: "primary", cancelled: "danger" };
      return colors[s] || "light";
    },
    getStatusText(s) {
      const texts = { draft: "Черновик", active: "Активна", paused: "Приостановлена", processing: "В процессе", completed: "Завершена", cancelled: "Отменена" };
      return texts[s] || s;
    }
  },
  mounted() {
    this.loadRequests();
  }
};
const _hoisted_1 = { class: "unified-requests" };
const _hoisted_2 = { class: "page-header d-flex justify-content-between align-items-center mb-4" };
const _hoisted_3 = { class: "page-title" };
const _hoisted_4 = { key: 0 };
const _hoisted_5 = ["href"];
const _hoisted_6 = { class: "card mb-4" };
const _hoisted_7 = { class: "card-body" };
const _hoisted_8 = { class: "row g-3" };
const _hoisted_9 = { class: "col-md-3" };
const _hoisted_10 = ["value"];
const _hoisted_11 = { class: "col-md-3" };
const _hoisted_12 = ["value"];
const _hoisted_13 = { class: "col-md-3" };
const _hoisted_14 = { class: "col-md-3" };
const _hoisted_15 = {
  key: 0,
  class: "text-center py-5"
};
const _hoisted_16 = {
  key: 1,
  class: "alert alert-danger text-center"
};
const _hoisted_17 = {
  key: 2,
  class: "text-center py-5"
};
const _hoisted_18 = {
  key: 0,
  class: "text-muted"
};
const _hoisted_19 = {
  key: 1,
  class: "text-muted"
};
const _hoisted_20 = {
  key: 2,
  class: "text-muted"
};
const _hoisted_21 = ["href"];
const _hoisted_22 = {
  key: 3,
  class: "row"
};
const _hoisted_23 = { class: "card h-100" };
const _hoisted_24 = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_25 = { class: "mb-0" };
const _hoisted_26 = { class: "card-body" };
const _hoisted_27 = { class: "card-text small" };
const _hoisted_28 = { class: "d-flex justify-content-between text-muted small mb-2" };
const _hoisted_29 = { class: "d-flex justify-content-between text-muted small" };
const _hoisted_30 = {
  key: 0,
  class: "mt-2"
};
const _hoisted_31 = { class: "badge bg-light text-dark me-1" };
const _hoisted_32 = {
  key: 0,
  class: "small text-muted mt-1"
};
const _hoisted_33 = {
  key: 1,
  class: "mt-2 p-2 bg-light rounded small"
};
const _hoisted_34 = { class: "card-footer bg-transparent" };
const _hoisted_35 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_36 = { class: "text-muted" };
const _hoisted_37 = ["href"];
const _hoisted_38 = ["href"];
const _hoisted_39 = {
  key: 4,
  class: "mt-3"
};
const _hoisted_40 = { class: "pagination justify-content-center mb-0" };
const _hoisted_41 = ["onClick"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("h1", _hoisted_3, toDisplayString($options.pageTitle), 1),
      $options.isLessee ? (openBlock(), createElementBlock("div", _hoisted_4, [
        createBaseVNode("a", {
          href: $options.createRoute,
          class: "btn btn-primary"
        }, [..._cache[10] || (_cache[10] = [
          createBaseVNode("i", { class: "fas fa-plus me-2" }, null, -1),
          createTextVNode("Создать заявку ", -1)
        ])], 8, _hoisted_5)
      ])) : createCommentVNode("", true)
    ]),
    createBaseVNode("div", _hoisted_6, [
      createBaseVNode("div", _hoisted_7, [
        createBaseVNode("div", _hoisted_8, [
          createBaseVNode("div", _hoisted_9, [
            _cache[12] || (_cache[12] = createBaseVNode("label", { class: "form-label" }, "Категория", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.filters.category_id = $event),
              class: "form-select",
              onChange: _cache[1] || (_cache[1] = (...args) => $options.loadRequests && $options.loadRequests(...args))
            }, [
              _cache[11] || (_cache[11] = createBaseVNode("option", { value: "" }, "Все категории", -1)),
              (openBlock(true), createElementBlock(Fragment, null, renderList($props.categories, (cat) => {
                return openBlock(), createElementBlock("option", {
                  key: cat.id,
                  value: cat.id
                }, toDisplayString(cat.name), 9, _hoisted_10);
              }), 128))
            ], 544), [
              [vModelSelect, $data.filters.category_id]
            ])
          ]),
          createBaseVNode("div", _hoisted_11, [
            _cache[14] || (_cache[14] = createBaseVNode("label", { class: "form-label" }, "Локация", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.filters.location_id = $event),
              class: "form-select",
              onChange: _cache[3] || (_cache[3] = (...args) => $options.loadRequests && $options.loadRequests(...args))
            }, [
              _cache[13] || (_cache[13] = createBaseVNode("option", { value: "" }, "Все локации", -1)),
              (openBlock(true), createElementBlock(Fragment, null, renderList($props.locations, (loc) => {
                return openBlock(), createElementBlock("option", {
                  key: loc.id,
                  value: loc.id
                }, toDisplayString(loc.name), 9, _hoisted_12);
              }), 128))
            ], 544), [
              [vModelSelect, $data.filters.location_id]
            ])
          ]),
          createBaseVNode("div", _hoisted_13, [
            _cache[16] || (_cache[16] = createBaseVNode("label", { class: "form-label" }, "Сортировка", -1)),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.filters.sort = $event),
              class: "form-select",
              onChange: _cache[5] || (_cache[5] = (...args) => $options.loadRequests && $options.loadRequests(...args))
            }, [..._cache[15] || (_cache[15] = [
              createBaseVNode("option", { value: "newest" }, "Сначала новые", -1),
              createBaseVNode("option", { value: "budget" }, "По бюджету", -1),
              createBaseVNode("option", { value: "proposals" }, "По предложениям", -1)
            ])], 544), [
              [vModelSelect, $data.filters.sort]
            ])
          ]),
          createBaseVNode("div", _hoisted_14, [
            _cache[18] || (_cache[18] = createBaseVNode("label", { class: "form-label" }, " ", -1)),
            $options.isLessee ? (openBlock(), createElementBlock("button", {
              key: 0,
              class: "btn btn-outline-secondary w-100",
              onClick: _cache[6] || (_cache[6] = (...args) => $options.loadRequests && $options.loadRequests(...args))
            }, [..._cache[17] || (_cache[17] = [
              createBaseVNode("i", { class: "fas fa-sync me-1" }, null, -1),
              createTextVNode("Обновить ", -1)
            ])])) : createCommentVNode("", true)
          ])
        ])
      ])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_15, [..._cache[19] || (_cache[19] = [
      createBaseVNode("div", {
        class: "spinner-border text-primary",
        role: "status"
      }, [
        createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
      ], -1),
      createBaseVNode("p", { class: "mt-2 text-muted" }, "Загрузка заявок...", -1)
    ])])) : $data.error ? (openBlock(), createElementBlock("div", _hoisted_16, [
      _cache[20] || (_cache[20] = createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1)),
      createTextVNode(toDisplayString($data.error), 1)
    ])) : !$data.requests.data || $data.requests.data.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_17, [
      _cache[22] || (_cache[22] = createBaseVNode("i", { class: "fas fa-clipboard-list fa-4x text-muted mb-3" }, null, -1)),
      _cache[23] || (_cache[23] = createBaseVNode("h4", null, "Заявки не найдены", -1)),
      $options.isGuest ? (openBlock(), createElementBlock("p", _hoisted_18, " Публичные заявки отсутствуют. Попробуйте изменить параметры фильтрации. ")) : $options.isLessee ? (openBlock(), createElementBlock("p", _hoisted_19, " У вас ещё нет заявок. Создайте первую! ")) : $options.isLessor ? (openBlock(), createElementBlock("p", _hoisted_20, " Заявки, соответствующие вашему оборудованию, не найдены. ")) : createCommentVNode("", true),
      $options.isLessee ? (openBlock(), createElementBlock("a", {
        key: 3,
        href: $options.createRoute,
        class: "btn btn-primary mt-2"
      }, [..._cache[21] || (_cache[21] = [
        createBaseVNode("i", { class: "fas fa-plus me-2" }, null, -1),
        createTextVNode("Создать заявку ", -1)
      ])], 8, _hoisted_21)) : createCommentVNode("", true)
    ])) : (openBlock(), createElementBlock("div", _hoisted_22, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.processedRequests, (request) => {
        var _a, _b, _c;
        return openBlock(), createElementBlock("div", {
          class: "col-lg-6 mb-4",
          key: request.id
        }, [
          createBaseVNode("div", _hoisted_23, [
            createBaseVNode("div", _hoisted_24, [
              createBaseVNode("h6", _hoisted_25, toDisplayString(request.title || "Без названия"), 1),
              createBaseVNode("span", {
                class: normalizeClass(["badge", "bg-" + $options.getStatusColor(request.status)])
              }, toDisplayString($options.getStatusText(request.status)), 3)
            ]),
            createBaseVNode("div", _hoisted_26, [
              createBaseVNode("p", _hoisted_27, toDisplayString(request.description_short || (request.description || "").substring(0, 200) || "Описание отсутствует"), 1),
              createBaseVNode("div", _hoisted_28, [
                createBaseVNode("span", null, [
                  _cache[24] || (_cache[24] = createBaseVNode("i", { class: "fas fa-calendar me-1" }, null, -1)),
                  createTextVNode(toDisplayString($options.formatPeriod(request.rental_period_start, request.rental_period_end)), 1)
                ]),
                createBaseVNode("span", null, toDisplayString(request.rental_days || $options.calcDays(request.rental_period_start, request.rental_period_end)) + " дн.", 1)
              ]),
              createBaseVNode("div", _hoisted_29, [
                createBaseVNode("span", null, [
                  _cache[25] || (_cache[25] = createBaseVNode("i", { class: "fas fa-map-marker-alt me-1" }, null, -1)),
                  createTextVNode(toDisplayString(((_a = request.location) == null ? void 0 : _a.name) || "Не указана"), 1)
                ]),
                createBaseVNode("span", null, [
                  _cache[26] || (_cache[26] = createBaseVNode("i", { class: "fas fa-tag me-1" }, null, -1)),
                  createTextVNode(toDisplayString(request.category || request.items && ((_c = (_b = request.items[0]) == null ? void 0 : _b.category) == null ? void 0 : _c.name) || "—"), 1)
                ])
              ]),
              request.items && request.items.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_30, [
                (openBlock(true), createElementBlock(Fragment, null, renderList(request.items.slice(0, 3), (item) => {
                  var _a2;
                  return openBlock(), createElementBlock("div", {
                    key: item.id,
                    class: "small"
                  }, [
                    createBaseVNode("span", _hoisted_31, toDisplayString(((_a2 = item.category) == null ? void 0 : _a2.name) || "—"), 1),
                    createTextVNode(" × " + toDisplayString(item.quantity || 1), 1)
                  ]);
                }), 128)),
                request.items.length > 3 ? (openBlock(), createElementBlock("div", _hoisted_32, " + ещё " + toDisplayString(request.items.length - 3) + " позиций ", 1)) : createCommentVNode("", true)
              ])) : createCommentVNode("", true),
              $options.isLessor && request.lessor_pricing ? (openBlock(), createElementBlock("div", _hoisted_33, [
                _cache[27] || (_cache[27] = createBaseVNode("strong", null, "Ваш бюджет:", -1)),
                createTextVNode(" " + toDisplayString($options.formatCurrency(request.lessor_pricing.total_lessor_budget || 0)), 1)
              ])) : createCommentVNode("", true)
            ]),
            createBaseVNode("div", _hoisted_34, [
              createBaseVNode("div", _hoisted_35, [
                createBaseVNode("small", _hoisted_36, [
                  _cache[28] || (_cache[28] = createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1)),
                  createTextVNode(toDisplayString($options.formatDate(request.created_at)), 1)
                ]),
                createBaseVNode("div", null, [
                  $options.isGuest ? (openBlock(), createElementBlock("button", {
                    key: 0,
                    class: "btn btn-sm btn-outline-secondary",
                    onClick: _cache[7] || (_cache[7] = (...args) => $options.redirectToLogin && $options.redirectToLogin(...args))
                  }, [..._cache[29] || (_cache[29] = [
                    createBaseVNode("i", { class: "fas fa-sign-in-alt me-1" }, null, -1),
                    createTextVNode("Авторизуйтесь ", -1)
                  ])])) : createCommentVNode("", true),
                  $options.isLessee ? (openBlock(), createElementBlock("a", {
                    key: 1,
                    href: "/lessee/rental-requests/" + request.id,
                    class: "btn btn-sm btn-outline-primary"
                  }, [..._cache[30] || (_cache[30] = [
                    createBaseVNode("i", { class: "fas fa-eye me-1" }, null, -1),
                    createTextVNode("Подробнее ", -1)
                  ])], 8, _hoisted_37)) : createCommentVNode("", true),
                  $options.isLessor ? (openBlock(), createElementBlock("a", {
                    key: 2,
                    href: "/lessor/rental-requests/" + request.id,
                    class: "btn btn-sm btn-outline-primary"
                  }, [..._cache[31] || (_cache[31] = [
                    createBaseVNode("i", { class: "fas fa-eye me-1" }, null, -1),
                    createTextVNode("Подробнее ", -1)
                  ])], 8, _hoisted_38)) : createCommentVNode("", true)
                ])
              ])
            ])
          ])
        ]);
      }), 128))
    ])),
    $data.requests.meta && $data.requests.meta.last_page > 1 ? (openBlock(), createElementBlock("nav", _hoisted_39, [
      createBaseVNode("ul", _hoisted_40, [
        createBaseVNode("li", {
          class: normalizeClass(["page-item", { disabled: $data.requests.meta.current_page <= 1 }])
        }, [
          createBaseVNode("button", {
            class: "page-link",
            onClick: _cache[8] || (_cache[8] = ($event) => $options.changePage($data.requests.meta.current_page - 1))
          }, "Назад")
        ], 2),
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.pages, (page) => {
          return openBlock(), createElementBlock("li", {
            class: normalizeClass(["page-item", { active: page === $data.requests.meta.current_page }]),
            key: page
          }, [
            createBaseVNode("button", {
              class: "page-link",
              onClick: ($event) => $options.changePage(page)
            }, toDisplayString(page), 9, _hoisted_41)
          ], 2);
        }), 128)),
        createBaseVNode("li", {
          class: normalizeClass(["page-item", { disabled: $data.requests.meta.current_page >= $data.requests.meta.last_page }])
        }, [
          createBaseVNode("button", {
            class: "page-link",
            onClick: _cache[9] || (_cache[9] = ($event) => $options.changePage($data.requests.meta.current_page + 1))
          }, "Вперед")
        ], 2)
      ])
    ])) : createCommentVNode("", true)
  ]);
}
const UnifiedRequests = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
document.addEventListener("DOMContentLoaded", function() {
  const container = document.getElementById("unified-requests-app");
  if (!container) {
    console.error("❌ Контейнер #unified-requests-app не найден");
    return;
  }
  try {
    const userRole = container.dataset.userRole || "guest";
    let authUser = null;
    try {
      authUser = container.dataset.authUser ? JSON.parse(container.dataset.authUser) : null;
    } catch (e) {
    }
    let categories = [];
    try {
      categories = container.dataset.categories ? JSON.parse(container.dataset.categories) : [];
    } catch (e) {
    }
    let locations = [];
    try {
      locations = container.dataset.locations ? JSON.parse(container.dataset.locations) : [];
    } catch (e) {
    }
    const app = createApp(UnifiedRequests, {
      userRole,
      authUser,
      categories,
      locations
    });
    if (window.vueAppManager && window.vueAppManager.canInitialize("unified-requests-app")) {
      window.vueAppManager.initializeApp("unified-requests-app", app);
    } else {
      app.mount("#unified-requests-app");
    }
    console.log("✅ UnifiedRequests смонтирован, роль:", userRole);
  } catch (error) {
    console.error("❌ Ошибка монтирования UnifiedRequests:", error);
  }
});
