@extends('layouts.app')

@section('title', $lessee->legal_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <!-- Боковая панель с информацией -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ $lessee->legal_name }}</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="bg-light p-4 rounded">
                            <i class="bi bi-building fs-1 text-primary"></i>
                        </div>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Статус:</span>
                            <span class="badge bg-{{ $lessee->status == 'verified' ? 'success' : 'warning' }}">
                                {{ $lessee->status }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>ИНН:</span>
                            <span>{{ $lessee->inn }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Телефон:</span>
                            <span>{{ $lessee->phone }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Директор:</span>
                            <span>{{ $lessee->director_name }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Дата регистрации:</span>
                            <span>{{ $lessee->created_at->format('d.m.Y') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- Вертикальные табы -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" 
                                    data-bs-target="#info" type="button" role="tab">
                                <i class="bi bi-info-circle me-1"></i> Основная информация
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" 
                                    data-bs-target="#orders" type="button" role="tab">
                                <i class="bi bi-list-check me-1"></i> Заказы ({{ $orders->total() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" 
                                    data-bs-target="#documents" type="button" role="tab">
                                <i class="bi bi-files me-1"></i> Документы
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Основная информация -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Юридические данные</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Юр. адрес:</th>
                                            <td>{{ $lessee->legal_address }}</td>
                                        </tr>
                                        <tr>
                                            <th>Факт. адрес:</th>
                                            <td>{{ $lessee->actual_address ?? 'Не указан' }}</td>
                                        </tr>
                                        <tr>
                                            <th>ОГРН:</th>
                                            <td>{{ $lessee->ogrn }}</td>
                                        </tr>
                                        <tr>
                                            <th>КПП:</th>
                                            <td>{{ $lessee->kpp }}</td>
                                        </tr>
                                        <tr>
                                            <th>Налоговая система:</th>
                                            <td>{{ $lessee->tax_system == 'vat' ? 'С НДС' : 'Без НДС' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Банковские реквизиты</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Банк:</th>
                                            <td>{{ $lessee->bank_name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Р/с:</th>
                                            <td>{{ $lessee->bank_account }}</td>
                                        </tr>
                                        <tr>
                                            <th>БИК:</th>
                                            <td>{{ $lessee->bik }}</td>
                                        </tr>
                                        <tr>
                                            <th>Корр. счет:</th>
                                            <td>{{ $lessee->correspondent_account ?? 'Не указан' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if($lessee->rejection_reason)
                                <div class="alert alert-danger mt-3">
                                    <h5>Причина отказа:</h5>
                                    <p>{{ $lessee->rejection_reason }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Заказы -->
                        <div class="tab-pane fade" id="orders" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Дата</th>
                                            <th>Арендодатель</th>
                                            <th>Техника</th>
                                            <th>Сумма</th>
                                            <th>Статус</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders as $order)
                                        <tr>
                                            <td>#{{ $order->id }}</td>
                                            <td>{{ $order->created_at->format('d.m.Y') }}</td>
                                            <td>{{ $order->lessorCompany->legal_name }}</td>
                                            <td>
                                                @foreach($order->items as $item)
                                                    <div>{{ $item->equipment->title }}</div>
                                                @endforeach
                                            </td>
                                            <td>{{ number_format($order->total_amount, 2) }} ₽</td>
                                            <td>
                                                <span class="badge bg-{{ $order->status_color }}">
                                                    {{ $order->status_text }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.lessees.orders.show', ['lessee' => $lessee->id, 'order' => $order->id]) }}" 
   class="btn btn-sm btn-outline-primary">
    <i class="bi bi-eye"></i>
</a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                {{ $orders->links() }}
                            </div>
                        </div>

                        <!-- Документы -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Раздел в разработке
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link {
        border: none;
        color: #495057;
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 3px solid #0d6efd;
    }
    .table-sm th, .table-sm td {
        padding: 0.5rem;
    }
</style>
@endsection