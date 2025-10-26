@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Путевой лист #{{ $document->number }}</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.documents.index', ['type' => 'waybills']) }}" class="btn btn-secondary">← Назад к списку</a>
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
                            <th>Номер путевого листа:</th>
                            <td>{{ $document->number }}</td>
                        </tr>
                        <tr>
                            <th>Период действия:</th>
                            <td>{{ $document->start_date->format('d.m.Y') }} - {{ $document->end_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <span class="badge badge-{{ $document->status == 'future' ? 'secondary' : ($document->status == 'active' ? 'success' : 'primary') }}">
                                    {{ $document->status_text }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Тип смены:</th>
                            <td>{{ $document->shift_type_text }}</td>
                        </tr>
                        <tr>
                            <th>Госномер:</th>
                            <td>{{ $document->license_plate }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Оборудование и оператор</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Оборудование:</th>
                            <td>{{ $document->equipment->title ?? 'Не указано' }}</td>
                        </tr>
                        <tr>
                            <th>Оператор:</th>
                            <td>{{ $document->operator->full_name ?? 'Не назначен' }}</td>
                        </tr>
                        <tr>
                            <th>Заказ:</th>
                            <td>#{{ $document->order_id }}</td>
                        </tr>
                        <tr>
                            <th>Ставка в час:</th>
                            <td>{{ number_format($document->hourly_rate, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Перспектива:</th>
                            <td>
                                <span class="badge badge-{{ $document->perspective == 'lessor' ? 'info' : 'warning' }}">
                                    {{ $document->perspective == 'lessor' ? 'Арендодатель' : 'Арендатор' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($document->shifts && count($document->shifts) > 0)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Смены и отработанные часы</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Объект</th>
                                    <th>Время выезда</th>
                                    <th>Время возвращения</th>
                                    <th>Отработано часов</th>
                                    <th>Простой (часы)</th>
                                    <th>Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->shifts as $shift)
                                    <tr>
                                        <td>{{ $shift->shift_date->format('d.m.Y') }}</td>
                                        <td>{{ $shift->object_name }}</td>
                                        <td>{{ $shift->departure_time }}</td>
                                        <td>{{ $shift->return_time }}</td>
                                        <td>{{ $shift->hours_worked }}</td>
                                        <td>{{ $shift->downtime_hours }}</td>
                                        <td>{{ number_format($shift->total_amount, 2) }} ₽</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">Итого:</th>
                                    <th>{{ $document->total_hours }}</th>
                                    <th>{{ $document->shifts->sum('downtime_hours') }}</th>
                                    <th>{{ number_format($document->total_amount, 2) }} ₽</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Примечания</h5>
                </div>
                <div class="card-body">
                    <p>{{ $document->notes ?? 'Отсутствуют' }}</p>
                    @if($document->operator_notes)
                        <h6>Примечания оператора:</h6>
                        <p>{{ $document->operator_notes }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

