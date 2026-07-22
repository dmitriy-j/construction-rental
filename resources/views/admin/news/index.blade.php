@extends('layouts.app')

@section('title', 'Новости')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <h1 class="h3 mb-0">Новости</h1>
        <div class="page-actions">
            <a href="{{ route('admin.news.create') }}" class="btn btn-primary btn-block-mobile">
                <i class="fas fa-plus"></i> Создать новость
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Заголовок</th>
                            <th class="table-mobile-hide-md">Категория</th>
                            <th class="table-mobile-hide-md">Статус</th>
                            <th class="table-mobile-hide-md">Просмотры</th>
                            <th class="table-mobile-hide-md">Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($news as $item)
                        <tr>
                            <td>
                                <a href="{{ route('admin.news.show', $item) }}" class="fw-medium">{{ $item->title }}</a>
                                <div class="small text-muted d-md-none">
                                    {{ $item->categoryName }} · {{ $item->published_at?->format('d.m.Y') ?? $item->created_at->format('d.m.Y') }}
                                </div>
                            </td>
                            <td class="table-mobile-hide-md">
                                <span class="badge bg-{{ $item->category === 'all' ? 'info' : ($item->category === 'lessee' ? 'success' : 'warning') }}">
                                    {{ $item->category === 'all' ? 'Все' : ($item->category === 'lessee' ? 'Арендаторы' : 'Арендодатели') }}
                                </span>
                            </td>
                            <td class="table-mobile-hide-md">
                                @if($item->is_active)
                                    <span class="badge bg-success">Опубликовано</span>
                                @else
                                    <span class="badge bg-secondary">Черновик</span>
                                @endif
                            </td>
                            <td class="table-mobile-hide-md">{{ $item->views_count }}</td>
                            <td class="table-mobile-hide-md">{{ $item->published_at?->format('d.m.Y') ?? $item->created_at->format('d.m.Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.news.edit', $item) }}" class="btn btn-outline-primary" title="Редактировать"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.news.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить новость?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger" title="Удалить"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Новостей пока нет</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($news->hasPages())
            <div class="mt-3">{{ $news->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
