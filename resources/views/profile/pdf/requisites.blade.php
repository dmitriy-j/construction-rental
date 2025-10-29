{{-- resources/views/profile/pdf/requisites.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Реквизиты компании - {{ $company->legal_name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            background: #f5f5f5;
            padding: 8px 12px;
            border-left: 4px solid #0b5ed7;
            font-size: 14px;
        }
        .row {
            display: flex;
            margin-bottom: 6px;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
        }
        .label {
            width: 250px;
            font-weight: bold;
            color: #666;
        }
        .value {
            flex: 1;
            font-weight: normal;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #0b5ed7;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->legal_name }}</div>
        <p>Банковские и юридические реквизиты</p>
        <p style="font-size: 10px; color: #666;">Дата формирования: {{ now()->format('d.m.Y H:i') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Юридическая информация</div>
        <div class="row">
            <div class="label">Полное наименование:</div>
            <div class="value">{{ $company->legal_name }}</div>
        </div>
        <div class="row">
            <div class="label">ИНН:</div>
            <div class="value">{{ $company->inn }}</div>
        </div>
        <div class="row">
            <div class="label">КПП:</div>
            <div class="value">{{ $company->kpp }}</div>
        </div>
        <div class="row">
            <div class="label">ОГРН:</div>
            <div class="value">{{ $company->ogrn }}</div>
        </div>
        <div class="row">
            <div class="label">ОКПО:</div>
            <div class="value">{{ $company->okpo ?? 'Не указано' }}</div>
        </div>
        <div class="row">
            <div class="label">Система налогообложения:</div>
            <div class="value">{{ $company->getTaxSystemCode() }}</div>
        </div>
        <div class="row">
            <div class="label">Юридический адрес:</div>
            <div class="value">{{ $company->legal_address }}</div>
        </div>
        <div class="row">
            <div class="label">Фактический адрес:</div>
            <div class="value">{{ $company->actual_address ?? $company->legal_address }}</div>
        </div>
        <div class="row">
            <div class="label">Директор:</div>
            <div class="value">{{ $company->director_name }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Банковские реквизиты</div>
        <div class="row">
            <div class="label">Наименование банка:</div>
            <div class="value">{{ $company->bank_name }}</div>
        </div>
        <div class="row">
            <div class="label">Расчетный счет:</div>
            <div class="value">{{ $company->bank_account }}</div>
        </div>
        <div class="row">
            <div class="label">БИК:</div>
            <div class="value">{{ $company->bik }}</div>
        </div>
        <div class="row">
            <div class="label">Корреспондентский счет:</div>
            <div class="value">{{ $company->correspondent_account }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Контактная информация</div>
        <div class="row">
            <div class="label">Контактный телефон:</div>
            <div class="value">{{ $company->phone }}</div>
        </div>
        <div class="row">
            <div class="label">Email для связи:</div>
            <div class="value">{{ $user->email }}</div>
        </div>
    </div>

    <div class="footer">
        <p>Сформировано в системе ConstructionRental • cr.loc</p>
        <p>Документ предназначен для служебного использования</p>
    </div>
</body>
</html>
