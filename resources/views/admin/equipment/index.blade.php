@extends('layouts.app')

@section('title', 'Каталог техники — Админ-панель')

@section('content')
<div class="container-fluid px-0">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 px-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Каталог техники</h1>
            <p class="text-muted mb-0 small">Управление всем оборудованием на платформе</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary fs-6 px-3 py-2"><i class="bi bi-tools me-1"></i> {{ $equipment->total() }} ед.</span>
            <a href="{{ route('admin.equipment.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Добавить</a>
        </div>
    </div>

    {{-- Filters — flat style without card --}}
    <div class="px-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Поиск</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Название, бренд..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Компания</label>
                <select name="company_id" class="form-select form-select-sm">
                    <option value="">Все</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Категория</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Все</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Статус</label>
                <select name="is_approved" class="form-select form-select-sm">
                    <option value="">Все</option>
                    <option value="1" {{ request('is_approved') === '1' ? 'selected' : '' }}>Одобрено</option>
                    <option value="0" {{ request('is_approved') === '0' ? 'selected' : '' }}>На проверке</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Тип</label>
                <select name="owner_type" class="form-select form-select-sm">
                    <option value="">Вся</option>
                    <option value="platform" {{ request('owner_type') === 'platform' ? 'selected' : '' }}>Платформа</option>
                    <option value="lessor" {{ request('owner_type') === 'lessor' ? 'selected' : '' }}>Арендодатели</option>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i></button>
            </div>
        </form>
        @if(request()->anyFilled(['search','company_id','category_id','is_approved','owner_type']))
        <div class="mt-2">
            <a href="{{ route('admin.equipment.index') }}" class="btn btn-sm btn-link text-decoration-none"><i class="bi bi-x-circle me-1"></i> Сбросить фильтры</a>
        </div>
        @endif
    </div>

    {{-- Table — flat design --}}
    <div class="px-3">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Название</th>
                        <th>Бренд</th>
                        <th>Модель</th>
                        <th>Год</th>
                        <th>Владелец</th>
                        <th>Категория</th>
                        <th>Локация</th>
                        <th>Статус</th>
                        <th style="width:130px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($equipment as $item)
                    <tr>
                        <td class="text-muted">{{ $item->id }}</td>
                        <td><a href="{{ route('admin.equipment.show', $item) }}" class="fw-medium text-decoration-none">{{ $item->title }}</a></td>
                        <td>{{ $item->brand }}</td>
                        <td>{{ $item->model }}</td>
                        <td>{{ $item->year }}</td>
                        <td>
                            @if($item->isPlatformOwned())
                                <span class="badge bg-info"><i class="bi bi-building-gear me-1"></i> Платформа</span>
                            @else
                                <span class="badge bg-secondary"><i class="bi bi-building me-1"></i> {{ $item->owner_name }}</span>
                            @endif
                        </td>
                        <td>{{ $item->category->name ?? '—' }}</td>
                        <td>{{ $item->location->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $item->is_approved ? 'success' : 'warning' }}">{{ $item->is_approved ? 'Одобрено' : 'На проверке' }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.equipment.show', $item) }}" class="btn btn-outline-primary" title="Подробнее"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.equipment.edit', $item) }}" class="btn btn-outline-warning" title="Редактировать"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.equipment.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Удалить"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($equipment->hasPages())
        <div class="mt-3">{{ $equipment->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
