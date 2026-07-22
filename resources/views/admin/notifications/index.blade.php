@extends('layouts.app')

@section('title', 'Уведомления')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Уведомления</h1>
        <div>
            @if($notifications->where('read_at', null)->count() > 0)
                <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-check-double"></i> Отметить все как прочитанные
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($notifications->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $isUnread = $notification->read_at === null;
                            $type = $data['type'] ?? 'unknown';

                            $iconMap = [
                                'bank_statement_uploaded' => 'fa-file-invoice',
                                'payment_overdue' => 'fa-exclamation-triangle',
                                'new_user_registered' => 'fa-user-plus',
                                'new_rental_request' => 'fa-clipboard-list',
                                'new_order' => 'fa-shopping-cart',
                                'order_completed' => 'fa-check-circle',
                                'contact_message' => 'fa-envelope',
                            ];
                            $icon = $iconMap[$type] ?? 'fa-bell';

                            $colorMap = [
                                'bank_statement_uploaded' => 'primary',
                                'payment_overdue' => 'danger',
                                'new_user_registered' => 'success',
                                'new_rental_request' => 'info',
                                'new_order' => 'warning',
                                'order_completed' => 'success',
                                'contact_message' => 'secondary',
                            ];
                            $color = $colorMap[$type] ?? 'secondary';
                        @endphp
                        <div class="list-group-item list-group-item-action {{ $isUnread ? 'list-group-item-light' : '' }}">
                            <div class="d-flex w-100 align-items-start">
                                <div class="me-3 mt-1">
                                    <span class="badge bg-{{ $color }} rounded-circle p-2">
                                        <i class="fas {{ $icon }} fa-fw"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-1 {{ $isUnread ? 'fw-bold' : '' }}">
                                            {{ $data['title'] ?? 'Уведомление' }}
                                            @if($isUnread)
                                                <span class="badge bg-primary ms-2">Новое</span>
                                            @endif
                                        </h6>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div class="mt-1">
                                        @if(!empty($data['data']))
                                            <div class="row small">
                                                @foreach($data['data'] as $label => $value)
                                                    @if(!in_array($label, ['type', 'url']))
                                                        <div class="col-md-6 mb-1">
                                                            <span class="text-muted">{{ $label }}:</span>
                                                            <span class="fw-medium">{{ $value }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        @if(!$isUnread)
                                            <form action="{{ route('admin.notifications.mark-read', $notification->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-link p-0 me-2 text-decoration-none">
                                                    <i class="fas fa-check"></i> Перейти к событию
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.notifications.mark-read', $notification->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary p-1 px-2 me-2">
                                                    <i class="fas fa-check"></i> Отметить прочитанным
                                                </button>
                                            </form>
                                        @endif
                                        <small class="text-muted">
                                            {{ $notification->created_at->format('d.m.Y H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="card-footer">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Нет уведомлений</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
