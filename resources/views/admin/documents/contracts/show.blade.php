<!-- resources/views/admin/documents/contracts/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Договор №{{ $document->number }}</h1>
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
                            <td>{{ $document->number }}</td>
                        </tr>
                        <tr>
                            <th>Описание:</th>
                            <td>{{ $document->description ?? 'Не указано' }}</td>
                        </tr>
                        <tr>
                            <th>Дата начала:</th>
                            <td>{{ $document->start_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Дата окончания:</th>
                            <td>{{ $document->end_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <span class="badge badge-{{ $document->is_active ? 'success' : 'secondary' }}">
                                    {{ $document->is_active ? 'Активен' : 'Неактивен' }}
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
                            <td>{{ $document->payment_type }}</td>
                        </tr>
                        <tr>
                            <th>Срок оплаты (дни):</th>
                            <td>{{ $document->payment_deadline }}</td>
                        </tr>
                        <tr>
                            <th>Штрафная ставка:</th>
                            <td>{{ $document->penalty_rate ?? 'Не указана' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Арендодатель</h5>
                </div>
                <div class="card-body">
                    @if($document->lessorCompany)
                        <table class="table table-sm">
                            <tr>
                                <th>Название:</th>
                                <td>{{ $document->lessorCompany->legal_name }}</td>
                            </tr>
                            <tr>
                                <th>ИНН:</th>
                                <td>{{ $document->lessorCompany->inn }}</td>
                            </tr>
                            <tr>
                                <th>КПП:</th>
                                <td>{{ $document->lessorCompany->kpp }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Арендодатель не указан</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Арендатор</h5>
                </div>
                <div class="card-body">
                    @if($document->lesseeCompany)
                        <table class="table table-sm">
                            <tr>
                                <th>Название:</th>
                                <td>{{ $document->lesseeCompany->legal_name }}</td>
                            </tr>
                            <tr>
                                <th>ИНН:</th>
                                <td>{{ $document->lesseeCompany->inn }}</td>
                            </tr>
                            <tr>
                                <th>КПП:</th>
                                <td>{{ $document->lesseeCompany->kpp }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Арендатор не указан</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($document->file_path)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <a href="{{ Storage::url($document->file_path) }}" class="btn btn-primary" target="_blank">
                        📄 Скачать договор (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
