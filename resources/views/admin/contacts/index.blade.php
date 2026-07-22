@extends('layouts.app')

@section('title', 'Обращения — Админ-панель')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold"><i class="bi bi-envelope me-2"></i> Обращения с сайта</h1>
            <p class="text-muted mb-0 small">Сообщения из формы обратной связи</p>
        </div>
        <span class="badge bg-primary fs-6 px-3 py-2"><i class="bi bi-chat-dots me-1"></i> {{ $messages->total() }} всего</span>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($messages->count() > 0)
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Сообщение</th>
                        <th style="width:100px;">Дата</th>
                        <th style="width:100px;">Статус</th>
                        <th style="width:100px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $msg)
                    <tr class="{{ !$msg->is_read ? 'table-active' : '' }}">
                        <td class="text-muted">{{ $msg->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2"
                                     style="width:32px;height:32px;background:linear-gradient(135deg,#0B5ED7,#002D72);min-width:32px;">
                                    <span class="text-white fw-bold small">{{ mb_substr($msg->name, 0, 1) }}</span>
                                </div>
                                <span class="{{ !$msg->is_read ? 'fw-semibold' : '' }}">{{ $msg->name }}</span>
                            </div>
                        </td>
                        <td><a href="tel:{{ $msg->phone }}" class="text-decoration-none small"><i class="bi bi-telephone me-1"></i>{{ $msg->phone }}</a></td>
                        <td>@if($msg->email)<a href="mailto:{{ $msg->email }}" class="text-decoration-none small"><i class="bi bi-envelope me-1"></i>{{ $msg->email }}</a>@else<span class="text-muted small">—</span>@endif</td>
                        <td><span class="text-truncate d-inline-block small" style="max-width:200px;">{{ $msg->message ?? '—' }}</span></td>
                        <td><span class="small text-nowrap"><i class="bi bi-calendar3 me-1"></i>{{ $msg->created_at->format('d.m.Y') }}</span><br><span class="small text-muted">{{ $msg->created_at->format('H:i') }}</span></td>
                        <td>@if($msg->is_read)<span class="badge bg-success"><i class="bi bi-check2 me-1"></i> Прочитано</span>@else<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Новое</span>@endif</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                @if(!$msg->is_read)
                                <form action="{{ route('admin.contacts.mark-read', $msg) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button type="submit" class="btn btn-outline-primary" title="Прочитано"><i class="bi bi-check2"></i></button></form>
                                @endif
                                <form action="{{ route('admin.contacts.destroy', $msg) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?');">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger" title="Удалить"><i class="bi bi-trash"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($messages->hasPages())<div class="card-footer">{{ $messages->links() }}</div>@endif
    </div>
    @else
    <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size:4rem;"></i>
            <h4 class="mt-3 text-muted">Нет обращений</h4>
            <p class="text-muted">Пока ни один пользователь не отправил сообщение через форму на сайте.</p>
        </div>
    </div>
    @endif
</div>
@endsection
