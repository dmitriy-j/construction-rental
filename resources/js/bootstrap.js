import axios from 'axios';

// Указываем, что при запросах нужно отправлять куки (например, сессионные)
axios.defaults.withCredentials = true;

// Автоматически подставляем токен из куки 'XSRF-TOKEN' в заголовок 'X-XSRF-TOKEN'
axios.defaults.withXSRFToken = true; // Ключевая настройка для Laravel

// ИЛИ альтернативный вариант: вручную читаем токен из meta-тега
// let token = document.head.querySelector('meta[name="csrf-token"]');
// if (token) {
//     axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
// } else {
//     console.error('CSRF token not found: Check meta tag in app.blade.php');
// }

window.axios = axios; // Делаем axios глобально доступным
