@extends('layouts.app')

@section('title', $lessor->legal_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <!-- Боковая панель с информацией -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ $lessor->legal_name }}</h5>
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
                            <span class="badge bg-{{ $lessor->status == 'verified' ? 'success' : 'warning' }}">
                                {{ $lessor->status }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>ИНН:</span>
                            <span>{{ $lessor->inn }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Телефон:</span>
                            <span>{{ $lessor->phone }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Директор:</span>
                            <span>{{ $lessor->director_name }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Техники: {{ $equipment->total() }}</span>
                            <span>{{ $lessor->equipment_count }}</span>
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
                data-bs-target="#equipment" type="button" role="tab">
            <i class="bi bi-tools me-1"></i> Техника ({{ $equipment->total() }})
        </button>
    </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" 
                                    data-bs-target="#requests" type="button" role="tab">
                                <i class="bi bi-list-check me-1"></i> Заявки
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
                                            <td>{{ $lessor->legal_address }}</td>
                                        </tr>
                                        <tr>
                                            <th>Факт. адрес:</th>
                                            <td>{{ $lessor->actual_address ?? 'Не указан' }}</td>
                                        </tr>
                                        <tr>
                                            <th>ОГРН:</th>
                                            <td>{{ $lessor->ogrn }}</td>
                                        </tr>
                                        <tr>
                                            <th>КПП:</th>
                                            <td>{{ $lessor->kpp }}</td>
                                        </tr>
                                        <tr>
                                            <th>Налоговая система:</th>
                                            <td>{{ $lessor->tax_system == 'vat' ? 'С НДС' : 'Без НДС' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Банковские реквизиты</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Банк:</th>
                                            <td>{{ $lessor->bank_name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Р/с:</th>
                                            <td>{{ $lessor->bank_account }}</td>
                                        </tr>
                                        <tr>
                                            <th>БИК:</th>
                                            <td>{{ $lessor->bik }}</td>
                                        </tr>
                                        <tr>
                                            <th>Корр. счет:</th>
                                            <td>{{ $lessor->correspondent_account ?? 'Не указан' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if($lessor->rejection_reason)
                                <div class="alert alert-danger mt-3">
                                    <h5>Причина отказа:</h5>
                                    <p>{{ $lessor->rejection_reason }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Техника -->
<div class="tab-pane fade" id="equipment" role="tabpanel">
    @if($equipment->total() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Бренд</th>
                        <th>Модель</th>
                        <th>Год</th>
                        <th>Статус</th>
                        <th>Цена/час</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($equipment as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->category->name ?? '-' }}</td>
                        <td>{{ $item->brand }}</td>
                        <td>{{ $item->model }}</td>
                        <td>{{ $item->year }}</td>
                        <td>
                            <span class="badge bg-{{ $item->is_approved ? 'success' : 'warning' }}">
                                {{ $item->is_approved ? 'Одобрено' : 'На проверке' }}
                            </span>
                        </td>
                        <td>
                            @if($item->rentalTerms->first())
                                {{ number_format($item->rentalTerms->first()->price_per_hour, 2) }} ₽
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.equipment.show', $item) }}" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Пагинация -->
        <div class="mt-3">
            {{ $equipment->links() }}
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Нет зарегистрированной техники
        </div>
    @endif
</div>

                        <!-- Заявки -->
                        <div class="tab-pane fade" id="requests" role="tabpanel">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Раздел в разработке
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