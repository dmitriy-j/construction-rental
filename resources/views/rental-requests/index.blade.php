@extends('layouts.app')

@section('title', 'Публичные заявки')

@section('content')
    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">Заявки на аренду</h1>
        <div id="rental-requests-app">
            <rental-requests />
        </div>
    </div>
@endsection
