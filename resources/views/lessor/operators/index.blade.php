@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Управление операторами</h1>
        <a href="{{ route('lessor.operators.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Добавить оператора
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Лицензия</th>
                        <th>Оборудование</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operators as $operator)
                    <tr>
                        <td>{{ $operator->full_name }}</td>
                        <td>{{ $operator->phone }}</td>
                        <td>{{ $operator->license_number }}</td>
                        <td>
                            @if($operator->equipment)
                                {{ $operator->equipment->title }}
                            @else
                                <span class="text-muted">Не назначено</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $operator->is_active ? 'success' : 'secondary' }}">
                                {{ $operator->is_active ? 'Активен' : 'Неактивен' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('lessor.operators.edit', $operator) }}"
                               class="btn btn-sm btn-outline-primary">
                               <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('lessor.operators.destroy', $operator) }}"
                                  method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Удалить оператора?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            Операторы не найдены
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($operators->hasPages())
            <div class="card-footer">
                {{ $operators->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
