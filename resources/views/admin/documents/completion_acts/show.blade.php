@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Акт выполненных работ #{{ $document->number }}</h1>
            <p class="text-muted">
        @if($document->perspective == 'lessor')
            <span class="badge badge-info">Арендодатель → Платформа</span>
        @elseif($document->perspective == 'platform')
            <span class="badge badge-primary">Платформа → Арендатор</span>
        @else
            <span class="badge badge-warning">Платформа → Арендатор</span>
        @endif
            </p>
        </div>
       <div class="col-md-6 text-right">
            <a href="{{ route('admin.documents.index', ['type' => 'completion_acts']) }}" class="btn btn-secondary">← Назад к списку</a>

            @if(in_array($document->perspective, ['lessee', 'platform']) && !$document->upd)
                <form action="{{ route('admin.completion-acts.generate-upd', $document) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-invoice"></i> Сформировать УПД
                    </button>
                </form>
            @endif

            @if($document->upd)
                <a href="{{ route('admin.documents.show', ['type' => 'upds', 'id' => $document->upd->id]) }}"
                class="btn btn-info">
                    <i class="fas fa-eye"></i> Просмотреть УПД
                </a>
            @endif
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
                            <th>Номер акта:</th>
                            <td>{{ $document->number }}</td>
                        </tr>
                        <tr>
                            <th>Дата акта:</th>
                            <td>{{ $document->act_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Период оказания услуг:</th>
                            <td>{{ $document->service_start_date->format('d.m.Y') }} - {{ $document->service_end_date->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <span class="badge badge-{{ $document->status == 'draft' ? 'secondary' : 'success' }}">
                                    {{ $document->status == 'draft' ? 'Черновик' : 'Подписан' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Заказ:</th>
                            <td>#{{ $document->order_id }}</td>
                        </tr>
                        @if($document->parent_order_id)
                        <tr>
                            <th>Родительский заказ:</th>
                            <td>#{{ $document->parent_order_id }}</td>
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
                            <th>Всего часов:</th>
                            <td>{{ $document->total_hours }}</td>
                        </tr>
                        <tr>
                            <th>Всего простоев:</th>
                            <td>{{ $document->total_downtime }}</td>
                        </tr>
                        <tr>
                            <th>Ставка в час:</th>
                            <td>{{ number_format($document->hourly_rate, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Общая сумма:</th>
                            <td>{{ number_format($document->total_amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Сумма аванса:</th>
                            <td>{{ number_format($document->prepayment_amount, 2) }} ₽</td>
                        </tr>
                        <tr>
                            <th>Итоговая сумма:</th>
                            <td>{{ number_format($document->final_amount, 2) }} ₽</td>
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
                    <h5>
                        @if($document->perspective == 'lessor')
                            Арендодатель (Исполнитель)
                        @else
                            Арендодатель (Платформа)
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        // Определяем компанию арендодателя в зависимости от перспективы
                        $lessorCompany = null;
                        if ($document->perspective == 'lessor') {
                            // Если акт для арендодателя, то арендодатель - это исполнитель (из заказа)
                            $lessorCompany = $document->order->lessorCompany;
                        } else {
                            // Если акт для арендатора, то арендодатель - это платформа
                            $lessorCompany = App\Models\Company::where('is_platform', true)->first();
                        }
                    @endphp
                    @if($lessorCompany)
                        <table class="table table-sm">
                            <tr>
                                <th>Название:</th>
                                <td>{{ $lessorCompany->legal_name }}</td>
                            </tr>
                            <tr>
                                <th>ИНН:</th>
                                <td>{{ $lessorCompany->inn }}</td>
                            </tr>
                            <tr>
                                <th>КПП:</th>
                                <td>{{ $lessorCompany->kpp }}</td>
                            </tr>
                            <tr>
                                <th>ОГРН:</th>
                                <td>{{ $lessorCompany->ogrn }}</td>
                            </tr>
                            <tr>
                                <th>Адрес:</th>
                                <td>{{ $lessorCompany->legal_address }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Информация об арендодателе не указана</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>
                        @if($document->perspective == 'lessor')
                            Арендатор (Платформа)
                        @else
                            Арендатор (Заказчик)
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        // Определяем компанию арендатора в зависимости от перспективы
                        $lesseeCompany = null;
                        if ($document->perspective == 'lessor') {
                            // Если акт для арендодателя, то арендатор - это платформа
                            $lesseeCompany = App\Models\Company::where('is_platform', true)->first();
                        } else {
                            // Если акт для арендатора, то арендатор - это заказчик (из родительского заказа, если есть, иначе из заказа)
                            if ($document->order->parentOrder) {
                                $lesseeCompany = $document->order->parentOrder->lesseeCompany;
                            } else {
                                $lesseeCompany = $document->order->lesseeCompany;
                            }
                        }
                    @endphp
                    @if($lesseeCompany)
                        <table class="table table-sm">
                            <tr>
                                <th>Название:</th>
                                <td>{{ $lesseeCompany->legal_name }}</td>
                            </tr>
                            <tr>
                                <th>ИНН:</th>
                                <td>{{ $lesseeCompany->inn }}</td>
                            </tr>
                            <tr>
                                <th>КПП:</th>
                                <td>{{ $lesseeCompany->kpp }}</td>
                            </tr>
                            <tr>
                                <th>ОГРН:</th>
                                <td>{{ $lesseeCompany->ogrn }}</td>
                            </tr>
                            <tr>
                                <th>Адрес:</th>
                                <td>{{ $lesseeCompany->legal_address }}</td>
                            </tr>
                        </table>
                    @else
                        <p class="text-muted">Информация об арендаторе не указана</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($document->items && count($document->items) > 0)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Выполненные работы</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Наименование</th>
                                    <th>Количество</th>
                                    <th>Единица измерения</th>
                                    <th>Цена за единицу</th>
                                    <th>Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->items as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ number_format($item->quantity, 2) }}</td>
                                        <td>{{ $item->unit }}</td>
                                        <td>{{ number_format($item->price, 2) }} ₽</td>
                                        <td>{{ number_format($item->amount, 2) }} ₽</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">Итого:</th>
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
                </div>
            </div>
        </div>
    </div>

    @if($document->act_file_path)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <a href="{{ Storage::url($document->act_file_path) }}" class="btn btn-primary" target="_blank">
                        📄 Скачать акт (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
