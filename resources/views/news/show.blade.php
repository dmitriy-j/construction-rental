@extends('layouts.app')

@section('content')
<div class="container py-5">
    <article class="news-article">
        <div class="mb-4">
            <a href="{{ route('news.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
                ← Все новости
            </a>
            <h1>{{ $news->title }}</h1>
            <p class="text-muted">
                Опубликовано: {{ $news->publish_date->format('d.m.Y H:i') }}
            </p>
        </div>

        <div class="news-content">
            {!! nl2br(e($news->content)) !!}
        </div>
    </article>
</div>
@endsection