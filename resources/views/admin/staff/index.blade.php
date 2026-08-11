@extends('layouts.app')
@section('title', 'Сотрудники')
@section('content')
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Сотрудники платформы</h4>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staffModal">+ Добавить сотрудника</button>
</div>
<div class="card shadow-sm">
<div class="card-body table-responsive">
<table class="table table-hover align-middle">
<thead><tr><th>Имя</th><th>Email</th><th>Роль</th><th></th></tr></thead>
<tbody>
@foreach($staff as $user)
<tr>
<td>{{ $user->name }}</td>
<td>{{ $user->email }}</td>
<td>@foreach($user->roles as $role)<span class="badge bg-info me-1">{{ $role->name }}</span>@endforeach</td>
<td>
<form action="{{ route('admin.staff.destroy', $user) }}" method="POST" class="d-inline">@csrf @method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить?')"><i class="bi bi-trash"></i></button></form>
</td>
</tr>
@endforeach
</tbody>
</table>
{{ $staff->links() }}
</div>
</div>
</div>

<div class="modal fade" id="staffModal"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<form method="POST" action="{{ route('admin.staff.store') }}">@csrf
<div class="modal-header"><h5 class="modal-title">Добавить сотрудника</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label>Имя *</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-2"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
<div class="mb-2"><label>Пароль *</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-2"><label>Роль *</label>
<select name="role" class="form-select" required>
@foreach($roles as $role)<option value="{{ $role }}">{{ $role }}</option>@endforeach
</select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button><button type="submit" class="btn btn-primary">Сохранить</button></div>
</form>
</div></div></div>
@endsection
