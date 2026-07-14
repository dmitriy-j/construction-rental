const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/RentalRequestDetail-D1EYVn9X.js","assets/runtime-dom.esm-bundler-BObhqzw5.js","assets/ProposalTemplates-DKXx8w66.js","assets/_plugin-vue_export-helper-1tPrXgE0.js","assets/ProposalTemplates-DN6vGbLH.css","assets/RentalRequestDetail-C0KhZ2Mz.css"])))=>i.map(i=>d[i]);
import { _ as __vitePreload } from "./preload-helper-DCPvANlu.js";
import { c as createApp } from "./runtime-dom.esm-bundler-BObhqzw5.js";
console.log("🚀 lessor-rental-request-detail.js: Начало загрузки детальной страницы заявки");
document.addEventListener("DOMContentLoaded", function() {
  console.log("🔍 Поиск элемента lessor-rental-request-detail...");
  const appElement = document.getElementById("lessor-rental-request-detail");
  if (!appElement) {
    console.error("❌ Элемент lessor-rental-request-detail не найден");
    return;
  }
  console.log("✅ Элемент найден, начинаем загрузку Vue компонентов...");
  Promise.all([
    __vitePreload(() => import("./RentalRequestDetail-D1EYVn9X.js"), true ? __vite__mapDeps([0,1,2,3,4,5]) : void 0),
    __vitePreload(() => import("./ProposalTemplates-DKXx8w66.js"), true ? __vite__mapDeps([2,1,3,4]) : void 0)
  ]).then(([
    RentalRequestDetailModule,
    ProposalTemplatesModule
  ]) => {
    console.log("✅ Все компоненты детальной страницы загружены");
    const app = createApp({});
    app.component("rental-request-detail", RentalRequestDetailModule.default);
    app.component("proposal-templates", ProposalTemplatesModule.default);
    if (window.vueAppManager && window.vueAppManager.canInitialize("lessor-rental-request-detail")) {
      window.vueAppManager.initializeApp("lessor-rental-request-detail", app);
      console.log("✅ Vue приложение детальной страницы успешно инициализировано через vue-manager");
    } else {
      app.mount(appElement);
      console.log("✅ Vue приложение детальной страницы успешно инициализировано напрямую");
    }
  }).catch((error) => {
    console.error("❌ Ошибка загрузки компонентов детальной страницы:", error);
    console.error("📋 Детали ошибки:", error.message);
    console.error("🔄 Stack trace:", error.stack);
  });
});
