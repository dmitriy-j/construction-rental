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
import { g as resolveComponent, a as createElementBlock, o as openBlock, b as createBaseVNode, d as createCommentVNode, i as createVNode, F as Fragment, r as renderList, t as toDisplayString, n as normalizeClass, x as createBlock, c as createApp } from "./runtime-dom.esm-bundler-B1SmakJY.js";
import { a as axios } from "./index-DM4mtReV.js";
import { D as DashboardDateFilter, a as Doughnut, L as Line } from "./DashboardDateFilter-BPP8BuuH.js";
import { C as Chart, a as CategoryScale, L as LinearScale, P as PointElement, b as LineElement, A as ArcElement, p as plugin_title, c as plugin_tooltip, d as plugin_legend, i as index } from "./chart-glRV5hiV.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
Chart.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, plugin_title, plugin_tooltip, plugin_legend, index);
const _sfc_main = {
  name: "AdminDashboard",
  components: { Line, Doughnut, DashboardDateFilter },
  data() {
    return {
      loading: true,
      period: "month",
      data: null,
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { callback: (v) => v.toLocaleString("ru-RU") + " ₽" } }
        }
      },
      doughnutOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom", labels: { boxWidth: 12 } } }
      }
    };
  },
  computed: {
    turnoverChartData() {
      var _a, _b;
      return ((_b = (_a = this.data) == null ? void 0 : _a.charts) == null ? void 0 : _b.turnover) || { labels: [], datasets: [] };
    },
    commissionChartData() {
      var _a, _b;
      return ((_b = (_a = this.data) == null ? void 0 : _a.charts) == null ? void 0 : _b.commission) || { labels: [], datasets: [] };
    },
    ordersByStatusChartData() {
      var _a, _b;
      return ((_b = (_a = this.data) == null ? void 0 : _a.charts) == null ? void 0 : _b.ordersByStatus) || { labels: [], datasets: [] };
    }
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      return __async(this, null, function* () {
        this.loading = true;
        try {
          const params = { period: this.period };
          const response = yield axios.get("/api/admin/dashboard", { params });
          this.data = response.data;
        } catch (error) {
          console.error("Error fetching admin dashboard data:", error);
        } finally {
          this.loading = false;
        }
      });
    },
    onFilterChange(filter) {
      this.period = filter.period;
      this.fetchData();
    },
    numberFormat(value) {
      return Number(value || 0).toLocaleString("ru-RU");
    },
    statusColor(status) {
      const colors = {
        pending: "warning",
        pending_approval: "warning",
        confirmed: "info",
        active: "success",
        completed: "secondary",
        cancelled: "danger",
        rejected: "danger",
        aggregated: "secondary",
        in_delivery: "info",
        extension_requested: "primary"
      };
      return colors[status] || "secondary";
    }
  }
};
const _hoisted_1 = { class: "admin-dashboard" };
const _hoisted_2 = { class: "d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2" };
const _hoisted_3 = {
  key: 0,
  class: "text-center py-5"
};
const _hoisted_4 = { class: "row g-3 mb-4" };
const _hoisted_5 = { class: "card kpi-card border-0 shadow-sm h-100" };
const _hoisted_6 = { class: "card-body" };
const _hoisted_7 = { class: "d-flex justify-content-between align-items-start" };
const _hoisted_8 = { class: "kpi-title mb-1" };
const _hoisted_9 = { class: "kpi-value mb-0" };
const _hoisted_10 = { class: "row g-3 mb-4" };
const _hoisted_11 = { class: "col-lg-6" };
const _hoisted_12 = { class: "card shadow-sm" };
const _hoisted_13 = { class: "card-body" };
const _hoisted_14 = {
  key: 1,
  class: "text-center py-4 text-muted"
};
const _hoisted_15 = { class: "col-lg-6" };
const _hoisted_16 = { class: "card shadow-sm" };
const _hoisted_17 = { class: "card-body" };
const _hoisted_18 = {
  key: 1,
  class: "text-center py-4 text-muted"
};
const _hoisted_19 = { class: "row g-3 mb-4" };
const _hoisted_20 = { class: "col-lg-4" };
const _hoisted_21 = { class: "card shadow-sm" };
const _hoisted_22 = { class: "card-body" };
const _hoisted_23 = {
  key: 1,
  class: "text-center py-4 text-muted"
};
const _hoisted_24 = { class: "col-lg-4" };
const _hoisted_25 = { class: "card shadow-sm h-100" };
const _hoisted_26 = { class: "card-body" };
const _hoisted_27 = { key: 0 };
const _hoisted_28 = { class: "text-truncate me-2" };
const _hoisted_29 = { class: "fw-bold text-success" };
const _hoisted_30 = {
  key: 1,
  class: "text-center py-4 text-muted"
};
const _hoisted_31 = { class: "col-lg-4" };
const _hoisted_32 = { class: "card shadow-sm h-100" };
const _hoisted_33 = { class: "card-body" };
const _hoisted_34 = { key: 0 };
const _hoisted_35 = { class: "text-truncate me-2" };
const _hoisted_36 = { class: "fw-bold text-primary" };
const _hoisted_37 = {
  key: 1,
  class: "text-center py-4 text-muted"
};
const _hoisted_38 = { class: "card shadow-sm" };
const _hoisted_39 = { class: "card-body p-0" };
const _hoisted_40 = { class: "table-responsive" };
const _hoisted_41 = { class: "table table-hover mb-0" };
const _hoisted_42 = { class: "fw-bold" };
const _hoisted_43 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_DashboardDateFilter = resolveComponent("DashboardDateFilter");
  const _component_Line = resolveComponent("Line");
  const _component_Doughnut = resolveComponent("Doughnut");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      _cache[0] || (_cache[0] = createBaseVNode("h1", { class: "h3 mb-0" }, "Панель управления", -1)),
      createVNode(_component_DashboardDateFilter, {
        value: $data.period,
        onChange: $options.onFilterChange
      }, null, 8, ["value", "onChange"])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_3, [..._cache[1] || (_cache[1] = [
      createBaseVNode("div", {
        class: "spinner-border text-primary",
        role: "status"
      }, [
        createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
      ], -1)
    ])])) : createCommentVNode("", true),
    !$data.loading && $data.data ? (openBlock(), createElementBlock(Fragment, { key: 1 }, [
      createBaseVNode("div", _hoisted_4, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.data.kpi, (kpi, index2) => {
          return openBlock(), createElementBlock("div", {
            key: index2,
            class: "col-xl-2 col-lg-3 col-md-4 col-sm-6"
          }, [
            createBaseVNode("div", _hoisted_5, [
              createBaseVNode("div", _hoisted_6, [
                createBaseVNode("div", _hoisted_7, [
                  createBaseVNode("div", null, [
                    createBaseVNode("p", _hoisted_8, toDisplayString(kpi.title), 1),
                    createBaseVNode("h4", _hoisted_9, toDisplayString(kpi.value), 1)
                  ]),
                  createBaseVNode("div", {
                    class: normalizeClass("kpi-icon bg-" + kpi.color + "-subtle rounded-3 p-2")
                  }, [
                    createBaseVNode("i", {
                      class: normalizeClass("bi " + kpi.icon + " text-" + kpi.color + " fs-4")
                    }, null, 2)
                  ], 2)
                ])
              ])
            ])
          ]);
        }), 128))
      ]),
      createBaseVNode("div", _hoisted_10, [
        createBaseVNode("div", _hoisted_11, [
          createBaseVNode("div", _hoisted_12, [
            _cache[2] || (_cache[2] = createBaseVNode("div", { class: "card-header bg-transparent border-bottom-0 pt-3 pb-0" }, [
              createBaseVNode("h5", { class: "mb-0" }, "Оборот по дням")
            ], -1)),
            createBaseVNode("div", _hoisted_13, [
              $data.data.charts.turnover.labels.length ? (openBlock(), createBlock(_component_Line, {
                key: 0,
                data: $options.turnoverChartData,
                options: $data.chartOptions,
                height: 250
              }, null, 8, ["data", "options"])) : (openBlock(), createElementBlock("div", _hoisted_14, "Нет данных за выбранный период"))
            ])
          ])
        ]),
        createBaseVNode("div", _hoisted_15, [
          createBaseVNode("div", _hoisted_16, [
            _cache[3] || (_cache[3] = createBaseVNode("div", { class: "card-header bg-transparent border-bottom-0 pt-3 pb-0" }, [
              createBaseVNode("h5", { class: "mb-0" }, "Комиссия платформы")
            ], -1)),
            createBaseVNode("div", _hoisted_17, [
              $data.data.charts.commission.labels.length ? (openBlock(), createBlock(_component_Line, {
                key: 0,
                data: $options.commissionChartData,
                options: $data.chartOptions,
                height: 250
              }, null, 8, ["data", "options"])) : (openBlock(), createElementBlock("div", _hoisted_18, "Нет данных за выбранный период"))
            ])
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_19, [
        createBaseVNode("div", _hoisted_20, [
          createBaseVNode("div", _hoisted_21, [
            _cache[4] || (_cache[4] = createBaseVNode("div", { class: "card-header bg-transparent border-bottom-0 pt-3 pb-0" }, [
              createBaseVNode("h5", { class: "mb-0" }, "Заказы по статусам")
            ], -1)),
            createBaseVNode("div", _hoisted_22, [
              $data.data.charts.ordersByStatus.labels.length ? (openBlock(), createBlock(_component_Doughnut, {
                key: 0,
                data: $options.ordersByStatusChartData,
                options: $data.doughnutOptions,
                height: 250
              }, null, 8, ["data", "options"])) : (openBlock(), createElementBlock("div", _hoisted_23, "Нет данных"))
            ])
          ])
        ]),
        createBaseVNode("div", _hoisted_24, [
          createBaseVNode("div", _hoisted_25, [
            _cache[5] || (_cache[5] = createBaseVNode("div", { class: "card-header bg-transparent border-bottom-0 pt-3 pb-0" }, [
              createBaseVNode("h5", { class: "mb-0" }, "Топ-5 арендодателей")
            ], -1)),
            createBaseVNode("div", _hoisted_26, [
              $data.data.topLessors.length ? (openBlock(), createElementBlock("div", _hoisted_27, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.data.topLessors, (lessor, i) => {
                  return openBlock(), createElementBlock("div", {
                    key: i,
                    class: "d-flex justify-content-between align-items-center py-2 border-bottom"
                  }, [
                    createBaseVNode("span", _hoisted_28, toDisplayString(i + 1) + ". " + toDisplayString(lessor.name), 1),
                    createBaseVNode("span", _hoisted_29, toDisplayString($options.numberFormat(lessor.total)) + " ₽", 1)
                  ]);
                }), 128))
              ])) : (openBlock(), createElementBlock("div", _hoisted_30, "Нет данных"))
            ])
          ])
        ]),
        createBaseVNode("div", _hoisted_31, [
          createBaseVNode("div", _hoisted_32, [
            _cache[6] || (_cache[6] = createBaseVNode("div", { class: "card-header bg-transparent border-bottom-0 pt-3 pb-0" }, [
              createBaseVNode("h5", { class: "mb-0" }, "Топ-5 арендаторов")
            ], -1)),
            createBaseVNode("div", _hoisted_33, [
              $data.data.topLessees.length ? (openBlock(), createElementBlock("div", _hoisted_34, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.data.topLessees, (lessee, i) => {
                  return openBlock(), createElementBlock("div", {
                    key: i,
                    class: "d-flex justify-content-between align-items-center py-2 border-bottom"
                  }, [
                    createBaseVNode("span", _hoisted_35, toDisplayString(i + 1) + ". " + toDisplayString(lessee.name), 1),
                    createBaseVNode("span", _hoisted_36, toDisplayString(lessee.total) + " заказ(ов)", 1)
                  ]);
                }), 128))
              ])) : (openBlock(), createElementBlock("div", _hoisted_37, "Нет данных"))
            ])
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_38, [
        _cache[9] || (_cache[9] = createBaseVNode("div", { class: "card-header bg-transparent d-flex justify-content-between align-items-center" }, [
          createBaseVNode("h5", { class: "mb-0" }, "Последние заказы")
        ], -1)),
        createBaseVNode("div", _hoisted_39, [
          createBaseVNode("div", _hoisted_40, [
            createBaseVNode("table", _hoisted_41, [
              _cache[8] || (_cache[8] = createBaseVNode("thead", { class: "table-light" }, [
                createBaseVNode("tr", null, [
                  createBaseVNode("th", null, "ID"),
                  createBaseVNode("th", null, "Арендатор"),
                  createBaseVNode("th", null, "Арендодатель"),
                  createBaseVNode("th", null, "Сумма"),
                  createBaseVNode("th", null, "Статус"),
                  createBaseVNode("th", null, "Дата")
                ])
              ], -1)),
              createBaseVNode("tbody", null, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.data.recentOrders, (order) => {
                  return openBlock(), createElementBlock("tr", {
                    key: order.id
                  }, [
                    createBaseVNode("td", null, "#" + toDisplayString(order.id), 1),
                    createBaseVNode("td", null, toDisplayString(order.lessee), 1),
                    createBaseVNode("td", null, toDisplayString(order.lessor), 1),
                    createBaseVNode("td", _hoisted_42, toDisplayString($options.numberFormat(order.amount)) + " ₽", 1),
                    createBaseVNode("td", null, [
                      createBaseVNode("span", {
                        class: normalizeClass("badge bg-" + $options.statusColor(order.status))
                      }, toDisplayString(order.status_text), 3)
                    ]),
                    createBaseVNode("td", null, toDisplayString(order.date), 1)
                  ]);
                }), 128)),
                !$data.data.recentOrders.length ? (openBlock(), createElementBlock("tr", _hoisted_43, [..._cache[7] || (_cache[7] = [
                  createBaseVNode("td", {
                    colspan: "6",
                    class: "text-center text-muted py-4"
                  }, "Нет заказов", -1)
                ])])) : createCommentVNode("", true)
              ])
            ])
          ])
        ])
      ])
    ], 64)) : createCommentVNode("", true)
  ]);
}
const AdminDashboard = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-9f2266ab"]]);
const appId = "admin-dashboard-app";
const element = document.getElementById(appId);
if (element && !element.__vue_app__) {
  const app = createApp(AdminDashboard);
  app.mount(element);
  console.log("✅ Admin dashboard mounted");
}
