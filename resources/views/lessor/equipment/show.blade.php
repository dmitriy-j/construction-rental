@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ $equipment->title }}</h1>
        <div>
            <a href="{{ route('lessor.equipment.edit', $equipment) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Редактировать
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">Основная информация</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Категория:</strong> {{ $equipment->category->name }}</p>
                            <p><strong>Бренд:</strong> {{ $equipment->brand }}</p>
                            <p><strong>Модель:</strong> {{ $equipment->model }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Год выпуска:</strong> {{ $equipment->year }}</p>
                            <p><strong>Наработка:</strong> {{ $equipment->hours_worked }} ч</p>
                            <p><strong>Локация:</strong> {{ $equipment->location->city }}, {{ $equipment->location->region }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p><strong>Описание:</strong></p>
                        <p>{{ $equipment->description }}</p>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Тарифы</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Период</th>
                                    <th>Цена</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($equipment->rentalTerms as $term)
                                <tr>
                                    <td>{{ $term->full_period }}</td>
                                    <td>{{ $term->formatted_price }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">Изображения</div>
                <div class="card-body">
                    <div id="equipmentCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            @foreach($equipment->images as $key => $image)
                            <div class="carousel-item {{ $image->is_main ? 'active' : '' }}">
                                <img src="{{ asset('storage/' . $image->path) }}"
                                     class="d-block w-100 rounded"
                                     alt="{{ $equipment->title }}">
                            </div>
                            @endforeach
                        </div>
                        <button class="carousel-control-prev" type="button"
                                data-bs-target="#equipmentCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button"
                                data-bs-target="#equipmentCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Статус</div>
                <div class="card-body">
                    @if($equipment->is_approved)
                        <span class="badge bg-success">Одобрено</span>
                    @else
                        <span class="badge bg-warning">На модерации</span>
                        <p class="mt-2">Ваша техника проходит проверку модератором. После одобрения она появится в каталоге.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
