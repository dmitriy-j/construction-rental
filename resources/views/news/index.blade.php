@extends('layouts.app')
@section('title', 'Новости')
@section('content')
<div class="container py-4">
    <div class="mb-5">
        <h1 class="fw-bold mb-2">Новости</h1>
        <p class="text-muted">Будьте в курсе последних событий и обновлений платформы</p>
    </div>

    @forelse($news as $item)
    <div class="card border-0 shadow-sm mb-4 card-hover">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-{{ $item->category === 'all' ? 'primary' : ($item->category === 'lessee' ? 'success' : 'warning') }}">
                    {{ $item->category === 'all' ? 'Для всех' : ($item->category === 'lessee' ? 'Арендаторам' : 'Арендодателям') }}
                </span>
                <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i>{{ $item->published_at?->format('d.m.Y') ?? $item->created_at->format('d.m.Y') }}</span>
            </div>
            <h4 class="fw-bold mb-2">
                <a href="{{ route('news.show', $item->slug) }}" class="text-decoration-none text-dark stretched-link">{{ $item->title }}</a>
            </h4>
            <p class="card-text text-muted mb-0">{{ $item->excerpt ?? Str::limit(strip_tags($item->content), 200) }}</p>
        </div>
    </div>
    @empty
    <div class="text-center py-5"><i class="fas fa-newspaper text-muted" style="font-size:4rem;"></i><p class="mt-3 text-muted fs-5">Новостей пока нет</p></div>
    @endforelse

    @if($news->hasPages())<div class="mt-4">{{ $news->links() }}</div>@endif
</div>
@endsection
