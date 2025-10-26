<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Транспортная накладная № {{ $note->document_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; }
        .signature-block { margin-top: 50px; }
        .signature-line { display: inline-block; width: 300px; border-bottom: 1px solid #000; margin: 0 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>ТРАНСПОРТНАЯ НАКЛАДНАЯ № {{ $note->document_number }}</h2>
        <p>от {{ $currentDate }}</p>
    </div>

    <table>
        <tr>
            <th colspan="4">1. Грузоотправитель</th>
        </tr>
        <tr>
            <td colspan="4">
                <strong>{{ $note->senderCompany->legal_name }}</strong><br>
                ИНН {{ $note->senderCompany->inn }}, {{ $note->senderCompany->legal_address }}
            </td>
        </tr>

        <tr>
            <th colspan="4">2. Грузополучатель</th>
        </tr>
        <tr>
            <td colspan="4">
                <strong>{{ $note->receiverCompany->legal_name }}</strong><br>
                ИНН {{ $note->receiverCompany->inn }}, {{ $note->receiverCompany->legal_address }}
            </td>
        </tr>

        <tr>
            <th colspan="4">3. Перевозчик</th>
        </tr>
        <tr>
            <td colspan="4">
                <strong>{{ $platform->legal_name }}</strong><br>
                ИНН {{ $platform->inn }}, {{ $platform->legal_address }}
            </td>
        </tr>

        <tr>
            <th colspan="4">4. Груз</th>
        </tr>
        <tr>
            <td colspan="4">
                {{ $note->cargo_description }}<br>
                Вес: {{ $note->cargo_weight }} т,
                Стоимость: {{ number_format($note->cargo_value, 2) }} руб
                Состояние: {{ $note->equipment_condition }} <!-- Добавляем состояние груза -->
            </td>
        </tr>

        <tr>
            <th colspan="4">5. Транспортное средство</th>
        </tr>
        <tr>
            <td colspan="4">
                {{ $note->vehicle_model }}, гос. номер {{ $note->vehicle_number }}<br>
                Водитель: {{ $note->driver_name }}, тел. {{ $note->driver_contact }}
            </td>
        </tr>

       <tr>
            <th>Пункт погрузки</th>
            <th>Пункт разгрузки</th>
            <th>Расстояние (км)</th>
            <th>Стоимость перевозки</th>
        </tr>
        <tr>
            <td>{{ $note->deliveryFrom->name }}</td>
            <td>{{ $note->deliveryTo->name }}</td>
            <td>{{ number_format($note->distance_km, 2) }}</td>
            <td>{{ number_format($note->calculated_cost, 2) }} руб</td>
        </tr>
    </table>

    <div class="signature-block">
        <p>Груз сдал: <span class="signature-line"></span> / {{ $note->senderCompany->ceo_name }} /</p>
        <p>Груз принял: <span class="signature-line"></span> / {{ $note->receiverCompany->ceo_name }} /</p>
        <p>Перевозчик: <span class="signature-line"></span> / {{ $platform->ceo_name }} /</p>
    </div>
</body>
</html>
