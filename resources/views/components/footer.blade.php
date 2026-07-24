<footer class="site-footer">
    {{-- Акцентная линия сверху --}}
    <div class="footer-accent"></div>

    <div class="container">
        {{-- Основная сетка --}}
        <div class="footer-grid">
            {{-- Колонка 1: Бренд --}}
            <div class="footer-column footer-brand-col">
                <div class="footer-logo">
                    <div class="logo-main">ФАП</div>
                    <div class="logo-subtitle">Федеральная Арендная Платформа</div>
                </div>
                <div class="footer-about">
                    <p>Федеральный оператор аренды строительной техники №1 в России. Работаем во всех 85 регионах страны с 2023 года.</p>
                </div>
                <div class="footer-social">
                    <a href="#" class="social-link" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
                    <a href="#" class="social-link" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" class="social-link" aria-label="VK"><i class="bi bi-vk"></i></a>
                    <a href="#" class="social-link" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            {{-- Колонка 2: Разделы --}}
            <div class="footer-column">
                <h5 class="footer-title">Разделы</h5>
                <ul class="footer-links">
                    <li><a href="/about">О компании</a></li>
                    <li><a href="/catalog">Каталог техники</a></li>
                    <li><a href="{{ route('rental-requests.index') }}">Заявки</a></li>
                    <li><a href="/repair">Ремонт</a></li>
                    <li><a href="/news">Новости</a></li>
                </ul>
            </div>

            {{-- Колонка 3: Помощь --}}
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

            {{-- Колонка 4: Контакты --}}
            <div class="footer-column">
                <h5 class="footer-title">Контакты</h5>
                <ul class="footer-contacts">
                    <li>
                        <span class="contact-icon"><i class="bi bi-geo-alt"></i></span>
                        <span>г. Москва, ул. Строителей, 12</span>
                    </li>
                    <li>
                        <span class="contact-icon"><i class="bi bi-telephone"></i></span>
                        <a href="tel:88001234567" class="contact-link">8 (800) 123-45-67</a>
                    </li>
                    <li>
                        <span class="contact-icon"><i class="bi bi-envelope"></i></span>
                        <a href="mailto:office@fap24.ru" class="contact-link">office@fap24.ru</a>
                    </li>
                    <li>
                        <span class="contact-icon"><i class="bi bi-clock"></i></span>
                        <span>Пн-Пт: 9:00-18:00</span>
                    </li>
                </ul>
                <div class="footer-badge-wrapper mt-3">
                    <span class="federal-badge badge">85 регионов России</span>
                </div>
            </div>
        </div>

        {{-- Нижняя часть --}}
        <div class="footer-bottom">
            <div class="footer-bottom-left">
                <div class="copyright">
                    &copy; {{ date('Y') }} Федеральная Арендная Платформа. Все права защищены.
                </div>
                <div class="footer-bottom-links">
                    <a href="/policy">Политика</a>
                    <a href="/terms">Условия</a>
                </div>
            </div>
            <div class="footer-bottom-right">
                <div class="footer-lang">
                    <select class="form-select form-select-sm">
                        <option>Русский</option>
                        <option>English</option>
                    </select>
                </div>
                <button class="scroll-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Наверх">
                    <i class="bi bi-arrow-up"></i>
                </button>
            </div>
        </div>
    </div>
</footer>

<style>
/* ============================================================
   ФУТЕР — ПРЕМИАЛЬНЫЙ УРОВЕНЬ
   Вдохновение: Linear, Stripe, Notion, Vercel
   ============================================================ */

/* --- Контейнер --- */
.site-footer {
    position: relative;
    background: linear-gradient(135deg, #002D72 0%, #001A4D 100%);
    padding: 4rem 0 1.5rem;
    color: rgba(255, 255, 255, 0.85);
    overflow: hidden;
}

/* Тонкая текстура поверх */
.site-footer::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
    z-index: 0;
}

.site-footer .container {
    max-width: 1320px;
    position: relative;
    z-index: 1;
}

/* --- Акцентная линия --- */
.footer-accent {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: calc(100% - 2rem);
    max-width: 1320px;
    height: 3px;
    background: linear-gradient(90deg, transparent, #FF8C00 20%, #FF6B00 50%, #FF8C00 80%, transparent);
    border-radius: 0 0 2px 2px;
    z-index: 2;
}

/* --- Сетка --- */
.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
    gap: 3rem;
    margin-bottom: 3rem;
}

@media (max-width: 992px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
    }
    .footer-brand-col {
        grid-column: 1 / -1;
    }
}

@media (max-width: 576px) {
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
}

/* --- Бренд --- */
.footer-logo .logo-main {
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    font-family: 'Montserrat', 'Inter', sans-serif;
    letter-spacing: -0.5px;
    line-height: 1;
}

.footer-logo .logo-subtitle {
    font-size: 0.8125rem;
    color: rgba(255,255,255,0.5);
    margin-top: 2px;
    font-weight: 400;
}

.footer-about {
    margin: 1.25rem 0;
    line-height: 1.7;
    font-size: 0.875rem;
    color: rgba(255,255,255,0.6);
    max-width: 320px;
}

/* --- Соцсети --- */
.footer-social {
    display: flex;
    gap: 0.625rem;
}

.social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.65);
    border-radius: 10px;
    text-decoration: none;
    font-size: 1.05rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255,255,255,0.08);
}

.social-link:hover {
    background: linear-gradient(135deg, #FF8C00, #FF6B00);
    color: #fff;
    transform: translateY(-3px) scale(1.05);
    border-color: transparent;
    box-shadow: 0 8px 20px rgba(255, 140, 0, 0.3);
}

/* --- Заголовки колонок --- */
.footer-title {
    color: #fff;
    font-weight: 600;
    font-size: 0.9375rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    position: relative;
    letter-spacing: 0.3px;
}

.footer-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 24px;
    height: 2px;
    background: #FF8C00;
    border-radius: 1px;
    transition: width 0.3s ease;
}

.footer-column:hover .footer-title::after {
    width: 40px;
}

/* --- Ссылки --- */
.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.625rem;
}

.footer-links a {
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-block;
    position: relative;
    padding-left: 0;
}

.footer-links a::before {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
    background: #FF8C00;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.footer-links a:hover {
    color: #FF8C00;
    transform: translateX(6px);
    padding-left: 4px;
}

.footer-links a:hover::before {
    width: 100%;
}

/* --- Контакты --- */
.footer-contacts {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-contacts li {
    margin-bottom: 0.875rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: rgba(255,255,255,0.6);
}

.contact-icon {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 2px;
}

.contact-icon i {
    color: #FF8C00;
    font-size: 0.9rem;
}

.contact-link {
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    transition: color 0.2s ease;
}

.contact-link:hover {
    color: #FF8C00;
}

.federal-badge {
    background: linear-gradient(135deg, #FF8C00, #FF6B00);
    color: #1a1a1a;
    font-weight: 700;
    border: none;
    padding: 0.4em 0.85em;
    font-size: 0.75rem;
    border-radius: 6px;
}

/* --- Нижняя часть --- */
.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.06);
    flex-wrap: wrap;
    gap: 1.25rem;
}

.footer-bottom-left {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.copyright {
    font-size: 0.8125rem;
    color: rgba(255,255,255,0.4);
}

.footer-bottom-links {
    display: flex;
    gap: 1rem;
}

.footer-bottom-links a {
    color: rgba(255,255,255,0.4);
    text-decoration: none;
    font-size: 0.8125rem;
    transition: color 0.2s ease;
}

.footer-bottom-links a:hover {
    color: #FF8C00;
}

.footer-bottom-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* --- Селект языка --- */
.footer-lang .form-select {
    max-width: 130px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.6);
    font-size: 0.8125rem;
    padding: 0.35rem 2rem 0.35rem 0.75rem;
    border-radius: 8px;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba(255,255,255,0.4)' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.footer-lang .form-select:hover {
    border-color: rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.1);
}

.footer-lang .form-select:focus {
    border-color: #FF8C00;
    box-shadow: 0 0 0 2px rgba(255, 140, 0, 0.15);
}

.footer-lang .form-select option {
    background: #002D72;
    color: #fff;
}

/* --- Кнопка "Наверх" --- */
.scroll-to-top {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem;
}

.scroll-to-top:hover {
    background: linear-gradient(135deg, #FF8C00, #FF6B00);
    border-color: transparent;
    color: #fff;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(255, 140, 0, 0.3);
}

/* --- Светлая тема (для авторизованных) --- */
.content-footer .site-footer {
    background: #fff;
    padding: 3rem 0 1.25rem;
}

.content-footer .site-footer::after {
    background-image: radial-gradient(rgba(0,0,0,0.02) 1px, transparent 1px);
}

.content-footer .footer-accent {
    background: linear-gradient(90deg, transparent, #0B5ED7 20%, #002D72 50%, #0B5ED7 80%, transparent);
}

.content-footer .logo-main {
    background: linear-gradient(135deg, #0B5ED7, #002D72);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.content-footer .logo-subtitle { color: rgba(0,0,0,0.4); }
.content-footer .footer-about p { color: rgba(0,0,0,0.55); }
.content-footer .footer-title { color: #1A1D21; }
.content-footer .footer-title::after { background: #0B5ED7; }
.content-footer .footer-links a { color: rgba(0,0,0,0.55); }
.content-footer .footer-links a:hover { color: #0B5ED7; }
.content-footer .footer-links a::before { background: #0B5ED7; }
.content-footer .footer-contacts li { color: rgba(0,0,0,0.55); }
.content-footer .contact-icon i { color: #0B5ED7; }
.content-footer .contact-link { color: rgba(0,0,0,0.55); }
.content-footer .contact-link:hover { color: #0B5ED7; }
.content-footer .social-link { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.06); color: rgba(0,0,0,0.5); }
.content-footer .social-link:hover { background: linear-gradient(135deg, #0B5ED7, #002D72); color: #fff; border-color: transparent; box-shadow: 0 8px 20px rgba(11,94,215,0.25); }
.content-footer .footer-bottom { border-top-color: rgba(0,0,0,0.06); }
.content-footer .copyright { color: rgba(0,0,0,0.4); }
.content-footer .footer-bottom-links a { color: rgba(0,0,0,0.4); }
.content-footer .footer-bottom-links a:hover { color: #0B5ED7; }
.content-footer .footer-lang .form-select { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.1); color: rgba(0,0,0,0.55); background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba(0,0,0,0.3)' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e"); }
.content-footer .footer-lang .form-select:focus { border-color: #0B5ED7; box-shadow: 0 0 0 2px rgba(11,94,215,0.15); }
.content-footer .footer-lang .form-select option { background: #fff; color: #1A1D21; }
.content-footer .scroll-to-top { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.1); color: rgba(0,0,0,0.4); }
.content-footer .scroll-to-top:hover { background: linear-gradient(135deg, #0B5ED7, #002D72); color: #fff; border-color: transparent; }
.content-footer .federal-badge { background: linear-gradient(135deg, #0B5ED7, #002D72); color: #fff; }

</style>
</write_to_file>
