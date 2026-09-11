@extends('layouts.app')
@section('title', 'Путевой лист #' . $waybill->id)

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Путевой лист #{{ $waybill->number ?: $waybill->id }}</h4>
    <div>
      <a href="{{ route('admin.orders.show', $waybill->order_id) }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> К заказу</a>
      <a href="{{ route('admin.documents.index', ['type' => 'waybills']) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list"></i> Список ПЛ</a>
      @if(in_array($waybill->status, ['active','future']))
      <form action="{{ route('admin.waybills.close', $waybill) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Закрыть путевой лист?')"><i class="bi bi-check2-square"></i> Закрыть путевой лист</button>
      </form>
      @endif
    </div>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  <div class="card shadow-sm mb-3">
    <div class="card-header bg-info text-white">
      <div class="d-flex flex-wrap justify-content-between align-items-center">
        <span><strong>Статус:</strong> {{ $waybill->status_text }}</span>
        <span><strong>Период:</strong> {{ $waybill->start_date->format('d.m.Y') }} — {{ $waybill->end_date->format('d.m.Y') }}</span>
        <span><strong>Смена:</strong> {{ $waybill->shift_type_text }}</span>
      </div>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3"><strong>Оборудование:</strong> {{ $waybill->orderItem?->equipment?->title ?: '—' }}</div>
        <div class="col-md-3"><strong>Заказ:</strong> #{{ $waybill->order_id }}</div>
        <div class="col-md-3"><strong>Ставка:</strong> {{ number_format($baseHourlyRate, 2) }} ₽/час</div>
        <div class="col-md-3"><strong>Перспектива:</strong> {{ $waybill->perspective === 'platform' ? 'Платформа' : $waybill->perspective }}</div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap gap-4">
        <span>Заполнено: <strong>{{ $filledShifts }}/{{ $totalShifts }}</strong> смен</span>
        <span>Отработано: <strong>{{ number_format($totalHours, 2) }}</strong> часов</span>
        <span>Итоговая сумма: <strong>{{ number_format($totalHours * $baseHourlyRate, 2) }} ₽</strong></span>
      </div>
      <div class="progress mt-2" style="height:8px;">
        <div class="progress-bar bg-success" style="width: {{ $totalShifts > 0 ? ($filledShifts / $totalShifts) * 100 : 0 }}%"></div>
      </div>
    </div>
  </div>

  @if($selectedShift)
  <div class="card shadow-sm mb-3">
    <div class="card-header"><h5 class="mb-0">Смена от {{ $selectedShift->shift_date->format('d.m.Y') }}</h5></div>
    <div class="card-body">
      <form method="POST" action="{{ route('admin.waybills.update', $waybill) }}">
        @csrf @method('PUT')
        <input type="hidden" name="shift_id" value="{{ $selectedShift->id }}">
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <label class="form-label fw-semibold">Дата смены</label>
            <input type="date" name="shift_date" value="{{ $selectedShift->shift_date->format('Y-m-d') }}" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Оператор платформы</label>
            <select name="operator_id" class="form-select">
              <option value="">— не назначен —</option>
              @foreach($operators as $op)
              <option value="{{ $op->id }}" @selected($waybill->operator_id === $op->id)>{{ $op->full_name }} ({{ $op->equipment?->title ?: 'нет техники' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-semibold">Объект</label>
            <input type="text" name="object_name" value="{{ $selectedShift->object_name }}" class="form-control" placeholder="Название объекта">
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-3"><label class="form-label">Выезд (время)</label><input type="time" name="departure_time" value="{{ $selectedShift->departure_time ? \Carbon\Carbon::parse($selectedShift->departure_time)->format('H:i') : '' }}" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Возвращение (время)</label><input type="time" name="return_time" value="{{ $selectedShift->return_time ? \Carbon\Carbon::parse($selectedShift->return_time)->format('H:i') : '' }}" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Часы работы</label><input type="number" step="0.1" min="0" max="24" name="hours_worked" value="{{ $selectedShift->hours_worked ?? 0 }}" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Простой (часы)</label><input type="number" step="0.1" min="0" max="24" name="downtime_hours" value="{{ $selectedShift->downtime_hours ?? 0 }}" class="form-control"></div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-2"><label class="form-label">Одометр нач.</label><input type="number" step="0.1" name="odometer_start" value="{{ $selectedShift->odometer_start }}" class="form-control"></div>
          <div class="col-md-2"><label class="form-label">Одометр кон.</label><input type="number" step="0.1" name="odometer_end" value="{{ $selectedShift->odometer_end }}" class="form-control"></div>
          <div class="col-md-2"><label class="form-label">Топливо нач.</label><input type="number" step="0.1" name="fuel_start" value="{{ $selectedShift->fuel_start }}" class="form-control"></div>
          <div class="col-md-2"><label class="form-label">Топливо кон.</label><input type="number" step="0.1" name="fuel_end" value="{{ $selectedShift->fuel_end }}" class="form-control"></div>
          <div class="col-md-2"><label class="form-label">Заправка (л)</label><input type="number" step="0.1" name="fuel_refilled_liters" value="{{ $selectedShift->fuel_refilled_liters }}" class="form-control"></div>
          <div class="col-md-2"><label class="form-label">Тип топлива</label><input type="text" name="fuel_refilled_type" value="{{ $selectedShift->fuel_refilled_type }}" class="form-control"></div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-4"><label class="form-label">Причина простоя</label><input type="text" name="downtime_cause" value="{{ $selectedShift->downtime_cause }}" class="form-control"></div>
          <div class="col-md-8"><label class="form-label">Описание работ</label><input type="text" name="work_description" value="{{ $selectedShift->work_description }}" class="form-control"></div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить смену</button>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Смены путевого листа</h5>
      <form action="{{ route('admin.waybills.store-shift', $waybill) }}" method="POST" class="d-inline-flex align-items-center gap-2">
        @csrf
        <input type="date" name="shift_date" class="form-control form-control-sm" required value="{{ ($waybill->shifts->last()?->shift_date?->copy()->addDay() ?? now())->format('Y-m-d') }}">
        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Добавить смену</button>
      </form>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead><tr><th>Дата</th><th>Объект</th><th>Выезд</th><th>Возвр.</th><th>Часы</th><th>Простой</th><th>Одометр</th><th>Топливо</th><th>Сумма</th><th></th></tr></thead>
        <tbody>
        @foreach($waybill->shifts as $shift)
          <tr class="{{ $shift->id === $selectedShift->id ? 'table-primary' : '' }}">
            <td><a href="{{ route('admin.waybills.show', ['waybill' => $waybill, 'shift_id' => $shift->id]) }}">{{ $shift->shift_date->format('d.m.Y') }}</a></td>
            <td>{{ $shift->object_name }}</td>
            <td>{{ $shift->departure_time ? \Carbon\Carbon::parse($shift->departure_time)->format('H:i') : '' }}</td>
            <td>{{ $shift->return_time ? \Carbon\Carbon::parse($shift->return_time)->format('H:i') : '' }}</td>
            <td>{{ $shift->hours_worked }}</td>
            <td>{{ $shift->downtime_hours }}</td>
            <td>{{ $shift->odometer_start }} - {{ $shift->odometer_end }}</td>
            <td>{{ $shift->fuel_start }} - {{ $shift->fuel_end }}</td>
            <td>{{ number_format($shift->total_amount ?? 0, 2) }} ₽</td>
            <td>
              @if($waybill->shifts->count() > 1)
              <form action="{{ route('admin.waybills.destroy-shift', ['waybill' => $waybill, 'shift' => $shift]) }}" method="POST" class="d-inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить смену?')"><i class="bi bi-trash"></i></button>
              </form>
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @else
  <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Нет смен для заполнения</div>
  @endif
</div>
@endsection
