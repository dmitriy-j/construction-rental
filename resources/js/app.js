import './bootstrap';
import Alpine from 'alpinejs';
import * as bootstrap from 'bootstrap';

window.Alpine = Alpine;
window.bootstrap = bootstrap;

Alpine.start();

// Явно импортируем Bootstrap и делаем глобальным


// Добавьте CSRF токен для всех запросов
window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Инициализация компонентов Bootstrap
document.addEventListener('DOMContentLoaded', () => {
    // Инициализация тултипов
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        .forEach(tooltip => new bootstrap.Tooltip(tooltip));

    // Инициализация других компонентов при необходимости
    // [].slice.call(document.querySelectorAll('.dropdown-toggle'))
    //     .forEach(dropdown => new bootstrap.Dropdown(dropdown));
});
