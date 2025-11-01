<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-column">
                <div class="footer-logo">
                    <div class="logo-main">ФАП</div>
                    <div class="logo-subtitle">Федеральная Арендная Платформа</div>
                </div>
                <div class="footer-about">
                    <p>Федеральный оператор аренды строительной техники №1 в России</p>
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
                    <li><i class="bi bi-telephone"></i> 8 (800) 123-45-67</li>
                    <li><i class="bi bi-envelope"></i> office@fap24.ru</li>
                    <li><i class="bi bi-clock"></i> Пн-Пт: 9:00-18:00</li>
                </ul>
                <div class="mt-3">
                    <span class="federal-badge badge">85 регионов России</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="copyright">
                &copy; 2024 Федеральная Арендная Платформа. Все права защищены.
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
    background: var(--bg-surface);
    border-top: 1px solid var(--divider);
    padding: 2rem 0 1rem;
}

.site-footer .container {
    max-height: none !important;
    overflow: visible !important;
}

.footer-grid {
    max-height: none !important;
    overflow: visible !important;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-column {
    max-height: none !important;
    overflow: visible !important;
}

.footer-logo .logo-main {
    font-size: 2rem;
    font-weight: 700;
    color: var(--fap-primary);
    font-family: 'Montserrat', sans-serif;
}

.footer-logo .logo-subtitle {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-top: -5px;
}

.footer-about {
    margin: 1rem 0;
    color: var(--text-secondary);
    line-height: 1.5;
}

.footer-social {
    display: flex;
    gap: 0.5rem;
}

.social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link:hover {
    background: var(--fap-dark);
    transform: translateY(-2px);
}

.footer-title {
    color: var(--text-primary);
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: var(--text-secondary);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: var(--fap-primary);
}

.footer-contacts {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-contacts li {
    margin-bottom: 0.75rem;
    display: flex;
    align-items: flex-start;
    color: var(--text-secondary);
}

.footer-contacts i {
    margin-right: 0.5rem;
    color: var(--fap-primary);
    width: 16px;
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--divider);
    flex-wrap: wrap;
    gap: 1rem;
}

.copyright {
    color: var(--text-secondary);
}

.footer-lang .form-select {
    max-width: 150px;
    border-color: var(--divider);
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

    .footer-bottom {
        flex-direction: column;
        text-align: center;
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
