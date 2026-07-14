{{-- resources/views/admin/documents/contracts/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Договор №{{ $contract->number }}</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.documents.index', ['type' => 'contracts']) }}" class="btn btn-secondary">← Назад к списку</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Основная информация</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Номер договора:</th>
                            <td>{{ $contract->number }}</td>
                        </tr>
                        <tr>
                            <th>Тип договора:</th>
                            <td>
                                <span class="badge badge-{{ $contract->counterparty_type === 'lessor' ? 'info' : 'warning' }}">
                                    {{ $contract->counterparty_type === 'lessor' ? 'С арендодателем' : 'С арендатором' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Описание:</th>
                            <td>{{ $contract->description ?? 'Не указано' }}</td>
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
                                <span class="badge badge-{{ $contract->is_active ? 'success' : 'secondary' }}">
                                    {{ $contract->is_active ? 'Активен' : 'Неактивен' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Условия договора</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Тип оплаты:</th>
                            <td>{{ $contract->payment_type }}</td>
                        </tr>
                        <tr>
                            <th>Срок оплаты (дни):</th>
                            <td>{{ $contract->payment_deadline }}</td>
                        </tr>
                        <tr>
                            <th>Штрафная ставка:</th>
                            <td>{{ $contract->penalty_rate ?? 'Не указана' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Информация о контрагенте</h5>
                </div>
                <div class="card-body">
                    @if($contract->counterpartyCompany)
                        <table class="table table-sm">
                            <tr>
                                <th>Тип контрагента:</th>
                                <td>{{ $contract->counterparty_type === 'lessor' ? 'Арендодатель' : 'Арендатор' }}</td>
                            </tr>
                            <tr>
                                <th>Название:</th>
                                <td>{{ $contract->counterpartyCompany->legal_name }}</td>
                            </tr>
                            <tr>
                                <th>ИНН:</th>
                                <td>{{ $contract->counterpartyCompany->inn }}</td>
                            </tr>
                            <tr>
                                <th>КПП:</th>
                                <td>{{ $contract->counterpartyCompany->kpp }}</td>
                            </tr>
                            <tr>
                                <th>ОГРН:</th>
                                <td>{{ $contract->counterpartyCompany->ogrn }}</td>
                            </tr>
                            <tr>
                                <th>Адрес:</th>
                                <td>{{ $contract->counterpartyCompany->legal_address }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Информация о контрагенте не найдена</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($contract->file_path)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <a href="{{ Storage::url($contract->file_path) }}" class="btn btn-primary" target="_blank">
                        📄 Скачать договор (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Кнопки управления для администраторов --}}
    @if(auth()->check() && auth()->user()->hasRole(['platform_super', 'platform_admin']))
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Управление договором</h5>
                </div>
                <div class="card-body text-center">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.contracts.edit', $contract) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Редактировать договор
                        </a>
                        <a href="{{ route('admin.contracts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-list"></i> К списку договоров
                        </a>
                        @if($contract->file_path)
                        <a href="{{ route('admin.contracts.download', $contract) }}" class="btn btn-success">
                            <i class="fas fa-download"></i> Скачать файл
                        </a>
                        @endif
                        <form action="{{ route('admin.contracts.destroy', $contract) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Вы уверены, что хотите удалить этот договор?')">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection