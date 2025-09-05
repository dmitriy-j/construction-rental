@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Генерация документа: {{ $documentTemplate->name }}</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.settings.document-templates.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.settings.document-templates.generate', $documentTemplate) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="entity_type" class="form-label">Тип сущности</label>
                    <select class="form-select" id="entity_type" name="entity_type" required>
                        <option value="">Выберите тип сущности</option>
                        <option value="order">Заказ</option>
                        <option value="act">Акт</option>
                        <option value="invoice">Счет</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="entity_id" class="form-label">Сущность</label>
                    <select class="form-select" id="entity_id" name="entity_id" required>
                        <option value="">Выберите сущность</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}">
                                @if(isset($entity->order_number))
                                    Заказ #{{ $entity->order_number }}
                                @elseif(isset($entity->act_number))
                                    Акт #{{ $entity->act_number }}
                                @else
                                    {{ $entity->id }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Сгенерировать документ
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const entityTypeSelect = document.getElementById('entity_type');
    const entityIdSelect = document.getElementById('entity_id');

    entityTypeSelect.addEventListener('change', function() {
        // Здесь можно добавить AJAX-запрос для загрузки соответствующих сущностей
        // Временно просто показываем все доступные сущности
        entityIdSelect.disabled = false;
    });
});
</script>
@endpush
