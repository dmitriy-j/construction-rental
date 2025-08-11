Экспертные роли для нового чата:
Laravel Architect
Проектирование структуры БД, моделей, миграций и отношений

Business Logic Engineer
Реализация workflow путевых листов согласно бизнес-процессам

UI/UX Specialist
Проектирование интерфейсов для диспетчеров и операторов

Testing Automation Expert
Создание тестов для критических процессов

Legacy Integration Specialist
Адаптация под существующие модели (Order, Equipment, Operator)

Workflow Optimizer
Оптимизация процессов продления аренды и закрытия периодов

Правила взаимодействия:
Все изменения должны соответствовать принципу:
Арендодатель → Платформа → Арендатор

Сохранение обратной совместимости с существующим кодом

Приоритет: юридическая корректность документов ЭСМ-2

Использовать статусную модель для всех сущностей

Реализовать историю изменений для аудита

Логика работы системы:
Diagram
Code

graph TD
    A[Начало аренды] --> B{Период аренды}
    B -->|≤ 10 дней| C[Создать 1 Waybill]
    B -->|> 10 дней| D[Создать N Waybills]
    C --> E[Статус: ACTIVE]
    D --> F[Первый: ACTIVE<br>Остальные: FUTURE]
    E --> G[Диспетчер заполняет данные]
    G --> H{Закрытие Waybill}
    H -->|Ручное| I[Создание CompletionAct]
    H -->|Авто| J[Активация следующего Waybill]
    I --> K[Обновление Order.actual_cost]
    J --> K
Ключевые требования:
Waybill:

1 Waybill = 1 оператор + до 10 дней

Статусы: FUTURE → ACTIVE → COMPLETED

Автопродление при закрытии

Связи: operator, days, order

WaybillDay:

Поля: work_date, hours_worked, downtime_hours, notes

Не редактировать после закрытия Waybill

Акты выполненных работ:

Автоматическое создание при закрытии Waybill

Расчет суммы: total_hours * hourly_rate

PDF-генерация по шаблону ЭСМ-2

Продление аренды:

Автосоздание дополнительных Waybill

Учет уже закрытых периодов

Уведомление диспетчеров

Необходимые файлы:
Предоставить в новый чат:

Модели:

Waybill.php

CompletionAct.php

Operator.php

Equipment.php

Order.php

RentalCondition.php

Контроллеры:

LessorOrderController.php

WaybillController.php

Миграции:

Структура таблиц waybills, operators, equipment

Документация:

Образец ЭСМ-2: [assistentus.ru/.../obrazec-putevoi-list-stroitelnoy-mashini-esm-2.xls]

Создать с нуля:

markdown
1. Модели:
   - `WaybillDay.php`
   - `WaybillService.php`

2. Миграции:
   - `2025_08_08_create_waybill_days_table.php`
   - `2025_08_08_alter_waybills_table.php`
   - `2025_08_08_add_operators_to_equipment.php`

3. Контроллеры:
   - `CompletionActController.php`
   - `WaybillDayController.php`

4. Сервисы:
   - `WaybillCreationService.php`
   - `WaybillActivationService.php`

5. Views:
   - `waybills/active.blade.php`
   - `waybills/show.blade.php`
   - `completion-acts/show.blade.php`

6. Тесты:
   - `WaybillPeriodTest.php`
   - `WaybillClosingTest.php`
   - `RentalExtensionTest.php`
Промт для нового чата:
markdown
Вы являетесь командой разработчиков платформы аренды строительной техники.
Требуется реализовать систему путевых листов ЭСМ-2 согласно следующим правилам:

1. **Общие принципы:**
   - Каждый Waybill привязан к 1 оператору (дневная/ночная смена)
   - Максимальный период Waybill: 10 дней
   - Статусы: FUTURE → ACTIVE → COMPLETED
   - Данные заполняются диспетчерами со слов операторов

2. **Создание Waybill:**
   - При начале аренды автоматически создаются Waybill:
     * Для дневного оператора (equipment.operator_day_id)
     * Для ночного оператора (equipment.operator_night_id)
   - Для периодов >10 дней создаются последовательные Waybill
   - Пример: 31 день = 4 Waybill (10+10+10+1)

3. **Работа с Waybill:**
   - Диспетчеры видят только ACTIVE Waybill
   - Закрытие Waybill → создание CompletionAct
   - После закрытия активируется следующий Waybill

4. **Продление аренды:**
   - Автосоздание дополнительных Waybill
   - Учет уже существующих периодов
   - Уведомление диспетчеров

5. **Технические требования:**
   - PHP 8.1, Laravel 10
   - Использовать Eloquent отношения
   - Реализовать Service-слой для бизнес-логики
   - Добавить history log для критических операций
   - Генерация PDF для CompletionAct

**Предоставленные файлы:**
[список приложенных файлов]

**Задачи:**
1. Создать миграции для новой структуры
2. Реализовать модели с отношениями
3. Разработать сервисы создания/закрытия Waybill
4. Создать интерфейс для диспетчеров
5. Реализовать PDF-генератор актов
6. Написать feature-тесты

**Важные нюансы:**
- WaybillDay не редактируется после закрытия Waybill
- При продлении аренды статус новых Waybill = FUTURE
- Расчет стоимости в акте: total_hours * hourly_rate
- Интерфейс должен отображать только ACTIVE Waybill
Рекомендации по запуску:
Начните с проектирования БД (миграции)

Реализуйте сервисный слой для сложной логики

Протестируйте сценарии:

Аренда 5 дней

Аренда 15 дней с продлением

Досрочное закрытие

Реализуйте интерфейс на Livewire для динамического обновления

Готов предоставить дополнительные детали по любому аспекту системы. Какие уточнения вам нужны перед запуском нового чата, Дмитрий Иванович?
