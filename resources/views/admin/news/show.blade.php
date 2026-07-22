@extends('layouts.app')
@section('title', $news->title)
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-4 page-header">
        <h1 class="h3 mb-0">{{ $news->title }}</h1>
        <div class="ms-auto page-actions">
            <a href="{{ route('admin.news.edit', $news) }}" class="btn btn-primary btn-block-mobile"><i class="fas fa-edit"></i> Редактировать</a>
            <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary btn-block-mobile"><i class="fas fa-arrow-left"></i> Назад</a>
        </div>
    </div>
    <div class="card"><div class="card-body">
        <div class="mb-3 text-muted small">
            <span class="badge bg-{{ $news->category === 'all' ? 'info' : ($news->category === 'lessee' ? 'success' : 'warning') }}">{{ $news->category === 'all' ? 'Для всех' : ($news->category === 'lessee' ? 'Арендаторы' : 'Арендодатели') }}</span>
            @if($news->is_active) <span class="badge bg-success ms-1">Опубликовано</span> @else <span class="badge bg-secondary ms-1">Черновик</span> @endif
            <span class="ms-2"><i class="fas fa-eye"></i> {{ $news->views_count }}</span>
            <span class="ms-2"><i class="fas fa-user"></i> {{ $news->author?->name }}</span>
            <span class="ms-2"><i class="fas fa-calendar"></i> {{ $news->published_at?->format('d.m.Y H:i') ?? $news->created_at->format('d.m.Y H:i') }}</span>
        </div>
        @if($news->excerpt)<div class="mb-3 p-3 bg-light rounded"><em>{{ $news->excerpt }}</em></div>@endif
        <div class="news-content">{!! nl2br(e($news->content)) !!}</div>
    </div></div>
</div>
@endsection
