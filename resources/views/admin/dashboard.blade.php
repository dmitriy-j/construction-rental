@extends('layouts.app')

@section('title', 'Статистика')
@section('breadcrumbs', Breadcrumbs::render('admin-dashboard'))

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Панель управления</h1>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Всего новостей</h5>
                    <h2>{{ $stats['total_news'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Опубликовано</h5>
                    <h2>{{ $stats['published_news'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5>Черновиков</h5>
                    <h2>{{ $stats['draft_news'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Последние новости
            <a href="{{ route('admin.news.create') }}" class="btn btn-sm btn-primary float-end">
                <i class="bi bi-plus"></i> Добавить
            </a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Заголовок</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['last_news'] as $news)
                    <tr>
                        <td>{{ $news->title }}</td>
                        <td>{{ $news->publish_date->format('d.m.Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $news->is_published ? 'success' : 'warning' }}">
                                {{ $news->is_published ? 'Опубликовано' : 'Черновик' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.news.edit', $news->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title">Арендаторы</h5>
                    <p class="card-text display-4">42</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-success">
                <div class="card-body">
                    <h5 class="card-title">Арендодатели</h5>
                    <p class="card-text display-4">28</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title">Техника</h5>
                    <p class="card-text display-4">156</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card border-warning">
                <div class="card-body">
                    <h5 class="card-title">Заявки</h5>
                    <p class="card-text display-4">17</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Последние заявки
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Клиент</th>
                                <th>Статус</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#123</td>
                                <td>ООО "СтройТех"</td>
                                <td><span class="badge bg-warning">В обработке</span></td>
                                <td>15.07.2023</td>
                            </tr>
                            <tr>
                                <td>#122</td>
                                <td>ИП Иванов</td>
                                <td><span class="badge bg-success">Завершена</span></td>
                                <td>14.07.2023</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Статистика по месяцам
                </div>
                <div class="card-body">
                    <canvas id="statsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Простой пример графика
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('statsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Май', 'Июнь', 'Июль'],
                    datasets: [{
                        label: 'Заявки',
                        data: [45, 60, 40],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)'
                    }, {
                        label: 'Завершено',
                        data: [35, 50, 30],
                        backgroundColor: 'rgba(75, 192, 192, 0.5)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
@endsection
