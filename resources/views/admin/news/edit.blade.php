@extends('admin.layouts.app')

@section('title', 'Редактировать новость')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4>Редактировать новость: {{ $news->title }}</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.news.update', $news->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="title" class="form-label">Заголовок</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $news->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Содержание</label>
                    <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="6" required>{{ old('content', $news->content) }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="publish_date" class="form-label">Дата публикации</label>
                        <input type="datetime-local" class="form-control @error('publish_date') is-invalid @enderror" id="publish_date" name="publish_date" value="{{ old('publish_date', $news->publish_date->format('Y-m-d\TH:i')) }}" required>
                        @error('publish_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Статус</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $news->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">
                                Опубликовать
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Назад
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Обновить
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
