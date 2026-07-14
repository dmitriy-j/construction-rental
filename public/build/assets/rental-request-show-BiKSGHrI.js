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
import { a as createElementBlock, o as openBlock, b as createBaseVNode, d as createTextVNode, e as createCommentVNode, t as toDisplayString, F as Fragment, r as renderList, n as normalizeClass, f as normalizeStyle, g as resolveComponent, i as createVNode, x as createBlock, c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import { a as axios } from "./index-DM4mtReV.js";
const _sfc_main$a = {
  name: "ProposalsList",
  props: {
    proposals: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ["proposal-rejected"],
  data() {
    return {
      isAddingToCart: false
    };
  },
  computed: {
    filteredProposals() {
      if (!this.proposals) return [];
      return this.proposals.filter((proposal) => {
        const isComment = proposal.status === "comment" || proposal.equipment_id === null;
        return !isComment && proposal.equipment_id;
      });
    },
    proposalsCount() {
      return this.filteredProposals.length;
    }
  },
  methods: {
    // 🔥 ИЗМЕНЯЕМ ЛОГИКУ ОТОБРАЖЕНИЯ ДОСТАВКИ
    hasDelivery(proposal) {
      if (!proposal.price_breakdown) return false;
      const pb = proposal.price_breakdown;
      return pb.delivery_breakdown && pb.delivery_breakdown.delivery_required || pb.delivery_breakdown && pb.delivery_breakdown.delivery_cost > 0 || pb.delivery_cost > 0;
    },
    shouldShowDelivery(proposal) {
      if (!proposal.price_breakdown) return false;
      const pb = proposal.price_breakdown;
      const deliveryBreakdown = pb.delivery_breakdown;
      if (!deliveryBreakdown) return false;
      return deliveryBreakdown.delivery_required || deliveryBreakdown.delivery_cost > 0 || deliveryBreakdown.distance_km > 0;
    },
    // 🔥 ОТЛАДОЧНЫЕ МЕТОДЫ
    checkDelivery(proposal) {
      console.log("🔍 Checking delivery for proposal:", proposal.id);
      console.log("📦 Full proposal:", proposal);
      console.log("💰 Price breakdown:", proposal.price_breakdown);
      if (!proposal.price_breakdown) {
        console.log("❌ No price_breakdown at all");
        return false;
      }
      const pb = proposal.price_breakdown;
      if (pb.delivery_breakdown) {
        console.log("✅ Found delivery_breakdown in root:", pb.delivery_breakdown);
        return true;
      }
      if (pb.delivery_cost !== void 0) {
        console.log("✅ Found delivery_cost in root:", pb.delivery_cost);
        return true;
      }
      console.log("❌ No delivery data found in any structure");
      return false;
    },
    getDeliveryDebugInfo(proposal) {
      const pb = proposal.price_breakdown;
      let info = "";
      if (pb.delivery_breakdown) {
        info = `Delivery breakdown: ${JSON.stringify(pb.delivery_breakdown)}`;
      } else if (pb.delivery_cost !== void 0) {
        info = `Delivery cost: ${pb.delivery_cost}`;
      }
      return info;
    },
    // 🔥 ОСНОВНЫЕ МЕТОДЫ ДОСТАВКИ
    getDeliveryCost(proposal) {
      if (!this.hasDelivery(proposal)) return 0;
      const pb = proposal.price_breakdown;
      if (pb.delivery_breakdown && pb.delivery_breakdown.delivery_cost) {
        return pb.delivery_breakdown.delivery_cost;
      }
      if (pb.delivery_cost) {
        return pb.delivery_cost;
      }
      return 0;
    },
    // 🔥 КОРРЕКТНЫЕ МЕТОДЫ ДЛЯ ЦЕН АРЕНДАТОРА
    getCustomerPricePerHour(proposal) {
      if (proposal.price_breakdown && proposal.price_breakdown.customer_price_per_unit) {
        return proposal.price_breakdown.customer_price_per_unit;
      }
      const workingHours = this.getWorkingHours(proposal);
      if (workingHours > 0 && proposal.proposed_quantity > 0) {
        return proposal.proposed_price / (workingHours * proposal.proposed_quantity);
      }
      return proposal.proposed_price;
    },
    // 🔥 ОБЩАЯ СТОИМОСТЬ ДЛЯ АРЕНДАТОРА
    getTotalCustomerPrice(proposal) {
      var _a;
      const basePrice = ((_a = proposal.price_breakdown) == null ? void 0 : _a.item_total_customer) || proposal.proposed_price;
      const deliveryCost = this.getDeliveryCost(proposal);
      return basePrice + deliveryCost;
    },
    getDeliveryDistance(proposal) {
      var _a, _b;
      return ((_b = (_a = proposal.price_breakdown) == null ? void 0 : _a.delivery_breakdown) == null ? void 0 : _b.distance_km) || 0;
    },
    getDeliveryRoute(proposal) {
      var _a;
      const delivery = (_a = proposal.price_breakdown) == null ? void 0 : _a.delivery_breakdown;
      if (!(delivery == null ? void 0 : delivery.from_location) || !(delivery == null ? void 0 : delivery.to_location)) return null;
      return `${delivery.from_location.name} → ${delivery.to_location.name}`;
    },
    getVehicleType(proposal) {
      var _a, _b;
      const vehicleType = (_b = (_a = proposal.price_breakdown) == null ? void 0 : _a.delivery_breakdown) == null ? void 0 : _b.vehicle_type;
      const types = {
        "light_truck": "Газель",
        "heavy_truck": "Фура",
        "lowbed_trailer": "Трал",
        "special_trailer": "Спецтранспорт"
      };
      return types[vehicleType] || vehicleType;
    },
    getWorkingHours(proposal) {
      if (proposal.price_breakdown && proposal.price_breakdown.working_hours) {
        return proposal.price_breakdown.working_hours;
      }
      if (proposal.rental_request) {
        const start = new Date(proposal.rental_request.rental_period_start);
        const end = new Date(proposal.rental_request.rental_period_end);
        const days = Math.ceil((end - start) / (1e3 * 3600 * 24)) + 1;
        const rentalConditions = proposal.rental_request.rental_conditions || {};
        const shiftHours = rentalConditions.hours_per_shift || 8;
        const shiftsPerDay = rentalConditions.shifts_per_day || 1;
        return days * shiftHours * shiftsPerDay;
      }
      return 1;
    },
    addToProposalCart(proposalId) {
      return __async(this, null, function* () {
        this.isAddingToCart = true;
        try {
          const response = yield fetch("/api/cart/proposal/add", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include",
            body: JSON.stringify({ proposal_id: proposalId })
          });
          const data = yield response.json();
          if (data.success) {
            this.showToast("success", "Предложение принято и добавлено в корзину");
            setTimeout(() => {
              window.location.reload();
            }, 1e3);
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          this.showToast("error", error.message);
        } finally {
          this.isAddingToCart = false;
        }
      });
    },
    getStatusBadgeClass(status) {
      const classes = {
        "accepted": "bg-success",
        "rejected": "bg-secondary",
        "counter_offer": "bg-warning text-dark",
        "pending": "bg-info"
      };
      return classes[status] || "bg-light text-dark";
    },
    showToast(type, message) {
      const toast = document.createElement("div");
      toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      toast.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
      toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
      document.body.appendChild(toast);
      setTimeout(() => {
        toast.remove();
      }, 5e3);
    },
    getEquipmentLink(equipmentId) {
      return `/catalog/${equipmentId}`;
    },
    getStatusText(status) {
      const statusMap = {
        "pending": "На рассмотрении",
        "accepted": "Принято",
        "rejected": "Отклонено",
        "counter_offer": "Контрпредложение"
      };
      return statusMap[status] || status;
    },
    formatDate(dateString) {
      if (!dateString) return "—";
      try {
        return new Date(dateString).toLocaleDateString("ru-RU", {
          day: "numeric",
          month: "long",
          year: "numeric"
        });
      } catch (error) {
        console.error("Date formatting error:", error);
        return "—";
      }
    },
    formatCurrency(amount) {
      if (!amount) return "0 ₽";
      return new Intl.NumberFormat("ru-RU", {
        style: "currency",
        currency: "RUB",
        minimumFractionDigits: 0
      }).format(amount);
    }
  }
};
const _hoisted_1$a = { class: "card" };
const _hoisted_2$a = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_3$a = { class: "card-title mb-0" };
const _hoisted_4$a = {
  key: 0,
  class: "badge bg-primary ms-2"
};
const _hoisted_5$9 = { class: "card-body" };
const _hoisted_6$7 = {
  key: 0,
  class: "text-center py-3"
};
const _hoisted_7$7 = {
  key: 1,
  class: "text-center py-4"
};
const _hoisted_8$6 = {
  key: 2,
  class: "proposals-list"
};
const _hoisted_9$5 = { class: "debug-info bg-dark text-white p-2 rounded mb-2 small" };
const _hoisted_10$5 = { key: 0 };
const _hoisted_11$5 = { class: "row align-items-start" };
const _hoisted_12$5 = { class: "col-md-8" };
const _hoisted_13$5 = { class: "d-flex align-items-start mb-2" };
const _hoisted_14$5 = { class: "flex-grow-1" };
const _hoisted_15$4 = { class: "d-flex align-items-center mb-2" };
const _hoisted_16$4 = {
  key: 0,
  class: "badge bg-warning"
};
const _hoisted_17$4 = { class: "equipment-info mb-2" };
const _hoisted_18$4 = { class: "mb-1" };
const _hoisted_19$3 = { key: 0 };
const _hoisted_20$3 = ["href"];
const _hoisted_21$3 = {
  key: 0,
  class: "text-muted ms-1"
};
const _hoisted_22$2 = {
  key: 0,
  class: "message-box bg-light p-2 rounded mb-2"
};
const _hoisted_23$1 = { class: "mb-0 text-dark small" };
const _hoisted_24$1 = { class: "price-quantity mb-2" };
const _hoisted_25$1 = { class: "d-flex align-items-center flex-wrap gap-2" };
const _hoisted_26$1 = { class: "text-success fw-bold fs-6" };
const _hoisted_27$1 = { class: "badge bg-primary" };
const _hoisted_28$1 = {
  key: 0,
  class: "badge bg-warning text-dark"
};
const _hoisted_29$1 = { class: "mt-2" };
const _hoisted_30$1 = { class: "text-muted" };
const _hoisted_31$1 = { class: "text-dark" };
const _hoisted_32$1 = { class: "text-muted ms-1" };
const _hoisted_33$1 = { key: 0 };
const _hoisted_34$1 = { class: "proposal-details small text-muted" };
const _hoisted_35$1 = { class: "d-flex flex-wrap gap-3" };
const _hoisted_36$1 = {
  key: 0,
  class: "badge bg-light text-dark"
};
const _hoisted_37$1 = {
  key: 1,
  class: "badge bg-secondary"
};
const _hoisted_38$1 = {
  key: 0,
  class: "delivery-check bg-warning p-2 rounded mb-2"
};
const _hoisted_39$1 = { class: "col-md-4" };
const _hoisted_40$1 = { class: "d-flex flex-column h-100" };
const _hoisted_41$1 = { class: "proposal-actions mb-3" };
const _hoisted_42$1 = ["onClick", "disabled"];
const _hoisted_43$1 = ["onClick"];
const _hoisted_44$1 = {
  key: 1,
  class: "text-center"
};
const _hoisted_45$1 = { class: "additional-actions mt-auto" };
const _hoisted_46$1 = { class: "d-grid gap-2" };
const _hoisted_47$1 = ["href"];
const _hoisted_48$1 = {
  key: 0,
  class: "delivery-info bg-light p-2 rounded mb-2"
};
const _hoisted_49$1 = { class: "d-flex justify-content-between align-items-center" };
const _hoisted_50$1 = { class: "text-warning" };
const _hoisted_51$1 = { class: "badge bg-info text-dark" };
const _hoisted_52$1 = {
  key: 0,
  class: "small text-muted mt-1"
};
function _sfc_render$a(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$a, [
    createBaseVNode("div", _hoisted_2$a, [
      createBaseVNode("h5", _hoisted_3$a, [
        _cache[0] || (_cache[0] = createBaseVNode("i", { class: "fas fa-handshake me-2" }, null, -1)),
        _cache[1] || (_cache[1] = createTextVNode(" Предложения от арендодателей ", -1)),
        $options.proposalsCount > 0 ? (openBlock(), createElementBlock("span", _hoisted_4$a, toDisplayString($options.proposalsCount), 1)) : createCommentVNode("", true)
      ])
    ]),
    createBaseVNode("div", _hoisted_5$9, [
      $props.loading ? (openBlock(), createElementBlock("div", _hoisted_6$7, [..._cache[2] || (_cache[2] = [
        createBaseVNode("div", {
          class: "spinner-border spinner-border-sm",
          role: "status"
        }, null, -1),
        createBaseVNode("p", { class: "mt-2 text-muted" }, "Загрузка предложений...", -1)
      ])])) : $options.proposalsCount === 0 ? (openBlock(), createElementBlock("div", _hoisted_7$7, [..._cache[3] || (_cache[3] = [
        createBaseVNode("i", { class: "fas fa-handshake fa-3x text-muted mb-3" }, null, -1),
        createBaseVNode("h5", null, "Пока нет предложений", -1),
        createBaseVNode("p", { class: "text-muted" }, "Арендодатели увидят вашу заявку и скоро предложат свои варианты", -1)
      ])])) : (openBlock(), createElementBlock("div", _hoisted_8$6, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.filteredProposals, (proposal) => {
          var _a, _b, _c;
          return openBlock(), createElementBlock("div", {
            key: proposal.id,
            class: "proposal-card mb-4 p-3 border rounded"
          }, [
            createBaseVNode("div", _hoisted_9$5, [
              _cache[4] || (_cache[4] = createBaseVNode("strong", null, "Отладка:", -1)),
              createTextVNode(" ID: " + toDisplayString(proposal.id) + ", Price: " + toDisplayString(proposal.proposed_price) + ", Has PB: " + toDisplayString(!!proposal.price_breakdown) + ", PB Type: " + toDisplayString(typeof proposal.price_breakdown) + " ", 1),
              proposal.price_breakdown ? (openBlock(), createElementBlock("div", _hoisted_10$5, " PB Keys: " + toDisplayString(Object.keys(proposal.price_breakdown)), 1)) : createCommentVNode("", true)
            ]),
            createBaseVNode("div", _hoisted_11$5, [
              createBaseVNode("div", _hoisted_12$5, [
                createBaseVNode("div", _hoisted_13$5, [
                  createBaseVNode("div", _hoisted_14$5, [
                    createBaseVNode("div", _hoisted_15$4, [
                      _cache[6] || (_cache[6] = createBaseVNode("h6", { class: "mb-0 me-2" }, [
                        createBaseVNode("i", { class: "fas fa-user me-1 text-muted" }),
                        createTextVNode("Арендодатель ")
                      ], -1)),
                      ((_b = (_a = proposal.lessor) == null ? void 0 : _a.company) == null ? void 0 : _b.average_rating) ? (openBlock(), createElementBlock("span", _hoisted_16$4, [
                        _cache[5] || (_cache[5] = createBaseVNode("i", { class: "fas fa-star me-1" }, null, -1)),
                        createTextVNode(" " + toDisplayString(proposal.lessor.company.average_rating.toFixed(1)), 1)
                      ])) : createCommentVNode("", true)
                    ]),
                    createBaseVNode("div", _hoisted_17$4, [
                      createBaseVNode("p", _hoisted_18$4, [
                        _cache[8] || (_cache[8] = createBaseVNode("i", { class: "fas fa-cube me-1 text-muted" }, null, -1)),
                        proposal.equipment ? (openBlock(), createElementBlock("span", _hoisted_19$3, [
                          createBaseVNode("a", {
                            href: $options.getEquipmentLink(proposal.equipment.id),
                            class: "text-decoration-none fw-bold equipment-link",
                            target: "_blank"
                          }, toDisplayString(proposal.equipment.title), 9, _hoisted_20$3),
                          proposal.equipment.brand || proposal.equipment.model ? (openBlock(), createElementBlock("span", _hoisted_21$3, " (" + toDisplayString(proposal.equipment.brand) + " " + toDisplayString(proposal.equipment.model) + ") ", 1)) : createCommentVNode("", true),
                          _cache[7] || (_cache[7] = createBaseVNode("i", { class: "fas fa-external-link-alt ms-1 small text-muted" }, null, -1))
                        ])) : createCommentVNode("", true)
                      ])
                    ]),
                    proposal.message && proposal.message.trim() ? (openBlock(), createElementBlock("div", _hoisted_22$2, [
                      createBaseVNode("p", _hoisted_23$1, [
                        _cache[9] || (_cache[9] = createBaseVNode("i", { class: "fas fa-comment me-1 text-muted" }, null, -1)),
                        createTextVNode(" " + toDisplayString(proposal.message), 1)
                      ])
                    ])) : createCommentVNode("", true),
                    createBaseVNode("div", _hoisted_24$1, [
                      createBaseVNode("div", _hoisted_25$1, [
                        createBaseVNode("span", _hoisted_26$1, toDisplayString($options.formatCurrency($options.getCustomerPricePerHour(proposal))) + " / час ", 1),
                        createBaseVNode("span", _hoisted_27$1, [
                          _cache[10] || (_cache[10] = createBaseVNode("i", { class: "fas fa-cube me-1" }, null, -1)),
                          createTextVNode(" " + toDisplayString(proposal.proposed_quantity) + " ед. ", 1)
                        ]),
                        $options.hasDelivery(proposal) ? (openBlock(), createElementBlock("span", _hoisted_28$1, [
                          _cache[11] || (_cache[11] = createBaseVNode("i", { class: "fas fa-truck me-1" }, null, -1)),
                          createTextVNode(" Доставка: " + toDisplayString($options.formatCurrency($options.getDeliveryCost(proposal))), 1)
                        ])) : createCommentVNode("", true)
                      ]),
                      createBaseVNode("div", _hoisted_29$1, [
                        createBaseVNode("span", _hoisted_30$1, [
                          _cache[13] || (_cache[13] = createTextVNode(" Общая стоимость: ", -1)),
                          createBaseVNode("strong", _hoisted_31$1, toDisplayString($options.formatCurrency($options.getTotalCustomerPrice(proposal))), 1),
                          createBaseVNode("span", _hoisted_32$1, [
                            createTextVNode(" (за " + toDisplayString($options.getWorkingHours(proposal)) + " часов ", 1),
                            $options.hasDelivery(proposal) ? (openBlock(), createElementBlock("span", _hoisted_33$1, " + доставка ")) : createCommentVNode("", true),
                            _cache[12] || (_cache[12] = createTextVNode(") ", -1))
                          ])
                        ])
                      ])
                    ]),
                    createBaseVNode("div", _hoisted_34$1, [
                      createBaseVNode("div", _hoisted_35$1, [
                        createBaseVNode("span", null, [
                          _cache[14] || (_cache[14] = createBaseVNode("i", { class: "fas fa-clock me-1" }, null, -1)),
                          createTextVNode(" " + toDisplayString($options.formatDate(proposal.created_at)), 1)
                        ]),
                        ((_c = proposal.equipment) == null ? void 0 : _c.category) ? (openBlock(), createElementBlock("span", _hoisted_36$1, toDisplayString(proposal.equipment.category.name), 1)) : createCommentVNode("", true),
                        $options.hasDelivery(proposal) ? (openBlock(), createElementBlock("span", _hoisted_37$1, [
                          _cache[15] || (_cache[15] = createBaseVNode("i", { class: "fas fa-truck me-1" }, null, -1)),
                          createTextVNode(" " + toDisplayString($options.getVehicleType(proposal)), 1)
                        ])) : createCommentVNode("", true)
                      ])
                    ])
                  ])
                ]),
                $options.checkDelivery(proposal) ? (openBlock(), createElementBlock("div", _hoisted_38$1, [
                  _cache[16] || (_cache[16] = createBaseVNode("strong", null, "Доставка найдена!", -1)),
                  createTextVNode(" " + toDisplayString($options.getDeliveryDebugInfo(proposal)), 1)
                ])) : createCommentVNode("", true)
              ]),
              createBaseVNode("div", _hoisted_39$1, [
                createBaseVNode("div", _hoisted_40$1, [
                  createBaseVNode("div", _hoisted_41$1, [
                    proposal.status === "pending" ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
                      createBaseVNode("button", {
                        class: "btn btn-success w-100 mb-2",
                        onClick: ($event) => $options.addToProposalCart(proposal.id),
                        disabled: $data.isAddingToCart
                      }, [
                        _cache[17] || (_cache[17] = createBaseVNode("i", { class: "fas fa-cart-plus me-1" }, null, -1)),
                        createTextVNode(" " + toDisplayString($data.isAddingToCart ? "Добавляется..." : "Добавить в корзину"), 1)
                      ], 8, _hoisted_42$1),
                      createBaseVNode("button", {
                        class: "btn btn-outline-danger w-100",
                        onClick: ($event) => _ctx.$emit("proposal-rejected", proposal.id)
                      }, [..._cache[18] || (_cache[18] = [
                        createBaseVNode("i", { class: "fas fa-times me-1" }, null, -1),
                        createTextVNode(" Отклонить ", -1)
                      ])], 8, _hoisted_43$1)
                    ], 64)) : (openBlock(), createElementBlock("div", _hoisted_44$1, [
                      createBaseVNode("span", {
                        class: normalizeClass(["badge status-badge w-100 py-2", $options.getStatusBadgeClass(proposal.status)])
                      }, toDisplayString($options.getStatusText(proposal.status)), 3)
                    ]))
                  ]),
                  createBaseVNode("div", _hoisted_45$1, [
                    createBaseVNode("div", _hoisted_46$1, [
                      createBaseVNode("a", {
                        href: $options.getEquipmentLink(proposal.equipment.id),
                        class: "btn btn-outline-primary btn-sm",
                        target: "_blank"
                      }, [..._cache[19] || (_cache[19] = [
                        createBaseVNode("i", { class: "fas fa-eye me-1" }, null, -1),
                        createTextVNode("Посмотреть технику ", -1)
                      ])], 8, _hoisted_47$1)
                    ])
                  ])
                ])
              ])
            ]),
            $options.hasDelivery(proposal) ? (openBlock(), createElementBlock("div", _hoisted_48$1, [
              createBaseVNode("div", _hoisted_49$1, [
                createBaseVNode("span", null, [
                  _cache[20] || (_cache[20] = createBaseVNode("i", { class: "fas fa-truck me-1 text-muted" }, null, -1)),
                  _cache[21] || (_cache[21] = createTextVNode(" Доставка: ", -1)),
                  createBaseVNode("strong", _hoisted_50$1, toDisplayString($options.formatCurrency($options.getDeliveryCost(proposal))), 1)
                ]),
                createBaseVNode("span", _hoisted_51$1, toDisplayString($options.getDeliveryDistance(proposal)) + " км ", 1)
              ]),
              $options.getDeliveryRoute(proposal) ? (openBlock(), createElementBlock("div", _hoisted_52$1, [
                _cache[22] || (_cache[22] = createBaseVNode("i", { class: "fas fa-route me-1" }, null, -1)),
                createTextVNode(" " + toDisplayString($options.getDeliveryRoute(proposal)), 1)
              ])) : createCommentVNode("", true)
            ])) : createCommentVNode("", true)
          ]);
        }), 128))
      ]))
    ])
  ]);
}
const ProposalsList = /* @__PURE__ */ _export_sfc(_sfc_main$a, [["render", _sfc_render$a], ["__scopeId", "data-v-6ebdaf22"]]);
const _sfc_main$9 = {
  name: "RequestStats",
  props: {
    request: {
      type: Object,
      required: true
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return "—";
      try {
        return new Date(dateString).toLocaleDateString("ru-RU");
      } catch (error) {
        return "—";
      }
    },
    formatCurrency(amount) {
      if (!amount && amount !== 0) return "—";
      try {
        return new Intl.NumberFormat("ru-RU").format(amount);
      } catch (error) {
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
  }
};
const _hoisted_1$9 = { class: "card mb-4" };
const _hoisted_2$9 = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_3$9 = { class: "card-body" };
const _hoisted_4$9 = { class: "stats-grid" };
const _hoisted_5$8 = { class: "stat-item" };
const _hoisted_6$6 = { class: "stat-value" };
const _hoisted_7$6 = { class: "stat-item" };
const _hoisted_8$5 = { class: "stat-value" };
const _hoisted_9$4 = { class: "stat-item" };
const _hoisted_10$4 = { class: "stat-value" };
const _hoisted_11$4 = {
  key: 0,
  class: "stat-item"
};
const _hoisted_12$4 = { class: "stat-value" };
const _hoisted_13$4 = { class: "stat-item" };
const _hoisted_14$4 = { class: "stat-value" };
const _hoisted_15$3 = { class: "stat-item" };
const _hoisted_16$3 = { class: "stat-value" };
const _hoisted_17$3 = {
  key: 0,
  class: "progress-section mt-3"
};
const _hoisted_18$3 = { class: "d-flex justify-content-between mb-2" };
const _hoisted_19$2 = { class: "text-muted" };
const _hoisted_20$2 = {
  class: "progress",
  style: { "height": "8px" }
};
const _hoisted_21$2 = ["title"];
function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$9, [
    createBaseVNode("div", _hoisted_2$9, [
      _cache[0] || (_cache[0] = createBaseVNode("h6", { class: "card-title mb-0" }, [
        createBaseVNode("i", { class: "fas fa-chart-bar me-2" }),
        createTextVNode("Статистика заявки ")
      ], -1)),
      createBaseVNode("span", {
        class: normalizeClass(["badge", `bg-${$options.getStatusColor($props.request.status)}`])
      }, toDisplayString($options.getStatusText($props.request.status)), 3)
    ]),
    createBaseVNode("div", _hoisted_3$9, [
      createBaseVNode("div", _hoisted_4$9, [
        createBaseVNode("div", _hoisted_5$8, [
          createBaseVNode("div", _hoisted_6$6, toDisplayString($props.request.views_count || 0), 1),
          _cache[1] || (_cache[1] = createBaseVNode("div", { class: "stat-label" }, [
            createBaseVNode("i", { class: "fas fa-eye me-1" }),
            createTextVNode("Просмотров ")
          ], -1))
        ]),
        createBaseVNode("div", _hoisted_7$6, [
          createBaseVNode("div", _hoisted_8$5, toDisplayString($props.request.responses_count || 0), 1),
          _cache[2] || (_cache[2] = createBaseVNode("div", { class: "stat-label" }, [
            createBaseVNode("i", { class: "fas fa-handshake me-1" }),
            createTextVNode("Предложений ")
          ], -1))
        ]),
        createBaseVNode("div", _hoisted_9$4, [
          createBaseVNode("div", _hoisted_10$4, toDisplayString($props.request.items_count || 0), 1),
          _cache[3] || (_cache[3] = createBaseVNode("div", { class: "stat-label" }, [
            createBaseVNode("i", { class: "fas fa-cube me-1" }),
            createTextVNode("Позиций ")
          ], -1))
        ]),
        $props.request.calculated_budget_from || $props.request.budget_from ? (openBlock(), createElementBlock("div", _hoisted_11$4, [
          createBaseVNode("div", _hoisted_12$4, toDisplayString($options.formatCurrency($props.request.calculated_budget_from || $props.request.budget_from)), 1),
          _cache[4] || (_cache[4] = createBaseVNode("div", { class: "stat-label" }, [
            createBaseVNode("i", { class: "fas fa-ruble-sign me-1" }),
            createTextVNode("Бюджет ")
          ], -1))
        ])) : createCommentVNode("", true),
        createBaseVNode("div", _hoisted_13$4, [
          createBaseVNode("div", _hoisted_14$4, toDisplayString($options.formatDate($props.request.rental_period_start)), 1),
          _cache[5] || (_cache[5] = createBaseVNode("div", { class: "stat-label" }, [
            createBaseVNode("i", { class: "fas fa-calendar-start me-1" }),
            createTextVNode("Начало ")
          ], -1))
        ]),
        createBaseVNode("div", _hoisted_15$3, [
          createBaseVNode("div", _hoisted_16$3, toDisplayString($options.formatDate($props.request.rental_period_end)), 1),
          _cache[6] || (_cache[6] = createBaseVNode("div", { class: "stat-label" }, [
            createBaseVNode("i", { class: "fas fa-calendar-end me-1" }),
            createTextVNode("Окончание ")
          ], -1))
        ])
      ]),
      $props.request.items_count > 0 ? (openBlock(), createElementBlock("div", _hoisted_17$3, [
        createBaseVNode("div", _hoisted_18$3, [
          _cache[7] || (_cache[7] = createBaseVNode("small", { class: "text-muted" }, "Заполнение предложений", -1)),
          createBaseVNode("small", _hoisted_19$2, toDisplayString($props.request.responses_count || 0) + "/" + toDisplayString($props.request.items_count), 1)
        ]),
        createBaseVNode("div", _hoisted_20$2, [
          createBaseVNode("div", {
            class: "progress-bar bg-success",
            role: "progressbar",
            style: normalizeStyle(`width: ${$options.getProposalProgress($props.request)}%`),
            title: `${$props.request.responses_count} предложений из ${$props.request.items_count} позиций`
          }, null, 12, _hoisted_21$2)
        ])
      ])) : createCommentVNode("", true)
    ])
  ]);
}
const RequestStats = /* @__PURE__ */ _export_sfc(_sfc_main$9, [["render", _sfc_render$9], ["__scopeId", "data-v-260c01e7"]]);
const _sfc_main$8 = {
  name: "RequestActions",
  props: {
    request: {
      type: Object,
      required: true
    }
  },
  emits: ["pause-request", "resume-request", "cancel-request", "edit-request"]
};
const _hoisted_1$8 = { class: "request-actions" };
const _hoisted_2$8 = { class: "card" };
const _hoisted_3$8 = { class: "card-body" };
const _hoisted_4$8 = {
  key: 0,
  class: "d-grid gap-2"
};
const _hoisted_5$7 = ["href"];
const _hoisted_6$5 = {
  key: 1,
  class: "d-grid gap-2"
};
const _hoisted_7$5 = ["href"];
const _hoisted_8$4 = {
  key: 2,
  class: "d-grid gap-2"
};
const _hoisted_9$3 = ["href"];
const _hoisted_10$3 = {
  key: 3,
  class: "alert alert-info"
};
const _hoisted_11$3 = {
  key: 4,
  class: "alert alert-warning"
};
const _hoisted_12$3 = {
  key: 5,
  class: "d-grid gap-2"
};
const _hoisted_13$3 = ["href"];
const _hoisted_14$3 = {
  key: 6,
  class: "alert alert-secondary"
};
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$8, [
    createBaseVNode("div", _hoisted_2$8, [
      _cache[21] || (_cache[21] = createBaseVNode("div", { class: "card-header" }, [
        createBaseVNode("h6", { class: "card-title mb-0" }, "Действия с заявкой")
      ], -1)),
      createBaseVNode("div", _hoisted_3$8, [
        $props.request.status === "active" ? (openBlock(), createElementBlock("div", _hoisted_4$8, [
          createBaseVNode("button", {
            class: "btn btn-warning btn-sm",
            onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("pause-request"))
          }, [..._cache[7] || (_cache[7] = [
            createBaseVNode("i", { class: "fas fa-pause me-2" }, null, -1),
            createTextVNode("Приостановить заявку ", -1)
          ])]),
          createBaseVNode("button", {
            class: "btn btn-outline-danger btn-sm",
            onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("cancel-request"))
          }, [..._cache[8] || (_cache[8] = [
            createBaseVNode("i", { class: "fas fa-times me-2" }, null, -1),
            createTextVNode("Отменить заявку ", -1)
          ])]),
          createBaseVNode("a", {
            href: `/lessee/rental-requests/${$props.request.id}/edit`,
            class: "btn btn-outline-primary btn-sm"
          }, [..._cache[9] || (_cache[9] = [
            createBaseVNode("i", { class: "fas fa-edit me-2" }, null, -1),
            createTextVNode("Редактировать ", -1)
          ])], 8, _hoisted_5$7)
        ])) : $props.request.status === "paused" ? (openBlock(), createElementBlock("div", _hoisted_6$5, [
          createBaseVNode("button", {
            class: "btn btn-success btn-sm",
            onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("resume-request"))
          }, [..._cache[10] || (_cache[10] = [
            createBaseVNode("i", { class: "fas fa-play me-2" }, null, -1),
            createTextVNode("Возобновить заявку ", -1)
          ])]),
          createBaseVNode("button", {
            class: "btn btn-outline-danger btn-sm",
            onClick: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("cancel-request"))
          }, [..._cache[11] || (_cache[11] = [
            createBaseVNode("i", { class: "fas fa-times me-2" }, null, -1),
            createTextVNode("Отменить заявку ", -1)
          ])]),
          createBaseVNode("a", {
            href: `/lessee/rental-requests/${$props.request.id}/edit`,
            class: "btn btn-outline-primary btn-sm"
          }, [..._cache[12] || (_cache[12] = [
            createBaseVNode("i", { class: "fas fa-edit me-2" }, null, -1),
            createTextVNode("Редактировать ", -1)
          ])], 8, _hoisted_7$5)
        ])) : $props.request.status === "processing" ? (openBlock(), createElementBlock("div", _hoisted_8$4, [
          createBaseVNode("button", {
            class: "btn btn-success btn-sm",
            onClick: _cache[4] || (_cache[4] = ($event) => _ctx.$emit("resume-request"))
          }, [..._cache[13] || (_cache[13] = [
            createBaseVNode("i", { class: "fas fa-play me-2" }, null, -1),
            createTextVNode("Возобновить заявку ", -1)
          ])]),
          createBaseVNode("button", {
            class: "btn btn-outline-danger btn-sm",
            onClick: _cache[5] || (_cache[5] = ($event) => _ctx.$emit("cancel-request"))
          }, [..._cache[14] || (_cache[14] = [
            createBaseVNode("i", { class: "fas fa-times me-2" }, null, -1),
            createTextVNode("Отменить заявку ", -1)
          ])]),
          createBaseVNode("a", {
            href: `/lessee/rental-requests/${$props.request.id}/edit`,
            class: "btn btn-outline-primary btn-sm"
          }, [..._cache[15] || (_cache[15] = [
            createBaseVNode("i", { class: "fas fa-edit me-2" }, null, -1),
            createTextVNode("Редактировать ", -1)
          ])], 8, _hoisted_9$3)
        ])) : $props.request.status === "completed" ? (openBlock(), createElementBlock("div", _hoisted_10$3, [..._cache[16] || (_cache[16] = [
          createBaseVNode("i", { class: "fas fa-flag-checkered me-2" }, null, -1),
          createTextVNode(" Заявка успешно завершена. ", -1)
        ])])) : $props.request.status === "cancelled" ? (openBlock(), createElementBlock("div", _hoisted_11$3, [..._cache[17] || (_cache[17] = [
          createBaseVNode("i", { class: "fas fa-ban me-2" }, null, -1),
          createTextVNode(" Заявка отменена. ", -1)
        ])])) : $props.request.status === "draft" ? (openBlock(), createElementBlock("div", _hoisted_12$3, [
          createBaseVNode("a", {
            href: `/lessee/rental-requests/${$props.request.id}/edit`,
            class: "btn btn-primary btn-sm"
          }, [..._cache[18] || (_cache[18] = [
            createBaseVNode("i", { class: "fas fa-edit me-2" }, null, -1),
            createTextVNode("Продолжить редактирование ", -1)
          ])], 8, _hoisted_13$3),
          createBaseVNode("button", {
            class: "btn btn-outline-danger btn-sm",
            onClick: _cache[6] || (_cache[6] = ($event) => _ctx.$emit("cancel-request"))
          }, [..._cache[19] || (_cache[19] = [
            createBaseVNode("i", { class: "fas fa-times me-2" }, null, -1),
            createTextVNode("Удалить черновик ", -1)
          ])])
        ])) : (openBlock(), createElementBlock("div", _hoisted_14$3, [
          _cache[20] || (_cache[20] = createBaseVNode("i", { class: "fas fa-question-circle me-2" }, null, -1)),
          createTextVNode(" Статус заявки: " + toDisplayString($props.request.status), 1)
        ]))
      ])
    ])
  ]);
}
const RequestActions = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$8], ["__scopeId", "data-v-b2b1de24"]]);
const _sfc_main$7 = {
  name: "QuickActions",
  props: {
    requestId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      isExporting: false
    };
  },
  methods: {
    createSimilar() {
      window.location.href = `/lessee/rental-requests/create?copy_from=${this.requestId}`;
    },
    // ⚠️ МЕТОД БЕЗ SWEETALERT2 - используем нативные уведомления
    exportToPDF() {
      return __async(this, null, function* () {
        var _a, _b;
        if (this.isExporting) return;
        this.isExporting = true;
        console.log("🚀 Starting PDF export for request:", this.requestId);
        try {
          this.showNotification("Экспорт в PDF", "Подготовка документа...", "info");
          const response = yield axios.get(
            `/api/lessee/rental-requests/${this.requestId}/export-pdf`,
            {
              responseType: "blob",
              timeout: 3e4
            }
          );
          console.log("📄 PDF response received:", {
            status: response.status,
            size: response.data.size,
            type: response.data.type
          });
          const blob = new Blob([response.data], { type: "application/pdf" });
          const url = URL.createObjectURL(blob);
          const link = document.createElement("a");
          link.href = url;
          link.download = `rental-request-${this.requestId}.pdf`;
          document.body.appendChild(link);
          link.click();
          setTimeout(() => {
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
          }, 1e3);
          this.showNotification("Успех!", "PDF документ успешно скачан", "success", 3e3);
          console.log("✅ PDF export completed successfully");
        } catch (error) {
          console.error("❌ PDF export error:", error);
          let errorMessage = "Не удалось скачать PDF. Попробуйте еще раз.";
          if (error.code === "ECONNABORTED" || error.message.includes("timeout")) {
            errorMessage = "Время ожидания истекло. PDF слишком большой или сервер перегружен.";
          } else if (((_a = error.response) == null ? void 0 : _a.status) === 500) {
            errorMessage = "Ошибка сервера при генерации PDF.";
          } else if (((_b = error.response) == null ? void 0 : _b.status) === 404) {
            errorMessage = "Функция экспорта PDF недоступна.";
          }
          this.showNotification("Ошибка", errorMessage, "error");
        } finally {
          this.isExporting = false;
        }
      });
    },
    // ⚠️ УНИВЕРСАЛЬНЫЙ МЕТОД ДЛЯ УВЕДОМЛЕНИЙ
    showNotification(title, message, type = "info", duration = 0) {
      const notification = document.createElement("div");
      notification.className = `custom-notification custom-notification-${type}`;
      notification.innerHTML = `
                <div class="custom-notification-content">
                    <div class="custom-notification-icon">${this.getIcon(type)}</div>
                    <div class="custom-notification-text">
                        <div class="custom-notification-title">${title}</div>
                        <div class="custom-notification-message">${message}</div>
                    </div>
                    <button class="custom-notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
      const style = document.createElement("style");
      style.textContent = `
                .custom-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border-left: 4px solid #007bff;
                    z-index: 10000;
                    min-width: 300px;
                    max-width: 400px;
                    animation: slideIn 0.3s ease-out;
                }
                .custom-notification-success {
                    border-left-color: #28a745;
                }
                .custom-notification-error {
                    border-left-color: #dc3545;
                }
                .custom-notification-warning {
                    border-left-color: #ffc107;
                }
                .custom-notification-info {
                    border-left-color: #17a2b8;
                }
                .custom-notification-content {
                    display: flex;
                    align-items: center;
                    padding: 16px;
                    position: relative;
                }
                .custom-notification-icon {
                    font-size: 20px;
                    margin-right: 12px;
                }
                .custom-notification-text {
                    flex: 1;
                }
                .custom-notification-title {
                    font-weight: bold;
                    margin-bottom: 4px;
                }
                .custom-notification-message {
                    color: #666;
                    font-size: 14px;
                }
                .custom-notification-close {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: #999;
                    margin-left: 10px;
                }
                .custom-notification-close:hover {
                    color: #666;
                }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
      if (!document.querySelector("#custom-notification-styles")) {
        style.id = "custom-notification-styles";
        document.head.appendChild(style);
      }
      document.body.appendChild(notification);
      if (duration > 0) {
        setTimeout(() => {
          if (notification.parentElement) {
            notification.remove();
          }
        }, duration);
      }
    },
    getIcon(type) {
      const icons = {
        success: "✅",
        error: "❌",
        warning: "⚠️",
        info: "ℹ️"
      };
      return icons[type] || "ℹ️";
    },
    // ⚠️ АЛЬТЕРНАТИВНЫЙ МЕТОД С NATIVE ALERT (для тестирования)
    exportToPDFWithAlert() {
      return __async(this, null, function* () {
        if (this.isExporting) return;
        this.isExporting = true;
        try {
          alert("Начинаем экспорт PDF...");
          const response = yield axios.get(
            `/api/lessee/rental-requests/${this.requestId}/export-pdf`,
            { responseType: "blob" }
          );
          const blobUrl = URL.createObjectURL(response.data);
          const link = document.createElement("a");
          link.href = blobUrl;
          link.download = `rental-request-${this.requestId}.pdf`;
          link.click();
          setTimeout(() => URL.revokeObjectURL(blobUrl), 1e3);
          alert("PDF успешно скачан!");
        } catch (error) {
          console.error("PDF export error:", error);
          alert("Ошибка: " + error.message);
        } finally {
          this.isExporting = false;
        }
      });
    },
    shareRequest() {
      return __async(this, null, function* () {
        try {
          if (navigator.share) {
            yield navigator.share({
              title: "Заявка на аренду техники",
              text: "Посмотрите эту заявку на аренду строительной техники",
              url: window.location.href
            });
            this.showNotification("Успешно!", "Заявка успешно отправлена", "success", 3e3);
          } else {
            yield navigator.clipboard.writeText(window.location.href);
            this.showNotification("Скопировано!", "Ссылка скопирована в буфер обмена", "success", 3e3);
          }
        } catch (error) {
          console.error("Ошибка при попытке поделиться:", error);
          if (error.name !== "AbortError") {
            this.showNotification("Ошибка", "Не удалось поделиться заявкой", "error");
          }
        }
      });
    }
  }
};
const _hoisted_1$7 = { class: "card" };
const _hoisted_2$7 = { class: "card-body" };
const _hoisted_3$7 = { class: "d-grid gap-2" };
const _hoisted_4$7 = ["disabled"];
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$7, [
    _cache[6] || (_cache[6] = createBaseVNode("div", { class: "card-header" }, [
      createBaseVNode("h6", { class: "card-title mb-0" }, "Быстрые действия")
    ], -1)),
    createBaseVNode("div", _hoisted_2$7, [
      createBaseVNode("div", _hoisted_3$7, [
        createBaseVNode("button", {
          class: "btn btn-outline-primary btn-sm",
          onClick: _cache[0] || (_cache[0] = (...args) => $options.createSimilar && $options.createSimilar(...args))
        }, [..._cache[3] || (_cache[3] = [
          createBaseVNode("i", { class: "fas fa-copy me-2" }, null, -1),
          createTextVNode("Создать похожую заявку ", -1)
        ])]),
        createBaseVNode("button", {
          class: "btn btn-outline-secondary btn-sm",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.exportToPDF && $options.exportToPDF(...args)),
          disabled: $data.isExporting
        }, [
          _cache[4] || (_cache[4] = createBaseVNode("i", { class: "fas fa-download me-2" }, null, -1)),
          createTextVNode(" " + toDisplayString($data.isExporting ? "Экспорт..." : "Экспорт в PDF"), 1)
        ], 8, _hoisted_4$7),
        createBaseVNode("button", {
          class: "btn btn-outline-secondary btn-sm",
          onClick: _cache[2] || (_cache[2] = (...args) => $options.shareRequest && $options.shareRequest(...args))
        }, [..._cache[5] || (_cache[5] = [
          createBaseVNode("i", { class: "fas fa-share-alt me-2" }, null, -1),
          createTextVNode("Поделиться заявкой ", -1)
        ])])
      ])
    ])
  ]);
}
const QuickActions = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7], ["__scopeId", "data-v-bb4470fa"]]);
const _sfc_main$6 = {
  name: "PauseRequestModal",
  props: {
    requestId: {
      type: [String, Number],
      required: true
    }
  },
  emits: ["confirmed", "closed"]
};
const _hoisted_1$6 = {
  class: "modal fade show d-block",
  tabindex: "-1",
  style: { "background-color": "rgba(0,0,0,0.5)" }
};
const _hoisted_2$6 = { class: "modal-dialog" };
const _hoisted_3$6 = { class: "modal-content" };
const _hoisted_4$6 = { class: "modal-header" };
const _hoisted_5$6 = { class: "modal-footer" };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$6, [
    createBaseVNode("div", _hoisted_2$6, [
      createBaseVNode("div", _hoisted_3$6, [
        createBaseVNode("div", _hoisted_4$6, [
          _cache[3] || (_cache[3] = createBaseVNode("h5", { class: "modal-title" }, "Приостановка заявки", -1)),
          createBaseVNode("button", {
            type: "button",
            class: "btn-close",
            onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("closed"))
          })
        ]),
        _cache[4] || (_cache[4] = createBaseVNode("div", { class: "modal-body" }, [
          createBaseVNode("p", null, "Вы уверены, что хотите приостановить заявку? Арендодатели больше не будут видеть её в поиске.")
        ], -1)),
        createBaseVNode("div", _hoisted_5$6, [
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-secondary",
            onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("closed"))
          }, "Отмена"),
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-warning",
            onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("confirmed"))
          }, "Приостановить")
        ])
      ])
    ])
  ]);
}
const PauseRequestModal = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6]]);
const _sfc_main$5 = {
  name: "CancelRequestModal",
  props: {
    requestId: {
      type: [String, Number],
      required: true
    }
  },
  emits: ["confirmed", "closed"]
};
const _hoisted_1$5 = {
  class: "modal fade show d-block",
  tabindex: "-1",
  style: { "background-color": "rgba(0,0,0,0.5)" }
};
const _hoisted_2$5 = { class: "modal-dialog" };
const _hoisted_3$5 = { class: "modal-content" };
const _hoisted_4$5 = { class: "modal-header" };
const _hoisted_5$5 = { class: "modal-footer" };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$5, [
    createBaseVNode("div", _hoisted_2$5, [
      createBaseVNode("div", _hoisted_3$5, [
        createBaseVNode("div", _hoisted_4$5, [
          _cache[3] || (_cache[3] = createBaseVNode("h5", { class: "modal-title" }, "Отмена заявки", -1)),
          createBaseVNode("button", {
            type: "button",
            class: "btn-close",
            onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("closed"))
          })
        ]),
        _cache[4] || (_cache[4] = createBaseVNode("div", { class: "modal-body" }, [
          createBaseVNode("p", null, "Вы уверены, что хотите отменить заявку? Это действие нельзя будет отменить.")
        ], -1)),
        createBaseVNode("div", _hoisted_5$5, [
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-secondary",
            onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("closed"))
          }, "Отмена"),
          createBaseVNode("button", {
            type: "button",
            class: "btn btn-danger",
            onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("confirmed"))
          }, "Отменить заявку")
        ])
      ])
    ])
  ]);
}
const CancelRequestModal = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5]]);
const _sfc_main$4 = {
  name: "RentalConditionsDisplay",
  props: {
    conditions: {
      type: Object,
      required: true,
      default: () => ({})
    }
  },
  methods: {
    getPaymentTypeText(type) {
      const types = {
        "hourly": "Почасовая",
        "shift": "Посменная",
        "daily": "Посуточная"
      };
      return types[type] || type;
    },
    getTransportationText(type) {
      const types = {
        "lessor": "Арендодателем",
        "lessee": "Арендатором",
        "shared": "Совместно"
      };
      return types[type] || type;
    },
    getGsmPaymentText(type) {
      const types = {
        "included": "Включена в стоимость",
        "separate": "Отдельная оплата"
      };
      return types[type] || type;
    }
  }
};
const _hoisted_1$4 = { class: "rental-conditions-display" };
const _hoisted_2$4 = { class: "conditions-grid" };
const _hoisted_3$4 = {
  key: 0,
  class: "condition-item"
};
const _hoisted_4$4 = { class: "condition-value" };
const _hoisted_5$4 = {
  key: 1,
  class: "condition-item"
};
const _hoisted_6$4 = { class: "condition-value" };
const _hoisted_7$4 = {
  key: 2,
  class: "condition-item"
};
const _hoisted_8$3 = { class: "condition-value" };
const _hoisted_9$2 = {
  key: 3,
  class: "condition-item"
};
const _hoisted_10$2 = { class: "condition-value" };
const _hoisted_11$2 = {
  key: 4,
  class: "condition-item"
};
const _hoisted_12$2 = { class: "condition-value" };
const _hoisted_13$2 = {
  key: 5,
  class: "condition-item"
};
const _hoisted_14$2 = { class: "condition-value" };
const _hoisted_15$2 = {
  key: 6,
  class: "condition-item"
};
const _hoisted_16$2 = { class: "condition-value" };
const _hoisted_17$2 = {
  key: 7,
  class: "condition-item"
};
const _hoisted_18$2 = { class: "condition-value" };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$4, [
    createBaseVNode("div", _hoisted_2$4, [
      $props.conditions.payment_type ? (openBlock(), createElementBlock("div", _hoisted_3$4, [
        _cache[0] || (_cache[0] = createBaseVNode("span", { class: "condition-label" }, "Тип оплаты:", -1)),
        createBaseVNode("span", _hoisted_4$4, toDisplayString($options.getPaymentTypeText($props.conditions.payment_type)), 1)
      ])) : createCommentVNode("", true),
      $props.conditions.hours_per_shift ? (openBlock(), createElementBlock("div", _hoisted_5$4, [
        _cache[1] || (_cache[1] = createBaseVNode("span", { class: "condition-label" }, "Часов в смене:", -1)),
        createBaseVNode("span", _hoisted_6$4, toDisplayString($props.conditions.hours_per_shift) + " ч", 1)
      ])) : createCommentVNode("", true),
      $props.conditions.shifts_per_day ? (openBlock(), createElementBlock("div", _hoisted_7$4, [
        _cache[2] || (_cache[2] = createBaseVNode("span", { class: "condition-label" }, "Смен в сутки:", -1)),
        createBaseVNode("span", _hoisted_8$3, toDisplayString($props.conditions.shifts_per_day), 1)
      ])) : createCommentVNode("", true),
      $props.conditions.transportation_organized_by ? (openBlock(), createElementBlock("div", _hoisted_9$2, [
        _cache[3] || (_cache[3] = createBaseVNode("span", { class: "condition-label" }, "Транспортировка:", -1)),
        createBaseVNode("span", _hoisted_10$2, toDisplayString($options.getTransportationText($props.conditions.transportation_organized_by)), 1)
      ])) : createCommentVNode("", true),
      $props.conditions.gsm_payment ? (openBlock(), createElementBlock("div", _hoisted_11$2, [
        _cache[4] || (_cache[4] = createBaseVNode("span", { class: "condition-label" }, "Оплата ГСМ:", -1)),
        createBaseVNode("span", _hoisted_12$2, toDisplayString($options.getGsmPaymentText($props.conditions.gsm_payment)), 1)
      ])) : createCommentVNode("", true),
      $props.conditions.operator_included !== void 0 ? (openBlock(), createElementBlock("div", _hoisted_13$2, [
        _cache[5] || (_cache[5] = createBaseVNode("span", { class: "condition-label" }, "Оператор включен:", -1)),
        createBaseVNode("span", _hoisted_14$2, toDisplayString($props.conditions.operator_included ? "Да" : "Нет"), 1)
      ])) : createCommentVNode("", true),
      $props.conditions.accommodation_payment !== void 0 ? (openBlock(), createElementBlock("div", _hoisted_15$2, [
        _cache[6] || (_cache[6] = createBaseVNode("span", { class: "condition-label" }, "Оплата проживания:", -1)),
        createBaseVNode("span", _hoisted_16$2, toDisplayString($props.conditions.accommodation_payment ? "Да" : "Нет"), 1)
      ])) : createCommentVNode("", true),
      $props.conditions.extension_possibility !== void 0 ? (openBlock(), createElementBlock("div", _hoisted_17$2, [
        _cache[7] || (_cache[7] = createBaseVNode("span", { class: "condition-label" }, "Возможно продление:", -1)),
        createBaseVNode("span", _hoisted_18$2, toDisplayString($props.conditions.extension_possibility ? "Да" : "Нет"), 1)
      ])) : createCommentVNode("", true)
    ])
  ]);
}
const RentalConditionsDisplay = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__scopeId", "data-v-632907e4"]]);
const _sfc_main$3 = {
  name: "SpecificationsDisplay",
  props: {
    specifications: {
      type: [Array, Object],
      default: () => []
    }
  },
  computed: {
    formattedSpecifications() {
      if (!this.specifications) return [];
      console.log("🔍 SpecificationsDisplay: получены спецификации", {
        type: typeof this.specifications,
        isArray: Array.isArray(this.specifications),
        value: this.specifications
      });
      if (typeof this.specifications === "object" && this.specifications.custom_specifications) {
        console.log("🎯 ДЕТАЛИ кастомных спецификаций:", {
          количество: Object.keys(this.specifications.custom_specifications).length,
          ключи: Object.keys(this.specifications.custom_specifications),
          данные: this.specifications.custom_specifications
        });
      }
      if (Array.isArray(this.specifications)) {
        const filtered = this.specifications.filter(
          (spec) => spec && spec.value !== null && spec.value !== "" && spec.value !== void 0
        );
        console.log("✅ SpecificationsDisplay: Используем отформатированный массив:", filtered);
        return filtered;
      }
      if (typeof this.specifications === "object") {
        const formatted = [];
        const specs = JSON.parse(JSON.stringify(this.specifications));
        if (specs.standard_specifications) {
          Object.entries(specs.standard_specifications).forEach(([key, value]) => {
            if (value !== null && value !== "" && value !== void 0) {
              formatted.push({
                key,
                label: this.formatSpecLabel(key),
                value,
                unit: this.getSpecUnit(key),
                display_value: value + (this.getSpecUnit(key) ? " " + this.getSpecUnit(key) : "")
              });
            }
          });
        }
        if (specs.custom_specifications) {
          Object.entries(specs.custom_specifications).forEach(([key, spec]) => {
            if (spec && spec.value !== null && spec.value !== "" && spec.value !== void 0) {
              formatted.push({
                key,
                label: spec.label || "Доп. параметр",
                value: spec.value,
                unit: spec.unit || "",
                display_value: spec.value + (spec.unit ? " " + spec.unit : "")
              });
            }
          });
        }
        if (Object.keys(specs).length > 0 && !specs.standard_specifications && !specs.custom_specifications) {
          Object.entries(specs).forEach(([key, value]) => {
            if (value !== null && value !== "" && value !== void 0 && typeof value !== "object") {
              formatted.push({
                key,
                label: this.formatSpecLabel(key),
                value,
                unit: this.getSpecUnit(key),
                display_value: value + (this.getSpecUnit(key) ? " " + this.getSpecUnit(key) : "")
              });
            }
          });
        }
        console.log("🔄 SpecificationsDisplay: Преобразованный объект в массив:", formatted);
        return formatted;
      }
      return [];
    }
  },
  methods: {
    formatSpecValue(value) {
      if (typeof value === "number") {
        return value % 1 === 0 ? value.toString() : value.toFixed(1);
      }
      return value;
    },
    formatSpecLabel(key) {
      const labels = {
        "bucket_volume": "Объем ковша",
        "weight": "Вес",
        // 🔥 ИСПРАВЛЕНО
        "power": "Мощность",
        "max_digging_depth": "Макс. глубина копания",
        "engine_power": "Мощность двигателя",
        "operating_weight": "Эксплуатационный вес",
        "transport_length": "Длина транспортировки",
        "transport_width": "Ширина транспортировки",
        "transport_height": "Высота транспортировки",
        "engine_type": "Тип двигателя",
        "fuel_tank_capacity": "Емкость топливного бака",
        "max_speed": "Макс. скорость",
        "bucket_capacity": "Емкость ковша",
        "body_volume": "Объем кузова",
        "load_capacity": "Грузоподъемность",
        "axle_configuration": "Колесная формула"
      };
      return labels[key] || key.split("_").map(
        (word) => word.charAt(0).toUpperCase() + word.slice(1)
      ).join(" ");
    },
    getSpecUnit(key) {
      const units = {
        "bucket_volume": "м³",
        "weight": "т",
        // 🔥 ДОБАВЛЕНО единица измерения
        "power": "л.с.",
        "max_digging_depth": "м",
        "engine_power": "кВт",
        "operating_weight": "т",
        "transport_length": "м",
        "transport_width": "м",
        "transport_height": "м",
        "fuel_tank_capacity": "л",
        "max_speed": "км/ч",
        "bucket_capacity": "м³",
        "body_volume": "м³",
        "load_capacity": "т"
      };
      return units[key] || "";
    }
  }
};
const _hoisted_1$3 = { class: "specifications-display" };
const _hoisted_2$3 = {
  key: 0,
  class: "specs-content"
};
const _hoisted_3$3 = { class: "specs-grid" };
const _hoisted_4$3 = { class: "spec-label" };
const _hoisted_5$3 = { class: "spec-value" };
const _hoisted_6$3 = {
  key: 0,
  class: "spec-unit"
};
const _hoisted_7$3 = {
  key: 1,
  class: "no-specs"
};
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$3, [
    $options.formattedSpecifications.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_2$3, [
      createBaseVNode("div", _hoisted_3$3, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.formattedSpecifications, (spec) => {
          return openBlock(), createElementBlock("div", {
            key: spec.key,
            class: "spec-item"
          }, [
            createBaseVNode("span", _hoisted_4$3, toDisplayString(spec.label) + ":", 1),
            createBaseVNode("span", _hoisted_5$3, [
              createTextVNode(toDisplayString($options.formatSpecValue(spec.value)) + " ", 1),
              spec.unit ? (openBlock(), createElementBlock("span", _hoisted_6$3, toDisplayString(spec.unit), 1)) : createCommentVNode("", true)
            ])
          ]);
        }), 128))
      ])
    ])) : (openBlock(), createElementBlock("div", _hoisted_7$3, [..._cache[0] || (_cache[0] = [
      createBaseVNode("i", { class: "fas fa-info-circle me-2" }, null, -1),
      createBaseVNode("span", null, "Технические параметры не указаны", -1)
    ])]))
  ]);
}
const SpecificationsDisplay = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-f9449ed5"]]);
const _sfc_main$2 = {
  name: "PositionCard",
  components: {
    SpecificationsDisplay,
    RentalConditionsDisplay
  },
  props: {
    item: {
      type: Object,
      required: true
    },
    initiallyExpanded: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isExpanded: this.initiallyExpanded
    };
  },
  computed: {
    conditionsTypeClass() {
      return this.item.conditions_type === "individual" ? "bg-warning" : "bg-secondary";
    },
    conditionsTypeText() {
      return this.item.conditions_type === "individual" ? "Индивидуальные" : "Общие";
    }
  },
  methods: {
    toggleExpanded() {
      this.isExpanded = !this.isExpanded;
    },
    formatCurrency(amount) {
      if (!amount) return "0 ₽";
      return new Intl.NumberFormat("ru-RU", {
        style: "currency",
        currency: "RUB",
        minimumFractionDigits: 0
      }).format(amount);
    },
    // 🔥 ИСПРАВЛЕННЫЙ МЕТОД: Приоритет готовым отформатированным данным
    getFormattedSpecifications() {
      if (this.item.formatted_specifications && this.item.formatted_specifications.length > 0) {
        console.log("✅ PositionCard: Используем formatted_specifications от бэкенда:", this.item.formatted_specifications);
        return this.item.formatted_specifications;
      }
      if (!this.item.specifications) {
        console.log("❌ Нет спецификаций в item:", this.item);
        return [];
      }
      console.log("🔍 PositionCard: Анализ спецификаций для самостоятельного форматирования:", {
        specifications: this.item.specifications,
        type: typeof this.item.specifications,
        isArray: Array.isArray(this.item.specifications)
      });
      const formatted = [];
      if (Array.isArray(this.item.specifications)) {
        console.log("📋 Обработка массива спецификаций:", this.item.specifications.length);
        this.item.specifications.forEach((spec) => {
          if (spec && spec.value !== null && spec.value !== "") {
            formatted.push({
              key: spec.key || spec.name,
              label: spec.label || spec.name || "Параметр",
              value: spec.value,
              unit: spec.unit || "",
              display_value: spec.value + (spec.unit ? " " + spec.unit : ""),
              formatted: (spec.label || spec.name || "Параметр") + ": " + spec.value + (spec.unit ? " " + spec.unit : "")
            });
          }
        });
      } else if (typeof this.item.specifications === "object") {
        const specs = JSON.parse(JSON.stringify(this.item.specifications));
        if (specs.standard_specifications && typeof specs.standard_specifications === "object") {
          console.log("🏗️ Обработка стандартных спецификаций:", Object.keys(specs.standard_specifications));
          Object.entries(specs.standard_specifications).forEach(([key, value]) => {
            if (value !== null && value !== "" && value !== void 0) {
              formatted.push({
                key,
                label: this.getSpecificationLabel(key),
                value,
                unit: this.getSpecificationUnit(key),
                display_value: value + (this.getSpecificationUnit(key) ? " " + this.getSpecificationUnit(key) : ""),
                formatted: this.getSpecificationLabel(key) + ": " + value + (this.getSpecificationUnit(key) ? " " + this.getSpecificationUnit(key) : "")
              });
            }
          });
        }
        if (specs.custom_specifications && typeof specs.custom_specifications === "object") {
          console.log("🎨 Обработка кастомных спецификаций:", Object.keys(specs.custom_specifications));
          Object.entries(specs.custom_specifications).forEach(([key, spec]) => {
            if (spec && spec.value !== null && spec.value !== "" && spec.value !== void 0) {
              formatted.push({
                key,
                label: spec.label || "Дополнительный параметр",
                value: spec.value,
                unit: spec.unit || "",
                display_value: spec.value + (spec.unit ? " " + spec.unit : ""),
                formatted: (spec.label || "Дополнительный параметр") + ": " + spec.value + (spec.unit ? " " + spec.unit : "")
              });
            }
          });
        }
        if (Object.keys(specs).length > 0 && !specs.standard_specifications && !specs.custom_specifications) {
          console.log("🔧 Обработка прямого объекта спецификаций:", Object.keys(specs));
          Object.entries(specs).forEach(([key, value]) => {
            if (value !== null && value !== "" && value !== void 0 && typeof value !== "object") {
              formatted.push({
                key,
                label: this.getSpecificationLabel(key),
                value,
                unit: this.getSpecificationUnit(key),
                display_value: value + (this.getSpecificationUnit(key) ? " " + this.getSpecificationUnit(key) : ""),
                formatted: this.getSpecificationLabel(key) + ": " + value + (this.getSpecificationUnit(key) ? " " + this.getSpecificationUnit(key) : "")
              });
            }
          });
        }
      }
      console.log("📊 PositionCard: Итоговые форматированные спецификации:", formatted);
      return formatted;
    },
    // Метод для получения читаемых названий спецификаций
    getSpecificationLabel(key) {
      const labels = {
        "bucket_volume": "Объем ковша",
        "weight": "Вес",
        // 🔥 ДОБАВЛЕНО
        "power": "Мощность",
        "max_digging_depth": "Макс. глубина копания",
        "engine_power": "Мощность двигателя",
        "operating_weight": "Эксплуатационный вес",
        "transport_length": "Длина транспортировки",
        "transport_width": "Ширина транспортировки",
        "transport_height": "Высота транспортировки",
        "engine_type": "Тип двигателя",
        "fuel_tank_capacity": "Емкость топливного бака",
        "max_speed": "Макс. скорость",
        "bucket_capacity": "Емкость ковша",
        "body_volume": "Объем кузова",
        "load_capacity": "Грузоподъемность",
        "axle_configuration": "Колесная формула",
        "weight": "Вес"
        // 🔥 ДОБАВЛЕНО
      };
      return labels[key] || this.formatKeyToLabel(key);
    },
    // Форматируем ключ в читаемый label
    formatKeyToLabel(key) {
      return key.split("_").map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(" ");
    },
    // Метод для получения единиц измерения
    getSpecificationUnit(key) {
      const units = {
        "bucket_volume": "м³",
        "weight": "т",
        "power": "л.с.",
        "max_digging_depth": "м",
        "engine_power": "кВт",
        "operating_weight": "т",
        "transport_length": "м",
        "transport_width": "м",
        "transport_height": "м",
        "fuel_tank_capacity": "л",
        "max_speed": "км/ч",
        "bucket_capacity": "м³",
        "body_volume": "м³",
        "load_capacity": "т"
      };
      return units[key] || "";
    }
  },
  mounted() {
    console.log("🔍 PositionCard mounted: данные для отображения", {
      id: this.item.id,
      has_formatted_specs: !!this.item.formatted_specifications,
      formatted_specs_count: this.item.formatted_specifications ? this.item.formatted_specifications.length : 0,
      has_raw_specs: !!this.item.specifications,
      raw_specs_keys: this.item.specifications ? Object.keys(this.item.specifications) : []
    });
    if (this.item.formatted_specifications) {
      console.log("📋 PositionCard formatted_specifications:", this.item.formatted_specifications);
    }
  }
};
const _hoisted_1$2 = { class: "position-summary" };
const _hoisted_2$2 = { class: "category-info" };
const _hoisted_3$2 = { class: "category-badge" };
const _hoisted_4$2 = { class: "quantity-badge" };
const _hoisted_5$2 = { class: "price-info" };
const _hoisted_6$2 = { class: "price" };
const _hoisted_7$2 = { class: "conditions-info" };
const _hoisted_8$2 = { class: "expand-icon" };
const _hoisted_9$1 = {
  key: 0,
  class: "position-details"
};
const _hoisted_10$1 = { class: "details-grid" };
const _hoisted_11$1 = { class: "details-section" };
const _hoisted_12$1 = { class: "section-title" };
const _hoisted_13$1 = { class: "text-muted ms-2" };
const _hoisted_14$1 = {
  key: 0,
  class: "alert alert-info py-1 mb-2"
};
const _hoisted_15$1 = { class: "details-section" };
const _hoisted_16$1 = { class: "details-section" };
const _hoisted_17$1 = { class: "item-details" };
const _hoisted_18$1 = { class: "detail-item" };
const _hoisted_19$1 = { class: "detail-value" };
const _hoisted_20$1 = { class: "detail-item" };
const _hoisted_21$1 = { class: "detail-value" };
const _hoisted_22$1 = {
  key: 0,
  class: "detail-item"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  var _a;
  const _component_SpecificationsDisplay = resolveComponent("SpecificationsDisplay");
  const _component_RentalConditionsDisplay = resolveComponent("RentalConditionsDisplay");
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(["position-card", { expanded: $data.isExpanded }])
  }, [
    createBaseVNode("div", {
      class: "position-header",
      onClick: _cache[0] || (_cache[0] = (...args) => $options.toggleExpanded && $options.toggleExpanded(...args))
    }, [
      createBaseVNode("div", _hoisted_1$2, [
        createBaseVNode("div", _hoisted_2$2, [
          createBaseVNode("span", _hoisted_3$2, toDisplayString(((_a = $props.item.category) == null ? void 0 : _a.name) || "Без категории"), 1),
          createBaseVNode("span", _hoisted_4$2, "×" + toDisplayString($props.item.quantity), 1)
        ]),
        createBaseVNode("div", _hoisted_5$2, [
          createBaseVNode("span", _hoisted_6$2, toDisplayString($options.formatCurrency($props.item.calculated_price || 0)), 1)
        ]),
        createBaseVNode("div", _hoisted_7$2, [
          createBaseVNode("span", {
            class: normalizeClass(["conditions-badge", $options.conditionsTypeClass])
          }, toDisplayString($options.conditionsTypeText), 3)
        ])
      ]),
      createBaseVNode("div", _hoisted_8$2, [
        createBaseVNode("i", {
          class: normalizeClass(["fas", $data.isExpanded ? "fa-chevron-up" : "fa-chevron-down"])
        }, null, 2)
      ])
    ]),
    $data.isExpanded ? (openBlock(), createElementBlock("div", _hoisted_9$1, [
      createBaseVNode("div", _hoisted_10$1, [
        createBaseVNode("div", _hoisted_11$1, [
          createBaseVNode("h6", _hoisted_12$1, [
            _cache[1] || (_cache[1] = createBaseVNode("i", { class: "fas fa-sliders-h me-2" }, null, -1)),
            _cache[2] || (_cache[2] = createTextVNode("Технические параметры ", -1)),
            createBaseVNode("small", _hoisted_13$1, " (" + toDisplayString($options.getFormattedSpecifications().length) + " параметров) ", 1)
          ]),
          $options.getFormattedSpecifications().length > 0 ? (openBlock(), createElementBlock("div", _hoisted_14$1, [
            createBaseVNode("small", null, [
              _cache[3] || (_cache[3] = createBaseVNode("i", { class: "fas fa-info-circle me-1" }, null, -1)),
              createTextVNode(" Используются " + toDisplayString($props.item.formatted_specifications ? "готовые" : "самостоятельно форматированные") + " спецификации ", 1)
            ])
          ])) : createCommentVNode("", true),
          createVNode(_component_SpecificationsDisplay, {
            specifications: $options.getFormattedSpecifications()
          }, null, 8, ["specifications"])
        ]),
        createBaseVNode("div", _hoisted_15$1, [
          _cache[4] || (_cache[4] = createBaseVNode("h6", { class: "section-title" }, [
            createBaseVNode("i", { class: "fas fa-file-contract me-2" }),
            createTextVNode("Условия аренды ")
          ], -1)),
          createVNode(_component_RentalConditionsDisplay, {
            conditions: $props.item.display_conditions || {}
          }, null, 8, ["conditions"])
        ]),
        createBaseVNode("div", _hoisted_16$1, [
          _cache[8] || (_cache[8] = createBaseVNode("h6", { class: "section-title" }, [
            createBaseVNode("i", { class: "fas fa-info-circle me-2" }),
            createTextVNode("Детали позиции ")
          ], -1)),
          createBaseVNode("div", _hoisted_17$1, [
            createBaseVNode("div", _hoisted_18$1, [
              _cache[5] || (_cache[5] = createBaseVNode("span", { class: "detail-label" }, "Стоимость часа:", -1)),
              createBaseVNode("span", _hoisted_19$1, toDisplayString($options.formatCurrency($props.item.hourly_rate)), 1)
            ]),
            createBaseVNode("div", _hoisted_20$1, [
              _cache[6] || (_cache[6] = createBaseVNode("span", { class: "detail-label" }, "Количество:", -1)),
              createBaseVNode("span", _hoisted_21$1, toDisplayString($props.item.quantity) + " ед.", 1)
            ]),
            $props.item.use_individual_conditions ? (openBlock(), createElementBlock("div", _hoisted_22$1, [..._cache[7] || (_cache[7] = [
              createBaseVNode("span", { class: "detail-label" }, "Индивидуальные условия:", -1),
              createBaseVNode("span", { class: "detail-value text-success" }, "Да", -1)
            ])])) : createCommentVNode("", true)
          ])
        ])
      ])
    ])) : createCommentVNode("", true)
  ], 2);
}
const PositionCard = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-1a7958f5"]]);
const _sfc_main$1 = {
  name: "CategoryGroup",
  components: {
    PositionCard
  },
  props: {
    category: {
      type: Object,
      required: true
    },
    initiallyExpanded: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isExpanded: this.initiallyExpanded
    };
  },
  methods: {
    toggleExpanded() {
      this.isExpanded = !this.isExpanded;
    }
  }
};
const _hoisted_1$1 = ["id"];
const _hoisted_2$1 = { class: "header-content" };
const _hoisted_3$1 = { class: "category-name" };
const _hoisted_4$1 = { class: "category-stats" };
const _hoisted_5$1 = { class: "stat" };
const _hoisted_6$1 = { class: "stat" };
const _hoisted_7$1 = { class: "expand-icon" };
const _hoisted_8$1 = {
  key: 0,
  class: "category-items"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_PositionCard = resolveComponent("PositionCard");
  return openBlock(), createElementBlock("div", {
    class: "category-group",
    id: `category-${$props.category.category_id}`
  }, [
    createBaseVNode("div", {
      class: "category-header",
      onClick: _cache[0] || (_cache[0] = (...args) => $options.toggleExpanded && $options.toggleExpanded(...args))
    }, [
      createBaseVNode("div", _hoisted_2$1, [
        createBaseVNode("h5", _hoisted_3$1, [
          createBaseVNode("i", {
            class: normalizeClass(["fas", $data.isExpanded ? "fa-folder-open" : "fa-folder"])
          }, null, 2),
          createTextVNode(" " + toDisplayString($props.category.category_name), 1)
        ]),
        createBaseVNode("div", _hoisted_4$1, [
          createBaseVNode("span", _hoisted_5$1, toDisplayString($props.category.items_count) + " позиций", 1),
          createBaseVNode("span", _hoisted_6$1, "× " + toDisplayString($props.category.total_quantity) + " ед.", 1)
        ])
      ]),
      createBaseVNode("div", _hoisted_7$1, [
        createBaseVNode("i", {
          class: normalizeClass(["fas", $data.isExpanded ? "fa-chevron-up" : "fa-chevron-down"])
        }, null, 2)
      ])
    ]),
    $data.isExpanded ? (openBlock(), createElementBlock("div", _hoisted_8$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($props.category.items, (item) => {
        return openBlock(), createBlock(_component_PositionCard, {
          key: item.id,
          item,
          "initially-expanded": $props.category.items.length <= 3
        }, null, 8, ["item", "initially-expanded"]);
      }), 128))
    ])) : createCommentVNode("", true)
  ], 8, _hoisted_1$1);
}
const CategoryGroup = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-923064f4"]]);
const _sfc_main = {
  name: "RentalRequestShow",
  components: {
    ProposalsList,
    RequestStats,
    RequestActions,
    QuickActions,
    PauseRequestModal,
    CancelRequestModal,
    RentalConditionsDisplay,
    CategoryGroup,
    PositionCard
  },
  props: {
    requestId: {
      type: [String, Number],
      required: true
    },
    apiUrl: {
      type: String,
      required: true
    },
    pauseUrl: {
      type: String,
      required: true
    },
    cancelUrl: {
      type: String,
      required: true
    },
    csrfToken: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loading: true,
      error: null,
      request: null,
      proposals: [],
      showPauseModal: false,
      showCancelModal: false,
      autoRefreshInterval: null,
      groupedByCategory: [],
      summary: {
        total_items: 0,
        total_quantity: 0,
        categories_count: 0
      }
    };
  },
  computed: {
    statusSteps() {
      return {
        "active": 1,
        "processing": 2,
        "completed": 3
      };
    }
  },
  methods: {
    loadRequest() {
      return __async(this, null, function* () {
        var _a, _b, _c;
        this.loading = true;
        this.error = null;
        try {
          console.log("🔄 Загрузка заявки по API URL:", this.apiUrl);
          const response = yield fetch(this.apiUrl, {
            headers: {
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          const contentType = response.headers.get("content-type");
          console.log("📄 Content-Type ответа:", contentType);
          if (!contentType || !contentType.includes("application/json")) {
            const textResponse = yield response.text();
            console.error("❌ Сервер вернул не JSON:", textResponse.substring(0, 500));
            throw new Error(`API вернул HTML вместо JSON. Status: ${response.status}`);
          }
          if (!response.ok) {
            throw new Error(`HTTP ошибка! Статус: ${response.status}`);
          }
          const data = yield response.json();
          console.log("✅ Данные от API:", data);
          if (data.success) {
            this.request = data.data;
            this.groupedByCategory = data.grouped_by_category || [];
            this.summary = data.summary || {
              total_items: ((_a = this.request.items) == null ? void 0 : _a.length) || 0,
              total_quantity: ((_b = this.request.items) == null ? void 0 : _b.reduce((sum, item) => sum + (item.quantity || 0), 0)) || 0,
              categories_count: new Set((_c = this.request.items) == null ? void 0 : _c.map((item) => item.category_id)).size || 0
            };
            this.proposals = this.request.responses || [];
            console.log("🔍 ДЕТАЛЬНАЯ ДИАГНОСТИКА ДАННЫХ ОТ БЭКЕНДА:");
            if (this.request.items && this.request.items.length > 0) {
              this.request.items.forEach((item, index) => {
                var _a2, _b2, _c2;
                console.log(`📦 Позиция ${index + 1} (ID: ${item.id}):`, {
                  // Основная информация
                  title: item.title,
                  category: (_a2 = item.category) == null ? void 0 : _a2.name,
                  // Спецификации - что приходит с бэкенда
                  raw_specifications: item.specifications,
                  formatted_specifications: item.formatted_specifications,
                  // Детальный анализ спецификаций
                  specs_type: typeof item.specifications,
                  specs_is_array: Array.isArray(item.specifications),
                  specs_keys: item.specifications ? Object.keys(item.specifications) : [],
                  // Форматированные спецификации
                  has_formatted_specs: !!item.formatted_specifications,
                  formatted_specs_count: item.formatted_specifications ? item.formatted_specifications.length : 0,
                  // Пример первого параметра для проверки
                  first_spec_if_any: item.specifications && Object.keys(item.specifications).length > 0 ? Object.entries(item.specifications)[0] : "нет"
                });
                if (item.specifications) {
                  const weightInStandard = (_b2 = item.specifications.standard_specifications) == null ? void 0 : _b2.weight;
                  const weightInCustom = (_c2 = item.specifications.custom_specifications) == null ? void 0 : _c2.weight;
                  const weightDirect = item.specifications.weight;
                  if (weightInStandard || weightInCustom || weightDirect) {
                    console.log("⚖️ НАЙДЕН ПАРАМЕТР WEIGHT:", {
                      key: "weight",
                      value: weightDirect || weightInStandard || weightInCustom,
                      in_standard_specs: weightInStandard,
                      in_custom_specs: weightInCustom,
                      direct_access: weightDirect,
                      location: weightInStandard ? "standard_specifications" : weightInCustom ? "custom_specifications" : weightDirect ? "direct" : "not_found"
                    });
                  }
                }
                if (item.formatted_specifications) {
                  const weightSpec = item.formatted_specifications.find(
                    (spec) => {
                      var _a3;
                      return spec.key === "weight" || ((_a3 = spec.label) == null ? void 0 : _a3.toLowerCase().includes("weight"));
                    }
                  );
                  if (weightSpec) {
                    console.log("⚖️ WEIGHT В FORMATTED_SPECIFICATIONS:", weightSpec);
                  }
                  console.log(
                    "🏷️ Все labels в formatted_specifications:",
                    item.formatted_specifications.map((spec) => ({
                      key: spec.key,
                      label: spec.label,
                      value: spec.value
                    }))
                  );
                }
              });
            }
            console.log("🚚 ДАННЫЕ О ДОСТАВКЕ:", {
              delivery_required: this.request.delivery_required,
              type: typeof this.request.delivery_required
            });
            if (this.request.items && this.request.items.length > 0) {
              this.request.items.forEach((item) => {
                if (!item.formatted_specifications && item.specifications) {
                  item.formatted_specifications = this.formatSpecificationsFrontend(item.specifications);
                }
                if (item.formatted_specifications) {
                  item.formatted_specifications = this.fixRussianLabels(item.formatted_specifications);
                }
                console.log(`📦 Позиция ${item.id} после обработки:`, {
                  has_formatted_specs: !!item.formatted_specifications,
                  formatted_specs_count: item.formatted_specifications ? item.formatted_specifications.length : 0,
                  formatted_specs_sample: item.formatted_specifications ? item.formatted_specifications.map((spec) => `${spec.label}: ${spec.value}`) : []
                });
              });
            }
            if (this.groupedByCategory && this.groupedByCategory.length > 0) {
              this.groupedByCategory.forEach((category) => {
                if (category.items && category.items.length > 0) {
                  category.items.forEach((item) => {
                    if (!item.formatted_specifications && item.specifications) {
                      item.formatted_specifications = this.formatSpecificationsFrontend(item.specifications);
                    }
                    if (item.formatted_specifications) {
                      item.formatted_specifications = this.fixRussianLabels(item.formatted_specifications);
                    }
                  });
                }
              });
            }
            console.log("✅ Заявка успешно загружена и обработана");
          } else {
            throw new Error(data.message || "Ошибка загрузки заявки");
          }
        } catch (error) {
          console.error("❌ Ошибка загрузки заявки:", error);
          this.error = error.message;
          this.showFallbackContent();
        } finally {
          this.loading = false;
        }
      });
    },
    // 🔥 НОВЫЙ МЕТОД: Принудительное исправление русских названий
    fixRussianLabels(specifications) {
      if (!Array.isArray(specifications)) return specifications;
      console.log("🔧 Исправление русских названий в спецификациях:", specifications);
      const labelMappings = {
        "weight": "Вес",
        "Weight": "Вес",
        "power": "Мощность",
        "Power": "Мощность",
        "bucket_volume": "Объем ковша",
        "load_capacity": "Грузоподъемность",
        "axle_configuration": "Колесная формула",
        "body_volume": "Объем кузова",
        "max_digging_depth": "Макс. глубина копания",
        "engine_power": "Мощность двигателя",
        "operating_weight": "Эксплуатационный вес",
        "transport_length": "Длина транспортировки",
        "transport_width": "Ширина транспортировки",
        "transport_height": "Высота транспортировки",
        "engine_type": "Тип двигателя",
        "fuel_tank_capacity": "Емкость топливного бака",
        "max_speed": "Макс. скорость",
        "bucket_capacity": "Емкость ковша"
      };
      const fixedSpecs = specifications.map((spec) => {
        let fixedLabel = spec.label;
        if (spec.label && labelMappings[spec.label]) {
          fixedLabel = labelMappings[spec.label];
          console.log(`🔄 Исправлен label: "${spec.label}" -> "${fixedLabel}"`);
        }
        if (fixedLabel === spec.label && spec.key && labelMappings[spec.key]) {
          fixedLabel = labelMappings[spec.key];
          console.log(`🔄 Исправлен по key: "${spec.key}" -> "${fixedLabel}"`);
        }
        if (fixedLabel === spec.label && /^[a-zA-Z_]+$/.test(fixedLabel)) {
          const possibleRussian = labelMappings[fixedLabel.toLowerCase()];
          if (possibleRussian) {
            fixedLabel = possibleRussian;
            console.log(`🔄 Исправлен через lowercase: "${spec.label}" -> "${fixedLabel}"`);
          }
        }
        return __spreadProps(__spreadValues({}, spec), {
          label: fixedLabel
        });
      });
      console.log("✅ Исправленные спецификации:", fixedSpecs);
      return fixedSpecs;
    },
    // 🔥 НОВЫЙ МЕТОД: Принудительное форматирование спецификаций
    forceFormatSpecifications(specifications) {
      if (!specifications) return [];
      console.log("🔧 Принудительное форматирование спецификаций:", specifications);
      const formatted = [];
      const labelMappings = {
        "weight": "Вес",
        "power": "Мощность",
        "bucket_volume": "Объем ковша",
        "load_capacity": "Грузоподъемность",
        "axle_configuration": "Колесная формула",
        "body_volume": "Объем кузова",
        "max_digging_depth": "Макс. глубина копания",
        "engine_power": "Мощность двигателя",
        "operating_weight": "Эксплуатационный вес",
        "transport_length": "Длина транспортировки",
        "transport_width": "Ширина транспортировки",
        "transport_height": "Высота транспортировки",
        "engine_type": "Тип двигателя",
        "fuel_tank_capacity": "Емкость топливного бака",
        "max_speed": "Макс. скорость",
        "bucket_capacity": "Емкость ковша"
      };
      const unitMappings = {
        "weight": "т",
        "power": "л.с.",
        "bucket_volume": "м³",
        "load_capacity": "т",
        "body_volume": "м³",
        "max_digging_depth": "м",
        "engine_power": "кВт",
        "operating_weight": "т",
        "transport_length": "м",
        "transport_width": "м",
        "transport_height": "м",
        "fuel_tank_capacity": "л",
        "max_speed": "км/ч",
        "bucket_capacity": "м³"
      };
      if (Array.isArray(specifications)) {
        return specifications.map((spec) => __spreadProps(__spreadValues({}, spec), {
          label: labelMappings[spec.key] || spec.label || this.formatKeyToLabel(spec.key),
          unit: unitMappings[spec.key] || spec.unit || "",
          display_value: spec.value + (unitMappings[spec.key] ? " " + unitMappings[spec.key] : spec.unit ? " " + spec.unit : "")
        }));
      }
      if (typeof specifications === "object") {
        if (specifications.standard_specifications && typeof specifications.standard_specifications === "object") {
          Object.entries(specifications.standard_specifications).forEach(([key, value]) => {
            if (value !== null && value !== "" && value !== void 0) {
              formatted.push({
                key,
                label: labelMappings[key] || this.formatKeyToLabel(key),
                value,
                unit: unitMappings[key] || "",
                display_value: value + (unitMappings[key] ? " " + unitMappings[key] : "")
              });
            }
          });
        }
        if (specifications.custom_specifications && typeof specifications.custom_specifications === "object") {
          Object.entries(specifications.custom_specifications).forEach(([key, spec]) => {
            if (spec && spec.value !== null && spec.value !== "" && spec.value !== void 0) {
              formatted.push({
                key,
                label: spec.label || "Дополнительный параметр",
                value: spec.value,
                unit: spec.unit || "",
                display_value: spec.value + (spec.unit ? " " + spec.unit : "")
              });
            }
          });
        }
        if (Object.keys(specifications).length > 0 && !specifications.standard_specifications && !specifications.custom_specifications) {
          Object.entries(specifications).forEach(([key, value]) => {
            if (key !== "metadata" && value !== null && value !== "" && value !== void 0 && typeof value !== "object") {
              formatted.push({
                key,
                label: labelMappings[key] || this.formatKeyToLabel(key),
                value,
                unit: unitMappings[key] || "",
                display_value: value + (unitMappings[key] ? " " + unitMappings[key] : "")
              });
            }
          });
        }
      }
      console.log("✅ Принудительно отформатированные спецификации:", formatted);
      return formatted;
    },
    // 🔥 НОВЫЙ МЕТОД: Форматирование спецификаций на фронтенде
    formatSpecificationsFrontend(specifications) {
      if (!specifications) return [];
      console.log("🔧 Форматирование спецификаций на фронтенде:", specifications);
      const formatted = this.forceFormatSpecifications(specifications);
      return this.fixRussianLabels(formatted);
    },
    getSpecificationLabel(key) {
      const labels = {
        "bucket_volume": "Объем ковша",
        "weight": "Вес",
        // 🔥 ДОБАВЛЕНО
        "power": "Мощность",
        "max_digging_depth": "Макс. глубина копания",
        "engine_power": "Мощность двигателя",
        "operating_weight": "Эксплуатационный вес",
        "transport_length": "Длина транспортировки",
        "transport_width": "Ширина транспортировки",
        "transport_height": "Высота транспортировки",
        "engine_type": "Тип двигателя",
        "fuel_tank_capacity": "Емкость топливного бака",
        "max_speed": "Макс. скорость",
        "bucket_capacity": "Емкость ковша",
        "body_volume": "Объем кузова",
        "load_capacity": "Грузоподъемность",
        "axle_configuration": "Колесная формула",
        "weight": "Вес"
        // 🔥 ДОБАВЛЕНО
      };
      return labels[key] || this.formatKeyToLabel(key);
    },
    getSpecificationUnit(key) {
      const units = {
        "bucket_volume": "м³",
        "weight": "т",
        "power": "л.с.",
        "max_digging_depth": "м",
        "engine_power": "кВт",
        "operating_weight": "т",
        "transport_length": "м",
        "transport_width": "м",
        "transport_height": "м",
        "fuel_tank_capacity": "л",
        "max_speed": "км/ч",
        "bucket_capacity": "м³",
        "body_volume": "м³",
        "load_capacity": "т"
      };
      return units[key] || "";
    },
    formatKeyToLabel(key) {
      return key.split("_").map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(" ");
    },
    getStatusBadgeClass(status) {
      const classes = {
        "draft": "bg-secondary",
        "active": "bg-success",
        "paused": "bg-warning",
        "processing": "bg-warning",
        "completed": "bg-primary",
        "cancelled": "bg-danger"
      };
      return classes[status] || "bg-light";
    },
    getStatusDisplayText(status) {
      const texts = {
        "draft": "Черновик",
        "active": "Активна",
        "paused": "Приостановлена",
        "processing": "Приостановлена",
        "completed": "Завершена",
        "cancelled": "Отменена"
      };
      return texts[status] || status;
    },
    resumeRequest() {
      return __async(this, null, function* () {
        try {
          const url = `/api/lessee/rental-requests/${this.requestId}/resume`;
          const response = yield fetch(url, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": this.csrfToken,
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          const data = yield response.json();
          if (data.success) {
            this.showToast("success", data.message);
            yield this.loadRequest();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          this.showToast("error", error.message);
        }
      });
    },
    scrollToCategory(categoryId) {
      const element = document.getElementById(`category-${categoryId}`);
      if (element) {
        element.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    },
    showFallbackContent() {
      const vueApp = document.getElementById("rental-request-show-app");
      const fallbackContent = document.getElementById("blade-fallback-content");
      if (vueApp && fallbackContent) {
        console.log("🔄 Переключаемся на резервный Blade контент");
        vueApp.style.display = "none";
        fallbackContent.style.display = "block";
      }
    },
    getStatusStepClass(targetStatus) {
      if (!this.request) return "";
      const currentStep = this.statusSteps[this.request.status] || 0;
      const targetStep = this.statusSteps[targetStatus] || 0;
      if (currentStep > targetStep) return "completed";
      if (currentStep === targetStep) return "active";
      return "";
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
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString("ru-RU");
    },
    formatCurrency(amount) {
      if (!amount) return "0 ₽";
      return new Intl.NumberFormat("ru-RU", {
        style: "currency",
        currency: "RUB",
        minimumFractionDigits: 0
      }).format(amount);
    },
    calculateRentalDays(startDate, endDate) {
      const start = new Date(startDate);
      const end = new Date(endDate);
      return Math.ceil((end - start) / (1e3 * 60 * 60 * 24)) + 1;
    },
    pauseRequest() {
      return __async(this, null, function* () {
        try {
          const response = yield fetch(this.pauseUrl, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": this.csrfToken,
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          const data = yield response.json();
          if (data.success) {
            this.showToast("success", data.message);
            yield this.loadRequest();
            this.showPauseModal = false;
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          this.showToast("error", error.message);
        }
      });
    },
    cancelRequest() {
      return __async(this, null, function* () {
        try {
          const response = yield fetch(this.cancelUrl, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": this.csrfToken,
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          const data = yield response.json();
          if (data.success) {
            this.showToast("success", data.message);
            yield this.loadRequest();
            this.showCancelModal = false;
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          this.showToast("error", error.message);
        }
      });
    },
    editRequest() {
      window.location.href = `/lessee/rental-requests/${this.requestId}/edit`;
    },
    onProposalAccepted(proposalId) {
      return __async(this, null, function* () {
        try {
          const url = `/api/lessee/rental-requests/${this.requestId}/proposals/${proposalId}/accept`;
          const response = yield fetch(url, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": this.csrfToken,
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          const data = yield response.json();
          if (data.success) {
            this.showToast("success", data.message);
            yield this.loadRequest();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          this.showToast("error", error.message);
        }
      });
    },
    onProposalRejected(proposalId) {
      return __async(this, null, function* () {
        try {
          const url = `/api/lessee/rental-requests/${this.requestId}/proposals/${proposalId}/reject`;
          const response = yield fetch(url, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": this.csrfToken,
              "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "include"
          });
          const data = yield response.json();
          if (data.success) {
            this.showToast("info", data.message);
            yield this.loadRequest();
          } else {
            throw new Error(data.message);
          }
        } catch (error) {
          this.showToast("error", error.message);
        }
      });
    },
    showToast(type, message) {
      const toast = document.createElement("div");
      toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      toast.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
      toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
      document.body.appendChild(toast);
      setTimeout(() => {
        toast.remove();
      }, 5e3);
    },
    setupAutoRefresh() {
      this.autoRefreshInterval = setInterval(() => {
        if (document.visibilityState === "visible") {
          this.loadRequest();
        }
      }, 12e4);
    }
  },
  mounted() {
    return __async(this, null, function* () {
      yield this.loadRequest();
      this.setupAutoRefresh();
    });
  },
  beforeUnmount() {
    if (this.autoRefreshInterval) {
      clearInterval(this.autoRefreshInterval);
    }
  }
};
const _hoisted_1 = {
  key: 0,
  class: "rental-request-show"
};
const _hoisted_2 = { class: "container-fluid px-4" };
const _hoisted_3 = { class: "row" };
const _hoisted_4 = { class: "col-12" };
const _hoisted_5 = { class: "page-header d-flex justify-content-between align-items-center mb-4" };
const _hoisted_6 = { class: "page-title" };
const _hoisted_7 = { class: "row mb-4" };
const _hoisted_8 = { class: "col-12" };
const _hoisted_9 = { class: "status-breadcrumb" };
const _hoisted_10 = { class: "row mb-4" };
const _hoisted_11 = { class: "col-12" };
const _hoisted_12 = { class: "request-stats-card card" };
const _hoisted_13 = { class: "card-body" };
const _hoisted_14 = { class: "stats-grid" };
const _hoisted_15 = { class: "stat-item" };
const _hoisted_16 = { class: "stat-value" };
const _hoisted_17 = { class: "stat-item" };
const _hoisted_18 = { class: "stat-value" };
const _hoisted_19 = { class: "stat-item" };
const _hoisted_20 = { class: "stat-value" };
const _hoisted_21 = { class: "stat-item" };
const _hoisted_22 = { class: "stat-value" };
const _hoisted_23 = {
  key: 0,
  class: "row mb-4"
};
const _hoisted_24 = { class: "col-12" };
const _hoisted_25 = { class: "category-nav card" };
const _hoisted_26 = { class: "card-body" };
const _hoisted_27 = { class: "nav-buttons" };
const _hoisted_28 = ["onClick"];
const _hoisted_29 = { class: "row" };
const _hoisted_30 = { class: "col-lg-8" };
const _hoisted_31 = { class: "card mb-4" };
const _hoisted_32 = { class: "card-header d-flex justify-content-between align-items-center" };
const _hoisted_33 = { class: "card-body" };
const _hoisted_34 = { class: "row" };
const _hoisted_35 = { class: "col-md-6" };
const _hoisted_36 = { class: "info-item mb-3" };
const _hoisted_37 = { class: "mb-0" };
const _hoisted_38 = { class: "info-item mb-3" };
const _hoisted_39 = { class: "mb-0" };
const _hoisted_40 = { class: "text-muted" };
const _hoisted_41 = { class: "col-md-6" };
const _hoisted_42 = { class: "info-item mb-3" };
const _hoisted_43 = { class: "mb-0" };
const _hoisted_44 = { class: "text-muted" };
const _hoisted_45 = { class: "info-item mb-3" };
const _hoisted_46 = { class: "mb-0 fs-5 text-success fw-bold" };
const _hoisted_47 = { class: "info-item mb-3" };
const _hoisted_48 = { class: "mb-0" };
const _hoisted_49 = {
  key: 0,
  class: "badge bg-info ms-2"
};
const _hoisted_50 = {
  key: 0,
  class: "card mb-4"
};
const _hoisted_51 = { class: "card-header" };
const _hoisted_52 = { class: "card-title mb-0" };
const _hoisted_53 = { class: "badge bg-primary ms-2" };
const _hoisted_54 = { class: "card-body p-0" };
const _hoisted_55 = {
  key: 0,
  class: "categories-list"
};
const _hoisted_56 = {
  key: 1,
  class: "positions-list p-3"
};
const _hoisted_57 = { class: "col-lg-4" };
const _hoisted_58 = {
  key: 1,
  class: "text-center py-5"
};
const _hoisted_59 = {
  key: 2,
  class: "alert alert-danger text-center"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _a, _b;
  const _component_CategoryGroup = resolveComponent("CategoryGroup");
  const _component_PositionCard = resolveComponent("PositionCard");
  const _component_ProposalsList = resolveComponent("ProposalsList");
  const _component_RequestStats = resolveComponent("RequestStats");
  const _component_RequestActions = resolveComponent("RequestActions");
  const _component_QuickActions = resolveComponent("QuickActions");
  const _component_PauseRequestModal = resolveComponent("PauseRequestModal");
  const _component_CancelRequestModal = resolveComponent("CancelRequestModal");
  return $data.request ? (openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createBaseVNode("div", _hoisted_4, [
          createBaseVNode("div", _hoisted_5, [
            createBaseVNode("h1", _hoisted_6, "Заявка на аренду: " + toDisplayString($data.request.title), 1),
            createBaseVNode("div", null, [
              _cache[5] || (_cache[5] = createBaseVNode("a", {
                href: "/lessee/rental-requests",
                class: "btn btn-outline-secondary me-2"
              }, [
                createBaseVNode("i", { class: "fas fa-arrow-left me-2" }),
                createTextVNode("Назад к списку ")
              ], -1)),
              $data.request.status === "active" ? (openBlock(), createElementBlock("button", {
                key: 0,
                class: "btn btn-warning me-2",
                onClick: _cache[0] || (_cache[0] = ($event) => $data.showPauseModal = true)
              }, [..._cache[4] || (_cache[4] = [
                createBaseVNode("i", { class: "fas fa-pause me-2" }, null, -1),
                createTextVNode("Приостановить ", -1)
              ])])) : createCommentVNode("", true)
            ])
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_7, [
        createBaseVNode("div", _hoisted_8, [
          createBaseVNode("div", _hoisted_9, [
            createBaseVNode("div", {
              class: normalizeClass(["step", $options.getStatusStepClass("active")])
            }, [..._cache[6] || (_cache[6] = [
              createBaseVNode("span", { class: "step-number" }, "1", -1),
              createBaseVNode("span", { class: "step-label" }, "Активна", -1)
            ])], 2),
            createBaseVNode("div", {
              class: normalizeClass(["step", $options.getStatusStepClass("processing")])
            }, [..._cache[7] || (_cache[7] = [
              createBaseVNode("span", { class: "step-number" }, "2", -1),
              createBaseVNode("span", { class: "step-label" }, "В процессе", -1)
            ])], 2),
            createBaseVNode("div", {
              class: normalizeClass(["step", $options.getStatusStepClass("completed")])
            }, [..._cache[8] || (_cache[8] = [
              createBaseVNode("span", { class: "step-number" }, "3", -1),
              createBaseVNode("span", { class: "step-label" }, "Завершена", -1)
            ])], 2)
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_10, [
        createBaseVNode("div", _hoisted_11, [
          createBaseVNode("div", _hoisted_12, [
            createBaseVNode("div", _hoisted_13, [
              createBaseVNode("div", _hoisted_14, [
                createBaseVNode("div", _hoisted_15, [
                  createBaseVNode("div", _hoisted_16, toDisplayString($data.summary.total_items), 1),
                  _cache[9] || (_cache[9] = createBaseVNode("div", { class: "stat-label" }, "Позиций", -1))
                ]),
                createBaseVNode("div", _hoisted_17, [
                  createBaseVNode("div", _hoisted_18, toDisplayString($data.summary.total_quantity), 1),
                  _cache[10] || (_cache[10] = createBaseVNode("div", { class: "stat-label" }, "Единиц техники", -1))
                ]),
                createBaseVNode("div", _hoisted_19, [
                  createBaseVNode("div", _hoisted_20, toDisplayString($data.summary.categories_count), 1),
                  _cache[11] || (_cache[11] = createBaseVNode("div", { class: "stat-label" }, "Категорий", -1))
                ]),
                createBaseVNode("div", _hoisted_21, [
                  createBaseVNode("div", _hoisted_22, toDisplayString($options.formatCurrency($data.request.total_budget || $data.request.calculated_budget_from)), 1),
                  _cache[12] || (_cache[12] = createBaseVNode("div", { class: "stat-label" }, "Общий бюджет", -1))
                ])
              ])
            ])
          ])
        ])
      ]),
      $data.groupedByCategory.length > 1 ? (openBlock(), createElementBlock("div", _hoisted_23, [
        createBaseVNode("div", _hoisted_24, [
          createBaseVNode("div", _hoisted_25, [
            createBaseVNode("div", _hoisted_26, [
              _cache[13] || (_cache[13] = createBaseVNode("h6", { class: "card-title mb-3" }, "Быстрая навигация по категориям", -1)),
              createBaseVNode("div", _hoisted_27, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.groupedByCategory, (category) => {
                  return openBlock(), createElementBlock("button", {
                    key: category.category_id,
                    class: "btn btn-outline-primary btn-sm",
                    onClick: ($event) => $options.scrollToCategory(category.category_id)
                  }, toDisplayString(category.category_name) + " (" + toDisplayString(category.items_count) + ") ", 9, _hoisted_28);
                }), 128))
              ])
            ])
          ])
        ])
      ])) : createCommentVNode("", true),
      createBaseVNode("div", _hoisted_29, [
        createBaseVNode("div", _hoisted_30, [
          createBaseVNode("div", _hoisted_31, [
            createBaseVNode("div", _hoisted_32, [
              _cache[14] || (_cache[14] = createBaseVNode("h5", { class: "card-title mb-0" }, [
                createBaseVNode("i", { class: "fas fa-info-circle me-2" }),
                createTextVNode("Основная информация ")
              ], -1)),
              createBaseVNode("span", {
                class: normalizeClass(["badge", $options.getStatusBadgeClass($data.request.status)])
              }, toDisplayString($options.getStatusDisplayText($data.request.status)), 3)
            ]),
            createBaseVNode("div", _hoisted_33, [
              createBaseVNode("div", _hoisted_34, [
                createBaseVNode("div", _hoisted_35, [
                  createBaseVNode("div", _hoisted_36, [
                    _cache[15] || (_cache[15] = createBaseVNode("label", { class: "text-muted small" }, "Описание проекта", -1)),
                    createBaseVNode("p", _hoisted_37, toDisplayString($data.request.description), 1)
                  ]),
                  createBaseVNode("div", _hoisted_38, [
                    _cache[18] || (_cache[18] = createBaseVNode("label", { class: "text-muted small" }, "Локация объекта", -1)),
                    createBaseVNode("p", _hoisted_39, [
                      _cache[16] || (_cache[16] = createBaseVNode("i", { class: "fas fa-map-marker-alt text-danger me-2" }, null, -1)),
                      createTextVNode(" " + toDisplayString(((_a = $data.request.location) == null ? void 0 : _a.name) || "Не указана") + " ", 1),
                      _cache[17] || (_cache[17] = createBaseVNode("br", null, null, -1)),
                      createBaseVNode("small", _hoisted_40, toDisplayString(((_b = $data.request.location) == null ? void 0 : _b.address) || ""), 1)
                    ])
                  ])
                ]),
                createBaseVNode("div", _hoisted_41, [
                  createBaseVNode("div", _hoisted_42, [
                    _cache[21] || (_cache[21] = createBaseVNode("label", { class: "text-muted small" }, "Период аренды", -1)),
                    createBaseVNode("p", _hoisted_43, [
                      _cache[19] || (_cache[19] = createBaseVNode("i", { class: "fas fa-calendar-alt text-primary me-2" }, null, -1)),
                      createTextVNode(" " + toDisplayString($options.formatDate($data.request.rental_period_start)) + " - " + toDisplayString($options.formatDate($data.request.rental_period_end)) + " ", 1),
                      _cache[20] || (_cache[20] = createBaseVNode("br", null, null, -1)),
                      createBaseVNode("small", _hoisted_44, toDisplayString($options.calculateRentalDays($data.request.rental_period_start, $data.request.rental_period_end)) + " дней ", 1)
                    ])
                  ]),
                  createBaseVNode("div", _hoisted_45, [
                    _cache[22] || (_cache[22] = createBaseVNode("label", { class: "text-muted small" }, "Бюджет заявки", -1)),
                    createBaseVNode("p", _hoisted_46, toDisplayString($options.formatCurrency($data.request.total_budget || $data.request.calculated_budget_from)), 1)
                  ]),
                  createBaseVNode("div", _hoisted_47, [
                    _cache[25] || (_cache[25] = createBaseVNode("label", { class: "text-muted small" }, "Доставка", -1)),
                    createBaseVNode("p", _hoisted_48, [
                      _cache[24] || (_cache[24] = createBaseVNode("i", { class: "fas fa-truck text-info me-2" }, null, -1)),
                      createTextVNode(" " + toDisplayString($data.request.delivery_required ? "Требуется доставка техники к объекту" : "Доставка не требуется") + " ", 1),
                      $data.request.delivery_required ? (openBlock(), createElementBlock("span", _hoisted_49, [..._cache[23] || (_cache[23] = [
                        createBaseVNode("i", { class: "fas fa-check me-1" }, null, -1),
                        createTextVNode("Включена ", -1)
                      ])])) : createCommentVNode("", true)
                    ])
                  ])
                ])
              ])
            ])
          ]),
          $data.request.items && $data.request.items.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_50, [
            createBaseVNode("div", _hoisted_51, [
              createBaseVNode("h5", _hoisted_52, [
                _cache[26] || (_cache[26] = createBaseVNode("i", { class: "fas fa-cubes me-2" }, null, -1)),
                _cache[27] || (_cache[27] = createTextVNode(" Позиции заявки ", -1)),
                createBaseVNode("span", _hoisted_53, toDisplayString($data.summary.total_items), 1)
              ])
            ]),
            createBaseVNode("div", _hoisted_54, [
              $data.groupedByCategory.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_55, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.groupedByCategory, (category) => {
                  return openBlock(), createBlock(_component_CategoryGroup, {
                    key: category.category_id,
                    category,
                    "initially-expanded": $data.groupedByCategory.length <= 3
                  }, null, 8, ["category", "initially-expanded"]);
                }), 128))
              ])) : (openBlock(), createElementBlock("div", _hoisted_56, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.request.items, (item) => {
                  return openBlock(), createBlock(_component_PositionCard, {
                    key: item.id,
                    item
                  }, null, 8, ["item"]);
                }), 128))
              ]))
            ])
          ])) : createCommentVNode("", true),
          createVNode(_component_ProposalsList, {
            "request-id": $props.requestId,
            proposals: $data.proposals,
            onProposalRejected: $options.onProposalRejected
          }, null, 8, ["request-id", "proposals", "onProposalRejected"])
        ]),
        createBaseVNode("div", _hoisted_57, [
          createVNode(_component_RequestStats, {
            request: $data.request,
            "views-count": $data.request.views_count || 0,
            "proposals-count": $data.request.responses_count || 0,
            "items-count": $data.request.items ? $data.request.items.length : 0
          }, null, 8, ["request", "views-count", "proposals-count", "items-count"]),
          createVNode(_component_RequestActions, {
            request: $data.request,
            onPauseRequest: $options.pauseRequest,
            onResumeRequest: $options.resumeRequest,
            onCancelRequest: $options.cancelRequest,
            onEditRequest: $options.editRequest
          }, null, 8, ["request", "onPauseRequest", "onResumeRequest", "onCancelRequest", "onEditRequest"]),
          createVNode(_component_QuickActions, {
            "request-id": $data.request.id
          }, null, 8, ["request-id"])
        ])
      ])
    ]),
    $data.showPauseModal ? (openBlock(), createBlock(_component_PauseRequestModal, {
      key: 0,
      "request-id": $data.request.id,
      onConfirmed: $options.pauseRequest,
      onClosed: _cache[1] || (_cache[1] = ($event) => $data.showPauseModal = false)
    }, null, 8, ["request-id", "onConfirmed"])) : createCommentVNode("", true),
    $data.showCancelModal ? (openBlock(), createBlock(_component_CancelRequestModal, {
      key: 1,
      "request-id": $data.request.id,
      onConfirmed: $options.cancelRequest,
      onClosed: _cache[2] || (_cache[2] = ($event) => $data.showCancelModal = false)
    }, null, 8, ["request-id", "onConfirmed"])) : createCommentVNode("", true)
  ])) : $data.loading ? (openBlock(), createElementBlock("div", _hoisted_58, [..._cache[28] || (_cache[28] = [
    createBaseVNode("div", {
      class: "spinner-border text-primary",
      role: "status"
    }, [
      createBaseVNode("span", { class: "visually-hidden" }, "Загрузка...")
    ], -1),
    createBaseVNode("p", { class: "mt-2" }, "Загрузка заявки...", -1)
  ])])) : $data.error ? (openBlock(), createElementBlock("div", _hoisted_59, [
    _cache[29] || (_cache[29] = createBaseVNode("i", { class: "fas fa-exclamation-triangle me-2" }, null, -1)),
    createTextVNode(" " + toDisplayString($data.error) + " ", 1),
    _cache[30] || (_cache[30] = createBaseVNode("br", null, null, -1)),
    createBaseVNode("button", {
      class: "btn btn-outline-danger btn-sm mt-2",
      onClick: _cache[3] || (_cache[3] = (...args) => $options.loadRequest && $options.loadRequest(...args))
    }, " Попробовать снова ")
  ])) : createCommentVNode("", true);
}
const RentalRequestShow = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-cf081581"]]);
document.addEventListener("DOMContentLoaded", () => {
  var _a;
  const appElement = document.getElementById("rental-request-show-app");
  if (appElement) {
    const requestId = appElement.dataset.requestId;
    const apiUrl = appElement.dataset.apiUrl;
    const pauseUrl = appElement.dataset.pauseUrl || `/api/lessee/rental-requests/${requestId}/pause`;
    const cancelUrl = appElement.dataset.cancelUrl || `/api/lessee/rental-requests/${requestId}/cancel`;
    const csrfToken = appElement.dataset.csrfToken || ((_a = document.querySelector('meta[name="csrf-token"]')) == null ? void 0 : _a.content);
    console.log("RentalRequestShow app initialization:", {
      requestId,
      apiUrl,
      pauseUrl,
      cancelUrl,
      hasCsrfToken: !!csrfToken
    });
    const app = createApp(RentalRequestShow, {
      requestId: parseInt(requestId),
      apiUrl,
      pauseUrl,
      cancelUrl,
      csrfToken
    });
    app.component("ProposalsList", ProposalsList);
    app.component("RequestStats", RequestStats);
    app.component("RequestActions", RequestActions);
    app.component("QuickActions", QuickActions);
    app.component("PauseRequestModal", PauseRequestModal);
    app.component("CancelRequestModal", CancelRequestModal);
    app.component("RentalConditionsDisplay", RentalConditionsDisplay);
    app.component("SpecificationsDisplay", SpecificationsDisplay);
    app.component("PositionCard", PositionCard);
    app.component("CategoryGroup", CategoryGroup);
    app.mount("#rental-request-show-app");
    console.log("RentalRequestShow app mounted successfully with all components");
  } else {
    console.log("Element #rental-request-show-app not found - this is normal on other pages");
  }
});
