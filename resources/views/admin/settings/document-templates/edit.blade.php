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
                                    <label for="scenario" class="form-label">Сценарий *</label>
                                    <select class="form-select" id="scenario" name="scenario" required>
                                        <option value="">Выберите сценарий</option>
                                        @foreach($scenarios as $key => $value)
                                            <option value="{{ $key }}" {{ ($documentTemplate->scenario ?? '') == $key ? 'selected' : '' }}>{{ $value }}</option>
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
                            <div class="alert alert-info mb-3">
                                <strong>Для табличной части (позиции УПД, товары в счетах):</strong><br>
                                Укажите начальные ячейки для каждого столбца, например:<br>
                                <code>items.#.name → B15</code>, <code>items.#.quantity → C15</code>, <code>items.#.price → D15</code><br>
                                Система автоматически заполнит таблицу, начиная с указанных ячеек.
                            </div>
                            <div id="field-mapping-container">
                                <div class="field-mapping-item mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Поле данных (например: order.id)" name="field_names[]">
                                        <input type="text" class="form-control" placeholder="Ячейка (например: A1)" name="field_cells[]">
                                        <button type="button" class="btn btn-danger remove-field"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
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

                                    <h6>Данные продавца (Платформа):</h6>
                                    <ul>
                                        <li><code>seller.name</code> - название</li>
                                        <li><code>seller.legal_name</code> - юридическое название</li>
                                        <li><code>seller.address</code> - адрес</li>
                                        <li><code>seller.inn</code> - ИНН</li>
                                        <li><code>seller.kpp</code> - КПП</li>
                                        <li><code>seller.inn_kpp</code> - ИНН/КПП (формат "1234567890/123456789")</li>
                                        <li><code>seller.bank_name</code> - банк</li>
                                        <li><code>seller.bik</code> - БИК</li>
                                        <li><code>seller.account_number</code> - расчетный счет</li>
                                        <li><code>seller.correspondent_account</code> - корр. счет</li>
                                    </ul>

                                    <h6>Данные покупателя (Арендатор):</h6>
                                    <ul>
                                        <li><code>buyer.name</code> - название</li>
                                        <li><code>buyer.legal_name</code> - юридическое название</li>
                                        <li><code>buyer.address</code> - адрес</li>
                                        <li><code>buyer.inn</code> - ИНН</li>
                                        <li><code>buyer.kpp</code> - КПП</li>
                                        <li><code>buyer.inn_kpp</code> - ИНН/КПП (формат "1234567890/123456789")</li>
                                        <li><code>buyer.bank_name</code> - банк</li>
                                        <li><code>buyer.bik</code> - БИК</li>
                                        <li><code>buyer.account_number</code> - расчетный счет</li>
                                        <li><code>buyer.correspondent_account</code> - корр. счет</li>
                                    </ul>

                                    <h6>Данные платформы (для обратной совместимости):</h6>
                                    <ul>
                                        <li><code>platform.name</code> - название</li>
                                        <li><code>platform.legal_name</code> - юридическое название</li>
                                        <li><code>platform.address</code> - адрес</li>
                                        <li><code>platform.inn</code> - ИНН</li>
                                        <li><code>platform.kpp</code> - КПП</li>
                                        <li><code>platform.inn_kpp</code> - ИНН/КПП</li>
                                        <li><code>platform.bank_name</code> - банк</li>
                                        <li><code>platform.bik</code> - БИК</li>
                                        <li><code>platform.account_number</code> - расчетный счет</li>
                                        <li><code>platform.correspondent_account</code> - корр. счет</li>
                                    </ul>

                                    <h6>Данные арендатора (для обратной совместимости):</h6>
                                    <ul>
                                        <li><code>lessee.name</code> - название</li>
                                        <li><code>lessee.legal_name</code> - юридическое название</li>
                                        <li><code>lessee.address</code> - адрес</li>
                                        <li><code>lessee.inn</code> - ИНН</li>
                                        <li><code>lessee.kpp</code> - КПП</li>
                                        <li><code>lessee.inn_kpp</code> - ИНН/КПП</li>
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
                                        <li><code>items.#.total_without_vat</code> - сумма без НДС (дублирует amount)</li>
                                        <li><code>items.#.total</code> - общая сумма с НДС (дублирует total_with_vat)</li>
                                        <li><code>items.#.period</code> - период оказания услуг</li>
                                        <li><code>items.#.index</code> - порядковый номер позиции</li>
                                    </ul>

                                    <div class="alert alert-info mt-3">
                                        <strong>Важно:</strong> Для исходящих УПД (Платформа → Арендатор) используйте поля <code>seller</code> и <code>buyer</code>.
                                        Поля <code>platform</code> и <code>lessee</code> оставлены для обратной совместимости.
                                    </div>

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
                                    <h6>Настройка табличной части (обязательно через "Настройку полей"):</h6>
                                    <p>Для позиций УПД укажите начальные ячейки для каждого столбца:</p>
                                    <div class="code-example mb-3">
                                        items.#.code → B15<br>
                                        items.#.name → C15<br>
                                        items.#.unit → D15<br>
                                        items.#.quantity → E15<br>
                                        items.#.price → F15<br>
                                        items.#.amount → G15<br>
                                        items.#.vat_rate → H15<br>
                                        items.#.vat_amount → I15<br>
                                        items.#.total_with_vat → J15
                                    </div>
                                    <p>Система автоматически заполнит таблицу, начиная с указанных ячеек.</p>

                                    <h6>Прямая подстановка для одиночных значений:</h6>
                                    <p>Укажите поле и ячейку для подстановки:</p>
                                    <ul>
                                        <li><code>upd.number → P1</code> - номер УПД в ячейку P1</li>
                                        <li><code>seller.name → B5</code> - название продавца в ячейку B5</li>
                                        <li><code>buyer.inn_kpp → C5</code> - ИНН/КПП покупателя в ячейку C5</li>
                                    </ul>

                                    <h6>Использование плейсхолдеров:</h6>
                                    <p>В любой ячейке Excel можно использовать конструкции:</p>
                                    <ul>
                                        <li><code>УПД №@{{upd.number}} от @{{upd.date}}</code></li>
                                        <li><code>Продавец: @{{seller.name}}, ИНН/КПП: @{{seller.inn_kpp}}</code></li>
                                        <li><code>Покупатель: @{{buyer.name}}, ИНН/КПП: @{{buyer.inn_kpp}}</code></li>
                                        <li><code>Период: @{{period}}</code></li>
                                    </ul>

                                    <h6>Особенности для табличной части:</h6>
                                    <ul>
                                        <li>Указывайте <strong>начальную ячейку</strong> для каждого столбца таблицы</li>
                                        <li>Система автоматически заполнит строки ниже начальной ячейки</li>
                                        <li>Для табличной части <strong>не используйте плейсхолдеры</strong> - только настройку полей</li>
                                        <li>Плейсхолдеры в названиях позиций (<code>@{{period}}</code>) автоматически заменяются</li>
                                    </ul>

                                    <div class="alert alert-info mt-3">
                                        <strong>Совет:</strong> Создайте в Excel таблицу с заголовками, затем в "Настройке полей" укажите
                                        начальные ячейки для каждого столбца данных. Система заполнит таблицу автоматически.
                                    </div>
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
    const container = document.getElementById('field-mapping-container');
    const mappingData = document.getElementById('mapping-data');
    const form = document.getElementById('template-form');

    // Функция для добавления нового поля
    function addNewField(fieldName = '', cell = '') {
        const newField = document.createElement('div');
        newField.className = 'field-mapping-item mb-2';
        newField.innerHTML = `
            <div class="input-group">
                <input type="text" class="form-control field-name" placeholder="Поле данных (например: items.#.name)" value="${fieldName}">
                <input type="text" class="form-control field-cell" placeholder="Ячейка (например: B15)" value="${cell}">
                <button type="button" class="btn btn-danger remove-field"><i class="bi bi-trash"></i></button>
            </div>
        `;
        container.appendChild(newField);

        // Обработчик удаления
        newField.querySelector('.remove-field').addEventListener('click', function() {
            newField.remove();
            updateMappingData();
        });

        // Обработчики изменения значений
        const inputs = newField.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', updateMappingData);
        });
    }

    // Функция обновления скрытого поля с маппингом
    function updateMappingData() {
        const mapping = {};
        const fieldNames = container.querySelectorAll('.field-name');
        const fieldCells = container.querySelectorAll('.field-cell');

        for (let i = 0; i < fieldNames.length; i++) {
            const name = fieldNames[i].value.trim();
            const cell = fieldCells[i].value.trim();

            if (name && cell) {
                mapping[name] = cell;
            }
        }

        mappingData.value = JSON.stringify(mapping);
        console.log('Mapping updated:', mapping); // Для отладки
    }

    // Обработчик добавления поля
    document.getElementById('add-field').addEventListener('click', function() {
        addNewField();
    });

    // Обработчик отправки формы
    form.addEventListener('submit', function(e) {
        // Обновляем маппинг перед отправкой
        updateMappingData();

        // Проверяем, что маппинг не пустой
        if (mappingData.value === '{}') {
            e.preventDefault();
            alert('Добавьте хотя бы одно поле в настройку маппинга');
            return;
        }

        console.log('Form submitted with mapping:', mappingData.value); // Для отладки
    });

    // Инициализация существующих данных маппинга
    @if(isset($documentTemplate) && !empty($documentTemplate->mapping))
        const existingMapping = @json($documentTemplate->mapping);
        container.innerHTML = ''; // Очищаем контейнер

        Object.entries(existingMapping).forEach(([field, cell]) => {
            addNewField(field, cell);
        });
    @else
        // Добавляем одно пустое поле по умолчанию
        addNewField();
    @endif

    // Инициализация маппинга
    updateMappingData();
});
</script>
@endpush
