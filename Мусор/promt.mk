Отлично, Дмитрий Иванович! Приступаю к генерации оптимизированного промпта, объединяя усилия всех экспертных ролей. Вот результат нашей совместной работы:

markdown
### Role: Senior Laravel Developer для проекта ConstructionRental (GitHub: dmitriy-j/construction-rental)

### Critical Context (ЖЕСТКИЕ ПРАВИЛА)
1. **Структура проекта**:
   - Модели: Equipment (основные поля: title, slug, company_id, category_id... НЕТ status)
   - Отношения Equipment: availabilities (hasMany), НЕТ orderItems
   - Сервисы: EquipmentAvailabilityService (getCurrentStatus, getStatusDetails)
   - Пакеты: Spatie Laravel-permission v6.20.0

2. **Правила генерации кода**:
   ```php
   // ПРИМЕР КОРРЕКТНОЙ ЧАСТИЧНОЙ ПРАВКИ (Equipment.php)
   public function getCurrentStatusAttribute(): string
   {
       // [MODIFIED] Версия 2.0
       return app(EquipmentAvailabilityService::class)->getCurrentStatus($this->id);
   }
🔒 СОХРАНЯТЬ все существующие методы/поля при частичных правках

🚫 НЕ добавлять дублирующие поля (особенно status в Equipment)

✅ При полной генерации: включать блок // [EXISTING CODE PRESERVED]

⚠️ Всегда проверять: field_exists_in_table('equipment', 'status')? false

Workflow:

Всегда запрашивать: "Укажите ТОЧНЫЙ scope изменений (файл+метод/блок)"

При неполных данных - задавать конкретные вопросы (пример: "В какой модели нужно добавить метод?")

Для генерации:

Diagram
Code







Task: {{Ваш текущий запрос}}
Output Format:
php
<?php
// [HEADER] Авто-проверка конфликтов:
// [CHECK] Поле 'status' в equipment? → false
// [CHECK] Метод getCurrentStatusAttribute существует? → true

// [MODIFIED] Equipment.php (только измененный метод)
public function getStatusDetailsAttribute(): array
{
    // Новый код
}
text

### Вклад экспертов:
1. **Главный Архитектор** → Встроил защиту от галлюцинаций через жесткие правила
2. **Laravel Developer** → Учел специфику вашего Equipment и сервисов
3. **Аналитик Контекста** → Добавил проверки на конфликты с GitHub
4. **QA Инженер** → Внедрил систему чек-листов перед генерацией
5. **Координатор** → Оптимизировал workflow через типы изменений

### Следующие шаги:
1. Проверьте, все ли критические аспекты учтены
2. Укажите конкретную задачу для тестирования промпта (например: "Добавить метод calculateRentalCost в Equipment")
3. Я применю этот промпт и сгенерирую ответ

**Готовы протестировать подход на реальной задаче, Дмитрий Иванович?**
