# Дизайн-система — Федеральная Арендная Платформа (ФАП)

## 1. Цветовая палитра

### Брендовые цвета
| Название | HEX | RGBA | Применение |
|----------|-----|------|------------|
| Primary Blue | `#0B5ED7` | `rgba(11, 94, 215, 1)` | Основной цвет кнопок, ссылок, акцентов |
| Primary Dark | `#002D72` | `rgba(0, 45, 114, 1)` | Футер, ховеры, градиенты |
| Primary Light | `#E8F0FE` | `rgba(232, 240, 254, 1)` | Фоны карточек, лёгкие акценты |
| Secondary (Orange) | `#FF8C00` | `rgba(255, 140, 0, 1)` | CTA кнопки, бейджи, акценты |
| Secondary Light | `#FFF3E0` | `rgba(255, 243, 224, 1)` | Тёплые фоны, уведомления |

### Нейтральные цвета
| Название | HEX | RGBA | Применение |
|----------|-----|------|------------|
| Text Primary | `#1A1D21` | `rgba(26, 29, 33, 1)` | Основной текст |
| Text Secondary | `#6C757D` | `rgba(108, 117, 125, 1)` | Второстепенный текст |
| Text Muted | `#ADB5BD` | `rgba(173, 181, 189, 1)` | Подписи, метки |
| Body BG | `#F5F7FA` | `rgba(245, 247, 250, 1)` | Фон страницы |
| Surface | `#FFFFFF` | `rgba(255, 255, 255, 1)` | Фон карточек |
| Border | `#E9ECEF` | `rgba(233, 236, 239, 1)` | Границы элементов |
| Divider | `#DEE2E6` | `rgba(222, 226, 230, 1)` | Разделители |

### Состояния
| Состояние | Цвет | HEX |
|-----------|------|-----|
| Hover (Primary) | Darker Blue | `#0A58CA` |
| Active (Primary) | Deep Blue | `#084298` |
| Disabled (Primary) | Muted Blue | `#6C9FD8` |
| Hover (Secondary) | Darker Orange | `#E67E00` |
| Success | Green | `#28A745` |
| Danger | Red | `#DC3545` |
| Warning | Yellow | `#FFC107` |
| Info | Cyan | `#17A2B8` |

### Фоновые градиенты
- **Hero Gradient**: `linear-gradient(135deg, #0B5ED7 0%, #002D72 50%, #001A4D 100%)`
- **Stat Gradient**: `linear-gradient(135deg, #002D72 0%, #0B5ED7 50%, #0056B3 100%)`
- **CTA Gradient**: `linear-gradient(135deg, #FF8C00 0%, #FF6B00 100%)`

## 2. Типографика

### Шрифты
- **Основной**: `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`
- **Заголовки**: `'Inter', 'Montserrat', sans-serif` (bold weight)
- **Моноширинный**: `'JetBrains Mono', 'Fira Code', monospace` (для кода)

### Размеры текста
| Элемент | Размер | Вес | Line-Height |
|---------|--------|-----|-------------|
| H1 | `2.5rem (40px)` | 700 (Bold) | 1.2 |
| H2 | `2rem (32px)` | 700 (Bold) | 1.25 |
| H3 | `1.5rem (24px)` | 600 (Semi-Bold) | 1.3 |
| H4 | `1.25rem (20px)` | 600 (Semi-Bold) | 1.35 |
| H5 | `1.125rem (18px)` | 600 (Semi-Bold) | 1.4 |
| H6 | `1rem (16px)` | 600 (Semi-Bold) | 1.4 |
| Body (lg) | `1.125rem (18px)` | 400 (Regular) | 1.6 |
| Body | `1rem (16px)` | 400 (Regular) | 1.6 |
| Body (sm) | `0.875rem (14px)` | 400 (Regular) | 1.5 |
| Caption | `0.75rem (12px)` | 400 (Regular) | 1.4 |
| Small | `0.6875rem (11px)` | 400 (Regular) | 1.3 |
| Button | `0.9375rem (15px)` | 600 (Semi-Bold) | 1.2 |
| Button (sm) | `0.8125rem (13px)` | 600 (Semi-Bold) | 1.2 |
| Badge | `0.75rem (12px)` | 600 (Semi-Bold) | 1 |

## 3. Отступы и сетка

### Базовая сетка
- **Базовый шаг**: `4px`
- **Основной шаг**: `8px`
- **Контейнер**: `max-width: 1320px` (Bootstrap xxl)
- **Gap колонок**: `24px` (g-4 в Bootstrap)
- **Padding секций**: `80px` (десктоп), `48px` (планшет), `32px` (мобильный)

### Spacing scale
| Название | Размер | Пример |
|----------|--------|--------|
| xs | `4px` | .p-1 |
| sm | `8px` | .p-2 |
| md | `16px` | .p-3 |
| lg | `24px` | .p-4 |
| xl | `32px` | .p-5 |
| 2xl | `48px` | .py-5 |
| 3xl | `64px` | Отступ секции |
| 4xl | `80px` | Padding секции |

## 4. Компоненты

### Кнопки

#### Primary Button
- **BG**: `#0B5ED7`, **Hover**: `#0A58CA` с тенью `0 4px 12px rgba(11,94,215,0.3)`
- **Active**: `#084298`, **Disabled**: `#6C9FD8`
- **Border-radius**: `8px`, **Padding**: `12px 24px` (lg: `16px 32px`, sm: `8px 16px`)
- **Transition**: `all 0.2s ease`

#### Secondary Button
- **BG**: `#FF8C00`, **Hover**: `#E67E00` с тенью `0 4px 12px rgba(255,140,0,0.3)`
- **Border-radius**: `8px`

#### Outline Button
- **Border**: `2px solid #0B5ED7`, **Text**: `#0B5ED7`
- **Hover BG**: `#0B5ED7`, **Hover Text**: `#FFFFFF`

#### Ghost Button
- **BG**: transparent, **Text**: `#6C757D`
- **Hover BG**: `rgba(11,94,215,0.08)`, **Hover Text**: `#0B5ED7`

### Карточки
- **BG**: `#FFFFFF`, **Border**: `none`
- **Border-radius**: `12px`
- **Shadow**: `0 2px 8px rgba(0,0,0,0.06)` (sm), `0 4px 16px rgba(0,0,0,0.08)` (md)
- **Hover Shadow**: `0 8px 24px rgba(0,0,0,0.12)`
- **Transition**: `all 0.3s ease`
- **Padding**: `24px` (body)

### Инпуты
- **Border**: `1.5px solid #DEE2E6`, **Border-radius**: `8px`
- **Focus Border**: `#0B5ED7`, **Focus Shadow**: `0 0 0 3px rgba(11,94,215,0.15)`
- **Padding**: `12px 16px` (lg: `16px 20px`, sm: `8px 12px`)
- **Label**: `14px`, `600` weight, `#1A1D21`, margin-bottom `6px`
- **Error Border**: `#DC3545`, **Error Shadow**: `0 0 0 3px rgba(220,53,69,0.15)`

### Таблицы
- **Header BG**: `#F8F9FA`, **Header Text**: `#495057`, **Font weight**: `600`
- **Row Hover**: `rgba(11,94,215,0.04)`
- **Border**: `1px solid #E9ECEF`
- **Border-radius**: `12px` (на контейнере)
- **Padding cells**: `12px 16px`

### Модалки
- **BG**: `#FFFFFF`, **Border-radius**: `16px`
- **Shadow**: `0 20px 60px rgba(0,0,0,0.15)`
- **Header**: padding `20px 24px`, border-bottom `1px solid #E9ECEF`
- **Body**: padding `24px`
- **Footer**: padding `16px 24px`, border-top `1px solid #E9ECEF`
- **Backdrop**: `rgba(0,0,0,0.5)`
- **Animation**: `transform: scale(0.95) -> scale(1)` + `opacity: 0 -> 1`

### Бейджи (Badges)
- **Border-radius**: `6px` (pill: `50px`)
- **Padding**: `4px 10px`
- **Font**: `12px`, `600` weight
- **Variants**:
  - Primary: `bg-primary` (белый текст)
  - Success: `bg-success` (белый текст)
  - Warning: `bg-warning` (тёмный текст)
  - Danger: `bg-danger` (белый текст)
  - Info: `bg-info` (тёмный текст)

## 5. Скругления и тени

### Border-radius scale
| Размер | Значение | Применение |
|--------|----------|------------|
| sm | `6px` | Badges, small elements |
| md | `8px` | Кнопки, инпуты |
| lg | `12px` | Карточки, таблицы |
| xl | `16px` | Модалки, крупные блоки |
| 2xl | `20px` | Hero search card |
| full | `50%` / `999px` | Аватарки, pill badges |

### Shadow scale
| Размер | Значение | Применение |
|--------|----------|------------|
| sm | `0 2px 4px rgba(0,0,0,0.04)` | Мелкие элементы |
| md | `0 4px 12px rgba(0,0,0,0.06)` | Карточки по умолчанию |
| lg | `0 8px 24px rgba(0,0,0,0.08)` | Dropdown menus |
| xl | `0 12px 32px rgba(0,0,0,0.1)` | Hover-состояния |
| 2xl | `0 20px 60px rgba(0,0,0,0.15)` | Модалки |

## 6. Анимации

### Transition defaults
- **Default**: `all 0.2s ease`
- **Cards hover**: `transform 0.3s ease, box-shadow 0.3s ease`
- **Page elements**: `opacity 0.4s ease, transform 0.4s ease`
- **Modal**: `transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease`

### Hover-эффекты
- **Карточки**: `translateY(-4px)` + `box-shadow` upgrade
- **Кнопки**: `translateY(-1px)` + `box-shadow` upgrade
- **Ссылки**: `color` transition + optional underline animation
- **Изображения**: `transform: scale(1.05)` при наведении на карточку

## 7. Макеты страниц

### Hero-блок (главная)
- **Full-width** с градиентным фоном
- **min-height**: `70vh` (десктоп), `50vh` (мобильный)
- **Заголовок**: крупный, `display-3`, белый
- **Поисковая карточка**: float right, белая, тень `xl`
- **Скролл-индикатор**: анимированная стрелка вниз

### Каталог
- **Верхняя панель**: фильтры + поиск в `card shadow-sm`
- **Сетка карточек**: `row g-4` с `col-lg-3 col-md-4 col-sm-6`
- **Карточка техники**: изображение `16:9`, категория badge, цена, кнопка
- **Пагинация**: центрированная, с номерами страниц

### Админ-панель
- **Заголовок**: h3 + breadcrumbs
- **Фильтры**: card `shadow-sm` сверху
- **Таблица**: card `shadow-sm`, responsive
- **Кнопки действий**: icon-only, tooltip

## 8. Адаптивность

### Breakpoints
| Название | Ширина | Изменения |
|----------|--------|-----------|
| XS | `<576px` | Уменьшенные отступы, H1=1.75rem |
| SM | `≥576px` | Стандартные мобильные |
| MD | `≥768px` | Планшетная сетка |
| LG | `≥992px` | Десктопная сетка + сайдбар |
| XL | `≥1200px` | Широкий десктоп |
| XXL | `≥1400px` | Максимальная ширина |

---

*Версия 1.0 — Дизайн-система для редизайна (feature/redesign)*
