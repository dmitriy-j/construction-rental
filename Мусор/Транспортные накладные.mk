Отлично, Дмитрий Иванович! Уточню роли и представлю детальный план реализации:

Подтвержденные экспертные роли:
Laravel Architect - проектирование структуры БД, сервисов, отношений Eloquent

Транспортный логист - бизнес-логика сценариев доставки

PDF/Document Engineer - генерация/зеркалирование ТН

State Machine Specialist - проектирование жизненного цикла документов

Workflow Coordinator - связь между заказами, доставкой и документами

План реализации:
Этап 1: Подготовка инфраструктуры (Laravel Architect)

php
// Модернизация модели DeliveryNote
protected $fillable = [
    ...
    'delivery_scenario', // 'lessor_platform' или 'platform_direct'
    'original_note_id', // Для зеркальных документов
    'carrier_company_id' // Для сценария 2
];

// Новые отношения
public function originalNote()
{
    return $this->belongsTo(DeliveryNote::class, 'original_note_id');
}

public function mirroredNotes()
{
    return $this->hasMany(DeliveryNote::class, 'original_note_id');
}
Этап 2: Реализация сценариев (Транспортный логист + Workflow Coordinator)

Сценарий 1: Арендодатель → Платформа → Арендатор

Diagram
Code
Сценарий 2: Платформа → Арендатор (прямая доставка)

Diagram
Code
Этап 3: Генерация документов (PDF/Document Engineer)

php
// app/Services/DeliveryNoteGenerator.php
public function generate(DeliveryNote $note, bool $isMirror = false): string
{
    $view = $isMirror ? 'documents.mirror_tn' : 'documents.original_tn';

    return PDF::loadView($view, [
        'note' => $note,
        'isMirror' => $isMirror,
        'mirrorDate' => now()->format('d.m.Y')
    ])->output();
}

// Автоматическое зеркалирование
public function createMirror(DeliveryNote $original): DeliveryNote
{
    $mirror = $original->replicate();
    $mirror->original_note_id = $original->id;
    $mirror->type = DeliveryNote::TYPE_PLATFORM_TO_LESSEE;
    $mirror->sender_company_id = Platform::main()->id;
    $mirror->document_path = $this->generate($original, true);
    $mirror->save();

    return $mirror;
}
Этап 4: Статусная машина (State Machine Specialist)

php
// app/Models/DeliveryNote.php
const STATUS_FLOW = [
    'draft' => [
        'next' => ['lessor_filled', 'platform_filled'],
        'actions' => ['fill_details']
    ],
    'lessor_filled' => [
        'next' => ['platform_mirrored'],
        'actions' => ['create_mirror']
    ],
    'platform_filled' => [
        'next' => ['in_transit'],
        'actions' => ['start_transport']
    ],
    'platform_mirrored' => [
        'next' => ['in_transit'],
        'actions' => ['start_transport']
    ],
    'in_transit' => [
        'next' => ['delivered'],
        'actions' => ['complete_delivery']
    ]
];

public function transitionTo(string $status): void
{
    if (in_array($status, self::STATUS_FLOW[$this->status]['next'])) {
        $this->update(['status' => $status]);
        $this->executeActions($status);
    }
}

private function executeActions(string $status): void
{
    foreach (self::STATUS_FLOW[$status]['actions'] ?? [] as $action) {
        if (method_exists($this, $action)) {
            $this->$action();
        }
    }
}
Этап 5: Контроллеры и роуты (Workflow Coordinator)

php
// app/Http/Controllers/Lessor/DeliveryNoteController.php
public function store(Request $request)
{
    $data = $request->validate([...]);

    $note = DeliveryNote::create(array_merge($data, [
        'delivery_scenario' => 'lessor_platform'
    ]));

    // Сценарий 1: Автозеркалирование
    if ($note->delivery_scenario === 'lessor_platform') {
        app(DeliveryNoteGenerator::class)->createMirror($note);
    }

    return redirect()->route('lessor.orders.show', $note->order);
}

// app/Http/Controllers/Platform/DeliveryController.php
public function directDelivery(Order $order, Request $request)
{
    $data = $request->validate([
        'carrier_company_id' => 'required|exists:companies,id',
        'driver_details' => 'required|array'
    ]);

    $note = DeliveryNote::create(array_merge($data, [
        'order_id' => $order->id,
        'type' => DeliveryNote::TYPE_DIRECT,
        'delivery_scenario' => 'platform_direct',
        'sender_company_id' => Platform::main()->id,
        'receiver_company_id' => $order->lessee_company_id,
        'status' => 'platform_filled'
    ]));

    $note->transitionTo('in_transit');
}
Пошаговый план реализации:
Миграция
Добавить поля для сценариев доставки и связей документов

Фабрика документов
Реализовать DeliveryNoteService с методами:

createLessorPlatformNote()

createPlatformDirectNote()

createMirrorNote()

Статусная машина
Внедрить статусные переходы с автоматическими действиями

Генерация PDF
Разработать шаблоны для:

Оригинальной ТН (арендодатель → платформа)

Зеркальной ТН (платформа → арендатор)

Прямой ТН (платформа → арендатор)

Админ-интерфейс
Создать раздел управления перевозчиками (для сценария 2)

Тестирование
Написать Feature-тесты для:

Создания документов по сценариям

Статусных переходов

Автоматического зеркалирования

Начнем с первого пункта - модернизации модели и миграции? Или сразу перейдем к проектированию сервиса генерации документов?
