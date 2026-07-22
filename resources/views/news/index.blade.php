@extends('layouts.app')
@section('title', 'Новости')
@section('content')
<div class="container py-4">
    <h1 class="mb-4">Новости</h1>
    @forelse($news as $item)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="card-title mb-1">
                        <a href="{{ route('news.show', $item->slug) }}" class="text-decoration-none">{{ $item->title }}</a>
                    </h5>
                    <div class="small text-muted mb-2">
                        <span class="badge bg-{{ $item->category === 'all' ? 'info' : ($item->category === 'lessee' ? 'success' : 'warning') }} me-1">
                            {{ $item->category === 'all' ? 'Для всех' : ($item->category === 'lessee' ? 'Арендаторам' : 'Арендодателям') }}
                        </span>
                        {{ $item->published_at?->format('d.m.Y') ?? $item->created_at->format('d.m.Y') }}
                    </div>
                    <p class="card-text">{{ $item->excerpt ?? Str::limit(strip_tags($item->content), 200) }}</p>
                </div>
            </div>
            <a href="{{ route('news.show', $item->slug) }}" class="btn btn-sm btn-outline-primary">Читать →</a>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted"><i class="fas fa-newspaper fa-3x mb-3"></i><p>Новостей пока нет</p></div>
    @endforelse
    @if($news->hasPages())<div class="mt-3">{{ $news->links() }}</div>@endif
</div>
@endsection
