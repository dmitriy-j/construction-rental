@component('mail::message')
# Новая заявка на партнёрство

**Имя:** {{ $data['name'] }}
**Телефон:** {{ $data['phone'] }}
@if(!empty($data['company']))
**Компания:** {{ $data['company'] }}
@endif
**Направление сотрудничества:** {{ $data['direction'] }}
**Регион деятельности:** {{ $data['region'] ?? 'Не указан' }}

С уважением,
{{ config('app.name') }}
@endcomponent
