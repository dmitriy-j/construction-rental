<!DOCTYPE html>
<html>
<head>
    <title>Регистрация компании завершена</title>
    <style>
        /* ... ваш стиль ... */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>ConstructionRental</h2>
            <p>Платформа для аренды строительной техники</p>
        </div>

        <div class="content">
            <h3>Регистрация компании успешно завершена!</h3>

            <!-- Используем director_name вместо contact_name -->
            <p>Здравствуйте, {{ $company->director_name }}!</p>

            <p>Ваша компания <strong>{{ $company->legal_name }}</strong> была успешно зарегистрирована на нашей платформе.</p>

            <div style="background-color: #f1f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p><strong>Ваши данные для входа:</strong></p>
                <ul>
                    <!-- Используем contact_email из компании -->
                    <li>Email: {{ $company->contact_email }}</li>
                    <li>Пароль: указанный при регистрации</li>
                </ul>
            </div>

            <p>Вы можете войти в свой аккаунт по ссылке:</p>
            <p style="text-align: center; margin: 25px 0;">
                <a href="{{ route('login') }}"
                   style="display: inline-block; padding: 10px 20px; background-color: #0d6efd;
                          color: white; text-decoration: none; border-radius: 5px;">
                    Войти в аккаунт
                </a>
            </p>

            <p>Если у вас возникли вопросы, свяжитесь с нашей поддержкой.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} ConstructionRental. Все права защищены.</p>
            <p>Это письмо отправлено автоматически, пожалуйста, не отвечайте на него.</p>
        </div>
    </div>
</body>
</html>
