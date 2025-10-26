@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>Финансовые отчеты</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Генерация отчета</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.reports.generate') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">Начальная дата</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">Конечная дата</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="report_type">Тип отчета</label>
                            <select class="form-control" id="report_type" name="report_type" required>
                                <option value="turnover">Обороты</option>
                                <option value="profit">Прибыль</option>
                                <option value="invoices">Счета</option>
                                <option value="upds">УПД</option>
                                <option value="company_balance">Балансы компаний</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="company_id">Компания (опционально)</label>
                            <select class="form-control" id="company_id" name="company_id">
                                <option value="">Все компании</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->legal_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Сгенерировать отчет</button>
            </form>
        </div>
    </div>
</div>
@endsection
