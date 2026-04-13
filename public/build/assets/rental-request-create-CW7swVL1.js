import { c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
import { C as CreateRentalRequestForm, B as BudgetCalculator } from "./CreateRentalRequestForm-DNvbrxWN.js";
import { R as RequestItems, a as RentalConditions } from "./RequestItems-Cig7CHK3.js";
import "./_plugin-vue_export-helper-1tPrXgE0.js";
document.addEventListener("DOMContentLoaded", () => {
  const appElement = document.getElementById("rental-request-app");
  if (appElement && !appElement._vueApp) {
    const categories = JSON.parse(appElement.dataset.categories || "[]");
    const locations = JSON.parse(appElement.dataset.locations || "[]");
    const storeUrl = appElement.dataset.storeUrl;
    const csrfToken = appElement.dataset.csrfToken;
    console.log("Vue app initialization:", {
      categoriesCount: categories == null ? void 0 : categories.length,
      locationsCount: locations == null ? void 0 : locations.length,
      storeUrl
    });
    const app = createApp(CreateRentalRequestForm, {
      categories,
      locations,
      storeUrl,
      csrfToken
    });
    app.component("RequestItems", RequestItems);
    app.component("RentalConditions", RentalConditions);
    app.component("BudgetCalculator", BudgetCalculator);
    appElement._vueApp = app;
    app.mount("#rental-request-app");
    console.log("Vue app mounted successfully with all components");
  } else {
    console.log("Vue app already mounted or container not found.");
  }
});
