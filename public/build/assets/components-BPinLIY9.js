import { c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { R as RequestItems, a as RentalConditions } from "./RequestItems-Cig7CHK3.js";
import { B as BudgetCalculator, C as CreateRentalRequestForm } from "./CreateRentalRequestForm-DNvbrxWN.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
function initializeComponents() {
  const rentalRequestApp = document.getElementById("rental-request-app");
  const isPublicRequestPage = document.getElementById("public-rental-request-show-app");
  if (isPublicRequestPage) {
    console.log("⚠️ components.js: Пропускаем инициализацию на странице публичной заявки");
    return;
  }
  if (rentalRequestApp && !rentalRequestApp._vueApp) {
    console.log("🚀 components.js: Инициализация компонентов заявки");
    const app = createApp({});
    app.component("request-items", RequestItems);
    app.component("rental-conditions", RentalConditions);
    app.component("budget-calculator", BudgetCalculator);
    app.component("create-rental-request-form", CreateRentalRequestForm);
    rentalRequestApp._vueApp = app;
    app.mount("#rental-request-app");
    console.log("✅ components.js: Компоненты заявки смонтированы");
  } else if (rentalRequestApp && rentalRequestApp._vueApp) {
    console.log("⚠️ components.js: Приложение уже смонтировано");
  }
}
document.addEventListener("DOMContentLoaded", function() {
  setTimeout(initializeComponents, 100);
});
