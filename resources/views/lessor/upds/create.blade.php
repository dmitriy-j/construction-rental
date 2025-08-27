@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Загрузка УПД по акту выполненных работ</h1>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('lessor.upds.index') }}" class="btn btn-secondary">Назад к списку</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('lessor.upds.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="waybill_id">Путевой лист с актом выполненных работ *</label>
                    <select class="form-control" id="waybill_id" name="waybill_id" required>
                        <option value="">Выберите путевой лист</option>
                        @foreach($waybills as $waybill)
                            <option value="{{ $waybill->id }}" {{ old('waybill_id') == $waybill->id ? 'selected' : '' }}>
                                Путевой лист #{{ $waybill->number }} - // Используем номер вместо ID
                                Заказ #{{ $waybill->order->id }} -
                                {{ $waybill->order->lesseeCompany->legal_name }}
                                ({{ $waybill->order->start_date->format('d.m.Y') }} - {{ $waybill->order->end_date->format('d.m.Y') }})
                                - Акт №{{ $waybill->completionAct->number ?? 'N/A' }}
                                - Сумма: {{ number_format($waybill->completionAct->total_amount ?? 0, 2) }} ₽
                            </option>
                        @endforeach
                    </select>
                    @error('waybill_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="upd_file">Файл УПД (Excel) *</label>
                    <input type="file" class="form-control-file" id="upd_file" name="upd_file" accept=".xlsx,.xls" required>
                    <small class="form-text text-muted">
                        Загрузите файл УПД в формате Excel. Файл будет обработан согласно настроенному шаблону вашей компании.
                        Максимальный размер: 10 МБ.
                    </small>
                    @error('upd_file')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-info">
                    <h5>Требования к файлу УПД:</h5>
                    <ul>
                        <li>Файл должен соответствовать шаблону вашей компании</li>
                        <li>Данные в УПД должны соответствовать данным заказа и акта выполненных работ</li>
                        <li>ИНН и наименования компаний должны совпадать с данными в системе</li>
                        <li>Суммы в УПД должны соответствовать суммам в заказе (допускается отклонение до 1%)</li>
                        <li>Период оказания услуг должен совпадать с периодом аренды</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary">Загрузить УПД</button>
                <a href="{{ route('lessor.upds.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </div>
    </div>
</div>
@endsection
