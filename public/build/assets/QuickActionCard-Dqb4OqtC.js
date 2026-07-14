import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { a as createElementBlock, o as openBlock, b as createBaseVNode, e as createCommentVNode, n as normalizeClass, t as toDisplayString } from "./runtime-dom.esm-bundler-BObhqzw5.js";
const _sfc_main = {
  name: "QuickActionCard",
  props: {
    title: {
      type: String,
      required: true
    },
    count: {
      type: [Number, String],
      default: null
    },
    icon: {
      type: String,
      default: "fas fa-cog"
    },
    color: {
      type: String,
      default: "primary",
      validator: (value) => ["primary", "success", "warning", "danger", "info", "secondary"].includes(value)
    },
    badge: {
      type: String,
      default: null
    },
    badgeType: {
      type: String,
      default: "primary"
    },
    disabled: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    showUpdateIndicator: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    colorClass() {
      return `color-${this.color}`;
    },
    badgeClass() {
      return `bg-${this.badgeType}`;
    },
    formattedCount() {
      if (this.count === null || this.count === void 0) return "";
      if (typeof this.count === "number") {
        if (this.count > 999) return "999+";
        if (this.count > 99) return "99+";
      }
      return this.count.toString();
    }
  },
  methods: {
    handleClick() {
      if (!this.disabled && !this.loading) {
        this.$emit("click");
      }
    }
  }
};
const _hoisted_1 = { class: "action-icon" };
const _hoisted_2 = {
  key: 1,
  class: "loading-spinner"
};
const _hoisted_3 = { class: "action-content" };
const _hoisted_4 = { class: "action-title" };
const _hoisted_5 = {
  key: 0,
  class: "action-count"
};
const _hoisted_6 = {
  key: 1,
  class: "action-count loading-skeleton"
};
const _hoisted_7 = {
  key: 0,
  class: "action-badge"
};
const _hoisted_8 = {
  key: 1,
  class: "update-indicator"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(["quick-action-card", [$options.colorClass, { "clickable": !$props.disabled && !$props.loading, "loading": $props.loading }]]),
    onClick: _cache[0] || (_cache[0] = (...args) => $options.handleClick && $options.handleClick(...args))
  }, [
    createBaseVNode("div", _hoisted_1, [
      !$props.loading ? (openBlock(), createElementBlock("i", {
        key: 0,
        class: normalizeClass($props.icon)
      }, null, 2)) : (openBlock(), createElementBlock("div", _hoisted_2, [..._cache[1] || (_cache[1] = [
        createBaseVNode("i", { class: "fas fa-spinner fa-spin" }, null, -1)
      ])]))
    ]),
    createBaseVNode("div", _hoisted_3, [
      createBaseVNode("div", _hoisted_4, toDisplayString($props.title), 1),
      !$props.loading && $props.count !== null && $props.count !== void 0 ? (openBlock(), createElementBlock("div", _hoisted_5, toDisplayString($options.formattedCount), 1)) : $props.loading ? (openBlock(), createElementBlock("div", _hoisted_6, "   ")) : createCommentVNode("", true)
    ]),
    $props.badge && !$props.loading ? (openBlock(), createElementBlock("div", _hoisted_7, [
      createBaseVNode("span", {
        class: normalizeClass(["badge", $options.badgeClass])
      }, toDisplayString($props.badge), 3)
    ])) : createCommentVNode("", true),
    $props.showUpdateIndicator ? (openBlock(), createElementBlock("div", _hoisted_8, [..._cache[2] || (_cache[2] = [
      createBaseVNode("i", { class: "fas fa-sync-alt fa-spin" }, null, -1)
    ])])) : createCommentVNode("", true)
  ], 2);
}
const QuickActionCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-26d7efdb"]]);
export {
  QuickActionCard as default
};
