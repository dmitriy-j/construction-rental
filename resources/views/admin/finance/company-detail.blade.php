@extends('layouts.app')

@section('title', $company->legal_name)

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.finance.lessee-debts') }}">Долги арендаторов</a></li>
            <li class="breadcrumb-item active">{{ $company->legal_name }}</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-md-8">
            <h2>{{ $company->legal_name }}</h2>
            <p>ИНН: {{ $company->inn }} | Телефон: {{ $company->phone }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.finance.reconciliation-act', $company) }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-file-text"></i> Акт сверки
            </a>
            <a href="{{ route('admin.finance.adjustment-create') }}" class="btn btn-primary">
                <i class="bi bi-pencil-square"></i> Корректировка баланса
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">История корректировок баланса</h5>
                </div>
                <div class="card-body">
                    @if($adjustments->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Тип</th>
                                        <th>Сумма</th>
                                        <th>Комментарий</th>
                                        <th>Администратор</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($adjustments as $adj)
                                    <tr>
                                        <td>{{ $adj->created_at->format('d.m.Y H:i') }}</td>
                                        <td><span class="badge bg-{{ $adj->type === 'credit' ? 'danger' : 'success' }}">{{ $adj->type_label }}</span></td>
                                        <td class="fw-bold">{{ number_format($adj->amount, 2) }} ₽</td>
                                        <td>{{ $adj->comment }}</td>
                                        <td>{{ $adj->admin->name ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="2">Итого корректировок:</td>
                                        <td class="{{ $totalAdjustments >= 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $totalAdjustments >= 0 ? '+' : '' }}{{ number_format($totalAdjustments, 2) }} ₽
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">Корректировок не было</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">УПД компании</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Номер</th>
                            <th>Дата</th>
                            <th>Тип</th>
                            <th>Контрагент</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upds as $upd)
                        <tr>
                            <td>{{ $upd->id }}</td>
                            <td>{{ $upd->number }}</td>
                            <td>{{ $upd->issue_date instanceof \Carbon\Carbon ? $upd->issue_date->format('d.m.Y') : $upd->issue_date }}</td>
                            <td>
                                @if($upd->lessee_company_id === $company->id)
                                    <span class="badge bg-danger">Исходящий (начисление)</span>
                                @else
                                    <span class="badge bg-warning">Входящий (поставка)</span>
                                @endif
                            </td>
                            <td>
                                @if($upd->lessee_company_id === $company->id)
                                    {{ $upd->lessorCompany->legal_name ?? 'Платформа' }}
                                @else
                                    {{ $upd->lesseeCompany->legal_name ?? 'Арендатор' }}
                                @endif
                            </td>
                            <td>{{ number_format($upd->total_amount, 2) }} ₽</td>
                            <td><span class="badge bg-{{ $upd->status_color }}">{{ $upd->status_text }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $upds->links() }}</div>
        </div>
    </div>
</div>
@endsection
