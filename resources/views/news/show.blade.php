@extends('layouts.app')
@section('title', $newsItem->title)
@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('news.index') }}">Новости</a></li>
            <li class="breadcrumb-item active">{{ Str::limit($newsItem->title, 40) }}</li>
        </ol>
    </nav>

    <article class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-{{ $newsItem->category === 'all' ? 'primary' : ($newsItem->category === 'lessee' ? 'success' : 'warning') }}">
                    {{ $newsItem->category === 'all' ? 'Для всех' : ($newsItem->category === 'lessee' ? 'Арендаторам' : 'Арендодателям') }}
                </span>
                <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i>{{ $newsItem->published_at?->format('d.m.Y') ?? $newsItem->created_at->format('d.m.Y') }}</span>
                <span class="small text-muted"><i class="bi bi-eye me-1"></i> {{ $newsItem->views_count }}</span>
            </div>

            <h1 class="fw-bold mb-4">{{ $newsItem->title }}</h1>

            @if($newsItem->excerpt)
                <div class="p-3 bg-soft-primary rounded-3 mb-4"><em class="text-muted">{{ $newsItem->excerpt }}</em></div>
            @endif

            <div class="news-content" style="line-height:1.8;font-size:1.0625rem;">{!! nl2br(e($newsItem->content)) !!}</div>
        </div>
    </article>

    <div class="mt-4">
        <a href="{{ route('news.index') }}" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i> Все новости</a>
    </div>
</div>
@endsection
