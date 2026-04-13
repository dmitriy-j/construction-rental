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
import { a as createElementBlock, o as openBlock, b as createBaseVNode, e as createCommentVNode, d as createTextVNode, F as Fragment, r as renderList, n as normalizeClass, t as toDisplayString, w as withDirectives, v as vModelSelect, h as createStaticVNode, j as vModelText, u as withModifiers, f as normalizeStyle, c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  name: "RentalRequestList",
  data() {
    return {
      requests: {},
      statistics: [],
      filters: {
        status: "all",
        search: "",
        sort: "newest",
        per_page: 15
      },
      viewMode: "table",
      loading: false,
      debounceTimeout: null,
      createRoute: "/lessee/rental-requests/create",
      error: null
    };
  },
  methods: {
    loadRequests(page = 1) {
      return __async(this, null, function* () {
        var _a;
        this.loading = true;
        this.error = null;
        try {
          console.log("🔍 Загружаем заявки...");
          const params = new URLSearchParams(__spreadValues({
            page
          }, this.filters));
          const apiUrl = `${window.location.origin}/api/lessee/rental-requests?${params}`;
          console.log("📡 API URL:", apiUrl);
          const response = yield fetch(apiUrl, {
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest",
              "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
            },
            credentials: "include"
          });
          console.log("📊 Ответ сервера:", response.status, response.statusText);
          if (!response.ok) {
            throw new Error(`HTTP ошибка! Статус: ${response.status}`);
          }
          const data = yield response.json();
          console.log("📦 Данные заявок:", data);
          if (data.success) {
            this.requests = data.data;
            console.log("✅ Успешно загружено заявок:", ((_a = data.data.data) == null ? void 0 : _a.length) || 0);
            this.calculateStatsFromRequests();
          } else {
            throw new Error(data.message || "Ошибка сервера");
          }
        } catch (error) {
          console.error("❌ Ошибка загрузки заявок:", error);
          this.error = `Не удалось загрузить заявки: ${error.message}`;
          this.requests = {
            data: [],
            meta: { total: 0, current_page: 1, last_page: 1 }
          };
          this.createEmptyStats();
        } finally {
          this.loading = false;
        }
      });
    },
    // УДАЛЕН метод loadStats() - используем только расчет из данных
    // Альтернативный метод расчета статистики из загруженных данных
    calculateStatsFromRequests() {
      var _a;
      if (!this.requests.data || this.requests.data.length === 0) {
        this.createEmptyStats();
        return;
      }
      const stats = {
        total: ((_a = this.requests.meta) == null ? void 0 : _a.total) || this.requests.data.length,
        active: this.requests.data.filter((r) => r.status === "active").length,
        processing: this.requests.data.filter((r) => r.status === "processing").length,
        completed: this.requests.data.filter((r) => r.status === "completed").length,
        cancelled: this.requests.data.filter((r) => r.status === "cancelled").length,
        draft: this.requests.data.filter((r) => r.status === "draft").length,
        total_items: this.requests.data.reduce((sum, r) => sum + (r.items_count || 0), 0),
        total_proposals: this.requests.data.reduce((sum, r) => sum + (r.responses_count || 0), 0),
        total_budget: this.requests.data.reduce((sum, r) => sum + (r.calculated_budget_from || r.budget_from || 0), 0)
      };
      this.statistics = [
        {
          key: "total",
          title: "Всего заявок",
          value: stats.total,
          color: "primary",
          icon: "fas fa-clipboard-list fa-2x"
        },
        {
          key: "active",
          title: "Активные",
          value: stats.active,
          color: "success",
          icon: "fas fa-play-circle fa-2x"
        },
        {
          key: "processing",
          title: "В процессе",
          value: stats.processing,
          color: "warning",
          icon: "fas fa-cogs fa-2x"
        },
        {
          key: "completed",
          title: "Завершенные",
          value: stats.completed,
          color: "info",
          icon: "fas fa-check-circle fa-2x"
        },
        {
          key: "total_items",
          title: "Всего позиций",
          value: stats.total_items,
          color: "secondary",
          icon: "fas fa-cubes fa-2x"
        },
        {
          key: "total_proposals",
          title: "Предложений",
          value: stats.total_proposals,
          color: "dark",
          icon: "fas fa-handshake fa-2x"
        }
      ];
      console.log("📊 Статистика рассчитана из данных:", stats);
    },
    // Создание пустой статистики
    createEmptyStats() {
      this.statistics = [
        {
          key: "total",
          title: "Всего заявок",
          value: 0,
          color: "primary",
          icon: "fas fa-clipboard-list fa-2x"
        },
        {
          key: "active",
          title: "Активные",
          value: 0,
          color: "success",
          icon: "fas fa-play-circle fa-2x"
        },
        {
          key: "processing",
          title: "В процессе",
          value: 0,
          color: "warning",
          icon: "fas fa-cogs fa-2x"
        },
        {
          key: "completed",
          title: "Завершенные",
          value: 0,
          color: "info",
          icon: "fas fa-check-circle fa-2x"
        },
        {
          key: "total_items",
          title: "Всего позиций",
          value: 0,
          color: "secondary",
          icon: "fas fa-cubes fa-2x"
        },
        {
          key: "total_proposals",
          title: "Предложений",
          value: 0,
          color: "dark",
          icon: "fas fa-handshake fa-2x"
        }
      ];
    },
    debouncedSearch() {
      clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        this.loadRequests(1);
      }, 500);
    },
    loadPage(page) {
      var _a;
      if (page >= 1 && page <= (((_a = this.requests.meta) == null ? void 0 : _a.last_page) || 1)) {
        this.loadRequests(page);
      }
    },
    loadPageFromUrl(url) {
      if (!url) return;
      try {
        const page = new URL(url).searchParams.get("page");
        this.loadRequests(parseInt(page) || 1);
      } catch (error) {
        console.error("Ошибка парсинга URL:", error);
      }
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
    formatDateTime(dateString) {
      if (!dateString) return "—";
      try {
        return new Date(dateString).toLocaleDateString("ru-RU") + " " + new Date(dateString).toLocaleTimeString("ru-RU", { hour: "2-digit", minute: "2-digit" });
      } catch (error) {
        console.error("Ошибка форматирования даты/времени:", error);
        return "—";
      }
    },
    formatCurrency(amount) {
      if (!amount && amount !== 0) return "—";
      try {
        return new Intl.NumberFormat("ru-RU").format(amount);
      } catch (error) {
        console.error("Ошибка форматирования валюты:", error);
        return "—";
      }
    },
    getStatusColor(status) {
      const colors = {
        "active": "success",
        "processing": "warning",
        "completed": "primary",
        "cancelled": "danger",
        "draft": "secondary"
      };
      return colors[status] || "light";
    },
    getStatusText(status) {
      const texts = {
        "active": "Активна",
        "processing": "В процессе",
        "completed": "Завершена",
        "cancelled": "Отменена",
        "draft": "Черновик"
      };
      return texts[status] || status;
    },
    getProposalProgress(request) {
      if (!request.responses_count || !request.items_count) return 0;
      return Math.min(100, request.responses_count / Math.max(1, request.items_count) * 100);
    }
  },
  mounted() {
    console.log("🔄 Компонент RentalRequestList монтирован");
    this.loadRequests();
    const savedViewMode = localStorage.getItem("rentalRequestsViewMode");
    if (savedViewMode) {
      this.viewMode = savedViewMode;
    }
  },
  watch: {
    viewMode(newVal) {
      localStorage.setItem("rentalRequestsViewMode", newVal);
    }
  }
};
const _hoisted_1 = { class: "rental-request-list" };
const _hoisted_2 = { class: "page-header d-flex justify-content-between align-items-center mb-4" };
const _hoisted_3 = ["href"];
const _hoisted_4 = { class: "row mb-4" };
const _hoisted_5 = { class: "card-body" };
const _hoisted_6 = { class: "d-flex justify-content-between" };
const _hoisted_7 = { class: "text-xs font-weight-bold text-uppercase mb-1" };
const _hoisted_8 = { class: "h5 mb-0" };
const _hoisted_9 = { class: "col-auto" };
const _hoisted_10 = { class: "card mb-4" };
const _hoisted_11 = { class: "card-body" };
const _hoisted_12 = { class: "row g-3" };
const _hoisted_13 = { class: "col-md-3" };
const _hoisted_14 = { class: "col-md-3" };
const _hoisted_15 = { class: "col-md-3" };
const _hoisted_16 = { class: "col-md-3" };
const _hoisted_17 = { class: "d-flex justify-content-between align-items-center mb-3" };
const _hoisted_18 = { class: "mb-0" };
const _hoisted_19 = {
  class: "btn-group",
  role: "group"
};
const _hoisted_20 = {
  key: 0,
  class: "card"
};
const _hoisted_21 = { class: "card-body p-0" };
const _hoisted_22 = { class: "table-responsive" };
const _hoisted_23 = { class: "table table-hover mb-0" };
const _hoisted_24 = ["href"];
const _hoisted_25 = { class: "text-muted" };
const _hoisted_26 = { key: 0 };
const _hoisted_27 = {
  key: 1,
  class: "badge bg-warning"
};
const _hoisted_28 = { class: "badge bg-secondary" };
const _hoisted_29 = { class: "badge bg-primary rounded-pill" };
const _hoisted_30 = { class: "btn-group btn-group-sm" };
const _hoisted_31 = ["href"];
const _hoisted_32 = {
  key: 0,
  class: "card-footer"
};
const _hoisted_33 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_34 = { class: "pagination mb-0" };
const _hoisted_35 = ["onClick", "innerHTML"];
const _hoisted_36 = {
  key: 1,
  class: "row"
};
const _hoisted_37 = { class: "card h-100 rental-request-card" };
const _hoisted_38 = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_39 = { class: "text-muted" };
const _hoisted_40 = { class: "card-body" };
const _hoisted_41 = { class: "card-title" };
const _hoisted_42 = { class: "card-text small text-muted" };
const _hoisted_43 = { class: "request-meta mb-3" };
const _hoisted_44 = { class: "d-flex justify-content-between small mb-1" };
const _hoisted_45 = { class: "text-end" };
const _hoisted_46 = { class: "d-flex justify-content-between small mb-1" };
const _hoisted_47 = { class: "d-flex justify-content-between small mb-1" };
const _hoisted_48 = { class: "d-flex justify-content-between small" };
const _hoisted_49 = {
  class: "progress mb-2",
  style: { "height": "6px" }
};
const _hoisted_50 = ["title"];
const _hoisted_51 = { class: "card-footer bg-transparent" };
const _hoisted_52 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_53 = { class: "badge bg-primary rounded-pill" };
const _hoisted_54 = { class: "btn-group btn-group-sm" };
const _hoisted_55 = ["href"];
const _hoisted_56 = { class: "text-muted d-block mt-1" };
const _hoisted_57 = {
  key: 2,
  class: "text-center py-5"
};
const _hoisted_58 = ["href"];
const _hoisted_59 = {
  key: 3,
  class: "text-center py-5"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      _cache[13] || (_cache[13] = createBaseVNode("h1", { class: "page-title" }, "Мои заявки на аренду", -1)),
      createBaseVNode("a", {
        href: $data.createRoute,
        class: "btn btn-primary"
      }, [..._cache[12] || (_cache[12] = [
        createBaseVNode("i", { class: "fas fa-plus me-2" }, null, -1),
        createTextVNode("Создать заявку ", -1)
      ])], 8, _hoisted_3)
    ]),
    createBaseVNode("div", _hoisted_4, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.statistics, (stat) => {
        return openBlock(), createElementBlock("div", {
          class: "col-xl-2 col-md-4",
          key: stat.key
        }, [
          createBaseVNode("div", {
            class: normalizeClass(["card text-white mb-4", `bg-${stat.color}`])
          }, [
            createBaseVNode("div", _hoisted_5, [
              createBaseVNode("div", _hoisted_6, [
                createBaseVNode("div", null, [
                  createBaseVNode("div", _hoisted_7, toDisplayString(stat.title), 1),
                  createBaseVNode("div", _hoisted_8, toDisplayString(stat.value), 1)
                ]),
                createBaseVNode("div", _hoisted_9, [
                  createBaseVNode("i", {
                    class: normalizeClass(stat.icon)
                  }, null, 2)
                ])
              ])
            ])
          ], 2)
        ]);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_10, [
      createBaseVNode("div", _hoisted_11, [
        createBaseVNode("div", _hoisted_12, [
          createBaseVNode("div", _hoisted_13, [
            _cache[15] || (_cache[15] = createBaseVNode("label", { class: "form-label" }, "Статус заявки", -1)),
            withDirectives(createBaseVNode("select", {
              class: "form-select",
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.filters.status = $event),
              onChange: _cache[1] || (_cache[1] = (...args) => $options.loadRequests && $options.loadRequests(...args))
            }, [..._cache[14] || (_cache[14] = [
              createStaticVNode('<option value="all" data-v-c33d2922>Все статусы</option><option value="active" data-v-c33d2922>Активные</option><option value="processing" data-v-c33d2922>В процессе</option><option value="completed" data-v-c33d2922>Завершенные</option><option value="cancelled" data-v-c33d2922>Отмененные</option>', 5)
            ])], 544), [
              [vModelSelect, $data.filters.status]
            ])
          ]),
          createBaseVNode("div", _hoisted_14, [
            _cache[16] || (_cache[16] = createBaseVNode("label", { class: "form-label" }, "Поиск", -1)),
            withDirectives(createBaseVNode("input", {
              type: "text",
              class: "form-control",
              "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.filters.search = $event),
              placeholder: "По названию или описанию",
              onInput: _cache[3] || (_cache[3] = (...args) => $options.debouncedSearch && $options.debouncedSearch(...args))
            }, null, 544), [
              [vModelText, $data.filters.search]
            ])
          ]),
          createBaseVNode("div", _hoisted_15, [
            _cache[18] || (_cache[18] = createBaseVNode("label", { class: "form-label" }, "Сортировка", -1)),
            withDirectives(createBaseVNode("select", {
              class: "form-select",
              "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.filters.sort = $event),
              onChange: _cache[5] || (_cache[5] = (...args) => $options.loadRequests && $options.loadRequests(...args))
            }, [..._cache[17] || (_cache[17] = [
              createBaseVNode("option", { value: "newest" }, "Сначала новые", -1),
              createBaseVNode("option", { value: "oldest" }, "Сначала старые", -1),
              createBaseVNode("option", { value: "proposals" }, "По количеству предложений", -1),
              createBaseVNode("option", { value: "budget" }, "По размеру бюджета", -1)
            ])], 544), [
              [vModelSelect, $data.filters.sort]
            ])
          ]),
          createBaseVNode("div", _hoisted_16, [
            _cache[20] || (_cache[20] = createBaseVNode("label", { class: "form-label" }, "Элементов на странице", -1)),
            withDirectives(createBaseVNode("select", {
              class: "form-select",
              "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.filters.per_page = $event),
              onChange: _cache[7] || (_cache[7] = (...args) => $options.loadRequests && $options.loadRequests(...args))
            }, [..._cache[19] || (_cache[19] = [
              createBaseVNode("option", { value: "10" }, "10", -1),
              createBaseVNode("option", { value: "15" }, "15", -1),
              createBaseVNode("option", { value: "25" }, "25", -1),
              createBaseVNode("option", { value: "50" }, "50", -1)
            ])], 544), [
              [vModelSelect, $data.filters.per_page]
            ])
          ])
        ])
      ])
    ]),
    createBaseVNode("div", _hoisted_17, [
      createBaseVNode("h5", _hoisted_18, "Найдено заявок: " + toDisplayString($data.requests.total), 1),
      createBaseVNode("div", _hoisted_19, [
        createBaseVNode("button", {
          type: "button",
          class: normalizeClass(["btn btn-outline-primary", { active: $data.viewMode === "table" }]),
          onClick: _cache[8] || (_cache[8] = ($event) => $data.viewMode = "table")
        }, [..._cache[21] || (_cache[21] = [
          createBaseVNode("i", { class: "fas fa-table" }, null, -1)
        ])], 2),
        createBaseVNode("button", {
          type: "button",
          class: normalizeClass(["btn btn-outline-primary", { active: $data.viewMode === "cards" }]),
          onClick: _cache[9] || (_cache[9] = ($event) => $data.viewMode = "cards")
        }, [..._cache[22] || (_cache[22] = [
          createBaseVNode("i", { class: "fas fa-th-large" }, null, -1)
        ])], 2)
      ])
    ]),
    $data.viewMode === "table" ? (openBlock(), createElementBlock("div", _hoisted_20, [
      createBaseVNode("div", _hoisted_21, [
        createBaseVNode("div", _hoisted_22, [
          createBaseVNode("table", _hoisted_23, [
            _cache[26] || (_cache[26] = createBaseVNode("thead", { class: "table-light" }, [
              createBaseVNode("tr", null, [
                createBaseVNode("th", null, "ID"),
                createBaseVNode("th", null, "Название заявки"),
                createBaseVNode("th", null, "Категории"),
                createBaseVNode("th", null, "Позиций"),
                createBaseVNode("th", null, "Период аренды"),
                createBaseVNode("th", null, "Бюджет"),
                createBaseVNode("th", null, "Статус"),
                createBaseVNode("th", null, "Предложения"),
                createBaseVNode("th", null, "Действия")
              ])
            ], -1)),
            createBaseVNode("tbody", null, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($data.requests.data, (request) => {
                return openBlock(), createElementBlock("tr", {
                  key: request.id
                }, [
                  createBaseVNode("td", null, "#" + toDisplayString(request.id), 1),
                  createBaseVNode("td", null, [
                    createBaseVNode("a", {
                      href: `/lessee/rental-requests/${request.id}`,
                      class: "text-decoration-none fw-bold"
                    }, toDisplayString(request.title), 9, _hoisted_24),
                    _cache[23] || (_cache[23] = createBaseVNode("br", null, null, -1)),
                    createBaseVNode("small", _hoisted_25, toDisplayString(request.description), 1)
                  ]),
                  createBaseVNode("td", null, [
                    request.items && request.items.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_26, [
                      (openBlock(true), createElementBlock(Fragment, null, renderList(request.items, (item) => {
                        var _a;
                        return openBlock(), createElementBlock("span", {
                          key: item.id,
                          class: "badge bg-light text-dark mb-1 me-1"
                        }, toDisplayString(((_a = item.category) == null ? void 0 : _a.name) || "Без категории"), 1);
                      }), 128))
                    ])) : (openBlock(), createElementBlock("span", _hoisted_27, "Нет позиций"))
                  ]),
                  createBaseVNode("td", null, [
                    createBaseVNode("span", _hoisted_28, toDisplayString(request.items_count), 1)
                  ]),
                  createBaseVNode("td", null, [
                    createBaseVNode("small", null, [
                      createTextVNode(toDisplayString($options.formatDate(request.rental_period_start)), 1),
                      _cache[24] || (_cache[24] = createBaseVNode("br", null, null, -1)),
                      createTextVNode(" " + toDisplayString($options.formatDate(request.rental_period_end)), 1)
                    ])
                  ]),
                  createBaseVNode("td", null, [
                    createBaseVNode("strong", null, toDisplayString($options.formatCurrency(request.calculated_budget_from || request.budget_from)) + " ₽", 1)
                  ]),
                  createBaseVNode("td", null, [
                    createBaseVNode("span", {
                      class: normalizeClass(["badge", `bg-${$options.getStatusColor(request.status)}`])
                    }, toDisplayString($options.getStatusText(request.status)), 3)
                  ]),
                  createBaseVNode("td", null, [
                    createBaseVNode("span", _hoisted_29, toDisplayString(request.responses_count), 1)
                  ]),
                  createBaseVNode("td", null, [
                    createBaseVNode("div", _hoisted_30, [
                      createBaseVNode("a", {
                        href: `/lessee/rental-requests/${request.id}`,
                        class: "btn btn-outline-primary",
                        title: "Просмотр"
                      }, [..._cache[25] || (_cache[25] = [
                        createBaseVNode("i", { class: "fas fa-eye" }, null, -1)
                      ])], 8, _hoisted_31)
                    ])
                  ])
                ]);
              }), 128))
            ])
          ])
        ])
      ]),
      $data.requests.meta ? (openBlock(), createElementBlock("div", _hoisted_32, [
        createBaseVNode("div", _hoisted_33, [
          createBaseVNode("div", null, " Показано с " + toDisplayString($data.requests.meta.from) + " по " + toDisplayString($data.requests.meta.to) + " из " + toDisplayString($data.requests.meta.total) + " записей ", 1),
          createBaseVNode("nav", null, [
            createBaseVNode("ul", _hoisted_34, [
              createBaseVNode("li", {
                class: normalizeClass(["page-item", { disabled: !$data.requests.links.prev }])
              }, [
                createBaseVNode("a", {
                  class: "page-link",
                  href: "#",
                  onClick: _cache[10] || (_cache[10] = withModifiers(($event) => $options.loadPage($data.requests.meta.current_page - 1), ["prevent"]))
                }, " Назад ")
              ], 2),
              (openBlock(true), createElementBlock(Fragment, null, renderList($data.requests.meta.links, (page) => {
                return openBlock(), createElementBlock("li", {
                  class: normalizeClass(["page-item", { active: page.active, disabled: !page.url }]),
                  key: page.label
                }, [
                  createBaseVNode("a", {
                    class: "page-link",
                    href: "#",
                    onClick: withModifiers(($event) => $options.loadPageFromUrl(page.url), ["prevent"]),
                    innerHTML: page.label
                  }, null, 8, _hoisted_35)
                ], 2);
              }), 128)),
              createBaseVNode("li", {
                class: normalizeClass(["page-item", { disabled: !$data.requests.links.next }])
              }, [
                createBaseVNode("a", {
                  class: "page-link",
                  href: "#",
                  onClick: _cache[11] || (_cache[11] = withModifiers(($event) => $options.loadPage($data.requests.meta.current_page + 1), ["prevent"]))
                }, " Вперед ")
              ], 2)
            ])
          ])
        ])
      ])) : createCommentVNode("", true)
    ])) : createCommentVNode("", true),
    $data.viewMode === "cards" ? (openBlock(), createElementBlock("div", _hoisted_36, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.requests.data, (request) => {
        return openBlock(), createElementBlock("div", {
          class: "col-xl-4 col-lg-6 col-md-6 mb-4",
          key: request.id
        }, [
          createBaseVNode("div", _hoisted_37, [
            createBaseVNode("div", _hoisted_38, [
              createBaseVNode("span", {
                class: normalizeClass(["badge", `bg-${$options.getStatusColor(request.status)}`])
              }, toDisplayString($options.getStatusText(request.status)), 3),
              createBaseVNode("small", _hoisted_39, "#" + toDisplayString(request.id), 1)
            ]),
            createBaseVNode("div", _hoisted_40, [
              createBaseVNode("h6", _hoisted_41, toDisplayString(request.title), 1),
              createBaseVNode("p", _hoisted_42, toDisplayString(request.description), 1),
              createBaseVNode("div", _hoisted_43, [
                createBaseVNode("div", _hoisted_44, [
                  _cache[27] || (_cache[27] = createBaseVNode("span", null, "Категории:", -1)),
                  createBaseVNode("div", _hoisted_45, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList(request.items, (item) => {
                      var _a;
                      return openBlock(), createElementBlock("span", {
                        key: item.id,
                        class: "badge bg-light text-dark d-block mb-1"
                      }, toDisplayString(((_a = item.category) == null ? void 0 : _a.name) || "Без категории"), 1);
                    }), 128))
                  ])
                ]),
                createBaseVNode("div", _hoisted_46, [
                  _cache[28] || (_cache[28] = createBaseVNode("span", null, "Период:", -1)),
                  createBaseVNode("strong", null, toDisplayString($options.formatDate(request.rental_period_start)) + " - " + toDisplayString($options.formatDate(request.rental_period_end)), 1)
                ]),
                createBaseVNode("div", _hoisted_47, [
                  _cache[29] || (_cache[29] = createBaseVNode("span", null, "Бюджет:", -1)),
                  createBaseVNode("strong", null, toDisplayString($options.formatCurrency(request.calculated_budget_from || request.budget_from)) + " ₽", 1)
                ]),
                createBaseVNode("div", _hoisted_48, [
                  _cache[30] || (_cache[30] = createBaseVNode("span", null, "Позиций:", -1)),
                  createBaseVNode("strong", null, toDisplayString(request.items_count), 1)
                ])
              ]),
              createBaseVNode("div", _hoisted_49, [
                createBaseVNode("div", {
                  class: "progress-bar bg-success",
                  role: "progressbar",
                  style: normalizeStyle(`width: ${$options.getProposalProgress(request)}%`),
                  title: `${request.responses_count} предложений из ${request.items_count} позиций`
                }, null, 12, _hoisted_50)
              ])
            ]),
            createBaseVNode("div", _hoisted_51, [
              createBaseVNode("div", _hoisted_52, [
                createBaseVNode("div", null, [
                  createBaseVNode("span", _hoisted_53, toDisplayString(request.responses_count), 1),
                  _cache[31] || (_cache[31] = createBaseVNode("small", { class: "text-muted ms-1" }, "предложений", -1))
                ]),
                createBaseVNode("div", _hoisted_54, [
                  createBaseVNode("a", {
                    href: `/lessee/rental-requests/${request.id}`,
                    class: "btn btn-outline-primary",
                    title: "Просмотр"
                  }, [..._cache[32] || (_cache[32] = [
                    createBaseVNode("i", { class: "fas fa-eye" }, null, -1)
                  ])], 8, _hoisted_55)
                ])
              ]),
              createBaseVNode("small", _hoisted_56, " Создана: " + toDisplayString($options.formatDateTime(request.created_at)), 1)
            ])
          ])
        ]);
      }), 128))
    ])) : createCommentVNode("", true),
    $data.requests.data && $data.requests.data.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_57, [
      _cache[34] || (_cache[34] = createBaseVNode("i", { class: "fas fa-clipboard-list fa-4x text-muted mb-3" }, null, -1)),
      _cache[35] || (_cache[35] = createBaseVNode("h4", null, "Заявки не найдены", -1)),
      _cache[36] || (_cache[36] = createBaseVNode("p", { class: "text-muted" }, "Попробуйте изменить параметры фильтрации", -1)),
      createBaseVNode("a", {
        href: $data.createRoute,
        class: "btn btn-primary"
      }, [..._cache[33] || (_cache[33] = [
        createBaseVNode("i", { class: "fas fa-plus me-2" }, null, -1),
        createTextVNode("Создать первую заявку ", -1)
      ])], 8, _hoisted_58)
    ])) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_59, [..._cache[37] || (_cache[37] = [
      createBaseVNode("div", {
        class: "spinner-border text-primary",
        role: "status"
      }, [
        createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
      ], -1),
      createBaseVNode("p", { class: "mt-2" }, "Загрузка заявок...", -1)
    ])])) : createCommentVNode("", true)
  ]);
}
const RentalRequestList = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-c33d2922"]]);
console.log("🟢 rental-requests.js - Инициализация Vue приложения для заявок");
document.addEventListener("DOMContentLoaded", function() {
  console.log("🚀 DOM готов для Vue приложения заявок");
  const appContainer = document.getElementById("rental-request-app");
  if (appContainer) {
    console.log("✅ Найден контейнер для Vue приложения заявок");
    try {
      const app = createApp(RentalRequestList);
      app.mount("#rental-request-app");
      console.log("🎉 Vue приложение заявок успешно смонтировано");
    } catch (error) {
      console.error("❌ Ошибка монтирования Vue приложения:", error);
    }
  } else {
    console.log("❌ Контейнер #rental-request-app не найден");
  }
});
