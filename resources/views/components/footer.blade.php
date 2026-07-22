<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-column">
                <div class="footer-logo">
                    <div class="logo-main">ФАП</div>
                    <div class="logo-subtitle">Федеральная Арендная Платформа</div>
                </div>
                <div class="footer-about">
                    <p>Федеральный оператор аренды строительной техники №1 в России. Работаем во всех 85 регионах страны.</p>
                </div>
                <div class="footer-social">
                    <a href="#" class="social-link" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
                    <a href="#" class="social-link" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" class="social-link" aria-label="VK"><i class="bi bi-vk"></i></a>
                    <a href="#" class="social-link" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <div class="footer-column">
                <h5 class="footer-title">Разделы</h5>
                <ul class="footer-links">
                    <li><a href="/about">О компании</a></li>
                    <li><a href="/catalog">Каталог техники</a></li>
                    <li><a href="{{ route('rental-requests.index') }}">Заявки</a></li>
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
                &copy; {{ date('Y') }} Федеральная Арендная Платформа. Все права защищены.
            </div>
            <div class="footer-lang d-flex align-items-center gap-2">
                <span class="small text-white-50">Язык:</span>
                <select class="form-select form-select-sm">
                    <option>Русский</option>
                    <option>English</option>
                </select>
            </div>
        </div>
    </div>
</footer>

<style>
/* ============================================================
   ФУТЕР — MODERN REDESIGN
   ============================================================ */
.site-footer {
    background: linear-gradient(135deg, #002D72 0%, #001A4D 100%);
    padding: 3.5rem 0 1.5rem;
    margin-top: auto;
    color: rgba(255, 255, 255, 0.85);
    position: relative;
}
.site-footer::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #FF8C00, #FF6B00);
}
.site-footer .container { max-width: 1320px; }

.footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2.5rem; margin-bottom: 2.5rem; }

.footer-logo .logo-main { font-size: 2rem; font-weight: 800; color: #fff; font-family: 'Montserrat', 'Inter', sans-serif; letter-spacing: -0.5px; }
.footer-logo .logo-subtitle { font-size: 0.8125rem; color: rgba(255,255,255,0.6); margin-top: -3px; }
.footer-about { margin: 1rem 0; line-height: 1.6; font-size: 0.875rem; color: rgba(255,255,255,0.65); max-width: 280px; }

.footer-social { display: flex; gap: 0.5rem; }
.social-link { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.7); border-radius: 50%; text-decoration: none; font-size: 1rem; transition: all 0.2s ease; border: 1px solid rgba(255,255,255,0.08); }
.social-link:hover { background: #FF8C00; color: #fff; transform: translateY(-2px); border-color: #FF8C00; box-shadow: 0 4px 12px rgba(255,140,0,0.3); }

.footer-title { color: #fff; font-weight: 600; margin-bottom: 1.25rem; font-size: 1rem; padding-bottom: 0.75rem; position: relative; }
.footer-title::after { content: ''; position: absolute; bottom: 0; left: 0; width: 30px; height: 2px; background: #FF8C00; border-radius: 1px; }

.footer-links { list-style: none; padding: 0; margin: 0; }
.footer-links li { margin-bottom: 0.625rem; }
.footer-links a { color: rgba(255,255,255,0.65); text-decoration: none; font-size: 0.875rem; transition: all 0.2s ease; }
.footer-links a:hover { color: #FF8C00; transform: translateX(4px); }

.footer-contacts { list-style: none; padding: 0; margin: 0; }
.footer-contacts li { margin-bottom: 0.75rem; display: flex; align-items: flex-start; font-size: 0.875rem; color: rgba(255,255,255,0.65); }
.footer-contacts i { margin-right: 0.625rem; color: #FF8C00; width: 16px; margin-top: 3px; flex-shrink: 0; }

.federal-badge { background: linear-gradient(135deg, #FF8C00, #FF6B00); color: #1a1a1a; font-weight: 700; border: none; padding: 0.35em 0.75em; font-size: 0.75rem; }

.footer-bottom { display: flex; justify-content: space-between; align-items: center; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); flex-wrap: wrap; gap: 1rem; }
.copyright { font-size: 0.8125rem; color: rgba(255,255,255,0.5); }
.footer-lang .form-select { max-width: 130px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.7); font-size: 0.8125rem; border-radius: 6px; }
.footer-lang .form-select:focus { border-color: #FF8C00; box-shadow: 0 0 0 2px rgba(255,140,0,0.15); }
.footer-lang .form-select option { background: #002D72; color: #fff; }

@media (max-width: 768px) { .site-footer { padding: 2.5rem 1rem 1.25rem; } .footer-grid { grid-template-columns: 1fr 1fr; gap: 1.5rem; } .footer-column:first-child { grid-column: 1 / -1; } }
@media (max-width: 480px) { .footer-grid { grid-template-columns: 1fr; } .footer-bottom { flex-direction: column; text-align: center; } .footer-title { text-align: center; } .footer-title::after { left: 50%; transform: translateX(-50%); } .footer-social { justify-content: center; } }

[data-theme="dark"] .site-footer { background: linear-gradient(135deg, #0D1B2A 0%, #1A1C23 100%); }
</style>
</write_to_file>
