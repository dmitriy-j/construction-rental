@extends('layouts.app')  <!-- Указываем, какой шаблон используем -->

@section('title', 'Главная страница')  <!-- Устанавливаем title -->

@section('content')  <!-- Вставляем контент в секцию 'content' -->
    <div class="container">
        <h1 class="mb-4">Диман! Добро пожаловать на лучший сайт епта!</h1>
        <div class="card">
            <div class="card-body">
                Это главная страница вашего проекта.
            </div>
        </div>
    </div>
@endsection