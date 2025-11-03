<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Заявка на аренду #{{ $rentalRequest->id }}</title>
    <style>
        @page {
            margin: 10px;
        }

        /* СБРАСЫВАЕМ ВСЕ ВОЗМОЖНЫЕ CSS ПЕРЕМЕННЫЕ */
        :root {
            --body-bg: white !important;
            --font-family-sans-serif: 'DejaVu Sans', Arial, sans-serif !important;
            --font-size-base: 10px !important;
            --line-height-base: 1.3 !important;
            --md-sys-color-primary: #0b5ed7 !important;
            --footer-height: auto !important;
            --sidebar-width: auto !important;
            --sidebar-mini-width: auto !important;
            --navbar-height: auto !important;
            --content-padding: 0 !important;
            --primary-gradient: linear-gradient(135deg, #0b5ed7 0%, #0a58ca 100%) !important;
            --accent-light: #00d2ff !important;
            --accent-dark: #00e5ff !important;
            --sidebar-collapsed-width: auto !important;
            --icon-size: auto !important;
            --icon-size-collapsed: auto !important;
            --transition-duration: 0s !important;
            --border-radius: 8px !important;
        }

        /* ЖЕСТКИЙ СБРОС ВСЕХ СТИЛЕЙ */
        * {
            all: unset;
            display: revert;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif !important;
            font-size: 10px !important;
            line-height: 1.3 !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .container {
            max-width: 100% !important;
            margin: 0 auto !important;
            background: white !important;
        }

        /* Шапка - ЧЕРНЫЙ текст на СВЕТЛОМ фоне */
        .header {
            background: #f8f9fa !important;
            color: #000000 !important;
            padding: 15px 20px !important;
            text-align: center !important;
            margin-bottom: 10px !important;
            border-bottom: 2px solid #0b5ed7 !important;
        }

        .document-title {
            font-size: 18px !important;
            font-weight: bold !important;
            margin: 0 0 5px 0 !important;
            text-transform: uppercase !important;
            color: #000000 !important;
        }

        .request-number {
            background: #0b5ed7 !important;
            color: #ffffff !important;
            padding: 5px 15px !important;
            border-radius: 15px !important;
            display: inline-block !important;
            font-size: 11px !important;
            font-weight: 600 !important;
        }

        /* Основная информация */
        .project-section {
            margin: 0 20px 10px 20px !important;
            padding: 15px !important;
            background: #f8f9fa !important;
            border-radius: 8px !important;
            border-left: 4px solid #0b5ed7 !important;
        }

        .project-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: flex-start !important;
            margin-bottom: 10px !important;
        }

        .project-main {
            flex: 1 !important;
        }

        .project-title {
            font-size: 14px !important;
            font-weight: bold !important;
            color: #0b5ed7 !important;
            margin: 0 0 5px 0 !important;
        }

        .project-meta {
            display: grid !important;
            grid-template-columns: auto auto !important;
            gap: 15px !important;
            font-size: 10px !important;
        }

        .meta-item {
            margin-bottom: 3px !important;
        }

        .meta-label {
            font-weight: 600 !important;
            color: #6c757d !important;
            display: inline-block !important;
            width: 80px !important;
        }

        .meta-value {
            color: #000000 !important;
            font-weight: 500 !important;
        }

        /* Бюджет и статус - слева */
        .budget-status {
            text-align: left !important; /* Изменили на left */
            min-width: 150px !important;
            background: #ffffff !important;
            padding: 10px !important;
            border-radius: 6px !important;
            border: 1px solid #dee2e6 !important;
        }

        .status-badge {
            display: inline-block !important;
            padding: 4px 10px !important;
            background: #0b5ed7 !important;
            color: #ffffff !important;
            border-radius: 12px !important;
            font-size: 9px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            margin-bottom: 8px !important; /* Увеличили отступ */
        }

        .budget-info {
            margin-top: 8px !important;
        }

        .budget-label {
            font-weight: 600 !important;
            color: #6c757d !important;
            font-size: 9px !important;
            text-transform: uppercase !important;
            margin-bottom: 2px !important;
        }

        .budget-amount {
            font-size: 16px !important;
            font-weight: bold !important;
            color: #198754 !important;
        }

        .project-description {
            margin-top: 8px !important;
            padding-top: 8px !important;
            border-top: 1px dashed #dee2e6 !important;
            font-size: 10px !important;
            color: #000000 !important;
            line-height: 1.3 !important;
        }

        /* Условия аренды */
        .conditions-section {
            margin: 0 20px 10px 20px !important;
            padding: 12px !important;
            background: white !important;
            border-radius: 6px !important;
            border: 1px solid #dee2e6 !important;
        }

        .section-title {
            font-size: 11px !important;
            font-weight: bold !important;
            color: #0b5ed7 !important;
            margin-bottom: 8px !important;
            text-transform: uppercase !important;
        }

        .conditions-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 8px !important;
        }

        .condition-row {
            display: grid !important;
            grid-template-columns: 80px 1fr !important;
            gap: 5px !important;
            align-items: center !important;
            padding: 3px 0 !important;
        }

        .condition-label {
            font-weight: 600 !important;
            color: #6c757d !important;
            font-size: 9px !important;
            text-transform: uppercase !important;
        }

        .condition-value {
            color: #000000 !important;
            font-size: 10px !important;
            font-weight: 500 !important;
        }

        /* Таблица позиций */
        .items-section {
            margin: 0 20px 15px 20px !important;
            padding: 12px !important;
            background: white !important;
            border-radius: 6px !important;
            border: 1px solid #dee2e6 !important;
        }

        .compact-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 9px !important;
            margin-bottom: 8px !important;
        }

        .compact-table th {
            background: #0b5ed7 !important;
            color: #ffffff !important;
            padding: 6px 5px !important;
            text-align: left !important;
            font-weight: bold !important;
            border: none !important;
        }

        .compact-table td {
            padding: 5px 5px !important;
            border-bottom: 1px solid #e9ecef !important;
            vertical-align: top !important;
            color: #000000 !important;
        }

        .compact-table tr:nth-child(even) td {
            background: #f8f9fa !important;
        }

        .specifications-container {
            max-height: 60px !important;
            overflow: hidden !important;
        }

        .specifications-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 1px !important;
        }

        .spec-item {
            padding: 1px 0 !important;
            line-height: 1.2 !important;
            font-size: 8px !important;
            color: #000000 !important;
        }

        .spec-item strong {
            color: #0b5ed7 !important;
        }

        .conditions-indicator {
            font-size: 8px !important;
            padding: 2px 6px !important;
            border-radius: 3px !important;
            background: #e9ecef !important;
            display: inline-block !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            color: #000000 !important;
        }

        .conditions-individual {
            background: #fff3cd !important;
            color: #856404 !important;
        }

        .conditions-standard {
            background: #d1edff !important;
            color: #0b5ed7 !important;
        }

        /* Итоги */
        .summary {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
            margin-top: 8px !important;
            padding: 8px !important;
            background: #f8f9fa !important;
            border-radius: 4px !important;
            font-size: 9px !important;
        }

        .summary-label {
            font-weight: 600 !important;
            color: #6c757d !important;
            font-size: 8px !important;
            margin-bottom: 1px !important;
        }

        .summary-value {
            font-weight: bold !important;
            color: #0b5ed7 !important;
            font-size: 10px !important;
        }

        /* Футер */
        .footer {
            background: #2b3035 !important;
            color: #ffffff !important;
            padding: 10px 20px !important;
            margin-top: 10px !important;
            font-size: 8px !important;
        }

        .footer-content {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }

        .footer-brand {
            font-weight: bold !important;
            font-size: 9px !important;
            color: #ffffff !important;
        }

        .footer-meta {
            color: #adb5bd !important;
        }

        /* Утилиты */
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        .mb-2 { margin-bottom: 2px !important; }
        .summary-item { text-align: center !important; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Шапка с ЧЕРНЫМ текстом на СВЕТЛОМ фоне -->
        <div class="header">
            <div class="document-title">Заявка на аренду</div>
            <div class="request-number">
                № {{ $rentalRequest->id }} от {{ $rentalRequest->created_at->format('d.m.Y') }}
            </div>
        </div>

        <!-- Основная информация -->
        <div class="project-section">
            <div class="project-header">
                <div class="project-main">
                    <div class="project-title">{{ $rentalRequest->title }}</div>
                    <div class="project-meta">
                        <div class="meta-item">
                            <span class="meta-label">Локация:</span>
                            <span class="meta-value">{{ $rentalRequest->location->name ?? 'Не указана' }}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Период аренды:</span>
                            <span class="meta-value">
                                {{ $rentalRequest->rental_period_start->format('d.m.Y') }} - {{ $rentalRequest->rental_period_end->format('d.m.Y') }}
                                ({{ \Carbon\Carbon::parse($rentalRequest->rental_period_start)->diffInDays($rentalRequest->rental_period_end) + 1 }} дн.)
                            </span>
                        </div>
                    </div>
                </div>
                <!-- Бюджет и статус - теперь слева -->
                <div class="budget-status">
                    <div class="status-badge">{{ $rentalRequest->status_text }}</div>
                    <div class="budget-info">
                        <div class="budget-label">Бюджет заявки</div>
                        <div class="budget-amount">
                            {{ number_format($rentalRequest->total_budget ?? $rentalRequest->calculated_budget_from, 0, ',', ' ') }} ₽
                        </div>
                    </div>
                </div>
            </div>
            @if($rentalRequest->description)
            <div class="project-description">
                {{ $rentalRequest->description }}
            </div>
            @endif
        </div>

        <!-- Условия аренды с правильным переводом -->
        @if($rentalRequest->rental_conditions && is_array($rentalRequest->rental_conditions))
        <div class="conditions-section">
            <div class="section-title">Условия аренды</div>
            <div class="conditions-grid">
                <!-- Первая колонка -->
                <div class="condition-row">
                    <span class="condition-label">Тип оплаты</span>
                    <span class="condition-value">
                        @php
                            $paymentTypes = [
                                'hourly' => 'Почасовая',
                                'daily' => 'Посуточная',
                                'monthly' => 'Помесячная',
                                'shift' => 'За смену'
                            ];
                        @endphp
                        {{ $paymentTypes[$rentalRequest->rental_conditions['payment_type']] ?? 'Не указано' }}
                    </span>
                </div>
                <div class="condition-row">
                    <span class="condition-label">Оплата ГСМ</span>
                    <span class="condition-value">
                        {{ $rentalRequest->rental_conditions['gsm_payment'] === 'included' ? 'Включено в стоимость' : 'Оплачивается отдельно' }}
                    </span>
                </div>
                <div class="condition-row">
                    <span class="condition-label">Смен в день</span>
                    <span class="condition-value">{{ $rentalRequest->rental_conditions['shifts_per_day'] ?? '1' }}</span>
                </div>
                <div class="condition-row">
                    <span class="condition-label">Часов в смену</span>
                    <span class="condition-value">{{ $rentalRequest->rental_conditions['hours_per_shift'] ?? '8' }}</span>
                </div>

                <!-- Вторая колонка -->
                <div class="condition-row">
                    <span class="condition-label">Оператор</span>
                    <span class="condition-value">
                        {{ ($rentalRequest->rental_conditions['operator_included'] ?? false) ? 'Включен' : 'Не включен' }}
                    </span>
                </div>
                <div class="condition-row">
                    <span class="condition-label">Проживание</span>
                    <span class="condition-value">
                        {{ ($rentalRequest->rental_conditions['accommodation_payment'] ?? false) ? 'Оплачивается' : 'Не оплачивается' }}
                    </span>
                </div>
                <div class="condition-row">
                    <span class="condition-label">Транспортировка</span>
                    <span class="condition-value">
                        @php
                            $transportation = [
                                'lessor' => 'Арендодателем',
                                'lessee' => 'Арендатором',
                                'separate' => 'Отдельным соглашением'
                            ];
                        @endphp
                        {{ $transportation[$rentalRequest->rental_conditions['transportation_organized_by']] ?? 'Не указано' }}
                    </span>
                </div>
                @if($rentalRequest->rental_conditions['extension_possibility'] ?? false)
                <div class="condition-row">
                    <span class="condition-label">Продление</span>
                    <span class="condition-value">Возможно</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Позиции заявки -->
        <div class="items-section">
            <div class="section-title">Позиции заявки</div>

            @if($items && count($items) > 0)
            <table class="compact-table">
                <thead>
                    <tr>
                        <th width="25%">Категория</th>
                        <th width="8%">Кол-во</th>
                        <th width="52%">Технические параметры</th>
                        <th width="15%">Условия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td><strong>{{ $item->category->name ?? 'Не указана' }}</strong></td>
                        <td class="text-center" style="font-weight: bold;">{{ $item->quantity }} шт.</td>
                        <td>
                            <div class="specifications-container">
                                <div class="specifications-list">
                                    @if(!empty($item->formatted_specifications) && is_array($item->formatted_specifications))
                                        @foreach($item->formatted_specifications as $spec)
                                            @if(!empty($spec['formatted']) && !empty($spec['value']))
                                                <div class="spec-item">
                                                    <strong>{{ $spec['label'] }}:</strong> {{ $spec['display_value'] }}
                                                </div>
                                            @endif
                                        @endforeach
                                    @else
                                        <div class="spec-item" style="color: #6c757d; font-style: italic;">
                                            Стандартные характеристики
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="conditions-indicator {{ $item->use_individual_conditions ? 'conditions-individual' : 'conditions-standard' }}">
                                {{ $item->use_individual_conditions ? 'Индивид.' : 'Стандарт.' }}
                            </span>
                            @if($item->hourly_rate)
                            <div class="mb-2">
                                <small style="font-weight: 600;">{{ number_format($item->hourly_rate, 0, ',', ' ') }} ₽/час</small>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="summary">
                <div class="summary-item">
                    <div class="summary-label">Всего позиций</div>
                    <div class="summary-value">{{ $items->count() }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Общее количество</div>
                    <div class="summary-value">{{ $items->sum('quantity') }} ед. техники</div>
                </div>
            </div>
            @else
            <div style="text-align: center; color: #6c757d; font-style: italic; padding: 15px;">
                Нет позиций в заявке
            </div>
            @endif
        </div>

        <!-- Футер -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-brand">Федеральная Арендная Платформа</div>
                <div class="footer-meta">
                    {{ $user->name ?? 'Система' }} • {{ $exportDate ?? now()->format('d.m.Y H:i') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
