Отлично, давайте структурируем процесс и разработаем корректное техническое задание. Вы абсолютно правы - генерация УПД должна быть завершающим этапом, когда вся логика работы с заказами и документами уже реализована.

1. Корректировка терминологии и полей
contract_date → agreement_date (дата заключения договора)
delivery_date → service_start_date (дата начала оказания услуг)
Новое поле → service_end_date (дата окончания оказания услуг)

2. Полный процесс аренды с документами
Оформление заказа:

Арендатор формирует заказ в системе

Система рассчитывает предварительную стоимость

Заключение договора:

Стороны подписывают договор (дата → agreement_date)

Фиксируются особые условия:

Форма оплаты (предоплата/постоплата)

Сроки предоставления документов

Размеры штрафов за простой

Передача техники:

Оформляется транспортная накладная

Фиксируется service_start_date

Рабочий процесс:

Ежедневное заполнение путевых листов

Фиксация отработанных часов/смен

Отметки о простоях и их причинах

Завершение аренды:

Фиксация service_end_date

Формирование акта выполненных работ

Расчет фактической стоимости с учетом:

Отработанного времени

Простоев и штрафов

Предоплаты (если была)

Закрывающие документы:

Формирование УПД/счетов-фактур

Отправка арендатору

Оплата

3. Необходимые сущности и миграции
3.1. Договора (Contracts)
php
Schema::create('contracts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained();
    $table->string('number')->comment('Номер договора');
    $table->date('agreement_date')->comment('Дата подписания');
    $table->enum('payment_type', ['prepay', 'postpay', 'mixed']);
    $table->integer('documentation_deadline')->comment('Срок предоставления документов (дни)');
    $table->integer('payment_deadline')->comment('Срок оплаты (дни)');
    $table->decimal('penalty_rate', 5, 2)->comment('Процент штрафа за простой');
    $table->string('file_path')->comment('Скан договора');
    $table->timestamps();
});
3.2. Транспортные накладные (DeliveryNotes)
php
Schema::create('delivery_notes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained();
    $table->date('delivery_date')->comment('Дата доставки');
    $table->string('driver_name');
    $table->string('receiver_name');
    $table->string('receiver_signature')->comment('Подпись получателя');
    $table->text('equipment_condition')->comment('Состояние оборудования');
    $table->timestamps();
});
3.3. Путевые листы (Waybills)
php
Schema::create('waybills', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained();
    $table->foreignId('equipment_id')->constrained();
    $table->date('work_date');
    $table->decimal('hours_worked', 8, 2);
    $table->decimal('downtime_hours', 8, 2)->default(0);
    $table->enum('downtime_cause', ['lessee', 'lessor', 'force_majeure'])->nullable();
    $table->string('operator_signature');
    $table->string('customer_signature');
    $table->timestamps();
});
3.4. Акты выполненных работ (CompletionActs)
php
Schema::create('completion_acts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained();
    $table->date('service_start_date');
    $table->date('service_end_date');
    $table->decimal('total_hours', 10, 2);
    $table->decimal('total_downtime', 10, 2);
    $table->decimal('penalty_amount', 12, 2);
    $table->decimal('total_amount', 12, 2);
    $table->decimal('prepayment_amount', 12, 2)->default(0);
    $table->decimal('final_amount', 12, 2);
    $table->string('act_file_path');
    $table->enum('status', ['draft', 'signed', 'paid', 'disputed']);
    $table->timestamps();
});
4. Процесс работы с документами
4.1. Кто заполняет?
Путевые листы:

Машинисты/операторы через мобильное приложение

Или диспетчеры арендодателя

Акты выполненных работ:

Автоматическое формирование системой на основе путевых листов

Ручная корректировка менеджером платформы

Транспортные накладные:

Менеджер платформы или представитель арендодателя

4.2. Автоматизация документооборота
Diagram
Code
5. Реализация в системе
5.1. Автоматическое формирование акта
php
class CompletionActGenerator
{
    public function generateForOrder(Order $order)
    {
        $waybills = $order->waybills;

        $act = new CompletionAct([
            'order_id' => $order->id,
            'service_start_date' => $order->deliveryNotes->delivery_date,
            'service_end_date' => now(),
            'total_hours' => $waybills->sum('hours_worked'),
            'total_downtime' => $waybills->sum('downtime_hours'),
            'penalty_amount' => $this->calculatePenalty($waybills),
            'prepayment_amount' => $order->prepayments->sum('amount'),
        ]);

        $act->total_amount = $this->calculateTotalAmount($order, $waybills);
        $act->final_amount = $act->total_amount - $act->prepayment_amount - $act->penalty_amount;

        return $act;
    }

    private function calculatePenalty($waybills)
    {
        return $waybills->filter(fn($w) => $w->downtime_cause === 'lessee')
            ->sum(fn($w) => $w->downtime_hours * $this->penaltyRate);
    }
}
5.2. Управление сроками
php
class ContractDeadlineService
{
    public function checkDeadlines()
    {
        $contracts = Contract::where('status', 'active')->get();

        foreach ($contracts as $contract) {
            $this->checkDocumentationDeadline($contract);
            $this->checkPaymentDeadline($contract);
        }
    }

    private function checkDocumentationDeadline(Contract $contract)
    {
        $deadline = $contract->agreement_date->addDays($contract->documentation_deadline);

        if (now() > $deadline && !$contract->has_documents) {
            $contract->applyPenalty();
            $contract->notifyParties();
        }
    }
}
6. Генерация УПД (когда будете готовы)
php
class UPDPdfGenerator
{
    public function generateForOrder(Order $order)
    {
        $act = $order->completionAct;

        return view('pdf.upd', [
            'order' => $order,
            'act' => $act,
            'period' => "с {$act->service_start_date->format('d.m.Y')} по {$act->service_end_date->format('d.m.Y')}",
            'services' => $this->prepareServicesData($order)
        ]);
    }

    private function prepareServicesData(Order $order)
    {
        return $order->equipment->map(function($eq) use ($order) {
            $hours = $order->waybills->where('equipment_id', $eq->id)->sum('hours_worked');

            return [
                'name' => "Аренда {$eq->name} ({$eq->model})",
                'quantity' => $hours,
                'unit' => 'час',
                'price' => $eq->hourly_rate,
                'total' => $hours * $eq->hourly_rate
            ];
        });
    }
}
7. Рекомендации по внедрению
Этапность разработки:

Сначала реализуйте ядро: заказы + оборудование

Затем добавьте транспортные накладные

После - путевые листы и акты

В конце - финансовые документы (УПД)

Роли пользователей:

Машинисты: только путевые листы

Диспетчеры арендодателя: накладные + путевые листы

Менеджеры платформы: контроль всего процесса

Бухгалтерия: финансовые документы

Интеграции:

Мобильное приложение для машинистов

Электронная подпись документов

Интеграция с 1С/бухгалтерией

Безопасность:

Хранение подписей в защищенном хранилище

Ведение истории изменений документов

Резервное копирование

8. Отвечаю на ваш вопрос
Вы ничего не упустили! Это комплексная система, и вы правильно выделили все ключевые сущности. Для ответа на оставшиеся вопросы:

Где хранить договора?

В таблице contracts (метаданные + ссылка на скан)

Сканы в защищенном S3-хранилище

Автоматическое vs ручное формирование:

Автоматическое: для стандартных случаев

Ручное: для сложных случаев/споров

Всегда возможность пересчета

Расчет стоимости:

Первоначальный расчет при заказе

Корректировка при формировании акта

Фиксация итоговой суммы в акте

Работа с экипажем:

Отдельная таблица equipment_operators

Привязка к путевым листам

Учет сменности

Начните с проектирования основных сущностей и их взаимосвязей, затем реализуйте процесс пошагово. Когда дойдете до УПД - буду рад помочь с реализацией!
