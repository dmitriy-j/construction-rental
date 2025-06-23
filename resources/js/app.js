import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'bootstrap/dist/css/bootstrap.min.css';


import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Инициализация компонентов Bootstrap
document.addEventListener('DOMContentLoaded', () => {
    // Инициализация тултипов
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        .forEach(tooltip => new bootstrap.Tooltip(tooltip));

    // Инициализация других компонентов при необходимости
    // [].slice.call(document.querySelectorAll('.dropdown-toggle'))
    //     .forEach(dropdown => new bootstrap.Dropdown(dropdown));
});
