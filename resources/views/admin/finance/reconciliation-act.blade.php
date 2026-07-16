@extends('layouts.app')

@section('title', 'Акт сверки — ' . $company->legal_name)

@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.finance.lessee-debts') }}">Долги арендаторов</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.finance.company-detail', $company) }}">{{ $company->legal_name }}</a></li>
            <li class="breadcrumb-item active">Акт сверки</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Акт сверки: {{ $company->legal_name }}</h4>
            <div>
                <span class="me-3">ИНН: {{ $company->inn }}</span>
                <a href="#" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Печать
                </a>
            </div>
        </div>
    </div>

    @if($finalBalance >= 0)
        <div class="alert alert-danger">
            <strong>Сальдо: {{ number_format($finalBalance, 2) }} ₽</strong> — компания должна платформе
        </div>
    @else
        <div class="alert alert-warning">
            <strong>Сальдо: {{ number_format($finalBalance, 2) }} ₽</strong> — платформа должна компании
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Детализация операций</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Дата</th>
                            <th>Номер документа</th>
                            <th>Основание</th>
                            <th class="text-end">Начисление</th>
                            <th class="text-end">Оплата</th>
                            <th class="text-end">Сальдо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr class="{{ $entry->type === 'adjustment' ? 'table-info' : '' }}">
                            <td>{{ $entry->date instanceof \Carbon\Carbon ? $entry->date->format('d.m.Y') : (is_string($entry->date) ? $entry->date : date('d.m.Y', strtotime($entry->date))) }}</td>
                            <td>{{ $entry->number }}</td>
                            <td>{{ Str::limit($entry->description, 60) }}</td>
                            <td class="text-end {{ $entry->type === 'accrual' ? 'text-danger fw-bold' : '' }}">
                                {{ $entry->type === 'accrual' ? number_format($entry->accrual, 2) . ' ₽' : '' }}
                            </td>
                            <td class="text-end {{ $entry->payment > 0 ? 'text-success' : '' }}">
                                {{ $entry->payment > 0 ? number_format($entry->payment, 2) . ' ₽' : '' }}
                            </td>
                            <td class="text-end fw-bold {{ $entry->balance >= 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($entry->balance, 2) }} ₽
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Нет операций</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <th colspan="5" class="text-end">Итоговое сальдо:</th>
                            <th class="text-end {{ $finalBalance >= 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($finalBalance, 2) }} ₽
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar-container, .navbar, .btn, nav[aria-label="breadcrumb"] { display: none !important; }
    .container-fluid { margin-left: 0 !important; }
    .card { border: 1px solid #ccc !important; }
    .table { font-size: 10pt; }
}
</style>
@endsection
