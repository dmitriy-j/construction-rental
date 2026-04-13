@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Управление договорами</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.documents.index', ['type' => 'contracts']) }}" class="btn btn-secondary mr-2">
                <i class="fas fa-list"></i> К общему списку документов
            </a>
            <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить договор
            </a>
        </div>
    </div>

    <!-- Статистика и фильтры -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <a href="{{ route('admin.contracts.index', ['type' => 'all']) }}"
                               class="text-decoration-none {{ $type == 'all' ? 'text-primary' : 'text-muted' }}">
                                <h4>{{ $stats['all'] }}</h4>
                                <p>Все договоры</p>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.contracts.index', ['type' => 'lessors']) }}"
                               class="text-decoration-none {{ $type == 'lessors' ? 'text-primary' : 'text-muted' }}">
                                <h4>{{ $stats['lessors'] }}</h4>
                                <p>С арендодателями</p>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.contracts.index', ['type' => 'lessees']) }}"
                               class="text-decoration-none {{ $type == 'lessees' ? 'text-primary' : 'text-muted' }}">
                                <h4>{{ $stats['lessees'] }}</h4>
                                <p>С арендаторами</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Тип договора</th>
                            <th>Контрагент</th>
                            <th>Дата начала</th>
                            <th>Дата окончания</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $contract)
                            <tr>
                                <td>
                                    <strong>{{ $contract->number }}</strong>
                                    @if($contract->file_path)
                                        <i class="fas fa-file-pdf text-danger ml-1" title="Есть файл договора"></i>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $contract->isWithLessor() ? 'info' : 'warning' }}">
                                        {{ $contract->counterparty_type_label }}
                                    </span>
                                </td>
                                <td>{{ $contract->counterpartyCompany->legal_name }}</td>
                                <td>{{ $contract->start_date->format('d.m.Y') }}</td>
                                <td>{{ $contract->end_date->format('d.m.Y') }}</td>
                                <td>
                                    <span class="badge badge-{{ $contract->is_active ? 'success' : 'secondary' }}">
                                        {{ $contract->is_active ? 'Активен' : 'Неактивен' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.contracts.show', $contract) }}"
                                           class="btn btn-info" title="Просмотр">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.contracts.edit', $contract) }}"
                                           class="btn btn-warning" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($contract->file_path)
                                        <a href="{{ route('admin.contracts.download', $contract) }}"
                                           class="btn btn-success" title="Скачать файл">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @endif
                                        <form action="{{ route('admin.contracts.destroy', $contract) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    title="Удалить"
                                                    onclick="return confirm('Вы уверены, что хотите удалить этот договор?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-2"></i><br>
                                    Договоры не найдены
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($contracts->hasPages())
                <div class="mt-3">
                    {{ $contracts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
