@php
use App\Models\Company;
use App\Models\DocumentTemplate;

$platformCompany = Company::where('is_platform', true)->first();
$updTemplate = DocumentTemplate::where('type', 'упд')->where('is_active', true)->first();

// Определяем тип УПД на основе сравнения компаний
$isIncomingUpd = $upd->lessor_company_id !== $platformCompany->id;
@endphp
@extends('layouts.app')

@section('content')


<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>УПД №{{ $upd->number }}</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.upds.index') }}" class="btn btn-secondary">← Назад к списку</a>

            @if($upd->status == 'pending')
                <form action="{{ route('admin.upds.accept', $upd) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Принять</button>
                </form>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">Отклонить</button>
            @endif

            @if($upd->file_path)
                <a href="{{ Storage::url($upd->file_path) }}" class="btn btn-info" target="_blank">Скачать файл</a>
            @endif

            <!-- Новая кнопка для генерации УПД из шаблона -->
            @if($updTemplate && !$isIncomingUpd)
                <a href="{{ route('admin.upds.generate-from-template', $upd) }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Сгенерировать УПД
                </a>
            @endif
        </div>
    </div>

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

    <!-- Остальная часть шаблона без изменений -->
    <!-- Табличная часть УПД -->
    @if($upd->items && count($upd->items) > 0)
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
                                @foreach($upd->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
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
