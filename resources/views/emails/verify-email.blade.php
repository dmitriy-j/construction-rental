<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Подтверждение email - ФАП</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c5282; color: white; padding: 20px; text-align: center; }
        .content { background: #f7fafc; padding: 30px; border-radius: 5px; }
        .button { display: inline-block; background: #2c5282; color: white;
                 padding: 12px 24px; text-decoration: none; border-radius: 4px;
                 margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #718096; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ФАП</h1>
            <p>Платформа аренды строительной техники</p>
        </div>

        <div class="content">
            <h2>Подтвердите ваш email адрес</h2>

            <p>Здравствуйте, <strong>{{ $user->name }}</strong>!</p>

            <p>Благодарим за регистрацию на платформе ФАП. Для завершения регистрации и активации вашего аккаунта, пожалуйста, подтвердите ваш email адрес.</p>

            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">
                    Подтвердить Email
                </a>
            </div>

            <p>Если кнопка не работает, скопируйте и вставьте следующую ссылку в браузер:</p>
            <p style="word-break: break-all; font-size: 12px; color: #4a5568;">
                {{ $verificationUrl }}
            </p>

            <p><strong>Компания:</strong> {{ $user->company->legal_name ?? 'Не указана' }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>

            <p>Если вы не регистрировались на нашей платформе, пожалуйста, проигнорируйте это письмо.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} ФАП. Все права защищены.</p>
            <p>Это письмо отправлено автоматически. Пожалуйста, не отвечайте на него.</p>
        </div>
    </div>
</body>
</html>
