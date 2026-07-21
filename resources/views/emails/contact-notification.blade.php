<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .email-header {
            background: linear-gradient(135deg, #0b5ed7, #002d72);
            padding: 30px 40px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .email-header p {
            color: rgba(255,255,255,0.85);
            margin: 8px 0 0;
            font-size: 14px;
        }
        .email-body {
            padding: 30px 40px;
        }
        .field-group {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .field-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .field-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #888;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .field-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        .message-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 8px;
            border-left: 4px solid #0b5ed7;
        }
        .message-box .field-value {
            font-style: italic;
            line-height: 1.6;
        }
        .status-badge {
            display: inline-block;
            background: #ffc107;
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 20px 40px;
            text-align: center;
            color: #888;
            font-size: 12px;
        }
        .email-footer a {
            color: #0b5ed7;
            text-decoration: none;
        }
        .btn-admin {
            display: inline-block;
            background: #0b5ed7;
            color: #ffffff;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn-admin:hover {
            background: #002d72;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>📬 Новое обращение</h1>
            <p>С сайта «{{ config('app.name') }}»</p>
        </div>

        <div class="email-body">
            <div class="field-group">
                <div class="field-label">Дата и время</div>
                <div class="field-value">{{ $contactMessage->created_at->format('d.m.Y H:i') }}</div>
            </div>

            <div class="field-group">
                <div class="field-label">Имя отправителя</div>
                <div class="field-value">{{ $contactMessage->name }}</div>
            </div>

            <div class="field-group">
                <div class="field-label">Телефон</div>
                <div class="field-value">
                    <a href="tel:{{ $contactMessage->phone }}" style="color: #0b5ed7; text-decoration: none;">
                        {{ $contactMessage->phone }}
                    </a>
                </div>
            </div>

            @if($contactMessage->email)
            <div class="field-group">
                <div class="field-label">Email</div>
                <div class="field-value">
                    <a href="mailto:{{ $contactMessage->email }}" style="color: #0b5ed7; text-decoration: none;">
                        {{ $contactMessage->email }}
                    </a>
                </div>
            </div>
            @endif

            @if($contactMessage->message)
            <div class="field-group">
                <div class="field-label">Сообщение</div>
                <div class="message-box">
                    <div class="field-value">{{ $contactMessage->message }}</div>
                </div>
            </div>
            @endif

            <div style="text-align: center; margin-top: 10px;">
                <span class="status-badge">⏳ Ожидает обработки</span>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/admin/contacts') }}" class="btn-admin">
                    👤 Перейти к обращениям в админ-панели
                </a>
            </div>
        </div>

        <div class="email-footer">
            <p>С уважением, команда «{{ config('app.name') }}»</p>
            <p>
                <a href="{{ url('/') }}">{{ config('app.name') }}</a> &bull;
                <a href="{{ url('/admin') }}">Админ-панель</a>
            </p>
        </div>
    </div>
</body>
</html>
