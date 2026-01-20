@php
use App\Models\Company;
use App\Models\DocumentTemplate;

$platformCompany = Company::where('is_platform', true)->first();
$updTemplate = DocumentTemplate::where('type', 'упд')->where('is_active', true)->first();

// Определяем тип УПД на основе сравнения компаний
$isIncomingUpd = $upd->lessor_company_id !== $platformCompany->id;

// Получаем счета связанные с этим УПД
$updInvoices = $upd->invoices ?? collect();

// ПОЛУЧАЕМ ГОС. НОМЕР ТЕХНИКИ
$licensePlate = null;
if ($upd->order) {
    // Пробуем получить гос. номер из путевых листов
    $waybill = $upd->order->waybills()->first();
    if ($waybill && $waybill->license_plate) {
        $licensePlate = $waybill->license_plate;
    }

    // Если в путевых листах нет, пробуем получить из оборудования
    if (!$licensePlate && $upd->order->items()->exists()) {
        $orderItem = $upd->order->items()->first();
        if ($orderItem && $orderItem->equipment) {
            $licensePlate = $orderItem->equipment->license_plate;
        }
    }

    // Если все еще нет, пробуем получить из самого заказа
    if (!$licensePlate && $upd->order->equipment) {
        $licensePlate = $upd->order->equipment->license_plate;
    }
}

// Формируем описание услуги с гос. номером
$serviceDescription = "Аренда ";
if ($upd->order && $upd->order->equipment) {
    $serviceDescription .= $upd->order->equipment->title . " " . ($upd->order->equipment->model ?? '');
    if ($licensePlate) {
        $serviceDescription .= " (гос. номер: {$licensePlate})";
    }
} else {
    $serviceDescription .= "техники";
}
$serviceDescription .= " за период " . $upd->service_period_start->format('d.m.Y') . " - " . $upd->service_period_end->format('d.m.Y');

@endphp
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>УПД №{{ $upd->number }}</h1>
            <p class="text-muted">{{ $serviceDescription }}</p>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.upds.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>

            @if($upd->status == 'pending')
                <form action="{{ route('admin.upds.accept', $upd) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Принять
                    </button>
                </form>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">
                    <i class="fas fa-times"></i> Отклонить
                </button>
            @endif

            <!-- Упрощенные кнопки скачивания -->
            <div class="btn-group" role="group">
                <!-- Для загруженных файлов (входящие УПД) -->
                @if($upd->file_path && $isIncomingUpd)
                    <a href="{{ route('admin.upds.download', $upd) }}" class="btn btn-info">
                        <i class="fas fa-download"></i> Скачать загруженный УПД
                    </a>
                @endif

                <!-- Для генерации исходящих УПД -->
                @if(!$isIncomingUpd || !$upd->file_path)
                    <a href="{{ route('admin.upds.download-generated', $upd) }}" class="btn btn-primary">
                        <i class="fas fa-file-download"></i> Сгенерировать и скачать УПД
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Блок с гос. номером -->
    @if($licensePlate)
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-car"></i>
                <strong>Техника:</strong>
                @if($upd->order && $upd->order->equipment)
                    {{ $upd->order->equipment->title }}
                    ({{ $upd->order->equipment->model ?? 'без модели' }})
                @else
                    Техника
                @endif
                | <strong>Гос. номер:</strong> {{ $licensePlate }}
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Информация о документе</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Номер УПД:</th>
                            <td>{{ $upd->number }}</td>
                        </tr>
                        <tr>
                            <th>Дата составления:</th>
                            <td>{{ $upd->issue_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Период оказания услуг:</th>
                            <td>{{ $upd->service_period_start->format('d.m.Y') }} - {{ $upd->service_period_end->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <span class="badge badge-{{ $upd->status_color }}" data-toggle="tooltip" title="{{ $upd->getStatusDescription() }}">
                                    {{ $upd->status_text }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Тип УПД:</th>
                            <td>
                                <span class="badge badge-{{ $isIncomingUpd ? 'info' : 'warning' }}">
                                    {{ $isIncomingUpd ? 'Входящий (Арендодатель → Платформа)' : 'Исходящий (Платформа → Арендатор)' }}
                                </span>
                            </td>
                        </tr>
                        @if($licensePlate)
                        <tr>
                            <th>Гос. номер техники:</th>
                            <td>{{ $licensePlate }}</td>
                        </tr>
                        @endif
                        @if($upd->rejection_reason)
                        <tr>
                            <th>Причина отклонения:</th>
                            <td>{{ $upd->rejection_reason }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Финансовая информация</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Сумма без НДС:</th>
                            <td>{{ number_format($upd->amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Сумма НДС:</th>
                            <td>{{ number_format($upd->tax_amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Итого с НДС:</th>
                            <td>{{ number_format($upd->total_amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Система налогообложения:</th>
                            <td>{{ $upd->tax_system }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Секция счетов -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Счета к УПД</h5>
                </div>
                <div class="card-body">
                    @php
                        $updInvoices = $upd->invoices ?? collect();
                        $invoiceTemplate = \App\Models\DocumentTemplate::where('type', 'invoice')
                            ->where('is_active', true)
                            ->whereIn('scenario', [
                                \App\Models\DocumentTemplate::INVOICE_SCENARIO_POSTPAYMENT_UPD,
                                \App\Models\DocumentTemplate::SCENARIO_INVOICE_UPD,
                                'postpayment_upd',
                                'invoice_upd'
                            ])
                            ->first();

                        // Если все еще не нашли, берем любой активный шаблон счета
                        if (!$invoiceTemplate) {
                            $invoiceTemplate = \App\Models\DocumentTemplate::where('type', 'invoice')
                                ->where('is_active', true)
                                ->first();
                        }

                        $canCreateInvoice = $upd->canCreateInvoice() && $upd->isOutgoing() && $invoiceTemplate;
                        @endphp

                    @if($updInvoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Номер счета</th>
                                        <th>Тип</th>
                                        <th>Дата</th>
                                        <th>Сумма</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($updInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->number }}</td>
                                        <td>
                                            <span class="badge badge-info">Постоплата к УПД</span>
                                        </td>
                                        <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
                                        <td>{{ number_format($invoice->amount, 2) }} ₽</td>
                                        <td>
                                            <span class="badge badge-{{ $invoice->getStatusColor() }}">
                                                {{ $invoice->getStatusText() }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.invoices.show', $invoice) }}"
                                            class="btn btn-sm btn-info" title="Просмотр">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($invoice->file_path)
                                            <a href="{{ route('admin.invoices.download', $invoice) }}"
                                            class="btn btn-sm btn-success" title="Скачать">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> К этому УПД еще не созданы счета
                        </div>
                    @endif

                    <!-- Кнопки создания счетов -->
                    @if($canCreateInvoice && $invoiceTemplate)
                    <div class="mt-3">
                        <form action="{{ route('admin.invoices.create-for-upd', $upd) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success"
                                    onclick="return confirm('Вы уверены, что хотите создать постоплатный счет для этого УПД?')">
                                <i class="fas fa-file-invoice-dollar"></i> Выставить постоплатный счет
                            </button>
                        </form>

                        @if($upd->order && in_array($upd->order->status, ['pending', 'active']))
                        <form action="{{ route('admin.invoices.create-for-order', $upd->order) }}" method="POST" class="d-inline ml-2">
                            @csrf
                            <button type="submit" class="btn btn-warning"
                                    onclick="return confirm('Вы уверены, что хотите создать предоплатный счет для заказа?')">
                                <i class="fas fa-file-invoice"></i> Выставить предоплатный счет
                            </button>
                        </form>
                        @endif
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        @if(!$canCreateInvoice)
                            @if(!$upd->isOutgoing())
                                Счета можно создавать только для исходящих УПД (Платформа → Арендатор)
                            @else
                                Счет можно создать для УПД в статусах: ожидание, отправлен, принят, проведен
                            @endif
                        @elseif(!$invoiceTemplate)
                            Не найден активный шаблон счета для УПД
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>
                        @if($isIncomingUpd)
                            Продавец (Арендодатель)
                        @else
                            Продавец (Платформа)
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $sellerCompany = $isIncomingUpd ? $upd->lessorCompany : $platformCompany;
                    @endphp

                    @if($sellerCompany)
                    <table class="table table-sm">
                        <tr>
                            <th>Название:</th>
                            <td>{{ $sellerCompany->legal_name }}</td>
                        </tr>
                        <tr>
                            <th>ИНН:</th>
                            <td>{{ $sellerCompany->inn }}</td>
                        </tr>
                        <tr>
                            <th>КПП:</th>
                            <td>{{ $sellerCompany->kpp }}</td>
                        </tr>
                        <tr>
                            <th>ОГРН:</th>
                            <td>{{ $sellerCompany->ogrn }}</td>
                        </tr>
                        <tr>
                            <th>Адрес:</th>
                            <td>{{ $sellerCompany->legal_address }}</td>
                        </tr>
                    </table>
                    @else
                    <div class="alert alert-warning">
                        Информация о продавце не найдена
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>
                        @if($isIncomingUpd)
                            Покупатель (Платформа)
                        @else
                            Покупатель (Арендатор)
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $buyerCompany = $isIncomingUpd ? $platformCompany : $upd->lesseeCompany;
                    @endphp

                    @if($buyerCompany)
                    <table class="table table-sm">
                        <tr>
                            <th>Название:</th>
                            <td>{{ $buyerCompany->legal_name }}</td>
                        </tr>
                        <tr>
                            <th>ИНН:</th>
                            <td>{{ $buyerCompany->inn }}</td>
                        </tr>
                        <tr>
                            <th>КПП:</th>
                            <td>{{ $buyerCompany->kpp }}</td>
                        </tr>
                        <tr>
                            <th>ОГРН:</th>
                            <td>{{ $buyerCompany->ogrn }}</td>
                        </tr>
                        <tr>
                            <th>Адрес:</th>
                            <td>{{ $buyerCompany->legal_address }}</td>
                        </tr>
                    </table>
                    @else
                    <div class="alert alert-warning">
                        Информация о покупателе не найдена
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

        <!-- Табличная часть УПД -->
        @if($preparedItems && count($preparedItems) > 0)
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Табличная часть УПД</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Наименование</th>
                                        <th>Количество</th>
                                        <th>Единица измерения</th>
                                        <th>Цена</th>
                                        <th>Сумма</th>
                                        <th>Ставка НДС</th>
                                        <th>Сумма НДС</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preparedItems as $item)
                                        <tr>
                                            <td>
                                                {{ $item->full_name }}
                                                @if(empty($equipmentData['vehicle_number']))
                                                    <br><small class="text-warning">Гос. номер не указан</small>
                                                @endif
                                            </td>
                                            <td>{{ number_format($item->quantity, 2) }}</td>
                                            <td>{{ $item->unit }}</td>
                                            <td>{{ number_format($item->price, 2) }} ₽</td>
                                            <td>{{ number_format($item->amount, 2) }} ₽</td>
                                            <td>{{ number_format($item->vat_rate, 0) }}%</td>
                                            <td>{{ number_format($item->vat_amount, 2) }} ₽</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Итого:</th>
                                        <th>{{ number_format($upd->amount, 2) }} ₽</th>
                                        <th></th>
                                        <th>{{ number_format($upd->tax_amount, 2) }} ₽</th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-right">Всего с НДС:</th>
                                        <th colspan="3">{{ number_format($upd->total_amount, 2) }} ₽</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.upds.reject', $upd) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Отклонение УПД</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Причина отклонения</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Подтвердить отклонение</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.badge-warning {
    color: #000 !important;
    background-color: #ffc107 !important;
}

.badge-success {
    color: #000 !important;
    background-color: #28a745 !important;
}

.badge-danger {
    color: #000 !important;
    background-color: #dc3545 !important;
}

.badge-info {
    color: #000 !important;
    background-color: #17a2b8 !important;
}

.badge-secondary {
    color: #000 !important;
    background-color: #6c757d !important;
}
</style>
@endsection
