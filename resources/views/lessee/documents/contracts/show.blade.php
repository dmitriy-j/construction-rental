@extends('layouts.app')

@section('title', 'Договор №' . $contract->number)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title mb-0">Договор №{{ $contract->number }}</h4>
                {{-- Кнопка "Назад к списку" перемещена вправо --}}
                <a href="{{ url('/lessee/contracts') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Назад к списку
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title mb-3">Основная информация</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Номер договора:</th>
                                    <td>{{ $contract->number }}</td>
                                </tr>
                                <tr>
                                    <th>Тип договора:</th>
                                    <td>
                                        <span class="badge bg-warning">
                                            {{ $contract->counterparty_type_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Платформа:</th>
                                    <td>{{ $contract->platformCompany->legal_name ?? 'Платформа' }}</td>
                                </tr>
                                <tr>
                                    <th>Дата начала:</th>
                                    <td>{{ $contract->start_date->format('d.m.Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Дата окончания:</th>
                                    <td>{{ $contract->end_date->format('d.m.Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Статус:</th>
                                    <td>
                                        <span class="badge bg-{{ $contract->is_active ? 'success' : 'secondary' }}">
                                            {{ $contract->is_active ? 'Активен' : 'Неактивен' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5 class="card-title mb-3">Условия договора</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Тип оплаты:</th>
                                    <td>{{ $contract->payment_type ?? 'Не указан' }}</td>
                                </tr>
                                <tr>
                                    <th>Срок документов (дни):</th>
                                    <td>{{ $contract->documentation_deadline ?? 'Не указан' }}</td>
                                </tr>
                                <tr>
                                    <th>Срок оплаты (дни):</th>
                                    <td>{{ $contract->payment_deadline ?? 'Не указан' }}</td>
                                </tr>
                                <tr>
                                    <th>Ставка пени:</th>
                                    <td>{{ $contract->penalty_rate ? $contract->penalty_rate . '%' : 'Не указана' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($contract->description)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="card-title mb-3">Описание договора</h5>
                            <div class="border rounded p-3 bg-light">
                                {!! nl2br(e($contract->description)) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                @if($contract->file_path)
                                <a href="{{ url('/lessee/contracts/' . $contract->id . '/download') }}"
                                   class="btn btn-success">
                                    <i class="fas fa-download me-1"></i> Скачать договор
                                </a>
                                @else
                                <span class="text-muted">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Файл договора отсутствует
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
