<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Заявка на аренду #{{ $rentalRequest->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .section {
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        h1 { color: #2c3e50; margin-bottom: 5px; }
        h2 { color: #34495e; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #e74c3c;
            color: white;
            border-radius: 4px;
            font-size: 10px;
        }
        .spec-item {
            margin-bottom: 3px;
            padding-left: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Заявка на аренду строительной техники</h1>
        <p>№ {{ $rentalRequest->id }} от {{ $rentalRequest->created_at->format('d.m.Y') }}</p>
    </div>

    <div class="section">
        <h2>Основная информация</h2>
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Название:</span> {{ $rentalRequest->title }}
                </div>
                <div class="info-item">
                    <span class="info-label">Статус:</span>
                    <span class="badge">{{ $rentalRequest->status_text }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Локация:</span>
                    {{ $rentalRequest->location->name ?? 'Не указана' }}
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Период аренды:</span><br>
                    {{ $rentalRequest->rental_period_start->format('d.m.Y') }} -
                    {{ $rentalRequest->rental_period_end->format('d.m.Y') }}
                </div>
                <div class="info-item">
                    <span class="info-label">Бюджет:</span>
                    {{ number_format($rentalRequest->total_budget ?? $rentalRequest->calculated_budget_from, 0, ',', ' ') }} ₽
                </div>
            </div>
        </div>

        @if($rentalRequest->description)
        <div class="info-item">
            <span class="info-label">Описание проекта:</span><br>
            {{ $rentalRequest->description }}
        </div>
        @endif
    </div>

    <div class="section">
        <h2>Позиции заявки</h2>
        @if($items && count($items) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th width="25%">Категория</th>
                    <th width="15%">Количество</th>
                    <th width="45%">Спецификации</th>
                    <th width="15%">Условия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->category->name ?? 'Не указана' }}</td>
                    <td>{{ $item->quantity }} шт.</td>
                    <td>
                        {{-- ✅ РАБОТАЕМ С JSON-ПОЛЕМ specifications --}}
                        @if(!empty($item->specifications) && is_array($item->specifications))
                            @foreach($item->specifications as $key => $value)
                                @if(!empty($value) && !is_array($value))
                                    <div class="spec-item">
                                        <strong>{{ $key }}:</strong> {{ $value }}
                                    </div>
                                @endif
                            @endforeach

                            {{-- ✅ АЛЬТЕРНАТИВНО: используем formatted_specifications --}}
                            @php
                                $formattedSpecs = $item->formatted_specifications ?? [];
                            @endphp
                            @if(empty($item->specifications) && !empty($formattedSpecs))
                                @foreach($formattedSpecs as $spec)
                                    @if(!empty($spec['formatted']))
                                        <div class="spec-item">{{ $spec['formatted'] }}</div>
                                    @endif
                                @endforeach
                            @endif
                        @else
                            <em>Стандартные характеристики</em>
                        @endif
                    </td>
                    <td>
                        @if($item->use_individual_conditions && $item->individual_conditions)
                            Индивидуальные
                        @else
                            Стандартные
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>Нет позиций в заявке</p>
        @endif
    </div>

    <div class="footer">
        <p>Сгенерировано: {{ $exportDate ?? now()->format('d.m.Y H:i') }}</p>
        <p>Пользователь: {{ $user->name ?? 'Система' }} • Компания: {{ $user->company->name ?? 'Не указана' }}</p>
        <p>Всего позиций: {{ $items->count() ?? 0 }}</p>
    </div>
</body>
</html>
