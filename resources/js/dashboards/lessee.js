import { createApp } from 'vue';
import LesseeDashboard from './LesseeDashboard.vue';

const appId = 'lessee-dashboard-app';
const element = document.getElementById(appId);

if (element && !element.__vue_app__) {
    const app = createApp(LesseeDashboard);
    app.mount(element);
    console.log('✅ Lessee dashboard mounted');
}
