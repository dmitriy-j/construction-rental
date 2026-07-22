@extends('layouts.app')
@section('title', 'Создать новость')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-4 page-header">
        <h1 class="h3 mb-0">Создать новость</h1>
        <div class="ms-auto page-actions">
            <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary btn-block-mobile"><i class="fas fa-arrow-left"></i> Назад</a>
        </div>
    </div>
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.news.store') }}" method="POST">
            @csrf
            <div class="mb-3"><label class="form-label">Заголовок *</label><input type="text" name="title" class="form-control" value="{{ old('title') }}" required></div>
            <div class="mb-3"><label class="form-label">Категория *</label>
                <select name="category" class="form-select" required>
                    <option value="all" {{ old('category')=='all' ? 'selected':'' }}>Для всех</option>
                    <option value="lessee" {{ old('category')=='lessee' ? 'selected':'' }}>Для арендаторов</option>
                    <option value="lessor" {{ old('category')=='lessor' ? 'selected':'' }}>Для арендодателей</option>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Краткое описание</label>
                <textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt') }}</textarea>
                <small class="text-muted">Если не заполнено, будет сгенерировано из первых 200 символов текста</small>
            </div>
            <div class="mb-3"><label class="form-label">Текст новости *</label>
                <textarea name="content" class="form-control" rows="10" required>{{ old('content') }}</textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" {{ old('is_active') ? 'checked':'' }}>
                        <label class="form-check-label" for="isActive">Опубликовать</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Дата публикации</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at') }}">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block-mobile"><i class="fas fa-save"></i> Создать</button>
        </form>
    </div></div>
</div>
@endsection
