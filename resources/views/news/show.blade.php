@extends('layouts.app')
@section('title', $newsItem->title)
@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('news.index') }}">Новости</a></li>
            <li class="breadcrumb-item active">{{ $newsItem->title }}</li>
        </ol>
    </nav>
    <article class="card">
        <div class="card-body">
            <h1 class="card-title mb-2">{{ $newsItem->title }}</h1>
            <div class="text-muted small mb-3">
                <span class="badge bg-{{ $newsItem->category === 'all' ? 'info' : ($newsItem->category === 'lessee' ? 'success' : 'warning') }} me-1">
                    {{ $newsItem->category === 'all' ? 'Для всех' : ($newsItem->category === 'lessee' ? 'Арендаторам' : 'Арендодателям') }}
                </span>
                {{ $newsItem->published_at?->format('d.m.Y') ?? $newsItem->created_at->format('d.m.Y') }}
                <span class="ms-2"><i class="fas fa-eye"></i> {{ $newsItem->views_count }}</span>
            </div>
            @if($newsItem->excerpt)
                <div class="p-3 bg-light rounded mb-3"><em>{{ $newsItem->excerpt }}</em></div>
            @endif
            <div class="news-content" style="line-height:1.8;">{!! nl2br(e($newsItem->content)) !!}</div>
        </div>
    </article>
    <div class="mt-3">
        <a href="{{ route('news.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Все новости</a>
    </div>
</div>
@endsection
