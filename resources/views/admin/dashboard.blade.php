@extends('layouts.admin')

@section('title', 'Статистика')
@section('breadcrumbs', Breadcrumbs::render('admin-dashboard'))

@section('content')
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
