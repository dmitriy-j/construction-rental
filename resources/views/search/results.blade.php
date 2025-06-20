@extends('layouts.app')
@section('title', 'Поиск')
@section('content')

<h1 class="text-2xl font-bold mb-6">Результаты поиска</h1>

<div class="relative">
    <input type="text" id="search-input" placeholder="Поиск..." class="w-full border rounded px-3 py-2">
    <ul id="search-results" class="absolute z-10 mt-1 w-full bg-white border rounded shadow-md hidden"></ul>
</div>

@if($results->count())
    <ul class="mt-6 space-y-4">
        @foreach($results as $result)
            <li class="bg-white shadow rounded p-4 transition transform hover:scale-105">
                <h2 class="text-xl font-semibold">{{ $result->title }}</h2>
                <p class="text-sm text-gray-600">{{ $result->content_snippet }}</p>
            </li>
        @endforeach
    </ul>
@else
    <p class="mt-6 text-gray-500">Ничего не найдено.</p>
@endif

@push('scripts')
<script src="/js/search.js"></script>
@endpush
@endsection