@extends('layouts.app') {{-- Убедитесь, что используете правильный layout --}}

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Финансовые проводки</h3>

        {{-- Форма фильтрации --}}
        <form action="{{ route('admin.finance.transactions') }}" method="GET" class="form-inline mt-3">
            <div class="form-group mr-2">
                <label for="company_id" class="mr-2">Компания:</label>
                <select name="company_id" id="company_id" class="form-control form-control-sm">
                    <option value="">Все компании</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                            {{ $company->legal_name }} (ИНН: {{ $company->inn }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mr-2">
                <label for="type" class="mr-2">Тип:</label>
                <select name="type" id="type" class="form-control form-control-sm">
                    <option value="">Все типы</option>
                    <option value="debit" {{ request('type') == 'debit' ? 'selected' : '' }}>Дебит (Приход)</option>
                    <option value="credit" {{ request('type') == 'credit' ? 'selected' : '' }}>Кредит (Расход)</option>
                </select>
            </div>

            <div class="form-group mr-2">
                <label for="purpose" class="mr-2">Назначение:</label>
                <input type="text" name="purpose" id="purpose" class="form-control form-control-sm"
                       value="{{ request('purpose') }}" placeholder="Фильтр по назначению">
            </div>

            <button type="submit" class="btn btn-primary btn-sm mr-2">Фильтровать</button>
            <a href="{{ route('admin.finance.transactions') }}" class="btn btn-secondary btn-sm">Сбросить</a>
        </form>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Компания</th>
                        <th>Сумма</th>
                        <th>Тип</th>
                        <th>Назначение</th>
                        <th>Описание</th>
                        <th>Баланс</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->id }}</td>
                        <td>{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($transaction->company)
                                {{ $transaction->company->legal_name }}
                                <br><small class="text-muted">ИНН: {{ $transaction->company->inn }}</small>
                            @else
                                <span class="text-muted">Не указана</span>
                            @endif
                        </td>
                        <td>{{ number_format($transaction->amount, 2) }} руб.</td>
                        <td>
                            <span class="badge badge-{{ $transaction->type == 'debit' ? 'success' : 'danger' }}">
                                {{ $transaction->type == 'debit' ? 'Приход' : 'Расход' }}
                            </span>
                        </td>
                        <td>{{ $transaction->purpose }}</td>
                        <td>{{ Str::limit($transaction->description, 50) }}</td>
                        <td>{{ number_format($transaction->balance_snapshot, 2) }} руб.</td>
                        <td>
                            @if($transaction->is_canceled)
                                <span class="badge badge-danger">Отменена</span>
                            @else
                                <span class="badge badge-success">Активна</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Транзакции не найдены</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Пагинация --}}
        <div class="d-flex justify-content-center mt-3">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
