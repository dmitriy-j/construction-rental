@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h1>Просмотр УПД #{{ $upd->number }}</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('lessor.upds.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Основная информация</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Номер УПД:</strong> {{ $upd->number }}</p>
                    <p><strong>Дата УПД:</strong> {{ $upd->issue_date->format('d.m.Y') }}</p>
                    <p><strong>Дата загрузки:</strong> {{ $upd->created_at->format('d.m.Y H:i') }}</p>
                    <p><strong>Статус:</strong>
                        <span class="badge badge-{{ $upd->status === 'accepted' ? 'success' : ($upd->status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ $upd->status === 'pending' ? 'Ожидает проверки' : ($upd->status === 'accepted' ? 'Принят' : 'Отклонен') }}
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Сумма без НДС:</strong> {{ number_format($upd->amount, 2, ',', ' ') }} ₽</p>
                    <p><strong>Сумма НДС:</strong> {{ number_format($upd->tax_amount, 2, ',', ' ') }} ₽</p>
                    <p><strong>Общая сумма:</strong> {{ number_format($upd->total_amount, 2, ',', ' ') }} ₽</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Реквизиты</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Продавец (Арендодатель)</h6>
                    <p><strong>Наименование:</strong> {{ $upd->lessorCompany->legal_name }}</p>
                    <p><strong>ИНН:</strong> {{ $upd->lessorCompany->inn }}</p>
                    @if($upd->lessorCompany->kpp)
                        <p><strong>КПП:</strong> {{ $upd->lessorCompany->kpp }}</p>
                    @endif
                </div>
                <div class="col-md-6">
                    <h6>Покупатель</h6>
                    @php
                        $platformCompany = \App\Models\Company::where('is_platform', true)->first();
                    @endphp
                    <p><strong>Наименование:</strong> {{ $platformCompany->legal_name ?? 'Платформа' }}</p>
                    <p><strong>ИНН:</strong> {{ $platformCompany->inn ?? '' }}</p>
                    @if($platformCompany && $platformCompany->kpp)
                        <p><strong>КПП:</strong> {{ $platformCompany->kpp }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Связанные документы</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Заказ:</strong> #{{ $upd->order->id }}</p>
                    <p><strong>Арендатор:</strong> {{ $upd->order->lesseeCompany->legal_name }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Путевой лист:</strong>
                        @if($upd->waybill)
                            <a href="{{ route('lessor.waybills.show', $upd->waybill) }}">
                                #{{ $upd->waybill->id }}
                            </a>
                        @else
                            <span class="text-muted">Не привязан</span>
                        @endif
                    </p>
                    <p><strong>Акт выполненных работ:</strong>
                        @if($upd->waybill && $upd->waybill->completionAct)
                            <a href="{{ route('lessor.documents.completion_acts.show', $upd->waybill->completionAct) }}">
                                №{{ $upd->waybill->completionAct->number }}
                            </a>
                        @else
                            <span class="text-muted">Не привязан</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if($upd->items && count($upd->items) > 0)
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Позиции УПД</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Наименование</th>
                            <th class="text-center">Кол-во</th>
                            <th class="text-center">Ед.</th>
                            <th class="text-right">Цена</th>
                            <th class="text-right">Сумма</th>
                            <th class="text-center">НДС</th>
                            <th class="text-right">Сумма НДС</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upd->items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td class="text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                                <td class="text-center">{{ $item->unit }}</td>
                                <td class="text-right">{{ number_format($item->price, 2, ',', ' ') }} ₽</td>
                                <td class="text-right">{{ number_format($item->amount, 2, ',', ' ') }} ₽</td>
                                <td class="text-center">{{ number_format($item->vat_rate, 0, ',', ' ') }}%</td>
                                <td class="text-right">{{ number_format($item->vat_amount, 2, ',', ' ') }} ₽</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .card-header {
        padding: 0.5rem 1rem;
        background-color: #f8f9fa;
    }

    .card-body {
        padding: 1rem;
    }

    .table {
        margin-bottom: 0;
        font-size: 0.85rem;
    }

    .table th {
        border-top: none;
        font-weight: 600;
        padding: 0.5rem;
    }

    .table td {
        padding: 0.5rem;
        vertical-align: middle;
    }
</style>
@endsection
