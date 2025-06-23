@component('mail::message')
# Регистрация компании успешно завершена!

Добрый день! Ваша компания **{{ $companyName }}** зарегистрирована на платформе.

**Данные для входа:**
Email: {{ $user->email }}
Пароль: (указанный при регистрации)

@component('mail::button', ['url' => $loginLink])
Войти в личный кабинет
@endcomponent

Если вы не регистрировались, пожалуйста, [свяжитесь с поддержкой](mailto:{{ $supportEmail }}).

С уважением,
Команда {{ config('app.name') }}
@endcomponent
