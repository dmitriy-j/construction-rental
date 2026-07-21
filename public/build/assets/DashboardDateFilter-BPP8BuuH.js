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
import { C as Chart$1, e as LineController, f as BarController, D as DoughnutController } from "./chart-glRV5hiV.js";
import { y as defineComponent, z as shallowRef, l as watch, A as h, B as version, k as ref, m as onMounted, C as onUnmounted, D as toRaw, E as nextTick, G as isProxy, a as createElementBlock, o as openBlock, b as createBaseVNode, d as createCommentVNode, F as Fragment, r as renderList, n as normalizeClass, t as toDisplayString, w as withDirectives, j as vModelText } from "./runtime-dom.esm-bundler-B1SmakJY.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const CommonProps = {
  data: {
    type: Object,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  },
  plugins: {
    type: Array,
    default: () => []
  },
  datasetIdKey: {
    type: String,
    default: "label"
  },
  updateMode: {
    type: String,
    default: void 0
  }
};
const A11yProps = {
  ariaLabel: {
    type: String
  },
  ariaDescribedby: {
    type: String
  }
};
const Props = __spreadValues(__spreadValues({
  type: {
    type: String,
    required: true
  },
  destroyDelay: {
    type: Number,
    default: 0
    // No delay by default
  }
}, CommonProps), A11yProps);
const compatProps = version[0] === "2" ? (internals, props) => Object.assign(internals, {
  attrs: props
}) : (internals, props) => Object.assign(internals, props);
function toRawIfProxy(obj) {
  return isProxy(obj) ? toRaw(obj) : obj;
}
function cloneProxy(obj) {
  let src = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : obj;
  return isProxy(src) ? new Proxy(obj, {}) : obj;
}
function setOptions(chart, nextOptions) {
  const options = chart.options;
  if (options && nextOptions) {
    Object.assign(options, nextOptions);
  }
}
function setLabels(currentData, nextLabels) {
  currentData.labels = nextLabels;
}
function setDatasets(currentData, nextDatasets, datasetIdKey) {
  const addedDatasets = [];
  currentData.datasets = nextDatasets.map((nextDataset) => {
    const currentDataset = currentData.datasets.find((dataset) => dataset[datasetIdKey] === nextDataset[datasetIdKey]);
    if (!currentDataset || !nextDataset.data || addedDatasets.includes(currentDataset)) {
      return __spreadValues({}, nextDataset);
    }
    addedDatasets.push(currentDataset);
    Object.assign(currentDataset, nextDataset);
    return currentDataset;
  });
}
function cloneData(data, datasetIdKey) {
  const nextData = {
    labels: [],
    datasets: []
  };
  setLabels(nextData, data.labels);
  setDatasets(nextData, data.datasets, datasetIdKey);
  return nextData;
}
const Chart = defineComponent({
  props: Props,
  setup(props, param) {
    let { expose, slots } = param;
    const canvasRef = ref(null);
    const chartRef = shallowRef(null);
    expose({
      chart: chartRef
    });
    const renderChart = () => {
      if (!canvasRef.value) return;
      const { type, data, options, plugins, datasetIdKey } = props;
      const clonedData = cloneData(data, datasetIdKey);
      const proxiedData = cloneProxy(clonedData, data);
      chartRef.value = new Chart$1(canvasRef.value, {
        type,
        data: proxiedData,
        options: __spreadValues({}, options),
        plugins
      });
    };
    const destroyChart = () => {
      const chart = toRaw(chartRef.value);
      if (chart) {
        if (props.destroyDelay > 0) {
          setTimeout(() => {
            chart.destroy();
            chartRef.value = null;
          }, props.destroyDelay);
        } else {
          chart.destroy();
          chartRef.value = null;
        }
      }
    };
    const update = (chart) => {
      chart.update(props.updateMode);
    };
    onMounted(renderChart);
    onUnmounted(destroyChart);
    watch([
      () => props.options,
      () => props.data
    ], (param2, param1) => {
      let [nextOptionsProxy, nextDataProxy] = param2, [prevOptionsProxy, prevDataProxy] = param1;
      const chart = toRaw(chartRef.value);
      if (!chart) {
        return;
      }
      let shouldUpdate = false;
      if (nextOptionsProxy) {
        const nextOptions = toRawIfProxy(nextOptionsProxy);
        const prevOptions = toRawIfProxy(prevOptionsProxy);
        if (nextOptions && nextOptions !== prevOptions) {
          setOptions(chart, nextOptions);
          shouldUpdate = true;
        }
      }
      if (nextDataProxy) {
        const nextLabels = toRawIfProxy(nextDataProxy.labels);
        const prevLabels = toRawIfProxy(prevDataProxy.labels);
        const nextDatasets = toRawIfProxy(nextDataProxy.datasets);
        const prevDatasets = toRawIfProxy(prevDataProxy.datasets);
        if (nextLabels !== prevLabels) {
          setLabels(chart.config.data, nextLabels);
          shouldUpdate = true;
        }
        if (nextDatasets && nextDatasets !== prevDatasets) {
          setDatasets(chart.config.data, nextDatasets, props.datasetIdKey);
          shouldUpdate = true;
        }
      }
      if (shouldUpdate) {
        nextTick(() => {
          update(chart);
        });
      }
    }, {
      deep: true
    });
    return () => {
      return h("canvas", {
        role: "img",
        "aria-label": props.ariaLabel,
        "aria-describedby": props.ariaDescribedby,
        ref: canvasRef
      }, [
        h("p", {}, [
          slots.default ? slots.default() : ""
        ])
      ]);
    };
  }
});
function createTypedChart(type, registerables) {
  Chart$1.register(registerables);
  return defineComponent({
    props: CommonProps,
    setup(props, param) {
      let { expose } = param;
      const chart = shallowRef(null);
      const chartComponentRef = shallowRef(null);
      watch(() => {
        var _a, _b;
        return (_b = (_a = chartComponentRef.value) == null ? void 0 : _a.chart) != null ? _b : null;
      }, (nextChart) => {
        chart.value = nextChart;
      }, {
        flush: "sync"
      });
      expose({
        chart
      });
      const reforwardRef = (instance) => {
        chartComponentRef.value = instance;
      };
      return () => {
        return h(Chart, compatProps({
          ref: reforwardRef
        }, __spreadValues({
          type
        }, props)));
      };
    }
  });
}
const Bar = /* @__PURE__ */ createTypedChart("bar", BarController);
const Doughnut = /* @__PURE__ */ createTypedChart("doughnut", DoughnutController);
const Line = /* @__PURE__ */ createTypedChart("line", LineController);
const _sfc_main = {
  name: "DashboardDateFilter",
  props: {
    value: { type: String, default: "month" }
  },
  emits: ["change"],
  data() {
    return {
      period: this.value,
      showCustom: false,
      customFrom: "",
      customTo: "",
      options: [
        { value: "today", label: "Сегодня" },
        { value: "week", label: "Неделя" },
        { value: "month", label: "Месяц" },
        { value: "year", label: "Год" }
      ]
    };
  },
  watch: {
    value(val) {
      this.period = val;
    }
  },
  methods: {
    selectPeriod(val) {
      this.period = val;
      this.showCustom = false;
      this.$emit("change", { period: val, from: null, to: null });
    },
    toggleCustom() {
      this.showCustom = !this.showCustom;
      const now = /* @__PURE__ */ new Date();
      const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1e3);
      this.customTo = now.toISOString().split("T")[0];
      this.customFrom = monthAgo.toISOString().split("T")[0];
    },
    applyCustom() {
      if (this.customFrom && this.customTo) {
        this.$emit("change", { period: "custom", from: this.customFrom, to: this.customTo });
      }
    }
  }
};
const _hoisted_1 = { class: "dashboard-filter mb-4" };
const _hoisted_2 = {
  class: "btn-group",
  role: "group"
};
const _hoisted_3 = ["onClick"];
const _hoisted_4 = {
  key: 0,
  class: "custom-date-range mt-2 d-flex align-items-center gap-2"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.options, (opt) => {
        return openBlock(), createElementBlock("button", {
          key: opt.value,
          class: normalizeClass(["btn", "btn-sm", $data.period === opt.value ? "btn-primary" : "btn-outline-primary"]),
          onClick: ($event) => $options.selectPeriod(opt.value)
        }, toDisplayString(opt.label), 11, _hoisted_3);
      }), 128)),
      createBaseVNode("button", {
        class: normalizeClass(["btn", "btn-sm", $data.showCustom ? "btn-primary" : "btn-outline-primary"]),
        onClick: _cache[0] || (_cache[0] = (...args) => $options.toggleCustom && $options.toggleCustom(...args))
      }, [..._cache[4] || (_cache[4] = [
        createBaseVNode("i", { class: "bi bi-calendar3" }, null, -1)
      ])], 2)
    ]),
    $data.showCustom ? (openBlock(), createElementBlock("div", _hoisted_4, [
      withDirectives(createBaseVNode("input", {
        type: "date",
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.customFrom = $event),
        class: "form-control form-control-sm",
        style: { "max-width": "180px" }
      }, null, 512), [
        [vModelText, $data.customFrom]
      ]),
      _cache[5] || (_cache[5] = createBaseVNode("span", null, "—", -1)),
      withDirectives(createBaseVNode("input", {
        type: "date",
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.customTo = $event),
        class: "form-control form-control-sm",
        style: { "max-width": "180px" }
      }, null, 512), [
        [vModelText, $data.customTo]
      ]),
      createBaseVNode("button", {
        class: "btn btn-sm btn-primary",
        onClick: _cache[3] || (_cache[3] = (...args) => $options.applyCustom && $options.applyCustom(...args))
      }, "Применить")
    ])) : createCommentVNode("", true)
  ]);
}
const DashboardDateFilter = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-c00075cf"]]);
export {
  Bar as B,
  DashboardDateFilter as D,
  Line as L,
  Doughnut as a
};
