const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/LessorRentalRequestList-DrPZ-CP3.js","assets/AnalyticsDashboard-BQ71M7_t.js","assets/RealTimeAnalytics-B0WV39m2.js","assets/_plugin-vue_export-helper-1tPrXgE0.js","assets/runtime-dom.esm-bundler-DgO_AsNV.js","assets/RealTimeAnalytics-B0oM9t9d.css","assets/StrategicAnalytics-uc6cXoOI.js","assets/StrategicAnalytics-DxYYXjft.css","assets/ProposalTemplates-OIORVCGX.js","assets/ProposalTemplates-DN6vGbLH.css","assets/QuickActionCard-Dnuq7SJ0.js","assets/QuickActionCard-BIYSPUtv.css","assets/sweetalert2.esm.all-DkqDp_b4.js","assets/AnalyticsDashboard-DPcyE9S5.css","assets/LessorRentalRequestList-CICIyho3.css","assets/TemplateCard-73Ow4mRz.js","assets/TemplateCard-BHANscmY.css","assets/RentalRequestDetail-XmX6D_Nt.js","assets/RentalRequestDetail-C0KhZ2Mz.css"])))=>i.map(i=>d[i]);
import { _ as __vitePreload } from "./preload-helper-DCPvANlu.js";
import { c as createApp } from "./runtime-dom.esm-bundler-DgO_AsNV.js";
console.log("🚀 lessor-rental-requests.js: Начало загрузки ЛК арендодателя");
document.addEventListener("DOMContentLoaded", function() {
  console.log("🔍 Поиск элемента lessor-rental-requests-app...");
  const appElement = document.getElementById("lessor-rental-requests-app");
  const fallbackElement = document.getElementById("lessor-html-fallback");
  if (!appElement) {
    console.error("❌ Элемент lessor-rental-requests-app не найден");
    if (fallbackElement) fallbackElement.style.display = "block";
    return;
  }
  console.log("✅ Элемент найден, начинаем загрузку Vue компонентов...");
  Promise.all([
    __vitePreload(() => import("./LessorRentalRequestList-DrPZ-CP3.js"), true ? __vite__mapDeps([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14]) : void 0),
    __vitePreload(() => import("./AnalyticsDashboard-BQ71M7_t.js"), true ? __vite__mapDeps([1,2,3,4,5,6,7,8,9,10,11,12,13]) : void 0),
    __vitePreload(() => import("./ProposalTemplates-OIORVCGX.js"), true ? __vite__mapDeps([8,4,3,9]) : void 0),
    __vitePreload(() => import("./RealTimeAnalytics-B0WV39m2.js"), true ? __vite__mapDeps([2,3,4,5]) : void 0),
    __vitePreload(() => import("./StrategicAnalytics-uc6cXoOI.js"), true ? __vite__mapDeps([6,3,4,7]) : void 0),
    __vitePreload(() => import("./QuickActionCard-Dnuq7SJ0.js"), true ? __vite__mapDeps([10,3,4,11]) : void 0),
    __vitePreload(() => import("./TemplateCard-73Ow4mRz.js"), true ? __vite__mapDeps([15,3,4,16]) : void 0),
    __vitePreload(() => import("./RentalRequestDetail-XmX6D_Nt.js"), true ? __vite__mapDeps([17,4,8,3,9,18]) : void 0)
    // 🔥 ДОБАВЛЕНО
  ]).then(([
    LessorRentalRequestListModule,
    AnalyticsDashboardModule,
    ProposalTemplatesModule,
    RealTimeAnalyticsModule,
    StrategicAnalyticsModule,
    QuickActionCardModule,
    TemplateCardModule,
    RentalRequestDetailModule
    // 🔥 ДОБАВЛЕНО
  ]) => {
    console.log("✅ Все компоненты ЛК арендодателя загружены");
    const app = createApp({});
    app.component("lessor-rental-request-list", LessorRentalRequestListModule.default);
    app.component("analytics-dashboard", AnalyticsDashboardModule.default);
    app.component("proposal-templates", ProposalTemplatesModule.default);
    app.component("real-time-analytics", RealTimeAnalyticsModule.default);
    app.component("strategic-analytics", StrategicAnalyticsModule.default);
    app.component("quick-action-card", QuickActionCardModule.default);
    app.component("template-card", TemplateCardModule.default);
    app.component("rental-request-detail", RentalRequestDetailModule.default);
    if (window.vueAppManager && window.vueAppManager.canInitialize("lessor-rental-requests-app")) {
      window.vueAppManager.initializeApp("lessor-rental-requests-app", app);
      appElement.style.display = "block";
      if (fallbackElement) fallbackElement.style.display = "none";
      console.log("✅ Vue приложение ЛК арендодателя успешно инициализировано через vue-manager");
    } else {
      app.mount(appElement);
      appElement.style.display = "block";
      if (fallbackElement) fallbackElement.style.display = "none";
      console.log("✅ Vue приложение ЛК арендодателя успешно инициализировано напрямую");
    }
  }).catch((error) => {
    console.error("❌ Ошибка загрузки компонентов ЛК арендодателя:", error);
    if (fallbackElement) {
      fallbackElement.style.display = "block";
      console.log("✅ Показан HTML fallback для ЛК арендодателя");
    }
  });
});
