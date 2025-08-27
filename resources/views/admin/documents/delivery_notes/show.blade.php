@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Транспортная накладная #{{ $document->document_number }}</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.documents.index', ['type' => 'delivery_notes']) }}" class="btn btn-secondary">← Назад к списку</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Информация о документе</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Номер документа:</th>
                            <td>{{ $document->document_number }}</td>
                        </tr>
                        <tr>
                            <th>Дата составления:</th>
                            <td>{{ $document->issue_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Тип накладной:</th>
                            <td>{{ \App\Models\DeliveryNote::types()[$document->type] ?? $document->type }}</td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <span class="badge badge-{{ $document->status == 'draft' ? 'secondary' : ($document->status == 'in_transit' ? 'warning' : ($document->status == 'delivered' ? 'info' : 'success')) }}">
                                    {{ \App\Models\DeliveryNote::statuses()[$document->status] ?? $document->status }}
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
                    <h5>Информация о перевозке</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Тип транспорта:</th>
                            <td>{{ \App\Models\DeliveryNote::vehicleTypes()[$document->transport_type] ?? $document->transport_type }}</td>
                        </tr>
                        <tr>
                            <th>Водитель:</th>
                            <td>{{ $document->transport_driver_name }}</td>
                        </tr>
                        <tr>
                            <th>Марка автомобиля:</th>
                            <td>{{ $document->transport_vehicle_model }}</td>
                        </tr>
                        <tr>
                            <th>Номер автомобиля:</th>
                            <td>{{ $document->transport_vehicle_number }}</td>
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
                    <h5>Отправитель</h5>
                </div>
                <div class="card-body">
                    @if($document->senderCompany)
                        <table class="table table-sm">
                            <tr>
                                <th>Компания:</th>
                                <td>{{ $document->senderCompany->legal_name }}</td>
                            </tr>
                            <tr>
                                <th>Адрес:</th>
                                <td>{{ $document->deliveryFrom->address ?? 'Не указан' }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Информация об отправителе не указана</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Получатель</h5>
                </div>
                <div class="card-body">
                    @if($document->receiverCompany)
                        <table class="table table-sm">
                            <tr>
                                <th>Компания:</th>
                                <td>{{ $document->receiverCompany->legal_name }}</td>
                            </tr>
                            <tr>
                                <th>Адрес:</th>
                                <td>{{ $document->deliveryTo->address ?? 'Не указан' }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Информация о получателе не указана</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Информация о грузе</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Описание груза:</th>
                            <td>{{ $document->cargo_description }}</td>
                        </tr>
                        <tr>
                            <th>Вес груза (кг):</th>
                            <td>{{ number_format($document->cargo_weight, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Стоимость груза:</th>
                            <td>{{ number_format($document->cargo_value, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Количество мест:</th>
                            <td>{{ $document->cargo_places_count }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
