<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Транспортная накладная {{ $note->document_number }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
            src: url({{ storage_path('fonts/dejavu-sans.ttf') }}) format('truetype');
        }
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: bold;
            src: url({{ storage_path('fonts/dejavu-sans-bold.ttf') }}) format('truetype');
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7pt;
            margin: 0;
            padding: 3mm;
            line-height: 1.2;
        }
        .container {
            width: 100%;
            max-width: 290mm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 2mm;
        }
        .header h1 {
            margin: 1mm 0;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .section {
            margin-bottom: 1mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 0.5mm;
            vertical-align: top;
            text-align: left;
            font-weight: normal;
            overflow: hidden;
        }
        .section-title {
            font-weight: bold;
            background-color: #f0f0f0;
            text-align: center;
            padding: 0.5mm;
        }
        .small-text {
            font-size: 6pt;
            color: #555;
        }
        .page-break {
            page-break-after: always;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .full-width {
            width: 100%;
        }
        .nowrap {
            white-space: nowrap;
        }
        /* Уточненные стили для соответствия ГОСТ */
        body { font-size: 6.5pt; }
        .section { margin-bottom: 0.5mm; }
        td { padding: 0.3mm; vertical-align: top; }
        .value-cell { font-weight: bold; white-space: nowrap; }
        .monospace { font-family: 'DejaVu Sans Mono', monospace; }

        /* Точные ширины колонок как в образце xls */
        .col-1 { width: 5mm; }
        .col-2 { width: 8mm; }
        .col-3 { width: 15mm; }
        .col-4 { width: 20mm; }
        .col-5 { width: 25mm; }
        .col-6 { width: 30mm; }
        .col-7 { width: 35mm; }
        .col-8 { width: 40mm; }
        .col-9 { width: 45mm; }
        .col-10 { width: 50mm; }
        .col-11 { width: 55mm; }
        .col-12 { width: 60mm; }
        .col-13 { width: 65mm; }
        .col-14 { width: 70mm; }
        .col-15 { width: 75mm; }
        .col-16 { width: 80mm; }
        .col-17 { width: 85mm; }
        .col-18 { width: 90mm; }
        .col-19 { width: 95mm; }
        .col-20 { width: 100mm; }
        .col-21 { width: 100mm; }
        .col-22 { width: 100mm; }
        .col-23 { width: 100mm; }
        .col-24 { width: 100mm; }
        .col-25 { width: 100mm; }
        .col-26 { width: 100mm; }
        .col-27 { width: 100mm; }
        .col-28 { width: 100mm; }
        .col-29 { width: 100mm; }
        .col-30 { width: 100mm; }
        .col-31 { width: 100mm; }
        .col-32 { width: 100mm; }
        .col-33 { width: 100mm; }
        .col-34 { width: 100mm; }
        .col-35 { width: 100mm; }
        .col-36 { width: 100mm; }
        .col-37 { width: 100mm; }
        .col-38 { width: 100mm; }
        .col-39 { width: 100mm; }
        .col-40 { width: 100mm; }
        .col-41 { width: 100mm; }
        .col-42 { width: 100mm; }
        .col-43 { width: 100mm; }
        .col-44 { width: 100mm; }
        .col-45 { width: 100mm; }
        .col-46 { width: 100mm; }
        .col-47 { width: 100mm; }
        .col-48 { width: 100mm; }
        .col-49 { width: 100mm; }
        .col-50 { width: 100mm; }
        .col-51 { width: 100mm; }
        .col-52 { width: 100mm; }
        .col-53 { width: 100mm; }
        .col-54 { width: 100mm; }
        .col-55 { width: 100mm; }
        .col-56 { width: 100mm; }
        .col-57 { width: 100mm; }
        .col-58 { width: 100mm; }
        .col-59 { width: 100mm; }

    </style>
</head>
<body>
    <div class="container">
        <!-- Страница 1 -->
        <div class="header">
            <div>Приложение N 4 к Правилам перевозок грузов автомобильным транспортом</div>
            <div>(в ред. Постановления Правительства РФ от 30.11.2021 N 2116)</div>
            <h1>ТРАНСПОРТНАЯ НАКЛАДНАЯ (ФОРМА)</h1>
        </div>

        <table>
            <tr>
                <td class="col-2">Транспортная накладная</td>
                <td class="col-18" colspan="36"></td>
                <td class="col-2 text-right">Заказ (заявка)</td>
            </tr>
            <tr>
                <td class="col-2">Дата</td>
                <td class="col-4">{{ $note->issue_date ? $note->issue_date->format('d.m.Y') : '' }}</td>
                <td class="col-2">Дата</td>
                <td class="col-4">{{ $note->order && $note->order->created_at ? $note->order->created_at->format('d.m.Y') : '' }}</td>
                <td class="col-1">N</td>
                <td class="col-4">{{ $note->document_number }}</td>
                <td class="col-1">N</td>
                <td class="col-4">{{ $note->order ? $note->order->id : '' }}</td>
            </tr>
            <tr>
                <td colspan="8">Экземпляр N 1</td>
            </tr>
        </table>

        <!-- 1. Грузоотправитель -->
        <div class="section">
            <div class="section-title">1. Грузоотправитель</div>
            <table>
                <tr>
                    <td colspan="59">
                        <div>{{ $platform->name }} ИНН {{ $platform->inn }}, {{ $platform->legal_address }}, тел. {{ $platform->phone }}</div>
                        <div class="small-text">(реквизиты, позволяющие идентифицировать Грузоотправителя)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59" class="small-text">
                        реквизиты документа, определяющего основания осуществления расчетов по договору перевозки иным лицом, отличным от грузоотправителя (при наличии)
                    </td>
                </tr>
            </table>
        </div>

        <!-- 2. Грузополучатель -->
        <div class="section">
            <div class="section-title">2. Грузополучатель</div>
            <table>
                <tr>
                    <td colspan="59">
                        <div>{{ $note->order->lesseeCompany->legal_name }}, ИНН {{ $note->order->lesseeCompany->inn }}, {{ $note->deliveryTo->address }}, тел. {{ $note->order->lesseeCompany->phone }}</div>
                        <div class="small-text">(реквизиты, позволяющие идентифицировать Грузополучателя)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>{{ $note->deliveryTo->address }}</div>
                        <div class="small-text">(адрес места доставки груза)</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 3. Груз -->
        <div class="section">
            <div class="section-title">3. Груз</div>
            <table>
                <tr>
                    <td colspan="27">
                        <div>{{ $note->cargo_description }}</div>
                        <div class="small-text">(отгрузочное наименование груза (для опасных грузов - в соответствии с ДОПОГ), его состояние и другая необходимая информация о грузе)</div>
                    </td>
                    <td colspan="32">
                        <div>{{ $note->cargo_places_count }} ({{ $note->cargo_places_count == 1 ? 'одно' : ($note->cargo_places_count == 2 ? 'два' : $note->cargo_places_count) }}) грузовых мест. Маркировка: отсутствует. Способ упаковки — без упаковки.</div>
                        <div class="small-text">(количество грузовых мест, маркировка, вид тары и способ упаковки)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="27">
                        <div>Места 1-{{ $note->cargo_places_count }}: {{ number_format($note->cargo_weight, 2, ',', ' ') }} кг брутто, {{ $note->cargo_dimensions }}</div>
                        <div>Всего {{ number_format($note->cargo_weight * $note->cargo_places_count, 2, ',', ' ') }} кг брутто</div>
                        <div class="small-text">(масса груза брутто в килограммах, масса груза нетто в килограммах (при возможности ее определения), размеры (высота, ширина, длина) в метрах (при перевозке крупногабаритного груза), объем груза в кубических метрах и плотность груза в соответствии с документацией на груз (при необходимости), дополнительные характеристики груза, учитывающие отраслевые особенности (при необходимости)</div>
                    </td>
                    <td colspan="32">
                        <div>—</div>
                        <div class="small-text">(в случае перевозки опасного груза - информация по каждому опасному веществу, материалу или изделию в соответствии с пунктом 5.4.1 ДОПОГ)</div>
                        <div>Объявленная стоимость груза — {{ number_format($note->cargo_value, 2, ',', ' ') }} руб.</div>
                        <div class="small-text">(объявленная стоимость (ценность) груза (при необходимости))</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 4. Сопроводительные документы -->
        <div class="section">
            <div class="section-title">4. Сопроводительные документы на груз (при наличии)</div>
            <table>
                <tr>
                    <td colspan="59">
                        <div>—</div>
                        <div class="small-text">(перечень прилагаемых к транспортной накладной документов, предусмотренных ДОПОГ, санитарными, таможенными (при наличии), карантинными, иными правилами в соответствии с законодательством Российской Федерации, либо регистрационные номера указанных документов, если такие документы (сведения о таких документах) содержатся в государственных информационных системах)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>Сертификат соответствия № 555, действителен с 01.06.2021 до 01.06.2023</div>
                        <div class="small-text">(перечень прилагаемых к грузу сертификатов, паспортов качества, удостоверений и других документов, наличие которых установлено законодательством Российской Федерации, либо регистрационные номера указанных документов, если такие документы (сведения о таких документах) содержатся в государственных информационных системах)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>Товарная накладная от {{ $note->issue_date ? $note->issue_date->format('d.m.Y') : '' }} № {{ $note->document_number }}</div>
                        <div class="small-text">(реквизиты, позволяющие идентифицировать документ(-ы), подтверждающий(-ие) отгрузку товаров) (при наличии), реквизиты сопроводительной ведомости (при перевозке груженых контейнеров или порожних контейнеров)</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 5. Указания грузоотправителя -->
        <div class="section">
            <div class="section-title">5. Указания грузоотправителя по особым условиям перевозки</div>
            <table>
                <tr>
                    <td colspan="27">
                        <div>дата и время доставки груза — {{ $note->delivery_date ? $note->delivery_date->format('d.m.Y, H:i') : 'не указано' }}</div>
                        <div class="small-text">(маршрут перевозки, дата и время/сроки доставки груза (при необходимости))</div>
                    </td>
                    <td colspan="32">
                        <div>менеджер {{ $platform->contact_person }} тел. {{ $platform->contact_phone }}</div>
                        <div class="small-text">(контактная информация о лицах, по указанию которых может осуществляться переадресовка)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="27">
                        <div>—</div>
                        <div class="small-text">(указания, необходимые для выполнения фитосанитарных, санитарных, карантинных, таможенных и прочих требований, установленных законодательством Российской Федерации)</div>
                    </td>
                    <td colspan="32">
                        <div>—</div>
                        <div class="small-text">(температурный режим перевозки груза (при необходимости), сведения о запорно-пломбировочных устройствах (в случае их предоставления грузоотправителем), запрещение перегрузки груза)</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 6. Перевозчик -->
        <div class="section">
            <div class="section-title">6. Перевозчик</div>
            <table>
                <tr>
                    <td colspan="27">
                        <div>{{ $platform->name }}, ИНН {{ $platform->inn }}, {{ $platform->legal_address }}, тел. {{ $platform->phone }}</div>
                        <div class="small-text">(реквизиты, позволяющие идентифицировать Перевозчика)</div>
                    </td>
                    <td colspan="32">
                        <div>{{ $note->transport_driver_name }}, ИНН {{ $note->driver_inn ?? 'не указано' }}, тел. {{ $note->driver_contact }}</div>
                        <div class="small-text">(реквизиты, позволяющие идентифицировать водителя(-ей))</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 7. Транспортное средство -->
        <div class="section">
            <div class="section-title">7. Транспортное средство</div>
            <table>
                <tr>
                    <td colspan="27">
                        <div>{{ $note->transport_vehicle_model }}, грузоподъемность – {{ $note->cargo_capacity ?? '20' }} т, вместимость – {{ $note->cargo_volume ?? '90' }} куб. м</div>
                        <div class="small-text">(тип, марка, грузоподъемность (в тоннах), вместимость (в кубических метрах))</div>
                    </td>
                    <td colspan="32">
                        <div>{{ $note->transport_vehicle_number }}</div>
                        <div class="small-text">(регистрационный номер транспортного средства)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="27">
                        <div>Тип владения: 1 - собственность</div>
                        <div class="small-text">(реквизиты документа(-ов), подтверждающего(- их) основание владения грузовым автомобилем (тягачом, а также прицепом (полуприцепом) (для типов владения 3, 4, 5))</div>
                    </td>
                    <td colspan="32">
                        <div>—</div>
                        <div class="small-text">(номер, дата и срок действия специального разрешения, установленный маршрут движения тяжеловесного и (или) крупногабаритного транспортного средства или транспортного средства, перевозящего опасный груз) (при наличии))</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 8. Прием груза -->
        <div class="section">
            <div class="section-title">8. Прием груза</div>
            <table>
                <tr>
                    <td colspan="27">
                        <div>{{ $platform->name }}, {{ $platform->legal_address }}</div>
                        <div class="small-text">(реквизиты лица, осуществляющего погрузку груза в транспортное средство)</div>
                        <div>{{ $platform->name }}</div>
                        <div class="small-text">(наименование (ИНН) владельца объекта инфраструктуры пункта погрузки)</div>
                        <div>{{ $note->deliveryFrom->address }}</div>
                        <div class="small-text">(адрес места погрузки)</div>
                    </td>
                    <td colspan="32">
                        <div>{{ $note->departure_time ? $note->departure_time->format('d.m.Y, H:i') : 'не указано' }}</div>
                        <div class="small-text">(заявленные дата и время подачи транспортного средства под погрузку)</div>
                        <div>{{ $note->departure_time ? $note->departure_time->format('d.m.Y, H:i') : 'не указано' }}</div>
                        <div class="small-text">(фактические дата и время прибытия под погрузку)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="27">
                        <div>{{ number_format($note->cargo_weight * $note->cargo_places_count, 2, ',', ' ') }} кг (брутто), расчетная масса груза</div>
                        <div class="small-text">(масса груза брутто в килограммах и метод ее определения (определение разницы между массой транспортного средства после погрузки и перед погрузкой по общей массе или взвешиванием поосно или расчетная масса груза))</div>
                        <div>{{ $note->cargo_places_count }} ({{ $note->cargo_places_count == 1 ? 'одно' : ($note->cargo_places_count == 2 ? 'два' : $note->cargo_places_count) }})</div>
                        <div class="small-text">(количество грузовых мест)</div>
                    </td>
                    <td colspan="32">
                        <div>—</div>
                        <div class="small-text">(оговорки и замечания перевозчика (при наличии) о дате и времени прибытия/убытия, о состоянии, креплении груза, тары, упаковки, маркировки, опломбирования, о массе груза и количестве грузовых мест, о проведении погрузочных работ))</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="27">
                        <div>{{ $platform->contact_person }} ({{ $platform->contact_position }}) (доверенность от {{ now()->subDays(10)->format('d.m.Y') }} № 345-А)</div>
                        <div class="small-text">(подпись, расшифровка подписи лица, осуществившего погрузку груза, с указанием реквизитов документа, подтверждающего полномочия лица на погрузку груза)</div>
                    </td>
                    <td colspan="32">
                        <div>{{ $note->transport_driver_name }}, водитель</div>
                        <div class="small-text">(подпись, расшифровка подписи водителя, принявшего груз для перевозки)</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 9. Переадресовка -->
        <div class="section">
            <div class="section-title">9. Переадресовка (при наличии)</div>
            <table>
                <tr>
                    <td colspan="59">
                        <div>—</div>
                        <div class="small-text">(дата, вид переадресовки на бумажном носителе или в электронном виде (с указанием вида доставки документа))</div>
                        <div>—</div>
                        <div class="small-text">(реквизиты лица, от которого получено указание на переадресовку)</div>
                        <div>—</div>
                        <div class="small-text">(адрес нового пункта выгрузки, новые дата и время подачи транспортного средства под выгрузку)</div>
                        <div>—</div>
                        <div class="small-text">(при изменении получателя груза - реквизиты нового получателя)</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Страница 2 -->
    <div class="page-break"></div>
    <div class="container">
        <div class="header">
            <h1>ТРАНСПОРТНАЯ НАКЛАДНАЯ (ФОРМА) - ПРОДОЛЖЕНИЕ</h1>
        </div>

        <!-- 10. Выдача груза -->
        <div class="section">
            <div class="section-title">10. Выдача груза</div>
            <table>
                <tr>
                    <td colspan="27">
                        <div>{{ $note->deliveryTo->address }}</div>
                        <div class="small-text">(адрес места выгрузки)</div>
                    </td>
                    <td colspan="32">
                        <div>{{ $note->delivery_date ? $note->delivery_date->format('d.m.Y, H:i') : 'не указано' }}</div>
                        <div class="small-text">(заявленные дата и время подачи транспортного средства под выгрузку)</div>
                        <div>{{ $note->delivery_date ? $note->delivery_date->format('d.m.Y, H:i') : 'не указано' }}</div>
                        <div class="small-text">(фактические дата и время прибытия)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="27">
                        <div>Груз упакован в картонные коробки, повреждений нет, маркировка отсутствовала</div>
                        <div class="small-text">(фактическое состояние груза, тары, упаковки, маркировки, опломбирования)</div>
                    </td>
                    <td colspan="32">
                        <div>{{ $note->cargo_places_count }} ({{ $note->cargo_places_count == 1 ? 'одно' : ($note->cargo_places_count == 2 ? 'два' : $note->cargo_places_count) }})</div>
                        <div class="small-text">(количество грузовых мест)</div>
                        <div>—</div>
                        <div class="small-text">(оговорки и замечания перевозчика (при наличии) о дате и времени прибытия/убытия, о состоянии груза, тары, упаковки, маркировки, опломбирования, о массе груза и количестве грузовых мест))</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="27">
                        <div>{{ $note->cargo_weight ? number_format($note->cargo_weight * $note->cargo_places_count, 2, ',', ' ') : '0,00' }} кг (брутто)</div>
                        <div class="small-text">(масса груза брутто в килограммах, масса груза нетто в килограммах (при возможности ее определения), плотность груза в соответствии с документацией на груз (при необходимости))</div>
                    </td>
                    <td colspan="32">
                        <div>{{ $note->order->lesseeCompany->contact_person }}</div>
                        <div class="small-text">(должность, подпись, расшифровка подписи грузополучателя или уполномоченного грузоотправителем лица)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="27"></td>
                    <td colspan="32">
                        <div>{{ $note->transport_driver_name }}, водитель</div>
                        <div class="small-text">(подпись, расшифровка подписи водителя, сдавшего груз грузополучателю или уполномоченному грузополучателем лицу)</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 11. Отметки -->
        <div class="section">
            <div class="section-title">11. Отметки грузоотправителей, грузополучателей, перевозчиков (при необходимости)</div>
            <table>
                <tr>
                    <td colspan="59">
                        <div>—</div>
                        <div class="small-text">(краткое описание обстоятельств, послуживших основанием для отметки, сведения о коммерческих и иных актах, в том числе о погрузке/выгрузке груза)</div>
                        <div>—</div>
                        <div class="small-text">(расчет и размер штрафа)</div>
                        <div>—</div>
                        <div class="small-text">(подпись, дата)</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 12. Стоимость перевозки -->
        <div class="section">
            <div class="section-title">12. Стоимость перевозки груза (установленная плата) в рублях (при необходимости)</div>
            <table>
                <tr>
                    <td class="col-10">{{ number_format($note->calculated_cost / 1.2, 2, ',', ' ') }}</td>
                    <td class="col-2">0.2</td>
                    <td class="col-6">{{ number_format($note->calculated_cost * 0.2, 2, ',', ' ') }}</td>
                    <td class="col-10">{{ number_format($note->calculated_cost, 2, ',', ' ') }}</td>
                    <td colspan="31"></td>
                </tr>
                <tr>
                    <td class="small-text">(стоимость перевозки без налога - всего)</td>
                    <td class="small-text">(налоговая ставка)</td>
                    <td class="small-text">(сумма налога, предъявляемая покупателю)</td>
                    <td class="small-text">(стоимость перевозки с налогом - всего)</td>
                    <td colspan="31"></td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>—</div>
                        <div class="small-text">(порядок (механизм) расчета (исчислений) платы) (при наличии порядка (механизма))</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>{{ $platform->name }}, ИНН {{ $platform->inn }}, {{ $platform->legal_address }}, тел. {{ $platform->phone }}</div>
                        <div class="small-text">(реквизиты, позволяющие идентифицировать Экономического субъекта, составляющего первичный учетный документ о факте хозяйственной жизни со стороны Перевозчика)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>{{ $platform->name }}, ИНН {{ $platform->inn }}, {{ $platform->legal_address }}, тел. {{ $platform->phone }}</div>
                        <div class="small-text">(реквизиты, позволяющие идентифицировать Экономического субъекта, составляющего первичный учетный документ о факте хозяйственной жизни со стороны Грузоотправителя)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>договор перевозки от {{ $note->order && $note->order->created_at ? $note->order->created_at->format('d.m.Y') : '' }} № {{ $note->order ? $note->order->id : '' }}</div>
                        <div class="small-text">(основание, по которому Экономический субъект является составителем документа о факте хозяйственной жизни)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>—</div>
                        <div class="small-text">(реквизиты, позволяющие идентифицировать лицо, от которого будут поступать денежные средства)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>{{ $platform->contact_person }} ({{ $platform->contact_position }})</div>
                        <div class="small-text">(подпись, расшифровка подписи лица, ответственного за оформление факта хозяйственной жизни со стороны Перевозчика (уполномоченного лица))</div>
                        <div>{{ $platform->contact_person }} ({{ $platform->contact_position }})</div>
                        <div class="small-text">(подпись, расшифровка подписи лица, ответственного за оформление факта хозяйственной жизни со стороны Грузоотправителя (уполномоченного лица))</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="59">
                        <div>менеджер (доверенность от {{ now()->subDays(10)->format('d.m.Y') }} № 12), {{ now()->format('d.m.Y') }}</div>
                        <div class="small-text">(должность, основание полномочий физического лица, уполномоченного Перевозчиком (уполномоченным лицом), дата подписания)</div>
                        <div>менеджер (доверенность от {{ now()->subDays(5)->format('d.m.Y') }} № 345-А), {{ now()->format('d.m.Y') }}</div>
                        <div class="small-text">(должность, основание полномочий физического лица, уполномоченного Грузоотправителем (уполномоченным лицом), дата подписания)</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Подписи и печати -->
        <table style="margin-top: 5mm; border: none;">
            <tr>
                <td style="width: 33%; vertical-align: bottom; border: none;">
                    <strong>Грузоотправитель:</strong><br>
                    {{ $platform->name }}<br><br>
                    _________________________<br>
                    <div class="small-text">(подпись, ФИО)</div>
                </td>
                <td style="width: 33%; vertical-align: bottom; border: none;">
                    <strong>Перевозчик:</strong><br>
                    {{ $platform->name }}<br><br>
                    _________________________<br>
                    <div class="small-text">(подпись, ФИО)</div>
                </td>
                <td style="width: 33%; vertical-align: bottom; border: none;">
                    <strong>Грузополучатель:</strong><br>
                    {{ $note->order->lesseeCompany->legal_name }}<br><br>
                    _________________________<br>
                    <div class="small-text">(подпись, ФИО)</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
