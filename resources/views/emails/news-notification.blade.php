<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $news->title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
        .header p { margin: 8px 0 0; opacity: 0.9; font-size: 14px; }
        .content { background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .meta { color: #94a3b8; font-size: 13px; margin-bottom: 15px; }
        .excerpt { font-size: 16px; line-height: 1.6; color: #475569; }
        .action-button { display: inline-block; background: #2563eb; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 500; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 12px; }
        .unsubscribe { color: #94a3b8; font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $news->title }}</h1>
            <p>{{ config('app.name', 'Платформа аренды техники') }}</p>
        </div>
        <div class="content">
            <div class="meta">{{ $news->published_at?->format('d.m.Y') ?? $news->created_at->format('d.m.Y') }}</div>
            <div class="excerpt">{!! nl2br(e($news->excerpt ?? Str::limit(strip_tags($news->content), 200))) !!}</div>
            <div style="text-align:center;">
                <a href="{{ $actionUrl }}" class="action-button">{{ $actionText }}</a>
            </div>
            @if($unsubscribeUrl)
                <div class="footer">
                    <p class="unsubscribe">Если вы не хотите получать эти письма, <a href="{{ $unsubscribeUrl }}">отпишитесь</a>.</p>
                </div>
            @endif
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
