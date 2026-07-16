@extends('layouts.app')

@section('title', 'История корректировок баланса')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>История корректировок баланса</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.finance.adjustment-create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Новая корректировка
            </a>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="company_id" class="form-select">
                        <option value="">Все компании</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->legal_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Фильтр</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.finance.balance-adjustments') }}" class="btn btn-outline-secondary w-100">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Все корректировки</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Дата</th>
                            <th>Компания</th>
                            <th>Тип</th>
                            <th>Сумма</th>
                            <th>Комментарий</th>
                            <th>Администратор</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adj)
                        <tr>
                            <td>{{ $adj->id }}</td>
                            <td>{{ $adj->created_at->format('d.m.Y H:i') }}</td>
                            <td><strong>{{ $adj->company->legal_name ?? 'N/A' }}</strong></td>
                            <td>
                                <span class="badge bg-{{ $adj->type === 'credit' ? 'danger' : 'success' }}">
                                    {{ $adj->type_label }}
                                </span>
                            </td>
                            <td class="fw-bold">{{ number_format($adj->amount, 2) }} ₽</td>
                            <td>{{ Str::limit($adj->comment, 50) }}</td>
                            <td>{{ $adj->admin->name ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">Нет данных</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $adjustments->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
