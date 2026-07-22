<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .content h2 {
            margin-top: 0;
            color: #1e293b;
            font-size: 18px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #64748b;
            font-size: 14px;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 500;
            font-size: 14px;
            text-align: right;
        }
        .detail-value.highlight {
            color: #2563eb;
            font-weight: 700;
            font-size: 16px;
        }
        .action-button {
            display: inline-block;
            background: #2563eb;
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            margin-top: 20px;
            font-size: 14px;
        }
        .action-button:hover {
            background: #1d4ed8;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 12px;
        }
        .badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $title }}</h1>
            <p>{{ config('app.name', 'Платформа аренды техники') }}</p>
        </div>
        <div class="content">
            <h2>Детали события</h2>
            @foreach ($data as $label => $value)
                @if (!in_array($label, ['type', 'url']))
                    <div class="detail-row">
                        <span class="detail-label">{{ $label }}</span>
                        <span class="detail-value {{ in_array($label, ['Сумма', 'Итого']) ? 'highlight' : '' }}">
                            @if (is_object($value) && method_exists($value, 'format'))
                                {{ $value->format('d.m.Y H:i') }}
                            @elseif (is_string($value) || is_numeric($value))
                                {{ $value }}
                            @else
                                {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                            @endif
                        </span>
                    </div>
                @endif
            @endforeach

            @if ($actionUrl)
                <div style="text-align: center; margin-top: 10px;">
                    <a href="{{ $actionUrl }}" class="action-button">{{ $actionText }}</a>
                </div>
            @endif
        </div>
        <div class="footer">
            <p>Это автоматическое уведомление платформы {{ config('app.name') }}.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
