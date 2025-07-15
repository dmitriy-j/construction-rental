@extends('layouts.app')

@section('title', 'Удалить новость')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="alert alert-danger">
                <h4 class="alert-heading">Удаление новости</h4>
                <p>Вы действительно хотите удалить новость <strong>"{{ $news->title }}"</strong>?</p>
                <hr>
                <p class="mb-0">Это действие нельзя будет отменить.</p>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Отмена
                </a>

                <form action="{{ route('admin.news.destroy', $news->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Удалить
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
