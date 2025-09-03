@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Детали счета #{{ $invoice->number }}</h3>
        <div class="card-tools">
            <a href="{{ route('admin.finance.invoices') }}" class="btn btn-default btn-sm">
                Назад к списку
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Основная информация</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Номер счета:</th>
                        <td>{{ $invoice->number }}</td>
                    </tr>
                    <tr>
                        <th>Компания:</th>
                        <td>
                            @if($invoice->company)
                                {{ $invoice->company->legal_name }}
                                <br><small class="text-muted">ИНН: {{ $invoice->company->inn }}</small>
                            @else
                                <span class="text-muted">Не указана</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Заказ:</th>
                        <td>
                            @if($invoice->order)
                                Заказ #{{ $invoice->order->id }}
                            @else
                                <span class="text-muted">Не указан</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Сумма:</th>
                        <td>{{ number_format($invoice->amount, 2) }} руб.</td>
                    </tr>
                </table>
            </div>

            <div class="col-md-6">
                <h5>Статус и даты</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Статус:</th>
                        <td>
                            <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'secondary' }}">
                                {{ $invoice->status === 'paid' ? 'Оплачен' : 'Ожидает оплаты' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Дата создания:</th>
                        <td>{{ $invoice->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Дата оплаты:</th>
                        <td>
                            @if($invoice->paid_at)
                                {{ $invoice->paid_at->format('d.m.Y H:i') }}
                            @else
                                <span class="text-muted">Не оплачен</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if($invoice->order && $invoice->order->items)
        <div class="row mt-4">
            <div class="col-12">
                <h5>Позиции заказа</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Оборудование</th>
                                <th>Количество</th>
                                <th>Стоимость аренды</th>
                                <th>Общая стоимость</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->order->items as $item)
                            <tr>
                                <td>
                                    @if($item->equipment)
                                        {{ $item->equipment->name }}
                                    @else
                                        <span class="text-muted">Не указано</span>
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->rental_cost, 2) }} руб.</td>
                                <td>{{ number_format($item->quantity * $item->rental_cost, 2) }} руб.</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
