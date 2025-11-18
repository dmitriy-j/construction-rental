@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Счет на оплату #{{ $document->number }}</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.documents.index', ['type' => 'invoices']) }}" class="btn btn-secondary">← Назад к списку</a>

            <!-- Скачивание через новый маршрут -->
            @if($document->file_path)
                <a href="{{ route('admin.invoices.download', $document) }}" class="btn btn-primary">
                    <i class="fas fa-download"></i> Скачать счет
                </a>
            @endif

            <!-- Отмена счета -->
            @if($document->status !== 'paid' && $document->status !== 'canceled')
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#cancelModal">
                    <i class="fas fa-ban"></i> Отменить счет
                </button>
            @endif
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
                            <th>Номер счета:</th>
                            <td>{{ $document->number }}</td>
                        </tr>
                        <tr>
                            <th>Дата выставления:</th>
                            <td>{{ $document->issue_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Срок оплаты:</th>
                            <td>{{ $document->due_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                @php
                                    $statusClass = match($document->status) {
                                        'draft' => 'secondary',
                                        'sent' => 'info',
                                        'viewed' => 'primary',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                        'canceled' => 'dark',
                                        default => 'light'
                                    };
                                @endphp
                                <span class="badge badge-{{ $statusClass }}">
                                    {{ $document->status }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Заказ:</th>
                            <td>#{{ $document->order_id }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Финансовая информация</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Сумма счета:</th>
                            <td>{{ number_format($document->amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Оплачено:</th>
                            <td>{{ number_format($document->amount_paid, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Остаток к оплате:</th>
                            <td>{{ number_format($document->amount - $document->amount_paid, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Комиссия платформы:</th>
                            <td>{{ number_format($document->platform_fee, 2) }} ₽</td>
                        </tr>
                        @if($document->paid_at)
                        <tr>
                            <th>Дата оплаты:</th>
                            <td>{{ $document->paid_at->format('d.m.Y H:i') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Остальная часть шаблона без изменений -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Плательщик</h5>
                </div>
                <div class="card-body">
                    @if($document->company)
                        <table class="table table-sm">
                            <tr>
                                <th>Компания:</th>
                                <td>{{ $document->company->legal_name }}</td>
                            </tr>
                            <tr>
                                <th>ИНН:</th>
                                <td>{{ $document->company->inn }}</td>
                            </tr>
                            <tr>
                                <th>КПП:</th>
                                <td>{{ $document->company->kpp }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Информация о плательщике не указана</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Реквизиты для оплаты</h5>
                </div>
                <div class="card-body">
                    @if($document->company)
                        <table class="table table-sm">
                            <tr>
                                <th>Банк:</th>
                                <td>{{ $document->company->bank_name }}</td>
                            </tr>
                            <tr>
                                <th>Расчетный счет:</th>
                                <td>{{ $document->company->bank_account }}</td>
                            </tr>
                            <tr>
                                <th>БИК:</th>
                                <td>{{ $document->company->bik }}</td>
                            </tr>
                            <tr>
                                <th>Корр. счет:</th>
                                <td>{{ $document->company->correspondent_account }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Реквизиты для оплаты не указаны</p>
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
                    <a href="{{ route('admin.invoices.download', $document) }}" class="btn btn-primary">
                        <i class="fas fa-download"></i> Скачать счет (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Модальное окно отмены -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.invoices.cancel', $document) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Отмена счета #{{ $document->number }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Причина отмены</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Подтвердить отмену</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
