@extends('layouts.app')

@section('title', 'Долги перед арендодателями')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>Задолженность платформы перед арендодателями</h2>
        </div>
    </div>

    <!-- Итоговая строка -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Общая сумма к выплате</h5>
                    <p class="card-text display-6">{{ number_format($totals['total_debt'], 2) }} ₽</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Всего по входящим УПД</h5>
                    <p class="card-text display-6">{{ number_format($totals['total_accrued'], 2) }} ₽</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Просроченные выплаты</h5>
                    <p class="card-text display-6">{{ number_format($totals['overdue_debt'], 2) }} ₽</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Поиск (название, ИНН)" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Применить</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.finance.lessor-debts') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Арендодатели</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Компания</th>
                            <th>ИНН</th>
                            <th>Начислено по УПД</th>
                            <th>Выплачено</th>
                            <th>Корректировки</th>
                            <th>К выплате</th>
                            <th>Просрочено</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lessors as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td><strong>{{ $item->legal_name }}</strong></td>
                            <td>{{ $item->inn }}</td>
                            <td class="text-end">{{ number_format($item->total_accrued, 2) }} ₽</td>
                            <td class="text-end text-success">{{ number_format($item->total_paid, 2) }} ₽</td>
                            <td class="text-end">{{ number_format($item->total_adjustments, 2) }} ₽</td>
                            <td class="fw-bold text-end">{{ number_format($item->total_debt, 2) }} ₽</td>
                            <td>
                                @if($item->overdue_debt > 0)
                                    <span class="text-danger fw-bold">{{ number_format($item->overdue_debt, 2) }} ₽</span>
                                @else
                                    <span class="text-success">Нет</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.finance.company-detail', $item->company) }}" class="btn btn-sm btn-info" title="Детализация">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.finance.reconciliation-act', $item->company) }}" class="btn btn-sm btn-outline-primary" title="Акт сверки">
                                    <i class="bi bi-file-text"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">Нет данных</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
