Рекомендации по использованию:
Генерация УПД:

php
// app/Services/DocumentService.php
public function generateUPDAct(Order $order)
{
    $platform = $order->platform;

    $data = [
        'seller' => [
            'name' => $platform->name,
            'inn' => $platform->inn,
            'kpp' => $platform->kpp,
            'address' => $platform->legal_address,
            'bank_details' => [
                'name' => $platform->bank_name,
                'bic' => $platform->bic,
                'account' => $platform->settlement_account,
                'correspondent' => $platform->correspondent_account
            ]
        ],
        // ... аналогично для покупателя и других данных
    ];

    return PDF::loadView('documents.upd', $data);
}
Добавление подписи и печати:

php
// В blade-шаблоне documents/upd.blade.php
@if($platform->signature_image_path)
<div class="signature">
    <img src="{{ storage_path($platform->signature_image_path) }}" alt="Подпись">
    <div>{{ $platform->ceo_name }}</div>
</div>
@endif
API для получения реквизитов:

php
// app/Http/Controllers/API/PlatformController.php
public function details()
{
    return response()->json(Platform::first());
}
Важные замечания:
Хранение чувствительных данных:

php
// В модели Platform
protected $casts = [
    'settlement_account' => 'encrypted',
    'correspondent_account' => 'encrypted',
];
Валидация при обновлении:

php
public function rules()
{
    return [
        'inn' => 'required|digits:10,12',
        'kpp' => 'nullable|digits:9',
        'ogrn' => 'nullable|digits_between:13,15',
        'bic' => 'required|digits:9',
        'settlement_account' => 'required|digits:20',
    ];
}
Администрирование:

Создать отдельный раздел в админке для управления реквизитами

Добавить историю изменений (аудит)

Для полноценной работы с УПД рекомендую добавить поля:

okved - Основной вид деятельности

okato - Код территории

certificate_number - Номер свидетельства о регистрации

Какие из этих полей вам критично добавить? Могу сразу подготовить миграцию для них.
