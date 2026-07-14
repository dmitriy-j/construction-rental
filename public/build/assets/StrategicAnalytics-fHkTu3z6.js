import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { a as createElementBlock, o as openBlock, b as createBaseVNode, d as createTextVNode, e as createCommentVNode, t as toDisplayString, f as normalizeStyle, n as normalizeClass, F as Fragment, r as renderList } from "./runtime-dom.esm-bundler-BObhqzw5.js";
const _sfc_main = {
  name: "StrategicAnalytics",
  props: {
    conversionData: {
      type: Object,
      default: () => ({
        myConversionRate: 0,
        marketConversionRate: 0,
        trend: "stable"
      })
    },
    priceAnalytics: {
      type: Object,
      default: () => ({
        myAvgPrice: 0,
        marketAvgPrice: 0,
        priceDifferencePercent: 0
      })
    },
    recommendations: {
      type: Array,
      default: () => []
    }
  },
  methods: {
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
    },
    getComparisonClass(difference) {
      if (difference > 10) return "text-success";
      if (difference > -10) return "text-warning";
      return "text-danger";
    },
    getComparisonIcon(difference) {
      if (difference > 0) return "fas fa-arrow-up text-success me-1";
      if (difference < 0) return "fas fa-arrow-down text-danger me-1";
      return "fas fa-equals text-secondary me-1";
    },
    getComparisonText(difference) {
      if (difference > 0) return "выше";
      if (difference < 0) return "ниже";
      return "на уровне";
    }
  },
  mounted() {
    console.log("✅ StrategicAnalytics mounted");
  }
};
const _hoisted_1 = { class: "strategic-analytics" };
const _hoisted_2 = { class: "row" };
const _hoisted_3 = { class: "col-md-6" };
const _hoisted_4 = { class: "card h-100" };
const _hoisted_5 = { class: "card-body" };
const _hoisted_6 = { class: "conversion-metrics" };
const _hoisted_7 = { class: "metric-row" };
const _hoisted_8 = { class: "metric-value text-primary" };
const _hoisted_9 = {
  key: 0,
  class: "fas fa-arrow-up text-success ms-1"
};
const _hoisted_10 = {
  key: 1,
  class: "fas fa-arrow-down text-danger ms-1"
};
const _hoisted_11 = { class: "metric-row" };
const _hoisted_12 = { class: "metric-value text-secondary" };
const _hoisted_13 = {
  class: "progress mt-3",
  style: { "height": "8px" }
};
const _hoisted_14 = { class: "col-md-6" };
const _hoisted_15 = { class: "card h-100" };
const _hoisted_16 = { class: "card-body" };
const _hoisted_17 = { class: "price-metrics" };
const _hoisted_18 = { class: "metric-row" };
const _hoisted_19 = { class: "metric-value text-success" };
const _hoisted_20 = { class: "metric-row" };
const _hoisted_21 = { class: "metric-value text-secondary" };
const _hoisted_22 = { class: "price-comparison mt-3" };
const _hoisted_23 = { class: "row mt-4" };
const _hoisted_24 = { class: "col-12" };
const _hoisted_25 = { class: "card" };
const _hoisted_26 = { class: "card-body" };
const _hoisted_27 = { class: "recommendations-list" };
const _hoisted_28 = { class: "recommendation-content" };
const _hoisted_29 = {
  key: 0,
  class: "recommendation-actions"
};
const _hoisted_30 = ["onClick"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createBaseVNode("div", _hoisted_4, [
          _cache[2] || (_cache[2] = createBaseVNode("div", { class: "card-header" }, [
            createBaseVNode("h6", { class: "mb-0" }, [
              createBaseVNode("i", { class: "fas fa-chart-line me-2 text-primary" }),
              createTextVNode(" Конверсия предложений ")
            ])
          ], -1)),
          createBaseVNode("div", _hoisted_5, [
            createBaseVNode("div", _hoisted_6, [
              createBaseVNode("div", _hoisted_7, [
                _cache[0] || (_cache[0] = createBaseVNode("div", { class: "metric-label" }, "Ваша конверсия", -1)),
                createBaseVNode("div", _hoisted_8, [
                  createTextVNode(toDisplayString($props.conversionData.myConversionRate) + "% ", 1),
                  $props.conversionData.trend === "up" ? (openBlock(), createElementBlock("i", _hoisted_9)) : $props.conversionData.trend === "down" ? (openBlock(), createElementBlock("i", _hoisted_10)) : createCommentVNode("", true)
                ])
              ]),
              createBaseVNode("div", _hoisted_11, [
                _cache[1] || (_cache[1] = createBaseVNode("div", { class: "metric-label" }, "Средняя по рынку", -1)),
                createBaseVNode("div", _hoisted_12, toDisplayString($props.conversionData.marketConversionRate) + "% ", 1)
              ]),
              createBaseVNode("div", _hoisted_13, [
                createBaseVNode("div", {
                  class: "progress-bar bg-primary",
                  style: normalizeStyle({ width: $props.conversionData.myConversionRate + "%" })
                }, null, 4)
              ])
            ])
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_14, [
        createBaseVNode("div", _hoisted_15, [
          _cache[5] || (_cache[5] = createBaseVNode("div", { class: "card-header" }, [
            createBaseVNode("h6", { class: "mb-0" }, [
              createBaseVNode("i", { class: "fas fa-tag me-2 text-success" }),
              createTextVNode(" Ценовая аналитика ")
            ])
          ], -1)),
          createBaseVNode("div", _hoisted_16, [
            createBaseVNode("div", _hoisted_17, [
              createBaseVNode("div", _hoisted_18, [
                _cache[3] || (_cache[3] = createBaseVNode("div", { class: "metric-label" }, "Ваша средняя цена", -1)),
                createBaseVNode("div", _hoisted_19, toDisplayString($options.formatCurrency($props.priceAnalytics.myAvgPrice)), 1)
              ]),
              createBaseVNode("div", _hoisted_20, [
                _cache[4] || (_cache[4] = createBaseVNode("div", { class: "metric-label" }, "Средняя по рынку", -1)),
                createBaseVNode("div", _hoisted_21, toDisplayString($options.formatCurrency($props.priceAnalytics.marketAvgPrice)), 1)
              ]),
              createBaseVNode("div", _hoisted_22, [
                createBaseVNode("div", {
                  class: normalizeClass(["comparison-item", $options.getComparisonClass($props.priceAnalytics.priceDifferencePercent)])
                }, [
                  createBaseVNode("i", {
                    class: normalizeClass($options.getComparisonIcon($props.priceAnalytics.priceDifferencePercent))
                  }, null, 2),
                  createTextVNode(" " + toDisplayString($options.getComparisonText($props.priceAnalytics.priceDifferencePercent)) + " на " + toDisplayString(Math.abs($props.priceAnalytics.priceDifferencePercent)) + "% ", 1)
                ], 2)
              ])
            ])
          ])
        ])
      ])
    ]),
    createBaseVNode("div", _hoisted_23, [
      createBaseVNode("div", _hoisted_24, [
        createBaseVNode("div", _hoisted_25, [
          _cache[6] || (_cache[6] = createBaseVNode("div", { class: "card-header" }, [
            createBaseVNode("h6", { class: "mb-0" }, [
              createBaseVNode("i", { class: "fas fa-lightbulb me-2 text-warning" }),
              createTextVNode(" Рекомендации для роста ")
            ])
          ], -1)),
          createBaseVNode("div", _hoisted_26, [
            createBaseVNode("div", _hoisted_27, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($props.recommendations, (rec) => {
                return openBlock(), createElementBlock("div", {
                  key: rec.id,
                  class: normalizeClass(["recommendation-item", "priority-" + rec.priority])
                }, [
                  createBaseVNode("div", _hoisted_28, [
                    createBaseVNode("i", {
                      class: normalizeClass(rec.icon + " me-2")
                    }, null, 2),
                    createTextVNode(" " + toDisplayString(rec.message), 1)
                  ]),
                  rec.action ? (openBlock(), createElementBlock("div", _hoisted_29, [
                    createBaseVNode("button", {
                      onClick: rec.action,
                      class: "btn btn-sm btn-outline-primary"
                    }, toDisplayString(rec.actionText || "Применить"), 9, _hoisted_30)
                  ])) : createCommentVNode("", true)
                ], 2);
              }), 128))
            ])
          ])
        ])
      ])
    ])
  ]);
}
const StrategicAnalytics = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-2abade50"]]);
export {
  StrategicAnalytics as default
};
