@extends('layouts.app')
@section('title', 'Операторы платформы')
@section('content')
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Операторы платформы</h4>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#operatorModal" onclick="clearForm()">+ Добавить</button>
</div>
<div class="card shadow-sm">
<div class="card-body table-responsive">
<table class="table table-hover align-middle">
<thead><tr><th>ФИО</th><th>Телефон</th><th>Техника</th><th>Смена</th><th>Статус</th><th></th></tr></thead>
<tbody>
@foreach($operators as $op)
<tr>
<td>{{ $op->full_name }}</td>
<td>{{ $op->phone }}</td>
<td>{{ $op->equipment ? $op->equipment->title : '—' }}</td>
<td>{{ $op->shift_type_text }}</td>
<td>@if($op->is_active)<span class="badge bg-success">Активен</span>@else<span class="badge bg-secondary">Неактивен</span>@endif</td>
<td>
<form action="{{ route('admin.operators.destroy', $op) }}" method="POST" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить?')"><i class="bi bi-trash"></i></button></form>
</td>
</tr>
@endforeach
</tbody>
</table>
{{ $operators->links() }}
</div>
</div>
</div>

<div class="modal fade" id="operatorModal"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" id="operatorForm">
@csrf
<div class="modal-header"><h5 class="modal-title">Добавить оператора платформы</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label>ФИО *</label><input type="text" name="full_name" class="form-control" required></div>
<div class="mb-2"><label>Телефон</label><input type="text" name="phone" class="form-control"></div>
<div class="mb-2"><label>Номер удостоверения</label><input type="text" name="license_number" class="form-control"></div>
<div class="mb-2"><label>Квалификация</label><input type="text" name="qualification" class="form-control"></div>
<div class="mb-2"><label>Техника платформы</label>
<select name="equipment_id" class="form-select">
<option value="">— выбрать —</option>
@foreach($equipment as $eq)<option value="{{ $eq->id }}">{{ $eq->title }}</option>@endforeach
</select></div>
<div class="mb-2"><label>Смена</label>
<select name="shift_type" class="form-select"><option value="day">Дневная</option><option value="night">Ночная</option></select></div>
<div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked><label class="form-check-label">Активен</label></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button><button type="submit" class="btn btn-primary">Сохранить</button></div>
</form></div></div></div>
@endsection
@push('scripts')
<script>
function clearForm(){document.getElementById('operatorForm').reset();document.querySelector('#operatorForm input[name=is_active]').checked=true;}
</script>
@endpush
