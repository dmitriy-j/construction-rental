import { createApp } from 'vue';
import AdminDashboard from './AdminDashboard.vue';

const appId = 'admin-dashboard-app';
const element = document.getElementById(appId);

if (element && !element.__vue_app__) {
    const app = createApp(AdminDashboard);
    app.mount(element);
    console.log('✅ Admin dashboard mounted');
}
