<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>УПД №{{ $order->id }} от {{ $order->created_at->format('d.m.Y') : '--' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
        }
        .document {
            width: 100%;
            border-collapse: collapse;
        }
        .document td, .document th {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            padding: 10px 0;
        }
        .section-title {
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 3px;
        }
        .bordered {
            border: 2px solid #000 !important;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .nowrap { white-space: nowrap; }
        .signature-area {
            margin-top: 40px;
        }
        .footer-note {
            font-size: 8pt;
            font-style: italic;
            margin-top: 5px;
        }
        .no-border { border: none !important; }
        .border-top { border-top: 1px solid #000 !important; }
        .border-bottom { border-bottom: 1px solid #000 !important; }
    </style>
</head>
<body>
    <table class="document">
        <!-- Шапка документа -->
        <tr>
            <td colspan="8" class="header-title bordered">
                Универсальный передаточный документ
            </td>
            <td colspan="20" class="bordered">
                <table width="100%">
                    <tr>
                        <td width="40%">Счет-фактура №</td>
                        <td class="border-bottom">{{ $order->id }}</td>
                    </tr>
                    <tr>
                        <td>Исправление №</td>
                        <td class="border-bottom">--</td>
                    </tr>
                </table>
            </td>
            <td colspan="5" class="bordered">
                <table width="100%">
                    <tr>
                        <td class="border-bottom">от</td>
                    </tr>
                    <tr>
                        <td class="border-bottom">{{ $order->created_at->format('d.m.Y') }}</td>
                    </tr>
                </table>
            </td>
            <td colspan="20" class="bordered text-center footer-note">
                Приложение №1 к постановлению Правительства РФ<br>
                от 26.12.2011 №1137 (в ред. от 16.08.2024 №1096)
            </td>
        </tr>

        <!-- Продавец (Платформа) -->
        <tr>
            <td colspan="5" rowspan="3" class="text-center">
                <div class="section-title">Статус:</div>
                1
            </td>
            <td colspan="12" class="section-title">Продавец:</td>
            <td colspan="35">{{ $platform->name }}</td>
            <td colspan="2" class="text-center">(2)</td>
            <td colspan="5" class="section-title">Покупатель:</td>
            <td colspan="15">{{ $counterparty->name }}</td>
            <td colspan="4" class="text-center">(6)</td>
        </tr>
        <tr>
            <td colspan="12" class="section-title">Адрес:</td>
            <td colspan="35">{{ $platform->legal_address }}</td>
            <td colspan="2" class="text-center">(2а)</td>
            <td colspan="5" class="section-title">Адрес:</td>
            <td colspan="15">{{ $counterparty->legal_address }}</td>
            <td colspan="4" class="text-center">(6а)</td>
        </tr>
        <tr>
            <td colspan="12" class="section-title">ИНН/КПП продавца:</td>
            <td colspan="35">{{ $platform->inn }}/{{ $platform->kpp }}</td>
            <td colspan="2" class="text-center">(2б)</td>
            <td colspan="5" class="section-title">ИНН/КПП покупателя:</td>
            <td colspan="15">{{ $counterparty->inn }}/{{ $counterparty->kpp }}</td>
            <td colspan="4" class="text-center">(6б)</td>
        </tr>

        <!-- Грузоотправитель и валюта -->
        <tr>
            <td colspan="4" rowspan="2" class="text-center footer-note">
                1 - счет-фактура и передаточный документ<br>
                2 - передаточный документ
            </td>
            <td colspan="12" class="section-title">Грузоотправитель и его адрес:</td>
            <td colspan="35">он же</td>
            <td colspan="2" class="text-center">(3)</td>
            <td colspan="5" class="section-title">Валюта:</td>
            <td colspan="15">Российский рубль, 643</td>
            <td colspan="4" class="text-center">(7)</td>
        </tr>
        <tr>
            <td colspan="12" class="section-title">Грузополучатель и его адрес:</td>
            <td colspan="35">{{ $counterparty->name }}, {{ $counterparty->legal_address }}</td>
            <td colspan="2" class="text-center">(4)</td>
            <td colspan="12" rowspan="2" class="section-title">Идентификатор контракта:</td>
            <td colspan="8" rowspan="2" class="border-bottom">Договор №{{ $order->contract_number }}</td>
            <td colspan="4" rowspan="2" class="text-center">(8)</td>
        </tr>
        <tr>
            <td colspan="4"></td>
            <td colspan="12" class="section-title">К платежному документу №</td>
            <td colspan="35" class="border-bottom">{{ $order->payment_number }}</td>
            <td colspan="2" class="text-center">(5)</td>
        </tr>

        <!-- Таблица товаров/услуг -->
        <tr>
            <td colspan="4" class="text-center section-title bordered">Код</td>
            <td colspan="3" class="text-center section-title bordered">№ п/п</td>
            <td colspan="9" class="text-center section-title bordered">Наименование товара</td>
            <td colspan="2" class="text-center section-title bordered">Код вида товара</td>
            <td colspan="3" class="text-center section-title bordered">Код</td>
            <td colspan="5" class="text-center section-title bordered">Единица измерения</td>
            <td colspan="2" class="text-center section-title bordered">Количество</td>
            <td colspan="8" class="text-center section-title bordered">Цена за единицу</td>
            <td colspan="7" class="text-center section-title bordered">Стоимость без налога</td>
            <td colspan="4" class="text-center section-title bordered">Акциз</td>
            <td colspan="3" class="text-center section-title bordered">Ставка НДС</td>
            <td colspan="3" class="text-center section-title bordered">Сумма НДС</td>
            <td colspan="3" class="text-center section-title bordered">Стоимость с налогом</td>
            <td colspan="1" class="text-center section-title bordered">Код</td>
            <td colspan="3" class="text-center section-title bordered">Страна</td>
            <td colspan="10" class="text-center section-title bordered">Номер декларации</td>
            <td colspan="2" class="text-center section-title bordered">Ед. изм.</td>
            <td colspan="5" class="text-center section-title bordered">Количество</td>
        </tr>

        @foreach($order->items as $item)
        <tr>
            <td colspan="4" class="text-left">{{ $item->code ?? '--' }}</td>
            <td colspan="3" class="text-center">{{ $loop->iteration }}</td>
            <td colspan="9" class="text-left">{{ $item->equipment->name }}</td>
            <td colspan="2" class="text-center">--</td>
            <td colspan="3" class="text-center">796</td>
            <td colspan="5" class="text-center">шт</td>
            <td colspan="2" class="text-right">{{ number_format($item->period_count, 2) }}</td>
            <td colspan="8" class="text-right">{{ number_format($item->base_price, 2) }}</td>
            <td colspan="7" class="text-right">{{ number_format($item->base_price * $item->period_count, 2) }}</td>
            <td colspan="4" class="text-center">без акциза</td>
            <td colspan="3" class="text-center">20%</td>
            <td colspan="3" class="text-right">{{ number_format($item->base_price * $item->period_count * 0.2, 2) }}</td>
            <td colspan="3" class="text-right">{{ number_format($item->base_price * $item->period_count * 1.2, 2) }}</td>
            <td colspan="1" class="text-center">--</td>
            <td colspan="3" class="text-center">--</td>
            <td colspan="10" class="text-center">--</td>
            <td colspan="2" class="text-center">--</td>
            <td colspan="5" class="text-center">--</td>
        </tr>
        @endforeach

        <!-- Итоговая строка -->
        <tr>
            <td colspan="16" class="section-title text-right">Всего к оплате:</td>
            <td colspan="8"></td>
            <td colspan="7" class="text-right">{{ number_format($order->base_amount, 2) }}</td>
            <td colspan="7" class="text-center">X</td>
            <td colspan="3" class="text-right">{{ number_format($order->base_amount * 0.2, 2) }}</td>
            <td colspan="3" class="text-right">{{ number_format($order->base_amount * 1.2, 2) }}</td>
            <td colspan="19"></td>
        </tr>

        <!-- Подписи -->
        <tr>
            <td colspan="20" rowspan="2" class="footer-note">
                Документ составлен на 1 листах
            </td>
            <td colspan="20" class="text-center">
                Руководитель организации
            </td>
            <td colspan="15" class="border-bottom text-center">{{ $platform->ceo_name }}</td>
            <td colspan="15" class="text-center">
                Главный бухгалтер
            </td>
            <td colspan="10" class="border-bottom text-center">{{ $platform->accountant_name }}</td>
        </tr>
        <tr>
            <td colspan="20" class="text-center footer-note">
                (должность)
            </td>
            <td colspan="15" class="text-center footer-note">
                (подпись) (ФИО)
            </td>
            <td colspan="15" class="text-center footer-note">
                (должность)
            </td>
            <td colspan="10" class="text-center footer-note">
                (подпись) (ФИО)
            </td>
        </tr>

        <!-- Основание -->
        <tr>
            <td colspan="20" class="section-title">
                Основание передачи (сдачи) / получения (приемки)
            </td>
            <td colspan="60" class="border-bottom">
                Договор №{{ $order->contract_number }} от {{ $order->contract_date->format('d.m.Y') : '--' }}
            </td>
        </tr>

        <!-- Данные о транспортировке -->
        <tr>
            <td colspan="20" class="section-title">
                Данные о транспортировке и грузе
            </td>
            <td colspan="60" class="border-bottom">
                Транспортная накладная №{{ $order->shipping_number }}
            </td>
        </tr>

        <!-- Блоки передачи/приемки -->
        <tr>
            <td colspan="40" class="section-title">
                Товар (груз) передал / услуги сдал
            </td>
            <td colspan="40" class="section-title">
                Товар (груз) получил / услуги принял
            </td>
        </tr>
        <tr>
            <td colspan="20" class="text-center">
                Генеральный директор
            </td>
            <td colspan="20" class="border-bottom text-center">
                {{ $platform->ceo_name }}
            </td>
            <td colspan="20" class="text-center">
                Представитель
            </td>
            <td colspan="20" class="border-bottom text-center">
                {{ $counterparty->contact_person }}
            </td>
        </tr>
        <tr>
            <td colspan="20" class="text-center footer-note">
                (должность)
            </td>
            <td colspan="20" class="text-center footer-note">
                (подпись) (ФИО)
            </td>
            <td colspan="20" class="text-center footer-note">
                (должность)
            </td>
            <td colspan="20" class="text-center footer-note">
                (подпись) (ФИО)
            </td>
        </tr>
        <tr>
            <td colspan="20" class="section-title">
                Дата отгрузки, передачи (сдачи)
            </td>
            <td colspan="20" class="border-bottom text-center">
                {{ $order->created_at->format('d.m.Y') : '--' }}
            </td>
            <td colspan="20" class="section-title">
                Дата получения (приемки)
            </td>
            <td colspan="20" class="border-bottom text-center">
                {{ $order->delivery_date->format('d.m.Y') : '--' }}
            </td>
        </tr>

        <!-- Места для печатей -->
        <tr>
            <td colspan="40" class="text-center signature-area">
                М.П.<br>
                <span class="footer-note">ООО "{{ $platform->name }}"<br>
                {{ $platform->inn }}/{{ $platform->kpp }}</span>
            </td>
            <td colspan="40" class="text-center signature-area">
                М.П.<br>
                <span class="footer-note">{{ $counterparty->name }}<br>
                {{ $counterparty->inn }}/{{ $counterparty->kpp }}</span>
            </td>
        </tr>
    </table>
</body>
</html>
