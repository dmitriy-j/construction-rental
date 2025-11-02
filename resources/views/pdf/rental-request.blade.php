<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Заявка на аренду #{{ $request->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f5f5f5; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Заявка на аренду техники</h1>
        <p>№ {{ $request->id }} от {{ $request->created_at->format('d.m.Y') }}</p>
    </div>

    <div class="section">
        <h3>Основная информация</h3>
        <p><strong>Название:</strong> {{ $request->title }}</p>
        <p><strong>Описание:</strong> {{ $request->description }}</p>
        <p><strong>Период аренды:</strong>
            {{ $request->rental_period_start->format('d.m.Y') }} -
            {{ $request->rental_period_end->format('d.m.Y') }}
        </p>
        <p><strong>Бюджет:</strong> {{ number_format($request->total_budget, 0, ',', ' ') }} ₽</p>
    </div>

    @if($request->items && count($request->items) > 0)
    <div class="section">
        <h3>Позиции заявки</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Категория</th>
                    <th>Количество</th>
                    <th>Условия аренды</th>
                </tr>
            </thead>
            <tbody>
                @foreach($request->items as $item)
                <tr>
                    <td>{{ $item->category->name ?? 'Не указана' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>
                        @if($item->use_individual_conditions && $item->individual_conditions)
                            Индивидуальные условия
                        @else
                            Стандартные условия
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Сгенерировано: {{ $exportDate }}</p>
        <p>Пользователь: {{ $user->name }} ({{ $user->company->name ?? 'Компания не указана' }})</p>
    </div>
</body>
</html>
