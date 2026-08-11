@extends('layouts.app')
@section('title', 'Путевой лист #' . $waybill->id)
@section('content')
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Путевой лист #{{ $waybill->id }}</h4>
<div>
<a href="{{ route('admin.orders.show', $waybill->order_id) }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> К заказу</a>
<form action="{{ route('admin.waybills.close', $waybill) }}" method="POST" class="d-inline">
@csrf
<button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Закрыть путевой лист?')"><i class="bi bi-check2-square"></i> Закрыть путевой лист</button>
</form>
</div>
</div>
<div class="card shadow-sm"><div class="card-body">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="row mb-3">
<div class="col-md-3"><strong>Оборудование:</strong> {{ $waybill->orderItem?->equipment?->title ?: '—' }}</div>
<div class="col-md-3"><strong>Оператор:</strong> {{ $waybill->operator?->full_name ?: 'Не назначен' }}</div>
<div class="col-md-3"><strong>Период:</strong> {{ $waybill->start_date->format('d.m.Y') }} — {{ $waybill->end_date->format('d.m.Y') }}</div>
<div class="col-md-3"><strong>Статус:</strong> {{ $waybill->status }}</div>
</div>
<form method="POST" action="{{ route('admin.waybills.update', $waybill) }}">
@csrf @method('PUT')
<div class="row mb-3">
<div class="col-md-6"><label class="form-label fw-semibold">Оператор платформы</label>
<select name="operator_id" class="form-select"><option value="">— не назначен —</option>
@foreach($operators as $op)<option value="{{ $op->id }}" @selected($waybill->operator_id === $op->id)>{{ $op->full_name }} ({{ $op->equipment?->title ?: 'нет техники' }})</option>@endforeach
</select></div>
<div class="col-md-6"><label class="form-label fw-semibold">Дата окончания</label>
<input type="date" name="end_date" value="{{ $waybill->end_date->format('Y-m-d') }}" class="form-control"></div>
</div>
<table class="table table-sm table-bordered"><thead><tr><th>Дата</th><th>Часы работы</th></tr></thead><tbody>
@foreach($waybill->shifts as $shift)
<tr><td>{{ \Carbon\Carbon::parse($shift->shift_date)->format('d.m.Y') }}</td>
<td><input type="number" step="0.5" min="0" max="24" class="form-control form-control-sm" name="shifts[{{ $shift->id }}][hours]" value="{{ $shift->hours_worked ?? 0 }}"></td></tr>
@endforeach
</tbody></table>
<button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить</button>
</form>
</div></div>
</div>
@endsection
