{{-- resources/views/profile/pdf/company-details.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Реквизиты компании - {{ $company->legal_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: bold; margin-bottom: 5px; background: #f5f5f5; padding: 5px; }
        .row { display: flex; margin-bottom: 3px; }
        .label { width: 200px; font-weight: bold; }
        .value { flex: 1; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Банковские реквизиты компании</h1>
        <p>Дата формирования: {{ now()->format('d.m.Y H:i') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Общая информация</div>
        <div class="row">
            <div class="label">Юридическое название:</div>
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
            <div class="label">Юридический адрес:</div>
            <div class="value">{{ $company->legal_address }}</div>
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
            <div class="label">Директор:</div>
            <div class="value">{{ $company->director_name }}</div>
        </div>
        <div class="row">
            <div class="label">Телефон:</div>
            <div class="value">{{ $company->phone }}</div>
        </div>
        <div class="row">
            <div class="label">Email пользователя:</div>
            <div class="value">{{ $user->email }}</div>
        </div>
    </div>

    <div class="footer">
        <p>Сформировано в системе ConstructionRental</p>
        <p>cr.loc</p>
    </div>
</body>
</html>
