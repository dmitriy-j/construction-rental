@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-truck-loading me-2"></i>Транспортная накладная #{{ $deliveryNote->id }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Информация о доставке</h5>
                            <p><strong>Статус:</strong>
                                <span class="badge bg-{{ $deliveryNote->status === 'completed' ? 'success' : 'info' }}">
                                    {{ $deliveryNote->status_text }}
                                </span>
                            </p>
                            <p><strong>Тип доставки:</strong> {{ $deliveryNote->delivery_type_text }}</p>
                            <p><strong>Отправитель:</strong> {{ $deliveryNote->senderCompany->legal_name }}</p>
                            <p><strong>Получатель:</strong> {{ $deliveryNote->receiverCompany->legal_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Детали доставки</h5>
                            <p><strong>Адрес отправления:</strong> {{ $deliveryNote->from_address }}</p>
                            <p><strong>Адрес назначения:</strong> {{ $deliveryNote->to_address }}</p>
                            <p><strong>Дата создания:</strong> {{ $deliveryNote->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Оборудование</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Наименование</th>
                                            <th>Количество</th>
                                            <th>Вес</th>
                                            <th>Габариты</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($deliveryNote->equipment)
                                            <tr>
                                                <td>{{ $deliveryNote->equipment->title }}</td>
                                                <td>1</td>
                                                <td>{{ $deliveryNote->equipment->weight }} кг</td>
                                                <td>{{ $deliveryNote->equipment->dimensions }}</td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td colspan="4" class="text-center">Оборудование не указано</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ url('/lessee/documents/delivery-notes/' . $deliveryNote->id . '/pdf') }}"
                           class="btn btn-primary">
                            <i class="fas fa-download me-1"></i>Скачать PDF
                        </a>
                        <a href="{{ route('lessee.orders.show', $deliveryNote->order_id) }}"
                           class="btn btn-outline-secondary">
                            <i class="fas fa-external-link-alt me-1"></i>Перейти к заказу
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
