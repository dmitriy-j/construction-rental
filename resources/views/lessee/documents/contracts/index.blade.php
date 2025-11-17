@extends('layouts.app')

@section('title', 'Мои договоры')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title mb-0">Договоры</h4>
                {{-- Кнопка "Назад к документам" перемещена вправо --}}
                <a href="{{ url('/lessee/documents?type=contracts') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Назад к документам
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($contracts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead>
                                <tr>
                                    <th>Номер договора</th>
                                    <th>Платформа</th>
                                    <th>Дата начала</th>
                                    <th>Дата окончания</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contracts as $contract)
                                <tr>
                                    <td>
                                        <strong>{{ $contract->number }}</strong>
                                    </td>
                                    <td>{{ $contract->platformCompany->legal_name ?? 'Платформа' }}</td>
                                    <td>{{ $contract->start_date->format('d.m.Y') }}</td>
                                    <td>{{ $contract->end_date->format('d.m.Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $contract->is_active ? 'success' : 'secondary' }}">
                                            {{ $contract->is_active ? 'Активен' : 'Неактивен' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ url('/lessee/contracts/' . $contract->id) }}"
                                               class="btn btn-info" title="Просмотр" data-bs-toggle="tooltip">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($contract->file_path)
                                            <a href="{{ url('/lessee/contracts/' . $contract->id . '/download') }}"
                                               class="btn btn-success" title="Скачать" data-bs-toggle="tooltip">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $contracts->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-file-contract fa-4x text-muted"></i>
                        </div>
                        <h5 class="text-muted">Договоры не найдены</h5>
                        <p class="text-muted">У вас пока нет заключенных договоров с платформой</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
@endsection
