// resources/js/vue-manager.js

class VueAppManager {
    constructor() {
        this.initializedApps = new Set();
        this.appInstances = new Map();
    }

    // Проверяет, можно ли инициализировать приложение
    canInitialize(appId) {
        // 🔥 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Не инициализировать несколько приложений на одной странице
        const existingApps = [
            'rental-requests-app',
            'public-rental-request-show-app',
            'rental-request-edit-app',
            'rental-request-app'
        ];

        const hasOtherApp = existingApps.some(id =>
            id !== appId && document.getElementById(id)
        );

        if (hasOtherApp) {
            console.warn(`⚠️ VueAppManager: Обнаружены другие приложения, пропускаем ${appId}`);
            return false;
        }

        return !this.initializedApps.has(appId);
    }

    // Регистрирует инициализированное приложение
    registerApp(appId, appInstance) {
        this.initializedApps.add(appId);
        this.appInstances.set(appId, appInstance);
        console.log(`✅ VueAppManager: Зарегистрировано приложение ${appId}`);
    }

    // Получает экземпляр приложения
    getApp(appId) {
        return this.appInstances.get(appId);
    }

    // Проверяет существование приложения
    hasApp(appId) {
        return this.initializedApps.has(appId);
    }
}

// Глобальный экземпляр менеджера
window.vueAppManager = new VueAppManager();
