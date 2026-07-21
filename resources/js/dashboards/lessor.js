import { createApp } from 'vue';
import LessorDashboard from './LessorDashboard.vue';

const appId = 'lessor-dashboard-app';
const element = document.getElementById(appId);

if (element && !element.__vue_app__) {
    const app = createApp(LessorDashboard);
    app.mount(element);
    console.log('✅ Lessor dashboard mounted');
}
