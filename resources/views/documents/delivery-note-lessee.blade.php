<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Транспортная накладная {{ $note->document_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin-bottom: 5px; font-size: 14pt; }
        .section { margin-bottom: 10px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #000; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 3px; text-align: left; vertical-align: top; }
        .signature-table { margin-top: 20px; width: 100%; }
        .signature-cell { width: 33%; vertical-align: top; position: relative; }
        .stamp { position: absolute; bottom: 0; opacity: 0.3; }
        .page-break { page-break-after: always; }
        .text-center { text-align: center; }
        .small { font-size: 9pt; }
        .warning { color: #856404; background-color: #fff3cd; padding: 3px; }
    </style>
</head>
<body>
    <!-- Страница 1 -->
    <div class="header">
        <h1>ТРАНСПОРТНАЯ НАКЛАДНАЯ № {{ $note->document_number }}</h1>
        <div>Дата составления: {{ $note->issue_date->format('d.m.Y') }}</div>
    </div>

    <table>
        <tr>
            <th colspan="2">Транспортная накладная</th>
            <th colspan="3">Заказ (заявка)</th>
        </tr>
        <tr>
            <td>Дата</td>
            <td>{{ $note->issue_date->format('d.m.Y') }}</td>
            <td>Дата</td>
            <td colspan="2">{{ $note->order->created_at->format('d.m.Y') ?? 'Н/Д' }}</td>
        </tr>
        <tr>
            <td>№</td>
            <td>{{ $note->document_number }}</td>
            <td>№</td>
            <td colspan="2">{{ $note->order->id ?? 'Н/Д' }}</td>
        </tr>
        <tr>
            <td colspan="2">Экземпляр №: 3 (грузополучатель)</td>
            <td colspan="3"></td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">1. Грузоотправитель</div>
        <table>
            <tr>
                <th>Наименование</th>
                <th>ИНН/КПП</th>
                <th>Юридический адрес</th>
                <th>Банковские реквизиты</th>
            </tr>
            <tr>
                <td>{{ $platform->name }}</td>
                <td>{{ $platform->inn }}/{{ $platform->kpp }}</td>
                <td>{{ $platform->legal_address }}</td>
                <td>
                    {{ $platform->bank_name }}, БИК: {{ $platform->bic }}<br>
                    Р/с: {{ $platform->settlement_account }}<br>
                    К/с: {{ $platform->correspondent_account }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Грузополучатель</div>
        <table>
            <tr>
                <th>Наименование</th>
                <th>ИНН/КПП</th>
                <th>Адрес доставки</th>
                <th>Контактное лицо</th>
            </tr>
            <tr>
                <td>{{ $note->order->lesseeCompany->legal_name ?? 'Компания не указана' }}</td>
                <td>
                    {{ $note->order->lesseeCompany->inn ?? 'Н/Д' }}/{{ $note->order->lesseeCompany->kpp ?? 'Н/Д' }}
                </td>
                <td>{{ $note->deliveryTo->address }}</td>
                <td>
                    {{ $note->order->lesseeCompany->director_name ?? 'Н/Д' }}<br>
                    Тел: {{ $note->order->lesseeCompany->phone ?? 'Н/Д' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. Груз</div>
        <table>
            <tr>
                <th>Наименование</th>
                <th>Вес брутто, кг</th>
                <th>Кол-во мест</th>
                <th>Упаковка</th>
                <th>Стоимость, руб.</th>
            </tr>
            <tr>
                <td>{{ $note->cargo_description }}</td>
                <td>{{ $note->cargo_weight }}</td>
                <td>1</td>
                <td>Без упаковки</td>
                <td>{{ number_format($note->cargo_value, 2) }}</td>
            </tr>
        </table>
        <table>
            <tr>
                <th>Особые отметки о состоянии груза</th>
            </tr>
            <tr>
                <td>{{ $note->equipment_condition }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. Сопроводительные документы</div>
        <table>
            <tr>
                <td>Паспорт оборудования, сертификаты соответствия</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">5. Особые условия перевозки</div>
        <table>
            <tr>
                <td>Хрупкий груз. Запрещена перегрузка. Требуется бережное обращение.</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">6. Перевозчик</div>
        <table>
            <tr>
                <th>Наименование</th>
                <th>ИНН/КПП</th>
                <th>Адрес</th>
                <th>Лицензия</th>
            </tr>
            <tr>
                <td>{{ $platform->name }}</td>
                <td>{{ $platform->inn }}/{{ $platform->kpp }}</td>
                <td>{{ $platform->legal_address }}</td>
                <td>№ {{ $platform->certificate_number }} от {{ $platform->created_at->format('d.m.Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">7. Транспортное средство</div>
        <table>
            <tr>
                <th>Тип ТС</th>
                <th>Марка, модель</th>
                <th>Гос. номер</th>
                <th>Водитель</th>
                <th>Контакты водителя</th>
            </tr>
            <tr>
                <td>{{ \App\Models\DeliveryNote::vehicleTypes()[$note->transport_type] ?? '' }}</td>
                <td>{{ $note->transport_vehicle_model }}</td>
                <td>{{ $note->transport_vehicle_number }}</td>
                <td>{{ $note->transport_driver_name }}</td>
                <td>{{ $note->driver_contact }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">8. Прием груза</div>
        <table>
            <tr>
                <th>Пункт погрузки</th>
                <th>Дата/время подачи ТС</th>
                <th>Дата/время прибытия</th>
                <th>Дата/время убытия</th>
            </tr>
            <tr>
                <td>{{ $note->deliveryFrom->address }}</td>
                <td>{{ $note->departure_time->format('d.m.Y H:i') }}</td>
                <td>{{ $note->departure_time->format('d.m.Y H:i') }}</td>
                <td>{{ $note->departure_time->format('d.m.Y H:i') }}</td>
            </tr>
            <tr>
                <th colspan="4">Сведения о грузе при погрузке</th>
            </tr>
            <tr>
                <td colspan="2">Масса груза (брутто): {{ $note->cargo_weight }} кг</td>
                <td colspan="2">Количество мест: 1</td>
            </tr>
            <tr>
                <td colspan="4">Состояние груза, упаковки, маркировки: {{ $note->equipment_condition }}</td>
            </tr>
            <tr>
                <td colspan="2">
                    Подпись грузоотправителя:
                    <div class="stamp">
                        @if($platform->stamp_image_path && Storage::exists($platform->stamp_image_path))
                            <img src="{{ storage_path($platform->stamp_image_path) }}" height="80">
                        @endif
                    </div>
                </td>
                <td colspan="2">
                    Подпись водителя: _________________________
                    <div class="small">({{ $note->transport_driver_name }})</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>
    <!-- Страница 2 -->

    <div class="header">
        <h1>ТРАНСПОРТНАЯ НАКЛАДНАЯ № {{ $note->document_number }} (лист 2)</h1>
    </div>

    <div class="section">
        <div class="section-title">10. Выдача груза</div>
        <table>
            <tr>
                <th>Пункт разгрузки</th>
                <th>Дата/время подачи ТС</th>
                <th>Дата/время прибытия</th>
                <th>Дата/время убытия</th>
            </tr>
            <tr>
                <td>{{ $note->deliveryTo->address }}</td>
                <td>{{ $note->delivery_date ? $note->delivery_date->format('d.m.Y H:i') : 'В пути' }}</td>
                <td>{{ $note->delivery_date ? $note->delivery_date->format('d.m.Y H:i') : 'В пути' }}</td>
                <td>{{ $note->delivery_date ? $note->delivery_date->format('d.m.Y H:i') : 'В пути' }}</td>
            </tr>
            <tr>
                <th colspan="4">Сведения о грузе при выгрузке</th>
            </tr>
            <tr>
                <td colspan="2">Масса груза (брутто): {{ $note->cargo_weight }} кг</td>
                <td colspan="2">Количество мест: 1</td>
            </tr>
            <tr>
                <td colspan="4">Состояние груза, упаковки, маркировки: {{ $note->equipment_condition ?? 'Без замечаний' }}</td>
            </tr>
            <tr>
                <td colspan="2">
                    Подпись грузополучателя:
                    <div class="stamp">
                        @if($note->order->lesseeCompany->stamp_image_path && Storage::exists($note->order->lesseeCompany->stamp_image_path))
                            <img src="{{ storage_path($note->order->lesseeCompany->stamp_image_path) }}" height="80">
                        @endif
                    </div>
                </td>
                <td colspan="2">
                    Подпись водителя: _________________________
                    <div class="small">({{ $note->transport_driver_name }})</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">12. Стоимость перевозки груза</div>
        <table>
            <tr>
                <th>Стоимость без НДС</th>
                <th>Ставка НДС</th>
                <th>Сумма НДС</th>
                <th>Итого с НДС</th>
            </tr>
            <tr>
                <td>{{ number_format($note->calculated_cost / 1.2, 2) }} ₽</td>
                <td>20%</td>
                <td>{{ number_format($note->calculated_cost * 0.2, 2) }} ₽</td>
                <td>{{ number_format($note->calculated_cost, 2) }} ₽</td>
            </tr>
        </table>
        <table>
            <tr>
                <td colspan="2">Способ оплаты: Безналичный расчет</td>
                <td colspan="2">Срок оплаты: 10 банковских дней</td>
            </tr>
        </table>
    </div>

    <table class="signature-table">
        <tr>
            <td class="signature-cell">
                <strong>Грузоотправитель:</strong><br>
                {{ $platform->name }}<br>
                _________________________<br>
                <div class="small">(должность, подпись, ФИО)</div>
                <div class="stamp">
                    @if($platform->stamp_image_path && Storage::exists($platform->stamp_image_path))
                        <img src="{{ storage_path($platform->stamp_image_path) }}" height="80">
                    @endif
                </div>
            </td>
            <td class="signature-cell">
                <strong>Перевозчик:</strong><br>
                {{ $platform->name }}<br>
                _________________________<br>
                <div class="small">(должность, подпись, ФИО)</div>
                <div class="stamp">
                    @if($platform->stamp_image_path && Storage::exists($platform->stamp_image_path))
                        <img src="{{ storage_path($platform->stamp_image_path) }}" height="80">
                    @endif
                </div>
            </td>
            <td class="signature-cell">
                <strong>Грузополучатель:</strong><br>
                {{ $note->order->lesseeCompany->legal_name ?? 'Компания не указана' }}<br>
                _________________________<br>
                <div class="small">(должность, подпись, ФИО)</div>
                <div class="stamp">
                    @if($note->order->lesseeCompany->stamp_image_path && Storage::exists($note->order->lesseeCompany->stamp_image_path))
                        <img src="{{ storage_path($note->order->lesseeCompany->stamp_image_path) }}" height="80">
                    @endif
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
