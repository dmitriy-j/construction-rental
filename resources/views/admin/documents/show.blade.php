@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>УПД №{{ $upd->number }}</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.upds.index') }}" class="btn btn-secondary">Назад к списку</a>
            @if($upd->status == 'pending')
                <form action="{{ route('admin.upds.accept', $upd) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Принять</button>
                </form>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">Отклонить</button>
            @endif

            @if($upd->file_path)
                <a href="{{ Storage::url($upd->file_path) }}" class="btn btn-info" target="_blank">Скачать файл</a>
            @endif
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
                            <th>Номер УПД:</th>
                            <td>{{ $upd->number }}</td>
                        </tr>
                        <tr>
                            <th>Дата составления:</th>
                            <td>{{ $upd->issue_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Период оказания услуг:</th>
                            <td>{{ $upd->service_period_start->format('d.m.Y') }} - {{ $upd->service_period_end->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <span class="badge badge-{{ $upd->status == 'pending' ? 'warning' : ($upd->status == 'accepted' ? 'success' : 'danger') }}">
                                    {{ $upd->status == 'pending' ? 'Ожидает' : ($upd->status == 'accepted' ? 'Принят' : 'Отклонен') }}
                                </span>
                            </td>
                        </tr>
                        @if($upd->rejection_reason)
                        <tr>
                            <th>Причина отклонения:</th>
                            <td>{{ $upd->rejection_reason }}</td>
                        </tr>
                        @endif
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
                            <th>Сумма без НДС:</th>
                            <td>{{ number_format($upd->amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Сумма НДС:</th>
                            <td>{{ number_format($upd->tax_amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Итого с НДС:</th>
                            <td>{{ number_format($upd->total_amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Система налогообложения:</th>
                            <td>{{ $upd->tax_system }}</td>
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
                    <h5>Информация об арендодателе (продавец)</h5>
                </div>
                <div class="card-body">
                    @php $lessor = $upd->lessorCompany; @endphp
                    <table class="table table-sm">
                        <tr>
                            <th>Название:</th>
                            <td>{{ $lessor->legal_name }}</td>
                        </tr>
                        <tr>
                            <th>ИНН:</th>
                            <td>{{ $lessor->inn }}</td>
                        </tr>
                        <tr>
                            <th>КПП:</th>
                            <td>{{ $lessor->kpp }}</td>
                        </tr>
                        <tr>
                            <th>ОГРН:</th>
                            <td>{{ $lessor->ogrn }}</td>
                        </tr>
                        <tr>
                            <th>Адрес:</th>
                            <td>{{ $lessor->legal_address }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Информация о платформе (покупатель)</h5>
                </div>
                <div class="card-body">
                    @php
                    // Получаем компанию-платформу
                    $platformCompany = App\Models\Company::where('is_platform', true)->first();
                    @endphp
                    <table class="table table-sm">
                        <tr>
                            <th>Название:</th>
                            <td>{{ $platformCompany->legal_name }}</td>
                        </tr>
                        <tr>
                            <th>ИНН:</th>
                            <td>{{ $platformCompany->inn }}</td>
                        </tr>
                        <tr>
                            <th>КПП:</th>
                            <td>{{ $platformCompany->kpp }}</td>
                        </tr>
                        <tr>
                            <th>ОГРН:</th>
                            <td>{{ $platformCompany->ogrn }}</td>
                        </tr>
                        <tr>
                            <th>Адрес:</th>
                            <td>{{ $platformCompany->legal_address }}</td>
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
                <h5>Сравнение заказов</h5>
            </div>
            <div class="card-body">
                @php
                $order = $upd->order;
                $parentOrder = $order->parentOrder;
                @endphp

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6>Дочерний заказ #{{ $order->id }}</h6>
                                <small class="text-muted">Платформа → Арендодатель</small>
                            </div>
                            <div class="card-body">
                                <p><strong>Арендодатель:</strong> {{ $order->lessorCompany->legal_name }}</p>
                                <p><strong>Период аренды:</strong> {{ $order->start_date->format('d.m.Y') }} - {{ $order->end_date->format('d.m.Y') }}</p>
                                <p><strong>Статус:</strong> {{ $order->status_text }}</p>
                                <p><strong>Общая стоимость:</strong> {{ number_format($order->total_amount, 2) }} ₽</p>
                            </div>
                        </div>
                    </div>

                    @if($parentOrder)
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6>Родительский заказ #{{ $parentOrder->id }}</h6>
                                <small class="text-muted">Платформа → Арендатор</small>
                            </div>
                            <div class="card-body">
                                <p><strong>Арендатор:</strong> {{ $parentOrder->lesseeCompany->legal_name }}</p>
                                <p><strong>Период аренды:</strong> {{ $parentOrder->start_date->format('d.m.Y') }} - {{ $parentOrder->end_date->format('d.m.Y') }}</p>
                                <p><strong>Статус:</strong> {{ $parentOrder->status_text }}</p>
                                <p><strong>Общая стоимость:</strong> {{ number_format($parentOrder->total_amount, 2) }} ₽</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                @if($parentOrder)
                <h6 class="mt-4">Сравнение позиций</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Оборудование</th>
                                <th class="text-center">Количество</th>
                                <th class="text-center">Цена за единицу</th>
                                <th class="text-center">Общая стоимость</th>
                                <th class="text-center">Разница в цене</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                @php
                                    // Находим соответствующую позицию в родительском заказе
                                    $parentItem = $parentOrder->items->where('equipment_id', $item->equipment_id)->first();

                                    if ($parentItem) {
                                        $priceDifference = $parentItem->price_per_unit - $item->price_per_unit;
                                        $totalDifference = $parentItem->total_price - $item->total_price;
                                        $priceDifferenceClass = $priceDifference > 0 ? 'text-success' : ($priceDifference < 0 ? 'text-danger' : 'text-muted');
                                        $totalDifferenceClass = $totalDifference > 0 ? 'text-success' : ($totalDifference < 0 ? 'text-danger' : 'text-muted');
                                    }
                                @endphp

                                <!-- Строка дочернего заказа -->
                                <tr class="table-info">
                                    <td><strong>{{ $item->equipment->title }}</strong></td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-center">{{ number_format($item->price_per_unit, 2) }} ₽</td>
                                    <td class="text-center">{{ number_format($item->total_price, 2) }} ₽</td>
                                    <td class="text-center {{ $priceDifferenceClass ?? 'text-muted' }}">
                                        @if($parentItem)
                                            {{ $priceDifference > 0 ? '+' : '' }}{{ number_format($priceDifference, 2) }} ₽
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Строка родительского заказа -->
                                @if($parentItem)
                                <tr class="table-warning">
                                    <td><small class="text-muted">(родительский заказ)</small></td>
                                    <td class="text-center">{{ $parentItem->quantity }}</td>
                                    <td class="text-center">{{ number_format($parentItem->price_per_unit, 2) }} ₽</td>
                                    <td class="text-center">{{ number_format($parentItem->total_price, 2) }} ₽</td>
                                    <td class="text-center {{ $totalDifferenceClass }}">
                                        {{ $totalDifference > 0 ? '+' : '' }}{{ number_format($totalDifference, 2) }} ₽
                                    </td>
                                </tr>
                                @else
                                <tr class="table-warning">
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-exclamation-triangle"></i> Оборудование отсутствует в родительском заказе
                                    </td>
                                </tr>
                                @endif

                                <!-- Разделитель -->
                                <tr>
                                    <td colspan="5" style="background-color: #f8f9fa; height: 10px;"></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @php
                                $totalDifference = $parentOrder->total_amount - $order->total_amount;
                                $totalDifferenceClass = $totalDifference > 0 ? 'text-success' : ($totalDifference < 0 ? 'text-danger' : 'text-muted');
                            @endphp
                            <tr class="table-secondary">
                                <th colspan="3" class="text-right">Итоговая разница:</th>
                                <th class="text-center">
                                    {{ number_format($totalDifference, 2) }} ₽
                                </th>
                                <th class="text-center {{ $totalDifferenceClass }}">
                                    {{ $totalDifference > 0 ? '+' : '' }}{{ number_format($totalDifference, 2) }} ₽
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Родительский заказ не найден для сравнения.
                </div>

                <h6 class="mt-4">Позиции дочернего заказа</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Оборудование</th>
                                <th class="text-center">Количество</th>
                                <th class="text-center">Цена за единицу</th>
                                <th class="text-center">Общая стоимость</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->equipment->title }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-center">{{ number_format($item->price_per_unit, 2) }} ₽</td>
                                <td class="text-center">{{ number_format($item->total_price, 2) }} ₽</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

    <!-- Табличная часть УПД -->
    @if($upd->items && count($upd->items) > 0)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Табличная часть УПД</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th>Количество</th>
                                    <th>Единица измерения</th>
                                    <th>Цена</th>
                                    <th>Сумма</th>
                                    <th>Ставка НДС</th>
                                    <th>Сумма НДС</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upd->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ number_format($item->quantity, 2) }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td>{{ number_format($item->price, 2) }} ₽</td>
                                        <td>{{ number_format($item->amount, 2) }} ₽</td>
                                        <td>{{ number_format($item->vat_rate, 0) }}%</td>
                                        <td>{{ number_format($item->vat_amount, 2) }} ₽</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">Итого:</th>
                                    <th>{{ number_format($upd->amount, 2) }} ₽</th>
                                    <th></th>
                                    <th>{{ number_format($upd->tax_amount, 2) }} ₽</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-right">Всего с НДС:</th>
                                    <th colspan="3">{{ number_format($upd->total_amount, 2) }} ₽</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.upds.reject', $upd) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Отклонение УПД</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Причина отклонения</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Подтвердить отклонение</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
