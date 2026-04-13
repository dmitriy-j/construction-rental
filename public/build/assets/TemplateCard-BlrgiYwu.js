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
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { a as createElementBlock, o as openBlock, b as createBaseVNode, t as toDisplayString, d as createTextVNode, n as normalizeClass } from "./runtime-dom.esm-bundler-BObhqzw5.js";
const _sfc_main = {
  name: "TemplateCard",
  props: {
    template: {
      type: Object,
      required: true
    }
  },
  methods: {
    getSuccessRateClass(rate) {
      if (rate >= 70) return "text-success";
      if (rate >= 40) return "text-warning";
      return "text-danger";
    },
    toggleTemplate() {
      return __async(this, null, function* () {
        try {
          const response = yield axios.put(`/api/lessor/proposal-templates/${this.template.id}`, {
            is_active: !this.template.is_active
          });
          this.$notify({
            title: "Успех",
            text: `Шаблон ${this.template.is_active ? "деактивирован" : "активирован"}`,
            type: "success"
          });
          this.$emit("updated");
        } catch (error) {
          console.error("Ошибка переключения шаблона:", error);
          this.$notify({
            title: "Ошибка",
            text: "Не удалось изменить статус шаблона",
            type: "error"
          });
        }
      });
    }
  }
};
const _hoisted_1 = { class: "template-card card h-100" };
const _hoisted_2 = { class: "card-body" };
const _hoisted_3 = { class: "d-flex justify-content-between align-items-start mb-3" };
const _hoisted_4 = { class: "card-title mb-0" };
const _hoisted_5 = { class: "form-check form-switch" };
const _hoisted_6 = ["checked"];
const _hoisted_7 = { class: "card-text text-muted small" };
const _hoisted_8 = { class: "template-meta mb-3" };
const _hoisted_9 = { class: "badge bg-secondary me-2" };
const _hoisted_10 = { class: "badge bg-primary" };
const _hoisted_11 = { class: "template-stats mb-3" };
const _hoisted_12 = { class: "row text-center" };
const _hoisted_13 = { class: "col-4" };
const _hoisted_14 = { class: "fw-bold" };
const _hoisted_15 = { class: "col-4" };
const _hoisted_16 = { class: "col-4" };
const _hoisted_17 = { class: "fw-bold" };
const _hoisted_18 = { class: "template-actions" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _a;
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createBaseVNode("h6", _hoisted_4, toDisplayString($props.template.name), 1),
        createBaseVNode("div", _hoisted_5, [
          createBaseVNode("input", {
            class: "form-check-input",
            type: "checkbox",
            checked: $props.template.is_active,
            onChange: _cache[0] || (_cache[0] = (...args) => $options.toggleTemplate && $options.toggleTemplate(...args))
          }, null, 40, _hoisted_6)
        ])
      ]),
      createBaseVNode("p", _hoisted_7, toDisplayString($props.template.description), 1),
      createBaseVNode("div", _hoisted_8, [
        createBaseVNode("span", _hoisted_9, toDisplayString((_a = $props.template.category) == null ? void 0 : _a.name), 1),
        createBaseVNode("span", _hoisted_10, [
          _cache[5] || (_cache[5] = createBaseVNode("i", { class: "fas fa-ruble-sign me-1" }, null, -1)),
          createTextVNode(" " + toDisplayString($props.template.proposed_price) + " / час ", 1)
        ])
      ]),
      createBaseVNode("div", _hoisted_11, [
        createBaseVNode("div", _hoisted_12, [
          createBaseVNode("div", _hoisted_13, [
            _cache[6] || (_cache[6] = createBaseVNode("small", { class: "text-muted" }, "Применений", -1)),
            createBaseVNode("div", _hoisted_14, toDisplayString($props.template.usage_count), 1)
          ]),
          createBaseVNode("div", _hoisted_15, [
            _cache[7] || (_cache[7] = createBaseVNode("small", { class: "text-muted" }, "Успешность", -1)),
            createBaseVNode("div", {
              class: normalizeClass(["fw-bold", $options.getSuccessRateClass($props.template.success_rate)])
            }, toDisplayString($props.template.success_rate) + "% ", 3)
          ]),
          createBaseVNode("div", _hoisted_16, [
            _cache[8] || (_cache[8] = createBaseVNode("small", { class: "text-muted" }, "Ответ", -1)),
            createBaseVNode("div", _hoisted_17, toDisplayString($props.template.response_time) + "ч", 1)
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_18, [
        createBaseVNode("button", {
          class: "btn btn-sm btn-outline-success me-2",
          onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("quick-apply", $props.template)),
          title: "Быстрое применение"
        }, [..._cache[9] || (_cache[9] = [
          createBaseVNode("i", { class: "fas fa-bolt" }, null, -1)
        ])]),
        createBaseVNode("button", {
          class: "btn btn-sm btn-outline-primary me-2",
          onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("apply", $props.template)),
          title: "Применить к заявке"
        }, [..._cache[10] || (_cache[10] = [
          createBaseVNode("i", { class: "fas fa-paper-plane" }, null, -1)
        ])]),
        createBaseVNode("button", {
          class: "btn btn-sm btn-outline-secondary me-2",
          onClick: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("edit", $props.template)),
          title: "Редактировать"
        }, [..._cache[11] || (_cache[11] = [
          createBaseVNode("i", { class: "fas fa-edit" }, null, -1)
        ])]),
        createBaseVNode("button", {
          class: "btn btn-sm btn-outline-danger",
          onClick: _cache[4] || (_cache[4] = ($event) => _ctx.$emit("delete", $props.template)),
          title: "Удалить"
        }, [..._cache[12] || (_cache[12] = [
          createBaseVNode("i", { class: "fas fa-trash" }, null, -1)
        ])])
      ])
    ])
  ]);
}
const TemplateCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-644b2ea8"]]);
export {
  TemplateCard as default
};
