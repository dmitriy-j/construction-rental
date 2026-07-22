@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Уведомления</h1>
        <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-check-circle"></i> Прочитать все
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $isAdminNotification = ($data['type'] ?? '') !== 'contact_message';
            @endphp
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between">
                    <div>
                        @if($isAdminNotification && !empty($data['data']))
                            <div class="{{ $notification->unread() ? 'fw-bold' : '' }} mb-1">
                                {{ $data['title'] ?? 'Уведомление' }}
                            </div>
                            <div class="small text-muted">
                                @foreach($data['data'] as $label => $value)
                                    @if(!in_array($label, ['url', 'type']))
                                        <span class="me-2">{{ $label }}: {{ $value }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <a href="{{ $data['url'] ?? ($data['action_url'] ?? '#') }}"
                               class="{{ $notification->unread() ? 'fw-bold' : '' }}">
                                {{ $data['message'] ?? ($data['title'] ?? 'Уведомление') }}
                            </a>
                        @endif
                        <div class="text-muted small mt-1">
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                    </div>
                    @if($notification->unread())
                    <form action="{{ route('notifications.markAsRead', $notification) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-link text-success" title="Отметить как прочитанное">
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted">Нет уведомлений</p>
            </div>
            @endforelse
        </div>
        @if($notifications->hasPages())
        <div class="card-footer">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
