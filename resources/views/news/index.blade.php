@extends('layouts.app')
@section('title', 'Новости')
@section('content')

<h1 class="text-2xl font-bold mb-6">Новости</h1>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach ($news as $item)
        <div class="bg-white shadow rounded-lg p-4 relative hover:shadow-lg transition">
            @if($item->is_urgent)
                <span class="absolute top-2 right-2 text-red-500">
                    <i class="fas fa-fire"></i>
                </span>
            @endif
            <h2 class="text-xl font-semibold">{{ $item->title }}</h2>
            <p class="text-sm text-gray-600 mt-2">{!! Str::limit($item->content, 100) !!}</p>
            <a href="/news/{{ $item->id }}" class="mt-4 inline-block text-blue-500 hover:underline">Подробнее</a>
        </div>
    @endforeach
</div>

<div class="mt-6">
    {{ $news->links() }}
</div>

@endsection