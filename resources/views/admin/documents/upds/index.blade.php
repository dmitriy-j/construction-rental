@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Управление УПД</h1>
        </div>
        <div class="col-md-6 text-right">
            <div class="btn-group">
                <a href="{{ route('admin.upds.index', ['status' => 'pending']) }}" class="btn btn-outline-primary {{ request('status') == 'pending' ? 'active' : '' }}">Ожидающие</a>
                <a href="{{ route('admin.upds.index', ['status' => 'accepted']) }}" class="btn btn-outline-success {{ request('status') == 'accepted' ? 'active' : '' }}">Принятые</a>
                <a href="{{ route('admin.upds.index', ['status' => 'rejected']) }}" class="btn btn-outline-danger {{ request('status') == 'rejected' ? 'active' : '' }}">Отклоненные</a>
                <a href="{{ route('admin.upds.index') }}" class="btn btn-outline-secondary">Все</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Дата</th>
                            <th>Арендодатель</th>
                            <th>Арендатор</th>
                            <th>Заказ</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upds as $upd)
                            <tr>
                                <td>{{ $upd->number }}</td>
                                <td>{{ $upd->issue_date->format('d.m.Y') }}</td>
                                <td>{{ $upd->lessorCompany->legal_name }}</td>
                                <td>{{ $upd->lesseeCompany->legal_name }}</td>
                                <td>#{{ $upd->order_id }}</td>
                                <td>{{ number_format($upd->total_amount, 2) }} ₽</td>
                                <td>
                                    <span class="badge badge-{{ $upd->status == 'pending' ? 'warning' : ($upd->status == 'accepted' ? 'success' : 'danger') }}">
                                        {{ $upd->status == 'pending' ? 'Ожидает' : ($upd->status == 'accepted' ? 'Принят' : 'Отклонен') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.upds.show', $upd) }}" class="btn btn-sm btn-info">Просмотр</a>
                                    @if($upd->status == 'pending')
                                        <form action="{{ route('admin.upds.accept', $upd) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Принять</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal{{ $upd->id }}">Отклонить</button>
                                    @endif
                                </td>
                            </tr>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal{{ $upd->id }}" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel{{ $upd->id }}" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.upds.reject', $upd) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="rejectModalLabel{{ $upd->id }}">Отклонение УПД</h5>
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
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">УПД не найдены</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $upds->links() }}
        </div>
    </div>
</div>
@endsection
