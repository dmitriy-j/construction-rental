@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Результаты отчета</h1>
            <p>Период: {{ $request->start_date }} - {{ $request->end_date }}</p>
            <p>Тип отчета: {{ $request->report_type }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($request->report_type == 'turnover')
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Тип операции</th>
                            <th>Назначение</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $item)
                            <tr>
                                <td>{{ $item->date }}</td>
                                <td>{{ $item->type == 'debit' ? 'Поступление' : 'Списание' }}</td>
                                <td>{{ $item->purpose }}</td>
                                <td>{{ number_format($item->total, 2) }} ₽</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <pre>{{ print_r($data, true) }}</pre>
            @endif
        </div>
    </div>
</div>
@endsection
