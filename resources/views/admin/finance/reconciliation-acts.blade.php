@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Акты сверки</h1>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Создать новый акт</h5>
        </div>
        <div class="card-body">
            <a href="{{ route('admin.reconciliation-acts.create') }}" class="btn btn-primary">
                Создать акт сверки
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>История актов сверки</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Компания</th>
                            <th>Период</th>
                            <th>Начальный баланс</th>
                            <th>Конечный баланс</th>
                            <th>Статус</th>
                            <th>Создан</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($acts as $act)
                            <tr>
                                <td>{{ $act->id }}</td>
                                <td>{{ $act->company->legal_name }}</td>
                                <td>{{ $act->period_start->format('d.m.Y') }} - {{ $act->period_end->format('d.m.Y') }}</td>
                                <td>{{ number_format($act->starting_balance, 2) }} ₽</td>
                                <td>{{ number_format($act->ending_balance, 2) }} ₽</td>
                                <td>
                                    <span class="badge badge-{{ $act->isFullyConfirmed() ? 'success' : 'warning' }}">
                                        {{ $act->isFullyConfirmed() ? 'Подтвержден' : 'Ожидает подтверждения' }}
                                    </span>
                                </td>
                                <td>{{ $act->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.reconciliation-acts.show', $act) }}" class="btn btn-sm btn-info">Просмотр</a>
                                    @if($act->file_path)
                                        <a href="{{ route('admin.reconciliation-acts.download', $act) }}" class="btn btn-sm btn-success">Скачать</a>
                                    @endif
                                    <form action="{{ route('admin.reconciliation-acts.destroy', $act) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить акт сверки?')">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Актов сверки не найдено</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $acts->links() }}
        </div>
    </div>
</div>
@endsection
