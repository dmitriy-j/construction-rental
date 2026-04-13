import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { a as createElementBlock, o as openBlock, b as createBaseVNode, t as toDisplayString, d as createTextVNode, e as createCommentVNode, F as Fragment, r as renderList, n as normalizeClass } from "./runtime-dom.esm-bundler-BObhqzw5.js";
const _sfc_main = {
  name: "RealTimeAnalytics",
  props: {
    analytics: {
      type: Object,
      required: true,
      default: () => ({
        activeRequests: 0,
        newRequestsToday: 0,
        myActiveProposals: 0,
        conversionRate: 0,
        avgResponseTime: "0ч",
        marketShare: "0%"
      })
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      lastUpdate: (/* @__PURE__ */ new Date()).toLocaleTimeString("ru-RU")
    };
  },
  computed: {
    realTimeMetrics() {
      return [
        {
          id: 1,
          value: this.analytics.activeRequests || 0,
          label: "Активных заявок",
          description: "Доступно для ответа",
          trendClass: "text-primary",
          highlight: true
        },
        {
          id: 2,
          value: this.analytics.newRequestsToday || 0,
          label: "Новых сегодня",
          description: "За последние 24 часа",
          trendClass: "text-info"
        },
        {
          id: 3,
          value: this.analytics.myActiveProposals || 0,
          label: "Ваших предложений",
          description: "Ожидают ответа",
          trendClass: "text-warning"
        },
        {
          id: 4,
          value: (this.analytics.conversionRate || 0) + "%",
          label: "Конверсия",
          description: "Принятых предложений",
          trendClass: this.analytics.conversionRate > 0 ? "text-success" : "text-secondary"
        },
        {
          id: 5,
          value: this.analytics.avgResponseTime || "0ч",
          label: "Время ответа",
          description: "Среднее",
          trendClass: "text-secondary"
        },
        {
          id: 6,
          value: this.analytics.marketShare || "0%",
          label: "Доля рынка",
          description: "Ваша активность",
          trendClass: "text-success"
        }
      ];
    },
    quickActions() {
      return [
        {
          id: 1,
          label: "Быстрое предложение",
          icon: "fas fa-bolt me-1",
          class: "btn-outline-primary",
          handler: this.quickProposal
        },
        {
          id: 2,
          label: "Мои шаблоны",
          icon: "fas fa-file-alt me-1",
          class: "btn-outline-success",
          handler: this.showTemplates
        },
        {
          id: 3,
          label: "Избранные",
          icon: "fas fa-star me-1",
          class: "btn-outline-warning",
          handler: this.showFavorites
        },
        {
          id: 4,
          label: "Экспорт данных",
          icon: "fas fa-download me-1",
          class: "btn-outline-info",
          handler: this.exportData
        }
      ];
    }
  },
  methods: {
    quickProposal() {
      this.$emit("quick-action", "proposal");
    },
    showTemplates() {
      this.$emit("quick-action", "templates");
    },
    showFavorites() {
      this.$emit("quick-action", "favorites");
    },
    exportData() {
      this.$emit("quick-action", "export");
    }
  },
  watch: {
    analytics: {
      handler() {
        this.lastUpdate = (/* @__PURE__ */ new Date()).toLocaleTimeString("ru-RU");
      },
      deep: true
    }
  }
};
const _hoisted_1 = { class: "real-time-analytics" };
const _hoisted_2 = { class: "card" };
const _hoisted_3 = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_4 = {
  key: 0,
  class: "last-update text-muted small"
};
const _hoisted_5 = {
  key: 1,
  class: "last-update text-muted small"
};
const _hoisted_6 = { class: "card-body" };
const _hoisted_7 = {
  key: 0,
  class: "text-center py-3"
};
const _hoisted_8 = {
  key: 1,
  class: "row text-center"
};
const _hoisted_9 = { class: "metric-label" };
const _hoisted_10 = { class: "metric-description small text-muted" };
const _hoisted_11 = {
  key: 2,
  class: "quick-actions mt-3 pt-3 border-top"
};
const _hoisted_12 = { class: "row g-2" };
const _hoisted_13 = ["onClick"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        _cache[1] || (_cache[1] = createBaseVNode("h6", { class: "mb-0" }, "📈 Аналитика в реальном времени", -1)),
        !$props.loading ? (openBlock(), createElementBlock("div", _hoisted_4, " Обновлено: " + toDisplayString($data.lastUpdate), 1)) : (openBlock(), createElementBlock("div", _hoisted_5, [..._cache[0] || (_cache[0] = [
          createBaseVNode("i", { class: "fas fa-spinner fa-spin" }, null, -1),
          createTextVNode(" Загрузка... ", -1)
        ])]))
      ]),
      createBaseVNode("div", _hoisted_6, [
        $props.loading ? (openBlock(), createElementBlock("div", _hoisted_7, [..._cache[2] || (_cache[2] = [
          createBaseVNode("div", {
            class: "spinner-border spinner-border-sm",
            role: "status"
          }, null, -1),
          createBaseVNode("span", { class: "ms-2" }, "Загрузка данных...", -1)
        ])])) : (openBlock(), createElementBlock("div", _hoisted_8, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.realTimeMetrics, (metric) => {
            return openBlock(), createElementBlock("div", {
              class: "col-md-2 col-6 mb-3",
              key: metric.id
            }, [
              createBaseVNode("div", {
                class: normalizeClass(["metric-card", { "highlight": metric.highlight }])
              }, [
                createBaseVNode("div", {
                  class: normalizeClass(["metric-value", metric.trendClass])
                }, toDisplayString(metric.value), 3),
                createBaseVNode("div", _hoisted_9, toDisplayString(metric.label), 1),
                createBaseVNode("div", _hoisted_10, toDisplayString(metric.description), 1)
              ], 2)
            ]);
          }), 128))
        ])),
        !$props.loading ? (openBlock(), createElementBlock("div", _hoisted_11, [
          createBaseVNode("div", _hoisted_12, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.quickActions, (action) => {
              return openBlock(), createElementBlock("div", {
                class: "col-auto",
                key: action.id
              }, [
                createBaseVNode("button", {
                  class: normalizeClass(["btn btn-sm", action.class]),
                  onClick: action.handler
                }, [
                  createBaseVNode("i", {
                    class: normalizeClass(action.icon)
                  }, null, 2),
                  createTextVNode(" " + toDisplayString(action.label), 1)
                ], 10, _hoisted_13)
              ]);
            }), 128))
          ])
        ])) : createCommentVNode("", true)
      ])
    ])
  ]);
}
const RealTimeAnalytics = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-bf8da176"]]);
export {
  RealTimeAnalytics as default
};
