@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Новости компании</h1>
    
    <div class="row g-4">
        @foreach($news as $item)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ $item->title }}</h5>
                    <p class="text-muted small mb-2">
                        {{ $item->publish_date->format('d.m.Y') }}
                    </p>
                    <p class="card-text">{{ $item->excerpt }}</p>
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('news.show', $item->slug) }}" class="btn btn-sm btn-outline-primary">
                        Читать далее
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $news->links() }}
    </div>
</div>
@endsection