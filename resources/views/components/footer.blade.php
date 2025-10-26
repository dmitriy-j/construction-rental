<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-column">
                <div class="footer-logo">
                    <div class="logo-main">ФАП</div>
                    <div class="logo-subtitle">Федеральная Арендная Платформа</div>
                </div>
                <div class="footer-about">
                    <p>Платформа для аренды строительной техники №1 в России</p>
                </div>
                <div class="footer-social">
                    <a href="#" class="social-link"><i class="bi bi-telegram"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-vk"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <div class="footer-column">
                <h5 class="footer-title">Разделы</h5>
                <ul class="footer-links">
                    <li><a href="/about">О компании</a></li>
                    <li><a href="/catalog">Каталог техники</a></li>
                    <li><a href="/requests">Заявки</a></li>
                    <li><a href="/free">Свободная техника</a></li>
                    <li><a href="/repair">Ремонт</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h5 class="footer-title">Помощь</h5>
                <ul class="footer-links">
                    <li><a href="/faq">FAQ</a></li>
                    <li><a href="/support">Техподдержка</a></li>
                    <li><a href="/docs">Документация</a></li>
                    <li><a href="/policy">Политика конфиденциальности</a></li>
                    <li><a href="/terms">Условия использования</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h5 class="footer-title">Контакты</h5>
                <ul class="footer-contacts">
                    <li><i class="bi bi-geo-alt"></i> г. Москва, ул. Строителей, 12</li>
                    <li><i class="bi bi-telephone"></i> +7 (495) 123-45-67</li>
                    <li><i class="bi bi-envelope"></i> info@fap-rental.ru</li>
                    <li><i class="bi bi-clock"></i> Пн-Пт: 9:00-18:00</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="copyright">
                &copy; 2023 ФАП. Все права защищены.
            </div>
            <div class="footer-lang">
                <select class="form-select">
                    <option>Русский</option>
                    <option>English</option>
                </select>
            </div>
        </div>
    </div>
</footer>

<style>
/* КРИТИЧЕСКИЕ ИСПРАВЛЕНИЯ ДЛЯ ФУТЕРА - УСТРАНЕНИЕ ДВОЙНОГО СКРОЛЛБАРА */
.site-footer {
    max-height: none !important;
    height: auto !important;
    overflow: visible !important;
    position: relative;
    z-index: 1;
}

.site-footer .container {
    max-height: none !important;
    overflow: visible !important;
}

.footer-grid {
    max-height: none !important;
    overflow: visible !important;
}

.footer-column {
    max-height: none !important;
    overflow: visible !important;
}

/* Гарантия что футер не создает скроллбар */
.site-footer,
.site-footer * {
    box-sizing: border-box !important;
    max-height: none !important;
    overflow: visible !important;
}

/* Фикс для очень маленьких экранов */
@media (max-width: 480px) {
    .footer-links li:nth-child(n+4) {
        display: list-item !important; /* Всегда показываем все элементы */
    }

    .show-more {
        display: none !important; /* Скрываем кнопку "Показать еще" */
    }
}
</style>

<script>
// ОБНОВЛЕННЫЙ СКРИПТ БЕЗ СОЗДАНИЯ ЛИШНИХ ЭЛЕМЕНТОВ
document.addEventListener('DOMContentLoaded', function() {
    function optimizeFooterForMobile() {
        const footer = document.querySelector('.site-footer');
        if (!footer) return;

        // УБИРАЕМ логику скрытия элементов - она вызывает проблемы со скроллбаром
        if (window.innerWidth < 768) {
            footer.classList.add('mobile-optimized');
        } else {
            footer.classList.remove('mobile-optimized');
        }

        // УДАЛЯЕМ все созданные кнопки "Показать еще" чтобы избежать накопления
        const existingShowMoreButtons = footer.querySelectorAll('.show-more');
        existingShowMoreButtons.forEach(btn => {
            btn.remove();
        });
    }

    // Запускаем только один раз при загрузке
    optimizeFooterForMobile();

    // УБИРАЕМ слушатель resize чтобы избежать многократного выполнения
    // window.addEventListener('resize', optimizeFooterForMobile);
});
</script>
