// resources/js/vue-manager.js

class VueAppManager {
    constructor() {
        this.initializedApps = new Set();
        this.appInstances = new Map();
        this.registeredComponents = new Map();
        console.log('✅ VueAppManager инициализирован');
    }

    // 🔥 УЛУЧШЕННАЯ ПРОВЕРКА С ПРОВЕРКОЙ DOM
    canInitialize(appId) {
        // 🔥 ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА ДЛЯ БЕЗОПАСНОСТИ
        const appElement = document.getElementById(appId);

        // Если элемента нет в DOM
        if (!appElement) {
            console.warn(`⚠️ VueAppManager: Элемент ${appId} не найден в DOM`);
            return false;
        }

        // Если уже инициализирован через менеджер
        if (this.initializedApps.has(appId)) {
            console.warn(`⚠️ VueAppManager: Приложение ${appId} уже инициализировано через менеджер`);
            return false;
        }

        // 🔥 ПРОВЕРКА НА ПРЯМОЕ СВОЙСТВО VUE (дополнительная защита)
        if (appElement.__vue_app__) {
            console.warn(`⚠️ VueAppManager: На элемент ${appId} уже напрямую смонтировано Vue приложение`);
            return false;
        }

        const existingApps = [
            'rental-requests-app',
            'public-rental-request-show-app',
            'rental-request-edit-app',
            'rental-request-app',
            'lessor-rental-requests-app'
        ];

        const hasOtherApp = existingApps.some(id =>
            id !== appId && document.getElementById(id)
        );

        if (hasOtherApp) {
            console.warn(`⚠️ VueAppManager: Обнаружены другие приложения, пропускаем ${appId}`);
            return false;
        }

        return true;
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

    // 🔥 БЕЗОПАСНАЯ ИНИЦИАЛИЗАЦИЯ ПРИЛОЖЕНИЯ
    initializeApp(appId, appInstance) {
        if (!this.canInitialize(appId)) {
            console.warn(`App ${appId} initialization skipped by manager`);
            return false;
        }

        try {
            const appElement = document.getElementById(appId);
            if (!appElement) {
                throw new Error(`Element #${appId} not found`);
            }

            appInstance.mount(appElement);
            this.registerApp(appId, appInstance);

            console.log(`✅ VueAppManager: Приложение ${appId} успешно смонтировано`);
            return true;
        } catch (error) {
            console.error(`VueAppManager: Failed to initialize app ${appId}:`, error);
            this.showFallback(appId);
            return false;
        }
    }

    // Метод для отображения fallback
    showFallback(appId) {
        const fallbackElement = document.getElementById(`${appId}-fallback`);
        if (fallbackElement) {
            fallbackElement.style.display = 'block';
            console.log(`✅ VueAppManager: Показан fallback для ${appId}`);
        }

        // Скрываем Vue app при ошибке
        const vueAppElement = document.getElementById(appId);
        if (vueAppElement) {
            vueAppElement.style.display = 'none';
        }
    }

    // 🔥 ДОБАВЛЯЕМ МЕТОД ДЛЯ РЕГИСТРАЦИИ КОМПОНЕНТОВ
    registerComponent(name, component) {
        this.registeredComponents.set(name, component);
        console.log(`✅ VueAppManager: Зарегистрирован компонент ${name}`);
    }

    // 🔥 ДОБАВЛЯЕМ МЕТОД ДЛЯ ПОЛУЧЕНИЯ КОМПОНЕНТА
    getComponent(name) {
        return this.registeredComponents.get(name);
    }

    // 🔥 ДОБАВЛЯЕМ МЕТОД ДЛЯ УНИЧТОЖЕНИЯ ПРИЛОЖЕНИЯ
    unmountApp(appId) {
        const app = this.appInstances.get(appId);
        if (app) {
            try {
                app.unmount();
                this.initializedApps.delete(appId);
                this.appInstances.delete(appId);
                console.log(`✅ VueAppManager: Приложение ${appId} уничтожено`);
            } catch (error) {
                console.error(`VueAppManager: Ошибка уничтожения приложения ${appId}:`, error);
            }
        }
    }
}

// Глобальный экземпляр менеджера
window.vueAppManager = new VueAppManager();
