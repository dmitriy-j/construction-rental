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
import RealTimeAnalytics from "./RealTimeAnalytics-CyTuuux6.js";
import StrategicAnalytics from "./StrategicAnalytics-fHkTu3z6.js";
import ProposalTemplates from "./ProposalTemplates-DKXx8w66.js";
import QuickActionCard from "./QuickActionCard-Dqb4OqtC.js";
import { C as Chart, r as registerables, S as Swal } from "./sweetalert2.esm.all-DkqDp_b4.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { a as createElementBlock, o as openBlock, h as createStaticVNode, b as createBaseVNode, t as toDisplayString, n as normalizeClass, d as createTextVNode, w as withDirectives, v as vModelSelect, g as resolveComponent, e as createCommentVNode, i as createVNode, F as Fragment, r as renderList } from "./runtime-dom.esm-bundler-BObhqzw5.js";
const _sfc_main$2 = {
  name: "ConversionTrendsChart",
  props: {
    data: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      chart: null,
      chartData: {
        labels: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
        datasets: [
          {
            label: "Ваша конверсия",
            data: [45, 52, 48, 55, 58, 62, 65, 63, 68, 72, 70, 75],
            borderColor: "#0d6efd",
            backgroundColor: "rgba(13, 110, 253, 0.1)",
            tension: 0.4,
            fill: true
          },
          {
            label: "Средняя по рынку",
            data: [40, 45, 42, 48, 50, 52, 55, 53, 56, 58, 57, 60],
            borderColor: "#6c757d",
            backgroundColor: "rgba(108, 117, 125, 0.1)",
            tension: 0.4,
            fill: true
          }
        ]
      }
    };
  },
  computed: {
    currentConversion() {
      const lastData = this.chartData.datasets[0].data;
      return lastData[lastData.length - 1] || 0;
    },
    trendValue() {
      const data = this.chartData.datasets[0].data;
      if (data.length < 2) return 0;
      return ((data[data.length - 1] - data[data.length - 2]) / data[data.length - 2] * 100).toFixed(1);
    },
    trendIcon() {
      return this.trendValue > 0 ? "↗" : this.trendValue < 0 ? "↘" : "→";
    },
    trendClass() {
      return this.trendValue > 0 ? "text-success" : this.trendValue < 0 ? "text-danger" : "text-secondary";
    }
  },
  mounted() {
    this.initChart();
  },
  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy();
    }
  },
  methods: {
    initChart() {
      if (this.chart) {
        this.chart.destroy();
      }
      const ctx = this.$refs.chartCanvas;
      if (!ctx) return;
      Chart.register(...registerables);
      this.chart = new Chart(ctx, {
        type: "line",
        data: this.chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: "index",
              intersect: false,
              backgroundColor: "rgba(0, 0, 0, 0.8)",
              titleColor: "#fff",
              bodyColor: "#fff",
              borderColor: "rgba(255, 255, 255, 0.1)",
              borderWidth: 1
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              ticks: {
                callback: function(value) {
                  return value + "%";
                },
                color: "#6c757d"
              },
              grid: {
                color: "rgba(0, 0, 0, 0.1)"
              }
            },
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: "#6c757d"
              }
            }
          },
          interaction: {
            mode: "nearest",
            axis: "x",
            intersect: false
          }
        }
      });
    }
  }
};
const _hoisted_1$2 = { class: "conversion-trends-chart" };
const _hoisted_2$2 = { class: "chart-container" };
const _hoisted_3$2 = { ref: "chartCanvas" };
const _hoisted_4$2 = { class: "chart-summary" };
const _hoisted_5$2 = { class: "summary-item" };
const _hoisted_6$2 = { class: "summary-value text-success" };
const _hoisted_7$1 = { class: "summary-item" };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$2, [
    _cache[2] || (_cache[2] = createStaticVNode('<div class="chart-header" data-v-9aef26fb><h6 class="chart-title" data-v-9aef26fb><i class="fas fa-chart-line me-2 text-primary" data-v-9aef26fb></i> Динамика конверсии </h6><div class="chart-legend" data-v-9aef26fb><span class="legend-item" data-v-9aef26fb><span class="legend-color my-conversion" data-v-9aef26fb></span> Ваша конверсия </span><span class="legend-item" data-v-9aef26fb><span class="legend-color market-conversion" data-v-9aef26fb></span> Рынок </span></div></div>', 1)),
    createBaseVNode("div", _hoisted_2$2, [
      createBaseVNode("canvas", _hoisted_3$2, null, 512)
    ]),
    createBaseVNode("div", _hoisted_4$2, [
      createBaseVNode("div", _hoisted_5$2, [
        _cache[0] || (_cache[0] = createBaseVNode("span", { class: "summary-label" }, "Текущая конверсия:", -1)),
        createBaseVNode("span", _hoisted_6$2, toDisplayString($options.currentConversion) + "%", 1)
      ]),
      createBaseVNode("div", _hoisted_7$1, [
        _cache[1] || (_cache[1] = createBaseVNode("span", { class: "summary-label" }, "Изменение за период:", -1)),
        createBaseVNode("span", {
          class: normalizeClass(["summary-value", $options.trendClass])
        }, toDisplayString($options.trendIcon) + " " + toDisplayString(Math.abs($options.trendValue)) + "% ", 3)
      ])
    ])
  ]);
}
const ConversionTrendsChart = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-9aef26fb"]]);
const _sfc_main$1 = {
  name: "PriceComparisonChart",
  props: {
    data: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      selectedPeriod: "month",
      chart: null,
      chartData: {
        labels: ["Экскаваторы", "Бульдозеры", "Краны", "Погрузчики", "Грузовики", "Компрессоры"],
        datasets: [
          {
            label: "Ваши цены",
            data: [3200, 2800, 4500, 1800, 2200, 1500],
            backgroundColor: "rgba(40, 167, 69, 0.8)",
            borderColor: "#28a745",
            borderWidth: 1
          },
          {
            label: "Средние по рынку",
            data: [2950, 2600, 4200, 1650, 2e3, 1350],
            backgroundColor: "rgba(108, 117, 125, 0.8)",
            borderColor: "#6c757d",
            borderWidth: 1
          }
        ]
      }
    };
  },
  computed: {
    priceDifference() {
      const myPrices = this.chartData.datasets[0].data;
      const marketPrices = this.chartData.datasets[1].data;
      const myAvg = myPrices.reduce((a, b) => a + b, 0) / myPrices.length;
      const marketAvg = marketPrices.reduce((a, b) => a + b, 0) / marketPrices.length;
      return ((myAvg - marketAvg) / marketAvg * 100).toFixed(1);
    },
    priceDifferenceText() {
      return this.priceDifference > 0 ? "выше" : "ниже";
    },
    priceDifferenceIcon() {
      return this.priceDifference > 0 ? "fa-arrow-up text-success" : "fa-arrow-down text-danger";
    },
    priceDifferenceClass() {
      return this.priceDifference > 10 ? "text-success" : this.priceDifference < -10 ? "text-danger" : "text-warning";
    }
  },
  mounted() {
    this.initChart();
  },
  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy();
    }
  },
  methods: {
    initChart() {
      if (this.chart) {
        this.chart.destroy();
      }
      const ctx = this.$refs.chartCanvas;
      if (!ctx) return;
      Chart.register(...registerables);
      this.chart = new Chart(ctx, {
        type: "bar",
        data: this.chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "top",
              labels: {
                color: "#495057",
                usePointStyle: true,
                padding: 20
              }
            },
            tooltip: {
              mode: "index",
              intersect: false,
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || "";
                  if (label) {
                    label += ": ";
                  }
                  label += new Intl.NumberFormat("ru-RU", {
                    style: "currency",
                    currency: "RUB",
                    minimumFractionDigits: 0
                  }).format(context.parsed.y);
                  label += "/час";
                  return label;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return new Intl.NumberFormat("ru-RU", {
                    style: "currency",
                    currency: "RUB",
                    minimumFractionDigits: 0
                  }).format(value);
                },
                color: "#6c757d"
              },
              grid: {
                color: "rgba(0, 0, 0, 0.1)"
              }
            },
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: "#6c757d",
                maxRotation: 45
              }
            }
          },
          interaction: {
            mode: "index",
            intersect: false
          }
        }
      });
    },
    updateChart() {
      console.log("Обновление графика для периода:", this.selectedPeriod);
      if (this.selectedPeriod === "quarter") {
        this.chartData.datasets[0].data = [3100, 2700, 4400, 1750, 2100, 1450];
        this.chartData.datasets[1].data = [2900, 2550, 4150, 1600, 1950, 1300];
      } else if (this.selectedPeriod === "year") {
        this.chartData.datasets[0].data = [3050, 2650, 4300, 1700, 2050, 1400];
        this.chartData.datasets[1].data = [2850, 2500, 4100, 1550, 1900, 1250];
      } else {
        this.chartData.datasets[0].data = [3200, 2800, 4500, 1800, 2200, 1500];
        this.chartData.datasets[1].data = [2950, 2600, 4200, 1650, 2e3, 1350];
      }
      this.initChart();
    }
  }
};
const _hoisted_1$1 = { class: "price-comparison-chart" };
const _hoisted_2$1 = { class: "chart-header" };
const _hoisted_3$1 = { class: "chart-filters" };
const _hoisted_4$1 = { class: "chart-container" };
const _hoisted_5$1 = { ref: "chartCanvas" };
const _hoisted_6$1 = { class: "chart-insights" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", _hoisted_2$1, [
      _cache[3] || (_cache[3] = createBaseVNode("h6", { class: "chart-title" }, [
        createBaseVNode("i", { class: "fas fa-tags me-2 text-success" }),
        createTextVNode(" Сравнение цен ")
      ], -1)),
      createBaseVNode("div", _hoisted_3$1, [
        withDirectives(createBaseVNode("select", {
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.selectedPeriod = $event),
          class: "form-select form-select-sm",
          onChange: _cache[1] || (_cache[1] = (...args) => $options.updateChart && $options.updateChart(...args))
        }, [..._cache[2] || (_cache[2] = [
          createBaseVNode("option", { value: "month" }, "За месяц", -1),
          createBaseVNode("option", { value: "quarter" }, "За квартал", -1),
          createBaseVNode("option", { value: "year" }, "За год", -1)
        ])], 544), [
          [vModelSelect, $data.selectedPeriod]
        ])
      ])
    ]),
    createBaseVNode("div", _hoisted_4$1, [
      createBaseVNode("canvas", _hoisted_5$1, null, 512)
    ]),
    createBaseVNode("div", _hoisted_6$1, [
      createBaseVNode("div", {
        class: normalizeClass(["insight-item", $options.priceDifferenceClass])
      }, [
        createBaseVNode("i", {
          class: normalizeClass(["fas", $options.priceDifferenceIcon])
        }, null, 2),
        createTextVNode(" Ваши цены " + toDisplayString($options.priceDifferenceText) + " на " + toDisplayString(Math.abs($options.priceDifference)) + "% ", 1)
      ], 2)
    ])
  ]);
}
const PriceComparisonChart = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-0bc33b54"]]);
const _sfc_main = {
  name: "AnalyticsDashboard",
  components: {
    RealTimeAnalytics,
    StrategicAnalytics,
    ProposalTemplates,
    QuickActionCard,
    ConversionTrendsChart,
    PriceComparisonChart
  },
  props: {
    initialData: {
      type: Object,
      default: () => ({})
    },
    categories: {
      type: Array,
      default: () => []
    },
    urgentRequests: {
      type: Array,
      default: () => []
    },
    templates: {
      type: Array,
      default: () => []
    },
    myProposalsCount: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      activeMode: "realtime",
      refreshing: false,
      loadingRealtime: false,
      loadingStrategic: false,
      loadingCounters: false,
      // ВСЕ ДАННЫЕ ИНИЦИАЛИЗИРУЕМ НУЛЯМИ - НИКАКИХ ФИКТИВНЫХ ДАННЫХ!
      dashboardCounters: {
        urgent_requests: 0,
        templates: 0,
        my_proposals: 0,
        active_requests: 0,
        last_updated: null
      },
      realTimeData: {
        activeRequests: 0,
        newRequestsToday: 0,
        myActiveProposals: 0,
        conversionRate: 0,
        avgResponseTime: "0ч",
        marketShare: "0%"
      },
      conversionData: {
        myConversionRate: 0,
        marketConversionRate: 0,
        trend: "stable"
      },
      priceAnalytics: {
        myAvgPrice: 0,
        marketAvgPrice: 0,
        priceDifferencePercent: 0
      },
      strategicRecommendations: [],
      criticalAlerts: [],
      conversionTrends: [],
      priceComparison: [],
      templateRecommendations: []
    };
  },
  computed: {
    urgentRequestsCount() {
      return this.urgentRequests.length || this.dashboardCounters.urgent_requests || 0;
    },
    templatesCount() {
      return this.templates.length || this.dashboardCounters.templates || 0;
    },
    myProposalsComputedCount() {
      return this.myProposalsCount || this.dashboardCounters.my_proposals || 0;
    }
  },
  methods: {
    refreshAllData() {
      return __async(this, null, function* () {
        this.refreshing = true;
        try {
          yield Promise.all([
            this.loadRealCounters(),
            this.loadRealTimeData(),
            this.loadStrategicData()
          ]);
          Swal.fire({
            title: "✅ Данные обновлены",
            text: `Обновлено: ${(/* @__PURE__ */ new Date()).toLocaleTimeString()}`,
            icon: "success",
            timer: 2e3,
            showConfirmButton: false,
            toast: true,
            position: "top-end"
          });
        } catch (error) {
          console.error("Ошибка обновления данных:", error);
          this.showErrorNotification("Не удалось обновить данные");
        } finally {
          this.refreshing = false;
        }
      });
    },
    loadRealCounters() {
      return __async(this, null, function* () {
        try {
          this.loadingCounters = true;
          console.log("📊 Загрузка реальных данных счетчиков...");
          const response = yield axios.get("/api/lessor/analytics/dashboard-counters");
          if (response.data.success) {
            this.dashboardCounters = __spreadProps(__spreadValues({}, response.data.data), {
              last_updated: (/* @__PURE__ */ new Date()).toISOString()
            });
            console.log("✅ Счетчики загружены:", this.dashboardCounters);
          } else {
            throw new Error(response.data.message || "Ошибка сервера");
          }
        } catch (error) {
          console.error("❌ Ошибка загрузки счетчиков:", error);
          this.showErrorNotification("Не удалось загрузить данные счетчиков");
          this.useOnlyRealData();
        } finally {
          this.loadingCounters = false;
        }
      });
    },
    loadRealTimeData() {
      return __async(this, null, function* () {
        try {
          this.loadingRealtime = true;
          console.log("🔄 Загрузка данных реального времени...");
          const response = yield axios.get("/api/lessor/analytics/realtime");
          if (response.data.success) {
            this.realTimeData = response.data.data;
            console.log("✅ Данные реального времени загружены:", this.realTimeData);
          } else {
            throw new Error(response.data.message || "Ошибка сервера");
          }
        } catch (error) {
          console.error("❌ Ошибка загрузки данных реального времени:", error);
          this.realTimeData = {
            activeRequests: 0,
            newRequestsToday: 0,
            myActiveProposals: 0,
            conversionRate: 0,
            avgResponseTime: "0ч",
            marketShare: "0%"
          };
        } finally {
          this.loadingRealtime = false;
        }
      });
    },
    loadStrategicData() {
      return __async(this, null, function* () {
        try {
          this.loadingStrategic = true;
          console.log("📈 Загрузка стратегической аналитики...");
          const response = yield axios.get("/api/lessor/analytics/strategic");
          if (response.data.success) {
            this.conversionData = response.data.data.conversion || {};
            this.priceAnalytics = response.data.data.pricing || {};
            this.strategicRecommendations = response.data.data.recommendations || [];
            this.criticalAlerts = response.data.data.alerts || [];
            console.log("✅ Стратегическая аналитика загружена");
          } else {
            throw new Error(response.data.message || "Ошибка сервера");
          }
        } catch (error) {
          console.error("❌ Ошибка загрузки стратегической аналитики:", error);
          this.conversionData = {
            myConversionRate: 0,
            marketConversionRate: 0,
            trend: "stable"
          };
          this.priceAnalytics = {
            myAvgPrice: 0,
            marketAvgPrice: 0,
            priceDifferencePercent: 0
          };
          this.strategicRecommendations = [];
          this.criticalAlerts = [];
        } finally {
          this.loadingStrategic = false;
        }
      });
    },
    useOnlyRealData() {
      this.dashboardCounters = {
        urgent_requests: this.urgentRequests.length || 0,
        templates: this.templates.length || 0,
        my_proposals: this.myProposalsCount || 0,
        active_requests: 0,
        last_updated: (/* @__PURE__ */ new Date()).toISOString()
      };
    },
    showErrorNotification(message) {
      Swal.fire({
        title: "❌ Ошибка загрузки",
        text: message,
        icon: "error",
        timer: 5e3,
        showConfirmButton: false,
        toast: true,
        position: "top-end"
      });
    },
    formatLastUpdated(timestamp) {
      if (!timestamp) return "";
      const date = new Date(timestamp);
      return date.toLocaleTimeString("ru-RU", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
      });
    },
    handleQuickAction(action) {
      console.log("Быстрое действие:", action);
      switch (action) {
        case "proposal":
          this.showQuickProposalModal();
          break;
        case "templates":
          this.showTemplates();
          break;
        case "favorites":
          this.showFavorites();
          break;
        case "export":
          this.exportAnalyticsData();
          break;
        case "refresh":
          this.refreshAllData();
          break;
      }
    },
    showTemplates() {
      this.activeMode = "templates";
    },
    showUrgentRequests() {
      this.$emit("show-urgent-requests");
    },
    showMyProposals() {
      this.$emit("show-my-proposals");
    },
    showAllRequests() {
      this.$emit("show-all-requests");
    },
    showQuickProposalModal() {
      this.$emit("quick-proposal");
    },
    showFavorites() {
      this.$emit("show-favorites");
    },
    handleTemplateApplied(templateData) {
      console.log("Шаблон применен:", templateData);
      this.dashboardCounters.my_proposals += 1;
      Swal.fire({
        title: "✅ Шаблон применен",
        text: `Шаблон "${templateData.template.name}" успешно применен`,
        icon: "success",
        timer: 3e3,
        showConfirmButton: false,
        toast: true,
        position: "top-end"
      });
    },
    handleTemplateSaved() {
      console.log("Шаблон сохранен");
      this.dashboardCounters.templates += 1;
      Swal.fire({
        title: "✅ Шаблон сохранен",
        text: "Новый шаблон успешно создан",
        icon: "success",
        timer: 3e3,
        showConfirmButton: false,
        toast: true,
        position: "top-end"
      });
    },
    exportAnalyticsData() {
      const data = {
        realTime: this.realTimeData,
        strategic: {
          conversion: this.conversionData,
          pricing: this.priceAnalytics
        },
        counters: this.dashboardCounters,
        exportDate: (/* @__PURE__ */ new Date()).toISOString()
      };
      const blob = new Blob([JSON.stringify(data, null, 2)], {
        type: "application/json"
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `analytics-dashboard-${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}.json`;
      a.click();
      URL.revokeObjectURL(url);
      Swal.fire({
        title: "📊 Экспорт завершен",
        text: "Данные аналитики успешно экспортированы",
        icon: "success",
        timer: 3e3,
        showConfirmButton: false,
        toast: true,
        position: "top-end"
      });
    },
    loadData() {
      return __async(this, null, function* () {
        try {
          yield Promise.all([
            this.loadRealCounters(),
            this.loadRealTimeData(),
            this.loadStrategicData()
          ]);
        } catch (error) {
          console.error("Ошибка загрузки данных аналитики:", error);
          this.showErrorNotification("Не удалось загрузить данные аналитики");
        }
      });
    }
  },
  watch: {
    urgentRequests: {
      handler(newRequests) {
        console.log("🔄 Обновление срочных заявок:", newRequests.length);
        this.dashboardCounters.urgent_requests = newRequests.length;
      },
      immediate: true,
      deep: true
    },
    templates: {
      handler(newTemplates) {
        console.log("🔄 Обновление шаблонов:", newTemplates.length);
        this.dashboardCounters.templates = newTemplates.length;
      },
      immediate: true,
      deep: true
    },
    myProposalsCount: {
      handler(newCount) {
        console.log("🔄 Обновление моих предложений:", newCount);
        this.dashboardCounters.my_proposals = newCount;
      },
      immediate: true
    }
  },
  mounted() {
    this.loadData();
    console.log("✅ AnalyticsDashboard mounted");
    console.log("📊 Начальные счетчики:", this.dashboardCounters);
    this.countersInterval = setInterval(() => {
      this.loadRealCounters();
    }, 12e4);
  },
  beforeUnmount() {
    if (this.countersInterval) {
      clearInterval(this.countersInterval);
    }
  }
};
const _hoisted_1 = { class: "analytics-dashboard" };
const _hoisted_2 = { class: "dashboard-header" };
const _hoisted_3 = { class: "dashboard-tabs" };
const _hoisted_4 = ["disabled"];
const _hoisted_5 = {
  key: 0,
  class: "realtime-mode"
};
const _hoisted_6 = { class: "quick-actions-grid mt-3" };
const _hoisted_7 = {
  key: 1,
  class: "strategic-mode"
};
const _hoisted_8 = {
  key: 0,
  class: "reports-section mt-4"
};
const _hoisted_9 = { class: "row" };
const _hoisted_10 = { class: "col-md-6" };
const _hoisted_11 = { class: "col-md-6" };
const _hoisted_12 = {
  key: 2,
  class: "templates-mode"
};
const _hoisted_13 = {
  key: 3,
  class: "critical-alerts mt-3"
};
const _hoisted_14 = ["onClick"];
const _hoisted_15 = {
  key: 4,
  class: "update-status mt-2"
};
const _hoisted_16 = { class: "text-muted" };
const _hoisted_17 = {
  key: 5,
  class: "loading-overlay"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_RealTimeAnalytics = resolveComponent("RealTimeAnalytics");
  const _component_QuickActionCard = resolveComponent("QuickActionCard");
  const _component_StrategicAnalytics = resolveComponent("StrategicAnalytics");
  const _component_ConversionTrendsChart = resolveComponent("ConversionTrendsChart");
  const _component_PriceComparisonChart = resolveComponent("PriceComparisonChart");
  const _component_ProposalTemplates = resolveComponent("ProposalTemplates");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      _cache[8] || (_cache[8] = createBaseVNode("h5", { class: "dashboard-title" }, [
        createBaseVNode("i", { class: "fas fa-chart-line me-2" }),
        createTextVNode(" Аналитика эффективности ")
      ], -1)),
      createBaseVNode("div", _hoisted_3, [
        createBaseVNode("button", {
          onClick: _cache[0] || (_cache[0] = ($event) => $data.activeMode = "realtime"),
          class: normalizeClass(["tab-button", { active: $data.activeMode === "realtime" }])
        }, [..._cache[4] || (_cache[4] = [
          createBaseVNode("i", { class: "fas fa-bolt me-1" }, null, -1),
          createTextVNode(" Оперативная ", -1)
        ])], 2),
        createBaseVNode("button", {
          onClick: _cache[1] || (_cache[1] = ($event) => $data.activeMode = "strategic"),
          class: normalizeClass(["tab-button", { active: $data.activeMode === "strategic" }])
        }, [..._cache[5] || (_cache[5] = [
          createBaseVNode("i", { class: "fas fa-chart-bar me-1" }, null, -1),
          createTextVNode(" Стратегическая ", -1)
        ])], 2),
        createBaseVNode("button", {
          onClick: _cache[2] || (_cache[2] = ($event) => $data.activeMode = "templates"),
          class: normalizeClass(["tab-button", { active: $data.activeMode === "templates" }])
        }, [..._cache[6] || (_cache[6] = [
          createBaseVNode("i", { class: "fas fa-file-alt me-1" }, null, -1),
          createTextVNode(" Шаблоны ", -1)
        ])], 2),
        createBaseVNode("button", {
          onClick: _cache[3] || (_cache[3] = (...args) => $options.refreshAllData && $options.refreshAllData(...args)),
          class: "tab-button refresh-btn",
          disabled: $data.refreshing
        }, [
          createBaseVNode("i", {
            class: normalizeClass(["fas fa-sync", { "fa-spin": $data.refreshing }])
          }, null, 2),
          _cache[7] || (_cache[7] = createTextVNode(" Обновить ", -1))
        ], 8, _hoisted_4)
      ])
    ]),
    $data.activeMode === "realtime" ? (openBlock(), createElementBlock("div", _hoisted_5, [
      createVNode(_component_RealTimeAnalytics, {
        analytics: $data.realTimeData,
        loading: $data.loadingRealtime,
        onQuickAction: $options.handleQuickAction
      }, null, 8, ["analytics", "loading", "onQuickAction"]),
      createBaseVNode("div", _hoisted_6, [
        createVNode(_component_QuickActionCard, {
          title: "Срочные заявки",
          count: $data.dashboardCounters.urgent_requests || 0,
          icon: "fas fa-exclamation-circle",
          color: "danger",
          onClick: $options.showUrgentRequests,
          loading: $data.loadingCounters,
          description: "Новые за последние 2 часа"
        }, null, 8, ["count", "onClick", "loading"]),
        createVNode(_component_QuickActionCard, {
          title: "Активные шаблоны",
          count: $data.dashboardCounters.templates || 0,
          icon: "fas fa-file-alt",
          color: "primary",
          onClick: $options.showTemplates,
          loading: $data.loadingCounters,
          description: "Готовые предложения"
        }, null, 8, ["count", "onClick", "loading"]),
        createVNode(_component_QuickActionCard, {
          title: "Мои предложения",
          count: $data.dashboardCounters.my_proposals || 0,
          icon: "fas fa-paper-plane",
          color: "warning",
          onClick: $options.showMyProposals,
          loading: $data.loadingCounters,
          description: "Ожидают ответа"
        }, null, 8, ["count", "onClick", "loading"]),
        createVNode(_component_QuickActionCard, {
          title: "Всего заявок",
          count: $data.dashboardCounters.active_requests || 0,
          icon: "fas fa-list",
          color: "info",
          onClick: $options.showAllRequests,
          loading: $data.loadingCounters,
          description: "Активные на платформе"
        }, null, 8, ["count", "onClick", "loading"])
      ])
    ])) : $data.activeMode === "strategic" ? (openBlock(), createElementBlock("div", _hoisted_7, [
      createVNode(_component_StrategicAnalytics, {
        "conversion-data": $data.conversionData,
        "price-analytics": $data.priceAnalytics,
        recommendations: $data.strategicRecommendations,
        loading: $data.loadingStrategic
      }, null, 8, ["conversion-data", "price-analytics", "recommendations", "loading"]),
      !$data.loadingStrategic && $data.conversionTrends.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_8, [
        createBaseVNode("div", _hoisted_9, [
          createBaseVNode("div", _hoisted_10, [
            createVNode(_component_ConversionTrendsChart, { data: $data.conversionTrends }, null, 8, ["data"])
          ]),
          createBaseVNode("div", _hoisted_11, [
            createVNode(_component_PriceComparisonChart, { data: $data.priceComparison }, null, 8, ["data"])
          ])
        ])
      ])) : createCommentVNode("", true)
    ])) : createCommentVNode("", true),
    $data.activeMode === "templates" ? (openBlock(), createElementBlock("div", _hoisted_12, [
      createVNode(_component_ProposalTemplates, {
        categories: $props.categories,
        onTemplateApplied: $options.handleTemplateApplied,
        onTemplateSaved: $options.handleTemplateSaved
      }, null, 8, ["categories", "onTemplateApplied", "onTemplateSaved"])
    ])) : createCommentVNode("", true),
    $data.criticalAlerts.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_13, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.criticalAlerts, (alert) => {
        return openBlock(), createElementBlock("div", {
          key: alert.id,
          class: "alert alert-warning"
        }, [
          _cache[9] || (_cache[9] = createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1)),
          createTextVNode(" " + toDisplayString(alert.message) + " ", 1),
          alert.action ? (openBlock(), createElementBlock("button", {
            key: 0,
            onClick: alert.action,
            class: "btn btn-sm btn-outline-warning ms-2"
          }, toDisplayString(alert.actionText), 9, _hoisted_14)) : createCommentVNode("", true)
        ]);
      }), 128))
    ])) : createCommentVNode("", true),
    $data.dashboardCounters.last_updated ? (openBlock(), createElementBlock("div", _hoisted_15, [
      createBaseVNode("small", _hoisted_16, [
        _cache[10] || (_cache[10] = createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1)),
        createTextVNode(" Обновлено: " + toDisplayString($options.formatLastUpdated($data.dashboardCounters.last_updated)), 1)
      ])
    ])) : createCommentVNode("", true),
    $data.loadingRealtime || $data.loadingStrategic ? (openBlock(), createElementBlock("div", _hoisted_17, [..._cache[11] || (_cache[11] = [
      createBaseVNode("div", {
        class: "spinner-border text-primary",
        role: "status"
      }, [
        createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
      ], -1)
    ])])) : createCommentVNode("", true)
  ]);
}
const AnalyticsDashboard = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-f02d6275"]]);
export {
  AnalyticsDashboard as default
};
