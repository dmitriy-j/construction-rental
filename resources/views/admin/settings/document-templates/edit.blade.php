@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Редактирование шаблона: {{ $documentTemplate->name }}</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.settings.document-templates.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.settings.document-templates.update', $documentTemplate) }}" method="POST" enctype="multipart/form-data" id="template-form">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Название шаблона *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $documentTemplate->name }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="type" class="form-label">Тип документа *</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Выберите тип документа</option>
                                        @foreach($templateTypes as $key => $value)
                                            <option value="{{ $key }}" {{ $documentTemplate->type == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea class="form-control" id="description" name="description" rows="3">{{ $documentTemplate->description }}</textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="template_file" class="form-label">Файл шаблона (Excel)</label>
                                    <input type="file" class="form-control" id="template_file" name="template_file" accept=".xlsx,.xls">
                                    <div class="form-text">
                                        Текущий файл:
                                        <a href="{{ Storage::disk('public')->url($documentTemplate->file_path) }}" target="_blank">
                                            {{ basename($documentTemplate->file_path) }}
                                        </a>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ $documentTemplate->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Активный шаблон</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Настройка полей</label>
                            <div id="field-mapping-container">
                                @if($documentTemplate->mapping)
                                    @foreach($documentTemplate->mapping as $field => $cell)
                                        <div class="field-mapping-item mb-2">
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Поле данных" name="field_names[]" value="{{ $field }}">
                                                <input type="text" class="form-control" placeholder="Ячейка" name="field_cells[]" value="{{ $cell }}">
                                                <button type="button" class="btn btn-danger remove-field"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" id="add-field" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-plus"></i> Добавить поле
                            </button>
                            <input type="hidden" name="mapping" id="mapping-data">
                        </div>

                        <button type="submit" class="btn btn-primary">Обновить шаблон</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Инструкция по работе с шаблонами</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="templateInstructions">
                        <!-- Основные принципы -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicPrinciples">
                                    Основные принципы
                                </button>
                            </h2>
                            <div id="basicPrinciples" class="accordion-collapse collapse show" data-bs-parent="#templateInstructions">
                                <div class="accordion-body">
                                    <p>Система поддерживает два способа подстановки данных в шаблоны:</p>
                                    <ol>
                                        <li><strong>Прямая подстановка</strong> - указание точной ячейки для каждого поля</li>
                                        <li><strong>Плейсхолдеры</strong> - использование меток вида <code>@{{field.path}}</code> в ячейках Excel</li>
                                    </ol>
                                    <p class="mb-1"><strong>Рекомендуется:</strong></p>
                                    <ul>
                                        <li>Для табличных данных (позиции УПД) использовать прямую подстановку</li>
                                        <li>Для одиночных значений использовать плейсхолдеры</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Для УПД -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#updFields">
                                    Поля для УПД
                                </button>
                            </h2>
                            <div id="updFields" class="accordion-collapse collapse" data-bs-parent="#templateInstructions">
                                <div class="accordion-body">
                                    <p>Для УПД доступны следующие поля данных:</p>

                                    <h6>Основные данные:</h6>
                                    <ul>
                                        <li><code>upd.number</code> - номер УПД</li>
                                        <li><code>upd.date</code> - дата УПД</li>
                                        <li><code>upd.contract_number</code> - номер договора</li>
                                        <li><code>upd.contract_date</code> - дата договора</li>
                                        <li><code>upd.shipment_date</code> - дата отгрузки</li>
                                        <li><code>upd.total_without_vat</code> - сумма без НДС</li>
                                        <li><code>upd.total_vat</code> - сумма НДС</li>
                                        <li><code>upd.total_with_vat</code> - сумма с НДС</li>
                                        <li><code>period</code> - период оказания услуг</li>
                                    </ul>

                                    <h6>Данные платформы (продавца):</h6>
                                    <ul>
                                        <li><code>platform.name</code> - название</li>
                                        <li><code>platform.legal_name</code> - юридическое название</li>
                                        <li><code>platform.address</code> - адрес</li>
                                        <li><code>platform.inn</code> - ИНН</li>
                                        <li><code>platform.kpp</code> - КПП</li>
                                        <li><code>platform.inn_kpp</code> - ИНН/КПП (формат "1234567890/123456789")</li>
                                        <li><code>platform.bank_name</code> - банк</li>
                                        <li><code>platform.bik</code> - БИК</li>
                                        <li><code>platform.account_number</code> - расчетный счет</li>
                                        <li><code>platform.correspondent_account</code> - корр. счет</li>
                                    </ul>

                                    <h6>Данные арендатора (покупателя):</h6>
                                    <ul>
                                        <li><code>lessee.name</code> - название</li>
                                        <li><code>lessee.legal_name</code> - юридическое название</li>
                                        <li><code>lessee.address</code> - адрес</li>
                                        <li><code>lessee.inn</code> - ИНН</li>
                                        <li><code>lessee.kpp</code> - КПП</li>
                                        <li><code>lessee.inn_kpp</code> - ИНН/КПП (формат "1234567890/123456789")</li>
                                        <li><code>lessee.bank_name</code> - банк</li>
                                        <li><code>lessee.bik</code> - БИК</li>
                                        <li><code>lessee.account_number</code> - расчетный счет</li>
                                        <li><code>lessee.correspondent_account</code> - корр. счет</li>
                                    </ul>

                                    <h6>Табличная часть (позиции):</h6>
                                    <ul>
                                        <li><code>items.#.code</code> - код позиции</li>
                                        <li><code>items.#.name</code> - наименование</li>
                                        <li><code>items.#.unit</code> - единица измерения</li>
                                        <li><code>items.#.quantity</code> - количество</li>
                                        <li><code>items.#.price</code> - цена за единицу</li>
                                        <li><code>items.#.amount</code> - сумма без НДС</li>
                                        <li><code>items.#.vat_rate</code> - ставка НДС</li>
                                        <li><code>items.#.vat_amount</code> - сумма НДС</li>
                                        <li><code>items.#.total_with_vat</code> - сумма с НДС</li>
                                    </ul>
                                    <p>Для табличной части используйте маппинг с указанием начальной ячейки, например <code>items.#.name → B15</code></p>
                                </div>
                            </div>
                        </div>
                        <!-- Для транспортной накладной -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#transportInvoiceFields">
                                    Поля для транспортной накладной
                                </button>
                            </h2>
                            <div id="transportInvoiceFields" class="accordion-collapse collapse" data-bs-parent="#templateInstructions">
                                <div class="accordion-body">
                                    <p>Для транспортной накладной доступны следующие поля данных:</p>

                                    <h6>Основные данные:</h6>
                                    <ul>
                                        <li><code>transport_invoice.number</code> - номер накладной</li>
                                        <li><code>transport_invoice.date</code> - дата составления</li>
                                        <li><code>transport_invoice.departure_date</code> - дата отправления</li>
                                        <li><code>transport_invoice.delivery_date</code> - дата доставки</li>
                                        <li><code>transport_invoice.departure_point</code> - пункт отправления</li>
                                        <li><code>transport_invoice.destination_point</code> - пункт назначения</li>
                                        <li><code>transport_invoice.distance</code> - расстояние (км)</li>
                                        <li><code>transport_invoice.cargo_weight</code> - вес груза</li>
                                        <li><code>transport_invoice.cargo_description</code> - описание груза</li>
                                    </ul>

                                    <h6>Данные перевозчика:</h6>
                                    <ul>
                                        <li><code>carrier.name</code> - название перевозчика</li>
                                        <li><code>carrier.address</code> - адрес перевозчика</li>
                                        <li><code>carrier.inn</code> - ИНН перевозчика</li>
                                        <li><code>carrier.vehicle_number</code> - гос. номер ТС</li>
                                        <li><code>carrier.driver_name</code> - ФИО водителя</li>
                                        <li><code>carrier.driver_license</code> - водительское удостоверение</li>
                                    </ul>

                                    <h6>Данные грузоотправителя/грузополучателя:</h6>
                                    <ul>
                                        <li><code>sender.name</code> - название отправителя</li>
                                        <li><code>sender.address</code> - адрес отправителя</li>
                                        <li><code>recipient.name</code> - название получателя</li>
                                        <li><code>recipient.address</code> - адрес получателя</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Для акта выполненных работ -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#completionActFields">
                                    Поля для акта выполненных работ
                                </button>
                            </h2>
                            <div id="completionActFields" class="accordion-collapse collapse" data-bs-parent="#templateInstructions">
                                <div class="accordion-body">
                                    <p>Для акта выполненных работ доступны следующие поля данных:</p>

                                    <h6>Основные данные:</h6>
                                    <ul>
                                        <li><code>completion_act.number</code> - номер акта</li>
                                        <li><code>completion_act.date</code> - дата составления</li>
                                        <li><code>completion_act.period_start</code> - период выполнения работ (начало)</li>
                                        <li><code>completion_act.period_end</code> - период выполнения работ (окончание)</li>
                                        <li><code>completion_act.work_description</code> - описание выполненных работ</li>
                                        <li><code>completion_act.total_amount</code> - общая сумма</li>
                                    </ul>

                                    <h6>Данные заказчика:</h6>
                                    <ul>
                                        <li><code>customer.name</code> - название заказчика</li>
                                        <li><code>customer.address</code> - адрес заказчика</li>
                                        <li><code>customer.inn</code> - ИНН заказчика</li>
                                        <li><code>customer.representative</code> - представитель заказчика</li>
                                    </ul>

                                    <h6>Данные исполнителя:</h6>
                                    <ul>
                                        <li><code>contractor.name</code> - название исполнителя</li>
                                        <li><code>contractor.address</code> - адрес исполнителя</li>
                                        <li><code>contractor.inn</code> - ИНН исполнителя</li>
                                        <li><code>contractor.representative</code> - представитель исполнителя</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Для ЭСМ-7 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#esm7Fields">
                                    Поля для ЭСМ-7
                                </button>
                            </h2>
                            <div id="esm7Fields" class="accordion-collapse collapse" data-bs-parent="#templateInstructions">
                                <div class="accordion-body">
                                    <p>Для формы ЭСМ-7 доступны следующие поля данных:</p>

                                    <h6>Основные данные:</h6>
                                    <ul>
                                        <li><code>esm7.number</code> - номер акта</li>
                                        <li><code>esm7.date</code> - дата составления</li>
                                        <li><code>esm7.contract_number</code> - номер договора</li>
                                        <li><code>esm7.contract_date</code> - дата договора</li>
                                        <li><code>esm7.work_period</code> - период выполнения работ</li>
                                        <li><code>esm7.total_cost</code> - стоимость работ</li>
                                    </ul>

                                    <h6>Данные заказчика:</h6>
                                    <ul>
                                        <li><code>customer.name</code> - название заказчика</li>
                                        <li><code>customer.address</code> - адрес заказчика</li>
                                        <li><code>customer.inn</code> - ИНН заказчика</li>
                                        <li><code>customer.kpp</code> - КПП заказчика</li>
                                    </ul>

                                    <h6>Данные подрядчика:</h6>
                                    <ul>
                                        <li><code>contractor.name</code> - название подрядчика</li>
                                        <li><code>contractor.address</code> - адрес подрядчика</li>
                                        <li><code>contractor.inn</code> - ИНН подрядчика</li>
                                        <li><code>contractor.kpp</code> - КПП подрядчика</li>
                                    </ul>

                                    <h6>Табличная часть (виды работ):</h6>
                                    <ul>
                                        <li><code>work_items.#.code</code> - код работы</li>
                                        <li><code>work_items.#.name</code> - наименование работы</li>
                                        <li><code>work_items.#.unit</code> - единица измерения</li>
                                        <li><code>work_items.#.quantity</code> - количество</li>
                                        <li><code>work_items.#.price</code> - цена за единицу</li>
                                        <li><code>work_items.#.cost</code> - стоимость</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Для счетов -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#invoiceFields">
                                    Поля для счетов
                                </button>
                            </h2>
                            <div id="invoiceFields" class="accordion-collapse collapse" data-bs-parent="#templateInstructions">
                                <div class="accordion-body">
                                    <p>Для счетов доступны следующие поля данных:</p>

                                    <h6>Основные данные:</h6>
                                    <ul>
                                        <li><code>invoice.number</code> - номер счета</li>
                                        <li><code>invoice.date</code> - дата счета</li>
                                        <li><code>invoice.due_date</code> - срок оплаты</li>
                                        <li><code>invoice.total_amount</code> - сумма к оплате</li>
                                        <li><code>invoice.currency</code> - валюта</li>
                                    </ul>

                                    <h6>Данные поставщика:</h6>
                                    <ul>
                                        <li><code>supplier.name</code> - название поставщика</li>
                                        <li><code>supplier.address</code> - адрес поставщика</li>
                                        <li><code>supplier.inn</code> - ИНН поставщика</li>
                                        <li><code>supplier.kpp</code> - КПП поставщика</li>
                                        <li><code>supplier.bank_name</code> - банк поставщика</li>
                                        <li><code>supplier.bank_account</code> - расчетный счет</li>
                                        <li><code>supplier.bik</code> - БИК банка</li>
                                    </ul>

                                    <h6>Данные плательщика:</h6>
                                    <ul>
                                        <li><code>payer.name</code> - название плательщика</li>
                                        <li><code>payer.address</code> - адрес плательщика</li>
                                        <li><code>payer.inn</code> - ИНН плательщика</li>
                                        <li><code>payer.kpp</code> - КПП плательщика</li>
                                    </ul>

                                    <h6>Табличная часть (товары/услуги):</h6>
                                    <ul>
                                        <li><code>invoice_items.#.name</code> - наименование</li>
                                        <li><code>invoice_items.#.quantity</code> - количество</li>
                                        <li><code>invoice_items.#.unit</code> - единица измерения</li>
                                        <li><code>invoice_items.#.price</code> - цена</li>
                                        <li><code>invoice_items.#.amount</code> - сумма</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Для путевых листов -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#waybillFields">
                                    Поля для путевых листов
                                </button>
                            </h2>
                            <div id="waybillFields" class="accordion-collapse collapse" data-bs-parent="#templateInstructions">
                                <div class="accordion-body">
                                    <p>Для путевых листов доступны следующие поля данных:</p>

                                    <h6>Основные данные:</h6>
                                    <ul>
                                        <li><code>waybill.number</code> - номер путевого листа</li>
                                        <li><code>waybill.date</code> - дата выдачи</li>
                                        <li><code>waybill.vehicle_number</code> - гос. номер ТС</li>
                                        <li><code>waybill.vehicle_model</code> - марка и модель ТС</li>
                                        <li><code>waybill.driver_name</code> - ФИО водителя</li>
                                        <li><code>waybill.driver_license</code> - водительское удостоверение</li>
                                    </ul>

                                    <h6>Маршрут движения:</h6>
                                    <ul>
                                        <li><code>waybill.route_start</code> - пункт отправления</li>
                                        <li><code>waybill.route_end</code> - пункт назначения</li>
                                        <li><code>waybill.distance_planned</code> - запланированный пробег (км)</li>
                                        <li><code>waybill.distance_actual</code> - фактический пробег (км)</li>
                                    </ul>

                                    <h6>Показания спидометра:</h6>
                                    <ul>
                                        <li><code>waybill.odometer_start</code> - показания при выезде</li>
                                        <li><code>waybill.odometer_end</code> - показания при возвращении</li>
                                    </ul>

                                    <h6>Топливо:</h6>
                                    <ul>
                                        <li><code>waybill.fuel_start</code> - остаток топлива при выезде</li>
                                        <li><code>waybill.fuel_end</code> - остаток топлива при возвращении</li>
                                        <li><code>waybill.fuel_issued</code> - выдано топлива</li>
                                        <li><code>waybill.fuel_consumption</code> - расход топлива</li>
                                    </ul>

                                    <h6>Временные параметры:</h6>
                                    <ul>
                                        <li><code>waybill.time_departure</code> - время выезда</li>
                                        <li><code>waybill.time_return</code> - время возвращения</li>
                                        <li><code>waybill.duration</code> - продолжительность рейса</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Примеры использования -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#usageExamples">
                                    Примеры использования
                                </button>
                            </h2>
                            <div id="usageExamples" class="accordion-collapse collapse" data-bs-parent="#templateInstructions">
                                <div class="accordion-body">
                                    <h6>Прямая подстановка (маппинг):</h6>
                                    <p>Укажите поле и ячейку, куда должно быть подставлено значение:</p>
                                    <ul>
                                        <li><code>upd.number → P1</code> - номер УПД в ячейку P1</li>
                                        <li><code>platform.name → B5</code> - название платформы в ячейку B5</li>
                                        <li><code>items.#.name → I15</code> - наименования позиций начиная с ячейки I15</li>
                                    </ul>

                                    <h6>Использование плейсхолдеров:</h6>
                                    <p>В любой ячейке Excel можно использовать конструкции:</p>
                                    <ul>
                                        <li><code>УПД №@{/{upd.number}} от @{/{upd.date}}</code></li>
                                        <li><code>Период: @{/{period}}</code></li>
                                        <li><code>Аренда @{/{items.#.name}} за период @{/{period}}</code></li>
                                    </ul>
                                    <p>При генерации документа плейсхолдеры будут автоматически заменены на значения.</p>

                                    <h6>Важные примечания:</h6>
                                    <ul>
                                        <li>Для табличной части обязательно используйте маппинг, а не плейсхолдеры</li>
                                        <li>Поля ИНН/КПП доступны как отдельно, так и в объединенном виде</li>
                                        <li>Все денежные значения автоматически форматируются</li>
                                        <li>Даты форматируются в формате ДД.ММ.ГГГГ</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    font-weight: 600;
}
.code-example {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    font-family: monospace;
    font-size: 0.9em;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Добавление нового поля
    document.getElementById('add-field').addEventListener('click', function() {
        const container = document.getElementById('field-mapping-container');
        const newField = document.createElement('div');
        newField.className = 'field-mapping-item mb-2';
        newField.innerHTML = `
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Поле данных (например: order.id)" name="field_names[]">
                <input type="text" class="form-control" placeholder="Ячейка (например: A1)" name="field_cells[]">
                <button type="button" class="btn btn-danger remove-field"><i class="bi bi-trash"></i></button>
            </div>
        `;
        container.appendChild(newField);

        // Добавляем обработчик для кнопки удаления
        newField.querySelector('.remove-field').addEventListener('click', function() {
            newField.remove();
        });
    });

    // Обработчики для кнопок удаления существующих полей
    document.querySelectorAll('.remove-field').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.field-mapping-item').remove();
        });
    });

    // Подготовка данных перед отправкой формы
    document.getElementById('template-form').addEventListener('submit', function(e) {
        const mapping = {};
        const fieldNames = document.getElementsByName('field_names[]');
        const fieldCells = document.getElementsByName('field_cells[]');

        for (let i = 0; i < fieldNames.length; i++) {
            if (fieldNames[i].value && fieldCells[i].value) {
                mapping[fieldNames[i].value] = fieldCells[i].value;
            }
        }

        document.getElementById('mapping-data').value = JSON.stringify(mapping);
    });
});
</script>
@endpush
